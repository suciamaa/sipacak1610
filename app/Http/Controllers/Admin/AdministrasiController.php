<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kegiatan;
use Illuminate\View\View;

class AdministrasiController extends Controller
{
    public function index(): View
    {
        $kegiatan = Kegiatan::withCount('penugasan')
            ->withCount([
                'penugasan as progress_count' => function ($query) {
                    $query->join('progress_harian', 'progress_harian.penugasan_id', '=', 'penugasan.id');
                },
            ])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.administrasi.index', compact('kegiatan'));
    }

    public function show(Kegiatan $kegiatan): View
    {
        return view('admin.administrasi.show', compact('kegiatan'));
    }
}
