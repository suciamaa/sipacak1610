@extends('layouts.app')
@section('title', $progress->exists ? 'Edit Progress' : 'Input Progress')
@push('styles')
<link href="{{ asset('assets/css/sipacak-progress-form.css') }}?v={{ @filemtime(public_path('assets/css/sipacak-progress-form.css')) }}" rel="stylesheet">
@endpush

@section('content')
@php
    $rows = old('details', $details->isNotEmpty() ? $details->toArray() : [[]]);
    $tglMulai = $penugasan->kegiatan->tanggal_mulai;
    $tglSelesai = $penugasan->kegiatan->tanggal_selesai;
    $hariIni = now()->toDateString();
    $tanggalDefault = $progress->exists
        ? $progress->tanggal->format('Y-m-d')
        : ($hariIni < $tglMulai->format('Y-m-d') ? $tglMulai->format('Y-m-d') : ($hariIni > $tglSelesai->format('Y-m-d') ? $tglSelesai->format('Y-m-d') : $hariIni));

    // Permasalahan, solusi, dan dokumentasi diisi sekali per hari (disimpan pada detail pertama).
    $firstDetail = $details->first();
    $permasalahanValue = old('permasalahan', $firstDetail?->permasalahan_uraian ?? '');
    $solusiValue = old('solusi', $firstDetail?->pemecahan_masalah ?? '');
    $existingPhotos = $details->flatMap(fn ($item) => $item->dokumentasi ?? collect());

    $time = fn ($value) => $value ? substr((string) $value, 0, 5) : '00:00';
    $role = $penugasan->role;
    $activities = [
        ['key' => 'perjalanan', 'title' => 'Perjalanan'],
        ['key' => 'koordinasi', 'title' => 'Koordinasi Penguasa Wilayah (Camat/Kades/Lurah/Ketua SLS)'],
        ['key' => 'kegiatan', 'title' => 'Jenis Kegiatan (Listing/Pendataan/Pengawasan/Supervisi)'],
    ];
@endphp

<div class="pf">
    <nav class="pf-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('petugas.dashboard') }}" aria-label="Beranda"><i class="bi bi-house-door-fill"></i></a>
        <span>/</span>
        <a href="{{ route('petugas.progress.index', $penugasan) }}">Progress</a>
        <span>/</span>
        <span aria-current="page">{{ $progress->exists ? 'Edit Progress' : 'Input Progress' }}</span>
    </nav>

    <header class="pf-hero">
        <span class="pf-hero-icon"><i class="bi bi-bar-chart-fill"></i></span>
        <div>
            <h1>{{ $progress->exists ? 'Edit Progress Laporan Lapangan' : 'Input Progress Laporan Lapangan' }}</h1>
            <p>Lengkapi form berikut sesuai kegiatan di lapangan</p>
        </div>
    </header>

    <form id="progress-form" method="POST" enctype="multipart/form-data" novalidate
          action="{{ $progress->exists ? route('petugas.progress.update', [$penugasan, $progress]) : route('petugas.progress.store', $penugasan) }}">
        @csrf
        @if($progress->exists) @method('PUT') @endif

        <div class="pf-panel">
            <section class="pf-date">
                <span class="pf-date-icon"><i class="bi bi-calendar3"></i></span>
                <div class="pf-date-field">
                    <label for="tanggal-progress">Tanggal</label>
                    <input id="tanggal-progress" class="form-control" type="date" name="tanggal"
                           min="{{ $tglMulai->format('Y-m-d') }}" max="{{ $tglSelesai->format('Y-m-d') }}"
                           title="Periode kegiatan: {{ $tglMulai->format('d/m/Y') }} – {{ $tglSelesai->format('d/m/Y') }}"
                           value="{{ old('tanggal', $tanggalDefault) }}" required>
                    @error('tanggal')<small class="pf-error">{{ $message }}</small>@enderror
                </div>
            </section>

            <div class="pf-banner"><i class="bi bi-people-fill"></i><span>SLS / BS yang Dikunjungi</span></div>

            <div id="details" class="pf-rows">
            @foreach($rows as $i => $d)
                @php
                    $villageId = old("details.$i.desa_kelurahan_id", $d['desa_kelurahan_id'] ?? '');
                    $villageName = old("details.$i.desa", $d['desa'] ?? '');
                    $modeValue = $penugasan->kegiatan->jenis_data_wilayah ?? 'sls';
                    $slsId = old("details.$i.sls_id", $d['sls_id'] ?? '');
                    $subId = old("details.$i.sub_sls_id", $d['sub_sls_id'] ?? '');
                    $blockValue = old("details.$i.blok_sensus", $d['blok_sensus'] ?? '');
                    $areaLabel = old("details.$i.sls", $d['sls'] ?? '');
                    $activityItems = old("details.$i.kegiatan_items", $d['kegiatan_rincian'] ?? [['jam_mulai' => $time($d['kegiatan_jam_mulai'] ?? null), 'jam_selesai' => $time($d['kegiatan_jam_selesai'] ?? null), 'uraian' => $d['kegiatan_uraian'] ?? '']]);
                    if (!is_array($activityItems) || !$activityItems) $activityItems = [['jam_mulai' => '00:00', 'jam_selesai' => '00:00', 'uraian' => '']];
                    $savedOutput = $d['output_rincian'] ?? [];
                    if (is_string($savedOutput)) $savedOutput = json_decode($savedOutput, true) ?: [];
                    if (!$savedOutput) {
                        if ($role === 'PPL' && isset($d['jumlah_responden']) && $d['jumlah_responden'] !== null) $savedOutput['pendataan'] = $d['jumlah_responden'];
                        if ($role !== 'PPL') {
                            if (isset($d['jumlah_responden_diawasi']) && $d['jumlah_responden_diawasi'] !== null) $savedOutput['pendampingan'] = $d['jumlah_responden_diawasi'];
                            if (isset($d['jumlah_dokumen_diperiksa']) && $d['jumlah_dokumen_diperiksa'] !== null) $savedOutput['pemeriksaan'] = $d['jumlah_dokumen_diperiksa'];
                        }
                    }
                    $outputJenis = old("details.$i.output_jenis", array_keys($savedOutput));
                    $outputCounts = old("details.$i.output_jumlah", $savedOutput);
                @endphp

                <article class="pf-card" data-row data-index="{{ $i }}">
                    <header class="pf-card-head">
                        <button type="button" class="pf-card-title" data-toggle aria-expanded="true">
                            <span class="pf-num" data-num>{{ $loop->iteration }}</span>
                            <span class="pf-title-text"><strong data-title>SLS {{ sprintf('%03d', $loop->iteration) }}</strong><em data-subtitle></em></span>
                        </button>
                        <div class="pf-card-actions">
                            <button type="button" class="pf-icon-btn pf-icon-danger" data-remove aria-label="Hapus SLS/BS ini" title="Hapus SLS/BS ini"><i class="bi bi-trash3-fill"></i></button>
                            <button type="button" class="pf-icon-btn" data-collapse aria-label="Ciutkan atau buka" title="Ciutkan / buka"><i class="bi bi-chevron-up"></i></button>
                        </div>
                    </header>
                    <p class="pf-error pf-remove-msg" data-remove-msg hidden>Minimal satu SLS/BS harus dipertahankan.</p>

                    <div class="pf-card-body" data-body>
                        <input type="hidden" name="details[{{ $i }}][id]" value="{{ $d['id'] ?? '' }}">
                        <input type="hidden" data-f="mode" name="details[{{ $i }}][jenis_data_wilayah]" value="{{ $modeValue ?: 'sls' }}">
                        <input type="hidden" data-f="vtype" name="details[{{ $i }}][jenis_wilayah]" value="{{ old("details.$i.jenis_wilayah", $d['jenis_wilayah'] ?? 'desa') }}">
                        <input type="hidden" data-f="vid" name="details[{{ $i }}][desa_kelurahan_id]" value="{{ $villageId }}">
                        <input type="hidden" data-f="vlabel" name="details[{{ $i }}][desa]" value="{{ $villageName }}">
                        <input type="hidden" data-f="slsid" name="details[{{ $i }}][sls_id]" value="{{ $slsId }}">
                        <input type="hidden" data-f="sub" name="details[{{ $i }}][sub_sls_id]" value="{{ $subId }}">
                        <input type="hidden" data-f="blockval" name="details[{{ $i }}][blok_sensus]" value="{{ $blockValue }}">
                        <input type="hidden" data-f="slslabel" name="details[{{ $i }}][sls]" value="{{ $areaLabel }}">

                        <section class="pf-block">
                            <h2 class="pf-h2"><i class="bi bi-geo-alt-fill pf-pin"></i>Wilayah Kerja</h2>
                            <div class="pf-grid pf-grid-3">
                                <div class="pf-field pf-field-kec">
                                    <label>Kecamatan</label>
                                    <select class="form-select" aria-label="Kecamatan penugasan" disabled><option selected value="{{ $penugasan->kecamatan_id }}">{{ $penugasan->kecamatan->nama_kecamatan }}</option></select>
                                </div>
                                <div class="pf-field">
                                    <label>Desa/Kelurahan</label>
                                    <select class="form-select" data-f="village" data-selected-id="{{ $villageId }}" data-selected-name="{{ $villageName }}" required><option value="">Pilih Desa/Kelurahan</option></select>
                                </div>
                                <div class="pf-field">
                                    <label data-area-label>{{ $modeValue === 'sls' ? 'SLS' : 'Blok Sensus' }}</label>
                                    <select class="form-select" data-f="area" required><option value="">Pilih {{ $modeValue === 'sls' ? 'SLS' : 'Blok Sensus' }}</option></select>
                                </div>
                                @if($modeValue === 'sls')
                                <div class="pf-field" data-sub-field>
                                    <label>Sub-SLS</label>
                                    <select class="form-select" data-f="sub-select" required><option value="">Pilih Sub-SLS</option></select>
                                </div>
                                @endif
                            </div>
                            <p class="pf-error" data-region-error hidden></p>
                        </section>

                        <section class="pf-block pf-block-activities">
                            <h2 class="pf-h2 pf-h2-bar">Kegiatan</h2>
                            <ol class="pf-steps">
                                @foreach($activities as $n => $activity)
                                    @php $key = $activity['key']; @endphp
                                    <li class="pf-step">
                                        <span class="pf-step-no">{{ $n + 1 }}</span>
                                        <div class="pf-step-body">
                                            <h3>{{ $activity['title'] }}@if($activity['key'] === 'koordinasi') <span class="pf-opt">Opsional</span>@endif</h3>
                                            @if($key === 'kegiatan')
                                                <p class="pf-error pf-time-warning" data-time-warning hidden role="alert"></p>
                                                <div class="pf-kegiatan-items" data-kegiatan-items>
                                                    @foreach($activityItems as $activityIndex => $item)
                                                        <div class="pf-kegiatan-item" data-kegiatan-item data-item-index="{{ $activityIndex }}">
                                                            <div class="pf-kegiatan-item-head"><strong data-kegiatan-number>Kegiatan {{ $loop->iteration }}</strong>
                                                                <button type="button" class="pf-icon-btn pf-icon-danger" data-remove-kegiatan @if($loop->first) hidden @endif aria-label="Hapus kegiatan"><i class="bi bi-trash3-fill"></i></button>
                                                            </div>
                                                            <div class="pf-grid pf-grid-act">
                                                                <div class="pf-field"><label>Jam Mulai</label><input type="time" class="form-control" data-t="kegiatan-start" name="details[{{ $i }}][kegiatan_items][{{ $activityIndex }}][jam_mulai]" value="{{ $item['jam_mulai'] ?? '00:00' }}" required></div>
                                                                <div class="pf-field"><label>Jam Selesai</label><input type="time" class="form-control" data-t="kegiatan-end" name="details[{{ $i }}][kegiatan_items][{{ $activityIndex }}][jam_selesai]" value="{{ $item['jam_selesai'] ?? '00:00' }}" required></div>
                                                            </div>
                                                            <p class="pf-error pf-time-warning" data-time-warning hidden role="alert"></p>
                                                            <div class="pf-field pf-activity-description"><label>Uraian</label><textarea class="form-control" rows="2" maxlength="2000" placeholder="Jenis aktivitas, lokasi pelaksanaan, dan proses kegiatan di lapangan." name="details[{{ $i }}][kegiatan_items][{{ $activityIndex }}][uraian]" required>{{ $item['uraian'] ?? '' }}</textarea>
                                                                <p class="pf-activity-help">Jelaskan jenis aktivitas yang dilakukan, lokasi pelaksanaan, dan proses kegiatan di lapangan.</p></div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <button type="button" class="pf-btn-outline pf-add-kegiatan" data-add-kegiatan><i class="bi bi-plus-lg"></i> Tambah Kegiatan</button>
                                            @else
                                                <p class="pf-error pf-time-warning" data-time-warning hidden role="alert"></p>
                                                <div class="pf-grid pf-grid-act">
                                                    <div class="pf-field"><label>Jam Mulai</label><input type="time" class="form-control" data-t="{{ $key }}-start" name="details[{{ $i }}][{{ $key }}_jam_mulai]" value="{{ old("details.$i.{$key}_jam_mulai", $time($d["{$key}_jam_mulai"] ?? null)) }}" @if($key !== 'koordinasi') required @endif></div>
                                                    <div class="pf-field"><label>Jam Selesai</label><input type="time" class="form-control" data-t="{{ $key }}-end" name="details[{{ $i }}][{{ $key }}_jam_selesai]" value="{{ old("details.$i.{$key}_jam_selesai", $time($d["{$key}_jam_selesai"] ?? null)) }}" @if($key !== 'koordinasi') required @endif></div>
                                                </div>
                                                <div class="pf-field pf-activity-description"><label>Uraian</label><textarea class="form-control" rows="2" maxlength="2000" placeholder="{{ $key === 'perjalanan' ? 'Rute atau tujuan perjalanan, titik keberangkatan, dan kondisi perjalanan.' : 'Pihak yang ditemui, tujuan koordinasi, dan hasil atau kesepakatan.' }}" name="details[{{ $i }}][{{ $key }}_uraian]" @if($key !== 'koordinasi') required @endif>{{ old("details.$i.{$key}_uraian", $d["{$key}_uraian"] ?? '') }}</textarea>
                                                    @if($key === 'perjalanan')<p class="pf-activity-help">Jelaskan rute atau tujuan perjalanan, titik keberangkatan, dan kondisi perjalanan.</p>@elseif($key === 'koordinasi')<p class="pf-activity-help">Tuliskan pihak yang ditemui, tujuan koordinasi, serta hasil atau kesepakatan yang diperoleh. Bagian ini boleh dikosongkan.</p>@endif</div>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                                <li class="pf-step">
                                    <span class="pf-step-no">4</span>
                                    <div class="pf-step-body">
                                        <h3>Output Kegiatan</h3>
                                        @php
                                            $outputOptions = $role === 'PPL' ? ['listing' => ['Listing', 'SLS/BS'], 'pendataan' => ['Pendataan', 'Responden']] : ['pendampingan' => ['Pendampingan Pendataan Responden', 'SLS/BS'], 'pemeriksaan' => ['Pemeriksaan Dokumen', 'Responden']];
                                        @endphp
                                        <div class="pf-output-options" data-output-options>
                                            @foreach($outputOptions as $outputKey => [$outputLabel, $unitLabel])
                                                <div class="pf-output-option" data-output-option>
                                                    <label class="pf-output-check"><input type="checkbox" name="details[{{ $i }}][output_jenis][]" value="{{ $outputKey }}" data-output-check @checked(in_array($outputKey, (array)$outputJenis, true))><span>{{ $outputLabel }}</span></label>
                                                    <div class="pf-field pf-output-count" data-output-count @if(!in_array($outputKey, (array)$outputJenis, true)) hidden @endif>
                                                        <input type="number" class="form-control" min="0" step="1" name="details[{{ $i }}][output_jumlah][{{ $outputKey }}]" value="{{ $outputCounts[$outputKey] ?? '' }}" placeholder="Masukkan jumlah" data-output-number @if(in_array($outputKey, (array)$outputJenis, true)) required @endif>
                                                        <span class="pf-output-unit">{{ $unitLabel }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <p class="pf-activity-help">Centang jenis output yang dikerjakan, lalu isi jumlah SLS/BS atau responden sesuai jenis output.</p>
                                    </div>
                                </li>
                            </ol>
                        </section>
                    </div>
                </article>
            @endforeach
            </div>

            <div class="pf-add">
                <button type="button" class="pf-btn-outline" id="pf-add-detail"><i class="bi bi-plus-lg"></i>{{ ($penugasan->kegiatan->jenis_data_wilayah ?? 'sls') === 'sls' ? 'Tambah SLS' : 'Tambah Blok Sensus' }}</button>
            </div>

            <section class="pf-issue">
                <span class="pf-round"><i class="bi bi-exclamation-lg"></i></span>
                <div class="pf-issue-body">
                    <h2 class="pf-h2-title">Permasalahan yang Dihadapi</h2>
                    <p class="pf-hint pf-hint-examples">Contoh Permasalahan :
    1) Responden sulit ditemui
    2) Kendala Signal
    3) Pemahaman konsep definisi
    4) Kendala Aplikasi</p>
                    <textarea class="form-control pf-issue-textarea" rows="4" minlength="100" maxlength="5000" name="permasalahan" placeholder="Tuliskan permasalahan yang dihadapi hari ini (minimal 100 karakter)." data-counter required>{{ $permasalahanValue }}</textarea>
                    <p class="pf-counter" data-counter-for>0 / 100 karakter minimum</p>
                </div>
            </section>

            <section class="pf-issue">
                <span class="pf-round"><i class="bi bi-check-lg"></i></span>
                <div class="pf-issue-body">
                    <h2 class="pf-h2-title">Solusi Penyelesaian Masalah</h2>
                    <p class="pf-hint">Uraikan langkah yang dilakukan untuk mengatasi permasalahan dalam 1 hari</p>
                    <textarea class="form-control pf-issue-textarea" rows="4" minlength="100" maxlength="5000" name="solusi" placeholder="Tuliskan langkah penyelesaian masalah (minimal 100 karakter)." data-counter required>{{ $solusiValue }}</textarea>
                    <p class="pf-counter" data-counter-for>0 / 100 karakter minimum</p>
                </div>
            </section>

            <section class="pf-issue pf-issue-docs">
                <span class="pf-round"><i class="bi bi-camera-fill"></i></span>
                <div class="pf-issue-body">
                    <div class="documentation-section">
                        <div class="documentation-head"><div><span class="eyebrow">DOKUMENTASI</span><h4>Dokumentasi Foto</h4><p>Setiap foto maksimal 250 KB. Format JPG/JPEG atau PNG.</p></div></div>
                        <div class="documentation-list">
                            @foreach($existingPhotos as $photo)
                                <div class="documentation-row documentation-existing" data-existing>
                                    <div class="documentation-preview"><img src="{{ route('petugas.progress.documentation', [$penugasan, $photo->id]) }}" alt="Dokumentasi tersimpan"></div>
                                    <span class="documentation-file-name">{{ $photo->nama_file }}</span>
                                    <input type="checkbox" hidden name="hapus_dokumentasi[]" value="{{ $photo->id }}" @checked(in_array($photo->id, old('hapus_dokumentasi', [])))>
                                    <button type="button" class="btn btn-small documentation-remove" data-remove-existing>{{ in_array($photo->id, old('hapus_dokumentasi', [])) ? 'Batal hapus' : 'Hapus' }}</button>
                                </div>
                            @endforeach
                        </div>
                        <div class="new-documentation-list" data-new-documentation-list></div>
                        <button type="button" class="btn btn-add-documentation" data-add-documentation>+ Tambah Foto</button>
                        <p class="pf-error" data-photo-msg aria-live="polite"></p>
                    </div>
                </div>
            </section>
        </div>

        <div class="pf-actions">
            <button type="button" class="pf-btn-ghost" id="reset-form" aria-label="Reset form"><i class="bi bi-arrow-clockwise"></i><span class="pf-btn-label">Reset Form</span></button>
            <button type="submit" class="pf-btn-primary"><i class="bi bi-send-fill"></i>{{ $progress->exists ? 'Simpan Perubahan' : 'Simpan Progress' }}</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('progress-form');
    const details = document.getElementById('details');
    if (!form || !details) return;

    const MAX_PHOTOS = 10;
    const REGION_MODE = @json($penugasan->kegiatan->jenis_data_wilayah ?? 'sls');
    const MAX_BYTES = 250 * 1024;
    const villageUrl = @json(route('petugas.progress.wilayah.desa', $penugasan));
    const blockUrl = @json(route('petugas.progress.wilayah.blok-sensus', $penugasan));
    const slsUrlBase = @json(url('/petugas/penugasan/' . $penugasan->id . '/wilayah/desa'));
    const slsCache = new Map();
    let villagesPromise = null;
    let nextIndex = Math.max(-1, ...[...details.querySelectorAll('[data-row]')].map(row => Number(row.dataset.index))) + 1;

    const f = (row, name) => row.querySelector(`[data-f="${name}"]`);
    const rowsOf = () => [...details.querySelectorAll('[data-row]')];
    const norm = value => String(value ?? '').toUpperCase().replace(/[^A-Z0-9]/g, '');
    const fetchJson = async url => {
        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!response.ok) throw new Error('Data wilayah tidak dapat dimuat. Silakan muat ulang halaman.');
        return response.json();
    };

    /* ---------- daftar desa/kelurahan gabungan (SLS + Blok Sensus) ---------- */
    const loadVillages = () => villagesPromise ||= Promise.all([
        fetchJson(villageUrl),
        fetchJson(blockUrl).catch(() => ({ villages: [] })),
    ]).then(([slsVillages, blockData]) => {
        const map = new Map();
        (Array.isArray(slsVillages) ? slsVillages : []).forEach(v => {
            map.set(String(v.id), { key: String(v.id), id: v.id, name: v.nama_wilayah, sourceName: v.nama_wilayah, type: v.jenis_wilayah || 'desa', hasSls: true, blocks: [] });
        });
        (blockData.villages || []).forEach(v => {
            const key = v.id ? String(v.id) : 'n:' + norm(v.nama_wilayah);
            const current = map.get(key);
            if (current) {
                current.blocks = v.blok_sensus || [];
                current.sourceName = v.nama_wilayah;
            } else {
                map.set(key, { key, id: v.id || null, name: v.nama_wilayah, sourceName: v.nama_wilayah, type: v.jenis_wilayah || 'desa', hasSls: false, blocks: v.blok_sensus || [] });
            }
        });
        return [...map.values()].sort((a, b) => a.type.localeCompare(b.type) || a.name.localeCompare(b.name));
    });
    const loadSls = async villageId => {
        const key = String(villageId);
        if (!slsCache.has(key)) {
            slsCache.set(key, fetchJson(`${slsUrlBase}/${encodeURIComponent(key)}/sls`).catch(error => { slsCache.delete(key); throw error; }));
        }
        return slsCache.get(key);
    };

    const option = (value, label, selected) => {
        const el = document.createElement('option');
        el.value = String(value);
        el.textContent = label;
        if (selected) el.selected = true;
        return el;
    };
    const setVillageOptions = (select, list, selectedKey) => {
        select.innerHTML = '';
        select.appendChild(option('', 'Pilih Desa/Kelurahan', false));
        list.forEach(v => select.appendChild(option(v.key, v.name, String(v.key) === String(selectedKey))));
    };
    const setAreaOptions = (select, slsRows, blocks, selectedValue) => {
        select.innerHTML = '';
        const isSls = REGION_MODE === 'sls';
        select.appendChild(option('', isSls ? 'Pilih SLS' : 'Pilih Blok Sensus', false));
        const items = isSls ? slsRows.map(item => ['sls:' + item.id, item.nmsls]) : blocks.map(code => ['blok:' + code, code]);
        items.forEach(([value, label]) => select.appendChild(option(value, label, value === selectedValue)));
    };

    /* ---------- judul kartu: "SLS 001 • <SLS / Blok Sensus terpilih>" ---------- */
    function refreshTitle(row) {
        const sub = row.querySelector('[data-subtitle]');
        if (!sub) return;
        const isBlock = f(row, 'mode').value === 'blok_sensus';
        const code = isBlock ? f(row, 'blockval').value : f(row, 'slslabel').value;
        sub.textContent = code ? (isBlock ? `Blok Sensus ${code}` : code) : '';
        sub.hidden = !sub.textContent;
    }
    function regionError(row, message) {
        const el = row.querySelector('[data-region-error]');
        if (!el) return;
        el.textContent = message;
        el.hidden = !message;
    }

    /* ---------- wilayah kerja ---------- */
    async function initRegion(row) {
        const village = f(row, 'village'), area = f(row, 'area'), subSelect = f(row, 'sub-select');
        const hMode = f(row, 'mode'), hType = f(row, 'vtype'), hVid = f(row, 'vid'), hVname = f(row, 'vlabel');
        const hSls = f(row, 'slsid'), hSub = f(row, 'sub'), hBlock = f(row, 'blockval'), hLabel = f(row, 'slslabel');
        const initial = {
            villageKey: village.dataset.selectedId || (village.dataset.selectedName ? 'n:' + norm(village.dataset.selectedName) : ''),
            area: REGION_MODE === 'blok_sensus' ? (hBlock.value ? 'blok:' + hBlock.value : '') : (hSls.value ? 'sls:' + hSls.value : ''),
            sls: hSls.value,
            sub: hSub.value,
            keep: true,
        };
        let list = [];
        let slsRows = [];
        let subRows = [];

        const clearHidden = () => {
            hMode.value = REGION_MODE; hSls.value = ''; hSub.value = ''; hBlock.value = ''; hLabel.value = '';
            refreshTitle(row);
        };
        function applyArea() {
            const current = list.find(item => item.key === village.value);
            const value = area.value || '';
            if (!current || !value) { clearHidden(); return; }
            if (REGION_MODE === 'blok_sensus' && value.startsWith('blok:')) {
                const code = value.slice(5);
                hMode.value = 'blok_sensus';
                hVname.value = current.sourceName;
                hBlock.value = code;
                hSls.value = '';
                hSub.value = '';
                hLabel.value = code;
            } else {
                const id = value.slice(4);
                const sls = slsRows.find(item => String(item.id) === String(id));
                hMode.value = 'sls';
                hVname.value = current.name;
                hBlock.value = '';
                hSls.value = id;
                hSub.value = initial.keep && String(id) === String(initial.sls) ? initial.sub : '';
                hLabel.value = sls ? sls.nmsls : '';
                if (subSelect) {
                    const selectedSub = initial.keep && String(id) === String(initial.sls) ? initial.sub : '';
                    const subs = subRows;
                    subSelect.innerHTML = '';
                    subSelect.appendChild(option('', 'Pilih Sub-SLS', false));
                    subs.filter(item => String(item.sls_id) === String(id)).forEach(item => subSelect.appendChild(option(item.id, item.nmsubsls, String(item.id) === String(selectedSub))));
                    hSub.value = selectedSub;
                }
            }
            refreshTitle(row);
        }
        async function loadArea() {
            const current = list.find(item => item.key === village.value);
            regionError(row, '');
            if (!current) {
                hVid.value = ''; hVname.value = '';
                slsRows = [];
                subRows = [];
                if (subSelect) { subSelect.innerHTML = ''; subSelect.appendChild(option('', 'Pilih Sub-SLS', false)); }
                setAreaOptions(area, [], [], '');
                clearHidden();
                return;
            }
            hVid.value = current.id || '';
            hVname.value = current.name;
            hType.value = current.type || 'desa';
            slsRows = [];
            if (REGION_MODE === 'sls' && current.hasSls && current.id) {
                const data = await loadSls(current.id);
                slsRows = data.sls || [];
                subRows = data.sub_sls || [];
            }
            setAreaOptions(area, slsRows, current.blocks || [], initial.keep ? initial.area : '');
            applyArea();
        }

        village.addEventListener('change', () => {
            initial.keep = false;
            loadArea().catch(error => regionError(row, error.message));
        });
        area.addEventListener('change', () => { initial.keep = initial.keep && area.value === initial.area; applyArea(); });
        subSelect?.addEventListener('change', () => { hSub.value = subSelect.value; });

        try {
            list = await loadVillages();
            list = list.filter(item => REGION_MODE === 'sls' ? item.hasSls : item.blocks.length > 0);
            setVillageOptions(village, list, initial.villageKey);
            await loadArea();
        } catch (error) {
            regionError(row, error.message);
        }
        refreshTitle(row);
    }

    /* ---------- jam kegiatan: selesai tidak boleh lebih awal daripada mulai ---------- */
    function bindTimes(row) {
        const pairs = [];
        ['perjalanan', 'koordinasi'].forEach(key => {
            const start = row.querySelector(`[data-t="${key}-start"]`), end = row.querySelector(`[data-t="${key}-end"]`);
            if (start && end) pairs.push([start, end]);
        });
        row.querySelectorAll('[data-kegiatan-item]').forEach(item => {
            const start = item.querySelector('[data-t="kegiatan-start"]'), end = item.querySelector('[data-t="kegiatan-end"]');
            if (start && end) pairs.push([start, end]);
        });
        pairs.forEach(([start, end]) => {
            if (start.dataset.timeBound) return;
            start.dataset.timeBound = end.dataset.timeBound = '1';
            const check = () => {
                let message = '';
                if (start.value && end.value && end.value < start.value) message = 'Jam selesai tidak boleh lebih awal daripada jam mulai.';
                else if (start.value && end.value && start.value < '13:00' && end.value > '12:00') message = 'Kegiatan tidak boleh berlangsung pada waktu istirahat pukul 12.00–13.00. Silakan ubah jam kegiatan.';
                start.setCustomValidity(message); end.setCustomValidity(message);
                const warning = start.closest('[data-kegiatan-item]')?.querySelector('[data-time-warning]') || start.closest('.pf-step-body')?.querySelector('[data-time-warning]');
                if (warning) { warning.textContent = message; warning.hidden = !message; }
            };
            start.addEventListener('input', check); end.addEventListener('input', check); check();
        });
    }

    /* ---------- item jenis kegiatan yang dapat ditambah ---------- */
    function bindActivityItems(card) {
        const list = card.querySelector('[data-kegiatan-items]');
        if (!list || list.dataset.bound) return;
        list.dataset.bound = '1';
        const sync = () => {
            // Selalu bangun ulang indeks name dari indeks item aktual di DOM.
            // Ini mencegah dua item terkirim dengan key kegiatan_items[0] yang sama.
            const items = [...list.querySelectorAll(':scope > [data-kegiatan-item]')];
            items.forEach((item, idx) => {
                item.dataset.itemIndex = String(idx);
                const number = item.querySelector('[data-kegiatan-number]');
                if (number) number.textContent = `Kegiatan ${idx + 1}`;
                const remove = item.querySelector('[data-remove-kegiatan]');
                if (remove) remove.hidden = items.length === 1;
                item.querySelectorAll('[name]').forEach(el => {
                    const match = el.name.match(/^details\[(\d+)\]\[kegiatan_items\]\[\d+\]\[([^\]]+)\]$/);
                    if (match) el.name = `details[${match[1]}][kegiatan_items][${idx}][${match[2]}]`;
                });
            });
        };
        // Sinkronkan sekali lagi tepat sebelum browser mengirim form.
        const form = list.closest('form');
        if (form && !form.dataset.activityItemsSubmitBound) {
            form.dataset.activityItemsSubmitBound = '1';
            form.addEventListener('submit', () => {
                form.querySelectorAll('[data-kegiatan-items]').forEach(container => {
                    const rows = [...container.querySelectorAll(':scope > [data-kegiatan-item]')];
                    rows.forEach((item, idx) => {
                        item.dataset.itemIndex = String(idx);
                        item.querySelectorAll('[name]').forEach(el => {
                            const match = el.name.match(/^details\[(\d+)\]\[kegiatan_items\]\[\d+\]\[([^\]]+)\]$/);
                            if (match) el.name = `details[${match[1]}][kegiatan_items][${idx}][${match[2]}]`;
                        });
                    });
                });
            });
        }
        list.addEventListener('click', e => {
            const btn = e.target.closest('[data-remove-kegiatan]'); if (!btn) return;
            if (list.children.length <= 1) return;
            btn.closest('[data-kegiatan-item]').remove(); sync();
        });
        card.querySelector('[data-add-kegiatan]')?.addEventListener('click', () => {
            const clone = list.querySelector('[data-kegiatan-item]').cloneNode(true);
            clone.querySelectorAll('input,textarea').forEach(el => {
                el.value = el.type === 'time' ? '00:00' : '';
                el.setCustomValidity('');
                if (el.dataset) delete el.dataset.timeBound;
            });
            clone.querySelector('[data-remove-kegiatan]').hidden = false;
            list.appendChild(clone); sync(); bindTimes(card);
            clone.scrollIntoView({behavior:'smooth',block:'center'});
        });
        sync();
    }
    function bindOutputOptions(card) {
        const wrap = card.querySelector('[data-output-options]'); if (!wrap || wrap.dataset.bound) return;
        wrap.dataset.bound = '1';
        const sync = () => {
            const checks = [...wrap.querySelectorAll('[data-output-check]')];
            const any = checks.some(item => item.checked);
            checks.forEach((item, index) => {
                item.required = !any && index === 0;
                const box = item.closest('[data-output-option]').querySelector('[data-output-count]');
                const input = box.querySelector('[data-output-number]');
                box.hidden = !item.checked; input.required = item.checked;
                if (!item.checked) input.value = '';
            });
        };
        wrap.addEventListener('change', e => { if (e.target.closest('[data-output-check]')) sync(); });
        sync();
    }

    /* ---------- dokumentasi foto: satu baris untuk setiap foto ---------- */
    const docList = form.querySelector('[data-new-documentation-list]');
    const docAdd = form.querySelector('[data-add-documentation]');
    const photoMessage = form.querySelector('[data-photo-msg]');
    function addDocumentationRow() {
        const current = docList.querySelectorAll('.new-documentation-row').length + form.querySelectorAll('.documentation-existing:not(.is-removed)').length;
        if (current >= MAX_PHOTOS) { photoMessage.textContent = `Maksimal ${MAX_PHOTOS} foto dokumentasi.`; return; }
        const row = document.createElement('div');
        row.className = 'documentation-row new-documentation-row';
        row.innerHTML = `<div class="documentation-preview"><span>Preview</span></div><div class="documentation-file-area"><span class="documentation-file-label">Pilih Foto</span><label class="documentation-file-wrap"><span class="documentation-choose-button">Pilih foto</span><span class="documentation-file-name"></span><input type="file" class="documentation-file" name="sls[1][dokumentasi_files][]" accept="image/jpeg,image/png" aria-label="Pilih Foto"></label></div><button type="button" class="btn btn-small documentation-remove">Hapus</button>`;
        const input = row.querySelector('input[type=file]');
        const preview = row.querySelector('.documentation-preview');
        input.addEventListener('change', () => {
            const file = input.files?.[0];
            const fileName = row.querySelector('.documentation-file-name');
            if (fileName) fileName.textContent = file ? file.name : '';
            preview.innerHTML = '';
            if (!file) { preview.innerHTML = '<span>Preview</span>'; return; }
            if (!['image/jpeg','image/png'].includes(file.type) || file.size > MAX_BYTES) {
                photoMessage.textContent = !['image/jpeg','image/png'].includes(file.type) ? 'Format foto harus JPG/JPEG atau PNG.' : 'Ukuran setiap foto maksimal 250 KB.';
                input.value = ''; if (fileName) fileName.textContent = ''; preview.innerHTML = '<span>Preview</span>'; return;
            }
            photoMessage.textContent = '';
            const img = document.createElement('img'); img.alt = 'Preview dokumentasi'; img.src = URL.createObjectURL(file); preview.appendChild(img);
        });
        row.querySelector('.documentation-remove').addEventListener('click', () => { row.remove(); photoMessage.textContent = ''; });
        docList.appendChild(row);
    }
    docAdd?.addEventListener('click', addDocumentationRow);
    // Saat mengedit progress yang sudah memiliki dokumentasi tersimpan,
    // jangan tampilkan baris upload kosong secara otomatis. Petugas tetap
    // dapat menambahkan foto baru melalui tombol "+ Tambah Foto".
    const hasExistingDocumentation = form.querySelector('.documentation-existing') !== null;
    if (docList && docList.children.length === 0 && !hasExistingDocumentation) {
        addDocumentationRow();
    }
    form.querySelectorAll('.documentation-existing input[type=checkbox]').forEach(box => {
        const row = box.closest('.documentation-existing');
        const button = row.querySelector('[data-remove-existing]');
        row.classList.toggle('is-removed', box.checked);
        button.textContent = box.checked ? 'Batal hapus' : 'Hapus';
        button.addEventListener('click', () => { box.checked = !box.checked; row.classList.toggle('is-removed', box.checked); button.textContent = box.checked ? 'Batal hapus' : 'Hapus'; });
    });
    const photoCount = () => form.querySelectorAll('.documentation-existing:not(.is-removed)').length + [...docList.querySelectorAll('input[type=file]')].filter(input => input.files?.length).length;

    /* ---------- kartu: ciutkan, hapus, nomor ---------- */
    function setCollapsed(row, collapsed) {
        row.classList.toggle('is-collapsed', collapsed);
        row.querySelector('[data-body]').hidden = collapsed;
        row.querySelector('[data-toggle]').setAttribute('aria-expanded', String(!collapsed));
    }
    function bindCard(row) {
        const toggle = () => setCollapsed(row, !row.classList.contains('is-collapsed'));
        row.querySelector('[data-toggle]').addEventListener('click', toggle);
        row.querySelector('[data-collapse]').addEventListener('click', toggle);
        row.querySelector('[data-remove]').addEventListener('click', () => {
            if (rowsOf().length <= 1) { row.querySelector('[data-remove-msg]').hidden = false; return; }
            row.remove();
            renumber();
        });
    }
    function renumber() {
        const rows = rowsOf();
        rows.forEach((row, index) => {
            row.querySelector('[data-num]').textContent = index + 1;
            row.querySelector('[data-title]').textContent = `${REGION_MODE === 'sls' ? 'SLS' : 'Blok Sensus'} ${String(index + 1).padStart(3, '0')}`;
            row.querySelector('[data-remove]').disabled = rows.length <= 1;
            row.querySelector('[data-remove-msg]').hidden = true;
        });
    }
    function bindRow(row) {
        bindCard(row);
        bindTimes(row);
        bindActivityItems(row);
        bindOutputOptions(row);
        initRegion(row);
    }
    rowsOf().forEach(bindRow);
    renumber();

    /* ---------- tambah SLS ---------- */
    document.getElementById('pf-add-detail')?.addEventListener('click', () => {
        const first = rowsOf()[0];
        if (!first) return;
        const clone = first.cloneNode(true);
        const index = nextIndex++;
        clone.dataset.index = index;
        clone.querySelectorAll('[data-time-bound]').forEach(element => { delete element.dataset.timeBound; });
        clone.querySelectorAll('[data-kegiatan-items], [data-output-options]').forEach(element => { delete element.dataset.bound; });
        clone.querySelectorAll('input, select, textarea').forEach(element => {
            if (element.name) element.name = element.name.replace(/details\[\d+\]/, `details[${index}]`);
            if (element.disabled) return;
            if (element.type === 'hidden') {
                const kind = element.dataset.f;
                element.value = kind === 'mode' ? REGION_MODE : (kind === 'vtype' ? 'desa' : '');
            } else if (element.type === 'checkbox') {
                element.checked = false;
                const option = element.closest('[data-output-option]');
                if (option) { const count = option.querySelector('[data-output-count]'); const number = option.querySelector('[data-output-number]'); if (count) count.hidden = true; if (number) { number.value = ''; number.required = false; } }
            } else if (element.tagName === 'SELECT') {
                element.selectedIndex = 0;
            } else {
                element.value = element.type === 'time' ? '00:00' : '';
                element.setCustomValidity?.('');
            }
            if (element.dataset.selectedId !== undefined) element.dataset.selectedId = '';
            if (element.dataset.selectedName !== undefined) element.dataset.selectedName = '';
        });
        clone.querySelectorAll('[data-region-error], [data-remove-msg]').forEach(el => { el.textContent = el.matches('[data-remove-msg]') ? el.textContent : ''; el.hidden = true; });
        const subtitle = clone.querySelector('[data-subtitle]');
        if (subtitle) { subtitle.textContent = ''; subtitle.hidden = true; }
        details.appendChild(clone);
        // Di HP, ciutkan kartu sebelumnya agar petugas tidak perlu menggulir panjang.
        if (window.matchMedia('(max-width: 640px)').matches) rowsOf().forEach(item => { if (item !== clone) setCollapsed(item, true); });
        setCollapsed(clone, false);
        bindRow(clone);
        renumber();
        clone.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    /* ---------- penghitung karakter (minimal 100) ---------- */
    form.querySelectorAll('textarea[data-counter]').forEach(area => {
        const out = area.parentElement.querySelector('[data-counter-for]');
        if (!out) return;
        const update = () => {
            const n = area.value.trim().length;
            out.textContent = n >= 100 ? `${n} karakter` : `${n} / 100 karakter minimum`;
            out.classList.toggle('is-ok', n >= 100);
        };
        area.addEventListener('input', update); update();
    });

    document.getElementById('reset-form')?.addEventListener('click', () => {
        if (confirm('Kosongkan semua isian dan mulai dari awal? Perubahan yang belum disimpan akan hilang.')) {
            window.location.assign(window.location.pathname);
        }
    });

    /* ---------- validasi ---------- */
    // Field yang tidak valid di kartu yang sedang diciutkan tidak bisa difokuskan browser: buka dulu kartunya.
    form.addEventListener('invalid', event => {
        const row = event.target.closest?.('[data-row]');
        if (row?.classList.contains('is-collapsed')) setCollapsed(row, false);
    }, true);

    form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
            return;
        }
        const message = form.querySelector('[data-photo-msg]');
        const total = photoCount();
        if (total < 1 || total > MAX_PHOTOS) {
            event.preventDefault();
            message.textContent = total < 1 ? 'Tambahkan minimal satu foto dokumentasi.' : `Maksimal ${MAX_PHOTOS} foto dokumentasi.`;
            form.querySelector('.documentation-section')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        for (const row of rowsOf()) {
            const mode = f(row, 'mode').value;
            const missing = mode === 'blok_sensus' ? !f(row, 'blockval').value : (!f(row, 'slsid').value || !f(row, 'sub').value);
            if (missing) {
                event.preventDefault();
                setCollapsed(row, false);
                regionError(row, 'Pilih Desa/Kelurahan dan wilayah kerja sesuai jenis data kegiatan. Untuk mode SLS, Sub-SLS juga wajib dipilih.');
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
        }
    });
})();
</script>
@endpush
