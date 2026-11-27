<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Kegiatan,Petugas,Kecamatan,Penugasan,ActivityLog};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class PenugasanController extends Controller {
 public function create(Kegiatan $kegiatan){$kegiatan->load('roles');$petugas=Petugas::with('user')->whereHas('user',fn($q)=>$q->where('role','petugas'))->orderBy('nama_lengkap')->get();$kecamatan=Kecamatan::orderBy('nama_kecamatan')->get();return view('admin.penugasan.create',compact('kegiatan','petugas','kecamatan'));}
 public function store(Request $r,Kegiatan $kegiatan){
  $roles=$kegiatan->roles->pluck('role')->all();
  $data=$r->validate([
   'assignments'=>'required|array|min:1',
   'assignments.*.kecamatan_id'=>'required|exists:kecamatan,id',
   'assignments.*.role'=>['required',Rule::in($roles)],
   'assignments.*.petugas_ids'=>'required|array|min:1',
   'assignments.*.petugas_ids.*'=>'required|distinct|exists:petugas,id',
  ],[
   'assignments.*.petugas_ids.required'=>'Pilih minimal satu petugas untuk setiap kecamatan.',
   'assignments.*.petugas_ids.min'=>'Pilih minimal satu petugas untuk setiap kecamatan.',
  ]);
  $created=0;$duplicates=0;$seen=[];
  DB::transaction(function()use($data,$kegiatan,$r,&$created,&$duplicates,&$seen){
   foreach($data['assignments'] as $group){
    foreach($group['petugas_ids'] as $petugasId){
     $key=$group['kecamatan_id'].'-'.$petugasId;
     if(isset($seen[$key])){$duplicates++;continue;}
     $seen[$key]=true;
     $exists=Penugasan::where(['kegiatan_id'=>$kegiatan->id,'petugas_id'=>$petugasId,'kecamatan_id'=>$group['kecamatan_id']])->exists();
     if($exists){$duplicates++;continue;}
     $assignment=Penugasan::create(['kegiatan_id'=>$kegiatan->id,'petugas_id'=>$petugasId,'kecamatan_id'=>$group['kecamatan_id'],'role'=>$group['role']]);
     $created++;
     ActivityLog::create(['user_id'=>$r->user()->id,'aksi'=>'assign','entitas'=>'penugasan','entitas_id'=>$assignment->id,'data_sesudah'=>$assignment->toArray(),'ip_address'=>$r->ip()]);
    }
   }
  });
  return redirect()->route('admin.kegiatan.show',$kegiatan)->with('status',"{$created} penugasan ditambahkan; {$duplicates} duplikasi dilewati.");
 }
 public function edit(Penugasan $penugasan){
  $penugasan->load(['kegiatan.roles','petugas.user']);
  $kegiatan=$penugasan->kegiatan;
  $petugas=Petugas::with('user')->whereHas('user',fn($q)=>$q->where('role','petugas'))->orderBy('nama_lengkap')->get();
  $kecamatan=Kecamatan::orderBy('nama_kecamatan')->get();
  $punyaProgress=$penugasan->progress()->exists();
  return view('admin.penugasan.edit',compact('penugasan','kegiatan','petugas','kecamatan','punyaProgress'));
 }
 public function update(Request $r,Penugasan $penugasan){
  $kegiatan=$penugasan->kegiatan()->with('roles')->first();
  $punyaProgress=$penugasan->progress()->exists();
  $data=$r->validate([
   'petugas_id'=>[$punyaProgress?'nullable':'required','exists:petugas,id'],
   'kecamatan_id'=>[$punyaProgress?'nullable':'required','exists:kecamatan,id'],
   'role'=>['required',Rule::in($kegiatan->roles->pluck('role')->all())],
   'status_penugasan'=>['required',Rule::in(['aktif','nonaktif'])],
  ]);
  // Petugas dan kecamatan dikunci setelah ada progress agar data laporan tidak berpindah pemilik/wilayah.
  if($punyaProgress){unset($data['petugas_id'],$data['kecamatan_id']);}
  $petugasId=$data['petugas_id']??$penugasan->petugas_id;
  $kecamatanId=$data['kecamatan_id']??$penugasan->kecamatan_id;
  $bentrok=Penugasan::where(['kegiatan_id'=>$kegiatan->id,'petugas_id'=>$petugasId,'kecamatan_id'=>$kecamatanId])->where('id','!=',$penugasan->id)->exists();
  if($bentrok){return back()->withInput()->withErrors(['petugas_id'=>'Petugas tersebut sudah ditugaskan di kecamatan ini pada kegiatan yang sama.']);}
  $before=$penugasan->toArray();
  $penugasan->update($data);
  ActivityLog::create(['user_id'=>$r->user()->id,'aksi'=>'update','entitas'=>'penugasan','entitas_id'=>$penugasan->id,'data_sebelum'=>$before,'data_sesudah'=>$penugasan->fresh()->toArray(),'ip_address'=>$r->ip()]);
  return redirect()->route('admin.kegiatan.show',$kegiatan)->with('status','Penugasan berhasil diperbarui.');
 }
 public function destroy(Request $r,Penugasan $penugasan){abort_if($penugasan->progress()->exists(),422,'Penugasan sudah memiliki progress. Ubah status menjadi nonaktif.');$penugasan->delete();return back()->with('status','Penugasan dihapus.');}
}
