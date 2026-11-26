<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Kegiatan,Penugasan,ProgressHarian,DetailProgress,ActivityLog,Lpd,Kwitansi,Dokumentasi};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class MonitoringController extends Controller {
 public function index(Request $r,Kegiatan $kegiatan){
  // Daftar tanggal sesuai periode kegiatan yang diinput admin.
  $dates=collect();
  if($kegiatan->tanggal_mulai&&$kegiatan->tanggal_selesai){
   foreach(\Carbon\CarbonPeriod::create($kegiatan->tanggal_mulai->copy()->startOfDay(),$kegiatan->tanggal_selesai->copy()->startOfDay()) as $d)$dates->push($d->copy());
  }
  $months=$dates->groupBy(fn($d)=>$d->format('Y-m'))->map(fn($g)=>['label'=>$g->first()->translatedFormat('F Y'),'count'=>$g->count()])->values();
  $roles=['PPL','PML','Supervisor'];
  $search=trim((string)$r->petugas);

  // Matriks centang per role: hanya butuh tanggal progress.
  $matrixAssignments=$kegiatan->penugasan()->with(['petugas','kecamatan','progress'=>fn($x)=>$x->select('id','penugasan_id','tanggal')])
   ->when($search!=='',fn($x)=>$x->whereHas('petugas',fn($y)=>$y->where('nama_lengkap','like','%'.$search.'%')))
   ->get()->sortBy(fn($a)=>mb_strtolower($a->petugas->nama_lengkap??''))->values();
  $matrix=[];
  foreach($roles as $role){
   $matrix[$role]=$matrixAssignments->where('role',$role)->values()->map(function($a){
    $a->done_dates=$a->progress->map(fn($p)=>$p->tanggal->toDateString())->flip();
    return $a;
   });
  }

  // Daftar detail progress (paginasi) seperti sebelumnya.
  $progressFilter=fn($x)=>$x->withCount('details')->orderByDesc('tanggal');
  if($r->filled('tanggal')){$progressFilter=fn($x)=>$x->whereDate('tanggal',$r->tanggal)->withCount('details')->orderByDesc('tanggal');}
  $q=$kegiatan->penugasan()->with(['petugas.user','kecamatan','progress'=>$progressFilter]);
  if($search!=='')$q->whereHas('petugas',fn($x)=>$x->where('nama_lengkap','like','%'.$search.'%'));
  if($r->filled('tanggal'))$q->whereHas('progress',fn($x)=>$x->whereDate('tanggal',$r->tanggal));
  if($r->filled('role')&&in_array($r->role,$roles,true))$q->where('role',$r->role);
  $assignments=$q->orderBy('id')->paginate(20)->withQueryString();
  $today=now()->toDateString();
  return view('admin.monitoring.index',compact('kegiatan','assignments','dates','months','matrix','roles','today'));
 }
 public function show(Request $r,Kegiatan $kegiatan,Penugasan $penugasan){
  abort_unless((int)$penugasan->kegiatan_id===(int)$kegiatan->id,404);
  $penugasan->load(['petugas.user','kecamatan']);
  $query=$penugasan->progress()->with(['details.dokumentasi','uraian.master','permasalahan.master'])->orderByDesc('tanggal');
  if($r->filled('tanggal'))$query->whereDate('tanggal',$r->tanggal);
  $progressList=$query->paginate(10)->withQueryString();
  $totalHari=$penugasan->progress()->count();
  $totalDetail=\App\Models\DetailProgress::whereHas('progress',fn($x)=>$x->where('penugasan_id',$penugasan->id))->count();
  return view('admin.monitoring.show',compact('kegiatan','penugasan','progressList','totalHari','totalDetail'));
 }
 public function updateDetail(Request $r,Kegiatan $kegiatan,DetailProgress $detail){
  abort_unless($detail->progress->penugasan->kegiatan_id===$kegiatan->id,404);
  $d=$r->validate(['desa'=>'required|string|max:150','sls'=>'required|string|max:150','jam_mulai'=>'required|date_format:H:i','jam_selesai'=>'required|date_format:H:i|after_or_equal:jam_mulai','pemecahan_masalah'=>'nullable|string|max:5000','jumlah_responden'=>'nullable|integer|min:0','jumlah_responden_diawasi'=>'nullable|integer|min:0','jumlah_dokumen_diperiksa'=>'nullable|integer|min:0']);
  $before=$detail->toArray();$detail->update($d);$assignment=$detail->progress->penugasan;$date=$detail->progress->tanggal->toDateString();
  foreach([Lpd::where('penugasan_id',$assignment->id)->whereDate('tanggal',$date)->first(),Kwitansi::where('penugasan_id',$assignment->id)->whereDate('tanggal',$date)->first()] as $doc){if($doc){if($doc->file_pdf)Storage::disk('local')->delete($doc->file_pdf);$doc->delete();}}
  ActivityLog::create(['user_id'=>$r->user()->id,'aksi'=>'admin_correct','entitas'=>'detail_progress','entitas_id'=>$detail->id,'data_sebelum'=>$before,'data_sesudah'=>$detail->fresh()->toArray(),'ip_address'=>$r->ip()]);
  return back()->with('status','Koreksi progress disimpan dan tercatat di audit log.');
 }
 public function preview(Kegiatan $kegiatan,$document){$d=Dokumentasi::whereHas('detail.progress.penugasan',fn($q)=>$q->where('kegiatan_id',$kegiatan->id))->findOrFail($document);abort_unless(Storage::disk('local')->exists($d->file_path),404);abort_unless(str_starts_with((string)$d->mime_type,'image/'),404);return Storage::disk('local')->response($d->file_path,$d->nama_file,['Content-Type'=>$d->mime_type,'Content-Disposition'=>'inline; filename="'.addslashes($d->nama_file).'"','Cache-Control'=>'private, max-age=3600']);}
 public function file(Kegiatan $kegiatan,$document){$d=Dokumentasi::whereHas('detail.progress.penugasan',fn($q)=>$q->where('kegiatan_id',$kegiatan->id))->findOrFail($document);abort_unless(Storage::disk('local')->exists($d->file_path),404);return Storage::disk('local')->download($d->file_path,$d->nama_file);}
}
