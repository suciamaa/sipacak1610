<?php

namespace App\Services;

use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Renderer PDF LPD (Laporan Perjalanan Dinas Dalam Kota < 8 Jam) dengan FPDI + TCPDF.
 *
 * Cara kerja:
 *  - templates/lpd_template.pdf (2 halaman) hanya berisi bagian STATIS:
 *      hlm 1 = judul, label "Nama Pelaksana / Wilayah Tugas / Tanggal" + titik dua, dan header tabel (1)-(5);
 *      hlm 2 = bar "DOKUMENTASI PELAKSANAAN".
 *  - Semua yang dinamis ditimpa (overlay) berdasarkan koordinat: nilai identitas, isi baris tabel,
 *    garis tabel, blok tanda tangan, bingkai foto, foto, dan keterangan foto.
 *  - Template TIDAK fix: tabel hanya sepanjang jumlah baris yang ada. Tepat di bawah baris terakhir langsung
 *    diikuti blok "(KECAMATAN), (tanggal) / Yang Melaporkan / (Nama Petugas)".
 *
 * Koordinat: satuan point (pt), asal di kiri-atas halaman A4 (595.28 x 841.89), sumbu Y ke bawah.
 * X = sisi kiri teks, Y = BASELINE teks. Nilai diukur dari Template_LPD_Harian.pdf (revisi 25 Nov 2026). Font Helvetica 9 pt.
 * Sel Permasalahan & Pemecahan Masalah menyatu memanjang untuk seluruh baris tabel (seperti di template).
 */
class LpdPdfRenderer
{
    public const PAGE_W = 595.28;
    public const PAGE_H = 841.89;

    private const FONT = 'helvetica';
    private const SIZE = 9.0;

    /** Koreksi halus (pt) bila seluruh teks terlihat naik/turun. Positif = teks turun. */
    private const BASELINE_FIX = 0.0;

    // ---- Identitas (halaman laporan) ----
    private const ID_NAME_X = 126.25;
    private const ID_AREA_X = 124.23;
    private const ID_DATE_X = 124.85;
    private const ID_NAME_Y = 72.89;
    private const ID_AREA_Y = 85.48;
    private const ID_DATE_Y = 98.08;

    // ---- Tabel ----
    /** Batas kolom (x): No | Pukul | Uraian | Permasalahan | Pemecahan Masalah. */
    private const COLS = [36.0, 56.82, 128.05, 358.13, 450.15, 560.18];
    /** Sisi atas baris pertama = garis bawah baris indeks (1)-(5) pada template. */
    private const TABLE_TOP = 193.25;
    /** Batas bawah maksimum garis tabel / blok tanda tangan. */
    private const PAGE_BOTTOM = 805.89;
    private const LINE_W = 0.4;

    private const TEXT_PITCH = 10.0;       // jarak antarbaris teks
    private const ROW_PAD_TOP = 17.06;     // atas baris -> baseline baris teks pertama
    private const ROW_PAD_BOTTOM = 6.94;   // baseline baris teks terakhir -> bawah baris (tinggi baris = 14 + 10 x baris)
    private const NO_TIME_RISE = 1.6;      // nomor & jam sedikit lebih tinggi dari baris teks pertama
    private const ISHOMA_HEIGHT = 28.0;
    private const ISHOMA_NO_Y = 15.26;
    private const ISHOMA_TEXT_Y = 17.87;

    private const NO_X = 42.42;
    private const TIME_CENTER_X = 94.72;
    private const NARR_X = 133.25;
    private const NARR_W = 220.1;
    private const PROB_X = 363.33;
    private const PROB_W = 81.6;
    private const SOL_X = 455.35;
    private const SOL_W = 99.6;

    // ---- Tanda tangan (di bawah tabel) ----
    private const SIGN_CENTER_X = 474.3;
    private const SIGN_GAP = 30.66;        // bawah tabel -> baseline "KECAMATAN, tanggal"
    private const SIGN_LABEL_DY = 16.2;    // -> "Yang Melaporkan"
    private const SIGN_NAME_DY = 57.0;     // -> nama petugas
    private const SIGN_BLOCK_H = 91.0;     // tinggi total yang dipesan (gap + dy + ruang bawah)

    // ---- Dokumentasi ----
    private const PHOTO_COL_X = [42.73, 306.72];
    private const PHOTO_ROW_Y = [117.02, 321.54, 526.05];
    private const PHOTO_FRAME_W = 247.0;
    private const PHOTO_FRAME_H = 146.95;
    private const CAPTION_DY = 10.45;      // bawah bingkai -> baseline baris keterangan pertama
    private const PHOTO_FRAME_LINE = 0.45;
    private const PHOTO_PER_PAGE = 6;
    private const PHOTO_MAX_PX = 1800;     // foto lebih besar dikecilkan agar PDF tidak membengkak

    private const MONTHS = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    private Fpdi $pdf;

    /** @var array<int,mixed> */
    private array $tpl = [];

    /** Posisi Y (atas) tempat baris berikutnya diletakkan pada halaman laporan aktif. */
    private float $y = self::TABLE_TOP;

    /** Identitas halaman laporan yang sedang dikerjakan (diulang pada tiap halaman lanjutan). */
    private array $identity = [];

    /**
     * Sisa baris teks sel Permasalahan & Pemecahan Masalah (sel menyatu memanjang untuk seluruh baris tabel).
     *
     * @var array{prob:array<int,array{text:string,justify:bool}>,sol:array<int,array{text:string,justify:bool}>}
     */
    private array $merged = ['prob' => [], 'sol' => []];

    /**
     * @param  array<int,array{assignment:mixed,progress:mixed,lpdTimeline:array}>  $items
     * @return string biner PDF
     */
    public function render(array $items): string
    {
        if (! $items) {
            throw new \InvalidArgumentException('Tidak ada data LPD untuk dirender.');
        }

        $templatePath = base_path('templates/lpd_template.pdf');
        if (! is_file($templatePath)) {
            throw new \RuntimeException('Template PDF LPD tidak ditemukan: ' . $templatePath);
        }

        $pdf = new Fpdi('P', 'pt', [self::PAGE_W, self::PAGE_H], true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0, true);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->setCellPadding(0);
        $pdf->setCellMargins(0, 0, 0, 0);
        $pdf->SetCreator('SIPACAK');
        $pdf->SetTitle('Laporan Perjalanan Dinas Dalam Kota');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetDrawColor(0, 0, 0);
        $this->pdf = $pdf;

        $pageCount = $pdf->setSourceFile($templatePath);
        if ($pageCount < 2) {
            throw new \RuntimeException('Template LPD harus berisi 2 halaman (laporan dan dokumentasi).');
        }
        $this->tpl[1] = $pdf->importPage(1);
        $this->tpl[2] = $pdf->importPage(2);

        foreach ($items as $item) {
            $this->renderItem($item['assignment'], $item['progress'], $item['lpdTimeline'] ?? []);
        }

        return $pdf->Output('lpd.pdf', 'S');
    }

    // ------------------------------------------------------------------------------------------------
    // SATU LPD = halaman laporan (1..n) + halaman dokumentasi (1..m)
    // ------------------------------------------------------------------------------------------------

    private function renderItem($assignment, $progress, array $timeline): void
    {
        $district = mb_strtoupper(trim((string) ($assignment->kecamatan->nama_kecamatan ?? '')));
        $dateText = self::dateText($progress->tanggal);

        $this->identity = [
            'name' => trim((string) ($assignment->petugas->nama_lengkap ?? '')),
            'district' => $district,
            'date' => $dateText,
        ];

        $this->merged = ['prob' => [], 'sol' => []];
        $this->newReportPage();

        if ($timeline === []) {
            $this->placeEmptyRow();
        } else {
            $rows = [];
            $number = 0;
            foreach ($timeline as $entry) {
                $number++;
                $rows[] = $this->prepareRow($entry, $number);
            }
            $this->merged = [
                'prob' => $this->paragraphs($this->mergedText($timeline, 'problems'), self::PROB_W, false),
                'sol' => $this->paragraphs($this->mergedText($timeline, 'solution'), self::SOL_W, false),
            ];
            $last = count($rows) - 1;
            foreach ($rows as $index => $row) {
                $this->placeRow($row, $index === $last);
            }
            $this->fitMergedCells();
        }

        $this->closeTable();
        $this->signature();

        $this->renderDocumentation($progress, $dateText);
    }

    private function newReportPage(): void
    {
        $this->page(1);
        $this->text(self::ID_NAME_X, self::ID_NAME_Y, $this->identity['name'], '', 560.0 - self::ID_NAME_X);
        $this->text(self::ID_AREA_X, self::ID_AREA_Y, $this->identity['district'], '', 560.0 - self::ID_AREA_X);
        $this->text(self::ID_DATE_X, self::ID_DATE_Y, $this->identity['date']);
        $this->y = self::TABLE_TOP;
    }

    // ------------------------------------------------------------------------------------------------
    // TABEL
    // ------------------------------------------------------------------------------------------------

    /**
     * @return array{type:string,no:int,time:string,narr:array,lines:int}
     */
    private function prepareRow(array $entry, int $number): array
    {
        $type = ($entry['type'] ?? 'activity') === 'ishoma' ? 'ishoma' : 'activity';
        $row = [
            'type' => $type,
            'no' => $number,
            'time' => $this->clean((string) ($entry['time'] ?? '')),
            'narr' => [],
            'lines' => 1,
        ];

        if ($type === 'activity') {
            $row['narr'] = $this->paragraphs((string) ($entry['narrative'] ?? ''), self::NARR_W, true);
            // Tinggi baris hanya mengikuti panjang uraian; Permasalahan/Pemecahan Masalah berupa sel menyatu.
            $row['lines'] = max(1, count($row['narr']));
        }

        return $row;
    }

    private static function rowHeight(int $lines): float
    {
        return self::ROW_PAD_TOP + ($lines - 1) * self::TEXT_PITCH + self::ROW_PAD_BOTTOM;
    }

    private function placeRow(array $row, bool $isLast): void
    {
        if ($row['type'] === 'ishoma') {
            $need = self::ISHOMA_HEIGHT + ($isLast ? self::SIGN_BLOCK_H : 0.0);
            if ($this->y + $need > self::PAGE_BOTTOM && $this->y > self::TABLE_TOP) {
                $this->closeTable();
                $this->newReportPage();
            }
            $this->y += $this->drawRow($row, $this->y, 0, 1, true);

            return;
        }

        $capacity = self::PAGE_BOTTOM - self::TABLE_TOP;
        $height = self::rowHeight($row['lines']);

        if ($height > $capacity) {
            // Baris lebih tinggi dari satu halaman penuh: dipotong per baris teks.
            $this->placeOversizeRow($row, $isLast);

            return;
        }

        $need = $height + ($isLast ? self::SIGN_BLOCK_H : 0.0);
        if ($this->y + $need > self::PAGE_BOTTOM && $this->y > self::TABLE_TOP) {
            // Baris dipindah utuh ke halaman berikutnya (baris terakhir ikut membawa blok tanda tangan).
            $this->closeTable();
            $this->newReportPage();
        }

        $this->y += $this->drawRow($row, $this->y, 0, $row['lines'], true);
    }

    private function placeOversizeRow(array $row, bool $isLast): void
    {
        $from = 0;
        $total = $row['lines'];
        $first = true;

        while ($from < $total) {
            $available = self::PAGE_BOTTOM - $this->y;
            $fit = (int) floor(($available - self::ROW_PAD_TOP - self::ROW_PAD_BOTTOM) / self::TEXT_PITCH) + 1;

            if ($fit < 1) {
                $this->closeTable();
                $this->newReportPage();
                continue;
            }

            $to = min($total, $from + $fit);
            $this->y += $this->drawRow($row, $this->y, $from, $to, $first);
            $first = false;
            $from = $to;

            if ($from < $total) {
                $this->closeTable();
                $this->newReportPage();
            }
        }
    }

    private function placeEmptyRow(): void
    {
        $top = $this->y;
        $height = self::ISHOMA_HEIGHT;
        $this->hline($top + $height);
        $this->centered('Belum terdapat detail progress untuk tanggal ini.', (self::COLS[0] + self::COLS[5]) / 2, $top + self::ISHOMA_TEXT_Y);
        $this->y += $height;
    }

    /**
     * Menggambar satu baris (atau potongan baris) lalu mengembalikan tingginya.
     * Garis bawah baris digambar di sini; garis tegak digambar saat tabel halaman itu ditutup.
     */
    private function drawRow(array $row, float $top, int $from, int $to, bool $first): float
    {
        if ($row['type'] === 'ishoma') {
            $bottom = $top + self::ISHOMA_HEIGHT;
            $this->hline($bottom);
            $this->text(self::NO_X, $top + self::ISHOMA_NO_Y, $row['no'] . '.');
            $this->centered($row['time'], self::TIME_CENTER_X, $top + self::ISHOMA_TEXT_Y);
            $this->centered('ISHOMA', (self::COLS[2] + self::COLS[3]) / 2, $top + self::ISHOMA_TEXT_Y);

            return self::ISHOMA_HEIGHT;
        }

        $count = max(1, $to - $from);
        $height = self::rowHeight($count);
        $this->hline($top + $height);

        if ($first) {
            $noY = $top + self::ROW_PAD_TOP - self::NO_TIME_RISE;
            $this->text(self::NO_X, $noY, $row['no'] . '.');
            $this->centered($row['time'], self::TIME_CENTER_X, $noY);
        }

        for ($i = $from; $i < $to; $i++) {
            if (! isset($row['narr'][$i])) {
                break;
            }
            $baseline = $top + self::ROW_PAD_TOP + ($i - $from) * self::TEXT_PITCH;
            $this->textLine(self::NARR_X, $baseline, $row['narr'][$i]['text'], self::NARR_W, $row['narr'][$i]['justify']);
        }

        return $height;
    }

    /** Garis tegak antar-kolom dari bawah header sampai bawah baris terakhir pada halaman aktif. */
    private function closeTable(): void
    {
        if ($this->y <= self::TABLE_TOP) {
            return;
        }
        $this->pdf->SetLineWidth(self::LINE_W);
        $this->pdf->SetDrawColor(0, 0, 0);
        foreach (self::COLS as $x) {
            $this->pdf->Line($x, self::TABLE_TOP, $x, $this->y);
        }
        $this->pdf->Line(self::COLS[0], $this->y, self::COLS[5], $this->y);
        $this->drawMergedCells();
    }

    /** Gabungan teks permasalahan/pemecahan dari seluruh baris kegiatan (tanpa "-" dan duplikat). */
    private function mergedText(array $timeline, string $key): string
    {
        $parts = [];
        foreach ($timeline as $entry) {
            if (($entry['type'] ?? 'activity') === 'ishoma') {
                continue;
            }
            foreach (preg_split('/\R/u', (string) ($entry[$key] ?? '')) ?: [] as $part) {
                $part = trim($part);
                if ($part !== '' && $part !== '-' && ! in_array($part, $parts, true)) {
                    $parts[] = $part;
                }
            }
        }

        return implode("\n", $parts);
    }

    /** Jumlah baris teks yang muat pada sel menyatu bila tabel halaman aktif berakhir di $this->y. */
    private function mergedCapacity(): int
    {
        return max(0, (int) floor(($this->y - self::TABLE_TOP - self::ROW_PAD_TOP - self::ROW_PAD_BOTTOM) / self::TEXT_PITCH) + 1);
    }

    /**
     * Sel Permasalahan & Pemecahan Masalah menyatu dari baris pertama sampai baris terakhir tabel.
     * Bila teksnya lebih panjang dari tabel, tabel dipanjangkan (atau dilanjutkan ke halaman berikutnya).
     */
    private function fitMergedCells(): void
    {
        while (true) {
            $need = max(count($this->merged['prob']), count($this->merged['sol']));
            if ($need === 0 || $need <= $this->mergedCapacity()) {
                return;
            }

            $wanted = self::TABLE_TOP + self::rowHeight($need);
            if ($wanted <= self::PAGE_BOTTOM - self::SIGN_BLOCK_H) {
                $this->y = max($this->y, $wanted);

                return;
            }

            $this->y = max($this->y, self::PAGE_BOTTOM);
            $this->closeTable();
            $this->newReportPage();
            $this->y = self::TABLE_TOP;
        }
    }

    private function drawMergedCells(): void
    {
        $capacity = $this->mergedCapacity();
        foreach (['prob' => [self::PROB_X, self::PROB_W], 'sol' => [self::SOL_X, self::SOL_W]] as $key => [$x, $w]) {
            $lines = array_splice($this->merged[$key], 0, $capacity);
            foreach ($lines as $i => $line) {
                $this->textLine($x, self::TABLE_TOP + self::ROW_PAD_TOP + $i * self::TEXT_PITCH, $line['text'], $w, false);
            }
        }
    }

    private function hline(float $y): void
    {
        $this->pdf->SetLineWidth(self::LINE_W);
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->Line(self::COLS[0], $y, self::COLS[3], $y);
    }

    // ------------------------------------------------------------------------------------------------
    // TANDA TANGAN - langsung di bawah tabel
    // ------------------------------------------------------------------------------------------------

    private function signature(): void
    {
        if ($this->y + self::SIGN_BLOCK_H > self::PAGE_BOTTOM) {
            // Cadangan (jarang): tidak muat di halaman terakhir tabel -> halaman polos hanya untuk tanda tangan.
            $this->pdf->AddPage('P', [self::PAGE_W, self::PAGE_H]);
            $this->pdf->SetTextColor(0, 0, 0);
            $this->y = 60.0 - self::SIGN_GAP;
        }

        $base = $this->y + self::SIGN_GAP;
        $center = self::SIGN_CENTER_X;
        $this->centered($this->identity['district'] . ', ' . $this->identity['date'], $center, $base);
        $this->centered('Yang Melaporkan', $center, $base + self::SIGN_LABEL_DY);
        $this->centered($this->identity['name'], $center, $base + self::SIGN_NAME_DY);
    }

    // ------------------------------------------------------------------------------------------------
    // DOKUMENTASI
    // ------------------------------------------------------------------------------------------------

    private function renderDocumentation($progress, string $dateText): void
    {
        $photos = [];
        foreach ($progress->details as $detail) {
            foreach ($detail->dokumentasi as $photo) {
                $photos[] = [
                    'path' => storage_path('app/private/' . $photo->file_path),
                    'caption' => self::photoCaption($detail),
                ];
            }
        }

        if ($photos === []) {
            $this->page(2);
            $this->centered('Belum terdapat foto dokumentasi pada progress tanggal ini.', self::PAGE_W / 2, 110.0);

            return;
        }

        $number = 0;
        foreach (array_chunk($photos, self::PHOTO_PER_PAGE) as $pagePhotos) {
            $this->page(2);
            foreach ($pagePhotos as $slot => $photo) {
                $number++;
                $x = self::PHOTO_COL_X[$slot % 2];
                $y = self::PHOTO_ROW_Y[intdiv($slot, 2)];
                $this->photoSlot($x, $y, $photo['path']);

                $captionY = $y + self::PHOTO_FRAME_H + self::CAPTION_DY;
                $this->text($x + 0.3, $captionY, $number . '.', 'B', null, 9.6);
                $this->text($x + 12.3, $captionY, $photo['caption'], '', self::PHOTO_FRAME_W - 13.0);
                $this->text($x + 12.3, $captionY + 12.02, '(' . $dateText . ')');
            }
        }
    }

    private function photoSlot(float $x, float $y, string $path): void
    {
        $this->pdf->SetLineWidth(self::PHOTO_FRAME_LINE);
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->Rect($x, $y, self::PHOTO_FRAME_W, self::PHOTO_FRAME_H, 'D');

        $photo = $this->loadPhoto($path);
        if ($photo === null) {
            $this->centered('Foto dokumentasi tidak ditemukan', $x + self::PHOTO_FRAME_W / 2, $y + self::PHOTO_FRAME_H / 2 + 3.0);

            return;
        }

        // Foto dimuat utuh (tidak terpotong) di dalam bingkai, inset 1 pt, dan dipusatkan.
        $boxW = self::PHOTO_FRAME_W - 2.0;
        $boxH = self::PHOTO_FRAME_H - 2.0;
        $scale = min($boxW / $photo['w'], $boxH / $photo['h']);
        $w = $photo['w'] * $scale;
        $h = $photo['h'] * $scale;
        $ix = $x + (self::PHOTO_FRAME_W - $w) / 2;
        $iy = $y + (self::PHOTO_FRAME_H - $h) / 2;

        try {
            $this->pdf->Image('@' . $photo['data'], $ix, $iy, $w, $h, '', '', '', false, 300, '', false, false, 0, false, false, false);
        } catch (\Throwable $e) {
            report($e);
            $this->centered('Foto dokumentasi tidak dapat dimuat', $x + self::PHOTO_FRAME_W / 2, $y + self::PHOTO_FRAME_H / 2 + 3.0);
        }
    }

    /**
     * Membaca foto, memutar sesuai EXIF, dan mengecilkan bila terlalu besar.
     *
     * @return array{data:string,w:int,h:int}|null
     */
    private function loadPhoto(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }
        $info = @getimagesizefromstring($raw);
        if (! $info || $info[0] < 1 || $info[1] < 1) {
            return null;
        }

        $w = (int) $info[0];
        $h = (int) $info[1];

        if ($info[2] === IMAGETYPE_JPEG && function_exists('imagecreatefromstring')) {
            $orientation = 1;
            if (function_exists('exif_read_data')) {
                $stream = fopen('php://temp', 'r+');
                if ($stream !== false) {
                    fwrite($stream, $raw);
                    rewind($stream);
                    $exif = @exif_read_data($stream);
                    fclose($stream);
                    $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
                }
            }

            $angles = [3 => 180, 6 => -90, 8 => 90];
            $needsRotate = isset($angles[$orientation]);
            $needsShrink = max($w, $h) > self::PHOTO_MAX_PX;

            if (($needsRotate || $needsShrink) && function_exists('imagejpeg')) {
                $image = @imagecreatefromstring($raw);
                if ($image !== false) {
                    if ($needsRotate && function_exists('imagerotate')) {
                        $rotated = @imagerotate($image, $angles[$orientation], 0);
                        if ($rotated !== false) {
                            imagedestroy($image);
                            $image = $rotated;
                        }
                    }

                    $nw = imagesx($image);
                    $nh = imagesy($image);
                    if (max($nw, $nh) > self::PHOTO_MAX_PX) {
                        $ratio = self::PHOTO_MAX_PX / max($nw, $nh);
                        $tw = max(1, (int) round($nw * $ratio));
                        $th = max(1, (int) round($nh * $ratio));
                        $resized = imagecreatetruecolor($tw, $th);
                        if ($resized !== false) {
                            imagecopyresampled($resized, $image, 0, 0, 0, 0, $tw, $th, $nw, $nh);
                            imagedestroy($image);
                            $image = $resized;
                            $nw = $tw;
                            $nh = $th;
                        }
                    }

                    ob_start();
                    imagejpeg($image, null, 88);
                    $encoded = (string) ob_get_clean();
                    imagedestroy($image);

                    if ($encoded !== '') {
                        return ['data' => $encoded, 'w' => $nw, 'h' => $nh];
                    }
                }
            }
        }

        return ['data' => $raw, 'w' => $w, 'h' => $h];
    }

    private static function photoCaption($detail): string
    {
        $sls = mb_strtoupper(trim((string) ($detail->masterSls?->nmsls ?? '')));
        if ($sls === '') {
            $sls = mb_strtoupper(trim((string) $detail->sls));
            if (str_contains($sls, ' / ')) {
                $sls = trim(explode(' / ', $sls, 2)[0]);
            }
        }
        if (($detail->jenis_data_wilayah ?? 'sls') === 'blok_sensus') {
            $sls = 'Blok Sensus ' . mb_strtoupper(trim((string) ($detail->blok_sensus ?: $detail->sls)));
        }

        return trim($sls . ' - ' . mb_strtoupper((string) $detail->desa), ' -');
    }

    // ------------------------------------------------------------------------------------------------
    // Helper teks (pola sama dengan KwitansiPdfRenderer)
    // ------------------------------------------------------------------------------------------------

    private function page(int $templateNumber): void
    {
        $this->pdf->AddPage('P', [self::PAGE_W, self::PAGE_H]);
        $this->pdf->useTemplate($this->tpl[$templateNumber], 0, 0, self::PAGE_W, self::PAGE_H);
        $this->pdf->SetTextColor(0, 0, 0);
    }

    private static function dateText($date): string
    {
        $carbon = $date instanceof \DateTimeInterface ? $date : new \DateTimeImmutable((string) $date);

        return $carbon->format('d') . ' ' . (self::MONTHS[(int) $carbon->format('n')] ?? '') . ' ' . $carbon->format('Y');
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
     * $maxWidth (opsional) mengecilkan font hanya bila satu baris tidak muat.
     */
    private function text(float $x, float $baseline, string $text, string $style = '', ?float $maxWidth = null, ?float $size = null): void
    {
        $text = $this->clean($text);
        if ($text === '') {
            return;
        }
        $size = $size ?? self::SIZE;
        $this->setFont($style, $size);
        if ($maxWidth !== null) {
            while ($size > 6.0 && $this->width($text) > $maxWidth) {
                $size -= 0.25;
                $this->setFont($style, $size);
            }
        }
        $this->cell($x, $baseline, $this->width($text) + 2.0, $text, $size, $style, 'L');
    }

    private function centered(string $text, float $centerX, float $baseline, string $style = ''): void
    {
        $text = $this->clean($text);
        if ($text === '') {
            return;
        }
        $this->setFont($style);
        $this->text($centerX - $this->width($text) / 2, $baseline, $text, $style);
    }

    /**
     * Satu baris dalam kolom tabel. Bila $justify, teks direntangkan rata kiri-kanan selebar $width
     * (baris terakhir paragraf tidak direntangkan).
     */
    private function textLine(float $x, float $baseline, string $text, float $width, bool $justify): void
    {
        $text = $this->clean($text);
        if ($text === '') {
            return;
        }
        $this->setFont();
        if ($justify && str_contains($text, ' ')) {
            $this->cell($x, $baseline, $width, $text, self::SIZE, '', 'J');

            return;
        }
        $this->cell($x, $baseline, $this->width($text) + 2.0, $text, self::SIZE, '', 'L');
    }

    /**
     * Memecah teks menjadi baris-baris untuk kolom selebar $width. Setiap paragraf (dipisah baris baru)
     * dibungkus sendiri; baris terakhir tiap paragraf tidak ber-justify.
     *
     * @return array<int,array{text:string,justify:bool}>
     */
    private function paragraphs(string $text, float $width, bool $justify): array
    {
        $text = $this->normalize($text);
        $result = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $paragraph) {
            $paragraph = trim((string) preg_replace('/\s+/u', ' ', $paragraph));
            if ($paragraph === '') {
                continue;
            }
            $lines = $this->wrap($paragraph, $width);
            foreach ($lines as $i => $line) {
                $result[] = ['text' => $line, 'justify' => $justify && $i < count($lines) - 1];
            }
        }

        return $result;
    }

    /**
     * Pembungkus baris berdasarkan lebar nyata font (greedy per kata). Kata yang lebih panjang dari
     * kolom dipotong per karakter agar tidak keluar dari garis tabel.
     *
     * @return array<int,string>
     */
    private function wrap(string $text, float $maxWidth): array
    {
        $this->setFont();
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            while ($this->width($word) > $maxWidth) {
                if ($current !== '') {
                    $lines[] = $current;
                    $current = '';
                }
                $cut = '';
                $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $used = 0;
                foreach ($chars as $char) {
                    if ($cut !== '' && $this->width($cut . $char) > $maxWidth) {
                        break;
                    }
                    $cut .= $char;
                    $used++;
                }
                $lines[] = $cut;
                $word = implode('', array_slice($chars, $used));
                if ($word === '') {
                    continue 2;
                }
            }

            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if ($current === '' || $this->width($candidate) <= $maxWidth) {
                $current = $candidate;
            } else {
                $lines[] = $current;
                $current = $word;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    /**
     * Satu-satunya titik yang berinteraksi dengan penempatan vertikal TCPDF: Cell diletakkan di
     * (x, baseline - ascent) dengan tinggi = ukuran font dan padding 0.
     */
    private function cell(float $x, float $baseline, float $w, string $text, float $size, string $style, string $align): void
    {
        $ascent = (float) $this->pdf->getFontAscent(self::FONT, $style, $size);
        $this->pdf->SetXY($x, $baseline + self::BASELINE_FIX - $ascent);
        $this->pdf->Cell($w, $size, $text, 0, 0, $align, false, '', 0, false, 'T', 'T');
    }

    /** Menyeragamkan tanda baca "pintar" agar tampil benar pada font inti Helvetica (Latin-1). */
    private function normalize(string $text): string
    {
        return strtr($text, [
            "\u{2018}" => "'", "\u{2019}" => "'", "\u{201C}" => '"', "\u{201D}" => '"',
            "\u{2013}" => '-', "\u{2014}" => '-', "\u{2026}" => '...', "\u{00A0}" => ' ',
        ]);
    }

    private function clean(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $this->normalize($text)));
    }
}
