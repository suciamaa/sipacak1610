<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MasterSubSls extends Model {
 protected $table='master_sub_sls'; protected $fillable=['sls_id','nmsubsls'];
 public function sls(){return $this->belongsTo(MasterSls::class,'sls_id');}
}
