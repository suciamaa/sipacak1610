@extends('layouts.app')
@section('content')
<div class="admin-lpd-page admin-lpd-fields-page">
    <div class="admin-lpd-heading">
        <div>
            <a class="admin-lpd-back" href="{{ route('admin.lpd.index', $kegiatan) }}"><i class="bi bi-arrow-left"></i> Kelola LPD</a>
            <h2>Pengaturan Uraian & Permasalahan</h2>
            <p>Kelola pilihan yang akan digunakan petugas saat mengisi progress <strong>{{ $kegiatan->nama_kegiatan }}</strong>.</p>
        </div>
    </div>

    @if(session('status'))<div class="alert alert-success admin-lpd-alert"><i class="bi bi-check-circle-fill"></i> {{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger admin-lpd-alert"><i class="bi bi-exclamation-circle-fill"></i><div>@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>@endif

    <section class="card admin-lpd-card admin-lpd-section">
        <div class="admin-lpd-card-head"><div><span class="admin-lpd-step">01</span><h5>Menemui Penguasa Wilayah</h5><p>Pilihan bawaan yang berlaku secara global dan tidak dapat diubah dari halaman ini.</p></div><span class="admin-lpd-count">Default global</span></div>
        <div class="admin-lpd-choice-grid">
            @forelse($penguasa as $default)
                <div class="admin-lpd-choice"><i class="bi bi-check-circle-fill"></i><div><strong>{{ $default->nama_uraian }}</strong><span>Pilihan default global</span></div></div>
            @empty
                <div class="admin-lpd-empty"><i class="bi bi-info-circle"></i><span>Pilihan default belum tersedia. Periksa migration dan data master.</span></div>
            @endforelse
        </div>
    </section>

    <section class="card admin-lpd-card admin-lpd-section">
        <div class="admin-lpd-card-head"><div><span class="admin-lpd-step">02</span><h5>Aktivitas yang Dilakukan</h5><p>Aktivitas default tersedia sebagai bawaan. Kamu dapat menambahkan aktivitas khusus untuk kegiatan ini.</p></div></div>
        <div class="table-responsive"><table class="table admin-lpd-table align-middle mb-0"><thead><tr><th>Nama Aktivitas</th><th>Sumber</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
        @forelse($aktivitas as $item)
            <tr>
                <td>@if($item->kegiatan_id === $kegiatan->id && !$item->is_default)<form id="activity-{{ $item->id }}" method="POST" action="{{ route('admin.lpd.uraian.update', [$kegiatan, $item]) }}">@csrf @method('PUT')</form><input form="activity-{{ $item->id }}" class="form-control admin-lpd-input" name="nama_uraian" value="{{ $item->nama_uraian }}" required>@else<strong>{{ $item->nama_uraian }}</strong>@endif</td>
                <td><span class="admin-lpd-source {{ $item->is_default ? 'is-default' : '' }}">{{ $item->is_default ? 'Default global' : 'Kegiatan ini' }}</span></td>
                <td>@if($item->is_default)<span class="admin-lpd-status is-ready">Aktif</span>@else<select form="activity-{{ $item->id }}" class="form-select form-select-sm admin-lpd-select" name="is_active"><option value="1" @selected($item->is_active)>Aktif</option><option value="0" @selected(!$item->is_active)>Nonaktif</option></select>@endif</td>
                <td class="text-end">@if($item->kegiatan_id === $kegiatan->id && !$item->is_default)<button form="activity-{{ $item->id }}" class="btn btn-sm btn-primary admin-lpd-action"><i class="bi bi-check2"></i> Simpan</button><form class="d-inline" method="POST" action="{{ route('admin.lpd.uraian.delete', [$kegiatan, $item]) }}" onsubmit="return confirm('Hapus aktivitas ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger admin-lpd-action"><i class="bi bi-trash"></i></button></form>@else<span class="text-muted small">—</span>@endif</td>
            </tr>
        @empty<tr><td colspan="4" class="text-center text-muted py-4">Belum ada aktivitas yang tersedia.</td></tr>@endforelse
        </tbody></table></div>
        <form method="POST" action="{{ route('admin.lpd.uraian.store', $kegiatan) }}" class="admin-lpd-add-form">@csrf<label for="new-activity">Tambah aktivitas baru</label><div class="input-group"><input id="new-activity" class="form-control admin-lpd-input" name="nama_uraian" placeholder="Contoh: Koordinasi dengan aparat desa" required maxlength="180"><button class="btn btn-primary" type="submit"><i class="bi bi-plus-lg"></i> Tambah Aktivitas</button></div></form>
    </section>

    <section class="card admin-lpd-card admin-lpd-section">
        <div class="admin-lpd-card-head"><div><span class="admin-lpd-step">03</span><h5>Pilihan Permasalahan</h5><p>Atur jenis kendala yang dapat dipilih petugas. Pilihan default global tetap tersedia, sedangkan pilihan khusus kegiatan dapat ditambah, diubah, dinonaktifkan, atau dihapus.</p></div><span class="admin-lpd-count">{{ $masalah->count() }} pilihan</span></div>
        <div class="admin-lpd-problem-list">
            @forelse($masalah as $m)
                <div class="admin-lpd-problem-item">
                    @if(!$m->is_default && $m->kegiatan_id === $kegiatan->id)
                        <form id="problem-{{ $m->id }}" method="POST" action="{{ route('admin.lpd.masalah.update', [$kegiatan, $m]) }}">@csrf @method('PUT')</form>
                        <input form="problem-{{ $m->id }}" class="form-control admin-lpd-input" name="nama_permasalahan" value="{{ $m->nama_permasalahan }}" required maxlength="180">
                    @else
                        <div class="admin-lpd-problem-name"><strong>{{ $m->nama_permasalahan }}</strong><span class="admin-lpd-source is-default">Default global</span></div>
                    @endif
                    <div class="admin-lpd-problem-controls">
                        @if($m->is_default)
                            <span class="admin-lpd-status is-ready"><i class="bi bi-check-circle-fill"></i> Aktif</span>
                        @else
                            <select form="problem-{{ $m->id }}" class="form-select form-select-sm admin-lpd-select" name="is_active"><option value="1" @selected($m->is_active)>Aktif</option><option value="0" @selected(!$m->is_active)>Nonaktif</option></select>
                            <button form="problem-{{ $m->id }}" class="btn btn-sm btn-primary admin-lpd-action"><i class="bi bi-check2"></i> Simpan</button>
                            <form method="POST" action="{{ route('admin.lpd.masalah.delete', [$kegiatan, $m]) }}" onsubmit="return confirm('Hapus pilihan permasalahan ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger admin-lpd-action"><i class="bi bi-trash"></i></button></form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="admin-lpd-empty"><i class="bi bi-inbox"></i><span>Belum ada pilihan permasalahan. Tambahkan pilihan pertama di bawah.</span></div>
            @endforelse
        </div>
        <form method="POST" action="{{ route('admin.lpd.masalah.store', $kegiatan) }}" class="admin-lpd-add-form">@csrf<label for="new-problem">Tambah permasalahan baru</label><div class="input-group"><input id="new-problem" class="form-control admin-lpd-input" name="nama_permasalahan" placeholder="Contoh: Kendala akses lokasi" required maxlength="180"><button class="btn btn-primary" type="submit"><i class="bi bi-plus-lg"></i> Tambah Permasalahan</button></div></form>
    </section>
</div>
@endsection
