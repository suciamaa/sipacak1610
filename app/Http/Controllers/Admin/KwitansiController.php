<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Kegiatan, AdministrasiKwitansi, ActivityLog, Penugasan};
use App\Imports\KwitansiImport;
use App\Exports\KwitansiTemplateExport;
use App\Services\DocumentService;
use App\Services\KwitansiWilayahService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Support\DocumentFilename;

class KwitansiController extends Controller
{
    public function index(Kegiatan $kegiatan)
    {
        abort_unless(in_array($kegiatan->tujuan_akhir, ['kwitansi', 'keduanya'], true), 404);
        $admin = $kegiatan->administrasiKwitansi()->with('rows.penugasan.petugas')->first();
        $rows = $kegiatan->penugasan()->with([
            'petugas.user', 'kecamatan', 'progress.details.desaKelurahan', 'kwitansi',
        ])->get()->flatMap(fn ($assignment) => $assignment->progress
            ->filter(fn ($progress) => $progress->tanggal->betweenIncluded($kegiatan->tanggal_mulai, $kegiatan->tanggal_selesai))
            ->filter(fn ($progress) => $progress->details->pluck('desa')->filter()->isNotEmpty())
            ->map(fn ($progress) => [
                'assignment' => $assignment,
                'progress' => $progress,
                'destination' => rescue(
                    fn () => app(KwitansiWilayahService::class)->destination($progress, $assignment),
                    'Perlu validasi master wilayah'
                ),
                'document' => $assignment->kwitansi->first(
                    fn ($document) => $document->tanggal->toDateString() === $progress->tanggal->toDateString()
                ),
            ]))->sortBy(fn ($row) => $row['progress']->tanggal)->values();

        return view('admin.kwitansi.index', compact('kegiatan', 'admin', 'rows'));
    }

    public function template(Kegiatan $kegiatan)
    {
        abort_unless(in_array($kegiatan->tujuan_akhir, ['kwitansi', 'keduanya'], true), 404);
        return Excel::download(
            new KwitansiTemplateExport($kegiatan),
            'Template_Kwitansi_' . str($kegiatan->nama_kegiatan)->slug() . '.xlsx'
        );
    }

    public function import(Request $request, Kegiatan $kegiatan, DocumentService $documentService, KwitansiWilayahService $wilayahService)
    {
        abort_unless(in_array($kegiatan->tujuan_akhir, ['kwitansi', 'keduanya'], true), 404);

        $validated = $request->validate([
            'nama_bendahara_pengeluaran' => 'required|string|max:150',
            'nip_bendahara_pengeluaran' => 'required|string|max:30',
            'nama_pejabat_pembuat_komitmen' => 'required|string|max:150',
            'nip_pejabat_pembuat_komitmen' => 'required|string|max:30',
            'excel' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $import = new KwitansiImport();
            Excel::import($import, $validated['excel']);
            $rows = $import->rows;
        } catch (\Throwable $exception) {
            return back()->withErrors(['excel' => 'File Excel tidak dapat dibaca. Pastikan file menggunakan template SIPACAK yang benar. Detail: ' . $exception->getMessage()]);
        }

        if (!$rows) {
            return back()->withErrors(['excel' => 'Tidak ada baris data pada Excel.']);
        }

        $requiredHeaders = [
            'nomor_surat_tugas', 'nama_pelaksana_perjalanan_dinas', 'tanggal_surat_tugas',
            'tanggal_tugas', 'asal_penugasan', 'daerah_tujuan_perjalanan_dinas',
            'lama_perjalanan', 'berangkat', 'pulang', 'jumlah_nominal',
            'hal', 'bulan_pengajuan_kwitansi',
        ];
        $headers = array_keys($rows[0]);
        foreach ($requiredHeaders as $header) {
            if (!in_array($header, $headers, true)) {
                return back()->withErrors(['excel' => 'Kolom template tidak ditemukan: ' . str_replace('_', ' ', $header) . '. Unduh template terbaru.']);
            }
        }

        $matched = [];
        $seenAssignmentDates = [];
        $errors = [];
        foreach ($rows as $index => $row) {
            if (!array_filter($row, fn ($value) => trim((string) $value) !== '')) {
                continue;
            }

            $line = $index + 2;
            $name = trim((string) ($row['nama_pelaksana_perjalanan_dinas'] ?? ''));
            $date = $this->dateValue($row['tanggal_tugas'] ?? null);
            $area = trim((string) ($row['daerah_tujuan_perjalanan_dinas'] ?? ''));
            $amount = $this->money($row['jumlah_nominal'] ?? null);
            $depart = $this->money($row['berangkat'] ?? null);
            $letterDateRaw = $row['tanggal_surat_tugas'] ?? null;
            $letterDate = $this->dateValue($letterDateRaw);

            if ($name === '') $errors[] = "Baris {$line}: Nama Pelaksana Perjalanan Dinas wajib diisi.";
            if (!$date) $errors[] = "Baris {$line}: Tanggal Tugas kosong atau tidak valid.";
            elseif ($date < $kegiatan->tanggal_mulai->toDateString() || $date > $kegiatan->tanggal_selesai->toDateString()) $errors[] = "Baris {$line}: Tanggal Tugas berada di luar periode kegiatan.";
            if ($area === '') $errors[] = "Baris {$line}: Daerah Tujuan Perjalanan Dinas wajib diisi.";
            if ($amount === null || $amount <= 0) $errors[] = "Baris {$line}: Jumlah Nominal wajib berupa angka Rupiah lebih dari nol.";
            if ($depart === null || $depart <= 0) $errors[] = "Baris {$line}: Berangkat wajib berupa angka Rupiah lebih dari nol.";
            if ($amount !== null && $depart !== null && $amount !== 2 * $depart) {
                $errors[] = "Baris {$line}: Jumlah Nominal harus sama dengan 2 x Berangkat sesuai format biaya perjalanan pulang-pergi.";
            }
            if (trim((string) ($row['hal'] ?? '')) === '') $errors[] = "Baris {$line}: Hal wajib diisi.";
            if (trim((string) ($row['bulan_pengajuan_kwitansi'] ?? '')) === '') $errors[] = "Baris {$line}: Bulan Pengajuan Kwitansi wajib diisi.";
            if (trim((string) $letterDateRaw) === '') $errors[] = "Baris {$line}: Tanggal Surat Tugas wajib diisi.";
            elseif (!$letterDate) $errors[] = "Baris {$line}: Tanggal Surat Tugas tidak valid.";
            if (trim((string) ($row['nomor_surat_tugas'] ?? '')) === '') $errors[] = "Baris {$line}: Nomor Surat Tugas wajib diisi.";
            if (trim((string) ($row['asal_penugasan'] ?? '')) === '') $errors[] = "Baris {$line}: Asal Penugasan wajib diisi.";
            if (trim((string) ($row['lama_perjalanan'] ?? '')) === '') $errors[] = "Baris {$line}: Lama Perjalanan wajib diisi.";

            if ($name === '' || !$date || $area === '' || $amount === null || $depart === null) {
                continue;
            }

            $assignments = $kegiatan->penugasan()
                ->whereHas('petugas', fn ($query) => $query->whereRaw('LOWER(TRIM(nama_lengkap)) = ?', [mb_strtolower($name)]))
                ->with(['petugas', 'kecamatan', 'progress.details.desaKelurahan'])
                ->get();

            $candidates = [];
            $wilayahErrors = [];
            foreach ($assignments as $assignment) {
                foreach ($assignment->progress as $progress) {
                    if ($progress->tanggal->toDateString() !== $date) continue;
                    if (!$progress->details->pluck('desa')->filter()->isNotEmpty()) continue;

                    try {
                        $expectedArea = $wilayahService->destination($progress, $assignment);
                    } catch (\Throwable $exception) {
                        $wilayahErrors[] = $exception->getMessage();
                        continue;
                    }

                    if ($this->normalizeArea($expectedArea) === $this->normalizeArea($area)) {
                        $candidates[] = [$assignment, $progress, $expectedArea];
                    }
                }
            }

            if (count($candidates) !== 1) {
                if (count($candidates) === 0 && $wilayahErrors) {
                    $errors[] = "Baris {$line}: Validasi master wilayah gagal: " . implode(' ', array_unique($wilayahErrors));
                } else {
                    $errors[] = count($candidates) === 0
                        ? "Baris {$line}: Tidak ditemukan progress yang cocok untuk {$name} pada {$date} dan wilayah '{$area}'. Pastikan tujuan menggunakan nama serta jenis Desa/Kelurahan sesuai master."
                        : "Baris {$line}: Kombinasi petugas, tanggal, dan wilayah ambigu. Baris tidak dapat diproses.";
                }
                continue;
            }

            [$assignment, $progress, $expectedArea] = $candidates[0];
            $uniqueDay = $assignment->id . '|' . $date;
            if (isset($seenAssignmentDates[$uniqueDay])) {
                $errors[] = "Baris {$line}: Data petugas dan tanggal ini muncul lebih dari satu kali pada Excel.";
                continue;
            }
            $seenAssignmentDates[$uniqueDay] = true;
            $data = $row;
            $data['tanggal_tugas'] = $date;
            $data['tanggal_surat_tugas'] = $letterDate;
            $data['jumlah_nominal'] = $amount;
            $data['terbilang'] = \App\Support\TerbilangRupiah::buat($amount);
            $data['berangkat'] = $depart;
            $data['pulang'] = $this->money($row['pulang'] ?? null);
            $data['daerah_tujuan_perjalanan_dinas'] = $expectedArea;
            $matched[] = [
                'penugasan_id' => $assignment->id,
                'tanggal' => $date,
                'nomor_kwitansi' => trim((string) ($row['nomor_surat_tugas'] ?? '')) ?: null,
                'data_administrasi' => $data,
                'status_matching' => 'cocok',
            ];
        }

        if (!$matched) $errors[] = 'Tidak ada baris Excel yang berhasil dicocokkan dengan data progress.';
        if ($errors) {
            return back()->withErrors(['excel' => 'Upload dibatalkan. Perbaiki seluruh kesalahan berikut, lalu unggah ulang.'])
                ->with('import_errors', array_values(array_unique($errors)));
        }

        $oldFile = null;
        DB::transaction(function () use ($validated, $request, $kegiatan, $matched, &$oldFile) {
            $admin = AdministrasiKwitansi::firstOrNew(['kegiatan_id' => $kegiatan->id]);
            $oldFile = $admin->file_excel_path;
            $admin->nama_bendahara_pengeluaran = $validated['nama_bendahara_pengeluaran'];
            $admin->nip_bendahara_pengeluaran = $validated['nip_bendahara_pengeluaran'];
            $admin->nama_pejabat_pembuat_komitmen = $validated['nama_pejabat_pembuat_komitmen'];
            $admin->nip_pejabat_pembuat_komitmen = $validated['nip_pejabat_pembuat_komitmen'];
            $admin->file_excel_path = $validated['excel']->store('sipacak/kwitansi-excel', 'local');
            $admin->file_excel_original_name = substr($validated['excel']->getClientOriginalName(), 0, 255);
            $admin->uploaded_at = now();
            $admin->save();

            // Replace data set atomically; remove stale generated receipt files/statuses.
            foreach ($admin->rows as $oldRow) {
                $document = \App\Models\Kwitansi::where('penugasan_id', $oldRow->penugasan_id)
                    ->whereDate('tanggal', $oldRow->tanggal)->first();
                if ($document) {
                    if ($document->file_pdf) Storage::disk('local')->delete($document->file_pdf);
                    $document->delete();
                }
            }
            $admin->rows()->delete();
            foreach ($matched as $record) $admin->rows()->create($record);

            ActivityLog::create([
                'user_id' => $request->user()->id,
                'aksi' => 'import',
                'entitas' => 'administrasi_kwitansi',
                'entitas_id' => $admin->id,
                'data_sesudah' => ['jumlah_baris_cocok' => count($matched)],
                'ip_address' => $request->ip(),
            ]);
        });

        if ($oldFile) Storage::disk('local')->delete($oldFile);

        $generated = 0;
        $generationErrors = [];
        foreach ($matched as $record) {
            try {
                $assignment = Penugasan::with('progress')->findOrFail($record['penugasan_id']);
                $progress = $assignment->progress()->whereDate('tanggal', $record['tanggal'])->firstOrFail();
                $documentService->kwitansi($assignment, $progress);
                $generated++;
            } catch (\Throwable $exception) {
                $generationErrors[] = $record['tanggal'] . ': ' . $exception->getMessage();
            }
        }

        $message = 'Upload berhasil divalidasi. ' . count($matched) . ' baris tersimpan dan ' . $generated . ' PDF kwitansi berhasil dibuat.';
        if ($generationErrors) {
            return back()->with('status', $message . ' Sebagian PDF belum berhasil dibuat.')
                ->with('import_errors', $generationErrors);
        }
        return back()->with('status', $message);
    }

    private function normalizeArea(string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($value)));
    }

    private function dateValue($value): ?string
    {
        if ($value === null || $value === '') return null;
        try {
            if (is_numeric($value)) return Carbon::create(1899, 12, 30)->addDays((int) $value)->format('Y-m-d');
            foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'Y/m/d', 'm/d/Y'] as $format) {
                try {
                    $date = Carbon::createFromFormat('!' . $format, (string) $value);
                    if ($date && $date->format($format) === (string) $value) return $date->format('Y-m-d');
                } catch (\Throwable) {}
            }
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function money($value): ?int
    {
        if (is_int($value) || is_float($value)) return (int) round($value);
        $raw = trim((string) $value);
        if ($raw === '') return null;
        if (preg_match('/^\d+$/', $raw)) return (int) $raw;
        if (preg_match('/^\d{1,3}(?:\.\d{3})+$/', $raw)) return (int) str_replace('.', '', $raw);
        if (preg_match('/^\d{1,3}(?:,\d{3})+$/', $raw)) return (int) str_replace(',', '', $raw);
        return null;
    }

    public function generate(Kegiatan $kegiatan, $progress, DocumentService $service)
    {
        $assignment = $kegiatan->penugasan()->with('progress')->findOrFail(request('penugasan_id'));
        $day = $assignment->progress->firstWhere('id', (int) $progress);
        abort_unless($day, 404);
        $document = $service->kwitansi($assignment, $day);
        $assignment->loadMissing('petugas', 'kegiatan');
        return response()->download(Storage::disk('local')->path($document->file_pdf), DocumentFilename::make('kwitansi', $kegiatan->nama_kegiatan, $assignment->petugas->nama_lengkap, $day->tanggal));
    }
}
