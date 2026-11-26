<?php
namespace App\Imports;
use Maatwebsite\Excel\Concerns\{ToCollection,WithHeadingRow};
use Illuminate\Support\Collection;
class KwitansiImport implements ToCollection,WithHeadingRow {
 public array $rows=[];
 public function collection(Collection $rows){$this->rows=$rows->map(fn($r)=>$r->toArray())->all();}
}
