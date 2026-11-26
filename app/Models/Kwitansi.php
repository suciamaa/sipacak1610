<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Kwitansi extends Model {
    protected $table='kwitansi'; protected $fillable=['penugasan_id','tanggal','status_kwitansi','file_pdf','generated_at'];
    protected function casts(): array { return ['tanggal'=>'date','generated_at'=>'datetime']; }
    public function penugasan(): BelongsTo { return $this->belongsTo(Penugasan::class); }
}
