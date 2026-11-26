<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MasterSls extends Model {
 protected $table='master_sls'; protected $fillable=['desa_kelurahan_id','nmsls'];
 public function desaKelurahan(){return $this->belongsTo(MasterDesaKelurahan::class,'desa_kelurahan_id');}
 public function subSls(){return $this->hasMany(MasterSubSls::class,'sls_id');}
}
