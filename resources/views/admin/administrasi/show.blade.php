@extends('layouts.app')
@section('title', 'Administrasi Kegiatan')
@section('page_title', 'Administrasi Kegiatan')
@section('content')
<div class="mb-3"><a class="back-link" href="{{ route('admin.administrasi.index') }}"><i class="bi bi-arrow-left"></i> Daftar Administrasi</a></div>
<div class="page-heading"><div><div class="page-eyebrow">Administrasi berdasarkan kegiatan</div><h2>{{ $kegiatan->nama_kegiatan }}</h2><p>{{ $kegiatan->tanggal_mulai->translatedFormat('d F Y') }} – {{ $kegiatan->tanggal_selesai->translatedFormat('d F Y') }} · <span class="badge {{ $kegiatan->status === 'aktif' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($kegiatan->status) }}</span></p></div></div>
<div class="row g-3">
@if(in_array($kegiatan->tujuan_akhir, ['lpd', 'keduanya'], true))
<div class="col-md-6"><div class="card admin-feature-card h-100 p-4"><div class="admin-feature-icon feature-blue"><i class="bi bi-file-earmark-pdf"></i></div><h5>Kelola LPD</h5><p class="text-secondary">Lihat progress petugas dan buat atau unduh Laporan Perjalanan Dinas untuk kegiatan ini.</p><a class="btn btn-primary mt-auto" href="{{ route('admin.lpd.index', $kegiatan) }}">Buka Kelola LPD <i class="bi bi-arrow-right ms-1"></i></a></div></div>
@endif
@if(in_array($kegiatan->tujuan_akhir, ['kwitansi', 'keduanya'], true))
<div class="col-md-6"><div class="card admin-feature-card h-100 p-4"><div class="admin-feature-icon feature-orange"><i class="bi bi-receipt"></i></div><h5>Kelola Kwitansi</h5><p class="text-secondary">Siapkan data administrasi, impor template, dan buat atau unduh Kwitansi untuk kegiatan ini.</p><a class="btn btn-warning mt-auto" href="{{ route('admin.kwitansi.index', $kegiatan) }}">Buka Kelola Kwitansi <i class="bi bi-arrow-right ms-1"></i></a></div></div>
@endif
</div>
@endsection
