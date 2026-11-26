<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ProgressHarian extends Model {
 protected $table='progress_harian'; protected $fillable=['penugasan_id','tanggal'];
 protected function casts(): array { return ['tanggal'=>'date']; }
 public function penugasan(): BelongsTo { return $this->belongsTo(Penugasan::class); }
 public function details(): HasMany { return $this->hasMany(DetailProgress::class,'progress_id'); }
 public function uraian(): HasMany { return $this->hasMany(ProgressUraian::class,'progress_id'); }
 public function permasalahan(): HasMany { return $this->hasMany(ProgressPermasalahan::class,'progress_id'); }
 public function lpd(): HasMany { return $this->hasMany(Lpd::class,'penugasan_id','penugasan_id')->whereColumn('lpd.tanggal','progress_harian.tanggal'); }
 public function kwitansi(): HasMany { return $this->hasMany(Kwitansi::class,'penugasan_id','penugasan_id')->whereColumn('kwitansi.tanggal','progress_harian.tanggal'); }
}
