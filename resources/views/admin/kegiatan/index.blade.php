@extends('layouts.app')
@section('title', 'Kelola Kegiatan')
@section('page_title', 'Kelola Kegiatan')
@section('content')
<div class="page-heading">
    <div><div class="page-eyebrow">Ringkasan SIPACAK</div><h2>Kelola Kegiatan</h2><p>Pantau kegiatan yang dikelola SIPACAK, lalu buka satu kegiatan untuk mengelola petugas, LPD, dan kwitansinya.</p></div>
    <a class="btn btn-success" href="{{ route('admin.kegiatan.create') }}"><i class="bi bi-plus-lg me-1"></i>Tambah Kegiatan</a>
</div>

<section class="ad-panel dash-summary" aria-label="Ringkasan kegiatan">
    <h3>Ringkasan kegiatan yang dikelola</h3>
    <div class="ad-metrics" style="--cols:6">
        <div class="ad-metric"><strong>{{ number_format($totalKegiatan) }}</strong><span>Total kegiatan</span></div>
        <div class="ad-metric is-key"><strong>{{ number_format($aktif) }}</strong><span>Kegiatan aktif</span></div>
        <div class="ad-metric"><strong>{{ number_format($nonaktif) }}</strong><span>Kegiatan nonaktif</span></div>
        <div class="ad-metric"><strong>{{ number_format($ditugaskan) }}</strong><span>Petugas ditugaskan</span></div>
        <div class="ad-metric"><strong>{{ number_format($totalProgress) }}</strong><span>Progress harian</span></div>
        <div class="ad-metric"><strong>{{ number_format($totalLpd + $totalKwitansi) }}</strong><span>Dokumen dibuat <small>({{ number_format($totalLpd) }} LPD · {{ number_format($totalKwitansi) }} kwitansi)</small></span></div>
    </div>
</section>

<div class="card p-3 p-lg-4 mt-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div><h5 class="mb-1">Daftar Kegiatan</h5><p class="text-secondary small mb-0">Klik <strong>Kelola Kegiatan</strong> untuk membuka petugas, LPD, dan kwitansi dalam satu halaman.</p></div>
    </div>
    <ul class="nav nav-tabs dash-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link {{ $tab === 'aktif' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-aktif" type="button" role="tab">Aktif <span class="badge text-bg-success ms-1">{{ $kegiatanAktif->count() }}</span></button></li>
        <li class="nav-item" role="presentation"><button class="nav-link {{ $tab === 'nonaktif' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-nonaktif" type="button" role="tab">Nonaktif <span class="badge text-bg-secondary ms-1">{{ $kegiatanNonaktif->count() }}</span></button></li>
    </ul>
    <div class="tab-content">
        @foreach(['aktif' => $kegiatanAktif, 'nonaktif' => $kegiatanNonaktif] as $key => $list)
        <div class="tab-pane fade {{ $tab === $key ? 'show active' : '' }}" id="tab-{{ $key }}" role="tabpanel">
            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nama Kegiatan</th><th>Periode</th><th>Petugas</th><th>Progress</th><th>Tujuan Dokumen</th><th class="text-center">Aksi</th></tr></thead><tbody>
            @forelse($list as $item)
                <tr>
                    <td class="fw-semibold">{{ $item->nama_kegiatan }}</td>
                    <td class="text-nowrap">{{ $item->tanggal_mulai->format('d/m/Y') }} – {{ $item->tanggal_selesai->format('d/m/Y') }}</td>
                    <td>{{ $item->penugasan_count }}</td>
                    <td>{{ $item->progress_count }}</td>
                    <td>{{ $item->tujuan_akhir === 'keduanya' ? 'LPD dan Kwitansi' : ($item->tujuan_akhir === 'lpd' ? 'LPD' : 'Kwitansi') }}</td>
                    <td class="text-center text-nowrap"><a class="btn btn-sm btn-primary" href="{{ route('admin.kegiatan.show', $item) }}"><i class="bi bi-gear me-1"></i>Kelola Kegiatan</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-secondary py-4">{{ $key === 'aktif' ? 'Belum ada kegiatan aktif.' : 'Belum ada kegiatan nonaktif.' }}</td></tr>
            @endforelse
            </tbody></table></div>
        </div>
        @endforeach
    </div>
</div>
@endsection
