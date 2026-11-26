<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  $defaults = [
   ['Kepala Desa','penguasa_wilayah'], ['Lurah','penguasa_wilayah'], ['Ketua SLS','penguasa_wilayah'],
   ['Penelusuran Wilayah','aktivitas'], ['Aktivitas Lainnya','aktivitas'],
  ];
  foreach ($defaults as [$name,$type]) {
   $existing = DB::table('master_uraian_lpd')->whereNull('kegiatan_id')->whereRaw('LOWER(nama_uraian)=?', [strtolower($name)])->first();
   if ($existing) DB::table('master_uraian_lpd')->where('id',$existing->id)->update(['nama_uraian'=>$name,'is_default'=>1,'is_active'=>1,'updated_at'=>now()]);
   else DB::table('master_uraian_lpd')->insert(['kegiatan_id'=>null,'nama_uraian'=>$name,'tipe_uraian'=>$type,'is_default'=>1,'is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
  }
 } public function down(): void {}
};
