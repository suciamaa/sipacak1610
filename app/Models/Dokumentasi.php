<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Dokumentasi extends Model {
    protected $table='dokumentasi'; protected $fillable=['detail_progress_id','nama_file','file_path','mime_type','ukuran'];
    public function detail(): BelongsTo { return $this->belongsTo(DetailProgress::class,'detail_progress_id'); }
}
