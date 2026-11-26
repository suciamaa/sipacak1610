<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class KegiatanRole extends Model {
    public $timestamps = false; protected $table='kegiatan_roles'; protected $fillable=['kegiatan_id','role'];
    public function kegiatan(): BelongsTo { return $this->belongsTo(Kegiatan::class); }
}
