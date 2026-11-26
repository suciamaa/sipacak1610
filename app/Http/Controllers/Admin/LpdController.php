<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Kegiatan,Lpd,ActivityLog,MasterUraianLpd,MasterPermasalahanLpd,ProgressHarian,Dokumentasi};
use Illuminate\Support\Facades\Storage;
use App\Services\DocumentService;
use App\Support\DocumentFilename;
use Illuminate\Http\Request;
class LpdController extends Controller {
 public function index(Kegiatan $kegiatan, DocumentService $service){
  abort_unless(in_array($kegiatan->tujuan_akhir,['lpd','keduanya']),404);
  $rows = collect();
  $assignments = $kegiatan->penugasan()->with(['petugas.user','kecamatan','progress'=>fn($q)=>$q->with('details.dokumentasi')->orderByDesc('tanggal'),'lpd'])->get();
  foreach ($assignments as $assignment) {
   foreach ($assignment->progress as $progress) {
    $doc = $assignment->lpd->first(fn($item) => $item->tanggal->format('Y-m-d') === $progress->tanggal->toDateString());
    if (
     !$doc
     || $doc->status_lpd !== 'sudah_dibuat'
     || !$doc->file_pdf
     || !Storage::disk('local')->exists($doc->file_pdf)
     || $doc->layout_version !== DocumentService::LPD_LAYOUT_VERSION
    ) {
     try {
      $doc = $service->lpd($assignment, $progress);
     } catch (\Throwable $exception) {
      report($exception);
      $doc = null;
     }
    }
    $rows->push(['penugasan'=>$assignment,'progress'=>$progress,'doc'=>$doc]);
   }
  }
  return view('admin.lpd.index',compact('kegiatan','rows'));
 }
 public function showProgress(Kegiatan $kegiatan, ProgressHarian $progress)
 {
  abort_unless(in_array($kegiatan->tujuan_akhir, ['lpd', 'keduanya']), 404);
  $assignment = $kegiatan->penugasan()
   ->with(['petugas.user', 'kecamatan'])
   ->whereHas('progress', fn ($q) => $q->whereKey($progress->id))
   ->firstOrFail();
  $progress->load([
   'details.dokumentasi',
   'details.uraian.master',
   'details.permasalahan.master',
   'details.masterSls',
   'details.masterSubSls',
  ]);
  return view('admin.lpd.progress-view', compact('kegiatan', 'assignment', 'progress'));
 }

 public function progressPhoto(Kegiatan $kegiatan, ProgressHarian $progress, Dokumentasi $photo)
 {
  abort_unless(in_array($kegiatan->tujuan_akhir, ['lpd', 'keduanya']), 404);
  $belongs = $kegiatan->penugasan()->whereHas('progress', fn ($q) => $q->whereKey($progress->id))
   ->whereHas('progress.details.dokumentasi', fn ($q) => $q->whereKey($photo->id))->exists();
  abort_unless($belongs, 404);
  abort_unless($photo->file_path && Storage::disk('local')->exists($photo->file_path), 404);
  if (request()->boolean('preview')) {
   return response()->file(Storage::disk('local')->path($photo->file_path), [
    'Content-Type' => $photo->mime_type ?: 'application/octet-stream',
    'Content-Disposition' => 'inline; filename="' . basename($photo->nama_file) . '"',
   ]);
  }
  return Storage::disk('local')->download($photo->file_path, $photo->nama_file);
 }

 public function fields(Kegiatan $kegiatan){abort_unless(in_array($kegiatan->tujuan_akhir,['lpd','keduanya']),404);$uraian=MasterUraianLpd::where(fn($q)=>$q->whereNull('kegiatan_id')->orWhere('kegiatan_id',$kegiatan->id))->orderBy('tipe_uraian')->get();$masalah=MasterPermasalahanLpd::where(fn($q)=>$q->whereNull('kegiatan_id')->orWhere('kegiatan_id',$kegiatan->id))->get();return view('admin.lpd.fields',compact('kegiatan','uraian','masalah'));}
 public function saveFields(Request $r,Kegiatan $kegiatan){$d=$r->validate(['uraian'=>'nullable|array','uraian.*.id'=>'nullable|integer','uraian.*.nama'=>'required|string|max:180','uraian.*.tipe'=>'required|in:penguasa_wilayah,aktivitas','permasalahan'=>'nullable|array','permasalahan.*.id'=>'nullable|integer','permasalahan.*.nama'=>'required|string|max:180']);foreach($d['uraian']??[] as $x){$m=isset($x['id'])?MasterUraianLpd::where('kegiatan_id',$kegiatan->id)->find($x['id']):new MasterUraianLpd();$m->fill(['kegiatan_id'=>$kegiatan->id,'nama_uraian'=>$x['nama'],'tipe_uraian'=>$x['tipe'],'is_default'=>false,'is_active'=>true])->save();}foreach($d['permasalahan']??[] as $x){$m=isset($x['id'])?MasterPermasalahanLpd::where('kegiatan_id',$kegiatan->id)->find($x['id']):new MasterPermasalahanLpd();$m->fill(['kegiatan_id'=>$kegiatan->id,'nama_permasalahan'=>$x['nama'],'is_default'=>false,'is_active'=>true])->save();}return back()->with('status','Konfigurasi pilihan LPD disimpan.');}
 public function download(Kegiatan $kegiatan, Lpd $document, DocumentService $service)
 {
  abort_unless(in_array($kegiatan->tujuan_akhir, ['lpd', 'keduanya']), 404);
  $assignment = $kegiatan->penugasan()
   ->with('petugas.user', 'kegiatan', 'kecamatan')
   ->whereKey($document->penugasan_id)
   ->firstOrFail();

  if ($document->status_lpd !== 'sudah_dibuat'
      || $document->layout_version !== DocumentService::LPD_LAYOUT_VERSION
      || !$document->file_pdf
      || !Storage::disk('local')->exists($document->file_pdf)) {
   $progress = $assignment->progress()->whereDate('tanggal', $document->tanggal)
    ->with(['details.dokumentasi', 'details.uraian.master', 'details.permasalahan.master', 'details.masterSls', 'details.masterSubSls'])
    ->firstOrFail();
   $document = $service->lpd($assignment, $progress);
  }

  abort_unless($document->status_lpd === 'sudah_dibuat' && $document->file_pdf
   && Storage::disk('local')->exists($document->file_pdf), 404);

  return Storage::disk('local')->download($document->file_pdf, DocumentFilename::make('lpd', $kegiatan->nama_kegiatan, $assignment->petugas->nama_lengkap, $document->tanggal));
 }
 public function generate(Kegiatan $kegiatan,$progress,DocumentService $service){abort_unless(in_array($kegiatan->tujuan_akhir,['lpd','keduanya']),404);$p=$kegiatan->penugasan()->with('progress.details.dokumentasi')->findOrFail(request('penugasan_id'));$day=$p->progress->firstWhere('id',(int)$progress);abort_unless($day,404);$doc=$service->lpd($p,$day);$p->loadMissing('petugas', 'kegiatan');
  return response()->download(storage_path('app/private/'.$doc->file_pdf), DocumentFilename::make('lpd', $kegiatan->nama_kegiatan, $p->petugas->nama_lengkap, $doc->tanggal));}
}
