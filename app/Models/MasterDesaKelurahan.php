<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MasterDesaKelurahan extends Model {
 protected $table='master_desa_kelurahan'; protected $fillable=['kecamatan_id','nama_wilayah','jenis_wilayah'];
 public function kecamatan(){return $this->belongsTo(Kecamatan::class);}
 public function sls(){return $this->hasMany(MasterSls::class,'desa_kelurahan_id');}
}
