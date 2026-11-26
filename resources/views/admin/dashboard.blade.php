@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('page_title', 'Dashboard')
@section('content')
<div class="page-heading">
    <div><h2>Dashboard Admin</h2><p>Pantau kondisi kegiatan, penugasan, dan dokumen administrasi SIPACAK.</p></div>
    <a class="btn btn-primary" href="{{ route('admin.kegiatan.index') }}"><i class="bi bi-calendar2-check me-1"></i>Lihat Kegiatan</a>
</div>
<div class="ad-panels">
    <section class="ad-panel" aria-label="Ringkasan kegiatan">
        <h3>Kegiatan</h3>
        <div class="ad-metrics" style="--cols:3">
            <div class="ad-metric"><strong>{{ number_format($totalKegiatan) }}</strong><span>Total</span></div>
            <div class="ad-metric is-key"><strong>{{ number_format($aktif) }}</strong><span>Aktif</span></div>
            <div class="ad-metric"><strong>{{ number_format($nonaktif) }}</strong><span>Nonaktif</span></div>
        </div>
    </section>
    <section class="ad-panel" aria-label="Petugas dan dokumen">
        <h3>Petugas dan dokumen</h3>
        <div class="ad-metrics" style="--cols:5">
            <div class="ad-metric"><strong>{{ number_format($totalPetugas) }}</strong><span>Petugas terdaftar</span></div>
            <div class="ad-metric"><strong>{{ number_format($ditugaskan) }}</strong><span>Sudah ditugaskan</span></div>
            <div class="ad-metric"><strong>{{ number_format($totalProgress) }}</strong><span>Progress harian</span></div>
            <div class="ad-metric"><strong>{{ number_format($totalLpd) }}</strong><span>LPD dibuat</span></div>
            <div class="ad-metric"><strong>{{ number_format($totalKwitansi) }}</strong><span>Kwitansi dibuat</span></div>
        </div>
    </section>
</div>
<div class="row g-3">
    <div class="col-xl-7">
        <div class="card p-3 p-lg-4 h-100">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3"><div><h5 class="mb-1">Progress per Kegiatan</h5><p class="text-secondary small mb-0">Jumlah progress harian yang telah disimpan petugas.</p></div><span class="badge text-bg-light">{{ $kegiatanMonitoring->count() }} kegiatan</span></div>
            @forelse($kegiatanMonitoring->sortByDesc('progress_count')->take(8) as $item)
                <div class="monitoring-bar-row">
                    <div class="d-flex justify-content-between gap-3 mb-1"><span class="fw-semibold text-truncate">{{ $item->nama_kegiatan }}</span><span class="fw-bold text-nowrap">{{ $item->progress_count }}</span></div>
                    <div class="monitoring-track"><div class="monitoring-fill" style="width: {{ min(100, ($item->progress_count / $maxProgress) * 100) }}%"></div></div>
                    <div class="small text-secondary mt-1">{{ $item->penugasan_count }} penugasan · {{ ucfirst($item->status) }}</div>
                </div>
            @empty
                <div class="empty-state"><div class="empty-state-icon"><i class="bi bi-bar-chart"></i></div>Belum ada data kegiatan untuk dimonitor.</div>
            @endforelse
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card p-3 p-lg-4 h-100">
            <h5 class="mb-1">Komposisi Kegiatan</h5><p class="text-secondary small mb-3">Perbandingan kegiatan aktif dan nonaktif.</p>
            @php
                $activityTotal = max(1, $aktif + $nonaktif);
                $activePercent = $aktif / $activityTotal * 100;
                $inactivePercent = $nonaktif / $activityTotal * 100;
            @endphp
            <div class="activity-composition">
                <div class="activity-donut" style="background: conic-gradient(#3a7ca5 0% {{ $activePercent }}%, #d3dde7 {{ $activePercent }}% 100%);">
                    <div class="activity-donut-hole"><strong>{{ $totalKegiatan }}</strong><span>Kegiatan</span></div>
                </div>
                <div class="activity-legend">
                    <div><span class="activity-legend-label"><i class="activity-legend-dot is-active"></i>Aktif</span><strong>{{ $aktif }} <small>({{ round($activePercent) }}%)</small></strong></div>
                    <div><span class="activity-legend-label"><i class="activity-legend-dot is-inactive"></i>Nonaktif</span><strong>{{ $nonaktif }} <small>({{ round($inactivePercent) }}%)</small></strong></div>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-between align-items-center"><span class="text-secondary small">Total kegiatan terdaftar</span><strong class="fs-4">{{ $totalKegiatan }}</strong></div>
            <a class="btn btn-outline-primary w-100 mt-3" href="{{ route('admin.kegiatan.index') }}">Buka pengelolaan kegiatan</a>
        </div>
    </div>
</div>
<div class="card p-3 p-lg-4 mt-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div><h5 class="mb-1">Kegiatan Terbaru</h5><p class="text-secondary small mb-0">Ringkasan status kegiatan untuk pemantauan.</p></div><a href="{{ route('admin.kegiatan.index') }}" class="small fw-bold">Lihat semua <i class="bi bi-arrow-right"></i></a></div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nama Kegiatan</th><th>Periode</th><th>Penugasan</th><th>Progress</th><th>Status</th><th class="text-center">Aksi</th></tr></thead><tbody>
    @forelse($kegiatanTerbaru as $item)
        <tr><td class="fw-semibold">{{ $item->nama_kegiatan }}</td><td class="text-nowrap">{{ $item->tanggal_mulai->format('d/m/Y') }} – {{ $item->tanggal_selesai->format('d/m/Y') }}</td><td>{{ $item->penugasan_count }}</td><td>{{ $item->progress_count }}</td><td><span class="badge {{ $item->status === 'aktif' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($item->status) }}</span></td><td class="text-center"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.kegiatan.show', $item) }}">Detail</a></td></tr>
    @empty
        <tr><td colspan="6" class="text-center text-secondary py-4">Belum ada kegiatan.</td></tr>
    @endforelse
    </tbody></table></div>
    @if($kegiatanTerbaru->hasPages())
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
            <small class="text-secondary">Menampilkan {{ $kegiatanTerbaru->firstItem() }}–{{ $kegiatanTerbaru->lastItem() }} dari {{ $kegiatanTerbaru->total() }} kegiatan</small>
            {{ $kegiatanTerbaru->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
