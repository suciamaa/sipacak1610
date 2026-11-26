@extends('layouts.app')
@section('title','Dashboard Petugas')

@php
    $today = now()->startOfDay();
    $totalWilayah = $assignments->pluck('kecamatan_id')->unique()->count();
    $berakhir = $assignments->filter(fn ($a) => $a->kegiatan->status === 'aktif')->map(fn ($a) => $a->kegiatan->tanggal_selesai->copy()->startOfDay())->filter(fn ($d) => $d->gte($today))->sort()->first();
    $sisaHari = $berakhir ? (int) $today->diffInDays($berakhir) : null;
    $namaDepan = \Illuminate\Support\Str::of(auth()->user()->name ?? 'Petugas')->before(' ')->toString();
@endphp

@section('before_main')
<section class="pt-hero" aria-label="Ringkasan petugas">
    <div class="pt-hero-inner">
        <div>
            <div class="pt-today"><i class="bi bi-calendar3"></i>{{ now()->translatedFormat('l, d F Y') }}</div>
            <h1>Halo, {{ $namaDepan }}.</h1>
            <p>@if($assignments->count())Pilih kegiatan di bawah untuk mengisi progress harian. LPD dan kwitansi disusun dari data yang Anda isi.@else Belum ada kegiatan untuk Anda. Admin akan menugaskan kegiatan dan wilayah kerja.@endif</p>
        </div>
        <div class="pt-stats" role="list">
            <div class="pt-stat" role="listitem"><strong>{{ $assignments->count() }}</strong><span>Penugasan aktif</span></div>
            <div class="pt-stat" role="listitem"><strong>{{ $totalWilayah }}</strong><span>Kecamatan kerja</span></div>
            <div class="pt-stat" role="listitem"><strong>{{ $sisaHari === null ? '-' : $sisaHari }}</strong><span>{{ $sisaHari === null ? 'Belum ada tenggat' : 'Hari hingga kegiatan terdekat selesai' }}</span></div>
        </div>
    </div>
</section>
@endsection

@section('content')
<div class="pt-list-head">
    <div>
        <h2>Kegiatan Anda</h2>
        <p>Penugasan yang diberikan admin ke akun ini.</p>
    </div>
</div>

<ul class="pt-list">
@forelse($assignments as $a)
    @php
        $mulai = $a->kegiatan->tanggal_mulai->copy()->startOfDay();
        $selesai = $a->kegiatan->tanggal_selesai->copy()->startOfDay();
        $total = max(1, $mulai->diffInDays($selesai));
        $persen = (int) round(min(1, max(0, $mulai->diffInDays($today, false) / $total)) * 100);
        $sisa = (int) $today->diffInDays($selesai, false);
        $lagi = (int) abs($today->diffInDays($mulai, false));
        $keterangan = $today->lt($mulai) ? 'Mulai '.$lagi.' hari lagi' : ($sisa < 0 ? 'Periode berakhir' : ($sisa === 0 ? 'Berakhir hari ini' : 'Sisa '.$sisa.' hari'));
        $aktif = $a->kegiatan->status === 'aktif';
    @endphp
    <li class="pt-item" data-role="{{ $a->role }}" data-late="{{ $sisa >= 0 && $sisa <= 3 ? 1 : 0 }}" style="--p:{{ $persen }}%">
        <div>
            <h3 class="pt-name">{{ $a->kegiatan->nama_kegiatan }}</h3>
            <div class="pt-meta">
                <span class="pt-role">{{ $a->role }}</span>
                <span><i class="bi bi-geo-alt"></i>{{ $a->kecamatan->nama_kecamatan }}</span>
            </div>
        </div>
        <div>
            <div class="pt-period"><b>{{ $mulai->format('d/m/Y') }} – {{ $selesai->format('d/m/Y') }}</b><span>{{ $keterangan }}</span></div>
            <div class="pt-bar" role="img" aria-label="Periode berjalan {{ $persen }} persen"><i></i></div>
        </div>
        <div class="pt-action">
            <span class="pt-status {{ $aktif ? '' : 'is-off' }}">{{ ucfirst($a->kegiatan->status) }}</span>
            <a class="pt-btn" href="{{ route('petugas.progress.index',$a) }}"><i class="bi bi-folder2-open"></i>Akses Kegiatan</a>
        </div>
    </li>
@empty
    <li class="pt-empty">
        <i class="bi bi-clipboard2-x"></i>
        <strong>Belum ada penugasan</strong>
        <p>Kegiatan yang ditugaskan admin akan muncul di sini. Hubungi admin jika Anda sudah seharusnya mendapat tugas.</p>
    </li>
@endforelse
</ul>
@endsection
