<?php
require __DIR__.'/config/database.php';
require __DIR__.'/includes/functions.php';
require __DIR__.'/includes/kwitansi_wilayah.php';
require_login();
$id = id_param($_GET['id'] ?? null);
if (!$id) { http_response_code(400); exit('ID kwitansi tidak valid.'); }
$sql = 'SELECT kw.*, a.nama_kegiatan, a.tanggal_mulai, a.tanggal_selesai,
               p.id AS petugas_id, p.nama_petugas, p.kecamatan_id, k.nama_kecamatan,
               ph.id AS progress_id
        FROM kwitansi kw
        JOIN kegiatan a ON a.id=kw.kegiatan_id
        JOIN petugas p ON p.id=kw.petugas_id
        JOIN kecamatan k ON k.id=p.kecamatan_id
        JOIN progress_harian ph ON ph.petugas_id=p.id AND ph.kecamatan_id=p.kecamatan_id AND ph.tanggal=kw.tanggal
        WHERE kw.id=? AND kw.status="dibuat" AND kw.tanggal BETWEEN a.tanggal_mulai AND a.tanggal_selesai';
$q=$pdo->prepare($sql); $q->execute([$id]); $r=$q->fetch();
if (!$r) { http_response_code(404); exit('Kwitansi tidak ditemukan, tidak aktif, atau Progress Harian pada tanggal tersebut tidak tersedia.'); }
if (!is_admin() && (int)$r['kecamatan_id'] !== (int)current_kecamatan_id()) { http_response_code(403); exit('Anda tidak memiliki akses ke kwitansi petugas di luar kecamatan akun ini.'); }
$currentRegion=kw_region_current_progress($pdo,(int)$r['petugas_id'],(int)$r['kecamatan_id'],(string)$r['tanggal'],(string)$r['nama_kecamatan'],(int)$r['kegiatan_id']);
if ($currentRegion['error'] !== null) { http_response_code(422); exit(htmlspecialchars($currentRegion['error'], ENT_QUOTES, 'UTF-8')); }
$desa=$currentRegion['villages'];
function kw_natural(array $items): string { $items=array_values($items); $n=count($items); if(!$n)return ''; if($n===1)return $items[0]; if($n===2)return $items[0].' dan '.$items[1]; $last=array_pop($items); return implode(', ',$items).', dan '.$last; }
function kw_rp(int $n): string { return 'Rp '.number_format($n,0,',','.'); }
function kw_words(int $n): string { $ones=['','Satu','Dua','Tiga','Empat','Lima','Enam','Tujuh','Delapan','Sembilan','Sepuluh','Sebelas']; if($n===0)return 'Nol'; if($n<0)return 'Minus '.kw_words(abs($n)); if($n<12)return $ones[$n]; if($n<20)return kw_words($n-10).' Belas'; if($n<100)return kw_words(intdiv($n,10)).' Puluh'.($n%10?' '.kw_words($n%10):''); if($n<200)return 'Seratus'.($n>100?' '.kw_words($n-100):''); if($n<1000)return kw_words(intdiv($n,100)).' Ratus'.($n%100?' '.kw_words($n%100):''); if($n<2000)return 'Seribu'.($n>1000?' '.kw_words($n-1000):''); foreach([1000000000=>'Miliar',1000000=>'Juta',1000=>'Ribu'] as $unit=>$label){if($n>=$unit){$q=intdiv($n,$unit);$rem=$n%$unit;return kw_words($q).' '.$label.($rem?' '.kw_words($rem):'');}} return (string)$n; }
function kw_pdfstr(string $s): string { $s=iconv('UTF-8','Windows-1252//TRANSLIT//IGNORE',$s)?:$s; return str_replace(['\\','(',')',"\r","
"],['\\\\','\\(','\\)',' ',' '],$s); }
function kw_txt(string $s,float $x,float $y,int $size=9,bool $bold=false): string { $f=$bold?'F2':'F1'; return "BT /$f $size Tf 1 0 0 1 $x $y Tm (".kw_pdfstr($s).") Tj ET
"; }
function kw_txt_justify(string $s,float $x,float $y,float $targetWidth,int $size=9,bool $bold=false): string { $spaces=substr_count(trim($s),' '); if($spaces<1)return kw_txt($s,$x,$y,$size,$bold); $wordSpace=max(0,($targetWidth-kw_text_width($s,$size))/$spaces); $f=$bold?'F2':'F1'; return "BT /$f $size Tf $wordSpace Tw 1 0 0 1 $x $y Tm (".kw_pdfstr($s).") Tj 0 Tw ET
"; }
function kw_text_width(string $s,float $size=9): float {
    // Match the same Times-family width estimate used by the existing centered labels.
    $width=0.0; $text=iconv('UTF-8','Windows-1252//TRANSLIT//IGNORE',$s)?:$s;
    $narrow=str_split(' il.,:;!'); $wide=str_split('MW@%&');
    foreach(str_split($text) as $ch){ if(in_array($ch,$narrow,true)) $width += 0.24*$size; elseif(in_array($ch,$wide,true)) $width += 0.78*$size; elseif(strtoupper($ch)===$ch && preg_match('/[A-Z]/',$ch)) $width += 0.62*$size; else $width += 0.48*$size; }
    return $width;
}
function kw_txt_underlined(string $s,float $x,float $y,int $size=9,bool $bold=false): string {
    // PDF has no native text-decoration property: draw a short underline measured to this text only.
    $width=kw_text_width($s,$size);
    return kw_txt($s,$x,$y,$size,$bold).kw_line($x,$y-1.6,$x+$width,$y-1.6,0.45);
}
function kw_txt_center(string $s,float $centerX,float $y,int $size=9,bool $bold=false): string {
    return kw_txt($s,$centerX-(kw_text_width($s,$size)/2),$y,$size,$bold);
}
function kw_txt_center_underlined(string $s,float $centerX,float $y,int $size=9,bool $bold=false): string {
    return kw_txt_underlined($s,$centerX-(kw_text_width($s,$size)/2),$y,$size,$bold);
}
function kw_line(float $x1,float $y1,float $x2,float $y2,float $w=.5): string { return "$w w $x1 $y1 m $x2 $y2 l S
"; }
function kw_wrap(string $text,int $max=85): array { $words=preg_split('/\s+/u',trim($text))?:[]; $lines=[];$line=''; foreach($words as $word){$next=$line===''?$word:$line.' '.$word; $len=strlen(iconv('UTF-8','Windows-1252//TRANSLIT//IGNORE',$next)?:$next); if($len>$max && $line!==''){$lines[]=$line;$line=$word;}else{$line=$next;}} if($line!=='')$lines[]=$line;return $lines?:['']; }
function kw_wrap_width(string $text,float $maxWidth,int $size=9): array { $words=preg_split('/\s+/u',trim($text))?:[]; $lines=[];$line=''; foreach($words as $word){$next=$line===''?$word:$line.' '.$word; if($line!=='' && kw_text_width($next,$size)>$maxWidth){$lines[]=$line;$line=$word;}else{$line=$next;}} if($line!=='')$lines[]=$line;return $lines?:['']; }
function kw_paragraph(string $text,float $x,float $y,int $max=85,int $size=9,float $leading=12,bool $bold=false): string { $out=''; foreach(kw_wrap($text,$max) as $line){$out.=kw_txt($line,$x,$y,$size,$bold);$y-=$leading;} return $out; }
$amount=(int)$r['nominal']; $terbilang=trim(kw_words($amount).' rupiah'); if(function_exists('mb_convert_case')){$terbilang=mb_convert_case(mb_strtolower($terbilang,'UTF-8'), MB_CASE_TITLE, 'UTF-8');}else{$terbilang=ucwords(strtolower($terbilang));} $date=date_long($r['tanggal']); $asal=trim((string)($r['asal_penugasan']??'')); if($asal==='')$asal='Ibukota kecamatan '.$r['nama_kecamatan']; $tujuan=$currentRegion['destination']; if($tujuan===null){http_response_code(422);exit('Tujuan perjalanan belum dapat ditentukan dari Progress Harian terbaru.');} $journey=$asal.' ke '.$tujuan.' (PP)'; $berangkat=$r['berangkat']??null; if($berangkat===null || $berangkat===''){http_response_code(422); exit('Nominal Berangkat belum tersimpan untuk kwitansi ini. Jalankan migrasi database kwitansi, lalu unggah ulang Excel dengan kolom Berangkat terisi.');} $detail='Transport (PP) '.$asal.' - '.$tujuan.' (2 x Rp '.number_format((int)$berangkat,0,',','.').')';
$W=595.28;$H=841.89;$objects=[];$add=function(string $s)use(&$objects){$objects[]=$s;return count($objects);};
$f1=$add('<< /Type /Font /Subtype /Type1 /BaseFont /Times-Roman /Encoding /WinAnsiEncoding >>'); $f2=$add('<< /Type /Font /Subtype /Type1 /BaseFont /Times-Bold /Encoding /WinAnsiEncoding >>'); $pages=[];
$logoPath=__DIR__.'/templates/logo_bps_kwitansi.jpg'; $logoData=is_file($logoPath)?file_get_contents($logoPath):false; $logoObj=null; if($logoData!==false){$logoObj=$add('<< /Type /XObject /Subtype /Image /Width 201 /Height 156 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($logoData)." >>
stream
".$logoData."
endstream");}
// PAGE 1 — KWITANSI; structure and signature columns follow the supplied Word reference.
$c=''; if($logoObj!==null)$c.='q 48 0 0 37 49 763 cm /Im1 Do Q'."
"; $c.=kw_txt_center('KWITANSI',297,752,9,true);
$letterDate=trim((string)($r['tanggal_surat_tugas']??'')); $letterDateText='Nomor: '.$r['nomor_surat_tugas'].($letterDate!==''?' Tanggal: '.date_long($letterDate):''); $paymentText=trim((string)$r['keperluan']).' Program Penyediaan dan Pelayanan Informasi Statistik Anggaran Badan Pusat Statistik Kabupaten Ogan Ilir Tahun Anggaran 2026 selama 1 Hari 054.01.GG.2902.FAN.ZZ1.051.C.524113 (Rincian terlampir)'; $labels=[['Sudah Terima dari', 'Pejabat Pembuat Komitmen BPS Kabupaten Ogan Ilir'],['Uang sebanyak',kw_rp($amount)],['Untuk pembayaran',$paymentText],['Berdasarkan Surat Tugas',$letterDateText],['Untuk perjalanan dinas dari',$journey],['Terbilang',$terbilang]];
$y=728;
foreach($labels as $i=>$item){$c.=kw_txt($item[0],66,$y,9);$c.=kw_txt(':',198,$y,9);$lines=($i===4)?kw_wrap_width($item[1],310,9):kw_wrap($item[1],78);foreach($lines as $lineIndex=>$line){if($i===4 && $lineIndex<count($lines)-1){$c.=kw_txt_justify($line,208,$y,337,9,false);}else{$c.=kw_txt($line,208,$y,9,false);} $y-=12;} $y-=($i===2?8:($i===4?10:5));}
$signatureTop=min(505,$y-24);
$c.=kw_txt('Lunas Pada tanggal:',66,$signatureTop,9);$c.=kw_txt('Bendahara Pengeluaran',66,$signatureTop-12,9);$c.=kw_txt('Badan Pusat Statistik',66,$signatureTop-24,9);$c.=kw_txt('Kabupaten Ogan Ilir',66,$signatureTop-36,9);
$c.=kw_txt('Setuju dibayar:',240,$signatureTop,9);$c.=kw_txt('Pejabat Pembuat Komitmen',240,$signatureTop-12,9);$c.=kw_txt('Badan Pusat Statistik Kabupaten',240,$signatureTop-24,9);$c.=kw_txt('Ogan Ilir',240,$signatureTop-36,9);
$bulanPengajuan=trim((string)($r['bulan_pengajuan_kwitansi']??''));
// Data lama yang masih memakai tanggal_pengajuan tetap ditampilkan dengan format lama.
$pengajuanDate=trim((string)($r['tanggal_pengajuan']??''));
$pengajuanText=$bulanPengajuan!==''?$bulanPengajuan:($pengajuanDate!==''?date_long($pengajuanDate):'');
// Enam ketukan kosong disediakan untuk penulisan tanggal secara manual. Tidak ada tanggal otomatis.
$c.=kw_txt('Indralaya,      '.$pengajuanText,408,$signatureTop,9);$c.=kw_txt('Yang Menerima',408,$signatureTop-15,9);
$sigNameY=$signatureTop-90; $c.=kw_txt_underlined('Pusvitasari, S.Sos., MP',66,$sigNameY,9);$c.=kw_txt('NIP. 198307172007012008',66,$sigNameY-12,9);
$c.=kw_txt_underlined('Yolanda Rizkie Aprilia, S.Tr.Stat',240,$sigNameY,9);$c.=kw_txt('NIP. 199904172022012006',240,$sigNameY-12,9);
$c.=kw_txt_underlined($r['nama_petugas'],408,$sigNameY,9,false);$c.=kw_txt('NIP. -',408,$sigNameY-12,9);
$content=$add('<< /Length '.strlen($c)." >>
stream
".$c.'endstream');$page=$add('<< /Type /Page /Parent PAGESREF 0 R /MediaBox [0 0 '.$W.' '.$H.'] /Resources << /Font << /F1 '.$f1.' 0 R /F2 '.$f2.' 0 R >>'.($logoObj!==null?' /XObject << /Im1 '.$logoObj.' 0 R >>':'').' >> /Contents '.$content.' 0 R >>');$pages[]=$page;
// PAGE 2 — layout aligned to the supplied three-page reference PDF.
$c='';
$c.=kw_txt_center('PERINCIAN PERHITUNGAN BIAYA PERJALANAN DINAS',297,780,9,true);
$c.=kw_txt('Lampiran Nomor Surat Tugas',70,748,9); $c.=kw_txt(': '.$r['nomor_surat_tugas'],260,748,9);
$letterDate=trim((string)($r['tanggal_surat_tugas']??''));
$c.=kw_txt('Tanggal',70,730,9); $c.=kw_txt(': '.($letterDate!==''?date_long($letterDate):''),260,730,9);

// Table: the task-detail row grows upward for long destination names.
// The lower table border and all content beneath it remain fixed to preserve bottom spacing.
$x0=38;$x1=68;$x2=370;$x3=455;$x4=555;
$detailBottom=558;$totalBottom=538;$jumlahBottom=518;$bottom=498;
$placeLines=kw_wrap_width($tujuan,180,9); // Wrap the destination to the available detail-cell width.
$extraPlaceHeight=(count($placeLines)-1)*12;
$duration=trim((string)($r['lama_perjalanan']??''));
// Keep the information block visually balanced between the date and table border.
// A longer destination gets extra lines without pushing the name into the date area.
$nameY=649;$golonganY=633;$placeY=617;$durationY=$placeY-($extraPlaceHeight+14);
$head2=$nameY+12;$head1=$head2+20;$top=$head1+20;
foreach([[$x0,$top,$x4,$top],[$x0,$head1,$x4,$head1],[$x0,$head2,$x4,$head2],[$x0,$detailBottom,$x4,$detailBottom],[$x0,$totalBottom,$x4,$totalBottom],[$x0,$jumlahBottom,$x4,$jumlahBottom],[$x0,$bottom,$x4,$bottom],[$x0,$top,$x0,$bottom],[$x1,$top,$x1,$bottom],[$x2,$top,$x2,$bottom],[$x3,$top,$x3,$bottom],[$x4,$top,$x4,$bottom]] as $ln)$c.=kw_line($ln[0],$ln[1],$ln[2],$ln[3]);
$c.=kw_txt_center('No.',53,$head1+5,9);$c.=kw_txt_center('Perincian Biaya',219,$head1+5,9);$c.=kw_txt_center('Jumlah',412.5,$head1+5,9);$c.=kw_txt_center('Keterangan',505,$head1+5,9);
$c.=kw_txt_center('(1)',53,$head2+5,9);$c.=kw_txt_center('(2)',219,$head2+5,9);$c.=kw_txt_center('(3)',412.5,$head2+5,9);$c.=kw_txt_center('(4)',505,$head2+5,9);
$c.=kw_txt('1',49,$nameY+5,9);
$labelX=82;$colonX=171;$valueX=181;
$c.=kw_txt('Nama yang bertugas',$labelX,$nameY,9);$c.=kw_txt(':',$colonX,$nameY,9);$c.=kw_txt($r['nama_petugas'],$valueX,$nameY,9);
$c.=kw_txt('Golongan',$labelX,$golonganY,9);$c.=kw_txt(':',$colonX,$golonganY,9);$c.=kw_txt('-', $valueX,$golonganY,9);
$c.=kw_txt('Tempat Tugas',$labelX,$placeY,9);$c.=kw_txt(':',$colonX,$placeY,9);foreach($placeLines as $i=>$line){$c.=kw_txt($line,$valueX,$placeY-($i*9),9);}
$c.=kw_txt('Lamanya Tugas',$labelX,$durationY,9);$c.=kw_txt(':',$colonX,$durationY,9);$c.=kw_txt(($duration!==''?$duration:'1 hari'),$valueX,$durationY,9);
$c.=kw_txt('Total Pengeluaran Riil',82,544,9);$totalRp=kw_rp($amount);$c.=kw_txt($totalRp,$x3-8-kw_text_width($totalRp,9),544,9);
$c.=kw_txt_center('Jumlah',219,524,9);$jumlahRp=kw_rp($amount);$c.=kw_txt($jumlahRp,$x3-8-kw_text_width($jumlahRp,9),524,9);
$c.=kw_txt('Terbilang : '.$terbilang,82,504,9);

// Signature/payment block compacted to match the supplied reference PDF.
// Keep the two payment columns aligned and reduce oversized vertical gaps.
$c.=kw_txt('Indralaya,      '.$pengajuanText,330,462,9);
$c.=kw_txt('Telah dibayar sejumlah',65,438,9);
$c.=kw_txt('Telah menerima jumlah uang sebesar',330,438,9);
$paymentAmount=kw_rp($amount);
$c.=kw_txt($paymentAmount,65,420,9);
$c.=kw_txt($paymentAmount,330,420,9);
$c.=kw_txt('Lunas pada tanggal:',65,393,9); $c.=kw_txt('Bendahara Pengeluaran',65,379,9); $c.=kw_txt('Badan Pusat Statistik',65,365,9); $c.=kw_txt('Kabupaten Ogan Ilir',65,351,9);
$c.=kw_txt('Yang Menerima',390,393,9);
$c.=kw_txt_underlined('Pusvitasari, S.Sos., MP',65,299,9);$c.=kw_txt('NIP. 198307172007012008',65,287,9);
$c.=kw_txt_underlined($r['nama_petugas'],390,299,9);$c.=kw_txt('NIP. -',390,287,9);

$c.=kw_txt_center('PERHITUNGAN SPD RAMPUNG',297,262,9,true);
$c.=kw_txt('Ditetapkan sejumlah',120,239,9);$c.=kw_txt(kw_rp($amount),410,239,9);
$c.=kw_txt('Yang telah dibayar sejumlah',120,223,9);$c.=kw_txt('Rp 0',410,223,9);
$c.=kw_txt('Sisa kurang/lebih',120,207,9);$c.=kw_txt(kw_rp($amount),410,207,9);
$c.=kw_txt_center('Pejabat Pembuat Komitmen',430,163,9);
$c.=kw_txt_center('Badan Pusat Statistik',430,150,9);
$c.=kw_txt_center('Kabupaten Ogan Ilir',430,137,9);
$c.=kw_txt_center_underlined('Yolanda Rizkie Aprilia, S.Tr.Stat',430,80,9);
$c.=kw_txt_center('NIP. 199904172022012006',430,68,9);

$content=$add('<< /Length '.strlen($c)." >>
stream
".$c.'endstream');
$page=$add('<< /Type /Page /Parent PAGESREF 0 R /MediaBox [0 0 '.$W.' '.$H.'] /Resources << /Font << /F1 '.$f1.' 0 R /F2 '.$f2.' 0 R >> >> /Contents '.$content.' 0 R >>');
$pages[]=$page;
// PAGE 3 — DAFTAR PENGELUARAN RIIL, with non-overlapping table rows and text.
$c='';$c.=kw_txt_center('DAFTAR PENGELUARAN RIIL',297,772,9,true);
$c.=kw_txt('Yang bertanda tangan di bawah ini:',70,739,9,false);
$c.=kw_txt('Nama',90,718,9,false);$c.=kw_txt(': '.$r['nama_petugas'],180,718,9,false);
$c.=kw_txt('NIP',90,701,9,false);$c.=kw_txt(': -',180,701,9,false);
$c.=kw_txt('Jabatan',90,684,9,false);$c.=kw_txt(': Petugas PPL Revisit Sensus Ekonomi 2026',180,684,9,false);
$c.=kw_paragraph('Berdasarkan Surat Tugas Nomor: '.$r['nomor_surat_tugas'].', Tanggal: '.(trim((string)($r['tanggal_surat_tugas']??''))!==''?date_long($r['tanggal_surat_tugas']):'').', dengan ini kami menyatakan dengan:',55,660,112,9,12,false);
$c.=kw_paragraph('1. Biaya transport pegawai dan/atau biaya penginapan di bawah ini yang tidak dapat diperoleh bukti-bukti pengeluarannya, meliputi:',55,626,120,9,12,false);

// Table layout mirrors the reference. The detail row expands when the transport
// description wraps to multiple lines, so long village lists never overlap borders.
$x0=40;$x1=65;$x2=380;$x3=455;$x4=555;
$top=595;$head1=565;$head2=545;
$detailLines=kw_wrap_width($detail,307,9); // Fit the description to the full URAIAN column width.
$detailBottom=min(480,511-((count($detailLines)-1)*12));
$totalBottom=$detailBottom-20;$bottom=$totalBottom-20;
$shift=425-$bottom; // shift content below the table to preserve consistent spacing.
foreach([[$x0,$top,$x4,$top],[$x0,$head1,$x4,$head1],[$x0,$head2,$x4,$head2],[$x1,$detailBottom,$x4,$detailBottom],[$x0,$totalBottom,$x4,$totalBottom],[$x0,$bottom,$x4,$bottom],[$x0,$top,$x0,$bottom],[$x1,$top,$x1,$bottom],[$x2,$top,$x2,$bottom],[$x3,$top,$x3,$bottom],[$x4,$top,$x4,$bottom]] as $ln)$c.=kw_line($ln[0],$ln[1],$ln[2],$ln[3]);
$c.=kw_txt_center('No.',52,581,9,false);$c.=kw_txt_center('URAIAN',222.5,581,9,false);$c.=kw_txt_center('Jumlah',417.5,583,9,false);$c.=kw_txt_center('(Rp)',417.5,570,9,false);$c.=kw_txt_center('Keterangan',505,581,9,false);
$c.=kw_txt_center('(1)',52,551,9,false);$c.=kw_txt_center('(2)',222.5,551,9,false);$c.=kw_txt_center('(3)',417.5,551,9,false);$c.=kw_txt_center('(4)',505,551,9,false);
$c.=kw_txt('1',50,521,9,false);
$yy=521;foreach($detailLines as $line){$c.=kw_txt($line,72,$yy,9,false);$yy-=12;}
$amountY=($head2+$detailBottom)/2; $c.=kw_txt(kw_rp($amount),390,$amountY,9,false);
// In the reference PDF, 'Jumlah' is left-aligned in the description column.
$c.=kw_txt('Jumlah',72,$detailBottom-14,9,false);$c.=kw_txt(kw_rp($amount),390,$detailBottom-14,9,false);
$c.=kw_txt('Terbilang : '.$terbilang,72,$totalBottom-14,9,false);
$c.=kw_paragraph('2. Jumlah uang tersebut pada angka 1 di atas benar-benar dikeluarkan untuk pelaksanaan perjalanan dinas dimaksud dan apabila di kemudian hari terdapat kelebihan atas pembayaran, kami bersedia untuk menyetorkan kelebihan tersebut ke Kas Negara.',55,414-$shift,120,9,12,false);
$c.=kw_paragraph('Demikian pernyataan ini kami buat dengan sebenarnya, untuk dipergunakan sebagaimana mestinya.',55,365-$shift,120,9,12,false);
$c.=kw_txt('Mengetahui/Menyetujui:',55,325-$shift,9,false);$c.=kw_txt('Setuju dibayar:',55,311-$shift,9,false);$c.=kw_txt('Pejabat Pembuat Komitmen',55,297-$shift,9,false);
$c.=kw_txt_underlined('Yolanda Rizkie Aprilia, S.Tr.Stat',55,245-$shift,9,false);$c.=kw_txt('NIP. 199904172022012006',55,233-$shift,9,false);
$c.=kw_txt('Indralaya,      '.$pengajuanText,365,325-$shift,9,false);$c.=kw_txt('Pelaksana SPD',365,311-$shift,9,false);
$c.=kw_txt_underlined($r['nama_petugas'],365,245-$shift,9,false);$c.=kw_txt('NIP. -',365,233-$shift,9,false);
$content=$add('<< /Length '.strlen($c)." >>
stream
".$c.'endstream');$page=$add('<< /Type /Page /Parent PAGESREF 0 R /MediaBox [0 0 '.$W.' '.$H.'] /Resources << /Font << /F1 '.$f1.' 0 R /F2 '.$f2.' 0 R >> >> /Contents '.$content.' 0 R >>');$pages[]=$page;
$pagesObj=$add('');$objects[$pagesObj-1]='<< /Type /Pages /Kids ['.implode(' ',array_map(fn($p)=>$p.' 0 R',$pages)).'] /Count 3 >>';foreach($pages as &$p)$objects[$p-1]=str_replace('PAGESREF',$pagesObj,$objects[$p-1]);unset($p);$catalog=$add('<< /Type /Catalog /Pages '.$pagesObj.' 0 R >>');$pdf="%PDF-1.4
%\xE2\xE3\xCF\xD3
";$offsets=[0];foreach($objects as $i=>$o){$offsets[$i+1]=strlen($pdf);$pdf.=($i+1)." 0 obj
".$o."
endobj
";}$xref=strlen($pdf);$pdf.="xref
0 ".(count($objects)+1)."
0000000000 65535 f 
";for($i=1;$i<=count($objects);$i++)$pdf.=sprintf("%010d 00000 n 
",$offsets[$i]);$pdf.="trailer
<< /Size ".(count($objects)+1)." /Root $catalog 0 R >>
startxref
$xref
%%EOF";
$filename='SIPACAK_Kwitansi_'.preg_replace('/[^A-Za-z0-9_-]+/','_', $r['nama_petugas']).'_'.str_replace('-','_',$r['tanggal']).'.pdf';header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="'.$filename.'"');header('Content-Length: '.strlen($pdf));header('Cache-Control: private, no-store, max-age=0');echo $pdf;
