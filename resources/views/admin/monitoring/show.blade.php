@extends('layouts.app')
@section('title', 'Detail Progress Petugas')
@section('page_title', 'Monitoring Progress')
@section('content')
<div class="mb-3"><a class="back-link" href="{{ route('admin.monitoring.index', $kegiatan) }}"><i class="bi bi-arrow-left"></i> Monitoring Progress</a></div>

<div class="page-heading">
    <div>
        <div class="page-eyebrow">Detail progress petugas</div>
        <h2>{{ $penugasan->petugas->nama_lengkap }}</h2>
        <p>{{ $kegiatan->nama_kegiatan }}</p>
    </div>
    <div class="mon-chips">
        <span class="badge mon-role-badge role-{{ strtolower($penugasan->role) }}">{{ $penugasan->role }}</span>
        <span class="mon-chip"><i class="bi bi-geo-alt"></i>{{ $penugasan->kecamatan->nama_kecamatan ?? '-' }}</span>
    </div>
</div>

<div class="mon-stats">
    <div class="mon-stat"><span>Hari progress</span><strong>{{ $totalHari }}</strong></div>
    <div class="mon-stat"><span>Total detail wilayah</span><strong>{{ $totalDetail }}</strong></div>
    <div class="mon-stat"><span>Email</span><strong class="is-text">{{ $penugasan->petugas->user->email ?? '-' }}</strong></div>
</div>

<form class="mon-filter card" method="GET" action="{{ route('admin.monitoring.show', [$kegiatan, $penugasan]) }}">
    <div class="mon-filter-field mon-grow">
        <label class="form-label" for="s-tanggal">Tanggal progress</label>
        <input id="s-tanggal" type="date" name="tanggal" class="form-control" value="{{ request('tanggal') }}" min="{{ $kegiatan->tanggal_mulai->toDateString() }}" max="{{ $kegiatan->tanggal_selesai->toDateString() }}">
    </div>
    <div class="mon-filter-actions">
        <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Tampilkan</button>
        @if(request('tanggal'))<a class="btn btn-outline-secondary" href="{{ route('admin.monitoring.show', [$kegiatan, $penugasan]) }}"><i class="bi bi-x-lg me-1"></i>Semua tanggal</a>@endif
    </div>
</form>

@if($progressList->count() > 1)
<div class="mon-toolbar">
    <span class="text-secondary small">{{ $progressList->total() }} hari progress</span>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary" type="button" data-mon-expand="show"><i class="bi bi-arrows-expand me-1"></i>Buka semua</button>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-mon-expand="hide"><i class="bi bi-arrows-collapse me-1"></i>Tutup semua</button>
    </div>
</div>
@endif

@forelse($progressList as $progress)
@php
    $fotoCount = $progress->details->sum(fn($d) => $d->dokumentasi->count());
    $open = $loop->first || request('tanggal');
@endphp
<article class="card mon-day-card">
    <button class="mon-day-toggle {{ $open ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#day-{{ $progress->id }}" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="day-{{ $progress->id }}">
        <span class="mon-day-date"><b>{{ $progress->tanggal->format('j') }}</b><small>{{ $progress->tanggal->translatedFormat('M') }}</small></span>
        <span class="mon-day-title"><strong>{{ $progress->tanggal->translatedFormat('l, d F Y') }}</strong><small>{{ $progress->details->count() }} wilayah · {{ $fotoCount }} foto · {{ $progress->uraian->count() }} uraian</small></span>
        <i class="bi bi-chevron-down mon-chevron"></i>
    </button>

    <div id="day-{{ $progress->id }}" class="collapse {{ $open ? 'show' : '' }}">
        <div class="mon-day-body">
            <div class="mon-section-label">Wilayah yang dikerjakan</div>
            <div class="mon-regions">
                @foreach($progress->details as $d)
                @php
                    $metrics = array_filter([
                        ['Responden', $d->jumlah_responden],
                        ['Responden diawasi', $d->jumlah_responden_diawasi],
                        ['Dokumen diperiksa', $d->jumlah_dokumen_diperiksa],
                    ], fn($m) => $m[1] !== null);
                @endphp
                <div class="mon-region">
                    <div class="mon-region-head">
                        <span class="mon-region-no">{{ $loop->iteration }}</span>
                        <div class="mon-region-name"><strong>{{ $d->desa }}</strong><small>SLS {{ $d->sls }}</small></div>
                        <span class="mon-time"><i class="bi bi-clock"></i>{{ substr($d->jam_mulai, 0, 5) }}–{{ substr($d->jam_selesai, 0, 5) }}</span>
                    </div>

                    @if(count($metrics))
                    <div class="mon-metrics">
                        @foreach($metrics as [$label, $value])<div><strong>{{ $value }}</strong><span>{{ $label }}</span></div>@endforeach
                    </div>
                    @endif

                    @php
                        $freeActivities = array_filter([
                            ['Perjalanan', $d->perjalanan_jam_mulai, $d->perjalanan_jam_selesai, $d->perjalanan_uraian],
                            ['Koordinasi Penguasa Wilayah', $d->koordinasi_jam_mulai, $d->koordinasi_jam_selesai, $d->koordinasi_uraian],
                            ['Jenis Kegiatan', $d->kegiatan_jam_mulai, $d->kegiatan_jam_selesai, $d->kegiatan_uraian],
                        ], fn ($a) => filled($a[3]));
                    @endphp
                    @if(count($freeActivities) || filled($d->output_uraian))
                    <div class="mon-note"><i class="bi bi-list-check"></i><div><b>Kegiatan</b>
                        @foreach($freeActivities as [$label, $start, $end, $text])
                        <p><strong>{{ $label }}</strong> ({{ substr((string) $start, 0, 5) }}–{{ substr((string) $end, 0, 5) }}): {{ $text }}</p>
                        @endforeach
                        @if(filled($d->output_uraian))<p><strong>Output Kegiatan</strong>: {{ $d->output_uraian }}</p>@endif
                    </div></div>
                    @endif

                    @if(filled($d->permasalahan_uraian))
                    <div class="mon-note"><i class="bi bi-exclamation-circle"></i><div><b>Permasalahan</b><p>{{ $d->permasalahan_uraian }}</p></div></div>
                    @endif

                    @if($d->pemecahan_masalah)
                    <div class="mon-note"><i class="bi bi-lightbulb"></i><div><b>Pemecahan masalah</b><p>{{ $d->pemecahan_masalah }}</p></div></div>
                    @endif

                    @if($d->dokumentasi->isNotEmpty())
                    <div class="mon-photos">
                        @foreach($d->dokumentasi as $doc)
                        <a class="mon-photo" href="{{ route('admin.monitoring.preview', [$kegiatan, $doc]) }}" data-lightbox data-download="{{ route('admin.monitoring.file', [$kegiatan, $doc]) }}" data-caption="{{ $d->desa }} · SLS {{ $d->sls }} — {{ $progress->tanggal->translatedFormat('d F Y') }}" title="Klik untuk memperbesar">
                            <img src="{{ route('admin.monitoring.preview', [$kegiatan, $doc]) }}" alt="Dokumentasi {{ $d->desa }}" loading="lazy">
                            <span class="mon-photo-zoom"><i class="bi bi-arrows-fullscreen"></i></span>
                        </a>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endforeach
            </div>

            <div class="mon-lists">
                <div>
                    <div class="mon-section-label">Uraian kegiatan</div>
                    <div class="mon-tags">@forelse($progress->uraian as $u)<span class="mon-tag">{{ $u->master?->nama_uraian }}@if($u->keterangan_lainnya) — {{ $u->keterangan_lainnya }}@endif</span>@empty<span class="text-secondary small">Tidak ada uraian.</span>@endforelse</div>
                </div>
                <div>
                    <div class="mon-section-label">Permasalahan</div>
                    <div class="mon-tags">@forelse($progress->permasalahan as $m)<span class="mon-tag is-warn">{{ $m->master?->nama_permasalahan }}@if($m->keterangan_lainnya) — {{ $m->keterangan_lainnya }}@endif</span>@empty<span class="text-secondary small">Tidak ada permasalahan.</span>@endforelse</div>
                </div>
            </div>
        </div>
    </div>
</article>
@empty
<div class="card mon-card"><div class="mon-empty">{{ request('tanggal') ? 'Tidak ada progress pada tanggal tersebut.' : 'Petugas ini belum memiliki progress.' }}</div></div>
@endforelse
@if($progressList->hasPages())<div class="mt-3 d-flex justify-content-center">{{ $progressList->links('pagination::bootstrap-5') }}</div>@endif

<div class="modal fade" id="photoModal" tabindex="-1" aria-label="Pratinjau foto" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content mon-lightbox">
            <div class="mon-lightbox-bar">
                <span id="photoCaption" class="mon-lightbox-caption"></span>
                <span class="mon-lightbox-count" id="photoCount"></span>
                <a id="photoDownload" class="btn btn-sm btn-light" href="#"><i class="bi bi-download me-1"></i>Unduh</a>
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="mon-lightbox-stage">
                <button type="button" class="mon-lb-nav is-prev" id="photoPrev" aria-label="Foto sebelumnya"><i class="bi bi-chevron-left"></i></button>
                <img id="photoImg" alt="">
                <button type="button" class="mon-lb-nav is-next" id="photoNext" aria-label="Foto berikutnya"><i class="bi bi-chevron-right"></i></button>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-mon-expand]').forEach(btn => btn.addEventListener('click', () => {
        const act = btn.dataset.monExpand;
        document.querySelectorAll('.mon-day-card .collapse').forEach(el => bootstrap.Collapse.getOrCreateInstance(el, {toggle: false})[act]());
    }));

    const links = Array.from(document.querySelectorAll('[data-lightbox]'));
    const modalEl = document.getElementById('photoModal');
    if (!links.length || !modalEl) return;
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const img = document.getElementById('photoImg');
    const cap = document.getElementById('photoCaption');
    const cnt = document.getElementById('photoCount');
    const dl = document.getElementById('photoDownload');
    const prev = document.getElementById('photoPrev');
    const next = document.getElementById('photoNext');
    let cur = 0;
    const show = i => {
        cur = (i + links.length) % links.length;
        const a = links[cur];
        img.src = a.href; cap.textContent = a.dataset.caption || '';
        dl.href = a.dataset.download || a.href;
        cnt.textContent = links.length > 1 ? (cur + 1) + ' / ' + links.length : '';
        prev.style.visibility = next.style.visibility = links.length > 1 ? 'visible' : 'hidden';
    };
    links.forEach((a, i) => a.addEventListener('click', e => { e.preventDefault(); show(i); modal.show(); }));
    prev.addEventListener('click', () => show(cur - 1));
    next.addEventListener('click', () => show(cur + 1));
    modalEl.addEventListener('keydown', e => { if (e.key === 'ArrowLeft') show(cur - 1); if (e.key === 'ArrowRight') show(cur + 1); });
    modalEl.addEventListener('hidden.bs.modal', () => { img.removeAttribute('src'); });
});
</script>
@endpush
