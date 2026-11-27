<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Services\DocumentService;
use Illuminate\Support\Facades\Log;
use App\Models\ActivityLog;
use App\Models\DetailProgress;
use App\Models\Dokumentasi;
use App\Models\Kwitansi;
use App\Models\Lpd;
use App\Models\MasterDesaKelurahan;
use App\Models\MasterPermasalahanLpd;
use App\Models\MasterSls;
use App\Models\MasterSubSls;
use App\Models\MasterUraianLpd;
use App\Models\Penugasan;
use App\Models\ProgressHarian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ProgressController extends Controller
{
    private const MAX_PHOTOS = 10;

    private function owned(Penugasan $penugasan): void
    {
        abort_unless($penugasan->petugas()->where('user_id', auth()->id())->exists(), 404);
    }

    private function choices(Penugasan $penugasan): array
    {
        $scope = fn ($query) => $query->whereNull('kegiatan_id')->orWhere('kegiatan_id', $penugasan->kegiatan_id);

        return [
            'uraian' => MasterUraianLpd::where($scope)
                ->where('is_active', true)
                ->orderBy('tipe_uraian')
                ->orderByRaw("CASE WHEN LOWER(TRIM(nama_uraian)) = 'aktivitas lainnya' THEN 1 ELSE 0 END")
                ->orderBy('nama_uraian')
                ->get(),
            'masalah' => MasterPermasalahanLpd::where($scope)
                ->where('is_active', true)
                ->orderByRaw("CASE WHEN LOWER(TRIM(nama_permasalahan)) IN ('masalah lainnya', 'kendala lainnya') THEN 1 ELSE 0 END")
                ->orderBy('nama_permasalahan')
                ->get(),
        ];
    }

    public function desaOptions(Penugasan $penugasan)
    {
        $this->owned($penugasan);

        return response()->json(
            MasterDesaKelurahan::where('kecamatan_id', $penugasan->kecamatan_id)
                ->whereHas('sls')
                ->orderBy('jenis_wilayah')
                ->orderBy('nama_wilayah')
                ->get(['id', 'nama_wilayah', 'jenis_wilayah'])
        );
    }

    public function blokSensusOptions(Penugasan $penugasan)
    {
        $this->owned($penugasan);
        $penugasan->loadMissing('kecamatan');

        $rows = collect($this->blockSensusRows($penugasan->kecamatan->nama_kecamatan));
        $masterVillages = MasterDesaKelurahan::where('kecamatan_id', $penugasan->kecamatan_id)->get();
        $aliases = $this->villageAliases();

        $villages = $rows->groupBy('desa')->map(function ($villageRows, $sourceName) use ($masterVillages, $aliases) {
            $canonical = $aliases[mb_strtoupper(trim($sourceName))] ?? $sourceName;
            $master = $masterVillages->first(fn ($item) => $this->normalizeName($item->nama_wilayah) === $this->normalizeName($canonical));

            return [
                'id' => $master?->id,
                'nama_wilayah' => $sourceName,
                'jenis_wilayah' => $master?->jenis_wilayah ?? 'desa',
                'blok_sensus' => $villageRows->pluck('blok_sensus')->unique()->values()->all(),
            ];
        })->values();

        return response()->json(['villages' => $villages]);
    }

    public function slsOptions(Penugasan $penugasan, MasterDesaKelurahan $desa)
    {
        $this->owned($penugasan);
        abort_unless((int) $desa->kecamatan_id === (int) $penugasan->kecamatan_id, 404);

        return response()->json([
            'sls' => MasterSls::where('desa_kelurahan_id', $desa->id)->orderBy('nmsls')->get(['id', 'nmsls']),
            'sub_sls' => MasterSubSls::whereHas('sls', fn ($query) => $query->where('desa_kelurahan_id', $desa->id))
                ->with('sls:id,nmsls')->orderBy('nmsubsls')->get(['id', 'sls_id', 'nmsubsls']),
        ]);
    }

    private function ensureOpen(Penugasan $penugasan): void
    {
        abort_unless($penugasan->kegiatan->status === 'aktif', 403, 'Kegiatan nonaktif.');
    }

    public function index(Penugasan $penugasan)
    {
        $this->owned($penugasan);
        $penugasan->load('kegiatan', 'kecamatan');
        $progress = $penugasan->progress()->with('details.dokumentasi')->orderByDesc('tanggal')->paginate(15);

        return view('petugas.progress.index', compact('penugasan', 'progress'));
    }

    public function create(Penugasan $penugasan)
    {
        $this->owned($penugasan);
        $penugasan->load('kegiatan', 'kecamatan');
        abort_unless($penugasan->kegiatan->status === 'aktif', 403, 'Kegiatan nonaktif.');

        return view('petugas.progress.form', [
            'penugasan' => $penugasan,
            'progress' => new ProgressHarian,
            'details' => collect(),
            'selectedUraian' => [],
            'selectedMasalah' => [],
            'uraianLainnya' => '',
            'permasalahanLainnya' => '',
        ]);
    }

    public function store(Request $request, Penugasan $penugasan)
    {
        $this->owned($penugasan);
        $this->ensureOpen($penugasan);
        $data = $this->validatePayload($request, $penugasan);

        // Satu penugasan hanya memiliki satu induk progress per tanggal.
        // Jika tanggal sudah ada, arahkan ke mode edit agar detail lama tidak tertimpa.
        $existing = $penugasan->progress()->whereDate('tanggal', $data['tanggal'])->first();
        if ($existing) {
            return redirect()->route('petugas.progress.edit', [$penugasan, $existing])
                ->with('status', 'Progress pada tanggal tersebut sudah tersedia. Anda dapat melanjutkan atau mengedit detail yang ada.');
        }

        $disk = Storage::disk('local');
        $uploadedPaths = [];
        try {
            $day = DB::transaction(function () use ($data, $penugasan, $request, &$uploadedPaths) {
                $day = ProgressHarian::firstOrCreate([
                    'penugasan_id' => $penugasan->id,
                    'tanggal' => $data['tanggal'],
                ]);
                $this->syncDetails($day, $data, $penugasan, !$day->wasRecentlyCreated, $uploadedPaths);
                $this->syncSelections($day);
                $this->invalidateDocs($penugasan, [$day->tanggal->toDateString()]);
                $this->audit($request, 'create', $day, null, [
                    'tanggal' => $day->tanggal->toDateString(),
                    'jumlah_detail' => count($data['details']),
                ]);

                return $day;
            });
        } catch (\Throwable $exception) {
            foreach ($uploadedPaths as $orphanPath) {
                $disk->delete($orphanPath);
            }
            throw $exception;
        }

        $this->refreshAutomaticLpd($penugasan, $day);
        return redirect()->route('petugas.progress.index', $penugasan)->with('status', 'Progress harian berhasil disimpan. Dokumen LPD diproses otomatis.');
    }

    public function edit(Penugasan $penugasan, ProgressHarian $progress)
    {
        $this->owned($penugasan);
        abort_unless($progress->penugasan_id === $penugasan->id, 404);
        $penugasan->load('kegiatan', 'kecamatan');
        $progress->load('details.dokumentasi', 'details.uraian', 'details.permasalahan', 'uraian', 'permasalahan');

        foreach ($progress->details as $detail) {
            if (!$detail->desa_kelurahan_id && ($detail->jenis_data_wilayah ?? 'sls') !== 'blok_sensus') {
                $village = MasterDesaKelurahan::where('kecamatan_id', $penugasan->kecamatan_id)
                    ->where('nama_wilayah', $detail->desa)->first();
                if ($village) {
                    $detail->desa_kelurahan_id = $village->id;
                    $detail->jenis_wilayah = $village->jenis_wilayah;
                    $slsName = explode(' / ', $detail->sls, 2)[0];
                    $sls = MasterSls::where('desa_kelurahan_id', $village->id)->where('nmsls', $slsName)->first();
                    if ($sls) {
                        $detail->sls_id = $sls->id;
                        if (str_contains($detail->sls, ' / ')) {
                            $subName = explode(' / ', $detail->sls, 2)[1];
                            $detail->sub_sls_id = MasterSubSls::where('sls_id', $sls->id)->where('nmsubsls', $subName)->value('id');
                        }
                    }
                }
            }
        }

        if ($progress->details->isNotEmpty()) {
            $firstDetail = $progress->details->first();
            if ($firstDetail->uraian->isEmpty() && $progress->uraian->isNotEmpty()) {
                $firstDetail->setRelation('uraian', $progress->uraian);
            }
            if ($firstDetail->permasalahan->isEmpty() && $progress->permasalahan->isNotEmpty()) {
                $firstDetail->setRelation('permasalahan', $progress->permasalahan);
            }
        }

        return view('petugas.progress.form', [
            'penugasan' => $penugasan,
            'progress' => $progress,
            'details' => $progress->details,
            'selectedUraian' => [],
            'selectedMasalah' => [],
            'uraianLainnya' => '',
            'permasalahanLainnya' => '',
        ]);
    }

    public function update(Request $request, Penugasan $penugasan, ProgressHarian $progress)
    {
        $this->owned($penugasan);
        abort_unless($progress->penugasan_id === $penugasan->id, 404);
        $this->ensureOpen($penugasan);
        $data = $this->validatePayload($request, $penugasan, true, $progress);
        $before = $progress->load('details')->toArray();

        $disk = Storage::disk('local');
        $uploadedPaths = [];
        try {
            DB::transaction(function () use ($data, $progress, $penugasan, $request, $before, &$uploadedPaths) {
                $other = ProgressHarian::where('penugasan_id', $penugasan->id)
                    ->whereDate('tanggal', $data['tanggal'])
                    ->where('id', '!=', $progress->id)->exists();
                abort_if($other, 422, 'Sudah ada progress pada tanggal tersebut untuk penugasan ini.');

                $oldDate = $progress->tanggal->toDateString();
                $progress->update(['tanggal' => $data['tanggal']]);
                $this->syncDetails($progress, $data, $penugasan, true, $uploadedPaths);
                $this->syncSelections($progress);
                $this->invalidateDocs($penugasan, [$oldDate, $data['tanggal']]);
                $this->audit($request, 'update', $progress, $before, $progress->fresh()->load('details')->toArray());
            });
        } catch (\Throwable $exception) {
            foreach ($uploadedPaths as $orphanPath) {
                $disk->delete($orphanPath);
            }
            throw $exception;
        }

        $this->refreshAutomaticLpd($penugasan, $progress->fresh());
        return redirect()->route('petugas.progress.index', $penugasan)->with('status', 'Progress berhasil diperbarui. Dokumen LPD diproses otomatis.');
    }

    private function validatePayload(Request $request, Penugasan $penugasan, bool $editing = false, ?ProgressHarian $currentProgress = null): array
    {
        $kegiatan = $penugasan->kegiatan;

        // Form dokumentasi menggunakan struktur sls[1][dokumentasi_files][];
        // normalisasikan ke key foto agar alur validasi dan penyimpanan yang sudah ada tetap dipakai.
        $nestedPhotos = $request->file('sls.1.dokumentasi_files', []);
        if (!empty($nestedPhotos)) {
            $request->files->set('foto', $nestedPhotos);
        }
        // Metadata dikirim dengan indeks yang sama seperti input file agar foto galeri
        // dan kamera tetap dapat dipasangkan meskipun ada baris upload kosong.
        $documentationMetadata = $request->input('sls.1.dokumentasi_metadata', []);

        $outputRules = [];

        $rules = [
            'tanggal' => ['required', 'date', 'after_or_equal:' . $kegiatan->tanggal_mulai->format('Y-m-d'), 'before_or_equal:' . $kegiatan->tanggal_selesai->format('Y-m-d')],
            'details' => ['required', 'array', 'min:1'],
            'details.*.id' => ['nullable', 'integer'],
            'details.*.jenis_data_wilayah' => ['required', Rule::in([$kegiatan->jenis_data_wilayah ?? 'sls'])],
            'details.*.jenis_wilayah' => ['required', 'in:desa,kelurahan'],
            'details.*.desa_kelurahan_id' => ['nullable', 'integer', 'exists:master_desa_kelurahan,id'],
            'details.*.sls_id' => ['nullable', 'integer', 'exists:master_sls,id'],
            'details.*.sub_sls_id' => [$kegiatan->jenis_data_wilayah === 'sls' ? 'required' : 'nullable', 'integer', 'exists:master_sub_sls,id'],
            'details.*.blok_sensus' => ['nullable', 'string', 'max:20'],
            'details.*.desa' => ['required', 'string', 'max:150'],
            'details.*.sls' => ['nullable', 'string', 'max:150'],
            'details.*.perjalanan_jam_mulai' => ['required', 'date_format:H:i'],
            'details.*.perjalanan_jam_selesai' => ['required', 'date_format:H:i'],
            'details.*.perjalanan_uraian' => ['required', 'string', 'max:2000'],
            'details.*.koordinasi_jam_mulai' => ['nullable', 'date_format:H:i'],
            'details.*.koordinasi_jam_selesai' => ['nullable', 'date_format:H:i'],
            'details.*.koordinasi_uraian' => ['nullable', 'string', 'max:2000'],
            // Jenis Kegiatan sekarang memakai daftar kegiatan dinamis:
            // details[*].kegiatan_items[*].{jam_mulai,jam_selesai,uraian}.
            // Field tunggal kegiatan_jam_mulai/jam_selesai sudah tidak dikirim form.
            'details.*.kegiatan_items' => ['required', 'array', 'min:1'],
            'details.*.kegiatan_items.*.jam_mulai' => ['required', 'date_format:H:i'],
            'details.*.kegiatan_items.*.jam_selesai' => ['required', 'date_format:H:i'],
            'details.*.kegiatan_items.*.uraian' => ['required', 'string', 'max:2000'],
            'details.*.output_jenis' => ['required', 'array', 'min:1'],
            'details.*.output_jenis.*' => ['required', 'string', 'in:listing,pendataan,pendampingan,pemeriksaan'],
            // Output disimpan satu kali pada setiap detail SLS, terpisah dari daftar kegiatan.
            // Input jumlah untuk output yang tidak dicentang boleh kosong.
            'details.*.output_jumlah' => ['nullable', 'array'],
            'details.*.output_jumlah.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'details.*.jumlah_responden' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'details.*.jumlah_responden_diawasi' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'details.*.jumlah_dokumen_diperiksa' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'details.*.assignment_keluarga' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'details.*.assignment_usaha' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'details.*.assignment_usaha_dengan_keluarga' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'permasalahan' => ['required', 'string', 'min:100', 'max:5000'],
            'solusi' => ['required', 'string', 'min:100', 'max:5000'],
            'foto' => ['nullable', 'array', 'max:' . self::MAX_PHOTOS],
            'foto.*' => ['image', 'mimes:jpg,jpeg,png', 'max:250'],
            'hapus_dokumentasi' => ['nullable', 'array'],
            'hapus_dokumentasi.*' => ['integer', 'exists:dokumentasi,id'],
        ];
        $data = $request->validate(array_merge($rules, $outputRules), [
            'tanggal.required' => 'Tanggal progress wajib diisi.',
            'tanggal.date' => 'Format tanggal progress tidak valid.',
            'tanggal.after_or_equal' => 'Tanggal progress tidak boleh sebelum tanggal mulai kegiatan (' . $kegiatan->tanggal_mulai->format('d-m-Y') . ').',
            'tanggal.before_or_equal' => 'Tanggal progress tidak boleh melebihi tanggal selesai kegiatan (' . $kegiatan->tanggal_selesai->format('d-m-Y') . ').',
            'details.required' => 'Minimal harus ada satu detail lokasi/SLS.',
            'details.min' => 'Minimal harus ada satu detail lokasi/SLS.',
            'details.*.jenis_data_wilayah.required' => 'Pilih jenis data wilayah SLS atau Blok Sensus.',
            'details.*.jenis_wilayah.required' => 'Pilih jenis wilayah Desa atau Kelurahan.',
            'details.*.desa.required' => 'Pilih Desa/Kelurahan untuk setiap detail lokasi.',
            'details.*.sub_sls_id.required' => 'Sub-SLS wajib dipilih untuk kegiatan dengan jenis data SLS.',
            'details.*.perjalanan_jam_mulai.required' => 'Jam mulai Perjalanan wajib diisi.',
            'details.*.perjalanan_jam_selesai.required' => 'Jam selesai Perjalanan wajib diisi.',
            'details.*.perjalanan_uraian.required' => 'Uraian Perjalanan wajib diisi.',
            'details.*.kegiatan_items.required' => 'Tambahkan minimal satu Jenis Kegiatan.',
            'details.*.kegiatan_items.*.jam_mulai.required' => 'Jam mulai setiap Jenis Kegiatan wajib diisi.',
            'details.*.kegiatan_items.*.jam_selesai.required' => 'Jam selesai setiap Jenis Kegiatan wajib diisi.',
            'details.*.kegiatan_items.*.uraian.required' => 'Uraian setiap Jenis Kegiatan wajib diisi.',
            'details.*.output_jenis.required' => 'Pilih minimal satu jenis output yang sesuai dengan peran petugas.',
            'details.*.output_jenis.*.required' => 'Jenis output wajib dipilih.',
            'details.*.output_jumlah.*.integer' => 'Jumlah output harus berupa angka bulat.',
            'details.*.output_jumlah.*.min' => 'Jumlah output tidak boleh kurang dari 0.',
            'details.*.output_jumlah.*.max' => 'Jumlah output maksimal 1.000.000.',
            'permasalahan.required' => 'Uraian Permasalahan yang Dihadapi wajib diisi.',
            'permasalahan.min' => 'Uraian Permasalahan yang Dihadapi minimal 100 karakter.',
            'solusi.required' => 'Uraian Solusi Penyelesaian Masalah wajib diisi.',
            'solusi.min' => 'Uraian Solusi Penyelesaian Masalah minimal 100 karakter.',
            'foto.max' => 'Maksimal ' . self::MAX_PHOTOS . ' foto dokumentasi.',
            'foto.*.image' => 'File dokumentasi harus berupa gambar.',
            'foto.*.max' => 'Ukuran setiap foto maksimal 250 KB.',
            'foto.*.mimes' => 'Foto hanya boleh menggunakan format JPG atau PNG.',
        ]);

        $validatedMetadata = [];
        foreach ((array) $documentationMetadata as $fileIndex => $rawMetadata) {
            if ($rawMetadata === null || trim((string) $rawMetadata) === '') {
                continue;
            }
            $meta = json_decode((string) $rawMetadata, true);
            if (!is_array($meta)) {
                $this->validationError('foto', 'Metadata dokumentasi tidak valid. Silakan pilih foto kembali.');
            }
            $source = $meta['capture_source'] ?? null;
            if (!in_array($source, ['camera', 'gallery'], true)) {
                $this->validationError('foto', 'Sumber foto dokumentasi tidak valid.');
            }
            if ($source === 'camera') {
                $lat = filter_var($meta['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
                $lng = filter_var($meta['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
                $accuracy = filter_var($meta['gps_accuracy'] ?? null, FILTER_VALIDATE_FLOAT);
                $capturedAt = $meta['captured_at'] ?? null;
                if ($lat === false || $lat < -90 || $lat > 90 ||
                    $lng === false || $lng < -180 || $lng > 180 ||
                    $accuracy === false || $accuracy <= 0 ||
                    !is_string($capturedAt) || strtotime($capturedAt) === false ||
                    ($meta['captured_timezone'] ?? null) !== 'Asia/Jakarta') {
                    $this->validationError('foto', 'Data GPS atau waktu foto tidak valid. Silakan ambil foto kembali.');
                }
                $maxAccuracy = (float) config('sipacak.gps_max_accuracy', 50);
                if ($accuracy > $maxAccuracy) {
                    $this->validationError('foto', 'Akurasi GPS melebihi batas ' . $maxAccuracy . ' meter. Ambil lokasi kembali.');
                }
            }
            $validatedMetadata[$fileIndex] = $meta;
        }
        $data['dokumentasi_metadata'] = $validatedMetadata;

        $existingDetails = [];
        foreach ($data['details'] as $index => $detailData) {
            $allowedOutputs = $penugasan->role === 'PPL' ? ['listing', 'pendataan'] : ['pendampingan', 'pemeriksaan'];
            $selectedOutputs = array_values(array_unique($detailData['output_jenis'] ?? []));
            if (!$selectedOutputs || array_diff($selectedOutputs, $allowedOutputs)) {
                $this->validationError("details.$index.output_jenis", 'Pilih minimal satu jenis output yang sesuai dengan peran petugas.');
            }
            $outputCounts = [];
            foreach ($selectedOutputs as $outputKey) {
                $count = $detailData['output_jumlah'][$outputKey] ?? null;
                if ($count === null || $count === '' || filter_var($count, FILTER_VALIDATE_INT) === false || (int)$count < 0) {
                    $this->validationError("details.$index.output_jumlah.$outputKey", 'Jumlah untuk setiap output yang dicentang wajib diisi dengan angka 0 atau lebih.');
                }
                $outputCounts[$outputKey] = (int)$count;
            }
            $data['details'][$index]['output_rincian'] = $outputCounts;
            $data['details'][$index]['jumlah_responden'] = $outputCounts['pendataan'] ?? 0;
            $data['details'][$index]['jumlah_responden_diawasi'] = $outputCounts['pendampingan'] ?? 0;
            $data['details'][$index]['jumlah_dokumen_diperiksa'] = $outputCounts['pemeriksaan'] ?? 0;
            $outputLabels = [
                'listing' => 'Listing: ' . ($outputCounts['listing'] ?? 0) . ' SLS/BS',
                'pendataan' => 'Pendataan: ' . ($outputCounts['pendataan'] ?? 0) . ' Responden',
                'pendampingan' => 'Pendampingan Pendataan Responden: ' . ($outputCounts['pendampingan'] ?? 0) . ' SLS/BS',
                'pemeriksaan' => 'Pemeriksaan Dokumen: ' . ($outputCounts['pemeriksaan'] ?? 0) . ' Responden',
            ];
            $data['details'][$index]['output_uraian'] = implode('; ', array_map(fn ($key) => $outputLabels[$key], $selectedOutputs));
            unset($data['details'][$index]['output_jenis'], $data['details'][$index]['output_jumlah']);

            $items = array_values($detailData['kegiatan_items'] ?? []);
            if (!$items) $this->validationError("details.$index.kegiatan_items", 'Tambahkan minimal satu jenis kegiatan.');
            $starts = []; $ends = []; $descriptions = [];
            foreach ($items as $itemIndex => $item) {
                $startTime = $item['jam_mulai'] ?? ''; $endTime = $item['jam_selesai'] ?? '';
                if ($endTime < $startTime) $this->validationError("details.$index.kegiatan_items.$itemIndex.jam_selesai", 'Jam selesai tidak boleh lebih awal daripada jam mulai.');
                if ($startTime < '13:00' && $endTime > '12:00') $this->validationError("details.$index.kegiatan_items.$itemIndex.jam_mulai", 'Kegiatan tidak boleh berlangsung pada waktu istirahat pukul 12.00–13.00.');
                $starts[] = $startTime; $ends[] = $endTime; $descriptions[] = trim((string)($item['uraian'] ?? ''));
            }
            $data['details'][$index]['kegiatan_rincian'] = $items;
            $data['details'][$index]['kegiatan_jam_mulai'] = min($starts);
            $data['details'][$index]['kegiatan_jam_selesai'] = max($ends);
            $data['details'][$index]['kegiatan_uraian'] = implode("\n\n", $descriptions);
            unset($data['details'][$index]['kegiatan_items']);

            foreach (['perjalanan' => 'Perjalanan', 'koordinasi' => 'Koordinasi Penguasa Wilayah'] as $prefix => $label) {
                if (($detailData[$prefix . '_jam_mulai'] ?? '') && ($detailData[$prefix . '_jam_selesai'] ?? '') && $detailData[$prefix . '_jam_selesai'] < $detailData[$prefix . '_jam_mulai']) {
                    $this->validationError("details.$index.{$prefix}_jam_selesai", "Jam selesai {$label} tidak boleh lebih awal daripada jam mulai.");
                }
                if (($detailData[$prefix . '_jam_mulai'] ?? '') && ($detailData[$prefix . '_jam_selesai'] ?? '') && $detailData[$prefix . '_jam_mulai'] < '13:00' && $detailData[$prefix . '_jam_selesai'] > '12:00') {
                    $this->validationError("details.$index.{$prefix}_jam_mulai", "{$label} tidak boleh berlangsung pada waktu istirahat pukul 12.00–13.00.");
                }
            }
            if (empty($data['details'][$index]['koordinasi_jam_mulai']) || empty($data['details'][$index]['koordinasi_jam_selesai'])) {
                $data['details'][$index]['koordinasi_jam_mulai'] = null;
                $data['details'][$index]['koordinasi_jam_selesai'] = null;
            }

            $detail = null;
            if (!empty($detailData['id'])) {
                $detailQuery = DetailProgress::where('id', $detailData['id'])
                    ->whereHas('progress', fn ($query) => $query->where('penugasan_id', $penugasan->id));
                if ($currentProgress) {
                    $detailQuery->where('progress_id', $currentProgress->id);
                }
                $detail = $detailQuery->with('dokumentasi')->first();
                if (!$detail) {
                    $this->validationError("details.$index.id", 'Detail lokasi yang diedit tidak ditemukan pada progress ini.');
                }
            }

            $existingDetails[$index] = $detail;

            if (($detailData['jenis_data_wilayah'] ?? 'sls') === 'sls') {
                if (empty($detailData['desa_kelurahan_id']) || empty($detailData['sls_id']) || empty($detailData['sub_sls_id'])) {
                    $this->validationError("details.$index.sub_sls_id", 'Pilih Desa/Kelurahan, SLS, dan Sub-SLS terlebih dahulu.');
                }

                $village = MasterDesaKelurahan::where('id', $detailData['desa_kelurahan_id'])
                    ->where('kecamatan_id', $penugasan->kecamatan_id)
                    ->where('jenis_wilayah', $detailData['jenis_wilayah'])
                    ->first();
                if (!$village) {
                    $this->validationError("details.$index.desa_kelurahan_id", 'Desa/Kelurahan tidak sesuai dengan kecamatan penugasan.');
                }

                $sls = MasterSls::where('id', $detailData['sls_id'])
                    ->where('desa_kelurahan_id', $village->id)->first();
                if (!$sls) {
                    $this->validationError("details.$index.sls_id", 'SLS tidak sesuai dengan Desa/Kelurahan yang dipilih.');
                }

                $sub = MasterSubSls::where('id', $detailData['sub_sls_id'])->where('sls_id', $sls->id)->first();
                if (!$sub) {
                    $this->validationError("details.$index.sub_sls_id", 'Sub-SLS tidak sesuai dengan SLS yang dipilih.');
                }

                $data['details'][$index]['desa_kelurahan_id'] = $village->id;
                $data['details'][$index]['sls_id'] = $sls->id;
                $data['details'][$index]['sub_sls_id'] = $sub?->id;
                $data['details'][$index]['desa'] = $village->nama_wilayah;
                $data['details'][$index]['sls'] = $sls->nmsls . ' / ' . $sub->nmsubsls;
                $data['details'][$index]['jenis_wilayah'] = $village->jenis_wilayah;
                $data['details'][$index]['blok_sensus'] = null;
            } else {
                $sourceVillage = trim((string) ($detailData['desa'] ?? ''));
                $blockCode = trim((string) ($detailData['blok_sensus'] ?? ''));
                $allowedRow = collect($this->blockSensusRows($penugasan->kecamatan->nama_kecamatan))->first(
                    fn ($row) => $this->normalizeName($row['desa']) === $this->normalizeName($sourceVillage)
                        && $this->normalizeName($row['blok_sensus']) === $this->normalizeName($blockCode)
                );
                if (!$allowedRow) {
                    $this->validationError("details.$index.blok_sensus", 'Blok Sensus tidak tersedia untuk kecamatan dan desa yang dipilih.');
                }

                $aliases = $this->villageAliases();
                $canonical = $aliases[mb_strtoupper($sourceVillage)] ?? $sourceVillage;
                $masterVillages = MasterDesaKelurahan::where('kecamatan_id', $penugasan->kecamatan_id)->get();
                $village = $masterVillages->first(fn ($item) => $this->normalizeName($item->nama_wilayah) === $this->normalizeName($canonical));

                $data['details'][$index]['desa_kelurahan_id'] = $village?->id;
                $data['details'][$index]['jenis_wilayah'] = $village?->jenis_wilayah ?? 'desa';
                $data['details'][$index]['sls_id'] = null;
                $data['details'][$index]['sub_sls_id'] = null;
                $data['details'][$index]['desa'] = $allowedRow['desa'];
                $data['details'][$index]['sls'] = $allowedRow['blok_sensus'];
                $data['details'][$index]['blok_sensus'] = $allowedRow['blok_sensus'];
            }
        }

        // Dokumentasi bersifat per hari: semua foto disimpan pada detail pertama.
        $deleteIds = array_values(array_unique(array_map('intval', $data['hapus_dokumentasi'] ?? [])));
        $existingPhotos = 0;
        if ($currentProgress) {
            $ownedIds = Dokumentasi::whereHas('detail', fn ($query) => $query->where('progress_id', $currentProgress->id))->pluck('id')->all();
            $existingPhotos = count($ownedIds);
            if (array_diff($deleteIds, $ownedIds) !== []) {
                $this->validationError('hapus_dokumentasi', 'Foto yang akan dihapus tidak sesuai dengan progress ini.');
            }
        } elseif ($deleteIds !== []) {
            $this->validationError('hapus_dokumentasi', 'Foto yang akan dihapus tidak sesuai dengan progress ini.');
        }
        $remaining = max(0, $existingPhotos - count($deleteIds)) + count($data['foto'] ?? []);
        if ($remaining > self::MAX_PHOTOS) {
            $this->validationError('foto', 'Maksimal ' . self::MAX_PHOTOS . ' foto dokumentasi, termasuk foto yang sudah tersimpan.');
        }
        if ($remaining < 1) {
            $this->validationError('foto', 'Tambahkan minimal 1 foto dokumentasi.');
        }

        foreach ($data['details'] as $index => $detailData) {
            $existingDetail = $existingDetails[$index] ?? null;
            $keluarga = (int) ($detailData['assignment_keluarga'] ?? $existingDetail?->assignment_keluarga ?? 0);
            $usaha = (int) ($detailData['assignment_usaha'] ?? $existingDetail?->assignment_usaha ?? 0);
            $usahaKeluarga = (int) ($detailData['assignment_usaha_dengan_keluarga'] ?? $existingDetail?->assignment_usaha_dengan_keluarga ?? 0);
            $data['details'][$index]['assignment_keluarga'] = $keluarga;
            $data['details'][$index]['assignment_usaha'] = $usaha;
            $data['details'][$index]['assignment_usaha_dengan_keluarga'] = $usahaKeluarga;
            $data['details'][$index]['jumlah_assignment'] = $keluarga + $usaha + $usahaKeluarga;

            // Rentang jam gabungan (dipakai LPD, kwitansi, dan monitoring) diturunkan dari 3 kegiatan.
            $starts = array_filter([$detailData['perjalanan_jam_mulai'] ?? null, $detailData['koordinasi_jam_mulai'] ?? null, $data['details'][$index]['kegiatan_jam_mulai'] ?? null]);
            $ends = array_filter([$detailData['perjalanan_jam_selesai'] ?? null, $detailData['koordinasi_jam_selesai'] ?? null, $data['details'][$index]['kegiatan_jam_selesai'] ?? null]);
            $data['details'][$index]['jam_mulai'] = min($starts);
            $data['details'][$index]['jam_selesai'] = max($ends);

            // Permasalahan dan solusi diisi sekali per hari, disalin ke setiap detail agar LPD per SLS tetap lengkap.
            $data['details'][$index]['permasalahan_uraian'] = trim((string) ($data['permasalahan'] ?? '')) ?: null;
            $data['details'][$index]['pemecahan_masalah'] = trim((string) $data['solusi']);
        }

        return $data;
    }

    private function validationError(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }

    private function blockSensusRows(string $district): array
    {
        $path = database_path('data/blok_sensus_ogan_ilir.json');
        if (!is_file($path)) {
            return [];
        }

        $rows = json_decode(file_get_contents($path), true) ?: [];

        return array_values(array_filter($rows, fn ($row) => mb_strtolower(trim($row['kecamatan'] ?? '')) === mb_strtolower(trim($district))));
    }

    private function villageAliases(): array
    {
        return [
            'PULAU KABAL' => 'Pulau Kabai',
            'SUAK BATOK' => 'Soak Batok',
            'SERIBANDING' => 'Sribanding',
            'JAGALANA' => 'Jagalano',
        ];
    }

    private function normalizeName(string $value): string
    {
        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper(trim($value)));
    }

    private function syncDetails(ProgressHarian $day, array $data, Penugasan $assignment, bool $editing = false, ?array &$uploadedPaths = null): void
    {
        $keep = [];
        $first = null;

        foreach ($data['details'] as $row) {
            $id = $row['id'] ?? null;
            unset($row['id']);

            if ($id) {
                $detail = $day->details()->findOrFail($id);
                $detail->update($row);
            } else {
                $detail = $day->details()->create($row);
            }
            $keep[] = $detail->id;
            $first ??= $detail;
        }

        // Dokumentasi per hari: hapus foto yang ditandai, lalu simpan foto baru pada detail pertama.
        $deleteIds = array_values(array_unique(array_map('intval', $data['hapus_dokumentasi'] ?? [])));
        if ($deleteIds) {
            $photos = Dokumentasi::whereIn('id', $deleteIds)
                ->whereHas('detail', fn ($query) => $query->where('progress_id', $day->id))
                ->get();
            foreach ($photos as $photo) {
                Storage::disk('local')->delete($photo->file_path);
                $photo->delete();
            }
        }

        if ($editing) {
            // SLS yang dibuang tidak boleh membawa foto hari itu: pindahkan ke detail pertama.
            $removed = $day->details()->whereNotIn('id', $keep)->get();
            foreach ($removed as $detail) {
                if ($first) {
                    $detail->dokumentasi()->update(['detail_progress_id' => $first->id]);
                }
                $detail->delete();
            }
        }

        foreach ($data['foto'] ?? [] as $fileIndex => $file) {
            $path = $file->store('sipacak/dokumentasi/' . $assignment->id, 'local');
            if ($uploadedPaths !== null) {
                $uploadedPaths[] = $path;
            }
            try {
                $meta = $data['dokumentasi_metadata'][$fileIndex] ?? ['capture_source' => 'gallery'];
                $isCamera = ($meta['capture_source'] ?? 'gallery') === 'camera';
                $first->dokumentasi()->create([
                    'nama_file' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'ukuran' => $file->getSize(),
                    'latitude' => $isCamera ? $meta['latitude'] : null,
                    'longitude' => $isCamera ? $meta['longitude'] : null,
                    'gps_accuracy' => $isCamera ? $meta['gps_accuracy'] : null,
                    'captured_at' => $isCamera ? \Illuminate\Support\Carbon::parse($meta['captured_at']) : null,
                    'captured_timezone' => $isCamera ? 'Asia/Jakarta' : null,
                    'server_received_at' => now(),
                    'capture_source' => $meta['capture_source'] ?? 'gallery',
                ]);
            } catch (\Throwable $exception) {
                Storage::disk('local')->delete($path);
                throw $exception;
            }
        }
    }

    private function syncSelections(ProgressHarian $progress): void
    {
        $progress->uraian()->delete();
        $progress->permasalahan()->delete();
        if (!in_array($progress->penugasan->kegiatan->tujuan_akhir, ['lpd', 'keduanya'], true)) {
            return;
        }

        $details = $progress->details()->with(['uraian', 'permasalahan'])->get();
        $uraianRows = $details->flatMap(fn ($detail) => $detail->uraian)->unique('uraian_id');
        $problemRows = $details->flatMap(fn ($detail) => $detail->permasalahan)->unique('permasalahan_id');

        foreach ($uraianRows as $item) {
            $progress->uraian()->create(['uraian_id' => $item->uraian_id, 'keterangan_lainnya' => $item->keterangan_lainnya]);
        }
        foreach ($problemRows as $item) {
            $progress->permasalahan()->create(['permasalahan_id' => $item->permasalahan_id, 'keterangan_lainnya' => $item->keterangan_lainnya]);
        }
    }

    private function refreshAutomaticLpd(Penugasan $assignment, ProgressHarian $progress): void
    {
        $assignment->loadMissing('kegiatan');
        if (!in_array($assignment->kegiatan->tujuan_akhir, ['lpd', 'keduanya'], true)) {
            return;
        }

        try {
            app(DocumentService::class)->lpd(
                $assignment->fresh()->load('petugas.user', 'kegiatan', 'kecamatan'),
                $progress->fresh()
            );
        } catch (\Throwable $exception) {
            Log::error('Pembuatan LPD otomatis gagal setelah progress disimpan.', [
                'penugasan_id' => $assignment->id,
                'progress_id' => $progress->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function invalidateDocs(Penugasan $assignment, array $dates): void
    {
        foreach (array_unique($dates) as $date) {
            foreach ([
                Lpd::where('penugasan_id', $assignment->id)->whereDate('tanggal', $date)->first(),
                Kwitansi::where('penugasan_id', $assignment->id)->whereDate('tanggal', $date)->first(),
            ] as $document) {
                if ($document) {
                    if ($document->file_pdf) {
                        Storage::disk('local')->delete($document->file_pdf);
                    }
                    $document->delete();
                }
            }
        }
    }

    private function audit(Request $request, string $action, ProgressHarian $progress, $before, $after): void
    {
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'aksi' => $action,
            'entitas' => 'progress_harian',
            'entitas_id' => $progress->id,
            'data_sebelum' => $before,
            'data_sesudah' => $after,
            'ip_address' => $request->ip(),
        ]);
    }

    public function documentation(Penugasan $penugasan, Dokumentasi $document)
    {
        $this->owned($penugasan);
        abort_unless($document->detail()->whereHas('progress', fn ($query) => $query->where('penugasan_id', $penugasan->id))->exists(), 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->response($document->file_path, $document->nama_file, [
            'Content-Type' => $document->mime_type,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function destroy(Request $request, Penugasan $penugasan, ProgressHarian $progress)
    {
        $this->owned($penugasan);
        abort_unless($progress->penugasan_id === $penugasan->id, 404);
        $before = $progress->load('details')->toArray();
        $date = $progress->tanggal->toDateString();

        DB::transaction(function () use ($progress, $penugasan, $date) {
            foreach ($progress->details()->with('dokumentasi')->get() as $detail) {
                foreach ($detail->dokumentasi as $photo) {
                    Storage::disk('local')->delete($photo->file_path);
                }
            }
            $progress->delete();
            $this->invalidateDocs($penugasan, [$date]);
        });

        $this->audit($request, 'delete', $progress, $before, null);

        return back()->with('status', 'Progress dihapus.');
    }
}
