<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Kegiatan,Petugas,Kecamatan,Penugasan,ActivityLog};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class PenugasanController extends Controller {
 public function create(Kegiatan $kegiatan){$kegiatan->load('roles');$petugas=Petugas::with('user')->whereHas('user',fn($q)=>$q->where('role','petugas'))->orderBy('nama_lengkap')->get();$assigned=$kegiatan->penugasan()->pluck('petugas_id')->unique()->all();$kecamatan=Kecamatan::orderBy('nama_kecamatan')->get();return view('admin.penugasan.create',compact('kegiatan','petugas','kecamatan','assigned'));}
 public function store(Request $r,Kegiatan $kegiatan){$roles=$kegiatan->roles->pluck('role')->all();$data=$r->validate(['assignments'=>'required|array|min:1','assignments.*.petugas_id'=>'required|exists:petugas,id','assignments.*.kecamatan_id'=>'required|exists:kecamatan,id','assignments.*.role'=>['required',Rule::in($roles)]]);$created=0;$duplicates=0;DB::transaction(function()use($data,$kegiatan,$r,&$created,&$duplicates){foreach($data['assignments'] as $row){$exists=Penugasan::where(['kegiatan_id'=>$kegiatan->id,'petugas_id'=>$row['petugas_id'],'kecamatan_id'=>$row['kecamatan_id']])->exists();if($exists){$duplicates++;continue;} $p=Penugasan::create(['kegiatan_id'=>$kegiatan->id]+$row);$created++;ActivityLog::create(['user_id'=>$r->user()->id,'aksi'=>'assign','entitas'=>'penugasan','entitas_id'=>$p->id,'data_sesudah'=>$p->toArray(),'ip_address'=>$r->ip()]);}});return redirect()->route('admin.kegiatan.show',$kegiatan)->with('status',"{$created} penugasan ditambahkan; {$duplicates} duplikasi dilewati.");}
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
