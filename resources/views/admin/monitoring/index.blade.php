@extends('layouts.app')
@section('title', 'Monitoring Progress')
@section('page_title', 'Monitoring Progress')
@section('content')
@php
    $totalPetugas = collect($matrix)->flatten(1)->count();
    $inputHariIni = collect($matrix)->flatten(1)->filter(fn($a) => $a->done_dates->has($today))->count();
    $roleIcon = ['PPL' => 'bi-person-badge', 'PML' => 'bi-person-check', 'Supervisor' => 'bi-person-gear'];
    $totalHari = max($dates->count(), 1);
@endphp
<div class="mb-3"><a class="back-link" href="{{ route('admin.kegiatan.show', $kegiatan) }}"><i class="bi bi-arrow-left"></i> Kelola Kegiatan</a></div>

<div class="page-heading">
    <div>
        <div class="page-eyebrow">Monitoring kegiatan</div>
        <h2>Monitoring Progress Petugas</h2>
        <p>{{ $kegiatan->nama_kegiatan }} · {{ $kegiatan->tanggal_mulai->translatedFormat('d F Y') }} – {{ $kegiatan->tanggal_selesai->translatedFormat('d F Y') }}</p>
    </div>
    <div class="mon-chips">
        <span class="mon-chip"><i class="bi bi-people"></i><strong>{{ $totalPetugas }}</strong> petugas</span>
        <span class="mon-chip is-ok"><i class="bi bi-check2-circle"></i><strong>{{ $inputHariIni }}</strong> input hari ini</span>
    </div>
</div>

<form class="mon-filter card" method="GET" action="{{ route('admin.monitoring.index', $kegiatan) }}">
    <div class="mon-filter-field mon-grow">
        <label class="form-label" for="f-petugas">Nama petugas</label>
        <input id="f-petugas" name="petugas" class="form-control" placeholder="Cari nama petugas" value="{{ request('petugas') }}">
    </div>
    <div class="mon-filter-field">
        <label class="form-label" for="f-role">Role</label>
        <select id="f-role" name="role" class="form-select">
            <option value="">Semua role</option>
            @foreach($roles as $role)<option value="{{ $role }}" @selected(request('role') === $role)>{{ $role }}</option>@endforeach
        </select>
    </div>
    <div class="mon-filter-field">
        <label class="form-label" for="f-tanggal">Tanggal</label>
        <input id="f-tanggal" type="date" name="tanggal" class="form-control" value="{{ request('tanggal') }}" min="{{ $kegiatan->tanggal_mulai->toDateString() }}" max="{{ $kegiatan->tanggal_selesai->toDateString() }}">
    </div>
    <div class="mon-filter-actions">
        <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.monitoring.index', $kegiatan) }}"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a>
    </div>
</form>

<div class="mon-legend">
    <span><i class="bi bi-check-square-fill mon-ico-done"></i> Sudah input progress</span>
    <span><i class="bi bi-square mon-ico-miss"></i> Belum input</span>
    <span><i class="mon-ico-future"></i> Belum waktunya</span>
    <span class="text-secondary">Klik centang untuk membuka detail hari itu.</span>
</div>

@foreach($roles as $role)
@php($rows = $matrix[$role])
@if(request('role') && request('role') !== $role) @continue @endif
<section class="card mon-card">
    <div class="mon-card-head">
        <div class="mon-card-title"><span class="mon-role-icon role-{{ strtolower($role) }}"><i class="bi {{ $roleIcon[$role] }}"></i></span><div><h5>Monitoring {{ $role }}</h5><small>{{ $rows->count() }} petugas · {{ $dates->count() }} hari periode</small></div></div>
    </div>
    <div class="mon-scroll">
        <table class="mon-matrix">
            <thead>
                @if($months->count() > 1)
                <tr class="mon-month-row">
                    <th class="mon-c-no" rowspan="2">No</th>
                    <th class="mon-c-name" rowspan="2">Nama</th>
                    @foreach($months as $m)<th colspan="{{ $m['count'] }}" class="mon-month">{{ $m['label'] }}</th>@endforeach
                    <th rowspan="2" class="mon-c-total">Total</th>
                    <th rowspan="2" class="mon-c-act">Aksi</th>
                </tr>
                <tr>
                    @foreach($dates as $d)<th class="mon-day {{ request('tanggal') === $d->toDateString() ? 'is-picked' : '' }} {{ $d->isSunday() ? 'is-off' : '' }} {{ $d->toDateString() === $today ? 'is-today' : '' }}"><b>{{ $d->format('j') }}</b><small>{{ $d->translatedFormat('D') }}</small></th>@endforeach
                </tr>
                @else
                <tr>
                    <th class="mon-c-no">No</th>
                    <th class="mon-c-name">Nama</th>
                    @foreach($dates as $d)<th class="mon-day {{ request('tanggal') === $d->toDateString() ? 'is-picked' : '' }} {{ $d->isSunday() ? 'is-off' : '' }} {{ $d->toDateString() === $today ? 'is-today' : '' }}"><b>{{ $d->format('j') }}</b><small>{{ $d->translatedFormat('D') }}</small></th>@endforeach
                    <th class="mon-c-total">Total</th>
                    <th class="mon-c-act">Aksi</th>
                </tr>
                @endif
            </thead>
            <tbody>
            @forelse($rows as $a)
                @php($done = $dates->filter(fn($d) => $a->done_dates->has($d->toDateString()))->count())
                <tr>
                    <td class="mon-c-no">{{ $loop->iteration }}</td>
                    <td class="mon-c-name"><strong>{{ $a->petugas->nama_lengkap }}</strong><small>{{ $a->kecamatan->nama_kecamatan ?? '-' }}</small></td>
                    @foreach($dates as $d)
                        @php($key = $d->toDateString())
                        <td class="mon-cell {{ request('tanggal') === $key ? 'is-picked' : '' }} {{ $key === $today ? 'is-today' : '' }}">
                            @if($a->done_dates->has($key))
                                <a href="{{ route('admin.monitoring.show', [$kegiatan, $a->id, 'tanggal' => $key]) }}" title="Lihat progress {{ $d->translatedFormat('d F Y') }}" aria-label="Sudah input {{ $d->translatedFormat('d F Y') }}"><i class="bi bi-check-square-fill mon-ico-done"></i></a>
                            @elseif($key <= $today)
                                <i class="bi bi-square mon-ico-miss" title="Belum input {{ $d->translatedFormat('d F Y') }}"></i>
                            @else
                                <i class="mon-ico-future"></i>
                            @endif
                        </td>
                    @endforeach
                    <td class="mon-c-total"><span class="mon-total {{ $done === 0 ? 'is-zero' : ($done >= $totalHari ? 'is-full' : '') }}">{{ $done }}/{{ $dates->count() }}</span></td>
                    <td class="mon-c-act"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.monitoring.show', [$kegiatan, $a->id]) }}"><i class="bi bi-eye me-1"></i>Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="{{ $dates->count() + 4 }}" class="mon-empty">Belum ada {{ $role }} pada kegiatan ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endforeach

<section class="card mon-card">
    <div class="mon-card-head">
        <div class="mon-card-title"><span class="mon-role-icon"><i class="bi bi-list-check"></i></span><div><h5>Daftar Detail Progress</h5><small>Pilih petugas untuk melihat detail progress per hari</small></div></div>
    </div>
    <div class="table-responsive">
        <table class="table mon-list align-middle">
            <thead><tr><th>Petugas</th><th>Kecamatan</th><th>Role</th><th class="text-center">Hari Progress</th><th class="text-center">Total Detail</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @forelse($assignments as $a)
                @php($total = $a->progress->count())
                @php($details = $a->progress->sum('details_count'))
                <tr>
                    <td><strong>{{ $a->petugas->nama_lengkap }}</strong><div class="small text-secondary">{{ $a->petugas->user->email ?? '' }}</div></td>
                    <td>{{ $a->kecamatan->nama_kecamatan ?? '-' }}</td>
                    <td><span class="badge mon-role-badge role-{{ strtolower($a->role) }}">{{ $a->role }}</span></td>
                    <td class="text-center">{{ $total }} hari</td>
                    <td class="text-center">{{ $details }}</td>
                    <td>
                        <span class="badge {{ $total ? 'text-bg-success' : 'text-bg-secondary' }}"><i class="bi {{ $total ? 'bi-check2' : 'bi-dash' }} me-1"></i>{{ $total ? 'Sudah ada progress' : 'Belum ada progress' }}</span>
                        <div class="small text-secondary mt-1">{{ $total ? 'Terakhir: '.$a->progress->first()->tanggal->format('d/m/Y') : 'Belum menginput' }}</div>
                    </td>
                    <td class="text-end">@if($total)<a class="btn btn-sm btn-primary" href="{{ route('admin.monitoring.show', [$kegiatan, $a->id]) }}"><i class="bi bi-eye me-1"></i>Lihat Progress</a>@else<button class="btn btn-sm btn-outline-secondary" type="button" disabled>Belum tersedia</button>@endif</td>
                </tr>
            @empty
                <tr><td colspan="7" class="mon-empty">Tidak ada petugas yang cocok dengan filter.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($assignments->hasPages())<div class="p-3 pt-0">{{ $assignments->links('pagination::bootstrap-5') }}</div>@endif
</section>
@endsection
