<?php

namespace App\Services;

use App\Models\{DetailProgress, Kegiatan, Penugasan, ProgressHarian, MasterDesaKelurahan};
use Illuminate\Support\Collection;
use RuntimeException;

class KwitansiWilayahService
{
    private function key(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value));
        return mb_strtolower($value, 'UTF-8');
    }

    private function cleanName(string $value): string
    {
        return trim((string) preg_replace('/^(?:desa|kelurahan)\s+/iu', '', trim($value)));
    }

    /**
     * Resolve each progress destination only inside the assignment's district.
     * Unknown names fail closed; the generator never guesses a district or type.
     *
     * @return Collection<int, MasterDesaKelurahan>
     */
    public function resolve(ProgressHarian $progress, Penugasan $assignment): Collection
    {
        $progress->loadMissing('details.desaKelurahan');
        $resolved = collect();

        foreach ($progress->details as $detail) {
            $raw = trim((string) $detail->desa);
            if ($raw === '') {
                continue;
            }

            $master = $detail->desaKelurahan;
            if ($master && (int) $master->kecamatan_id !== (int) $assignment->kecamatan_id) {
                throw new RuntimeException("Wilayah '{$raw}' pada progress tidak termasuk kecamatan penugasan.");
            }

            if (! $master) {
                $candidate = $this->cleanName($raw);
                $master = MasterDesaKelurahan::query()
                    ->where('kecamatan_id', $assignment->kecamatan_id)
                    ->get()
                    ->first(fn ($item) => $this->key($item->nama_wilayah) === $this->key($candidate));
            }

            if (! $master) {
                throw new RuntimeException("Wilayah '{$raw}' tidak ditemukan dalam master Desa/Kelurahan untuk kecamatan penugasan.");
            }

            if (! in_array($master->jenis_wilayah, ['desa', 'kelurahan'], true)) {
                throw new RuntimeException("Jenis wilayah '{$master->nama_wilayah}' belum valid pada master wilayah.");
            }

            if (! $resolved->contains('id', $master->id)) {
                $resolved->push($master);
            }
        }

        if ($resolved->isEmpty()) {
            throw new RuntimeException('Progress belum memiliki desa/kelurahan tujuan perjalanan dinas yang valid.');
        }

        return $resolved->values();
    }

    public function destination(ProgressHarian $progress, Penugasan $assignment): string
    {
        $items = $this->resolve($progress, $assignment)
            ->map(fn ($item) => ucfirst($item->jenis_wilayah).' '.$item->nama_wilayah)
            ->all();

        return $this->join($items).' Kecamatan '.$assignment->kecamatan->nama_kecamatan;
    }

    public function journeyDescription(ProgressHarian $progress, Penugasan $assignment): string
    {
        $items = $this->resolve($progress, $assignment)
            ->map(fn ($item) => ucfirst($item->jenis_wilayah).' '.$item->nama_wilayah)
            ->all();

        return 'Ibukota kecamatan '.$assignment->kecamatan->nama_kecamatan.' ke '.$this->join($items).' (PP)';
    }

    public function matches(string $provided, ProgressHarian $progress, Penugasan $assignment): bool
    {
        return $this->key($provided) === $this->key($this->destination($progress, $assignment));
    }

    private function join(array $items): string
    {
        $items = array_values(array_unique(array_filter(array_map('trim', $items))));
        $count = count($items);

        if ($count === 0) return '';
        if ($count === 1) return $items[0];
        if ($count === 2) return $items[0].' dan '.$items[1];

        return implode(', ', array_slice($items, 0, -1)).' dan '.end($items);
    }
}
