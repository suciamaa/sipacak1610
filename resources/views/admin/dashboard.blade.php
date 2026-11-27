@extends('layouts.app')
@section('title', 'Beranda Admin SIPACAK')
@section('page_title', 'Beranda')
@section('content')
<section class="admin-welcome-hero">
 <div class="admin-welcome-copy"><span class="admin-welcome-eyebrow"><i class="bi bi-stars"></i> Sistem kerja lapangan BPS Kabupaten Ogan Ilir</span>
  <h1>Selamat datang di <span>SIPACAK</span></h1>
  <p>Sistem Laporan Lapangan dan Administrasi Cepat Akurat membantu pengelolaan kegiatan, penugasan petugas, pemantauan progress, serta dokumen administrasi dalam satu alur kerja yang terintegrasi.</p>
 </div><div class="admin-welcome-art"><div class="welcome-orbit orbit-one"></div><div class="welcome-orbit orbit-two"></div><div class="welcome-icon"><i class="bi bi-clipboard2-data"></i></div><span class="welcome-float float-a"><i class="bi bi-people-fill"></i> Penugasan</span><span class="welcome-float float-b"><i class="bi bi-graph-up-arrow"></i> Monitoring</span><span class="welcome-float float-c"><i class="bi bi-file-earmark-check"></i> Administrasi</span></div>
</section>
<div class="admin-welcome-section"><div><span class="page-eyebrow">Ruang kerja administrator</span><h2>Semua proses dalam satu tempat</h2><p>Mulai dari menyiapkan kegiatan hingga memastikan laporan dan dokumen tersusun dengan baik.</p></div></div>
<div class="admin-feature-grid">
 <article class="admin-feature-card"><span><i class="bi bi-calendar2-check"></i></span><h3>Kelola Kegiatan</h3><p>Buat kegiatan, atur periode, wilayah, serta role petugas yang dibutuhkan.</p></article>
 <article class="admin-feature-card"><span><i class="bi bi-people"></i></span><h3>Penugasan Petugas</h3><p>Tetapkan petugas ke wilayah kerja secara manual atau melalui template Excel.</p></article>
 <article class="admin-feature-card"><span><i class="bi bi-activity"></i></span><h3>Monitoring Progress</h3><p>Pantau perkembangan laporan lapangan dan dokumentasi dari petugas.</p></article>
 <article class="admin-feature-card"><span><i class="bi bi-folder2-open"></i></span><h3>Administrasi</h3><p>Kelola LPD dan kwitansi yang dihasilkan dari kegiatan lapangan.</p></article>
</div>
<div class="admin-feature-action">
 <div><strong>Siap mengelola kegiatan?</strong><span>Akses daftar kegiatan untuk mengatur wilayah dan penugasan petugas.</span></div>
 <a class="btn btn-primary" href="{{ route('admin.kegiatan.index') }}"><i class="bi bi-calendar2-check me-2"></i>Kelola Kegiatan <i class="bi bi-arrow-right ms-1"></i></a>
</div>
@endsection
