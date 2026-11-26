<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProgressPermasalahan extends Model {public $timestamps=false;protected $table='progress_permasalahan';protected $fillable=['progress_id','permasalahan_id','keterangan_lainnya'];public function master():BelongsTo{return $this->belongsTo(MasterPermasalahanLpd::class,'permasalahan_id');}}
