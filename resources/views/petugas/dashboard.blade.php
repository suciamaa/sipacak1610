@extends('layouts.app')
@section('title','Dashboard Petugas')

@php
    $today = now()->startOfDay();
    $totalWilayah = $allAssignments->pluck('kecamatan_id')->unique()->count();
    $berakhir = $allAssignments->filter(fn ($a) => $a->kegiatan->status === 'aktif')->map(fn ($a) => $a->kegiatan->tanggal_selesai->copy()->startOfDay())->filter(fn ($d) => $d->gte($today))->sort()->first();
    $sisaHari = $berakhir ? (int) $today->diffInDays($berakhir) : null;
    $namaDepan = \Illuminate\Support\Str::of(auth()->user()->name ?? 'Petugas')->before(' ')->toString();
@endphp

@section('before_main')
<section class="pt-hero" aria-label="Ringkasan petugas">
    <div class="pt-hero-inner">
        <div>
            <div class="pt-today"><i class="bi bi-calendar3"></i>{{ now()->translatedFormat('l, d F Y') }}</div>
            <h1>Halo, {{ $namaDepan }}.</h1>
            <p>@if($allAssignments->count())Pilih kegiatan di bawah untuk mengisi progress harian. LPD dan kwitansi disusun dari data yang Anda isi.@else Belum ada kegiatan untuk Anda. Admin akan menugaskan kegiatan dan wilayah kerja.@endif</p>
        </div>
        <div class="pt-stats" role="list">
            <div class="pt-stat" role="listitem"><strong>{{ $allAssignments->count() }}</strong><span>Penugasan aktif</span></div>
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

<form method="GET" action="{{ route('petugas.dashboard') }}" class="pt-filters" role="search">
    <div class="pt-search-wrap">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari nama kegiatan, kecamatan, atau peran..." aria-label="Cari kegiatan">
    </div>
    <div class="pt-filter-control">
        <label for="filter-year">Tahun</label>
        <select name="year" id="filter-year" class="form-select">
            <option value="">Semua tahun</option>
            @foreach($years as $year)
                <option value="{{ $year }}" @selected(request('year') == $year)>{{ $year }}</option>
            @endforeach
        </select>
    </div>
    <div class="pt-filter-control">
        <label for="filter-sort">Urutan</label>
        <select name="sort" id="filter-sort" class="form-select">
            <option value="terbaru" @selected($sort === 'terbaru')>Terbaru</option>
            <option value="terlama" @selected($sort === 'terlama')>Terlama</option>
        </select>
    </div>
    <button class="pt-filter-submit" type="submit"><i class="bi bi-funnel"></i> Terapkan</button>
    @if(request()->filled('search') || request()->filled('year') || request('sort') === 'terlama')
        <a class="pt-filter-reset" href="{{ route('petugas.dashboard') }}">Reset</a>
    @endif
</form>

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
        <strong>{{ $allAssignments->isEmpty() ? 'Belum ada penugasan' : 'Kegiatan tidak ditemukan' }}</strong>
        <p>{{ $allAssignments->isEmpty() ? 'Kegiatan yang ditugaskan admin akan muncul di sini. Hubungi admin jika Anda sudah seharusnya mendapat tugas.' : 'Coba ubah kata kunci atau filter tahun yang digunakan.' }}</p>
    </li>
@endforelse
</ul>
@endsection
