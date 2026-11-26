<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class DetailProgressUraian extends Model {
    public $timestamps = false;
    protected $table = 'detail_progress_uraian';
    protected $fillable = ['detail_progress_id','uraian_id','keterangan_lainnya'];
    public function master(): BelongsTo { return $this->belongsTo(MasterUraianLpd::class, 'uraian_id'); }
}
