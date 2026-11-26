<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Penugasan;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $allAssignments = Penugasan::with(['kegiatan', 'kecamatan'])
            ->whereHas('petugas', fn ($query) => $query->where('user_id', auth()->id()))
            ->where('status_penugasan', 'aktif')
            ->get();

        $years = $allAssignments
            ->pluck('kegiatan.tanggal_mulai')
            ->filter()
            ->map(fn ($date) => $date->format('Y'))
            ->unique()
            ->sortDesc()
            ->values();

        $assignments = $allAssignments;

        if ($request->filled('year')) {
            $assignments = $assignments->filter(
                fn ($assignment) => $assignment->kegiatan->tanggal_mulai->format('Y') === (string) $request->input('year')
            );
        }

        if ($request->filled('search')) {
            $keyword = mb_strtolower(trim($request->input('search')));
            $assignments = $assignments->filter(function ($assignment) use ($keyword) {
                $haystack = mb_strtolower(implode(' ', [
                    $assignment->kegiatan->nama_kegiatan ?? '',
                    $assignment->kecamatan->nama_kecamatan ?? '',
                    $assignment->role ?? '',
                    $assignment->kegiatan->status ?? '',
                ]));

                return str_contains($haystack, $keyword);
            });
        }

        $sort = $request->input('sort', 'terbaru');
        $assignments = $sort === 'terlama'
            ? $assignments->sortBy(fn ($assignment) => $assignment->kegiatan->tanggal_mulai)->values()
            : $assignments->sortByDesc(fn ($assignment) => $assignment->kegiatan->tanggal_mulai)->values();

        return view('petugas.dashboard', compact('assignments', 'allAssignments', 'years', 'sort'));
    }
}
