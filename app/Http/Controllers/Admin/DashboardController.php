<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kegiatan;
use App\Models\Kwitansi;
use App\Models\Lpd;
use App\Models\Petugas;
use App\Models\Penugasan;
use App\Models\ProgressHarian;

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
        return view('admin.dashboard');
    }
}
