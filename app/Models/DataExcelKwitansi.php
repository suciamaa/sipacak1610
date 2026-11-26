<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class DataExcelKwitansi extends Model {
 protected $table='data_excel_kwitansi'; protected $fillable=['administrasi_kwitansi_id','penugasan_id','tanggal','nomor_kwitansi','data_administrasi','status_matching'];
 protected function casts():array{return ['tanggal'=>'date','data_administrasi'=>'array'];}
 public function administrasi():BelongsTo{return $this->belongsTo(AdministrasiKwitansi::class,'administrasi_kwitansi_id');}
 public function penugasan():BelongsTo{return $this->belongsTo(Penugasan::class);}
}
