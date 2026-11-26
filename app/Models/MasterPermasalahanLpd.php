<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class MasterPermasalahanLpd extends Model {protected $table='master_permasalahan_lpd';protected $fillable=['kegiatan_id','nama_permasalahan','is_default','is_active'];protected function casts():array{return ['is_default'=>'boolean','is_active'=>'boolean'];}public function kegiatan():BelongsTo{return $this->belongsTo(Kegiatan::class);}public function progressPermasalahan():HasMany{return $this->hasMany(ProgressPermasalahan::class,'permasalahan_id');}}
