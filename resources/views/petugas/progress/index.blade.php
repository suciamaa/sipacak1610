@extends('layouts.app')
@section('title','Progress Harian')
@section('content')
<div class="progress-overview-heading d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div class="progress-overview-copy">
        <a class="btn btn-sm btn-outline-primary progress-back-button mb-3" href="{{ route('petugas.dashboard') }}">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
        <span class="page-eyebrow d-block">RINGKASAN PENUGASAN</span>
        <h2 class="mb-1">{{ $penugasan->kegiatan->nama_kegiatan }}</h2>
        <div class="progress-assignment-meta">
            <span><i class="bi bi-geo-alt"></i>{{ $penugasan->kecamatan->nama_kecamatan }}</span>
            <span class="progress-meta-divider"></span>
            <span><i class="bi bi-person-badge"></i>{{ $penugasan->role }}</span>
        </div>
    </div>
    <a class="btn btn-success progress-create-button" href="{{ route('petugas.progress.create',$penugasan) }}">
        <i class="bi bi-plus-lg me-1"></i>Input Progress Harian
    </a>
</div>

<div class="card progress-history-card mb-4">
    <div class="progress-history-heading">
        <div>
            <span class="muted-label">CATATAN KEGIATAN</span>
            <h5 class="mb-1">Riwayat Progress Harian</h5>
            <p class="text-secondary mb-0">Daftar progress yang sudah dicatat untuk penugasan ini.</p>
        </div>
        <span class="progress-history-icon"><i class="bi bi-calendar2-check"></i></span>
    </div>
    <div class="table-scroll-hint d-none"><i class="bi bi-arrow-left-right"></i><span>Geser tabel ke samping untuk melihat seluruh informasi.</span></div>
    <div class="table-responsive progress-history-table-wrap d-none d-md-block" tabindex="0" aria-label="Tabel riwayat progress. Geser secara horizontal untuk melihat seluruh kolom.">
        <table class="table align-middle progress-history-table">
            <thead>
                <tr>
                    <th>Hari</th>
                    <th>Tanggal</th>
                    <th class="text-center">Jam Mulai</th>
                    <th class="text-center">Jam Selesai</th>
                    <th class="col-desa">Desa</th>
                    <th class="col-sls">SLS / Blok</th>
                    <th class="text-center col-aksi">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($progress as $p)
                @php $detailRows = $p->details; $rowCount = max(1, $detailRows->count()); @endphp
                @forelse($detailRows as $detailIndex => $d)
                    <tr>
                        @if($detailIndex === 0)
                            <td rowspan="{{ $rowCount }}"><span class="progress-date-value">{{ $p->tanggal->locale('id')->translatedFormat('l') }}</span></td>
                            <td rowspan="{{ $rowCount }}"><span class="progress-date-value">{{ $p->tanggal->format('d/m/Y') }}</span></td>
                        @endif
                        <td class="text-center text-nowrap">{{ substr($d->jam_mulai,0,5) }}</td>
                        <td class="text-center text-nowrap">{{ substr($d->jam_selesai,0,5) }}</td>
                        <td class="col-desa">{{ $d->desa }}</td>
                        <td class="col-sls">{{ $d->sls }}</td>
                        @if($detailIndex === 0)
                            <td class="text-center col-aksi" rowspan="{{ $rowCount }}">
                                <div class="progress-row-actions">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('petugas.progress.edit',[$penugasan,$p]) }}"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                                    <form class="m-0" method="POST" action="{{ route('petugas.progress.destroy',[$penugasan,$p]) }}" onsubmit="return confirm('Hapus seluruh progress tanggal ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash3 me-1"></i>Hapus</button>
                                    </form>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td>{{ $p->tanggal->locale('id')->translatedFormat('l') }}</td>
                        <td><span class="progress-date-value">{{ $p->tanggal->format('d/m/Y') }}</span></td>
                        <td colspan="4" class="text-secondary">Belum ada detail lokasi.</td>
                        <td class="text-center col-aksi"><div class="progress-row-actions">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('petugas.progress.edit',[$penugasan,$p]) }}">Edit</a>
                            <form class="m-0" method="POST" action="{{ route('petugas.progress.destroy',[$penugasan,$p]) }}" onsubmit="return confirm('Hapus seluruh progress tanggal ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button></form>
                        </div></td>
                    </tr>
                @endforelse
            @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-journal-x"></i></div>
                            <strong>Belum ada progress harian</strong>
                            <p class="mb-0">Progress yang kamu simpan akan tampil di sini.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{-- Tampilan dropdown per tanggal untuk layar HP --}}
    <div class="progress-card-list d-md-none">
        @forelse($progress as $p)
            <details class="progress-day-dropdown">
                <summary class="progress-day-summary">
                    <span class="progress-day-summary-date">
                        <strong>{{ $p->tanggal->locale('id')->translatedFormat('l') }}</strong>
                        <span>{{ $p->tanggal->format('d/m/Y') }}</span>
                    </span>
                    <span class="progress-day-summary-meta">{{ $p->details->count() }} lokasi</span>
                    <i class="bi bi-chevron-down progress-day-chevron" aria-hidden="true"></i>
                </summary>
                <div class="progress-day-dropdown-content">
                    @forelse($p->details as $d)
                        <div class="progress-day-detail">
                            <div class="progress-day-time">{{ substr($d->jam_mulai,0,5) }}–{{ substr($d->jam_selesai,0,5) }}</div>
                            <div class="progress-day-place">
                                <span class="progress-day-village">{{ $d->desa }}</span>
                                <b class="progress-sls-chip">{{ $d->sls }}</b>
                            </div>
                        </div>
                    @empty
                        <div class="progress-day-detail is-empty">Belum ada detail lokasi.</div>
                    @endforelse
                    <div class="progress-day-dropdown-actions">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('petugas.progress.edit',[$penugasan,$p]) }}">
                            <i class="bi bi-pencil-square me-1"></i>Edit
                        </a>
                        <form class="m-0" method="POST" action="{{ route('petugas.progress.destroy',[$penugasan,$p]) }}" onsubmit="return confirm('Hapus seluruh progress tanggal ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash3 me-1"></i>Hapus</button>
                        </form>
                    </div>
                </div>
            </details>
        @empty
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-journal-x"></i></div>
                <strong>Belum ada progress harian</strong>
                <p class="mb-0">Progress yang kamu simpan akan tampil di sini.</p>
            </div>
        @endforelse
    </div>
    @if($progress->hasPages())
        <div class="progress-history-pagination">{{ $progress->links('pagination::bootstrap-5') }}</div>
    @endif
</div>

@if(in_array($penugasan->kegiatan->tujuan_akhir,['kwitansi','lpd','keduanya']))
<div class="progress-download-area">
    <div class="progress-download-intro">
        <span class="muted-label">DOKUMEN PENUGASAN</span>
        <strong>Unduh dokumen</strong>
        <small>Pilih tanggal tertentu atau unduh seluruh dokumen dalam periode penugasan.</small>
    </div>
    <div class="progress-download-actions">
        @if(in_array($penugasan->kegiatan->tujuan_akhir,['kwitansi','keduanya']))
        <details class="download-menu download-menu-kwitansi">
            <summary class="btn btn-download-kwitansi"><i class="bi bi-receipt me-2"></i>Download Kwitansi <i class="bi bi-chevron-down ms-2"></i></summary>
            <div class="download-menu-panel">
                <form method="GET" action="{{ route('petugas.documents.download',[$penugasan,'kwitansi']) }}">
                    <label class="form-label">Download berdasarkan tanggal</label>
                    <input class="form-control mb-2" type="date" name="tanggal" min="{{ $penugasan->kegiatan->tanggal_mulai->format('Y-m-d') }}" max="{{ $penugasan->kegiatan->tanggal_selesai->format('Y-m-d') }}" required>
                    <button class="btn btn-sm btn-download-kwitansi w-100" type="submit">Download tanggal terpilih <i class="bi bi-download ms-1"></i></button>
                </form>
                <div class="dropdown-divider my-3"></div>
                <a class="btn btn-sm btn-outline-warning w-100" href="{{ route('petugas.documents.download',[$penugasan,'kwitansi']) }}">Download seluruh periode (ZIP) <i class="bi bi-download ms-1"></i></a>
                <a class="btn btn-sm btn-download-pdf w-100 mt-2" href="{{ route('petugas.documents.download',[$penugasan,'kwitansi']) }}?format=pdf"><i class="bi bi-file-earmark-pdf me-1"></i>Download Seluruh Dalam 1 PDF</a>
            </div>
        </details>
        @endif

        @if(in_array($penugasan->kegiatan->tujuan_akhir,['lpd','keduanya']))
        <details class="download-menu download-menu-lpd">
            <summary class="btn btn-download-lpd"><i class="bi bi-file-earmark-pdf me-2"></i>Download LPD <i class="bi bi-chevron-down ms-2"></i></summary>
            <div class="download-menu-panel">
                <form method="GET" action="{{ route('petugas.documents.download',[$penugasan,'lpd']) }}">
                    <label class="form-label">Download berdasarkan tanggal</label>
                    <input class="form-control mb-2" type="date" name="tanggal" min="{{ $penugasan->kegiatan->tanggal_mulai->format('Y-m-d') }}" max="{{ $penugasan->kegiatan->tanggal_selesai->format('Y-m-d') }}" required>
                    <button class="btn btn-sm btn-download-lpd w-100" type="submit">Download tanggal terpilih <i class="bi bi-download ms-1"></i></button>
                </form>
                <div class="dropdown-divider my-3"></div>
                <a class="btn btn-sm btn-outline-primary w-100" href="{{ route('petugas.documents.download',[$penugasan,'lpd']) }}">Download seluruh periode (ZIP) <i class="bi bi-download ms-1"></i></a>
                <a class="btn btn-sm btn-download-pdf w-100 mt-2" href="{{ route('petugas.documents.download',[$penugasan,'lpd']) }}?format=pdf"><i class="bi bi-file-earmark-pdf me-1"></i>Download Seluruh Dalam 1 PDF</a>
            </div>
        </details>
        @endif
    </div>
</div>
@endif
@endsection
