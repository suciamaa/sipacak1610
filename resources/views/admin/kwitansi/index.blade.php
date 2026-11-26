@extends('layouts.app')

@section('content')
<div class="mb-3"><a class="admin-lpd-back" href="{{ route('admin.administrasi.show', $kegiatan) }}"><i class="bi bi-arrow-left"></i> Administrasi Kegiatan</a></div>
<h2>Kelola Kwitansi · {{ $kegiatan->nama_kegiatan }}</h2>

<div class="card p-3 mb-3">
    <h5>Siapkan Data Administrasi Kwitansi</h5>
    <p>Unduh template yang sudah berisi daftar progress petugas per tanggal. Lengkapi data perjalanan dinas pada setiap baris, lalu isi identitas pejabat sebelum mengunggah kembali.</p>
    <a class="btn btn-outline-success mb-3" href="{{ route('admin.kwitansi.template', $kegiatan) }}">Download Template Excel</a>

    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.kwitansi.import', $kegiatan) }}">
        @csrf
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Nama Bendahara Pengeluaran</label>
                <input class="form-control" name="nama_bendahara_pengeluaran" value="{{ old('nama_bendahara_pengeluaran', $admin?->nama_bendahara_pengeluaran) }}" required maxlength="150">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">NIP Bendahara Pengeluaran</label>
                <input class="form-control" name="nip_bendahara_pengeluaran" value="{{ old('nip_bendahara_pengeluaran', $admin?->nip_bendahara_pengeluaran) }}" required maxlength="30">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Nama Pejabat Pembuat Komitmen (PPK)</label>
                <input class="form-control" name="nama_pejabat_pembuat_komitmen" value="{{ old('nama_pejabat_pembuat_komitmen', $admin?->nama_pejabat_pembuat_komitmen) }}" required maxlength="150">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">NIP Pejabat Pembuat Komitmen (PPK)</label>
                <input class="form-control" name="nip_pejabat_pembuat_komitmen" value="{{ old('nip_pejabat_pembuat_komitmen', $admin?->nip_pejabat_pembuat_komitmen) }}" required maxlength="30">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">Upload Excel Final</label>
                <input type="file" class="form-control" name="excel" accept=".xlsx,.xls" required>
                <small class="text-muted">Upload ulang akan menggantikan data Excel sebelumnya. Kwitansi lama akan ditandai untuk dibuat ulang.</small>
            </div>
        </div>
        <button class="btn btn-success">Validasi dan Simpan Excel</button>
    </form>

    @if($errors->any())
        <div class="alert alert-danger mt-3 mb-0">
            <strong>Upload belum diproses.</strong>
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @if(session('import_errors'))
        <hr><h6>Baris yang perlu diperbaiki</h6>
        <ul class="mb-0">@foreach(session('import_errors') as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif
    @if($admin?->uploaded_at)
        <div class="mt-3 small text-muted">
            <div>File Excel terakhir: <strong>{{ $admin->file_excel_original_name ?: basename($admin->file_excel_path) }}</strong></div>
            <div>Diunggah: {{ $admin->uploaded_at->format('d/m/Y H:i') }}</div>
            <div>Data kwitansi tervalidasi: <strong>{{ $admin->rows->count() }}</strong> baris</div>
        </div>
    @endif
</div>

<div class="card p-3 table-responsive">
    <h5>Daftar Kwitansi Harian</h5>
    <table class="table">
        <thead><tr><th>Tanggal</th><th>Petugas</th><th>Wilayah</th><th>Nominal</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            @php
                $matched = $admin?->rows->first(fn($item) => $item->penugasan_id === $row['assignment']->id && $item->tanggal?->toDateString() === $row['progress']->tanggal->toDateString());
                $data = $matched?->data_administrasi ?? [];
            @endphp
            <tr>
                <td>{{ $row['progress']->tanggal->format('d/m/Y') }}</td>
                <td>{{ $row['assignment']->petugas->nama_lengkap }}</td>
                <td>{{ $row['destination'] }}</td>
                <td>{{ isset($data['jumlah_nominal']) ? 'Rp '.number_format((int)$data['jumlah_nominal'],0,',','.') : '—' }}</td>
                <td>
                    @if($row['document']?->status_kwitansi === 'sudah_dibuat')<span class="badge bg-success">Sudah dibuat</span>
                    @elseif($matched)<span class="badge bg-warning text-dark">Siap dibuat</span>
                    @else<span class="badge bg-secondary">Menunggu Excel</span>@endif
                </td>
                <td>
                    @if($matched)
                        <form method="POST" action="{{ route('admin.kwitansi.generate', [$kegiatan, $row['progress']->id]) }}">
                            @csrf
                            <input type="hidden" name="penugasan_id" value="{{ $row['assignment']->id }}">
                            <button class="btn btn-sm btn-success">Generate / Download PDF</button>
                        </form>
                    @else
                        <span class="text-muted">Belum ada data Excel cocok</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted">Belum ada progress yang memenuhi syarat kwitansi.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
