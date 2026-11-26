<?php
namespace App\Services;
use App\Models\{Penugasan,ProgressHarian,Lpd,Kwitansi};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class DocumentService {
 public const LPD_LAYOUT_VERSION = '20261125-v35';
 public function lpd(Penugasan $assignment,ProgressHarian $progress):Lpd {
  $assignment->loadMissing('petugas.user','kegiatan','kecamatan');
  $progress->loadMissing(
   'details.dokumentasi',
   'details.uraian.master',
   'details.permasalahan.master',
   'details.masterSls',
   'details.masterSubSls',
   'uraian.master',
   'permasalahan.master'
  );
  $lpdTimeline = app(LpdContentBuilder::class)->build($progress, $assignment);

  $doc=Lpd::firstOrNew(['penugasan_id'=>$assignment->id,'tanggal'=>$progress->tanggal->toDateString()]);
  $pdfBinary=app(LpdPdfRenderer::class)->render([['assignment'=>$assignment,'progress'=>$progress,'lpdTimeline'=>$lpdTimeline]]);
  $path='sipacak/lpd/'.$assignment->id.'/'.str_replace('-','',$progress->tanggal->toDateString()).'.pdf';
  Storage::disk('local')->put($path,$pdfBinary);
  $doc->fill(['status_lpd'=>'sudah_dibuat','file_pdf'=>$path,'generated_at'=>now(),'layout_version'=>self::LPD_LAYOUT_VERSION])->save();
  return $doc;
 }
 public function kwitansi(Penugasan $assignment,ProgressHarian $progress):Kwitansi {
  $assignment->loadMissing('petugas.user','kegiatan','kecamatan');$progress->loadMissing('details.desaKelurahan');abort_unless($assignment->kegiatan->nomor_kegiatan,422,'Akun POK belum diisi pada data kegiatan.');$admin=$assignment->kegiatan->administrasiKwitansi;
  abort_unless($admin && $admin->nama_bendahara_pengeluaran && $admin->nip_bendahara_pengeluaran && $admin->nama_pejabat_pembuat_komitmen && $admin->nip_pejabat_pembuat_komitmen,422,'Identitas nama dan NIP Bendahara Pengeluaran serta PPK belum lengkap.');
  $row=$admin->rows()->where('penugasan_id',$assignment->id)->whereDate('tanggal',$progress->tanggal)->where('status_matching','cocok')->first();abort_unless($row,422,'Data administrasi Excel belum cocok untuk petugas dan tanggal ini.');
  $data=$row->data_administrasi??[];$wilayah=app(KwitansiWilayahService::class);$data['daerah_tujuan_perjalanan_dinas']=$wilayah->destination($progress,$assignment);$data['uraian_tujuan_perjalanan']=$wilayah->journeyDescription($progress,$assignment);$pdfBinary=app(KwitansiPdfRenderer::class)->render([['assignment'=>$assignment,'progress'=>$progress,'admin'=>$admin,'data'=>$data]]);$path='sipacak/kwitansi/'.$assignment->id.'/'.str_replace('-','',$progress->tanggal->toDateString()).'.pdf';Storage::disk('local')->put($path,$pdfBinary);$doc=Kwitansi::firstOrNew(['penugasan_id'=>$assignment->id,'tanggal'=>$progress->tanggal->toDateString()]);$doc->fill(['status_kwitansi'=>'sudah_dibuat','file_pdf'=>$path,'generated_at'=>now()])->save();return $doc;
 }
}
