<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PenugasanImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        // Rows are returned by Excel::toCollection(); this concern enables
        // automatic heading normalization (nama, wilayah_penugasan, role).
    }
}
