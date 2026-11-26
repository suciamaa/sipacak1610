@extends('layouts.app')
@section('title','Kwitansi dan Dokumen')
@section('content')
@php
    $tujuan = $penugasan->kegiatan->tujuan_akhir;
    $adaLpd = in_array($tujuan, ['lpd','keduanya']);
    $adaKwitansi = in_array($tujuan, ['kwitansi','keduanya']);
@endphp
<a class="btn btn-sm btn-outline-primary progress-back-button mb-3" href="{{ route('petugas.progress.index', $penugasan) }}"><i class="bi bi-arrow-left me-1"></i>Kembali ke Progress</a>
<h2 class="doc-page-title">Kwitansi dan Dokumen · {{ $penugasan->kegiatan->nama_kegiatan }}</h2>

{{-- Tabel untuk layar lebar --}}
<div class="card p-3 table-responsive doc-table-wrap d-none d-md-block">
    <table class="table align-middle">
        <thead><tr><th>Tanggal</th><th>Nominal Kwitansi</th><th>LPD</th><th>Kwitansi</th></tr></thead>
        <tbody>
        @forelse($days as $day)
            @php $nominal = $day->kwitansi_data['jumlah_nominal'] ?? null; @endphp
            <tr>
                <td>{{ $day->tanggal->format('d/m/Y') }}</td>
                <td>{{ $nominal !== null ? 'Rp '.number_format((int)$nominal, 0, ',', '.') : '—' }}</td>
                <td>
                    @if($adaLpd)
                        @if($doc = $day->lpd->first())
                            <a class="btn btn-sm btn-outline-success" href="{{ route('petugas.documents.lpd', [$penugasan, $doc]) }}">Download LPD</a>
                        @else <span class="text-muted">Belum Dibuat</span> @endif
                    @else — @endif
                </td>
                <td>
                    @if($adaKwitansi)
                        @if($doc = $day->kwitansi->first())
                            <a class="btn btn-sm btn-outline-success" href="{{ route('petugas.documents.kwitansi', [$penugasan, $doc]) }}">Download PDF 3 Halaman</a>
                        @else <span class="text-muted">Belum Dibuat</span> @endif
                    @else — @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted">Belum ada progress harian.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- Kartu untuk HP --}}
<div class="progress-card-list doc-card-list d-md-none">
    @forelse($days as $day)
        @php $nominal = $day->kwitansi_data['jumlah_nominal'] ?? null; @endphp
        <article class="progress-day-card">
            <header class="progress-day-head">
                <div class="progress-day-date">
                    <strong>{{ $day->tanggal->locale('id')->translatedFormat('l') }}</strong>
                    <span>{{ $day->tanggal->format('d/m/Y') }}</span>
                </div>
                @if($adaKwitansi && $nominal !== null)
                    <span class="progress-day-nominal">Rp {{ number_format((int)$nominal, 0, ',', '.') }}</span>
                @endif
            </header>
            <div class="progress-day-foot">
                @if($adaLpd)
                    <div class="doc-slot">
                        <small>LPD</small>
                        @if($doc = $day->lpd->first())
                            <a class="btn btn-sm btn-outline-success" href="{{ route('petugas.documents.lpd', [$penugasan, $doc]) }}"><i class="bi bi-download me-1"></i>Unduh LPD</a>
                        @else <span class="doc-none">Belum dibuat</span> @endif
                    </div>
                @endif
                @if($adaKwitansi)
                    <div class="doc-slot">
                        <small>Kwitansi</small>
                        @if($doc = $day->kwitansi->first())
                            <a class="btn btn-sm btn-outline-success" href="{{ route('petugas.documents.kwitansi', [$penugasan, $doc]) }}"><i class="bi bi-download me-1"></i>Unduh PDF</a>
                        @else <span class="doc-none">Belum dibuat</span> @endif
                    </div>
                @endif
            </div>
        </article>
    @empty
        <div class="empty-state p-3 text-center text-muted">Belum ada progress harian.</div>
    @endforelse
</div>
<div class="progress-history-pagination">{{ $days->links('pagination::bootstrap-5') }}</div>
@endsection
