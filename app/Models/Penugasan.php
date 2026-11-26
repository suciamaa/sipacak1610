<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Penugasan extends Model {
 protected $table='penugasan'; protected $fillable=['kegiatan_id','petugas_id','kecamatan_id','role','status_penugasan'];
 public function kegiatan(): BelongsTo { return $this->belongsTo(Kegiatan::class); }
 public function petugas(): BelongsTo { return $this->belongsTo(Petugas::class); }
 public function kecamatan(): BelongsTo { return $this->belongsTo(Kecamatan::class); }
 public function progress(): HasMany { return $this->hasMany(ProgressHarian::class); }
 public function lpd(): HasMany { return $this->hasMany(Lpd::class); }
 public function kwitansi(): HasMany { return $this->hasMany(Kwitansi::class); }
}
