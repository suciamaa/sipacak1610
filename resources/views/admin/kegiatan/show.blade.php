@extends('layouts.app')
@section('title', 'Kelola Kegiatan')
@section('page_title', 'Kelola Kegiatan')
@section('content')
<div class="mb-3"><a class="back-link" href="{{ route('admin.dashboard', ['tab' => $kegiatan->status]) }}"><i class="bi bi-arrow-left"></i> Dashboard</a></div>
<div class="page-heading">
    <div><div class="page-eyebrow">Kelola kegiatan</div><h2>{{ $kegiatan->nama_kegiatan }}</h2><p>{{ $kegiatan->tanggal_mulai->translatedFormat('d F Y') }} – {{ $kegiatan->tanggal_selesai->translatedFormat('d F Y') }} · <span class="badge {{ $kegiatan->status === 'aktif' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($kegiatan->status) }}</span> · {{ strtoupper($kegiatan->tujuan_akhir) }}</p></div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="{{ route('admin.kegiatan.edit', $kegiatan) }}"><i class="bi bi-pencil me-1"></i>Edit Kegiatan</a>
        <form method="POST" action="{{ route('admin.kegiatan.destroy', $kegiatan) }}" onsubmit="return confirm('Hapus kegiatan &quot;{{ e($kegiatan->nama_kegiatan) }}&quot;?\n\nSeluruh penugasan dan data administrasi kegiatan ini ikut terhapus. Tindakan ini tidak dapat dibatalkan.')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger" type="submit" @disabled($punyaProgress) @if($punyaProgress) title="Sudah ada progress petugas. Nonaktifkan kegiatan sebagai gantinya." @endif><i class="bi bi-trash3 me-1"></i>Hapus Kegiatan</button>
        </form>
    </div>
</div>
@if($punyaProgress)
<div class="alert alert-warning py-2 small"><i class="bi bi-info-circle me-1"></i>Kegiatan ini sudah memiliki progress petugas sehingga tidak bisa dihapus. Ubah statusnya menjadi <strong>Nonaktif</strong> bila sudah tidak dipakai.</div>
@endif
<section class="ad-panel mb-3" aria-label="Ringkasan kegiatan ini">
    <div class="ad-metrics" style="--cols:3">
        <div class="ad-metric"><strong>{{ $kegiatan->penugasan->count() }}</strong><span>Penugasan petugas</span></div>
        <div class="ad-metric"><strong>{{ $totalProgress }}</strong><span>Progress harian</span></div>
        <div class="ad-metric"><strong>{{ $kegiatan->tujuan_akhir === 'keduanya' ? 'LPD + Kwitansi' : ($kegiatan->tujuan_akhir === 'lpd' ? 'LPD' : 'Kwitansi') }}</strong><span>Tujuan dokumen</span></div>
    </div>
</section>
<div class="kegiatan-actions mb-3">
    <a class="btn btn-success" href="{{ route('admin.penugasan.create', $kegiatan) }}"><i class="bi bi-person-plus me-1"></i>Assign Petugas</a>
    @if(in_array($kegiatan->tujuan_akhir, ['lpd', 'keduanya'], true))
    <a class="btn btn-primary" href="{{ route('admin.lpd.index', $kegiatan) }}"><i class="bi bi-file-earmark-pdf me-1"></i>Kelola LPD</a>
    @endif
    @if(in_array($kegiatan->tujuan_akhir, ['kwitansi', 'keduanya'], true))
    <a class="btn btn-warning" href="{{ route('admin.kwitansi.index', $kegiatan) }}"><i class="bi bi-receipt me-1"></i>Kwitansi</a>
    @endif
    <a class="btn btn-outline-secondary" href="{{ route('admin.monitoring.index', $kegiatan) }}"><i class="bi bi-clipboard-data me-1"></i>Monitoring Progress</a>
</div>
<div class="card p-3 p-lg-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><h5 class="mb-0">Petugas yang Ditugaskan</h5><span class="badge text-bg-light">{{ $kegiatan->penugasan->count() }} penugasan</span></div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Nama Petugas</th><th>Email</th><th>Kecamatan</th><th>Role</th><th>Status</th><th class="text-center">Aksi</th></tr></thead><tbody>
    @forelse($kegiatan->penugasan as $p)
        <tr>
            <td class="fw-semibold">{{ $p->petugas->nama_lengkap }}</td>
            <td>{{ $p->petugas->user->email }}</td>
            <td>{{ $p->kecamatan->nama_kecamatan }}</td>
            <td>{{ $p->role }}</td>
            <td><span class="badge {{ $p->status_penugasan === 'aktif' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($p->status_penugasan) }}</span></td>
            <td class="text-center text-nowrap">
                <div class="d-inline-flex gap-1">
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.penugasan.edit', $p) }}"><i class="bi bi-pencil me-1"></i>Edit</a>
                    <form method="POST" action="{{ route('admin.penugasan.destroy', $p) }}" onsubmit="return confirm('Hapus penugasan ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash3 me-1"></i>Hapus</button></form>
                </div>
            </td>
        </tr>
    @empty<tr><td colspan="6" class="text-center text-secondary py-4">Belum ada petugas ditugaskan.</td></tr>@endforelse
    </tbody></table></div>
</div>
@endsection
