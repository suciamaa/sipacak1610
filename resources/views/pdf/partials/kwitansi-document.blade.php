@php
    $amount = (int) ($data['jumlah_nominal'] ?? 0);
    $rupiah = 'Rp. ' . number_format($amount, 0, ',', '.');
    $origin = trim((string) ($data['asal_penugasan'] ?? ''));
    $destination = trim((string) ($data['daerah_tujuan_perjalanan_dinas'] ?? ''));
    $origin = $origin !== '' ? $origin : 'Ibukota kecamatan ' . $assignment->kecamatan->nama_kecamatan;
    $duration = trim((string)($data['lama_perjalanan'] ?? '1 hari'));
    $letterDate = $data['tanggal_surat_tugas'] ?? null;
    $letterDateText = $letterDate ? \Carbon\Carbon::parse($letterDate)->translatedFormat('d F Y') : '-';
    $month = trim((string)($data['bulan_pengajuan_kwitansi'] ?? ''));
    $nomorSurat = trim((string)($data['nomor_surat_tugas'] ?? '-'));
    $terbilang = \App\Support\TerbilangRupiah::buat($amount);
    $purpose = trim((string)($data['hal'] ?? ''));
    $berangkat = (int)($data['berangkat'] ?? 0);
    $nomorKegiatan = trim((string)($assignment->kegiatan->nomor_kegiatan ?? ''));
    $kwitansiYear = \Carbon\Carbon::parse($data['tanggal_tugas'] ?? $progress->tanggal)->format('Y');
    $transport = 'Transport (PP) (' . $origin . ') - (' . $destination . ') (2 x Rp ' . number_format($berangkat, 0, ',', '.') . ')';
    $journey = $origin . ' ke ' . $destination;
    $logoPath = base_path('templates/logo_bps_kwitansi.jpg');
    $logo = is_file($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
@endphp

{{-- HALAMAN 1: KWITANSI --}}
<div class="page">
    @if($logo)
        <div class="receipt-logo"><img src="data:image/jpeg;base64,{{ $logo }}" alt="Logo BPS"></div>
    @endif
    <h2 class="receipt-title">KWITANSI</h2>

    <div class="line"><span class="label">Sudah Terima dari</span><span class="colon">:</span><span class="value">Pejabat Pembuat Komitmen BPS Kabupaten Ogan Ilir</span></div>
    <div class="line"><span class="label">Uang Sebanyak</span><span class="colon">:</span><span class="value">{{ $rupiah }}</span></div>
    <div class="line"><span class="label">Untuk Pembayaran</span><span class="colon">:</span><span class="value">{{ $purpose }} Program Penyediaan dan Pelayanan Informasi Statistik Anggaran Badan Pusat Statistik Kabupaten Ogan Ilir Tahun Anggaran {{ $kwitansiYear }} selama 1 Hari {{ $nomorKegiatan }} (Rincian terlampir)</span></div>
    <div class="line"><span class="label">Berdasarkan Surat Tugas</span><span class="colon">:</span><span class="value">Nomor: {{ $nomorSurat }} Tanggal: {{ $letterDateText }}</span></div>
    <div class="line"><span class="label">Untuk perjalanan dinas dari</span><span class="colon">:</span><span class="value">{{ $journey }} (PP)</span></div>
    <div class="line"><span class="label">Terbilang</span><span class="colon">:</span><span class="value">{{ $terbilang }}</span></div>

    <table class="sign">
        <tr>
            <td>
                Lunas Pada tanggal:<br>Bendahara Pengeluaran<br>Badan Pusat Statistik<br>Kabupaten Ogan Ilir
                <br><br><br><br><span class="under">{{ $admin->nama_bendahara_pengeluaran }}</span><br>NIP. {{ $admin->nip_bendahara_pengeluaran }}
            </td>
            <td>
                Setuju dibayar:<br>Pejabat Pembuat Komitmen<br>Badan Pusat Statistik Kabupaten<br>Ogan Ilir
                <br><br><br><br><span class="under">{{ $admin->nama_pejabat_pembuat_komitmen }}</span><br>NIP. {{ $admin->nip_pejabat_pembuat_komitmen }}
            </td>
            <td>
                Indralaya,<br>Yang Menerima
                <br><br><br><br><br><br><span class="under">{{ $assignment->petugas->nama_lengkap }}</span><br>NIP. -
            </td>
        </tr>
    </table>
</div>
<div class="page-break"></div>

{{-- HALAMAN 2: PERINCIAN PERHITUNGAN BIAYA PERJALANAN DINAS --}}
<div class="page">
    <h2 class="section-title">PERINCIAN PERHITUNGAN BIAYA PERJALANAN DINAS</h2>
    <div class="ordinary-line"><span>Lampiran Nomor Surat Tugas</span><span>:</span><span>{{ $nomorSurat }}</span></div>
    <div class="ordinary-line"><span>Tanggal</span><span>:</span><span>{{ $letterDateText }}</span></div>

    <table class="grid wide-table detail-table">
        <colgroup><col style="width:7%"><col style="width:53%"><col style="width:20%"><col style="width:20%"></colgroup>
        <thead>
            <tr><th>No.</th><th>Perincian Biaya</th><th>Jumlah</th><th>Keterangan</th></tr>
            <tr><th>(1)</th><th>(2)</th><th>(3)</th><th>(4)</th></tr>
        </thead>
        <tbody>
            <tr>
                <td class="center">1</td>
                <td>
                    <div class="detail-line"><span class="detail-label">Nama yang bertugas</span><span class="detail-colon">:</span><span class="detail-value">{{ $assignment->petugas->nama_lengkap }}</span></div>
                    <div class="detail-line"><span class="detail-label">Golongan</span><span class="detail-colon">:</span><span class="detail-value">-</span></div>
                    <div class="detail-line"><span class="detail-label">Tempat Tugas</span><span class="detail-colon">:</span><span class="detail-value">{{ $destination }}</span></div>
                    <div class="detail-line"><span class="detail-label">Lamanya Tugas</span><span class="detail-colon">:</span><span class="detail-value">{{ $duration }}</span></div>
                </td>
                <td class="right">{{ $rupiah }}</td><td></td>
            </tr>
            <tr><td colspan="2">Total Pengeluaran Riil</td><td class="right">{{ $rupiah }}</td><td></td></tr>
            <tr><td colspan="2">Jumlah</td><td class="right">{{ $rupiah }}</td><td></td></tr>
        </tbody>
    </table>

    <p>Terbilang: {{ $terbilang }}</p>
    <table class="signature">
        <tr>
            <td>
                Telah dibayar sejumlah<br>{{ $rupiah }}<br><br>Lunas pada tanggal:<br>Bendahara Pengeluaran<br>Badan Pusat Statistik<br>Kabupaten Ogan Ilir<br><br><br>
                <span class="under">{{ $admin->nama_bendahara_pengeluaran }}</span><br>NIP. {{ $admin->nip_bendahara_pengeluaran }}
            </td>
            <td>
                Indralaya,<br><br>Telah menerima jumlah uang sebesar<br>{{ $rupiah }}<br><br><br>Yang Menerima<br><br><br><br>
                <span class="under">{{ $assignment->petugas->nama_lengkap }}</span><br>NIP. -
            </td>
        </tr>
    </table>

    <h3 class="center calculation-title">PERHITUNGAN SPD RAMPUNG</h3>
    <table class="calculation">
        <tr><td>Ditetapkan sejumlah</td><td class="right">{{ $rupiah }}</td></tr>
        <tr><td>Yang telah dibayar sejumlah</td><td class="right">Rp. 0</td></tr>
        <tr><td>Sisa kurang/lebih</td><td class="right">{{ $rupiah }}</td></tr>
    </table>
    <div class="ppk-block">
        Pejabat Pembuat Komitmen<br>
        Badan Pusat Statistik<br>
        Kabupaten Ogan Ilir<br><br><br><br>
        <span class="under">{{ $admin->nama_pejabat_pembuat_komitmen }}</span><br>
        NIP. {{ $admin->nip_pejabat_pembuat_komitmen }}
    </div>
</div>
<div class="page-break"></div>

{{-- HALAMAN 3: DAFTAR PENGELUARAN RIIL --}}
<div class="page">
    <h2 class="section-title">DAFTAR PENGELUARAN RIIL</h2>
    <p>Yang bertanda tangan di bawah ini:</p>
    <div class="identity-line"><span>Nama</span><span>:</span><span>{{ $assignment->petugas->nama_lengkap }}</span></div>
    <div class="identity-line"><span>NIP</span><span>:</span><span>-</span></div>
    <div class="identity-line"><span>Jabatan</span><span>:</span><span>Petugas {{ $assignment->role }} {{ $assignment->kegiatan->nama_kegiatan }}</span></div>

    <p>Berdasarkan Surat Tugas Nomor: {{ $nomorSurat }}, Tanggal: {{ $letterDateText }}, dengan ini kami menyatakan:</p>
    <p>1. Biaya transport pegawai dan/atau biaya penginapan di bawah ini yang tidak dapat diperoleh bukti-bukti pengeluarannya, meliputi:</p>

    <table class="grid wide-table expenditure-table">
        <colgroup><col style="width:7%"><col style="width:53%"><col style="width:20%"><col style="width:20%"></colgroup>
        <thead>
            <tr><th>No.</th><th>URAIAN</th><th>Jumlah (Rp)</th><th>Keterangan</th></tr>
            <tr><th>(1)</th><th>(2)</th><th>(3)</th><th>(4)</th></tr>
        </thead>
        <tbody>
            <tr><td class="center">1</td><td>{{ $transport }}</td><td class="right">{{ $rupiah }}</td><td></td></tr>
            <tr><td colspan="2">Jumlah</td><td class="right">{{ $rupiah }}</td><td></td></tr>
        </tbody>
    </table>

    <p>Terbilang: {{ $terbilang }}</p>
    <p>2. Jumlah uang tersebut pada angka 1 di atas benar-benar dikeluarkan untuk pelaksanaan perjalanan dinas dimaksud dan apabila di kemudian hari terdapat kelebihan atas pembayaran, kami bersedia untuk menyetorkan kelebihan tersebut ke Kas Negara.</p>
    <p>Demikian pernyataan ini kami buat dengan sebenarnya, untuk dipergunakan sebagaimana mestinya.</p>

    <table class="signature">
        <tr>
            <td>
                Mengetahui/Menyetujui:<br>Setuju dibayar:<br>Pejabat Pembuat Komitmen<br><br><br><br>
                <span class="under">{{ $admin->nama_pejabat_pembuat_komitmen }}</span><br>NIP. {{ $admin->nip_pejabat_pembuat_komitmen }}
            </td>
            <td>
                Indralaya,<br>Pelaksana SPD<br><br><br><br>
                <span class="under">{{ $assignment->petugas->nama_lengkap }}</span><br>NIP. -
            </td>
        </tr>
    </table>
</div>
