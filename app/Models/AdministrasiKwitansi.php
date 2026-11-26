<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class AdministrasiKwitansi extends Model {
 protected $table='administrasi_kwitansi'; protected $fillable=['kegiatan_id','nama_bendahara_pengeluaran','nip_bendahara_pengeluaran','nama_pejabat_pembuat_komitmen','nip_pejabat_pembuat_komitmen','file_excel_path','file_excel_original_name','uploaded_at'];
 protected function casts():array{return ['uploaded_at'=>'datetime'];}
 public function kegiatan():BelongsTo{return $this->belongsTo(Kegiatan::class);}
 public function rows():HasMany{return $this->hasMany(DataExcelKwitansi::class,'administrasi_kwitansi_id');}
}
