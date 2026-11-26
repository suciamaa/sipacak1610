<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Kwitansi;
use App\Models\DataExcelKwitansi;
use App\Models\Lpd;
use App\Models\Penugasan;
use App\Models\ProgressHarian;
use App\Services\LpdContentBuilder;
use App\Services\DocumentService;
use App\Services\KwitansiWilayahService;
use App\Support\DocumentFilename;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DokumenController extends Controller
{
    private function own(Penugasan $penugasan): void
    {
        abort_unless($penugasan->petugas()->where('user_id', auth()->id())->exists(), 404);
    }

    public function index(Penugasan $penugasan)
    {
        $this->own($penugasan);
        $penugasan->load('kegiatan');
        $days = $penugasan->progress()->orderByDesc('tanggal')->paginate(20);

        foreach ($days as $day) {
            $day->setRelation('lpd', collect([
                Lpd::where('penugasan_id', $penugasan->id)->whereDate('tanggal', $day->tanggal)->first(),
            ])->filter());
            $day->setRelation('kwitansi', collect([
                Kwitansi::where('penugasan_id', $penugasan->id)->whereDate('tanggal', $day->tanggal)->first(),
            ])->filter());
            $day->kwitansi_data = $penugasan->kegiatan->administrasiKwitansi?->rows()
                ->where('penugasan_id', $penugasan->id)
                ->whereDate('tanggal', $day->tanggal)
                ->where('status_matching', 'cocok')
                ->first()?->data_administrasi ?? [];
        }

        return view('petugas.dokumen.index', compact('penugasan', 'days'));
    }

    public function download(Penugasan $penugasan, string $type, Request $request)
    {
        $this->own($penugasan);
        abort_unless(in_array($type, ['lpd', 'kwitansi'], true), 404);
        $penugasan->loadMissing('kegiatan', 'kecamatan', 'petugas');
        abort_unless(
            $type === 'lpd'
                ? in_array($penugasan->kegiatan->tujuan_akhir, ['lpd', 'keduanya'], true)
                : in_array($penugasan->kegiatan->tujuan_akhir, ['kwitansi', 'keduanya'], true),
            404
        );

        if ($type === 'lpd') {
            $this->refreshOutdatedLpd($penugasan);
        }

        $model = $type === 'lpd' ? Lpd::class : Kwitansi::class;
        $statusColumn = $type === 'lpd' ? 'status_lpd' : 'status_kwitansi';
        $query = $model::where('penugasan_id', $penugasan->id)
            ->where($statusColumn, 'sudah_dibuat')
            ->whereNotNull('file_pdf');

        if ($request->filled('tanggal')) {
            $date = $request->validate([
                'tanggal' => [
                    'required',
                    'date',
                    'after_or_equal:' . $penugasan->kegiatan->tanggal_mulai->format('Y-m-d'),
                    'before_or_equal:' . $penugasan->kegiatan->tanggal_selesai->format('Y-m-d'),
                ],
            ])['tanggal'];
            $document = $query->whereDate('tanggal', $date)->firstOrFail();
            abort_unless(Storage::disk('local')->exists($document->file_pdf), 404);

            return Storage::disk('local')->download($document->file_pdf, DocumentFilename::make($type, $penugasan->kegiatan->nama_kegiatan, $penugasan->petugas->nama_lengkap, $document->tanggal));
        }

        $documents = $query->orderBy('tanggal')->get()
            ->filter(fn ($document) => $document->file_pdf && Storage::disk('local')->exists($document->file_pdf))
            ->values();
        abort_if($documents->isEmpty(), 404, 'Belum ada dokumen yang tersedia untuk diunduh.');

        if ($request->query('format') === 'pdf') {
            return $type === 'lpd'
                ? $this->downloadLpdBundle($penugasan, $documents)
                : $this->downloadKwitansiBundle($penugasan, $documents);
        }

        abort_unless(class_exists(\ZipArchive::class), 501, 'Fitur unduh seluruh periode ZIP memerlukan ekstensi PHP ZIP aktif.');
        $zipPath = tempnam(sys_get_temp_dir(), 'sipacak_docs_');
        $zip = new \ZipArchive();
        abort_unless($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true, 500, 'Gagal menyiapkan arsip dokumen.');

        foreach ($documents as $document) {
            $entryName = DocumentFilename::make($type, $penugasan->kegiatan->nama_kegiatan, $penugasan->petugas->nama_lengkap, $document->tanggal);
            $zip->addFile(Storage::disk('local')->path($document->file_pdf), $entryName);
        }
        $zip->close();

        return response()->download(
            $zipPath,
            DocumentFilename::forCollection($type, $penugasan, $documents, 'zip')
        )->deleteFileAfterSend(true);
    }

    private function downloadLpdBundle(Penugasan $penugasan, $documents)
    {
        $dates = $documents->map(fn ($document) => $document->tanggal->toDateString())->all();
        $progressRows = ProgressHarian::where('penugasan_id', $penugasan->id)
            ->whereIn('tanggal', $dates)
            ->with([
                'details.dokumentasi',
                'details.uraian.master',
                'details.permasalahan.master',
                'details.masterSls',
                'details.masterSubSls',
                'uraian.master',
                'permasalahan.master',
            ])
            ->orderBy('tanggal')
            ->get();

        $builder = app(LpdContentBuilder::class);
        $items = $progressRows->map(fn ($progress) => [
            'assignment' => $penugasan,
            'progress' => $progress,
            'lpdTimeline' => $builder->build($progress, $penugasan),
        ])->values()->all();
        abort_if(!$items, 404, 'Tidak ada LPD yang dapat digabungkan.');
        abort_if(count($items) !== $documents->count(), 422, 'Sebagian LPD tidak memiliki data progress yang sesuai sehingga belum dapat digabungkan.');

        $binary = app(\App\Services\LpdPdfRenderer::class)->render($items);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . DocumentFilename::forCollection('lpd', $penugasan, $documents, 'pdf') . '"',
        ]);
    }

    private function downloadKwitansiBundle(Penugasan $penugasan, $documents)
    {
        $admin = $penugasan->kegiatan->administrasiKwitansi;
        abort_unless($admin, 404, 'Data administrasi kwitansi belum tersedia.');
        $items = [];

        foreach ($documents as $document) {
            $progress = $penugasan->progress()
                ->with('details.desaKelurahan')
                ->whereDate('tanggal', $document->tanggal)
                ->first();
            abort_unless($progress, 422, 'Progress untuk salah satu tanggal kwitansi tidak ditemukan.');

            $row = $admin->rows()
                ->where('penugasan_id', $penugasan->id)
                ->whereDate('tanggal', $document->tanggal)
                ->where('status_matching', 'cocok')
                ->first();
            abort_unless($row, 422, 'Data Excel untuk salah satu tanggal kwitansi tidak tersedia atau belum cocok.');

            $data = $row->data_administrasi ?? [];
            $data['terbilang'] = \App\Support\TerbilangRupiah::buat((int) ($data['jumlah_nominal'] ?? 0));
            $wilayah = app(KwitansiWilayahService::class);
            $data['daerah_tujuan_perjalanan_dinas'] = $wilayah->destination($progress, $penugasan);
            $data['uraian_tujuan_perjalanan'] = $wilayah->journeyDescription($progress, $penugasan);

            $items[] = [
                'assignment' => $penugasan,
                'progress' => $progress,
                'admin' => $admin,
                'data' => $data,
            ];
        }

        abort_if(!$items, 404, 'Tidak ada kwitansi yang dapat digabungkan.');
        abort_if(count($items) !== $documents->count(), 422, 'Sebagian kwitansi tidak dapat disiapkan untuk PDF gabungan. Periksa kembali data administrasi.');

        // Satu PDF berisi 3 halaman per kwitansi: template FPDI + teks TCPDF berdasarkan koordinat.
        $binary = app(\App\Services\KwitansiPdfRenderer::class)->render($items);

        return response()->streamDownload(
            static function () use ($binary) {
                echo $binary;
            },
            DocumentFilename::forCollection('kwitansi', $penugasan, $documents, 'pdf'),
            ['Content-Type' => 'application/pdf']
        );
    }

    public function lpd(Penugasan $penugasan, Lpd $document)
    {
        $this->own($penugasan);
        abort_unless($document->penugasan_id === $penugasan->id && $penugasan->kegiatan->tujuan_akhir !== 'kwitansi', 404);

        $this->refreshOutdatedLpd($penugasan, $document->tanggal->toDateString());
        $document = Lpd::where('penugasan_id', $penugasan->id)
            ->whereDate('tanggal', $document->tanggal)
            ->firstOrFail();

        abort_unless(
            $document->status_lpd === 'sudah_dibuat'
            && $document->file_pdf
            && Storage::disk('local')->exists($document->file_pdf),
            404
        );

        return Storage::disk('local')->download($document->file_pdf, DocumentFilename::make('lpd', $penugasan->kegiatan->nama_kegiatan, $penugasan->petugas->nama_lengkap, $document->tanggal));
    }

    /**
     * Regenerate LPD lama agar dokumen tersimpan dan unduhan ZIP memakai
     * format terbaru tanpa mengubah alur maupun data modul kwitansi.
     */
    private function refreshOutdatedLpd(Penugasan $penugasan, ?string $date = null): void
    {
        $penugasan->loadMissing('petugas.user', 'kegiatan', 'kecamatan');

        $query = $penugasan->progress()->with([
            'details.dokumentasi',
            'details.uraian.master',
            'details.permasalahan.master',
            'details.masterSls',
            'details.masterSubSls',
        ]);

        if ($date !== null) {
            $query->whereDate('tanggal', $date);
        }

        foreach ($query->get() as $progress) {
            $document = Lpd::where('penugasan_id', $penugasan->id)
                ->whereDate('tanggal', $progress->tanggal)
                ->first();

            $isMissing = !$document
                || $document->status_lpd !== 'sudah_dibuat'
                || !$document->file_pdf
                || !Storage::disk('local')->exists($document->file_pdf);
            $isOutdated = $document
                && $document->layout_version !== DocumentService::LPD_LAYOUT_VERSION;

            if ($isMissing || $isOutdated) {
                app(DocumentService::class)->lpd($penugasan, $progress);
            }
        }
    }

    public function kwitansi(Penugasan $penugasan, Kwitansi $document)
    {
        $this->own($penugasan);
        abort_unless($document->penugasan_id === $penugasan->id && $penugasan->kegiatan->tujuan_akhir !== 'lpd', 404);
        abort_unless($document->status_kwitansi === 'sudah_dibuat' && Storage::disk('local')->exists($document->file_pdf), 404);

        $filename = DocumentFilename::make('kwitansi', $penugasan->kegiatan->nama_kegiatan, $penugasan->petugas->nama_lengkap, $document->tanggal);

        return Storage::disk('local')->download($document->file_pdf, $filename);
    }
}
