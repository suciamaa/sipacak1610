<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/../legacy_reference/includes/kwitansi_wilayah.php';
if (!function_exists('date_long')) { function date_long(?string $date): string { if(!$date)return '-'; $m=[1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; $t=strtotime($date); return date('j',$t).' '.$m[(int)date('n',$t)].' '.date('Y',$t); } }
if (!function_exists('progress_meta_decode')) { function progress_meta_decode($raw): array { return ['structured'=>false,'legacy_text'=>trim((string)$raw),'aktivitas'=>[]]; } }
function lpd_join_natural(array $items): string {
    $items = array_values(array_filter(array_map(static fn($v) => trim((string)$v), $items), static fn($v) => $v !== ''));
    $count = count($items);
    if ($count === 0) return '';
    if ($count === 1) return $items[0];
    if ($count === 2) return $items[0] . ' dan ' . $items[1];
    $last = array_pop($items);
    return implode(', ', $items) . ', dan ' . $last;
}

function lpd_activity_meta(array $r): array {
    $meta = progress_meta_decode($r['permasalahan'] ?? '');
    if (!($meta['structured'] ?? false)) {
        return [
            'kepala_desa' => false,
            'lurah' => false,
            'ketua_sls' => false,
            'penelusuran_wilayah' => false,
            'konfirmasi_ulang_data' => false,
            'pendataan_lengkap' => false,
        ];
    }
    return $meta['aktivitas'];
}

function lpd_problem_text(array $r): string {
    $meta = progress_meta_decode($r['permasalahan'] ?? '');
    if (!($meta['structured'] ?? false)) {
        return trim((string)($meta['legacy_text'] ?? ''));
    }

    $p = $meta['permasalahan'] ?? [];
    $items = [];
    if (!empty($p['kendala_aplikasi_fasih'])) $items[] = 'kendala aplikasi FASIH';
    if (!empty($p['responden_sulit_ditemui'])) $items[] = 'responden sulit ditemui';
    if (!empty($p['perbedaan_data'])) $items[] = 'perbedaan data';
    if (trim((string)($p['kendala_lainnya'] ?? '')) !== '') {
        $items[] = trim((string)$p['kendala_lainnya']);
    }

    if (!$items) return '';
    return 'Permasalahan yang dihadapi yaitu ' . lpd_join_natural($items) . '.';
}

function lpd_location_text(array $r): string {
    $desa = trim((string)($r['nama_desa'] ?? ''));
    $sls = trim((string)($r['nama_sls'] ?? ''));
    $subSls = trim((string)($r['nama_sub_sls'] ?? ''));

    // Tentukan jenis wilayah dari master resmi berdasarkan kecamatan + nama wilayah.
    // Jika nama tidak dikenal, pertahankan label lama agar satu data yang tidak cocok
    // tidak menggagalkan pembuatan LPD.
    $kecamatan = trim((string)($r['nama_kecamatan'] ?? ''));
    $candidate = preg_replace('/^(?:desa|kelurahan)\s+/iu', '', $desa);
    $mapped = kw_region_label($kecamatan, $desa);
    $jenis = $mapped !== null ? strtok($mapped, ' ') : 'Desa';
    $namaUntukDokumen = $mapped !== null ? $candidate : $desa;
    $location = $jenis . ' ' . $namaUntukDokumen . ", SLS {$sls}";
    if ($subSls !== '') {
        $location .= ", sub SLS {$subSls}";
    }
    return $location;
}

function lpd_activity_intro(array $r): string {
    $text = trim((string)($r['uraian_kegiatan'] ?? ''));
    return $text !== '' ? $text : 'Pelaksanaan kegiatan lapangan pada wilayah yang ditugaskan.';
}

function lpd_assignment_value(array $r, string $key): int {
    $value = $r[$key] ?? null;
    if ($value === null || trim((string)$value) === '') return 0;
    return (int)$value;
}

function lpd_uraian_hasil(array $r, bool $melanjutkan = false): string {
    $text = trim((string)($r['output_tujuan'] ?? ''));
    return $text !== '' ? $text : trim((string)($r['uraian_kegiatan'] ?? ''));
}

function lpd_uraian_full(array $r): string {
    $text = trim((string)($r['uraian_kegiatan'] ?? ''));
    $output = trim((string)($r['output_tujuan'] ?? ''));
    return trim($text . ($output !== '' && $output !== $text ? ' Output: ' . $output : ''));
}

/*
 * The supplied LPD template is rendered once as PNG backgrounds. The PNGs
 * are generated from the exact uploaded PDF template, so the visual layout,
 * wording, borders and spacing remain the same. PHP only overlays database
 * values and photos on the blank areas/cells.
 */
function pdf_escape_lpd(string $s): string {
    $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);
    if ($converted !== false) {
        $s = $converted;
    }
    return str_replace(
        ['\\', '(', ')', "\r", "\n"],
        ['\\\\', '\\(', '\\)', '', ' '],
        $s
    );
}

function pdf_text_lpd(
    string $text,
    float $x,
    float $y,
    float $size = 8,
    string $font = 'F1',
    array $rgb = [0, 0, 0]
): string {
    [$r, $g, $b] = $rgb;
    return sprintf(
        "%.3f %.3f %.3f rg\nBT /%s %.2f Tf %.2f %.2f Td (%s) Tj ET\n",
        $r, $g, $b, $font, $size, $x, $y, pdf_escape_lpd($text)
    );
}

function pdf_rect_lpd(
    float $x,
    float $y,
    float $w,
    float $h,
    bool $fill = false,
    array $rgb = [1, 1, 1],
    float $lineWidth = 0.5
): string {
    [$r, $g, $b] = $rgb;
    return sprintf(
        "%.3f %.3f %.3f rg\n0 0 0 RG\n%.2f w\n%.2f %.2f %.2f %.2f re\n%s\n",
        $r,
        $g,
        $b,
        $lineWidth,
        $x,
        $y,
        $w,
        $h,
        $fill ? 'B' : 'S'
    );
}

function lpd_strlen(string $text): int {
    return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
}

function pdf_fill_rect_lpd(float $x, float $y, float $w, float $h, array $rgb = [1, 1, 1]): string {
    [$r, $g, $b] = $rgb;
    return sprintf(
        "%.3f %.3f %.3f rg\n%.2f %.2f %.2f %.2f re f\n",
        $r, $g, $b, $x, $y, $w, $h
    );
}

function pdf_char_width_lpd(string $char): float {
    /* Approximate Helvetica character widths in 1/1000 em. */
    static $w = [
        ' '=>278,'!'=>278,'"'=>355,'#'=>556,'$'=>556,'%'=>889,'&'=>667,"'"=>191,
        '('=>333,')'=>333,'*'=>389,'+'=>584,','=>278,'-'=>333,'.'=>278,'/'=>278,
        ':'=>278,';'=>278,'<'=>584,'='=>584,'>'=>584,'?'=>556,'@'=>1015,
        'A'=>667,'B'=>667,'C'=>722,'D'=>722,'E'=>667,'F'=>611,'G'=>778,'H'=>722,
        'I'=>278,'J'=>500,'K'=>667,'L'=>556,'M'=>833,'N'=>722,'O'=>778,'P'=>667,
        'Q'=>778,'R'=>722,'S'=>667,'T'=>611,'U'=>722,'V'=>667,'W'=>944,'X'=>667,
        'Y'=>667,'Z'=>611,
        'a'=>556,'b'=>556,'c'=>500,'d'=>556,'e'=>556,'f'=>278,'g'=>556,'h'=>556,
        'i'=>222,'j'=>222,'k'=>500,'l'=>222,'m'=>833,'n'=>556,'o'=>556,'p'=>556,
        'q'=>556,'r'=>333,'s'=>500,'t'=>278,'u'=>556,'v'=>500,'w'=>722,'x'=>500,
        'y'=>500,'z'=>500,
        '0'=>556,'1'=>556,'2'=>556,'3'=>556,'4'=>556,'5'=>556,'6'=>556,'7'=>556,
        '8'=>556,'9'=>556
    ];
    return ($w[$char] ?? 556) / 1000;
}

function pdf_text_width_lpd(string $text, float $size): float {
    $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
    if ($converted !== false) {
        $text = $converted;
    }
    $width = 0.0;
    $len = strlen($text);
    for ($i = 0; $i < $len; $i++) {
        $width += pdf_char_width_lpd($text[$i]) * $size;
    }
    return $width;
}

function pdf_wrap_lpd(string $text, float $maxWidth, float $size = 10): array {
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    if ($text === '') {
        return [];
    }

    $words = preg_split('/\s+/u', $text) ?: [];
    $lines = [];
    $line = '';

    foreach ($words as $word) {
        $test = $line === '' ? $word : $line . ' ' . $word;

        if ($line !== '' && pdf_text_width_lpd($test, $size) > $maxWidth) {
            $lines[] = $line;
            $line = '';
        }

        /* A long SLS/name/URL must never escape the cell. Break it at the
         * character level when even a single word is wider than the cell. */
        if ($line === '' && pdf_text_width_lpd($word, $size) > $maxWidth) {
            $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($chars as $char) {
                $candidate = $line . $char;
                if ($line !== '' && pdf_text_width_lpd($candidate, $size) > $maxWidth) {
                    $lines[] = $line;
                    $line = $char;
                } else {
                    $line = $candidate;
                }
            }
            continue;
        }

        $line = $line === '' ? $word : $line . ' ' . $word;
    }

    if ($line !== '') {
        $lines[] = $line;
    }

    return $lines;
}

function pdf_center_lpd(
    string $text,
    float $cx,
    float $y,
    float $size = 8,
    string $font = 'F1',
    array $rgb = [0, 0, 0]
): string {
    $w = pdf_text_width_lpd($text, $size);
    return pdf_text_lpd($text, $cx - ($w / 2), $y, $size, $font, $rgb);
}

/* PNG decoder used for the template background and uploaded PNG photos. */
function png_rgb_data(string $path): ?array {
    $bin = @file_get_contents($path);
    if ($bin === false || substr($bin, 0, 8) !== "\x89PNG\r\n\x1a\n") {
        return null;
    }

    $pos = 8;
    $w = $h = $ct = $bd = null;
    $idat = '';
    $palette = '';

    while ($pos + 8 <= strlen($bin)) {
        $lenData = substr($bin, $pos, 4);
        if (strlen($lenData) !== 4) {
            break;
        }

        $len = unpack('N', $lenData)[1];
        $type = substr($bin, $pos + 4, 4);
        $chunk = substr($bin, $pos + 8, $len);
        $pos += 12 + $len;

        if ($type === 'IHDR') {
            $u = unpack('Nwidth/Nheight/Cbit/Ccolor/Ccomp/Cfilter/Cinterlace', $chunk);
            $w = $u['width'];
            $h = $u['height'];
            $bd = $u['bit'];
            $ct = $u['color'];
        } elseif ($type === 'PLTE') {
            $palette = $chunk;
        } elseif ($type === 'IDAT') {
            $idat .= $chunk;
        } elseif ($type === 'IEND') {
            break;
        }
    }

    if (!$w || !$h || $bd !== 8 || !in_array($ct, [0, 2, 3, 4, 6], true)) {
        return null;
    }

    if ($ct === 3 && $palette === '') {
        return null;
    }

    $raw = @gzuncompress($idat);
    if ($raw === false) {
        return null;
    }

    $channels = [0 => 1, 2 => 3, 3 => 1, 4 => 2, 6 => 4][$ct];
    $bpp = $channels;
    $stride = $w * $channels;
    $out = '';
    $prev = array_fill(0, $stride, 0);
    $off = 0;

    for ($y = 0; $y < $h; $y++) {
        if ($off >= strlen($raw)) {
            return null;
        }

        $filter = ord($raw[$off++]);
        $cur = [];

        for ($x = 0; $x < $stride; $x++) {
            if ($off >= strlen($raw)) {
                return null;
            }
            $cur[$x] = ord($raw[$off++]);
        }

        for ($x = 0; $x < $stride; $x++) {
            $a = $x >= $bpp ? $cur[$x - $bpp] : 0;
            $b = $prev[$x] ?? 0;
            $c = $x >= $bpp ? ($prev[$x - $bpp] ?? 0) : 0;

            if ($filter === 1) {
                $cur[$x] = ($cur[$x] + $a) & 255;
            } elseif ($filter === 2) {
                $cur[$x] = ($cur[$x] + $b) & 255;
            } elseif ($filter === 3) {
                $cur[$x] = ($cur[$x] + intdiv($a + $b, 2)) & 255;
            } elseif ($filter === 4) {
                $p = $a + $b - $c;
                $pa = abs($p - $a);
                $pb = abs($p - $b);
                $pc = abs($p - $c);
                $pr = $pa <= $pb && $pa <= $pc ? $a : ($pb <= $pc ? $b : $c);
                $cur[$x] = ($cur[$x] + $pr) & 255;
            }
        }

        for ($x = 0; $x < $w; $x++) {
            $i = $x * $channels;

            if ($ct === 0) {
                $v = $cur[$i];
                $out .= chr($v) . chr($v) . chr($v);
            } elseif ($ct === 2) {
                $out .= chr($cur[$i]) . chr($cur[$i + 1]) . chr($cur[$i + 2]);
            } elseif ($ct === 3) {
                $pi = $cur[$i] * 3;
                $out .= chr(ord($palette[$pi] ?? "\0"))
                    . chr(ord($palette[$pi + 1] ?? "\0"))
                    . chr(ord($palette[$pi + 2] ?? "\0"));
            } elseif ($ct === 4) {
                $v = $cur[$i];
                $out .= chr($v) . chr($v) . chr($v);
            } else {
                $out .= chr($cur[$i]) . chr($cur[$i + 1]) . chr($cur[$i + 2]);
            }
        }

        $prev = $cur;
    }

    return [$w, $h, $out];
}

function pdf_image_object(array &$objects, string $path): ?array {
    $info = @getimagesize($path);
    if (!$info) {
        return null;
    }

    $type = $info[2];

    if ($type === IMAGETYPE_JPEG) {
        $data = @file_get_contents($path);
        if ($data === false) {
            return null;
        }

        $obj = '<< /Type /XObject /Subtype /Image /Width ' . $info[0]
            . ' /Height ' . $info[1]
            . ' /ColorSpace /DeviceRGB /BitsPerComponent 8'
            . ' /Filter /DCTDecode /Length ' . strlen($data)
            . " >>\nstream\n" . $data . "\nendstream";

        $objects[] = $obj;
        return [count($objects), $info[0], $info[1]];
    }

    if ($type === IMAGETYPE_PNG) {
        $decoded = png_rgb_data($path);
        if (!$decoded) {
            return null;
        }

        [$w, $h, $rgb] = $decoded;
        $row = '';

        for ($y = 0; $y < $h; $y++) {
            $row .= chr(0) . substr($rgb, $y * $w * 3, $w * 3);
        }

        $compressed = gzcompress($row, 6);

        $obj = '<< /Type /XObject /Subtype /Image /Width ' . $w
            . ' /Height ' . $h
            . ' /ColorSpace /DeviceRGB /BitsPerComponent 8'
            . ' /Filter /FlateDecode'
            . ' /DecodeParms << /Predictor 15 /Colors 3 /BitsPerComponent 8 /Columns ' . $w . ' >>'
            . ' /Length ' . strlen($compressed)
            . " >>\nstream\n" . $compressed . "\nendstream";

        $objects[] = $obj;
        return [count($objects), $w, $h];
    }

    return null;
}

function pdf_add_image(
    string &$content,
    string $resourceName,
    int $objectId,
    float $x,
    float $y,
    float $w,
    float $h
): void {
    $content .= "q\n";
    $content .= sprintf("%.3f 0 0 %.3f %.3f %.3f cm\n", $w, $h, $x, $y);
    $content .= '/' . $resourceName . " Do\nQ\n";
}

function pdf_add_wrapped_cell(
    string &$content,
    string $text,
    float $x,
    float $bottom,
    float $width,
    float $height,
    float $startSize = 9.0,
    float $minSize = 9.0,
    string $font = 'F1',
    bool $verticalCenter = false,
    bool $justify = false
): void {
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    if ($text === '') {
        return;
    }

    /*
     * LPD reference layout: generated dynamic text uses 9 pt.
     * The font size is never reduced to force a row onto a page.  Instead,
     * the row itself grows with the wrapped content and pagination moves a
     * complete row to the next page when there is not enough room.
     */
    $size = 9.0;
    $lineHeight = 10.0;
    /* Keep a visible, even inset so text never touches the table rules. */
    $innerX = $x + 5.0;
    $innerY = $bottom + 5.0;
    $innerW = max(9.5, $width - 10.0);
    $innerH = max(9.5, $height - 10.0);
    $lines = pdf_wrap_lpd($text, $innerW, $size);

    if (!$lines) {
        return;
    }

    if ($verticalCenter) {
        $blockHeight = max(0.0, (count($lines) - 1) * $lineHeight);
        $y = $bottom + (($height + $blockHeight) / 2.0) + 1.0;
    } else {
        /* 5 pt top/bottom padding; keep the final line off the lower border. */
        $y = $bottom + $height - 14.0;
    }

    /* The row height is calculated from the same wrapping function above,
     * so no clipping mask is needed. For the activity column, justify each
     * non-final line to the cell edges like Word's Justify alignment. The
     * final line remains naturally left-aligned to avoid stretched spacing. */
    $lastLineIndex = count($lines) - 1;
    foreach ($lines as $lineIndex => $line) {
        if ($justify && $lineIndex < $lastLineIndex) {
            $spaceCount = substr_count($line, ' ');
            $textWidth = pdf_text_width_lpd($line, 9.0);
            $wordSpacing = $spaceCount > 0 ? max(0.0, ($innerW - $textWidth) / $spaceCount) : 0.0;
            $content .= sprintf(
                "BT /%s %.2f Tf %.3f Tw %.2f %.2f Td (%s) Tj ET\n0 Tw\n",
                $font,
                9.0,
                $wordSpacing,
                $innerX,
                $y,
                pdf_escape_lpd($line)
            );
        } else {
            $content .= pdf_text_lpd($line, $innerX, $y, 9.0, $font);
        }
        $y -= $lineHeight;
    }
}

function lpd_time_value(array $r): string {
    $start = trim((string)($r['jam_mulai'] ?? ''));
    $end = trim((string)($r['jam_selesai'] ?? ''));

    if ($start !== '') {
        $start = substr($start, 0, 5);
    }
    if ($end !== '') {
        $end = substr($end, 0, 5);
    }

    if ($start === '' && $end === '') {
        return '';
    }

    return $start . ' - ' . $end;
}

function lpd_minutes(string $time): ?int {
    $time = trim($time);
    if (!preg_match('/^(\d{1,2}):(\d{2})$/', $time, $m)) {
        return null;
    }
    $hour = (int)$m[1];
    $minute = (int)$m[2];
    if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
        return null;
    }
    return ($hour * 60) + $minute;
}

function lpd_hhmm(int $minutes): string {
    $minutes = max(0, min(1439, $minutes));
    return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
}

/**
 * Expand one stored progress row only for LPD rendering.
 * Database values are never changed. 12:00-13:00 is treated as Ishoma.
 */
/**
 * Build one Ishoma row for LPD rendering only.
 * Database values are never changed.
 */
function lpd_make_ishoma_row(array $source, int $startMin, int $endMin, int $groupId = 0): array {
    $ishoma = $source;
    $ishoma['_lpd_type'] = 'ishoma';
    $ishoma['_lpd_group_id'] = $groupId;
    $ishoma['_lpd_time'] = lpd_hhmm($startMin) . ' - ' . lpd_hhmm($endMin);
    $ishoma['_lpd_uraian'] = 'ISHOMA';
    $ishoma['permasalahan'] = '';
    $ishoma['_lpd_problem'] = '';
    $ishoma['pemecahan_masalah'] = '';
    return $ishoma;
}

/**
 * Expand one stored progress row only for LPD rendering.
 *
 * Default Ishoma periods:
 *   12:00-13:00
 *   18:00-19:00
 *
 * The stored/database times are never changed. The activity is only split
 * for PDF rendering so that no printed activity duration includes Ishoma.
 */
function lpd_expand_row(array $r): array {
    $groupId = (int)($r['id'] ?? 0);
    $r['_lpd_group_id'] = $groupId;

    $start = substr(trim((string)($r['jam_mulai'] ?? '')), 0, 5);
    $end = substr(trim((string)($r['jam_selesai'] ?? '')), 0, 5);
    $startMin = lpd_minutes($start);
    $endMin = lpd_minutes($end);

    $r['_lpd_problem'] = lpd_problem_text($r);
    $r['_lpd_full_uraian'] = lpd_uraian_full($r);

    if ($startMin === null || $endMin === null || $endMin <= $startMin) {
        $r['_lpd_type'] = 'activity';
        $r['_lpd_time'] = lpd_time_value($r);
        $r['_lpd_uraian'] = $r['_lpd_full_uraian'];
        return [$r];
    }

    $ishomaPeriods = [
        [12 * 60, 13 * 60],
        [18 * 60, 19 * 60],
    ];

    foreach ($ishomaPeriods as [$ishomaStart, $ishomaEnd]) {
        /* Starts exactly at the beginning of Ishoma: the occupied Ishoma
         * period is represented by the automatic Ishoma row, followed only
         * by the post-Ishoma activity when one exists. */
        if ($startMin === $ishomaStart && $endMin > $ishomaStart) {
            $result = [lpd_make_ishoma_row($r, $ishomaStart, $ishomaEnd, $groupId)];
            if ($endMin > $ishomaEnd) {
                $post = $r;
                $post['_lpd_type'] = 'activity';
                $post['_lpd_time'] = lpd_hhmm($ishomaEnd) . ' - ' . lpd_hhmm($endMin);
                $post['_lpd_uraian'] = lpd_uraian_hasil($r, true);
                $post['_lpd_problem'] = '';
                $post['permasalahan'] = '';
                $post['pemecahan_masalah'] = '';
                $result[] = $post;
            }
            return $result;
        }

        /* Activity crosses the beginning of Ishoma. */
        if ($startMin < $ishomaStart && $endMin > $ishomaStart) {
            $result = [];

            $pre = $r;
            $pre['_lpd_type'] = 'activity';
            $pre['_lpd_time'] = lpd_hhmm($startMin) . ' - ' . lpd_hhmm($ishomaStart);
            $pre['_lpd_uraian'] = lpd_activity_intro($r);
            $result[] = $pre;

            $result[] = lpd_make_ishoma_row($r, $ishomaStart, $ishomaEnd, $groupId);

            /* No zero-length activity after Ishoma when the source ends at
             * the Ishoma boundary (e.g. 08:00-13:00). */
            if ($endMin > $ishomaEnd) {
                $post = $r;
                $post['_lpd_type'] = 'activity';
                $post['_lpd_time'] = lpd_hhmm($ishomaEnd) . ' - ' . lpd_hhmm($endMin);
                $post['_lpd_uraian'] = lpd_uraian_hasil($r, true);
                $post['_lpd_problem'] = '';
                $post['permasalahan'] = '';
                $post['pemecahan_masalah'] = '';
                $result[] = $post;
            }
            return $result;
        }
    }

    $r['_lpd_type'] = 'activity';
    $r['_lpd_time'] = lpd_time_value($r);
    $r['_lpd_uraian'] = $r['_lpd_full_uraian'];
    return [$r];
}

/**
 * Expand all stored rows for PDF rendering only.
 *
 * Besides splitting activities that cross an Ishoma period, insert a default
 * Ishoma row when two consecutive activities leave the complete default
 * Ishoma period as a gap (for example 08:00-12:00 and 13:00-15:00).
 * Database rows are never changed.
 */
function lpd_expand_rows(array $rows): array {
    $expanded = [];
    $ishomaPeriods = [
        [12 * 60, 13 * 60],
        [18 * 60, 19 * 60],
    ];

    /* Work in chronological order for timeline generation only. */
    usort($rows, static function (array $a, array $b): int {
        $aStart = lpd_minutes(substr(trim((string)($a['jam_mulai'] ?? '')), 0, 5));
        $bStart = lpd_minutes(substr(trim((string)($b['jam_mulai'] ?? '')), 0, 5));
        if ($aStart === null && $bStart === null) return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
        if ($aStart === null) return 1;
        if ($bStart === null) return -1;
        if ($aStart !== $bStart) return $aStart <=> $bStart;
        return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
    });

    $previousEnd = null;
    $previousSource = null;
    $insertedIshoma = [];

    foreach ($rows as $row) {
        $start = lpd_minutes(substr(trim((string)($row['jam_mulai'] ?? '')), 0, 5));
        $end = lpd_minutes(substr(trim((string)($row['jam_selesai'] ?? '')), 0, 5));

        if ($previousEnd !== null && $start !== null && $previousSource !== null && $start > $previousEnd) {
            foreach ($ishomaPeriods as [$ishomaStart, $ishomaEnd]) {
                if ($previousEnd <= $ishomaStart && $start >= $ishomaEnd) {
                    $key = $ishomaStart . '-' . $ishomaEnd;
                    if (!isset($insertedIshoma[$key])) {
                        $expanded[] = lpd_make_ishoma_row(
                            $previousSource,
                            $ishomaStart,
                            $ishomaEnd,
                            (int)($previousSource['id'] ?? 0)
                        );
                        $insertedIshoma[$key] = true;
                    }
                }
            }
        }

        foreach (lpd_expand_row($row) as $entry) {
            $expanded[] = $entry;
            if (($entry['_lpd_type'] ?? '') === 'ishoma') {
                $key = lpd_minutes(substr((string)($entry['_lpd_time'] ?? ''), 0, 5));
                $endPart = lpd_minutes(substr((string)($entry['_lpd_time'] ?? ''), 8, 5));
                if ($key !== null && $endPart !== null) {
                    $insertedIshoma[$key . '-' . $endPart] = true;
                }
            }
        }

        if ($end !== null) {
            $previousEnd = $end;
            $previousSource = $row;
        }
    }

    /* If the final activity ends exactly at the start of an Ishoma period,
     * keep that default break visible even when there is no later activity.
     * This makes Ishoma part of the rendered timeline rather than something
     * that appears only when a later row happens to exist. */
    if ($previousEnd !== null && $previousSource !== null) {
        foreach ($ishomaPeriods as [$ishomaStart, $ishomaEnd]) {
            if ($previousEnd === $ishomaStart) {
                $key = $ishomaStart . '-' . $ishomaEnd;
                if (!isset($insertedIshoma[$key])) {
                    $expanded[] = lpd_make_ishoma_row(
                        $previousSource,
                        $ishomaStart,
                        $ishomaEnd,
                        (int)($previousSource['id'] ?? 0)
                    );
                    $insertedIshoma[$key] = true;
                }
                break;
            }
        }
    }

    usort($expanded, static function (array $a, array $b): int {
        $aTime = trim((string)($a['_lpd_time'] ?? ''));
        $bTime = trim((string)($b['_lpd_time'] ?? ''));
        $aStart = lpd_minutes(substr($aTime, 0, 5));
        $bStart = lpd_minutes(substr($bTime, 0, 5));
        if ($aStart === null && $bStart === null) return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
        if ($aStart === null) return 1;
        if ($bStart === null) return -1;
        if ($aStart !== $bStart) return $aStart <=> $bStart;
        $aType = (($a['_lpd_type'] ?? '') === 'ishoma') ? 0 : 1;
        $bType = (($b['_lpd_type'] ?? '') === 'ishoma') ? 0 : 1;
        if ($aType !== $bType) return $aType <=> $bType;
        return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
    });

    return $expanded;
}

function lpd_filename(string $date): string {
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $d = new DateTimeImmutable($date);
    return 'LPD_' . $d->format('d') . '_' . $months[(int)$d->format('n')] . '_' . $d->format('Y') . '.pdf';
}


function lpd_get_rows(PDO $pdo, int $pid, int $kid, string $date): array {
    $q = $pdo->prepare('SELECT dp.*, dp.id_detail AS id, dp.desa AS nama_desa, dp.sls AS nama_sls, dp.sub_sls AS nama_sub_sls, dp.kecamatan AS nama_kecamatan, p.nama_petugas, ph.tanggal FROM detail_progress dp JOIN progress_harian ph ON ph.id_progress=dp.id_progress JOIN petugas p ON p.id_petugas=ph.id_petugas WHERE ph.id_petugas=? AND ph.id_kegiatan=? AND ph.tanggal=? ORDER BY dp.jam_mulai, dp.id_detail');
    $q->execute([$pid, $kid, $date]);
    return $q->fetchAll() ?: [];
}


function lpd_get_docs(PDO $pdo, int $pid, int $kid, string $date): array {
    $q = $pdo->prepare('SELECT d.*, d.path_file AS file_path, dp.desa AS nama_desa, dp.sls AS nama_sls FROM dokumentasi d JOIN detail_progress dp ON dp.id_detail=d.id_detail JOIN progress_harian ph ON ph.id_progress=dp.id_progress WHERE ph.id_petugas=? AND ph.id_kegiatan=? AND ph.tanggal=? ORDER BY dp.id_detail, d.id_dokumentasi');
    $q->execute([$pid, $kid, $date]);
    return $q->fetchAll() ?: [];
}

function lpd_required_cell_height(string $text, float $width, float $size = 9.0, float $lineHeight = 10.0): float {
    $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    if ($text === '') return 0.0;
    $lines = pdf_wrap_lpd($text, max(9.5, $width - 10.0), $size);
    if (!$lines) return 0.0;

    /* The same 9 pt font and line height are used for measurement and
     * rendering. Extra vertical breathing room prevents the final line from
     * touching the border. */
    /* Comfortable but compact padding above and below each cell's text. */
    return (count($lines) * $lineHeight) + 14.0;
}

function lpd_required_row_height(array $r): float {
    if (($r['_lpd_type'] ?? '') === 'ishoma') return 28.0;

    $activity = (string)($r['_lpd_uraian'] ?? '');
    $problem = (string)($r['_lpd_problem'] ?? ($r['permasalahan'] ?? ''));
    $solution = (string)($r['pemecahan_masalah'] ?? '');

    /* All generated text columns use 9 pt and compact line spacing. */
    $activityH = lpd_required_cell_height($activity, 230.0, 9.0, 10.0);
    $problemH = lpd_required_cell_height($problem, 92.0, 9.0, 10.0);
    $solutionH = lpd_required_cell_height($solution, 110.0, 9.0, 10.0);

    return max(28.0, $activityH, $problemH, $solutionH);
}

function lpd_paginate_rows(array $rows, float $availableHeight): array {
    if (!$rows) return [[]];

    /*
     * Pack rows by their measured content height. There is intentionally no
     * fixed row-count rule and no "one progress row = one page" rule.
     * Ishoma rows participate in the same height calculation as normal rows.
     */
    $pages = [];
    $current = [];
    $currentHeight = 0.0;

    foreach ($rows as $row) {
        $rowHeight = lpd_required_row_height($row);

        if ($current && ($currentHeight + $rowHeight > $availableHeight)) {
            $pages[] = $current;
            $current = [];
            $currentHeight = 0.0;
        }

        /* Keep an individual row intact. The row-height calculation is based
         * on the same wrapping metrics used by the renderer. */
        $current[] = $row;
        $currentHeight += $rowHeight;
    }

    if ($current) $pages[] = $current;
    return $pages ?: [[]];
}

function lpd_build_pdf(PDO $pdo, array $petugas, int $kid, string $date): string {
    $W = 595.28;
    $H = 841.89;

    $templatePage1 = dirname(__DIR__) . '/templates/LPD Harian - page 1.png';
    $templatePage2 = dirname(__DIR__) . '/templates/LPD Harian - page 2.png';

    if (!is_file($templatePage1) || !is_file($templatePage2)) {
        throw new RuntimeException('Template LPD belum lengkap.');
    }

    $objects = [];
    $addObj = static function (string $body) use (&$objects): int {
        $objects[] = $body;
        return count($objects);
    };

    $fontRegular = $addObj('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');
    $fontBold = $addObj('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>');
    $pagesObj = $addObj('');
    $pageRefs = [];

    $bg1 = pdf_image_object($objects, $templatePage1);
    $bg2 = pdf_image_object($objects, $templatePage2);

    if (!$bg1 || !$bg2) {
        throw new RuntimeException('Template LPD tidak dapat dibaca.');
    }

    [$bg1Obj, $bg1W, $bg1H] = $bg1;
    [$bg2Obj, $bg2W, $bg2H] = $bg2;

    $rows = lpd_get_rows($pdo, (int)$petugas['id'], $kid, $date);
    $docs = lpd_get_docs($pdo, (int)$petugas['id'], $kid, $date);

    /*
     * The latest uploaded LPD template keeps the original header/footer and
     * introduces a dedicated short Ishoma row.  We keep that template as the
     * visual base, then redraw only the dynamic body grid so the number of
     * rows can safely change when Ishoma is inserted.
     */
    /* Exact table coordinates measured from LPD_template(1).pdf. */
    $x0 = 36.0;
    $x1 = 60.0;
    $x2 = 128.0;
    $x3 = 358.0;
    $x4 = 450.0;
    $x5 = 560.0;

    /* Body begins immediately below the (1)-(5) header row and ends at
     * the bottom of the blank table area in the supplied template. */
    $bodyTop = 647.5;
    /* Give the dynamic body enough vertical room for the 9 pt
     * activity text.  The signature remains below this area. */
    /* Earlier activity pages may use the lower page area. The final activity
     * page reserves a fixed footer zone so the signature always stays at the
     * bottom of the page, regardless of how much whitespace remains. */
    $bodyBottom = 40.0;
    $lastPageBodyBottom = 145.0;
    /* Rows are paginated by their actual wrapped content, not by a fixed row count. */
    $availableBodyHeight = $bodyTop - $bodyBottom;
    $lastPageAvailableBodyHeight = $bodyTop - $lastPageBodyBottom;

    $expandedRows = lpd_expand_rows($rows);
    $rowPages = lpd_paginate_rows($expandedRows, $availableBodyHeight);

    /* If the final packed page would collide with the signature-safe area,
     * move complete rows from its beginning to a new final page. Rows are
     * never split. */
    if (count($rowPages) > 0) {
        $lastIndex = count($rowPages) - 1;
        $lastHeight = 0.0;
        foreach ($rowPages[$lastIndex] as $r) {
            $lastHeight += lpd_required_row_height($r);
        }
        while (count($rowPages[$lastIndex]) > 1 && $lastHeight > $lastPageAvailableBodyHeight) {
            /* Move the last row forward so chronological order is preserved. */
            $moved = array_pop($rowPages[$lastIndex]);
            $movedHeight = lpd_required_row_height($moved);
            $lastHeight -= $movedHeight;
            $rowPages[] = [$moved];
            $lastIndex = count($rowPages) - 1;
            $lastHeight = $movedHeight;
        }
    }

    foreach ($rowPages as $pageIndex => $pageRows) {
        $content = '';

        /* Template background. */

        pdf_add_image($content, 'Bg1', $bg1Obj, 0, 0, $W, $H);

        /*
         * Rebuild the table header only. Move it below the date field and use
         * the exact same X coordinates as the activity rows so all vertical
         * borders align. Keep the original template and footer/signature.
         */
        $headerBottom = $bodyTop;
        $headerMid = 671.5;
        $headerTop = 736.0;
        $content .= pdf_fill_rect_lpd($x0 - 0.8, $headerBottom - 0.8, ($x5 - $x0) + 1.6, ($headerTop - $headerBottom) + 1.6, [1, 1, 1]);
        $content .= pdf_rect_lpd($x0, $headerBottom, $x5 - $x0, $headerTop - $headerBottom, false, [0, 0, 0], 0.5);
        foreach ([$x1, $x2, $x3, $x4] as $headerX) {
            $content .= sprintf("0 0 0 RG\n0.5 w\n%.3f %.3f m %.3f %.3f l S\n", $headerX, $headerBottom, $headerX, $headerTop);
        }
        $content .= sprintf("0 0 0 RG\n0.5 w\n%.3f %.3f m %.3f %.3f l S\n", $x0, $headerMid, $x5, $headerMid);
        $content .= pdf_center_lpd('No', ($x0 + $x1) / 2, 700.0, 9.0, 'F2');
        $content .= pdf_center_lpd('Pukul', ($x1 + $x2) / 2, 707.0, 9.0, 'F2');
        $content .= pdf_center_lpd('(WIB)', ($x1 + $x2) / 2, 694.0, 9.0, 'F2');
        $content .= pdf_center_lpd('Uraian Laporan Kegiatan', ($x2 + $x3) / 2, 700.0, 9.0, 'F2');
        $content .= pdf_center_lpd('Permasalahan', ($x3 + $x4) / 2, 700.0, 9.0, 'F2');
        $content .= pdf_center_lpd('Pemecahan Masalah', ($x4 + $x5) / 2, 700.0, 9.0, 'F2');
        foreach ([[$x0,$x1,'(1)'],[$x1,$x2,'(2)'],[$x2,$x3,'(3)'],[$x3,$x4,'(4)'],[$x4,$x5,'(5)']] as $headerCol) {
            $content .= pdf_center_lpd($headerCol[2], ($headerCol[0] + $headerCol[1]) / 2, 654.0, 9.0, 'F1');
        }

        /*
         * Fill only the blank value areas beside the template labels.
         * The original template labels, borders and headings are untouched.
         */
        /* Values are written into the blank fields of the supplied template. */
        // Values sit on the same baselines as the blank fields in the supplied template.
        $content .= pdf_text_lpd((string)$petugas['nama_petugas'], 125.33, 769.00, 9.0);
        $content .= pdf_text_lpd((string)$petugas['nama_kecamatan'], 125.33, 756.30, 9.0);
        $content .= pdf_text_lpd(date_long($date), 125.33, 743.65, 9.0);

        /*
         * Footer/signature is shown only once: on the LAST activity page,
         * immediately before the documentation page(s).  Earlier activity
         * pages still have the template footer area cleared so the template's
         * duplicate signature text does not appear there.
         */
        $content .= pdf_fill_rect_lpd(390, 45, 170, 175, [1, 1, 1]);
        /* The supplied template has a leftover standalone '2026' at the lower-left footer.
         * It is not part of the generated LPD and must be cleared. */
        $content .= pdf_fill_rect_lpd(28, 150, 90, 45, [1, 1, 1]);


        /*
         * Clear the template's fixed body rows/labels. Header and footer are
         * left intact. Then redraw the same table boundaries dynamically.
         */
        $pageBodyBottom = ($pageIndex === (count($rowPages) - 1)) ? $lastPageBodyBottom : $bodyBottom;
        $content .= pdf_fill_rect_lpd($x0 - 0.8, $pageBodyBottom - 0.8, ($x5 - $x0) + 1.6, ($bodyTop - $pageBodyBottom) + 1.6, [1, 1, 1]);

        /* Row heights are content-driven.  Do not proportionally shrink
         * them: the activity column must remain a true 9 pt text block and
         * the cell must grow with the wrapped paragraph. */
        $slotHeights = array_map('lpd_required_row_height', $pageRows);

        /* With no actual entries, do not draw a body table at all. */
        $renderedBodyBottom = $bodyTop;
        foreach ($slotHeights as $height) {
            $renderedBodyBottom -= $height;
        }
        $renderedBodyBottom = max($pageBodyBottom, $renderedBodyBottom);

        /* Dynamic table outer/vertical borders stop exactly at the last row. */
        if ($pageRows) {
            $content .= pdf_rect_lpd(
                $x0,
                $renderedBodyBottom,
                $x5 - $x0,
                $bodyTop - $renderedBodyBottom,
                false,
                [0, 0, 0],
                0.5
            );
            foreach ([$x1, $x2, $x3, $x4] as $vx) {
                $content .= sprintf(
                    "0 0 0 RG\n0.5 w\n%.3f %.3f m %.3f %.3f l S\n",
                    $vx,
                    $renderedBodyBottom,
                    $vx,
                    $bodyTop
                );
            }
        }

        $cursorTop = $bodyTop;
        foreach ($slotHeights as $slotIndex => $slotHeight) {
            $slotBottom = $cursorTop - $slotHeight;
            $content .= sprintf("0 0 0 RG\n0.5 w\n%.3f %.3f m %.3f %.3f l S\n", $x0, $slotBottom, $x5, $slotBottom);

            if (isset($pageRows[$slotIndex])) {
                $r = $pageRows[$slotIndex];
                /* Every rendered timeline row, including the automatic ISHOMA row,
                 * receives a sequential number in actual PDF display order. */
                $number = array_sum(array_map('count', array_slice($rowPages, 0, $pageIndex))) + $slotIndex + 1;
                $type = (string)($r['_lpd_type'] ?? 'activity');
                $content .= pdf_text_lpd((string)$number . '.', $x0 + 7.0, $cursorTop - 14.5, 9.0);

                if ($type === 'ishoma') {
                    /* Ishoma remains a normal chronological row and is never used as a page-break trigger. */
                    $ishomaY = $slotBottom + ($slotHeight / 2.0) - 3.0;
                    $content .= pdf_center_lpd((string)($r['_lpd_time'] ?? ''), ($x1 + $x2) / 2, $ishomaY, 9.0);
                    $content .= pdf_center_lpd('ISHOMA', ($x2 + $x3) / 2, $ishomaY, 9.0);
                    $content .= pdf_center_lpd('-', ($x3 + $x4) / 2, $ishomaY, 9.0);
                    $content .= pdf_center_lpd('-', ($x4 + $x5) / 2, $ishomaY, 9.0);
                } else {
                    $time = (string)($r['_lpd_time'] ?? lpd_time_value($r));
                    if ($time !== '') {
                        $content .= pdf_center_lpd($time, ($x1 + $x2) / 2, $cursorTop - 14.5, 9.0);
                    }

                    /* Activity remains LEFT aligned and TOP aligned. */
                    pdf_add_wrapped_cell(
                        $content,
                        (string)($r['_lpd_uraian'] ?? lpd_uraian($r)),
                        $x2,
                        $slotBottom + 2,
                        ($x3 - $x2),
                        $slotHeight - 4,
                        9.0,
                        9.0,
                        'F1',
                        false,
                        true
                    );

                    pdf_add_wrapped_cell(
                        $content,
                        (string)($r['_lpd_problem'] ?? ($r['permasalahan'] ?? '')),
                        $x3,
                        $slotBottom + 2,
                        ($x4 - $x3),
                        $slotHeight - 4,
                        9.0,
                        9.0
                    );

                    pdf_add_wrapped_cell(
                        $content,
                        (string)($r['pemecahan_masalah'] ?? ''),
                        $x4,
                        $slotBottom + 2,
                        ($x5 - $x4),
                        $slotHeight - 4,
                        9.0,
                        9.0
                    );
                }
            }

            $cursorTop = $slotBottom;
        }

        /* Draw the signature AFTER the table and row contents. Previously it was
         * drawn before the body white-out rectangle, which could cover the date
         * and title and leave only the officer name visible. */
        if ($pageIndex === (count($rowPages) - 1)) {
            $footerColor = [0, 0, 0];
            $footerCenterX = 474;
            $footerDate = trim((string)$petugas['nama_kecamatan']) . ', ' . date_long($date);
            /* Anchor the signature block to the bottom of the page, not to the
             * table's final row. The final page reserves space up to y=145 so
             * the table cannot collide with this fixed footer block. */
            $footerDateY = 108.0;
            $content .= pdf_center_lpd($footerDate, $footerCenterX, $footerDateY, 9.0, 'F1', $footerColor);
            $content .= pdf_center_lpd('Yang Melaporkan', $footerCenterX, $footerDateY - 16.0, 9.0, 'F1', $footerColor);
            $content .= pdf_center_lpd((string)$petugas['nama_petugas'], $footerCenterX, 51.0, 9.0, 'F1', $footerColor);
        }

        $contentObj = $addObj(
            '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream"
        );

        $pageObj = $addObj(
            '<< /Type /Page /Parent ' . $pagesObj . ' 0 R'
            . ' /MediaBox [0 0 ' . $W . ' ' . $H . ']'
            . ' /Resources << /Font << /F1 ' . $fontRegular . ' 0 R /F2 ' . $fontBold . ' 0 R >>'
            . ' /XObject << /Bg1 ' . $bg1Obj . ' 0 R >> >>'
            . ' /Contents ' . $contentObj . ' 0 R >>'
        );

        $pageRefs[] = $pageObj;
    }

    /*
     * Documentation is always tied to the selected date. Photos are placed
     * on the template's second page, directly below the fixed
     * "DOKUMENTASI PELAKSANAAN" heading.
     *
     * The layout is intentionally 2 columns x 3 rows so the page can show
     * six photos cleanly. If there are more than six photos, another
     * documentation page is created using the same template.
     */
    $docPages = array_chunk($docs, 6);
    if (!$docPages) {
        $docPages = [[]];
    }

    foreach ($docPages as $docRows) {
        $content = '';
        pdf_add_image($content, 'Bg2', $bg2Obj, 0, 0, $W, $H);

        /* Six balanced photo slots below the documentation heading. */
        $positions = [
            [43, 532, 245, 145],
            [307, 532, 245, 145],
            [43, 326, 245, 145],
            [307, 326, 245, 145],
            [43, 120, 245, 145],
            [307, 120, 245, 145],
        ];

        $imageResources = ['/Bg2 ' . $bg2Obj . ' 0 R'];
        $photoIndex = 0;

        foreach ($docRows as $doc) {
            if (!isset($positions[$photoIndex])) {
                break;
            }

            /* file_path is stored relative to the application root. */
            $relativePath = ltrim(str_replace('\\', '/', (string)$doc['file_path']), '/');
            $path = dirname(__DIR__) . '/' . $relativePath;
            if (!is_file($path)) {
                continue;
            }

            $img = pdf_image_object($objects, $path);
            if (!$img) {
                continue;
            }

            [$imgObj, $iw, $ih] = $img;
            [$px, $py, $pw, $ph] = $positions[$photoIndex];

            /* Thin frame around each photo, matching the clean template. */
            $content .= pdf_rect_lpd($px - 1, $py - 1, $pw + 2, $ph + 2, false, [0.75, 0.75, 0.75], 0.45);

            $scale = min($pw / $iw, $ph / $ih);
            $dw = $iw * $scale;
            $dh = $ih * $scale;
            $dx = $px + (($pw - $dw) / 2);
            $dy = $py + (($ph - $dh) / 2);

            $resourceName = 'Im' . $imgObj;
            pdf_add_image($content, $resourceName, $imgObj, $dx, $dy, $dw, $dh);
            $imageResources[] = '/' . $resourceName . ' ' . $imgObj . ' 0 R';

            /* Short caption: sequence + SLS/Desa + date, without touching the photo. */
            $sls = trim((string)($doc['nama_sls'] ?? ''));
            $desa = trim((string)($doc['nama_desa'] ?? ''));
            $caption = trim(($sls !== '' ? $sls : 'Dokumentasi') . ($desa !== '' ? ' - ' . $desa : ''));
            $captionDate = date_long($date);
            $captionLines = pdf_wrap_lpd($caption, $pw - 12, 9.0);
            /* Keep the complete caption; wrap it instead of truncating with an ellipsis. */

            $textY = $py - 12;
            $content .= pdf_text_lpd(($photoIndex + 1) . '.', $px, $textY, 9.5, 'F2');
            $textX = $px + 12;
            foreach ($captionLines as $line) {
                $content .= pdf_text_lpd($line, $textX, $textY, 9.0);
                $textY -= 12;
            }
            $content .= pdf_text_lpd('(' . $captionDate . ')', $textX, $textY, 9.0);

            $photoIndex++;
        }

        $contentObj = $addObj(
            '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream"
        );

        $resources = '<< /Font << /F1 ' . $fontRegular . ' 0 R /F2 ' . $fontBold . ' 0 R >>'
            . ' /XObject << ' . implode(' ', $imageResources) . ' >> >>';

        $pageObj = $addObj(
            '<< /Type /Page /Parent ' . $pagesObj . ' 0 R'
            . ' /MediaBox [0 0 ' . $W . ' ' . $H . ']'
            . ' /Resources ' . $resources
            . ' /Contents ' . $contentObj . ' 0 R >>'
        );

        $pageRefs[] = $pageObj;
    }

    $kids = implode(' ', array_map(
        static fn(int $r): string => $r . ' 0 R',
        $pageRefs
    ));

    $objects[$pagesObj - 1] =
        '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($pageRefs) . ' >>';

    $catalog = $addObj('<< /Type /Catalog /Pages ' . $pagesObj . ' 0 R >>');

    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [0];

    foreach ($objects as $i => $body) {
        $offsets[$i + 1] = strlen($pdf);
        $pdf .= ($i + 1) . " 0 obj\n" . $body . "\nendobj\n";
    }

    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";

    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }

    $pdf .= "trailer\n<< /Size " . (count($objects) + 1)
        . " /Root " . $catalog . " 0 R >>\n"
        . "startxref\n" . $xref . "\n%%EOF";

    return $pdf;
}


