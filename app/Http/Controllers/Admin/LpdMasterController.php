<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Kegiatan,MasterUraianLpd,MasterPermasalahanLpd,ActivityLog};
use Illuminate\Http\Request;
class LpdMasterController extends Controller {
 public function index(Kegiatan $kegiatan){
  abort_unless(in_array($kegiatan->tujuan_akhir,['lpd','keduanya']),404);
  $penguasa=MasterUraianLpd::whereNull('kegiatan_id')->where('tipe_uraian','penguasa_wilayah')->orderBy('id')->get();
  $aktivitas=MasterUraianLpd::where(fn($q)=>$q->whereNull('kegiatan_id')->orWhere('kegiatan_id',$kegiatan->id))->where('tipe_uraian','aktivitas')->orderBy('is_default','desc')->orderBy('nama_uraian')->get();
  $masalah=MasterPermasalahanLpd::where(fn($q)=>$q->whereNull('kegiatan_id')->orWhere('kegiatan_id',$kegiatan->id))->get();
  return view('admin.lpd.fields',compact('kegiatan','penguasa','aktivitas','masalah'));
 }
 public function storeUraian(Request $r,Kegiatan $kegiatan){$d=$r->validate(['nama_uraian'=>'required|string|max:180']);$m=MasterUraianLpd::create(['kegiatan_id'=>$kegiatan->id,'nama_uraian'=>$d['nama_uraian'],'tipe_uraian'=>'aktivitas','is_default'=>false,'is_active'=>true]);$this->log($r,$m,'create');return back()->with('status','Aktivitas ditambahkan.');}
 public function updateUraian(Request $r,Kegiatan $kegiatan,MasterUraianLpd $item){abort_unless($item->kegiatan_id===$kegiatan->id && $item->tipe_uraian==='aktivitas' && !$item->is_default,404);$d=$r->validate(['nama_uraian'=>'required|string|max:180','is_active'=>'required|boolean']);$item->update($d);$this->log($r,$item,'update');return back()->with('status','Aktivitas diperbarui.');}
 public function deleteUraian(Request $r,Kegiatan $kegiatan,MasterUraianLpd $item){abort_unless($item->kegiatan_id===$kegiatan->id && $item->tipe_uraian==='aktivitas' && !$item->is_default,404);abort_if($item->progressUraian()->exists(),422,'Aktivitas sudah digunakan dalam progress. Nonaktifkan saja.');$item->delete();$this->log($r,$item,'delete');return back()->with('status','Aktivitas dihapus.');}
 public function storeMasalah(Request $r,Kegiatan $kegiatan){$d=$r->validate(['nama_permasalahan'=>'required|string|max:180']);$m=MasterPermasalahanLpd::create($d+['kegiatan_id'=>$kegiatan->id,'is_default'=>false,'is_active'=>true]);$this->log($r,$m,'create');return back()->with('status','Pilihan permasalahan ditambahkan.');}
 public function updateMasalah(Request $r,Kegiatan $kegiatan,MasterPermasalahanLpd $item){abort_unless($item->kegiatan_id===$kegiatan->id,404);$d=$r->validate(['nama_permasalahan'=>'required|string|max:180','is_active'=>'required|boolean']);$item->update($d);$this->log($r,$item,'update');return back()->with('status','Pilihan permasalahan diperbarui.');}
 public function deleteMasalah(Request $r,Kegiatan $kegiatan,MasterPermasalahanLpd $item){abort_unless($item->kegiatan_id===$kegiatan->id,404);abort_if($item->progressPermasalahan()->exists(),422,'Pilihan sudah digunakan. Nonaktifkan saja.');$item->delete();$this->log($r,$item,'delete');return back()->with('status','Pilihan dihapus.');}
 private function log($r,$m,$action){ActivityLog::create(['user_id'=>$r->user()->id,'aksi'=>$action,'entitas'=>class_basename($m),'entitas_id'=>$m->id,'data_sesudah'=>$m->toArray(),'ip_address'=>$r->ip()]);}
}
