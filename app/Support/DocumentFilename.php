<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/** Centralized, Windows-safe filenames for generated SIPACAK PDFs and archives. */
class DocumentFilename
{
    private const MONTHS = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public static function make(
        string $type,
        ?string $activity,
        ?string $staff,
        CarbonInterface|string $date,
        string $extension = 'pdf',
        bool $combined = false
    ): string {
        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        $dateLabel = $date->day . ' ' . self::MONTHS[(int) $date->month] . ' ' . $date->year;
        $prefix = self::typeLabel($type);
        $combinedLabel = $combined ? $prefix . ' Gabungan ' : $prefix . ' ';

        return self::safe($combinedLabel . self::safe($activity ?: 'Kegiatan') . '_'
            . self::safe($staff ?: 'Petugas') . '_' . $dateLabel) . '.' . self::safeExtension($extension);
    }

    /** Build a truthful name for a set of documents from the same assignment. */
    public static function forCollection(string $type, $assignment, $documents, string $extension): string
    {
        $dates = collect($documents)->pluck('tanggal')->filter()->map(
            fn ($date) => Carbon::parse($date)->toDateString()
        )->unique()->sort()->values();

        $activity = $assignment->kegiatan?->nama_kegiatan;
        $staff = $assignment->petugas?->nama_lengkap;

        if ($dates->count() === 1) {
            return self::make($type, $activity, $staff, $dates->first(), $extension);
        }

        $dateLabel = 'Beberapa Tanggal';
        if ($dates->count() > 1 && $dates->last() === Carbon::parse($dates->first())->addDays($dates->count() - 1)->toDateString()) {
            $first = Carbon::parse($dates->first());
            $last = Carbon::parse($dates->last());
            if ($first->year === $last->year && $first->month === $last->month) {
                $dateLabel = $first->day . '-' . $last->day . ' ' . self::MONTHS[(int) $last->month] . ' ' . $last->year;
            } elseif ($first->year === $last->year) {
                $dateLabel = $first->day . ' ' . self::MONTHS[(int) $first->month] . ' - ' . $last->day . ' ' . self::MONTHS[(int) $last->month] . ' ' . $last->year;
            } else {
                $dateLabel = $first->day . ' ' . self::MONTHS[(int) $first->month] . ' ' . $first->year . ' - ' . $last->day . ' ' . self::MONTHS[(int) $last->month] . ' ' . $last->year;
            }
        }

        $prefix = self::typeLabel($type);
        $label = $extension === 'pdf' ? $prefix . ' Gabungan ' : $prefix . ' ';
        return self::safe($label . self::safe($activity ?: 'Kegiatan') . '_'
            . self::safe($staff ?: 'Petugas') . '_' . $dateLabel) . '.' . self::safeExtension($extension);
    }

    private static function typeLabel(string $type): string
    {
        return strtolower($type) === 'lpd' ? 'LPD' : 'Kwitansi';
    }

    private static function safe(string $value): string
    {
        $value = preg_replace('/[\\x00-\\x1F\\x7F<>:"\\/\\\\|?*]+/u', ' ', trim($value)) ?? '';
        $value = preg_replace('/\\s+/u', ' ', $value) ?? '';
        $value = trim($value, " .\\t\\n\\r\\0\\x0B");
        if ($value === '') $value = 'Dokumen';
        // Windows reserves these device names, even when followed by an extension.
        if (preg_match('/^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\\.|$)/i', $value)) {
            $value = 'reserved-' . $value;
        }
        return $value;
    }

    private static function safeExtension(string $extension): string
    {
        $extension = strtolower(preg_replace('/[^a-z0-9]/i', '', $extension) ?? 'pdf');
        return $extension !== '' ? $extension : 'pdf';
    }
}
