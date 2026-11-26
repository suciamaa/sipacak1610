<?php
require '../config/database.php'; require '../includes/functions.php'; require '../includes/kwitansi_wilayah.php'; require_admin();
$headers=['No','Nomor Surat Tugas','Nama Pelaksana Perjalanan Dinas','Tanggal Surat Tugas','Tanggal Tugas','Asal Penugasan','Daerah Tujuan Perjalanan Dinas','Lama Perjalanan','Berangkat','Pulang','Jumlah Nominal','Hal','Bulan Pengajuan Kwitansi'];
function xesc($s){return htmlspecialchars((string)$s,ENT_XML1|ENT_QUOTES,'UTF-8');}
function xcol($n){$s='';while($n>0){$n--; $s=chr(65+$n%26).$s;$n=intdiv($n,26);}return $s;}
function excel_title_case($value){
 $value=trim((string)$value);
 if($value==='')return '';
 if(function_exists('mb_convert_case')&&defined('MB_CASE_TITLE'))$value=mb_convert_case(mb_strtolower($value,'UTF-8'),MB_CASE_TITLE,'UTF-8');
 else $value=ucwords(strtolower($value));
 // Pertahankan angka Romawi sebagai huruf kapital, misalnya KANDIS II -> Kandis II.
 $roman='(?:xxx|xx(?:ix|iv|v?i{0,3})?|x(?:ix|iv|v?i{0,3})?|ix|iv|v?i{1,3}|v)';
 return preg_replace_callback('/(?<![\p{L}\p{N}])'.$roman.'(?![\p{L}\p{N}])/iu',static function($m){return strtoupper($m[0]);},$value);
}
function xlsx_make($rows,$filename){
 if(!class_exists('ZipArchive')){http_response_code(500);exit('Ekstensi PHP ZipArchive belum aktif.');}
 $tmp=tempnam(sys_get_temp_dir(),'kwx');$z=new ZipArchive();$z->open($tmp,ZipArchive::OVERWRITE);
 $z->addFromString('[Content_Types].xml','<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
 $z->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
 $z->addFromString('xl/workbook.xml','<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Kwitansi" sheetId="1" r:id="rId1"/></sheets></workbook>');
 $z->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
 $z->addFromString('xl/styles.xml','<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="dd/mm/yyyy"/></numFmts><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="1"><fill><patternFill patternType="none"/></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs></styleSheet>');
 $sheet='<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols><col min="13" max="13" width="20" customWidth="1"/></cols><sheetData>';
 foreach($rows as $ri=>$row){$rn=$ri+1;$sheet.="<row r=\"".$rn."\">";foreach(array_values($row) as $ci=>$v){$ref=xcol($ci+1).$rn;if($ci===12 && $rn>1){if(trim((string)$v)==="")$sheet.="<c r=\"$ref\" t=\"inlineStr\"><is><t></t></is></c>";else $sheet.="<c r=\"$ref\" t=\"inlineStr\"><is><t xml:space=\"preserve\">".xesc($v)."</t></is></c>";}elseif(is_int($v)||is_float($v))$sheet.="<c r=\"$ref\"><v>".$v."</v></c>";else $sheet.="<c r=\"$ref\" t=\"inlineStr\"><is><t xml:space=\"preserve\">".xesc($v)."</t></is></c>";} $sheet.="</row>";}
 $sheet.='</sheetData></worksheet>';$z->addFromString('xl/worksheets/sheet1.xml',$sheet);$z->close();
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="'.$filename.'"');header('Content-Length: '.filesize($tmp));readfile($tmp);unlink($tmp);exit;
}
function normalize_excel_date($v){
 $v=trim((string)$v); if($v==='')return '';
 if(preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$v)){ $d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);return $d&&$d->format('Y-m-d')===$v?$v:''; }
 if(is_numeric($v) && (float)$v>20000 && (float)$v<80000){$dt=(new DateTimeImmutable('1899-12-30'))->modify('+'.(int)$v.' days');return $dt->format('Y-m-d');}
 foreach(['!d/m/Y','!d-m-Y','!Y/m/d','!m/d/Y'] as $fmt){$dt=DateTimeImmutable::createFromFormat($fmt,$v);$e=DateTimeImmutable::getLastErrors();if($dt && ($e===false||($e['warning_count']===0&&$e['error_count']===0)) && $dt->format(substr($fmt,1))===$v)return $dt->format('Y-m-d');}
 return '';
}
function read_xlsx($path){
 if(!class_exists('ZipArchive'))throw new RuntimeException('Ekstensi ZipArchive belum aktif.');
 $z=new ZipArchive();if($z->open($path)!==true)throw new RuntimeException('File bukan workbook Excel yang dapat dibaca.');
 $shared=[];$sx=$z->getFromName('xl/sharedStrings.xml');if($sx){$xml=@simplexml_load_string($sx);if($xml)foreach($xml->si as $si){$t='';foreach($si->xpath('.//*[local-name()="t"]') as $part)$t.=(string)$part;$shared[]=$t;}}
 $sheet=$z->getFromName('xl/worksheets/sheet1.xml');$z->close();if(!$sheet)throw new RuntimeException('Sheet1 tidak ditemukan.');
 $xml=@simplexml_load_string($sheet);if(!$xml)throw new RuntimeException('Struktur worksheet rusak.');$out=[];
 foreach($xml->sheetData->row as $row){$line=[];foreach($row->c as $c){$ref=(string)$c['r'];preg_match('/^[A-Z]+/',$ref,$m);$letters=$m[0]??'A';$idx=0;foreach(str_split($letters) as $ch)$idx=$idx*26+ord($ch)-64;$idx--; $v=(string)$c->v;if((string)$c['t']==='s')$v=$shared[(int)$v]??'';elseif((string)$c['t']==='inlineStr')$v=implode('',array_map('strval',$c->is->t));$line[$idx]=$v;}if($line){$max=max(array_keys($line));$row=array_replace(array_fill(0,$max+1,''),$line); while($row && trim((string)end($row))===''){array_pop($row);} $out[]=$row;}}
 return $out;
}
function excel_money_value($value): ?int {
 $raw=trim((string)$value); if($raw==='')return null;
 // Accept Excel numeric values, plain integers, and conventional Indonesian thousands separators.
 if(preg_match('/^\\d+(?:\\.\\d+)?$/',$raw)){
   if(strpos($raw,'.')!==false){$parts=explode('.',$raw); if(strlen(end($parts))===3)$raw=implode('',$parts); else { if((float)$raw!=(int)$raw)return null; $raw=(string)(int)$raw; }}
 } elseif(preg_match('/^\\d{1,3}(?:\\.\\d{3})+$/',$raw)) { $raw=str_replace('.','',$raw); }
 elseif(preg_match('/^\\d{1,3}(?:,\\d{3})+$/',$raw)) { $raw=str_replace(',','',$raw); }
 elseif(preg_match('/^\\d{1,3}(?:\\.\\d{3})*,\\d{1,2}$/',$raw)) { return null; }
 else return null;
 if(!ctype_digit($raw))return null;
 $n=(int)$raw; return (string)$n===$raw ? $n : null;
}
if(isset($_GET['download'])){
 $activity=id_param($_GET['kegiatan_id']??null);if(!$activity)exit('Kegiatan tidak valid.');
 $q=$pdo->prepare('SELECT id,nama_kegiatan,tanggal_mulai,tanggal_selesai FROM kegiatan WHERE id=? AND aktif=1');$q->execute([$activity]);$a=$q->fetch();if(!$a)exit('Kegiatan tidak ditemukan.');
 $q=$pdo->prepare('SELECT p.id,p.nama_petugas,k.nama_kecamatan,ph.id progress_id,ph.tanggal FROM kegiatan a JOIN progress_harian ph ON ph.tanggal BETWEEN a.tanggal_mulai AND a.tanggal_selesai JOIN petugas p ON p.id=ph.petugas_id AND p.kecamatan_id=ph.kecamatan_id JOIN kecamatan k ON k.id=p.kecamatan_id WHERE a.id=? AND EXISTS(SELECT 1 FROM progress_sls ps WHERE ps.progress_id=ph.id AND TRIM(ps.nama_desa)<>"") ORDER BY ph.tanggal,p.nama_petugas');$q->execute([$activity]);$records=$q->fetchAll();
 $rows=[$headers];$n=0;foreach($records as $r){
   // Read only villages actually recorded for this petugas/date, preserving assignment order.
   $q2=$pdo->prepare('SELECT TRIM(nama_desa) AS nama_desa FROM progress_sls WHERE progress_id=? AND TRIM(nama_desa)<>"" ORDER BY id');$q2->execute([$r['progress_id']]);$rawDesa=$q2->fetchAll(PDO::FETCH_COLUMN);
   $desa=[];$seen=[];foreach($rawDesa as $namaDesa){$namaDesa=trim((string)$namaDesa);if($namaDesa==='')continue;$key=function_exists('mb_strtolower')?mb_strtolower($namaDesa,'UTF-8'):strtolower($namaDesa);if(isset($seen[$key]))continue;$seen[$key]=true;$desa[]=$namaDesa;}
   if(!$desa)continue;
   // Jenis wilayah ditentukan dari master data berdasarkan kecamatan + nama wilayah.
   $dest=kw_region_destination($desa,(string)$r['nama_kecamatan']);
   if($dest===null){ $unknown=[]; foreach($desa as $rawName){ if(kw_region_label((string)$r['nama_kecamatan'],(string)$rawName)===null)$unknown[]='['.$rawName.']'; } http_response_code(422); exit('Nama wilayah pada Progress Harian tidak ditemukan dalam master Desa/Kelurahan. Petugas: '.htmlspecialchars((string)$r['nama_petugas'],ENT_QUOTES,'UTF-8').'; tanggal: '.htmlspecialchars((string)$r['tanggal'],ENT_QUOTES,'UTF-8').'; kecamatan: '.htmlspecialchars(excel_title_case($r['nama_kecamatan']),ENT_QUOTES,'UTF-8').'; nama yang tidak cocok: '.htmlspecialchars(implode(', ',$unknown),ENT_QUOTES,'UTF-8').'. Periksa nilai nama_desa pada Progress Harian.'); }
   $rows[]=[++$n,'',$r['nama_petugas'],'', $r['tanggal'],'Kantor BPS Kabupaten Ogan Ilir',$dest,'','','','','',''];
 }
 xlsx_make($rows,'Template_Kwitansi_Revisit_SE2026.xlsx');
}
$messages=[];$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf();
 if(empty($_FILES['excel'])||$_FILES['excel']['error']!==UPLOAD_ERR_OK)$errors[]='Pilih file Excel .xlsx yang valid.';
 elseif($_FILES['excel']['size']>5*1024*1024)$errors[]='Ukuran file maksimal 5 MB.';
 else try{
 $data=read_xlsx($_FILES['excel']['tmp_name']);if(!$data)throw new RuntimeException('Workbook kosong.');
 $actual=array_map(fn($v)=>trim((string)$v),$data[0]);
 $legacyHeaders=['No','Nomor Surat Tugas','Nama Pelaksana Perjalanan Dinas','Tanggal Surat Tugas','Tanggal Tugas','Asal Penugasan','Daerah Tujuan Perjalanan Dinas','Lama Perjalanan','Berangkat','Pulang','Jumlah Nominal','Terbilang','Hal'];
 $legacyPengajuanColumn=in_array('Tanggal Pengajuan',$actual,true) && !in_array('Bulan Pengajuan Kwitansi',$actual,true);
 $allowed=array_flip(array_merge($headers,$legacyHeaders,['Tanggal Pengajuan']));
 foreach($actual as $h){if(!isset($allowed[$h]))throw new RuntimeException('Header Excel tidak dikenal: '.$h);}
 foreach(['No','Nama Pelaksana Perjalanan Dinas','Tanggal Tugas','Jumlah Nominal','Hal'] as $requiredHeader){if(!in_array($requiredHeader,$actual,true))throw new RuntimeException('Kolom wajib tidak ditemukan: '.$requiredHeader);}
 $canonical=[];foreach(array_slice($data,1) as $oldRow){$oldRow=array_pad($oldRow,count($actual),'');$map=[];foreach($actual as $ci=>$h){if($h==='Terbilang')continue;$canonicalHeader=($h==='Tanggal Pengajuan'?'Bulan Pengajuan Kwitansi':$h);$map[$canonicalHeader]=$oldRow[$ci]??'';} $canonical[]=array_map(fn($h)=>$map[$h]??'', $headers);}
 $data=array_merge([$headers],$canonical);
 $processed=0;$failed=0;$skipped=0;$details=[];$pdo->beginTransaction();
 $activityId=id_param($_POST['kegiatan_id']??null);
 if(!$activityId) throw new RuntimeException('Kegiatan wajib dipilih.');
 $qa=$pdo->prepare('SELECT id,tanggal_mulai,tanggal_selesai FROM kegiatan WHERE id=? AND aktif=1');$qa->execute([$activityId]);$activity=$qa->fetch();
 if(!$activity) throw new RuntimeException('Kegiatan tidak ditemukan atau tidak aktif.');
 $requiredMeta=['tanggal_surat_tugas','lama_perjalanan','berangkat','pulang','asal_penugasan','daerah_tujuan','tanggal_pengajuan','bulan_pengajuan_kwitansi'];$qc=$pdo->query('SHOW COLUMNS FROM kwitansi');$existingCols=array_column($qc->fetchAll(), 'Field');$missingMeta=array_values(array_diff($requiredMeta,$existingCols));if($missingMeta)throw new RuntimeException('Struktur database kwitansi belum diperbarui. Jalankan migrasi database kwitansi yang tersedia (termasuk database/migration_kwitansi_bulan_pengajuan.sql), lalu unggah ulang Excel.');
 foreach(array_slice($data,1) as $idx=>$r){$excelRow=$idx+2;$r=array_pad(array_slice($r,0,count($headers)),count($headers),'');if(count(array_filter($r,fn($v)=>trim((string)$v)!==''))===0){$skipped++;continue;}
 $name=trim((string)$r[2]);$date=normalize_excel_date($r[4]);$pengajuanRaw=trim((string)($r[12]??''));$pengajuanLegacyDate=$legacyPengajuanColumn?normalize_excel_date($pengajuanRaw):'';$bulanPengajuan=$legacyPengajuanColumn?'':$pengajuanRaw;$nom=excel_money_value($r[10]);$letter=trim((string)$r[1]);$purpose=trim((string)$r[11]);$errorsRow=[];
 if($name==='')$errorsRow[]='Nama petugas kosong';
 if($legacyPengajuanColumn && $pengajuanLegacyDate==='')$errorsRow[]='Tanggal Pengajuan pada template lama harus berupa tanggal valid (contoh 14/10/2026)'; elseif(!$legacyPengajuanColumn && $bulanPengajuan==='')$errorsRow[]='Bulan Pengajuan Kwitansi wajib diisi (contoh Oktober 2026)';
 if($nom===null)$errorsRow[]='Jumlah Nominal wajib diisi angka Rupiah yang valid';
 if(excel_money_value($r[8])===null)$errorsRow[]='Berangkat wajib diisi angka sebelum PDF tiga halaman dibuat';
 if($purpose==='')$errorsRow[]='Hal wajib diisi';
 if($errorsRow){$failed++;$details[]="Baris $excelRow: ".implode(', ',$errorsRow);continue;}
 $q=$pdo->prepare('SELECT p.id,p.kecamatan_id FROM petugas p WHERE TRIM(p.nama_petugas)=TRIM(?)');$q->execute([$name]);$matches=$q->fetchAll();
 if(count($matches)!==1){$failed++;$details[]="Baris $excelRow: nama petugas tidak ditemukan atau tidak unik; baris ini tidak diproses agar kwitansi tidak salah petugas";continue;}
 $person=$matches[0];
 // Baris Excel dengan Tanggal Tugas kosong adalah format lama per petugas. Perluas ke setiap tanggal progress valid pada kegiatan ini.
 if($date===''){
   $qp=$pdo->prepare('SELECT ph.tanggal FROM progress_harian ph WHERE ph.petugas_id=? AND ph.kecamatan_id=? AND ph.tanggal BETWEEN ? AND ? AND EXISTS(SELECT 1 FROM progress_sls ps WHERE ps.progress_id=ph.id AND TRIM(ps.nama_desa)<>"") ORDER BY ph.tanggal');
   $qp->execute([$person['id'],$person['kecamatan_id'],$activity['tanggal_mulai'],$activity['tanggal_selesai']]);$dates=$qp->fetchAll(PDO::FETCH_COLUMN);
   if(!$dates){$failed++;$details[]="Baris $excelRow: Tanggal Tugas kosong dan tidak ditemukan progress dengan detail desa pada periode kegiatan";continue;}
 } else {
   if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||$date<$activity['tanggal_mulai']||$date>$activity['tanggal_selesai']){$failed++;$details[]="Baris $excelRow: Tanggal Tugas tidak valid atau di luar periode kegiatan";continue;}
   $dates=[$date];
 }
 foreach($dates as $taskDate){
   $q=$pdo->prepare('SELECT ph.id FROM progress_harian ph WHERE ph.petugas_id=? AND ph.kecamatan_id=? AND ph.tanggal=? AND EXISTS(SELECT 1 FROM progress_sls ps WHERE ps.progress_id=ph.id AND TRIM(ps.nama_desa)<>"")');$q->execute([$person['id'],$person['kecamatan_id'],$taskDate]);
   if(!$q->fetch()){$failed++;$details[]="Baris $excelRow: progress/detail desa tidak ditemukan untuk tanggal $taskDate";continue;}
   $excelLetterDate=normalize_excel_date($r[3]);$excelLetterDate=preg_match('/^\d{4}-\d{2}-\d{2}$/',$excelLetterDate)?$excelLetterDate:null;
   $berangkat=excel_money_value($r[8]);$pulang=excel_money_value($r[9]);
   $duration=trim((string)$r[7]);$terbilang=null;$asal=trim((string)$r[5]);$destination='';
   $q=$pdo->prepare('SELECT p.kecamatan_id,k.nama_kecamatan FROM petugas p JOIN kecamatan k ON k.id=p.kecamatan_id WHERE p.id=?');$q->execute([$person['id']]);$personRegion=$q->fetch();
   $currentRegion=kw_region_current_progress($pdo,(int)$person['id'],(int)$personRegion['kecamatan_id'],$taskDate,(string)$personRegion['nama_kecamatan'],(int)$activityId);
   if($currentRegion['error']!==null){$failed++;$details[]="Baris $excelRow: ".$currentRegion['error'];continue;}
   $destination=$currentRegion['destination'];
   $q=$pdo->prepare("INSERT INTO kwitansi(kegiatan_id,petugas_id,tanggal,nominal,keperluan,nomor_surat_tugas,tanggal_surat_tugas,lama_perjalanan,berangkat,pulang,terbilang,asal_penugasan,daerah_tujuan,tanggal_pengajuan,bulan_pengajuan_kwitansi,status,dibuat_oleh) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'dibuat',?) ON DUPLICATE KEY UPDATE nominal=VALUES(nominal),keperluan=IF(VALUES(keperluan) IS NULL OR VALUES(keperluan)='',keperluan,VALUES(keperluan)),nomor_surat_tugas=IF(VALUES(nomor_surat_tugas) IS NULL OR VALUES(nomor_surat_tugas)='',nomor_surat_tugas,VALUES(nomor_surat_tugas)),tanggal_surat_tugas=IF(VALUES(tanggal_surat_tugas) IS NULL,tanggal_surat_tugas,VALUES(tanggal_surat_tugas)),lama_perjalanan=IF(VALUES(lama_perjalanan) IS NULL OR VALUES(lama_perjalanan)='',lama_perjalanan,VALUES(lama_perjalanan)),berangkat=IF(VALUES(berangkat) IS NULL,berangkat,VALUES(berangkat)),pulang=IF(VALUES(pulang) IS NULL,pulang,VALUES(pulang)),terbilang=IF(VALUES(terbilang) IS NULL,terbilang,VALUES(terbilang)),asal_penugasan=IF(VALUES(asal_penugasan) IS NULL OR VALUES(asal_penugasan)='',asal_penugasan,VALUES(asal_penugasan)),daerah_tujuan=VALUES(daerah_tujuan),tanggal_pengajuan=IF(VALUES(tanggal_pengajuan) IS NULL,tanggal_pengajuan,VALUES(tanggal_pengajuan)),bulan_pengajuan_kwitansi=IF(VALUES(bulan_pengajuan_kwitansi) IS NULL OR VALUES(bulan_pengajuan_kwitansi)='',bulan_pengajuan_kwitansi,VALUES(bulan_pengajuan_kwitansi)),dibuat_oleh=COALESCE(VALUES(dibuat_oleh),dibuat_oleh),updated_at=CURRENT_TIMESTAMP");
   $q->execute([$activityId,$person['id'],$taskDate,$nom,$purpose,$letter,$excelLetterDate,$duration,$berangkat,$pulang,null,($asal!==''?$asal:null),($destination!==''?$destination:null),($pengajuanLegacyDate!==''?$pengajuanLegacyDate:null),($bulanPengajuan!==''?$bulanPengajuan:null),(int)(current_user()['id']??0)?:null]);$processed++;
 }
 }
 $pdo->commit();$messages[]="Kwitansi dibuat/diperbarui: $processed; gagal: $failed; dilewati: $skipped. Jika Tanggal Tugas kosong, baris dipetakan ke seluruh tanggal progress valid petugas pada periode kegiatan. Progress harian tidak diubah.";$_SESSION['kwitansi_import_errors']=$details;
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$errors[]=$e->getMessage();}
}
$pageTitle='Template dan Upload Excel Kwitansi';require '../includes/header.php';
?>
<section class="container section kwitansi-excel-admin"><div class="section-head kwitansi-excel-page-head"><div class="kwitansi-excel-heading-copy"><span class="eyebrow">ADMINISTRASI KWITANSI</span><h1>Kelola Excel Kwitansi</h1><p>Unduh template, lengkapi data administrasi, lalu unggah kembali untuk membuat atau memperbarui data kwitansi.</p></div><a class="btn kwitansi-back-button" href="kwitansi.php"><span aria-hidden="true">←</span> Kembali ke Kwitansi</a></div>
<?php foreach($messages as $m):?><div class="alert alert-success"><?=e($m)?></div><?php endforeach;?><?php foreach($errors as $m):?><div class="alert alert-error"><?=e($m)?></div><?php endforeach;?>
<div class="card kwitansi-excel-panel kwitansi-download-panel"><div class="kwitansi-step-title"><span class="kwitansi-step-number">1</span><div><h2>Download Template Excel</h2><p>Unduh template untuk mengisi data administrasi kwitansi sesuai format yang disediakan.</p></div></div><form method="get"><input type="hidden" name="download" value="1"><label>Kegiatan<select name="kegiatan_id" required><?php foreach($pdo->query('SELECT id,nama_kegiatan FROM kegiatan WHERE aktif=1 ORDER BY nama_kegiatan')->fetchAll() as $a):?><option value="<?=$a['id']?>"><?=e($a['nama_kegiatan'])?></option><?php endforeach;?></select></label><p><button class="btn btn-primary">Download Template Excel</button></p></form></div>
<div class="card kwitansi-excel-panel kwitansi-upload-panel"><div class="kwitansi-step-title"><span class="kwitansi-step-number">2</span><div><h2>Upload Excel yang telah dilengkapi</h2><p>Kolom dipetakan berdasarkan nama header. Bulan Pengajuan Kwitansi wajib diisi (contoh Oktober 2026). Template lama dengan header Tanggal Pengajuan tetap didukung untuk kompatibilitas. Baris valid diproses; baris tidak valid dilaporkan terpisah.</p></div></div><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?=e(csrf())?>"><label>Kegiatan<select name="kegiatan_id" required><?php foreach($pdo->query('SELECT id,nama_kegiatan FROM kegiatan WHERE aktif=1 ORDER BY nama_kegiatan')->fetchAll() as $a):?><option value="<?=$a['id']?>"><?=e($a['nama_kegiatan'])?></option><?php endforeach;?></select></label><p><input type="file" name="excel" accept=".xlsx" required></p><button class="btn btn-primary">Upload dan Validasi</button></form></div>
<?php $detail=$_SESSION['kwitansi_import_errors']??[];unset($_SESSION['kwitansi_import_errors']);if($detail):?><div class="card kwitansi-excel-panel kwitansi-error-panel"><h2>Detail baris gagal</h2><ul><?php foreach($detail as $d):?><li><?=e($d)?></li><?php endforeach;?></ul></div><?php endif;?></section><?php require '../includes/footer.php';?>
