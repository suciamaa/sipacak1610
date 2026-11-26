@extends('layouts.app')
@section('title', 'Kegiatan')
@section('page_title', 'Kegiatan')
@section('content')
<div class="page-heading">
    <div><div class="page-eyebrow">Pengelolaan operasional</div><h2>Kegiatan</h2><p>Kelola kegiatan, periode pelaksanaan, dan penugasan petugas.</p></div>
    <a class="btn btn-success" href="{{ route('admin.kegiatan.create') }}"><i class="bi bi-plus-lg me-1"></i>Tambah Kegiatan</a>
</div>
<div class="card p-3 p-lg-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><h5 class="mb-0">Daftar Kegiatan</h5><span class="small text-secondary">{{ $kegiatan->total() }} data</span></div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nama Kegiatan</th><th>Periode</th><th>Penugasan</th><th>Status</th><th>Tujuan Dokumen</th><th class="text-center">Aksi</th></tr></thead><tbody>
    @forelse($kegiatan as $item)
        <tr><td class="fw-semibold">{{ $item->nama_kegiatan }}</td><td class="text-nowrap">{{ $item->tanggal_mulai->format('d/m/Y') }} – {{ $item->tanggal_selesai->format('d/m/Y') }}</td><td>{{ $item->penugasan_count }}</td><td><span class="badge {{ $item->status === 'aktif' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($item->status) }}</span></td><td>{{ $item->tujuan_akhir === 'keduanya' ? 'LPD dan Kwitansi' : ($item->tujuan_akhir === 'lpd' ? 'LPD' : 'Kwitansi') }}</td><td class="text-center text-nowrap"><div class="d-inline-flex gap-1">
            <a class="btn btn-sm btn-primary" href="{{ route('admin.kegiatan.show', $item) }}"><i class="bi bi-gear me-1"></i>Kelola Kegiatan</a>
            <form method="POST" action="{{ route('admin.kegiatan.destroy', $item) }}" onsubmit="return confirm('Hapus kegiatan &quot;{{ e($item->nama_kegiatan) }}&quot;?\n\nSeluruh penugasan dan data administrasi kegiatan ini ikut terhapus. Tindakan ini tidak dapat dibatalkan.')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit" @disabled($item->punya_progress) title="{{ $item->punya_progress ? 'Sudah ada progress petugas, tidak bisa dihapus' : 'Hapus kegiatan' }}"><i class="bi bi-trash3 me-1"></i>Hapus</button></form>
        </div></td></tr>
    @empty
        <tr><td colspan="6" class="text-center text-secondary py-4">Belum ada data kegiatan.</td></tr>
    @endforelse
    </tbody></table></div>
    {{ $kegiatan->links('pagination::bootstrap-5') }}
</div>
@endsection
