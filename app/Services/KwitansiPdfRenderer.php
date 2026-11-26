<?php

namespace App\Services;

use App\Support\TerbilangRupiah;
use Carbon\Carbon;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Renderer PDF Kwitansi Petugas (3 halaman) dengan FPDI + TCPDF.
 *
 * Cara kerja:
 *  - templates/kwitansi_template.pdf = layout kwitansi terbaru TANPA teks. Isinya hanya logo BPS dan garis tabel
 *    (halaman 2 dan 3). Semua teks, termasuk garis bawah nama, digambar oleh kelas ini.
 *  - Tiap halaman template di-import FPDI, lalu teks ditimpa (overlay) berdasarkan koordinat.
 *
 * Koordinat: satuan point (pt), asal di kiri-atas halaman A4 (595.28 x 841.89), sumbu Y ke bawah.
 * Angka X = sisi kiri teks, angka Y = BASELINE teks (garis dasar huruf), sama dengan posisi pada PDF acuan.
 * Font Times 9 pt.
 */
class KwitansiPdfRenderer
{
    public const PAGE_W = 595.28;
    public const PAGE_H = 841.89;

    private const FONT = 'times';
    private const SIZE = 9.0;

    /** Koreksi halus (pt) bila seluruh teks terlihat naik/turun. Positif = teks turun. */
    private const BASELINE_FIX = 0.0;

    /** Jarak garis bawah nama dari baseline (pt) dan ketebalannya. */
    private const UNDERLINE_GAP = 1.6;
    private const UNDERLINE_WIDTH = 0.45;

    private const PURPOSE_SUFFIX = 'Program Penyediaan dan Pelayanan Informasi Statistik Anggaran Badan Pusat Statistik Kabupaten Ogan Ilir Tahun Anggaran';

    private const MONTHS = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    private Fpdi $pdf;

    /** @var array<int,mixed> */
    private array $tpl = [];

    /**
     * @param  array<int,array{assignment:mixed,progress:mixed,admin:mixed,data:array}>  $items
     * @return string biner PDF (3 halaman per item)
     */
    public function render(array $items): string
    {
        if (! $items) {
            throw new \InvalidArgumentException('Tidak ada data kwitansi untuk dirender.');
        }

        $templatePath = base_path('templates/kwitansi_template.pdf');
        if (! is_file($templatePath)) {
            throw new \RuntimeException('Template PDF kwitansi tidak ditemukan: ' . $templatePath);
        }

        $pdf = new Fpdi('P', 'pt', [self::PAGE_W, self::PAGE_H], true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0, true);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->setCellPadding(0);
        $pdf->setCellMargins(0, 0, 0, 0);
        $pdf->SetCreator('SIPACAK');
        $pdf->SetTitle('Kwitansi Petugas');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetDrawColor(0, 0, 0);
        $this->pdf = $pdf;

        $pageCount = $pdf->setSourceFile($templatePath);
        if ($pageCount < 3) {
            throw new \RuntimeException('Template kwitansi harus berisi 3 halaman.');
        }
        for ($n = 1; $n <= 3; $n++) {
            $this->tpl[$n] = $pdf->importPage($n);
        }

        foreach ($items as $item) {
            $d = self::payload($item['assignment'], $item['progress'], $item['admin'], $item['data'] ?? []);
            $this->page(1, fn () => $this->page1($d));
            $this->page(2, fn () => $this->page2($d));
            $this->page(3, fn () => $this->page3($d));
        }

        return $pdf->Output('kwitansi.pdf', 'S');
    }

    /**
     * Menyiapkan seluruh teks.
     *
     * @return array<string,mixed>
     */
    public static function payload($assignment, $progress, $admin, array $data): array
    {
        $amount = (int) ($data['jumlah_nominal'] ?? 0);
        $rupiah = 'Rp ' . number_format($amount, 0, ',', '.');

        $origin = trim((string) ($data['asal_penugasan'] ?? ''));
        $destination = trim((string) ($data['daerah_tujuan_perjalanan_dinas'] ?? ''));
        $origin = $origin !== '' ? $origin : 'Ibukota kecamatan ' . ($assignment->kecamatan->nama_kecamatan ?? '');

        $letterDate = $data['tanggal_surat_tugas'] ?? null;
        $berangkat = (int) ($data['berangkat'] ?? 0);
        $dutyDate = $data['tanggal_tugas'] ?? $progress->tanggal;
        $year = Carbon::parse($dutyDate)->format('Y');

        return [
            'rupiah' => $rupiah,
            'terbilang' => TerbilangRupiah::buat($amount),
            'purpose' => trim((string) ($data['hal'] ?? '')),
            'year' => $year,
            'monthYear' => self::monthYear((string) ($data['bulan_pengajuan_kwitansi'] ?? ''), $year),
            'nomorKegiatan' => trim((string) ($assignment->kegiatan->nomor_kegiatan ?? '')),
            'nomorSurat' => trim((string) ($data['nomor_surat_tugas'] ?? '-')),
            'tanggalSurat' => $letterDate ? Carbon::parse($letterDate)->locale('id')->translatedFormat('d F Y') : '-',
            'journey' => $origin . ' ke ' . $destination,
            'destination' => $destination,
            'duration' => trim((string) ($data['lama_perjalanan'] ?? '1 hari')),
            'transport' => 'Transport (PP) (' . $origin . ') - (' . $destination . ') (2 x Rp ' . number_format($berangkat, 0, ',', '.') . ')',
            'petugas' => trim((string) ($assignment->petugas->nama_lengkap ?? '')),
            'jabatan' => trim('Petugas ' . ($assignment->role ?? '') . ' ' . ($assignment->kegiatan->nama_kegiatan ?? '')),
            'bendahara' => trim((string) $admin->nama_bendahara_pengeluaran),
            'nipBendahara' => trim((string) $admin->nip_bendahara_pengeluaran),
            'ppk' => trim((string) $admin->nama_pejabat_pembuat_komitmen),
            'nipPpk' => trim((string) $admin->nip_pejabat_pembuat_komitmen),
        ];
    }

    /**
     * "(bulan pengajuan) (tahun)" untuk baris "Indralaya, ...".
     * Kolom Excel boleh berisi nama bulan ("Oktober"), angka 1-12, atau tanggal (2026-10-05).
     * Tahun mengikuti tahun tanggal tugas, kecuali isian Excel sudah memuat tahun sendiri.
     */
    public static function monthYear(string $raw, string $year): string
    {
        $raw = trim(preg_replace('/\s+/u', ' ', $raw) ?? '');
        if ($raw === '') {
            return '';
        }
        if (ctype_digit($raw) && (int) $raw >= 1 && (int) $raw <= 12) {
            $raw = self::MONTHS[(int) $raw];
        } elseif (preg_match('/^\d{4}-\d{1,2}(-\d{1,2})?/', $raw)) {
            try {
                $date = Carbon::parse($raw);
                $raw = self::MONTHS[$date->month];
                $year = $date->format('Y');
            } catch (\Throwable) {
                // dibiarkan apa adanya
            }
        } else {
            $raw = mb_convert_case($raw, MB_CASE_TITLE, 'UTF-8');
        }

        return preg_match('/\b\d{4}\b/', $raw) ? $raw : $raw . ' ' . $year;
    }

    // ------------------------------------------------------------------------------------------------
    // HALAMAN 1 - KWITANSI
    // ------------------------------------------------------------------------------------------------

    private function page1(array $d): void
    {
        $this->centered('KWITANSI', self::PAGE_W / 2, 89.89, 'B');

        $labelX = 66.0;
        $colonX = 198.0;
        $valueX = 208.0;
        $valueW = 305.0;

        $row = function (float $y, string $label, string $value) use ($labelX, $colonX, $valueX) {
            $this->text($labelX, $y, $label);
            $this->text($colonX, $y, ':');
            $this->text($valueX, $y, $value);
        };

        $row(113.89, 'Sudah Terima dari', 'Pejabat Pembuat Komitmen BPS Kabupaten Ogan Ilir');
        $row(130.89, 'Uang sebanyak', $d['rupiah']);

        // Untuk pembayaran: paragraf, jarak antarbaris 12 pt.
        $paragraph = trim($d['purpose'] . ' ' . self::PURPOSE_SUFFIX . ' ' . $d['year'] . ' selama 1 Hari ' . $d['nomorKegiatan'] . ' (Rincian terlampir)');
        $this->text($labelX, 147.89, 'Untuk pembayaran');
        $this->text($colonX, 147.89, ':');
        $y = $this->flow($valueX, 147.89, $paragraph, $valueW, 12.0, 99)['last'];

        // Baris-baris berikutnya tetap pada koordinat acuan selama paragraf <= 4 baris; bila lebih, ikut turun.
        $y = max($y + 20.0, 203.89);
        $row($y, 'Berdasarkan Surat Tugas', 'Nomor: ' . $d['nomorSurat'] . ' Tanggal: ' . $d['tanggalSurat']);
        $y += 17.0;
        $this->text($labelX, $y, 'Untuk perjalanan dinas dari');
        $this->text($colonX, $y, ':');
        $y = $this->flow($valueX, $y, $d['journey'] . ' (PP)', $valueW, 12.0, 99)['last'];
        $y = max($y + 22.0, 254.89);
        $row($y, 'Terbilang', $d['terbilang']);

        // Kolom kiri (Bendahara).
        $this->block(66.0, [[336.89, 'Lunas Pada tanggal:'], [348.89, 'Bendahara Pengeluaran'], [360.89, 'Badan Pusat Statistik'], [372.89, 'Kabupaten Ogan Ilir']]);
        $this->text(66.0, 426.89, $d['bendahara'], 'U');
        $this->text(66.0, 438.89, 'NIP. ' . $d['nipBendahara']);

        // Kolom tengah (PPK).
        $this->block(240.0, [[336.89, 'Setuju dibayar:'], [348.89, 'Pejabat Pembuat Komitmen'], [360.89, 'Badan Pusat Statistik Kabupaten'], [372.89, 'Ogan Ilir']]);
        $this->text(240.0, 426.89, $d['ppk'], 'U');
        $this->text(240.0, 438.89, 'NIP. ' . $d['nipPpk']);

        // Kolom kanan (Yang Menerima).
        $this->dateLine(408.0, 336.89, $d['monthYear']);
        $this->text(408.0, 351.89, 'Yang Menerima');
        $this->text(408.0, 426.89, $d['petugas'], 'U', 566.0 - 408.0);
        $this->text(408.0, 438.89, 'NIP. -');
    }

    // ------------------------------------------------------------------------------------------------
    // HALAMAN 2 - PERINCIAN PERHITUNGAN BIAYA PERJALANAN DINAS
    // ------------------------------------------------------------------------------------------------

    private function page2(array $d): void
    {
        $this->centered('PERINCIAN PERHITUNGAN BIAYA PERJALANAN DINAS', self::PAGE_W / 2, 61.89, 'B');

        $this->text(70.0, 93.89, 'Lampiran Nomor Surat Tugas');
        $this->text(260.0, 93.89, ': ' . $d['nomorSurat']);
        $this->text(70.0, 111.89, 'Tanggal');
        $this->text(260.0, 111.89, ': ' . $d['tanggalSurat']);

        // Judul kolom tabel (rata tengah pada tiap kolom: 38-68, 68-370, 370-455, 455-555).
        foreach ([[53.0, 'No.', '(1)'], [219.0, 'Perincian Biaya', '(2)'], [412.5, 'Jumlah', '(3)'], [505.0, 'Keterangan', '(4)']] as [$cx, $title, $num]) {
            $this->centered($title, $cx, 155.89);
            $this->centered($num, $cx, 175.89);
        }

        // Baris rincian petugas. Tiap baris mengikuti baris sebelumnya bila teksnya lebih dari satu baris.
        $this->text(49.0, 187.89, '1');
        $labelX = 82.0;
        $colonX = 171.0;
        $valueX = 181.0;
        $valueW = 180.0;

        $this->text($labelX, 192.89, 'Nama yang bertugas');
        $this->text($colonX, 192.89, ':');
        $nama = $this->flow($valueX, 192.89, $d['petugas'], $valueW, 10.0, 3);

        $y = max(208.89, $nama['last'] + 16.0);
        $this->text($labelX, $y, 'Golongan');
        $this->text($colonX, $y, ':');
        $this->text($valueX, $y, '-');

        // Tempat Tugas: ukuran font tetap, teks panjang dibungkus ke baris berikutnya.
        $y = max(224.89, $y + 16.0);
        $this->text($labelX, $y, 'Tempat Tugas');
        $this->text($colonX, $y, ':');
        $place = $this->flow($valueX, $y, $d['destination'], $valueW, 10.0, 4);

        $y = max(250.89, $place['last'] + ($place['count'] > 1 ? 17.0 : 26.0));
        $this->text($labelX, $y, 'Lamanya Tugas');
        $this->text($colonX, $y, ':');
        $this->text($valueX, $y, $d['duration']);

        $this->text(82.0, 297.89, 'Total Pengeluaran Riil');
        $this->right($d['rupiah'], 448.9, 297.89);
        $this->centered('Jumlah', 219.0, 317.89);
        $this->right($d['rupiah'], 448.9, 317.89);
        $this->text(82.0, 337.89, 'Terbilang : ' . $d['terbilang']);

        // Blok pembayaran / penerimaan.
        $this->dateLine(330.0, 379.89, $d['monthYear']);
        $this->text(65.0, 403.89, 'Telah dibayar sejumlah');
        $this->text(65.0, 421.89, $d['rupiah']);
        $this->text(330.0, 403.89, 'Telah menerima jumlah uang sebesar');
        $this->text(330.0, 421.89, $d['rupiah']);
        $this->block(65.0, [[448.89, 'Lunas pada tanggal:'], [462.89, 'Bendahara Pengeluaran'], [476.89, 'Badan Pusat Statistik'], [490.89, 'Kabupaten Ogan Ilir']]);
        $this->text(390.0, 448.89, 'Yang Menerima');

        $this->text(65.0, 542.89, $d['bendahara'], 'U');
        $this->text(65.0, 554.89, 'NIP. ' . $d['nipBendahara']);
        $this->text(390.0, 542.89, $d['petugas'], 'U', 566.0 - 390.0);
        $this->text(390.0, 554.89, 'NIP. -');

        // Perhitungan SPD rampung.
        $this->centered('PERHITUNGAN SPD RAMPUNG', self::PAGE_W / 2, 579.89, 'B');
        $this->text(120.0, 602.89, 'Ditetapkan sejumlah');
        $this->text(410.0, 602.89, $d['rupiah']);
        $this->text(120.0, 618.89, 'Yang telah dibayar sejumlah');
        $this->text(410.0, 618.89, 'Rp 0');
        $this->text(120.0, 634.89, 'Sisa kurang/lebih');
        $this->text(410.0, 634.89, $d['rupiah']);

        // Blok PPK (kanan bawah), rata tengah.
        $cx = 429.0;
        $this->centered('Pejabat Pembuat Komitmen', $cx, 678.89);
        $this->centered('Badan Pusat Statistik', $cx, 691.89);
        $this->centered('Kabupaten Ogan Ilir', $cx, 704.89);
        $this->centered($d['ppk'], $cx, 761.89, '', 'U');
        $this->centered('NIP. ' . $d['nipPpk'], $cx, 773.89);
    }

    // ------------------------------------------------------------------------------------------------
    // HALAMAN 3 - DAFTAR PENGELUARAN RIIL
    // ------------------------------------------------------------------------------------------------

    private function page3(array $d): void
    {
        $this->centered('DAFTAR PENGELUARAN RIIL', self::PAGE_W / 2, 69.89, 'B');

        $this->text(70.0, 102.89, 'Yang bertanda tangan di bawah ini:');
        $this->text(90.0, 123.89, 'Nama');
        $this->text(180.0, 123.89, ':');
        $this->text(184.5, 123.89, $d['petugas']);
        $this->text(90.0, 140.89, 'NIP');
        $this->text(180.0, 140.89, ':');
        $this->text(184.5, 140.89, '-');
        $this->text(90.0, 157.89, 'Jabatan');
        $this->text(180.0, 157.89, ':');
        $this->flow(184.5, 157.89, $d['jabatan'], 555.0 - 184.5, 12.0, 2);

        $y = $this->flow(55.0, 181.89, 'Berdasarkan Surat Tugas Nomor: ' . $d['nomorSurat'] . ', Tanggal: ' . $d['tanggalSurat'] . ', dengan ini kami menyatakan dengan:', 466.0, 12.0, 99)['last'];
        $y = max($y + 22.0, 215.89);
        $this->flow(55.0, $y, '1. Biaya transport pegawai dan/atau biaya penginapan di bawah ini yang tidak dapat diperoleh bukti-bukti pengeluarannya, meliputi:', 466.0, 12.0, 99);

        // Judul kolom tabel (kolom: 40-65, 65-380, 380-455, 455-555).
        foreach ([[52.5, 'No.', '(1)'], [222.5, 'URAIAN', '(2)'], [417.5, 'Jumlah', '(3)'], [505.0, 'Keterangan', '(4)']] as [$cx, $title, $num]) {
            $this->centered($title, $cx, $title === 'Jumlah' ? 258.89 : 260.89);
            $this->centered($num, $cx, 290.89);
        }
        $this->centered('(Rp)', 417.5, 271.89);

        // Baris isi: uraian dibungkus pada lebar sel (maks. 4 baris sebelum font dikecilkan).
        $this->text(50.0, 320.89, '1');
        $this->flow(72.0, 320.89, $d['transport'], 285.0, 12.0, 4);
        $this->text(390.0, 329.39, $d['rupiah']);
        $this->text(72.0, 375.89, 'Jumlah');
        $this->text(390.0, 375.89, $d['rupiah']);
        $this->text(72.0, 395.89, 'Terbilang : ' . $d['terbilang']);

        // Butir 2 dan kalimat penutup.
        $this->flow(55.0, 412.89, '2. Jumlah uang tersebut pada angka 1 di atas benar-benar dikeluarkan untuk pelaksanaan perjalanan dinas dimaksud dan apabila di kemudian hari terdapat kelebihan atas pembayaran, kami bersedia untuk menyetorkan kelebihan tersebut ke Kas Negara.', 466.0, 12.0, 99);
        $this->text(55.0, 461.89, 'Demikian pernyataan ini kami buat dengan sebenarnya, untuk dipergunakan sebagaimana mestinya.');

        // Tanda tangan.
        $this->block(55.0, [[501.89, 'Mengetahui/Menyetujui:'], [515.89, 'Setuju dibayar:'], [529.89, 'Pejabat Pembuat Komitmen']]);
        $this->text(55.0, 581.89, $d['ppk'], 'U');
        $this->text(55.0, 593.89, 'NIP. ' . $d['nipPpk']);

        $this->dateLine(365.0, 501.89, $d['monthYear']);
        $this->text(365.0, 515.89, 'Pelaksana SPD');
        $this->text(365.0, 581.89, $d['petugas'], 'U', 566.0 - 365.0);
        $this->text(365.0, 593.89, 'NIP. -');
    }

    // ------------------------------------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------------------------------------

    private function page(int $templateNumber, callable $draw): void
    {
        $this->pdf->AddPage('P', [self::PAGE_W, self::PAGE_H]);
        $this->pdf->useTemplate($this->tpl[$templateNumber], 0, 0, self::PAGE_W, self::PAGE_H);
        $this->pdf->SetTextColor(0, 0, 0);
        $draw();
    }

    /** @param  array<int,array{0:float,1:string}>  $lines  pasangan [baseline, teks] pada X yang sama */
    private function block(float $x, array $lines): void
    {
        foreach ($lines as [$y, $t]) {
            $this->text($x, $y, $t);
        }
    }

    /** "Indralaya," lalu bulan pengajuan + tahun, dengan jarak seperti pada PDF acuan. */
    private function dateLine(float $x, float $baseline, string $monthYear): void
    {
        $this->text($x, $baseline, 'Indralaya,');
        if ($monthYear !== '') {
            $this->setFont();
            $this->text($x + $this->width('Indralaya,') + 13.5, $baseline, $monthYear);
        }
    }

    private function setFont(string $style = '', ?float $size = null): void
    {
        $this->pdf->SetFont(self::FONT, $style, $size ?? self::SIZE);
    }

    /** Lebar string pada font aktif. */
    private function width(string $text): float
    {
        return (float) $this->pdf->GetStringWidth($text);
    }

    /**
     * Satu baris teks. $x = sisi kiri, $baseline = garis dasar huruf.
     * Style 'U' = garis bawah sepanjang teks (digambar eksplisit, bukan mengandalkan style font).
     * $maxWidth (opsional) mengecilkan font hanya bila satu baris tidak muat.
     */
    private function text(float $x, float $baseline, string $text, string $style = '', ?float $maxWidth = null, ?float $size = null): void
    {
        $text = $this->clean($text);
        if ($text === '') {
            return;
        }
        $underline = str_contains($style, 'U');
        $fontStyle = str_replace('U', '', $style);
        $size = $size ?? self::SIZE;
        $this->setFont($fontStyle, $size);
        if ($maxWidth !== null) {
            while ($size > 6.0 && $this->width($text) > $maxWidth) {
                $size -= 0.25;
                $this->setFont($fontStyle, $size);
            }
        }
        $w = $this->width($text);
        $this->cell($x, $baseline, $w + 2.0, $text, $size, $fontStyle);
        if ($underline) {
            $this->underline($x, $baseline, $w);
        }
    }

    private function centered(string $text, float $centerX, float $baseline, string $style = '', string $extra = ''): void
    {
        $text = $this->clean($text);
        if ($text === '') {
            return;
        }
        $this->setFont($style);
        $w = $this->width($text);
        $this->text($centerX - $w / 2, $baseline, $text, $style . $extra);
    }

    /** Teks rata kanan: sisi kanan teks tepat di $rightX (agar nominal besar tidak menabrak garis kolom). */
    private function right(string $text, float $rightX, float $baseline): void
    {
        $text = $this->clean($text);
        $this->setFont();
        $this->text($rightX - $this->width($text), $baseline, $text);
    }

    private function underline(float $x, float $baseline, float $width): void
    {
        $y = $baseline + self::UNDERLINE_GAP + self::BASELINE_FIX;
        $this->pdf->SetLineWidth(self::UNDERLINE_WIDTH);
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->Line($x, $y, $x + $width, $y);
    }

    /**
     * Menulis teks bungkus-baris mulai ($x,$baseline) dengan jarak $pitch. Ukuran font TETAP 9 pt;
     * baru bila baris melebihi $maxLines, font dikecilkan (min. 6 pt) sampai muat.
     *
     * @return array{last:float,count:int} baseline baris terakhir dan jumlah baris
     */
    private function flow(float $x, float $baseline, string $text, float $maxWidth, float $pitch, int $maxLines): array
    {
        $size = self::SIZE;
        while (true) {
            $lines = $this->wrap($text, $maxWidth, $size);
            if (count($lines) <= $maxLines || $size <= 6.0) {
                break;
            }
            $size -= 0.25;
        }
        foreach ($lines as $i => $line) {
            $this->text($x, $baseline + $i * $pitch, $line, '', null, $size);
        }

        return ['last' => $baseline + (count($lines) - 1) * $pitch, 'count' => count($lines)];
    }

    /**
     * Pembungkus baris berdasarkan lebar nyata font (greedy per kata).
     *
     * @return array<int,string>
     */
    private function wrap(string $text, float $maxWidth, ?float $size = null): array
    {
        $this->setFont('', $size);
        $words = preg_split('/\s+/u', $this->clean($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if ($this->width($candidate) <= $maxWidth || $current === '') {
                $current = $candidate;
            } else {
                $lines[] = $current;
                $current = $word;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines ?: [''];
    }

    /**
     * Satu-satunya titik yang berinteraksi dengan penempatan vertikal TCPDF: Cell diletakkan di
     * (x, baseline - ascent) dengan tinggi = ukuran font dan padding 0.
     */
    private function cell(float $x, float $baseline, float $w, string $text, float $size, string $style): void
    {
        $ascent = (float) $this->pdf->getFontAscent(self::FONT, $style, $size);
        $this->pdf->SetXY($x, $baseline + self::BASELINE_FIX - $ascent);
        $this->pdf->Cell($w, $size, $text, 0, 0, 'L', false, '', 0, false, 'T', 'T');
    }

    private function clean(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
