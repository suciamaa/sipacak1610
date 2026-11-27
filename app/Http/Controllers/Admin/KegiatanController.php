<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Kegiatan,KegiatanRole,ActivityLog};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class KegiatanController extends Controller {
 public function index(){
  $progressByKegiatan=\App\Models\ProgressHarian::query()->join('penugasan','penugasan.id','=','progress_harian.penugasan_id')->selectRaw('penugasan.kegiatan_id, COUNT(progress_harian.id) as total_progress')->groupBy('penugasan.kegiatan_id')->pluck('total_progress','kegiatan_id');
  $kegiatan=Kegiatan::withCount('penugasan')->orderByDesc('created_at')->get()->each(fn(Kegiatan $item)=>$item->progress_count=(int)($progressByKegiatan[$item->id]??0));
  return view('admin.kegiatan.index', \App\Http\Controllers\Admin\DashboardController::statistics()+['kegiatanAktif'=>$kegiatan->where('status','aktif')->values(),'kegiatanNonaktif'=>$kegiatan->where('status','nonaktif')->values(),'tab'=>request('tab')==='nonaktif'?'nonaktif':'aktif']);
 }
 public function create(){ return view('admin.kegiatan.form',['kegiatan'=>new Kegiatan]); }
 public function store(Request $r){ $data=$this->validated($r); $item=DB::transaction(function()use($data,$r){$k=Kegiatan::create(collect($data)->except('roles')->all()); foreach($data['roles'] as $role)$k->roles()->create(['role'=>$role]); ActivityLog::create(['user_id'=>$r->user()->id,'aksi'=>'create','entitas'=>'kegiatan','entitas_id'=>$k->id,'data_sesudah'=>$k->toArray(),'ip_address'=>$r->ip()]); return $k;}); return redirect()->route('admin.kegiatan.show',$item)->with('status','Kegiatan berhasil dibuat.'); }
 public function show(Kegiatan $kegiatan){ $kegiatan->load(['roles','penugasan.petugas.user','penugasan.kecamatan']); $punyaProgress=$kegiatan->penugasan()->whereHas('progress')->exists(); $totalProgress=\App\Models\ProgressHarian::whereHas('penugasan',fn($q)=>$q->where('kegiatan_id',$kegiatan->id))->count(); return view('admin.kegiatan.show',compact('kegiatan','punyaProgress','totalProgress')); }
 public function edit(Kegiatan $kegiatan){ $kegiatan->load('roles'); return view('admin.kegiatan.form',compact('kegiatan')); }
 public function update(Request $r,Kegiatan $kegiatan){ if ($kegiatan->penugasan()->whereHas('progress')->exists() && $r->input('jenis_data_wilayah') !== $kegiatan->jenis_data_wilayah) { return back()->withInput()->withErrors(['jenis_data_wilayah'=>'Jenis data wilayah tidak dapat diubah karena kegiatan sudah memiliki progress.']); } $data=$this->validated($r); DB::transaction(function()use($data,$kegiatan,$r){$before=$kegiatan->toArray();$kegiatan->update(collect($data)->except('roles')->all());$kegiatan->roles()->delete();foreach($data['roles'] as $role)$kegiatan->roles()->create(['role'=>$role]);ActivityLog::create(['user_id'=>$r->user()->id,'aksi'=>'update','entitas'=>'kegiatan','entitas_id'=>$kegiatan->id,'data_sebelum'=>$before,'data_sesudah'=>$kegiatan->fresh()->toArray(),'ip_address'=>$r->ip()]);}); return redirect()->route('admin.kegiatan.show',$kegiatan)->with('status','Kegiatan berhasil diperbarui.'); }
 public function destroy(Request $r,Kegiatan $kegiatan){
  if($kegiatan->penugasan()->whereHas('progress')->exists()){
   return back()->withErrors(['kegiatan'=>'Kegiatan sudah memiliki progress petugas dan tidak dapat dihapus. Ubah statusnya menjadi Nonaktif sebagai gantinya.']);
  }
  $berkasExcel=$kegiatan->administrasiKwitansi?->file_excel_path;
  DB::transaction(function()use($r,$kegiatan){
   ActivityLog::create(['user_id'=>$r->user()->id,'aksi'=>'delete','entitas'=>'kegiatan','entitas_id'=>$kegiatan->id,'data_sebelum'=>$kegiatan->toArray(),'ip_address'=>$r->ip()]);
   $kegiatan->delete();
  });
  if($berkasExcel){\Illuminate\Support\Facades\Storage::disk('local')->delete($berkasExcel);}
  return redirect()->route('admin.kegiatan.index')->with('status','Kegiatan "'.$kegiatan->nama_kegiatan.'" berhasil dihapus.');
 }
 private function validated(Request $r):array { return $r->validate(['nama_kegiatan'=>'required|string|max:150','nomor_kegiatan'=>[Rule::requiredIf(fn () => in_array($r->input('tujuan_akhir'), ['kwitansi','keduanya'], true)), 'nullable', 'string', 'max:150'],'tanggal_mulai'=>'required|date','tanggal_selesai'=>'required|date|after_or_equal:tanggal_mulai','status'=>['required',Rule::in(['aktif','nonaktif'])],'deskripsi'=>'nullable|string|max:5000','tujuan_akhir'=>['required',Rule::in(['lpd','kwitansi','keduanya'])],'jenis_data_wilayah'=>['required',Rule::in(['sls','blok_sensus'])],'roles'=>'required|array|min:1','roles.*'=>[Rule::in(['PPL','PML','Supervisor'])]],[],['nomor_kegiatan'=>'Akun POK']); }
}
