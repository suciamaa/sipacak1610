<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kegiatan;
use App\Models\Kwitansi;
use App\Models\Lpd;
use App\Models\Petugas;
use App\Models\Penugasan;
use App\Models\ProgressHarian;
use Illuminate\Pagination\LengthAwarePaginator;

class DashboardController extends Controller
{
    public static function statistics(): array
    {
        return [
            'totalKegiatan' => Kegiatan::count(),
            'aktif' => Kegiatan::where('status', 'aktif')->count(),
            'nonaktif' => Kegiatan::where('status', 'nonaktif')->count(),
            'totalPetugas' => Petugas::count(),
            'ditugaskan' => Penugasan::distinct('petugas_id')->count('petugas_id'),
            'totalProgress' => ProgressHarian::count(),
            'totalLpd' => Lpd::where('status_lpd', 'sudah_dibuat')->count(),
            'totalKwitansi' => Kwitansi::where('status_kwitansi', 'sudah_dibuat')->count(),
        ];
    }

    public function __invoke()
    {
        $kegiatan = Kegiatan::withCount('penugasan')->orderByDesc('created_at')->get();
        $progressByKegiatan = ProgressHarian::query()
            ->join('penugasan', 'penugasan.id', '=', 'progress_harian.penugasan_id')
            ->selectRaw('penugasan.kegiatan_id, COUNT(progress_harian.id) as total_progress')
            ->groupBy('penugasan.kegiatan_id')
            ->pluck('total_progress', 'kegiatan_id');

        $kegiatanMonitoring = $kegiatan->map(function (Kegiatan $item) use ($progressByKegiatan) {
            $item->progress_count = (int) ($progressByKegiatan[$item->id] ?? 0);
            return $item;
        });

        $kegiatanAktifMonitoring = $kegiatanMonitoring
            ->where('status', 'aktif')
            ->values();

        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $kegiatanTerbaru = new LengthAwarePaginator(
            $kegiatanMonitoring->forPage($currentPage, $perPage)->values(),
            $kegiatanMonitoring->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $stats = self::statistics();
        return view('admin.dashboard', $stats + [
            'kegiatanMonitoring' => $kegiatanAktifMonitoring,
            'kegiatanTerbaru' => $kegiatanTerbaru,
            'maxProgress' => max(1, (int) $kegiatanAktifMonitoring->max('progress_count')),
        ]);
    }
}
