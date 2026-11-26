<?php

namespace App\Services;

use App\Models\DetailProgress;
use Illuminate\Support\Collection;

class LpdNarrativeService
{
    private function normalized(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    public function location(DetailProgress $detail): string
    {
        $type = ($detail->jenis_wilayah ?? 'desa') === 'kelurahan' ? 'Kelurahan' : 'Desa';
        $village = trim((string) $detail->desa);
        $sls = trim((string) $detail->sls);
        $label = $type . ' ' . $village;
        if (($detail->jenis_data_wilayah ?? 'sls') === 'blok_sensus') {
            return $label . ', Blok Sensus ' . $sls;
        }
        return $label . ($sls !== '' ? ', SLS ' . $sls : '');
    }

    public function activityIntro(DetailProgress $detail, string $district): string
    {
        $names = $detail->uraian->map(fn ($row) => $this->normalized($row->master?->nama_uraian ?? ''))->filter()->all();
        $has = fn (array $needles) => collect($names)->contains(fn ($name) => collect($needles)->contains(fn ($needle) => str_contains($name, $needle)));
        $location = $this->location($detail);
        $parts = ["Melaksanakan perjalanan kegiatan menuju {$location} dalam rangka pelaksanaan kegiatan di Kecamatan {$district}."];
        $village = trim((string) $detail->desa);
        $sls = trim((string) $detail->sls);
        $officials = [];
        if ($has(['kepala desa'])) $officials[] = "Kepala Desa {$village}";
        if ($has(['lurah'])) $officials[] = "Lurah {$village}";
        if ($has(['ketua sls'])) $officials[] = "Ketua SLS {$sls}";
        if ($officials) {
            $parts[] = 'Kegiatan diawali dengan menemui ' . $this->joinNatural($officials) . ' untuk koordinasi dan memperoleh informasi terkait kondisi wilayah serta keberadaan sasaran pendataan.';
        }
        $sweep = $has(['penelusuran wilayah', 'penyisiran']);
        $confirm = $has(['konfirmasi ulang data']);
        $complete = $has(['pendataan lengkap']);
        if ($sweep && $confirm && $complete) {
            $parts[] = 'Selanjutnya dilakukan penyisiran wilayah untuk menemukan keluarga atau usaha yang terlewat, konfirmasi ulang data sekaligus melaksanakan pendataan lengkap untuk keluarga, BKU, dan usaha dalam keluarga.';
        } elseif ($sweep && $complete) {
            $parts[] = 'Selanjutnya dilakukan penyisiran wilayah untuk menemukan keluarga atau usaha yang terlewat dan melaksanakan pendataan lengkap untuk keluarga, BKU, dan usaha dalam keluarga.';
        } elseif ($sweep && $confirm) {
            $parts[] = 'Selanjutnya dilakukan penyisiran wilayah untuk menemukan keluarga atau usaha yang terlewat dan konfirmasi ulang data.';
        } elseif ($confirm && $complete) {
            $parts[] = 'Selanjutnya dilakukan konfirmasi ulang data sekaligus melaksanakan pendataan lengkap untuk keluarga, BKU, dan usaha dalam keluarga.';
        } elseif ($sweep) {
            $parts[] = 'Selanjutnya dilakukan penyisiran wilayah untuk menemukan keluarga atau usaha yang terlewat.';
        } elseif ($confirm) {
            $parts[] = 'Selanjutnya dilakukan konfirmasi ulang data.';
        } elseif ($complete) {
            $parts[] = 'Selanjutnya dilakukan pendataan lengkap untuk keluarga, BKU, dan usaha dalam keluarga.';
        }
        $other = $detail->uraian->filter(fn ($row) => str_contains($this->normalized($row->master?->nama_uraian ?? ''), 'lainnya'))
            ->pluck('keterangan_lainnya')->filter()->unique()->values();
        foreach ($other as $description) $parts[] = trim((string) $description);
        return implode(' ', $parts);
    }

    public function assignmentResult(DetailProgress $detail): string
    {
        $kel = (int) ($detail->assignment_keluarga ?? 0);
        $bku = (int) ($detail->assignment_usaha ?? 0);
        $uk = (int) ($detail->assignment_usaha_dengan_keluarga ?? 0);
        $location = $this->location($detail);
        if ($kel === 0 && $bku === 0 && $uk === 0) {
            return "Berdasarkan hasil penyisiran dan pendataan lengkap di {$location} tidak didapatkan tambahan assignment.";
        }
        $sentences = [];
        if ($kel > 0) $sentences[] = "Diperoleh tambahan assignment keluarga sebanyak {$kel} assignment.";
        if ($bku > 0) $sentences[] = "Diperoleh tambahan assignment usaha (BKU) sebanyak {$bku} assignment.";
        if ($uk > 0) $sentences[] = "Diperoleh tambahan assignment usaha dalam keluarga sebanyak {$uk} assignment.";
        return "Berdasarkan hasil kegiatan di {$location}, " . implode(' ', $sentences);
    }

    public function problems(DetailProgress $detail): string
    {
        return $detail->permasalahan->map(function ($row) {
            $name = trim((string) ($row->master?->nama_permasalahan ?? ''));
            $other = trim((string) ($row->keterangan_lainnya ?? ''));
            return $name . ($other !== '' ? ': ' . $other : '');
        })->filter()->implode('; ');
    }

    public function joinNatural(array $items): string
    {
        $items = array_values(array_filter($items));
        return match (count($items)) {
            0 => '',
            1 => $items[0],
            2 => $items[0] . ' dan ' . $items[1],
            default => implode(', ', array_slice($items, 0, -1)) . ', dan ' . end($items),
        };
    }

    public function timeline(Collection $details): array
    {
        $rows = [];
        $sorted = $details->sortBy(fn ($d) => substr((string) $d->jam_mulai, 0, 5))->values();
        $insertedLunch = false;
        $previousEnd = null;
        foreach ($sorted as $index => $detail) {
            $start = $this->minutes(substr((string) $detail->jam_mulai, 0, 5));
            $end = $this->minutes(substr((string) $detail->jam_selesai, 0, 5));
            if ($start === null || $end === null) continue;
            if (!$insertedLunch && $previousEnd !== null && $previousEnd <= 720 && $start >= 780) {
                $rows[] = ['type' => 'ishoma', 'start' => 720, 'end' => 780];
                $insertedLunch = true;
            }
            if ($start < 720 && $end > 720) {
                $rows[] = ['type' => 'activity', 'detail' => $detail, 'start' => $start, 'end' => 720];
                if (!$insertedLunch) {
                    $rows[] = ['type' => 'ishoma', 'start' => 720, 'end' => 780];
                    $insertedLunch = true;
                }
                if ($end > 780) $rows[] = ['type' => 'activity', 'detail' => $detail, 'start' => 780, 'end' => $end];
            } elseif ($start >= 720 && $start < 780) {
                if (!$insertedLunch) {
                    $rows[] = ['type' => 'ishoma', 'start' => 720, 'end' => 780];
                    $insertedLunch = true;
                }
                if ($end > 780) $rows[] = ['type' => 'activity', 'detail' => $detail, 'start' => 780, 'end' => $end];
            } else {
                $rows[] = ['type' => 'activity', 'detail' => $detail, 'start' => $start, 'end' => $end];
            }
            $previousEnd = max($previousEnd ?? 0, $end);
        }
        if (!$insertedLunch && $previousEnd === 720) {
            $rows[] = ['type' => 'ishoma', 'start' => 720, 'end' => 780];
        }
        usort($rows, fn ($a, $b) => [$a['start'], $a['type'] === 'ishoma' ? 0 : 1] <=> [$b['start'], $b['type'] === 'ishoma' ? 0 : 1]);
        return $rows;
    }

    public function formatMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function minutes(string $time): ?int
    {
        if (!preg_match('/^([0-2][0-9]):([0-5][0-9])$/', $time, $m)) return null;
        return ((int) $m[1] * 60) + (int) $m[2];
    }
}
