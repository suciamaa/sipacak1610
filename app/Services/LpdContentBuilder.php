<?php

namespace App\Services;

use App\Models\DetailProgress;
use App\Models\Penugasan;
use App\Models\ProgressHarian;

class LpdContentBuilder
{
    /**
     * Jendela ISHOMA [mulai, selesai] dalam menit sejak 00:00.
     * Kegiatan yang melewati jendela ini dipotong, dan baris ISHOMA selalu muncul.
     */
    private const LUNCH_START = 12 * 60;
    private const LUNCH_END = 13 * 60;

    /**
     * Timeline LPD dibangun dari jam masing-masing aktivitas pada setiap detail SLS.
     * Jam 12:00-13:00 merupakan jeda tetap harian, bukan durasi yang disimpan petugas.
     */
    public function build(ProgressHarian $progress, Penugasan $assignment): array
    {
        $details = $progress->details->values();
        $prepared = $details->map(fn (DetailProgress $detail) => $this->prepareDetail($detail, $assignment));
        $events = [];

        foreach ($prepared as $item) {
            $detail = $item['detail'];

            // Perjalanan dan koordinasi tetap diambil dari kolom yang diisi petugas.
            $this->appendEvent(
                $events, $item, 'perjalanan',
                $this->minutes($detail->perjalanan_jam_mulai),
                $this->minutes($detail->perjalanan_jam_selesai),
                $item['paragraph_1']
            );
            $this->appendEvent(
                $events, $item, 'koordinasi',
                $this->minutes($detail->koordinasi_jam_mulai),
                $this->minutes($detail->koordinasi_jam_selesai),
                $item['paragraph_2']
            );

            // Versi form terbaru menyimpan setiap jenis kegiatan beserta jamnya
            // di JSON kegiatan_rincian. Jangan gabungkan seluruh jenis kegiatan
            // menjadi satu baris/rentang waktu.
            $activityItems = collect($detail->kegiatan_rincian ?? [])
                ->filter(function ($row) {
                    if (!is_array($row)) {
                        return false;
                    }
                    $start = $this->minutes($row['jam_mulai'] ?? null);
                    $end = $this->minutes($row['jam_selesai'] ?? null);
                    return $start !== null && $end !== null && $end > $start;
                })
                ->values();

            if ($activityItems->isEmpty()) {
                $start = $this->minutes($detail->kegiatan_jam_mulai ?: $detail->jam_mulai);
                $end = $this->minutes($detail->kegiatan_jam_selesai ?: $detail->jam_selesai);
                $text = trim((string) ($detail->kegiatan_uraian ?: $item['activity_narrative']));
                if ($text === '') {
                    $text = 'Melaksanakan kegiatan lapangan.';
                }
                if ($start !== null && $end !== null && $end > $start) {
                    $activityEvent = $this->event($item, $start, $end, $text, true);
                    $activityEvent['output_suffix'] = $this->respondentOutput($detail, $assignment, $item['activity_name']);
                    $events[] = $activityEvent;
                }
                continue;
            }

            $lastIndex = $activityItems->count() - 1;
            foreach ($activityItems as $activityIndex => $activity) {
                $start = $this->minutes($activity['jam_mulai'] ?? null);
                $end = $this->minutes($activity['jam_selesai'] ?? null);
                if ($start === null || $end === null || $end <= $start) {
                    continue;
                }

                $text = trim((string) ($activity['uraian'] ?? ''));
                if ($text === '') {
                    $text = trim((string) ($activity['nama'] ?? $activity['jenis'] ?? 'Melaksanakan kegiatan lapangan.'));
                }

                // Output ditambahkan hanya pada kegiatan terakhir dari detail/SLS
                // yang bersangkutan, bukan kegiatan terakhir seluruh dokumen.
                $output = $activityIndex === $lastIndex
                    ? $this->respondentOutput($detail, $assignment, $item['activity_name'])
                    : '';

                $activityEvent = $this->event($item, $start, $end, $text, true);
                $activityEvent['output_suffix'] = $output;
                $events[] = $activityEvent;
            }
        }

        // Urutkan seluruh kegiatan berdasarkan waktu mulai sebenarnya.
        // Untuk waktu mulai yang sama, urutan input petugas tetap dipertahankan.
        foreach ($events as $index => &$event) {
            $event['_order'] = $index;
        }
        unset($event);
        usort($events, fn ($a, $b) =>
            ($a['start'] <=> $b['start'])
            ?: ($a['end'] <=> $b['end'])
            ?: ($a['_order'] <=> $b['_order'])
        );

        // ISHOMA adalah jeda tetap harian dan wajib muncul satu kali pada setiap LPD,
        // termasuk ketika tidak ada aktivitas yang bersinggungan dengan pukul 12:00-13:00.
        // Seluruh event tetap dipertahankan dan diurutkan berdasarkan waktu mulai.
        $showLunch = true;

        $timeline = [];
        $lunchInserted = false;
        foreach ($events as $event) {
            $segments = [];

            if (!$showLunch || $event['end'] <= self::LUNCH_START || $event['start'] >= self::LUNCH_END) {
                $segments[] = [$event['start'], $event['end']];
            } else {
                if ($event['start'] < self::LUNCH_START) {
                    $segments[] = [$event['start'], self::LUNCH_START];
                }
                if ($event['end'] > self::LUNCH_END) {
                    $segments[] = [self::LUNCH_END, $event['end']];
                }
            }

            foreach ($segments as [$segmentStart, $segmentEnd]) {
                if ($segmentEnd <= $segmentStart) {
                    continue;
                }

                if ($showLunch && !$lunchInserted && $segmentStart >= self::LUNCH_END) {
                    $this->pushLunch($timeline);
                    $lunchInserted = true;
                }

                $timeline[] = [
                    'type' => 'activity',
                    'time' => $this->time($segmentStart) . ' - ' . $this->time($segmentEnd),
                    'location' => $event['item']['location'],
                    'narrative' => $event['narrative'] . (
                        !empty($event['output_suffix']) && $segmentEnd === $event['end']
                            ? ' ' . $event['output_suffix']
                            : ''
                    ),
                    'problems' => '',
                    'solution' => '',
                    'detail' => $event['item']['detail'],
                ];
            }
        }

        if ($showLunch && !$lunchInserted) {
            // Jika semua aktivitas berakhir pada/selama jam istirahat, letakkan
            // ISHOMA setelah aktivitas sebelum tengah hari.
            $insertAt = count($timeline);
            foreach ($timeline as $i => $row) {
                if ($this->minutes(explode(' - ', $row['time'])[0] ?? null) >= self::LUNCH_END) {
                    $insertAt = $i;
                    break;
                }
            }
            array_splice($timeline, $insertAt, 0, [[
                'type' => 'ishoma',
                'time' => '12:00 - 13:00',
                'location' => '',
                'narrative' => 'ISHOMA',
                'problems' => '',
                'solution' => '',
                'detail' => null,
            ]]);
        }

        // Permasalahan dan solusi tetap dicantumkan satu kali pada tabel harian.
        $problemParts = $prepared->pluck('problems')->map(function ($value) {
            $value = preg_replace('/^Permasalahan yang dihadapi yaitu\s*/iu', '', trim((string) $value));
            return rtrim(trim((string) $value), " \t\n\r.");
        })->filter()->unique()->values()->all();
        $solutions = $prepared->pluck('solution')->map(fn ($v) => trim((string) $v))->filter()->unique()->values()->all();

        foreach ($timeline as &$entry) {
            if (($entry['type'] ?? '') !== 'activity') {
                continue;
            }
            $entry['problems'] = $problemParts
                ? 'Permasalahan yang dihadapi yaitu ' . $this->joinNatural($problemParts) . '.'
                : '';
            $entry['solution'] = implode("\n", $solutions);
            break;
        }
        unset($entry);

        // Hilangkan metadata pengurutan internal.
        return array_values($timeline);
    }

    private function respondentOutput(DetailProgress $detail, Penugasan $assignment, string $activityName): string
    {
        if ($assignment->role === 'PPL') {
            $count = $detail->jumlah_responden;
            if ($count === null && is_array($detail->output_rincian)) {
                $count = $detail->output_rincian['pendataan'] ?? null;
            }
            if ($count === null) {
                return '';
            }
            return "Dari kegiatan {$activityName}, dihasilkan output jumlah responden sebanyak " . (int) $count . '.';
        }

        $count = $detail->jumlah_responden_diawasi;
        if ($count === null && is_array($detail->output_rincian)) {
            $count = $detail->output_rincian['pendampingan'] ?? null;
        }
        if ($count === null) {
            return '';
        }
        return "Dari kegiatan {$activityName}, dihasilkan output jumlah responden yang diawasi sebanyak " . (int) $count . '.';
    }

    private function appendEvent(array &$events, array $item, string $type, ?int $start, ?int $end, string $narrative): void
    {
        if ($start === null || $end === null || $end <= $start || trim($narrative) === '') return;
        $events[] = $this->event($item, $start, $end, $narrative, true);
    }

    private function event(array $item, int $start, int $end, string $narrative, bool $issues): array
    {
        return ['item' => $item, 'start' => $start, 'end' => $end, 'narrative' => trim($narrative), 'issues' => $issues];
    }

    private function pushLunch(array &$timeline): void
    {
        $timeline[] = [
            'type' => 'ishoma',
            'time' => '12:00 - 13:00',
            'location' => '',
            'narrative' => 'ISHOMA',
            'problems' => '',
            'solution' => '',
            'detail' => null,
        ];
    }

    private function prepareDetail(DetailProgress $detail, Penugasan $assignment): array
    {
        $village = trim((string) $detail->desa);
        $villageType = ($detail->jenis_wilayah ?? 'desa') === 'kelurahan' ? 'Kelurahan' : 'Desa';
        $slsParts = $this->slsParts($detail);
        $isBlock = ($detail->jenis_data_wilayah ?? 'sls') === 'blok_sensus';

        $location = $villageType . ' ' . $village;
        if ($slsParts['sls'] !== '' && !$isBlock) {
            $location .= ', SLS ' . $slsParts['sls'];
        }

        if ($slsParts['sub'] !== '') {
            $location .= ', sub SLS ' . $slsParts['sub'];
        }

        if ($isBlock) {
            $block = trim((string) ($detail->blok_sensus ?: $detail->sls));
            if ($block !== '') {
                $location .= ', Blok Sensus ' . $block;
            }
        }

        $activityName = trim((string) $assignment->kegiatan?->nama_kegiatan);
        $activityName = $activityName !== '' ? $activityName : 'kegiatan lapangan';
        $district = trim((string) $assignment->kecamatan?->nama_kecamatan);

        $paragraph1 = "Melaksanakan perjalanan kegiatan menuju {$location} dalam rangka pelaksanaan kegiatan {$activityName} di Kecamatan {$district}, Kabupaten OGAN ILIR.";

        $leaders = [];
        $activities = [];

        foreach ($detail->uraian as $selected) {
            $name = trim((string) ($selected->master?->nama_uraian ?? ''));
            if ($name === '') {
                continue;
            }

            $type = $selected->master?->tipe_uraian ?? 'aktivitas';
            if ($type === 'penguasa_wilayah') {
                $leaders[] = $this->leaderLabel($name, $village, $slsParts['sls']);
                continue;
            }

            $activities[] = [
                'name' => $name,
                'other' => $selected->keterangan_lainnya,
            ];
        }

        $sentences = [];
        if ($leaders !== []) {
            $sentences[] = 'Kegiatan diawali dengan menemui ' . $this->joinNatural($leaders)
                . ' untuk melakukan koordinasi dan memperoleh informasi terkait kondisi wilayah serta keberadaan pelaku usaha yang menjadi sasaran pendataan.';
        }
        if ($activities !== []) {
            $activityNarrative = $this->activityNarrative($activities);
            if ($activityNarrative !== '') {
                $sentences[] = $activityNarrative;
            }
        }
        $paragraph2 = implode(' ', $sentences);

        $paragraph3 = $this->resultParagraph($detail, $assignment, $activityName, $location);

        $clean = static fn ($value): string => rtrim(trim((string) $value), " \t\n\r.");
        $travelDescription = $clean($detail->perjalanan_uraian);
        $coordinationDescription = $clean($detail->koordinasi_uraian);
        $activityDescription = $clean($detail->kegiatan_uraian);

        $paragraph1 = $travelDescription !== ''
            ? $travelDescription
            : "Melakukan perjalanan menuju wilayah penugasan {$location}, Kecamatan {$district}, Kabupaten Ogan Ilir.";

        $paragraph2 = $coordinationDescription !== ''
            ? $coordinationDescription
            : "Melakukan koordinasi dengan penguasa wilayah di {$location}.";

        $paragraph3 = $this->resultParagraph($detail, $assignment, $activityName, $location);
        $activityNarrative = $this->activityNarrative($activities);
        $problems = [];
        foreach ($detail->permasalahan as $selected) {
            $name = trim((string) ($selected->master?->nama_permasalahan ?? ''));
            if ($name === '') {
                continue;
            }

            if (str_contains(mb_strtolower($name), 'lainnya')) {
                $description = trim((string) ($selected->keterangan_lainnya ?? ''));
                if ($description !== '') {
                    $problems[] = $description;
                }
                continue;
            }

            $problemLabel = preg_replace('/\\bfasih\\b/i', 'FASIH', $name);
            $problemLabel = preg_replace('/\\bbku\\b/i', 'BKU', $problemLabel);
            $problems[] = trim($problemLabel);
        }

        $problemText = $problems !== []
            ? 'Permasalahan yang dihadapi yaitu ' . $this->joinNatural($problems) . '.'
            : '';
        $freeProblem = trim((string) $detail->permasalahan_uraian);
        if ($freeProblem !== '') {
            $problemText = 'Permasalahan yang dihadapi yaitu ' . rtrim($freeProblem, " \t\n\r.") . '.';
        }

        $start = $this->minutes($detail->jam_mulai);
        $end = $this->minutes($detail->jam_selesai);

        return [
            'detail' => $detail,
            'location' => $location,
            'activity_name' => $activityName,
            'start' => $start,
            'end' => $end,
            'raw_time' => trim((string) $detail->jam_mulai) . ' - ' . trim((string) $detail->jam_selesai),
            'paragraph_1' => $paragraph1,
            'paragraph_2' => $paragraph2,
            'paragraph_3' => $paragraph3,
            'activity_description' => $activityDescription !== '' ? $activityDescription : $activityNarrative,
            'activity_narrative' => $activityNarrative,
            'problems' => $problemText,
            'solution' => trim((string) $detail->pemecahan_masalah),
        ];
    }

    private function resultParagraph(
        DetailProgress $detail,
        Penugasan $assignment,
        string $activityName,
        string $location
    ): string {
        if ($assignment->role === 'PPL') {
            $respondents = (int) ($detail->jumlah_responden ?? 0);
            return "Dari kegiatan {$activityName}, dihasilkan output jumlah responden sebanyak {$respondents}.";
        }

        $supervisedRespondents = (int) ($detail->jumlah_responden_diawasi ?? 0);
        $checkedDocuments = (int) ($detail->jumlah_dokumen_diperiksa ?? 0);

        return "Dari kegiatan {$activityName}, dihasilkan output jumlah responden yang diawasi sebanyak {$supervisedRespondents} dan dokumen yang diperiksa sebanyak {$checkedDocuments}.";
    }

    private function hasDataActivity(DetailProgress $detail): bool
    {
        foreach ($detail->uraian as $selected) {
            $name = mb_strtolower(trim((string) ($selected->master?->nama_uraian ?? '')));
            if (
                str_contains($name, 'penelusuran')
                || str_contains($name, 'menelusuri')
                || str_contains($name, 'pendataan')
                || str_contains($name, 'penyisiran')
            ) {
                return true;
            }
        }

        return false;
    }

    private function slsParts(DetailProgress $detail): array
    {
        $sls = trim((string) ($detail->masterSls?->nmsls ?? ''));
        $sub = trim((string) ($detail->masterSubSls?->nmsubsls ?? ''));

        $stored = trim((string) $detail->sls);
        if ($sls === '' && $stored !== '') {
            $pieces = preg_split('/\s*\/\s*/', $stored, 2);
            $sls = trim((string) ($pieces[0] ?? ''));
            if ($sub === '' && isset($pieces[1])) {
                $sub = trim((string) $pieces[1]);
            }
        }

        return ['sls' => $sls, 'sub' => $sub];
    }

    private function leaderLabel(string $name, string $village, string $sls): string
    {
        $normalized = mb_strtolower(trim($name));

        if (str_contains($normalized, 'kepala desa')) {
            return 'Kepala Desa ' . $village;
        }
        if (str_contains($normalized, 'lurah')) {
            return 'Lurah ' . $village;
        }
        if (str_contains($normalized, 'ketua sls')) {
            return 'Ketua SLS' . ($sls !== '' ? ' ' . $sls : '');
        }

        return $name;
    }

    /**
     * Ubah pilihan uraian pada form menjadi narasi laporan seperti format
     * LPD acuan. Uraian khusus/lainnya tetap memakai keterangan yang diinput
     * petugas agar tidak mengarang kegiatan yang tidak dipilih.
     */
    private function activityNarrative(array $activities): string
    {
        $hasPenelusuran = false;
        $hasKonfirmasi = false;
        $hasPendataan = false;
        $custom = [];

        foreach ($activities as $activity) {
            $name = trim((string) ($activity['name'] ?? ''));
            $other = trim((string) ($activity['other'] ?? ''));
            $normalized = mb_strtolower($name);

            if (str_contains($normalized, 'penelusuran wilayah')) {
                $hasPenelusuran = true;
            } elseif (str_contains($normalized, 'konfirmasi ulang data')) {
                $hasKonfirmasi = true;
            } elseif (str_contains($normalized, 'pendataan lengkap')) {
                $hasPendataan = true;
            } else {
                $text = str_contains($normalized, 'lainnya') && $other !== '' ? $other : $name;
                if ($text !== '') {
                    $custom[] = rtrim($text, '. ') . '.';
                }
            }
        }

        $known = [];
        if ($hasPenelusuran) {
            $known[] = 'Selanjutnya dilakukan penyisiran wilayah untuk menemukan keluarga atau usaha yang terlewat';
        }
        if ($hasKonfirmasi) {
            $known[] = $hasPenelusuran
                ? 'konfirmasi ulang data SE2026'
                : 'Selanjutnya dilakukan konfirmasi ulang data SE2026';
        }
        if ($hasPendataan) {
            $known[] = ($hasPenelusuran || $hasKonfirmasi)
                ? 'sekaligus melaksanakan pendataan lengkap SE2026 untuk keluarga, BKU, dan usaha dalam keluarga'
                : 'Selanjutnya dilaksanakan pendataan lengkap SE2026 untuk keluarga, BKU, dan usaha dalam keluarga';
        }

        $narrative = '';
        if ($known !== []) {
            $narrative = implode(', ', $known);
            $narrative = preg_replace('/, sekaligus /', ' sekaligus ', $narrative) ?? $narrative;
            $narrative = rtrim($narrative, '. ') . '.';
        }

        if ($custom !== []) {
            $narrative .= ($narrative !== '' ? ' ' : '') . implode(' ', $custom);
        }

        return trim($narrative);
    }

    private function activityRow(array $item, string $time, string $narrative, bool $includeIssues): array
    {
        return [
            'type' => 'activity',
            'time' => $time,
            'location' => $item['location'],
            'narrative' => $narrative,
            'problems' => $includeIssues ? $item['problems'] : '',
            'solution' => $includeIssues ? $item['solution'] : '',
            'detail' => $item['detail'],
        ];
    }

    private function pushBreak(array &$timeline, array &$inserted, int $index): void
    {
        if (isset($inserted[$index]) || !isset(self::BREAKS[$index])) {
            return;
        }

        [$breakStart, $breakEnd] = self::BREAKS[$index];
        $timeline[] = [
            'type' => 'ishoma',
            'time' => $this->time($breakStart) . ' - ' . $this->time($breakEnd),
            'location' => '',
            'narrative' => 'ISHOMA',
            'problems' => '-',
            'solution' => '-',
            'detail' => null,
        ];
        $inserted[$index] = true;
    }

    private function joinParagraphs(array $paragraphs): string
    {
        return implode(' ', array_values(array_filter(
            array_map(fn ($paragraph) => trim((string) $paragraph), $paragraphs),
            fn ($paragraph) => $paragraph !== ''
        )));
    }

    private function joinNatural(array $items): string
    {
        $items = array_values(array_filter(array_map(
            fn ($item) => trim((string) $item),
            $items
        )));

        if (count($items) < 2) {
            return $items[0] ?? '';
        }
        if (count($items) === 2) {
            return $items[0] . ' dan ' . $items[1];
        }

        $last = array_pop($items);
        return implode(', ', $items) . ', dan ' . $last;
    }

    private function minutes($value): ?int
    {
        if (!$value) {
            return null;
        }

        $time = substr((string) $value, 0, 5);
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $matches)) {
            return null;
        }

        return ((int) $matches[1] * 60) + (int) $matches[2];
    }

    private function time(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
