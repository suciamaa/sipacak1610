<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class DetailProgressPermasalahan extends Model {
    public $timestamps = false;
    protected $table = 'detail_progress_permasalahan';
    protected $fillable = ['detail_progress_id','permasalahan_id','keterangan_lainnya'];
    public function master(): BelongsTo { return $this->belongsTo(MasterPermasalahanLpd::class, 'permasalahan_id'); }
}
