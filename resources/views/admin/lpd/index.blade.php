@extends('layouts.app')
@section('content')
<div class="admin-lpd-page">
    <div class="admin-lpd-heading">
        <div>
            <a class="admin-lpd-back" href="{{ route('admin.administrasi.show', $kegiatan) }}"><i class="bi bi-arrow-left"></i> Administrasi Kegiatan</a>
            <h2>Kelola LPD</h2>
            <p>Setiap progress yang tersimpan pada kegiatan <strong>{{ $kegiatan->nama_kegiatan }}</strong> otomatis dibuat menjadi dokumen LPD dan tersedia untuk diunduh.</p>
        </div>
        <a class="btn btn-outline-primary admin-lpd-manage" href="{{ route('admin.lpd.fields', $kegiatan) }}"><i class="bi bi-sliders"></i> Kelola Uraian & Permasalahan</a>
    </div>

    <div class="card admin-lpd-card">
        <div class="admin-lpd-card-head">
            <div><h5>Daftar LPD</h5><p>Dokumen LPD dibuat otomatis dari seluruh data progress yang tersimpan.</p></div>
            <span class="admin-lpd-count">{{ $rows->count() }} data</span>
        </div>
        <div class="table-responsive">
            <table class="table admin-lpd-table align-middle mb-0">
                <thead><tr><th>Tanggal</th><th>Petugas</th><th>Kecamatan</th><th class="text-center">Jumlah SLS</th><th>Status LPD</th><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td class="text-nowrap"><strong>{{ $row['progress']->tanggal->format('d/m/Y') }}</strong></td>
                        <td><span class="admin-lpd-person">{{ $row['penugasan']->petugas->nama_lengkap }}</span></td>
                        <td>{{ $row['penugasan']->kecamatan->nama_kecamatan }}</td>
                        <td class="text-center"><span class="admin-lpd-sls">{{ $row['progress']->details->count() }}</span></td>
                        <td>@if($row['doc']?->status_lpd === 'sudah_dibuat' && $row['doc']?->file_pdf)<span class="admin-lpd-status is-ready"><i class="bi bi-check-circle-fill"></i> Tersedia</span>@else<span class="admin-lpd-status is-error"><i class="bi bi-exclamation-circle"></i> Gagal dibuat</span>@endif</td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.lpd.progress.show', [$kegiatan, $row['progress']]) }}"><i class="bi bi-eye"></i> Lihat Progress</a>
                            @if($row['doc']?->status_lpd === 'sudah_dibuat' && $row['doc']?->file_pdf)
                                <a class="btn btn-sm btn-primary admin-lpd-download" href="{{ route('admin.lpd.download', [$kegiatan, $row['doc']]) }}"><i class="bi bi-download"></i> Download</a>
                            @else
                                <span class="admin-lpd-pending">Dokumen tidak tersedia</span>
                            @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="admin-lpd-empty"><i class="bi bi-inbox"></i><strong>Belum ada progress</strong><span>Dokumen LPD akan muncul otomatis setelah petugas mengisi dan menyimpan progress.</span></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
