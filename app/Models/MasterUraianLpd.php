<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class MasterUraianLpd extends Model {protected $table='master_uraian_lpd';protected $fillable=['kegiatan_id','nama_uraian','tipe_uraian','is_default','is_active'];protected function casts():array{return ['is_default'=>'boolean','is_active'=>'boolean'];}public function kegiatan():BelongsTo{return $this->belongsTo(Kegiatan::class);}public function progressUraian():HasMany{return $this->hasMany(ProgressUraian::class,'uraian_id');}}
