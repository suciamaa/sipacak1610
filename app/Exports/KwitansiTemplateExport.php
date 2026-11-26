<?php
namespace App\Exports;

use App\Models\Kegiatan;
use App\Services\KwitansiWilayahService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class KwitansiTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(private readonly Kegiatan $kegiatan) {}

    public function headings(): array
    {
        return [
            'Nomor Surat Tugas',
            'Nama Pelaksana Perjalanan Dinas',
            'Tanggal Surat Tugas',
            'Tanggal Tugas',
            'Asal Penugasan',
            'Daerah Tujuan Perjalanan Dinas',
            'Lama Perjalanan',
            'Berangkat',
            'Pulang',
            'Jumlah Nominal',
            'Hal',
            'Bulan Pengajuan Kwitansi',
        ];
    }

    public function array(): array
    {
        $assignments = $this->kegiatan->penugasan()
            ->with(['petugas', 'kecamatan', 'progress.details.desaKelurahan'])
            ->get();
        $rows = [];
        foreach ($assignments as $assignment) {
            foreach ($assignment->progress->whereBetween('tanggal', [$this->kegiatan->tanggal_mulai, $this->kegiatan->tanggal_selesai])->sortBy('tanggal') as $progress) {
                $villages = $progress->details->pluck('desa')->map(fn ($v) => trim((string) $v))->filter()->unique(fn ($v) => mb_strtolower($v))->values()->all();
                if (!$villages) continue;
                try {
                    $destination = app(KwitansiWilayahService::class)->destination($progress, $assignment);
                } catch (\Throwable) {
                    // Preserve the raw source names in the workbook. The importer
                    // will report the unresolved master-data error instead of guessing.
                    $destination = self::joinVillages($villages) . ' Kecamatan ' . $assignment->kecamatan->nama_kecamatan;
                }
                $rows[] = [
                    '',
                    $assignment->petugas->nama_lengkap,
                    '',
                    $progress->tanggal->format('d/m/Y'),
                    '',
                    $destination,
                    '', '', '', '', '', '',
                ];
            }
        }
        return $rows;
    }

    public static function joinVillages(array $villages): string
    {
        $villages = array_values(array_unique(array_filter(array_map('trim', $villages), fn ($v) => $v !== '')));
        $count = count($villages);
        if ($count <= 1) return $villages[0] ?? '';
        if ($count === 2) return $villages[0] . ' dan ' . $villages[1];
        return implode(', ', array_slice($villages, 0, -1)) . ' dan ' . $villages[$count - 1];
    }
}
