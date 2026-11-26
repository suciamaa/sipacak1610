@extends('layouts.app')
@section('content')
<link href="{{ asset('assets/css/sipacak-progress-form.css') }}?v={{ @filemtime(public_path('assets/css/sipacak-progress-form.css')) }}" rel="stylesheet">
<style>
.admin-lpd-view{max-width:1120px;margin:0 auto;padding:24px 16px 48px}.admin-lpd-view .view-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:22px}.admin-lpd-view .view-head h2{margin:8px 0 5px;font-weight:800}.admin-lpd-view .view-sub{color:#687789;margin:0}.admin-lpd-view .view-card{background:#fff;border:1px solid #e4eaf0;border-radius:16px;padding:22px;margin-bottom:18px}.admin-lpd-view .view-card h3{font-size:17px;font-weight:800;margin:0 0 16px;color:#17324d}.admin-lpd-view .view-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.admin-lpd-view .view-item{background:#f7f9fc;border:1px solid #e8edf3;border-radius:10px;padding:12px 14px;min-width:0}.admin-lpd-view .view-item.full{grid-column:1/-1}.admin-lpd-view .view-label{display:block;font-size:12px;font-weight:700;color:#718096;margin-bottom:6px}.admin-lpd-view .view-value{white-space:pre-wrap;overflow-wrap:anywhere;color:#23364a}.admin-lpd-view .view-activity{border-left:4px solid #0093dd;padding-left:14px;margin:14px 0}.admin-lpd-view .view-activity h4{font-size:15px;font-weight:800;margin:0 0 10px}.admin-lpd-view .photo-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.admin-lpd-view .photo-card{border:1px solid #e4eaf0;border-radius:12px;padding:10px}.admin-lpd-view .photo-card img{width:100%;height:190px;object-fit:cover;border-radius:8px;background:#f2f5f8}.admin-lpd-view .photo-card .photo-name{font-size:13px;margin:8px 0;overflow-wrap:anywhere}.admin-lpd-view .read-only-note{display:inline-flex;align-items:center;gap:7px;border-radius:20px;padding:7px 12px;background:#eef7fc;color:#087bb2;font-size:12px;font-weight:700}
@media(max-width:700px){.admin-lpd-view .view-head{display:block}.admin-lpd-view .view-grid{grid-template-columns:1fr}.admin-lpd-view .photo-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:460px){.admin-lpd-view .photo-grid{grid-template-columns:1fr}}
</style>
<div class="admin-lpd-view">
    <div class="view-head">
        <div>
            <a href="{{ route('admin.lpd.index', $kegiatan) }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali ke Kelola LPD</a>
            <h2>Detail Input Progress Petugas</h2>
            <p class="view-sub">{{ $kegiatan->nama_kegiatan }} · {{ $assignment->petugas->nama_lengkap }} · {{ $progress->tanggal->format('d/m/Y') }}</p>
        </div>
        <span class="read-only-note"><i class="bi bi-eye"></i> Mode lihat saja (Admin)</span>
    </div>

    <section class="view-card">
        <h3>Informasi Progress</h3>
        <div class="view-grid">
            <div class="view-item"><span class="view-label">Nama Petugas</span><div class="view-value">{{ $assignment->petugas->nama_lengkap }}</div></div>
            <div class="view-item"><span class="view-label">Role</span><div class="view-value">{{ $assignment->role }}</div></div>
            <div class="view-item"><span class="view-label">Kecamatan</span><div class="view-value">{{ $assignment->kecamatan->nama_kecamatan }}</div></div>
            <div class="view-item"><span class="view-label">Tanggal Progress</span><div class="view-value">{{ $progress->tanggal->translatedFormat('d F Y') }}</div></div>
        </div>
    </section>

    @foreach($progress->details as $index => $detail)
        @php
            $villageType = ($detail->jenis_wilayah ?? 'desa') === 'kelurahan' ? 'Kelurahan' : 'Desa';
            $areaName = ($detail->jenis_data_wilayah ?? 'sls') === 'blok_sensus'
                ? 'Blok Sensus ' . ($detail->blok_sensus ?: $detail->sls)
                : 'SLS ' . ($detail->masterSls?->nmsls ?? $detail->sls) . ($detail->masterSubSls?->nmsubsls ? ' / Sub-SLS ' . $detail->masterSubSls->nmsubsls : '');
        @endphp
        <section class="view-card">
            <h3>{{ ($detail->jenis_data_wilayah ?? 'sls') === 'sls' ? 'SLS' : 'Blok Sensus' }} {{ $index + 1 }} · {{ $detail->desa }}</h3>
            <div class="view-grid">
                <div class="view-item"><span class="view-label">Wilayah</span><div class="view-value">{{ $villageType }} {{ $detail->desa }}</div></div>
                <div class="view-item"><span class="view-label">{{ ($detail->jenis_data_wilayah ?? 'sls') === 'sls' ? 'SLS / Sub-SLS' : 'Blok Sensus' }}</span><div class="view-value">{{ $areaName }}</div></div>
            </div>

            <div class="view-activity">
                <h4>1. Perjalanan</h4>
                <div class="view-grid">
                    <div class="view-item"><span class="view-label">Jam Mulai – Selesai</span><div class="view-value">{{ substr((string)$detail->perjalanan_jam_mulai,0,5) }} – {{ substr((string)$detail->perjalanan_jam_selesai,0,5) }}</div></div>
                    <div class="view-item full"><span class="view-label">Uraian Perjalanan</span><div class="view-value">{{ $detail->perjalanan_uraian }}</div></div>
                </div>
            </div>
            <div class="view-activity">
                <h4>2. Koordinasi Penguasa Wilayah</h4>
                <div class="view-grid">
                    <div class="view-item"><span class="view-label">Jam Mulai – Selesai</span><div class="view-value">{{ substr((string)$detail->koordinasi_jam_mulai,0,5) }} – {{ substr((string)$detail->koordinasi_jam_selesai,0,5) }}</div></div>
                    <div class="view-item full"><span class="view-label">Uraian Koordinasi</span><div class="view-value">{{ $detail->koordinasi_uraian }}</div></div>
                </div>
            </div>
            <div class="view-activity">
                <h4>3. Jenis Kegiatan (Listing/Pendataan/Pengawasan/Supervisi)</h4>
                <div class="view-grid">
                    <div class="view-item"><span class="view-label">Jam Mulai – Selesai</span><div class="view-value">{{ substr((string)$detail->kegiatan_jam_mulai,0,5) }} – {{ substr((string)$detail->kegiatan_jam_selesai,0,5) }}</div></div>
                    <div class="view-item full"><span class="view-label">Uraian Jenis Kegiatan</span><div class="view-value">{{ $detail->kegiatan_uraian }}</div></div>
                    @if($assignment->role === 'PPL')
                        <div class="view-item"><span class="view-label">Jumlah Responden</span><div class="view-value">{{ $detail->jumlah_responden ?? 0 }}</div></div>
                    @else
                        <div class="view-item"><span class="view-label">Responden yang Diawasi</span><div class="view-value">{{ $detail->jumlah_responden_diawasi ?? 0 }}</div></div>
                        <div class="view-item"><span class="view-label">Dokumen yang Diperiksa</span><div class="view-value">{{ $detail->jumlah_dokumen_diperiksa ?? 0 }}</div></div>
                    @endif
                </div>
            </div>
        </section>
    @endforeach

    <section class="view-card">
        <h3>Permasalahan dan Pemecahan Masalah</h3>
        <div class="view-grid">
            <div class="view-item"><span class="view-label">Permasalahan</span><div class="view-value">{{ $progress->details->pluck('permasalahan_uraian')->filter()->unique()->implode("\n") ?: 'Tidak ada keterangan.' }}</div></div>
            <div class="view-item"><span class="view-label">Pemecahan Masalah</span><div class="view-value">{{ $progress->details->pluck('pemecahan_masalah')->filter()->unique()->implode("\n") ?: 'Tidak ada keterangan.' }}</div></div>
        </div>
    </section>

    <section class="view-card">
        <h3>Dokumentasi Foto</h3>
        @php $photos = $progress->details->flatMap(fn($detail) => $detail->dokumentasi); @endphp
        @if($photos->isEmpty())
            <p class="text-muted mb-0">Belum ada foto dokumentasi yang diunggah.</p>
        @else
            <div class="photo-grid">
                @foreach($photos as $photo)
                    <div class="photo-card">
                        <img src="{{ route('admin.lpd.progress.photo', [$kegiatan, $progress, $photo]) }}?preview=1" alt="{{ $photo->nama_file }}" loading="lazy">
                        <div class="photo-name">{{ $photo->nama_file }}</div>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.lpd.progress.photo', [$kegiatan, $progress, $photo]) }}"><i class="bi bi-download"></i> Download Foto</a>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
