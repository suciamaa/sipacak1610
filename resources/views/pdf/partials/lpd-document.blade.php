@php
    $monthNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    $dateText = $progress->tanggal->format('d') . ' ' .
        ($monthNames[(int) $progress->tanggal->format('n')] ?? '') . ' ' .
        $progress->tanggal->format('Y');
    $photoItems = collect();
    $combinedProblemText = collect($lpdTimeline)->pluck('problems')->filter(fn ($value) => filled($value))->unique()->implode("\n");
    $combinedSolutionText = collect($lpdTimeline)->pluck('solution')->filter(fn ($value) => filled($value))->unique()->implode("\n");
    $timelineRowCount = max(1, count($lpdTimeline));
    $reportTextLength = collect($lpdTimeline)->sum(fn ($entry) => strlen($entry['narrative'] ?? ''));
    $signatureSpacing = count($lpdTimeline) <= 3 && $reportTextLength <= 3200
        ? 'signature-roomy'
        : 'signature-compact';
    foreach ($progress->details as $photoDetail) {
        foreach ($photoDetail->dokumentasi as $photo) {
            $photoItems->push(['photo' => $photo, 'detail' => $photoDetail]);
        }
    }
@endphp

<div class="lpd-report">
    <h1 class="report-title">LAPORAN PERJALANAN DINAS DALAM KOTA &lt; 8 JAM</h1>

    <div class="identity-table">
        <div class="identity-row"><span class="identity-label">Nama Pelaksana</span><span class="identity-colon">:</span><span class="identity-value">{{ $assignment->petugas->nama_lengkap }}</span></div>
        <div class="identity-row"><span class="identity-label">Wilayah Tugas</span><span class="identity-colon">:</span><span class="identity-value">{{ mb_strtoupper($assignment->kecamatan->nama_kecamatan) }}</span></div>
        <div class="identity-row"><span class="identity-label">Tanggal</span><span class="identity-colon">:</span><span class="identity-value">{{ $dateText }}</span></div>
    </div>

    <table class="lpd-table">
        <colgroup>
            <col width="3.2%">
            <col width="15.7%">
            <col width="42.6%">
            <col width="17.0%">
            <col width="21.5%">
        </colgroup>
        <thead>
            <tr class="table-headings">
                <th>No</th>
                <th>Pukul<br>(WIB)</th>
                <th>Uraian Laporan Kegiatan</th>
                <th>Permasalahan</th>
                <th>Pemecahan Masalah</th>
            </tr>
            <tr class="table-indexes">
                <th>(1)</th>
                <th>(2)</th>
                <th>(3)</th>
                <th>(4)</th>
                <th>(5)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lpdTimeline as $entry)
                @if($entry['type'] === 'ishoma')
                    <tr class="ishoma-row">
                        <td class="number-cell">{{ $loop->iteration }}.</td>
                        <td class="time-cell">{{ $entry['time'] }}</td>
                        <td class="ishoma-cell">ISHOMA</td>
                        @if($loop->first)
                        <td class="problem-cell" rowspan="{{ $timelineRowCount }}">{{ $combinedProblemText }}</td>
                        <td class="solution-cell" rowspan="{{ $timelineRowCount }}">{{ $combinedSolutionText }}</td>
                    @endif
                    </tr>
                @else
                    <tr class="activity-row">
                        <td class="number-cell">{{ $loop->iteration }}.</td>
                        <td class="time-cell">{{ $entry['time'] }}</td>
                        <td class="narrative-cell">{{ $entry['narrative'] }}</td>
                        @if($loop->first)
                            <td class="problem-cell" rowspan="{{ $timelineRowCount }}">{{ $combinedProblemText }}</td>
                            <td class="solution-cell" rowspan="{{ $timelineRowCount }}">{{ $combinedSolutionText }}</td>
                        @endif
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="5" class="empty-row">Belum terdapat detail progress untuk tanggal ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-block {{ $signatureSpacing }}">
        <div class="signature-date">{{ mb_strtoupper($assignment->kecamatan->nama_kecamatan) }}, {{ $dateText }}</div>
        <div class="signature-label">Yang Melaporkan</div>
        <div class="signature-name">{{ $assignment->petugas->nama_lengkap }}</div>
    </div>
</div>

@if($photoItems->isEmpty())
    <div class="documentation-page">
        <div class="documentation-title"><span>DOKUMENTASI PELAKSANAAN</span></div>
        <p class="no-documentation">Belum terdapat foto dokumentasi pada progress tanggal ini.</p>
    </div>
@else
    @foreach($photoItems->chunk(6) as $pageIndex => $photoPage)
        <div class="documentation-page">
            <div class="documentation-title"><span>DOKUMENTASI PELAKSANAAN</span></div>

            <table class="photo-grid">
                <tbody>
                    @foreach($photoPage->chunk(2) as $rowIndex => $photoRow)
                        <tr>
                            @foreach($photoRow as $columnIndex => $photoItem)
                                @php
                                    $photo = $photoItem['photo'];
                                    $photoDetail = $photoItem['detail'];
                                    $photoNumber = ($rowIndex * 2) + $columnIndex + 1;
                                    $photoPath = storage_path('app/private/' . $photo->file_path);
                                    $photoSls = mb_strtoupper(trim((string) ($photoDetail->masterSls?->nmsls ?? '')));
                                    if ($photoSls === '') {
                                        $photoSls = mb_strtoupper(trim((string) $photoDetail->sls));
                                        if (str_contains($photoSls, ' / ')) {
                                            $photoSls = trim(explode(' / ', $photoSls, 2)[0]);
                                        }
                                    }
                                    if (($photoDetail->jenis_data_wilayah ?? 'sls') === 'blok_sensus') {
                                        $photoSls = 'Blok Sensus ' . mb_strtoupper(trim((string) ($photoDetail->blok_sensus ?: $photoDetail->sls)));
                                    }
                                    $photoCaption = trim($photoSls . ' - ' . mb_strtoupper($photoDetail->desa), ' -');
                                @endphp
                                <td class="photo-cell">
                                    <table class="photo-frame">
                                        <tr>
                                            <td>
                                                @if(is_file($photoPath))
                                                    <img src="data:{{ $photo->mime_type }};base64,{{ base64_encode(file_get_contents($photoPath)) }}" alt="Dokumentasi {{ $photoCaption }}">
                                                @else
                                                    <span class="photo-missing">Foto dokumentasi tidak ditemukan</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                    <div class="photo-caption">
                                        <strong>{{ $photoNumber }}.</strong> {{ $photoCaption }}<br>
                                        ({{ $dateText }})
                                    </div>
                                </td>
                            @endforeach
                            @if($photoRow->count() === 1)
                                <td class="photo-cell photo-empty-cell"></td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
@endif
