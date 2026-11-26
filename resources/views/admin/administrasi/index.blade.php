@extends('layouts.app')
@section('title', 'Administrasi')
@section('page_title', 'Administrasi')
@section('content')
<div class="page-heading"><div><div class="page-eyebrow">Dokumen kegiatan</div><h2>Administrasi</h2><p>Pilih kegiatan terlebih dahulu untuk mengelola LPD dan Kwitansi.</p></div></div>
<div class="card p-3 p-lg-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div><h5 class="mb-1">Daftar Kegiatan</h5><div class="text-secondary small">Dokumen administrasi dipisahkan berdasarkan kegiatan.</div></div><span class="badge rounded-pill text-bg-light">{{ $kegiatan->total() }} kegiatan</span></div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nama Kegiatan</th><th>Periode</th><th>Petugas</th><th>Progress</th><th>Status</th><th class="text-center">Aksi</th></tr></thead><tbody>
    @forelse($kegiatan as $item)
        <tr><td class="fw-semibold">{{ $item->nama_kegiatan }}</td><td class="text-nowrap">{{ $item->tanggal_mulai->format('d/m/Y') }} – {{ $item->tanggal_selesai->format('d/m/Y') }}</td><td>{{ $item->penugasan_count }}</td><td>{{ $item->progress_count }}</td><td><span class="badge {{ $item->status === 'aktif' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($item->status) }}</span></td><td class="text-center"><a class="btn btn-sm btn-primary" href="{{ route('admin.administrasi.show', $item) }}"><i class="bi bi-folder2-open me-1"></i>Kelola Administrasi</a></td></tr>
    @empty<tr><td colspan="6" class="text-center text-secondary py-4">Belum ada kegiatan.</td></tr>@endforelse
    </tbody></table></div>{{ $kegiatan->links('pagination::bootstrap-5') }}
</div>
@endsection
