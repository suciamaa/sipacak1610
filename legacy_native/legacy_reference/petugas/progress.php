<?php
require '../config/database.php';
require '../includes/functions.php';
require_kecamatan();

$kid=current_kecamatan_id();
$k=get_kecamatan($pdo,$kid);
$errors=[];
$edit=null;

$pid=id_param($_GET['petugas_id']??$_POST['petugas_id']??null);
if(!$pid){
    flash('error','Silakan pilih nama petugas terlebih dahulu.');
    redirect('dashboard.php');
}

$ps=$pdo->prepare('SELECT id,nama_petugas,kecamatan_id FROM petugas WHERE id=? AND kecamatan_id=?');
$ps->execute([$pid,$kid]);
$selectedPetugas=$ps->fetch();
if(!$selectedPetugas){
    http_response_code(403);
    exit('Anda tidak memiliki akses ke petugas dari kecamatan ini.');
}

// Kwitansi yang dapat diunduh untuk petugas yang sedang dipilih.
$kwitansiByDate=[];
$qKwitansi=$pdo->prepare('SELECT kw.id,kw.tanggal FROM kwitansi kw
    JOIN progress_harian ph ON ph.petugas_id=kw.petugas_id AND ph.kecamatan_id=? AND ph.tanggal=kw.tanggal
    WHERE kw.petugas_id=? AND kw.status="dibuat" AND kw.tanggal BETWEEN ? AND ?
    ORDER BY kw.tanggal');
$qKwitansi->execute([$kid,$pid,ACTIVITY_START,ACTIVITY_END]);
foreach($qKwitansi->fetchAll() as $kwitansiRow){
    $kwitansiByDate[$kwitansiRow['tanggal']]=$kwitansiRow;
}

/* Data Desa → SLS berasal dari Excel yang diubah menjadi JSON saat project disiapkan. */
$dataPath=__DIR__.'/../data/desa_sls.json';
$desaSls=[];
if(is_file($dataPath)){
    $raw=json_decode((string)file_get_contents($dataPath),true);
    if(is_array($raw)) $desaSls=$raw;
}
$kecamatanNama=(string)$k['nama_kecamatan'];
$desaMap=$desaSls[$kecamatanNama]??[];

$subSlsPath=__DIR__.'/../data/sub_sls.json';
$subSlsData=[];
if(is_file($subSlsPath)){
    $rawSub=json_decode((string)file_get_contents($subSlsPath),true);
    if(is_array($rawSub)) $subSlsData=$rawSub;
}
$subSlsMap=$subSlsData[$kecamatanNama]??[];

$requestedDate=trim((string)($_GET['progress_date']??$_POST['tanggal']??''));
$selectedDate=valid_date($requestedDate)?$requestedDate:latest_progress_date($pdo,$pid,$kid);

// Backfill progress lama milik petugas ini: assignment > 0 selalu berarti
// Pendataan lengkap SE2026 aktif, termasuk data yang dibuat sebelum logic ini
// diterapkan. Aktivitas/permasalahan lain tidak diubah.
sync_assignment_pendataan_lengkap($pdo,$pid,$kid);

$editId=id_param($_GET['edit']??null);
if($editId){
    $q=$pdo->prepare('SELECT ph.* FROM progress_harian ph WHERE ph.id=? AND ph.kecamatan_id=? AND ph.petugas_id=?');
    $q->execute([$editId,$kid,$pid]);
    $found=$q->fetch();
    if(!$found){
        http_response_code(403);
        exit('Anda tidak memiliki akses ke data progress ini.');
    }
    $edit=$found;
    $selectedDate=$edit['tanggal'];
}

function progress_time_normalize(?string $time): string {
    $time=trim((string)$time);
    if($time==='') return '';
    // Nilai TIME dari database kadang berbentuk HH:MM:SS, sedangkan
    // input type=time dan validasi aplikasi menggunakan HH:MM.
    if(preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/',$time)) return substr($time,0,5);
    return $time;
}

function progress_time_valid(?string $time): bool {
    $time=progress_time_normalize($time);
    if($time===null || $time==='') return true;
    return (bool)preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',$time);
}

function normalize_text(mixed $value, int $max=1000): string {
    $value=trim((string)$value);
    if(function_exists('mb_substr')) return mb_substr($value,0,$max,'UTF-8');
    return substr($value,0,$max);
}

function documentation_upload_error(int $code): string {
    return match($code){
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas upload server.',
        UPLOAD_ERR_PARTIAL => 'File hanya terunggah sebagian.',
        UPLOAD_ERR_NO_FILE => 'Tidak ada file yang dipilih.',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary upload server tidak tersedia.',
        UPLOAD_ERR_CANT_WRITE => 'File tidak dapat ditulis ke server.',
        UPLOAD_ERR_EXTENSION => 'Upload file dihentikan oleh ekstensi server.',
        default => 'File gagal diunggah.'
    };
}

function progress_save_exception_message(Throwable $e): string {
    $message=trim((string)$e->getMessage());
    $lower=strtolower($message);
    if(str_contains($lower,'duplicate entry')) {
        if(str_contains($lower,'uq_progress_petugas_tanggal') || str_contains($lower,'progress_harian')) {
            return 'Progress untuk petugas dan tanggal tersebut sudah tersedia. Silakan pilih tanggal 27 September 2026 pada filter lalu gunakan tombol Edit.';
        }
        if(str_contains($lower,'uq_progress_sls')) {
            return 'SLS yang sama boleh dipakai jika Sub SLS berbeda. Struktur database masih memakai aturan lama; jalankan database/migration_sub_sls_duplicate_rule.sql lalu coba simpan kembali.';
        }
        return 'Data yang sama sudah tercatat di database. Periksa kembali tanggal dan SLS yang dipilih.';
    }
    if(str_contains($lower,'cannot add or update a child row') || str_contains($lower,'foreign key constraint')) {
        return 'Data progress gagal disimpan karena relasi data petugas/kecamatan/SLS tidak valid. Muat ulang halaman lalu pilih kembali Desa dan SLS.';
    }
    if(str_contains($lower,'unknown column') || str_contains($lower,"doesn't exist")) {
        return 'Struktur database belum sesuai dengan versi aplikasi ini. Pastikan tabel progress_sls, dokumentasi, dan kolom assignment_usaha_dengan_keluarga sudah terpasang.';
    }
    if(str_contains($lower,'permission denied') || str_contains($lower,'failed to open stream') || str_contains($lower,'cannot create directory') || str_contains($lower,'folder dokumentasi')) {
        return 'Folder uploads/dokumentasi tidak dapat ditulis oleh server. Pastikan folder tersebut memiliki izin tulis untuk PHP.';
    }
    if(str_contains($lower,'foto dokumentasi tidak dapat disimpan') || str_contains($lower,'move_uploaded_file')) {
        return 'Foto dokumentasi gagal disimpan ke server. Pastikan folder uploads/dokumentasi dapat ditulis dan ukuran foto tidak melebihi 250 KB.';
    }
    return 'Data progress tidak dapat disimpan. Silakan coba lagi. Jika masih gagal, periksa log PHP/server untuk detail error.';
}

function delete_documentation_file(string $relativePath): void {
    $relativePath=ltrim(str_replace(['\\','..'],['/',''], $relativePath),'/');
    $base=realpath(__DIR__.'/../uploads');
    if(!$base) return;
    $candidate=realpath(__DIR__.'/../'.$relativePath);
    if($candidate && str_starts_with($candidate,$base.DIRECTORY_SEPARATOR) && is_file($candidate)) @unlink($candidate);
}

/*
 * Blok form selalu berupa array. Saat edit, seluruh SLS untuk tanggal tersebut
 * diambil beserta dokumentasinya. Saat tanggal belum memiliki progress, form kosong/default.
 */
$formBlocks=[];
if($edit){
    $q=$pdo->prepare('SELECT ps.*, COUNT(d.id) AS jumlah_dokumentasi
                      FROM progress_sls ps
                      LEFT JOIN dokumentasi d ON d.progress_sls_id=ps.id
                      WHERE ps.progress_id=?
                      GROUP BY ps.id
                      ORDER BY ps.id');
    $q->execute([$edit['id']]);
    $formBlocks=$q->fetchAll();
    if(!$formBlocks){
        $formBlocks=[[
            'id'=>null,
            'nama_desa'=>'','nama_sls'=>'','nama_sub_sls'=>'',
            'assignment_keluarga'=>(int)$edit['assignment_keluarga'],
            'assignment_usaha'=>(int)$edit['assignment_usaha'],
            'assignment_usaha_dengan_keluarga'=>(int)($edit['assignment_usaha_dengan_keluarga']??0),
            'jumlah_assignment'=>(int)$edit['jumlah_assignment'],
            'jam_mulai'=>'','jam_selesai'=>'','permasalahan'=>'','pemecahan_masalah'=>'',
            'legacy'=>true,
        ]];
    }
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    check_csrf();
    $action=(string)($_POST['action']??'save');
    $id=id_param($_POST['id']??null);
    $date=trim((string)($_POST['tanggal']??''));
    $blocks=$_POST['sls']??[];
    $blocks=is_array($blocks)?$blocks:[];

    if(!valid_date($date)) {
        $errors[]='Tanggal harus berada pada periode 17–30 September 2026.';
    } elseif($date>progress_input_max_date()) {
        $errors[]='Progress hanya dapat diinput sampai tanggal hari ini.';
    }
    if(!$blocks) $errors[]='Minimal satu SLS harus diinput.';

    $cleanBlocks=[];
    $seenSls=[];
    foreach($blocks as $i=>$block){
        $idx=(int)$i+1;
        if(!is_array($block)){ $errors[]="Data SLS {$idx} tidak valid."; continue; }
        $progressSlsId=id_param($block['progress_sls_id']??null);
        $desa=trim((string)($block['desa']??''));
        $sls=trim((string)($block['sls']??''));
        $subSls=trim((string)($block['sub_sls']??''));
        $kel=filter_var($block['assignment_keluarga']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
        $usaha=filter_var($block['assignment_usaha']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
        $usahaKeluarga=filter_var($block['assignment_usaha_dengan_keluarga']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
        $jamMulai=progress_time_normalize((string)($block['jam_mulai']??''));
        $jamSelesai=progress_time_normalize((string)($block['jam_selesai']??''));
        // Jika salah satu assignment > 0, Pendataan lengkap SE2026 wajib aktif.
        // Aturan ini ditegakkan di server agar tetap berlaku meskipun checkbox
        // disabled/tidak dikirim atau request dimanipulasi dari sisi browser.
        $hasAssignment = ($kel !== false && (int)$kel > 0)
            || ($usaha !== false && (int)$usaha > 0)
            || ($usahaKeluarga !== false && (int)$usahaKeluarga > 0);

        $activity=[
            'kepala_desa'=>!empty($block['menemui_petugas_wilayah']['kepala_desa']),
            'lurah'=>!empty($block['menemui_petugas_wilayah']['lurah']),
            'ketua_sls'=>!empty($block['menemui_petugas_wilayah']['ketua_sls']),
            'penelusuran_wilayah'=>!empty($block['aktivitas_dilakukan']['penelusuran_wilayah']),
            'konfirmasi_ulang_data'=>!empty($block['aktivitas_dilakukan']['konfirmasi_ulang_data']),
            'pendataan_lengkap'=>$hasAssignment
                || !empty($block['aktivitas_dilakukan']['pendataan_lengkap'])
                || !empty($block['menemui_petugas_wilayah']['pendataan']),
        ];
        $problem=[
            'kendala_aplikasi_fasih'=>!empty($block['permasalahan']['kendala_aplikasi_fasih']),
            'responden_sulit_ditemui'=>!empty($block['permasalahan']['responden_sulit_ditemui']),
            'perbedaan_data'=>!empty($block['permasalahan']['perbedaan_data']),
            'kendala_lainnya'=>!empty($block['permasalahan']['kendala_lainnya_checked']) ? normalize_text($block['permasalahan']['kendala_lainnya_text']??'',1000) : '',
        ];
        if(!$activity['kepala_desa'] && !$activity['lurah'] && !$activity['ketua_sls'] && !$activity['penelusuran_wilayah'] && !$activity['konfirmasi_ulang_data'] && !$activity['pendataan_lengkap']) $errors[]="SLS {$idx}: Pilih minimal satu Uraian Aktivitas.";
        if(!$problem['kendala_lainnya']) $problem['kendala_lainnya']='';
        $legacyProblemText=normalize_text($block['legacy_problem_text']??'',1000);
        $hasProblem=$problem['kendala_aplikasi_fasih']||$problem['responden_sulit_ditemui']||$problem['perbedaan_data']||$problem['kendala_lainnya']!=='';
        if(!$hasProblem && $legacyProblemText!=='') {
            $problem['legacy_text']=$legacyProblemText;
            $hasProblem=true;
        }
        if(!$hasProblem) $errors[]="SLS {$idx}: Pilih minimal satu Permasalahan.";
        if(!empty($block['permasalahan']['kendala_lainnya_checked']) && trim($problem['kendala_lainnya'])==='') $errors[]="SLS {$idx}: Tuliskan kendala lainnya.";
        $permasalahan=progress_meta_encode($activity,$problem);
        $pemecahan=normalize_text($block['pemecahan_masalah']??'',1000);

        if(!array_key_exists($desa,$desaMap)) $errors[]="SLS {$idx}: Desa wajib dipilih dan harus berasal dari kecamatan ".e($kecamatanNama).'.';
        if($desa!=='' && $sls!=='' && (!isset($desaMap[$desa]) || !in_array($sls,$desaMap[$desa],true))) $errors[]="SLS {$idx}: Nama SLS tidak sesuai dengan Desa yang dipilih.";
        if($desa==='' || $sls==='') $errors[]="SLS {$idx}: Desa dan SLS wajib dipilih.";
        $allowedSubSls=[];
        foreach((array)($subSlsMap[$desa]??[]) as $slsKey=>$subList){
            if(strtoupper(trim((string)$slsKey))===strtoupper(trim($sls))){
                $allowedSubSls=(array)$subList;
                break;
            }
        }
        if($sls!=='' && $allowedSubSls){
            if($subSls==='' || !in_array($subSls,$allowedSubSls,true)) $errors[]="SLS {$idx}: Sub SLS wajib dipilih sesuai daftar yang tersedia.";
        }else{
            // SLS yang tidak ada dalam daftar Sub SLS memang tidak menggunakan Sub SLS.
            $subSls='';
        }
        if($kel===false||$kel===null) $errors[]="SLS {$idx}: Assignment keluarga harus berupa angka 0 atau lebih.";
        if($usaha===false||$usaha===null) $errors[]="SLS {$idx}: Assignment Usaha (BKU) harus berupa angka 0 atau lebih.";
        if($usahaKeluarga===false||$usahaKeluarga===null) $errors[]="SLS {$idx}: Assignment Usaha dalam Keluarga harus berupa angka 0 atau lebih.";
        /*
         * Saat edit, jam milik SLS yang sudah tersimpan tidak wajib diketik ulang.
         * Jika field dikosongkan, nilai lama akan dipertahankan di blok update.
         * Untuk SLS baru, jam tetap wajib diisi seperti biasa.
         */
        $isExistingSls = ($action==='update' && $progressSlsId);
        if(!$isExistingSls && $jamMulai==='') $errors[]="SLS {$idx}: Jam mulai wajib diisi.";
        elseif($jamMulai!=='' && !progress_time_valid($jamMulai)) $errors[]="SLS {$idx}: Jam mulai harus berformat HH:MM.";
        if(!$isExistingSls && $jamSelesai==='') $errors[]="SLS {$idx}: Jam selesai wajib diisi.";
        elseif($jamSelesai!=='' && !progress_time_valid($jamSelesai)) $errors[]="SLS {$idx}: Jam selesai harus berformat HH:MM.";
        if($jamMulai!=='' && $jamSelesai!=='' && $jamSelesai<$jamMulai) $errors[]="SLS {$idx}: Jam selesai tidak boleh lebih awal dari jam mulai.";
        if($permasalahan==='') $errors[]="SLS {$idx}: Permasalahan wajib diisi.";
        if($pemecahan==='') $errors[]="SLS {$idx}: Pemecahan Masalah wajib diisi.";

        // Blok boleh menggunakan Desa yang sama dengan SLS berbeda.
        // SLS yang sama juga boleh dipakai kembali selama Sub SLS-nya berbeda.
        // Yang dianggap duplikat hanya kombinasi Desa + SLS + Sub SLS yang sama.
        $key=$desa.'\0'.$sls.'\0'.$subSls;
        if($sls!=='' && isset($seenSls[$key])) $errors[]="SLS {$idx}: Desa, SLS, dan Sub SLS tersebut sudah dipilih pada blok sebelumnya.";
        if($sls!=='') $seenSls[$key]=true;

        $cleanBlocks[]=[
            'progress_sls_id'=>$progressSlsId,
            'nama_desa'=>$desa,
            'nama_sls'=>$sls,
            'nama_sub_sls'=>$subSls,
            'assignment_keluarga'=>$kel===false||$kel===null?0:(int)$kel,
            'assignment_usaha'=>$usaha===false||$usaha===null?0:(int)$usaha,
            'assignment_usaha_dengan_keluarga'=>$usahaKeluarga===false||$usahaKeluarga===null?0:(int)$usahaKeluarga,
            'jam_mulai'=>$jamMulai!==''?$jamMulai:null,
            'jam_selesai'=>$jamSelesai!==''?$jamSelesai:null,
            'permasalahan'=>$permasalahan,
            'pemecahan_masalah'=>$pemecahan,
            'existing_doc_ids'=>array_values(array_filter(array_map('id_param',(array)($block['existing_doc_ids']??[])))),
            'delete_doc_ids'=>array_values(array_filter(array_map('id_param',(array)($block['delete_doc_ids']??[])))),
            'uploads'=>$_FILES['sls']['tmp_name'][$i]['dokumentasi_files']??[],
            'upload_names'=>$_FILES['sls']['name'][$i]['dokumentasi_files']??[],
            'upload_types'=>$_FILES['sls']['type'][$i]['dokumentasi_files']??[],
            'upload_errors'=>$_FILES['sls']['error'][$i]['dokumentasi_files']??[],
            'upload_sizes'=>$_FILES['sls']['size'][$i]['dokumentasi_files']??[],
        ];
    }

    /* Validasi file dilakukan sebelum perubahan database agar satu file bermasalah
       tidak membuat sebagian progress tersimpan. */
    $allowedMime=['image/jpeg'=>'jpg','image/png'=>'png'];
    foreach($cleanBlocks as $i=>$block){
        $files=$block['uploads'];
        if(!is_array($files)) continue;
        foreach($files as $j=>$tmp){
            $err=(int)($block['upload_errors'][$j]??UPLOAD_ERR_NO_FILE);
            if($err===UPLOAD_ERR_NO_FILE) continue;
            $name=(string)($block['upload_names'][$j]??'foto');
            $size=(int)($block['upload_sizes'][$j]??0);
            if($err!==UPLOAD_ERR_OK){$errors[]='SLS '.($i+1).' - '.$name.': '.documentation_upload_error($err);continue;}
            if($size>250*1024){$errors[]='SLS '.($i+1).' - '.$name.': Ukuran foto maksimal 250 KB.';continue;}
            $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
            if(!isset($allowedMime[$mime])) $errors[]='SLS '.($i+1).' - '.$name.': Format file tidak didukung. Silakan upload JPG, JPEG, atau PNG.';
        }
        $existingCount=count($block['existing_doc_ids']??[]);
        $newValidCount=0;
        $newUploadCount=0;
        foreach($files as $j=>$tmp){
            $err=(int)($block['upload_errors'][$j]??UPLOAD_ERR_NO_FILE);
            if($err!==UPLOAD_ERR_NO_FILE) $newUploadCount++;
            if($err===UPLOAD_ERR_OK) $newValidCount++;
        }
        $submittedDocs=$existingCount+$newUploadCount;
        $validDocs=$existingCount+$newValidCount;
        if($submittedDocs>5) $errors[]='SLS '.($i+1).': Maksimal 5 dokumentasi foto untuk setiap SLS.';
        if($validDocs===0) $errors[]='SLS '.($i+1).': Minimal satu dokumentasi foto wajib diunggah.';
    }

    if(!$errors){
        $sumKel=array_sum(array_column($cleanBlocks,'assignment_keluarga'));
        $sumUsaha=array_sum(array_column($cleanBlocks,'assignment_usaha'));
        $sumUsahaKeluarga=array_sum(array_column($cleanBlocks,'assignment_usaha_dengan_keluarga'));
        $sumTotal=$sumKel+$sumUsaha+$sumUsahaKeluarga;
        $newFiles=[];

        try{
            $pdo->beginTransaction();
            if($action==='update' && $id){
                $q=$pdo->prepare('SELECT * FROM progress_harian WHERE id=? AND kecamatan_id=? AND petugas_id=?');
                $q->execute([$id,$kid,$pid]);
                $existingProgress=$q->fetch();
                if(!$existingProgress){
                    $pdo->rollBack();
                    http_response_code(403);
                    exit('Anda tidak memiliki akses ke data progress ini.');
                }
                if($date!==$existingProgress['tanggal']){
                    $pdo->rollBack();
                    $errors[]='Tanggal progress tidak dapat dipindahkan saat edit. Pilih tanggal lain dari filter tanggal untuk mengedit progress tanggal tersebut.';
                }else{
                    $q=$pdo->prepare('SELECT id FROM progress_harian WHERE petugas_id=? AND tanggal=? AND id<>?');
                    $q->execute([$pid,$date,$id]);
                    if($q->fetch()){
                        $pdo->rollBack();
                        $errors[]='Progress untuk petugas dan tanggal tersebut sudah tersedia. Silakan edit data yang sudah ada.';
                    }else{
                        $q=$pdo->prepare('SELECT id,file_path FROM dokumentasi WHERE progress_sls_id IN (SELECT id FROM progress_sls WHERE progress_id=?)');
                        $q->execute([$id]);
                        $oldDocs=$q->fetchAll();
                        $q=$pdo->prepare('SELECT id FROM progress_sls WHERE progress_id=?');
                        $q->execute([$id]);
                        $oldSlsIds=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));

                        $q=$pdo->prepare('UPDATE progress_harian SET assignment_keluarga=?,assignment_usaha=?,assignment_usaha_dengan_keluarga=?,jumlah_assignment=? WHERE id=? AND kecamatan_id=? AND petugas_id=?');
                        $q->execute([$sumKel,$sumUsaha,$sumUsahaKeluarga,$sumTotal,$id,$kid,$pid]);

                        $updateSls=$pdo->prepare('UPDATE progress_sls SET petugas_id=?,kecamatan_id=?,tanggal=?,nama_desa=?,nama_sls=?,nama_sub_sls=?,assignment_keluarga=?,assignment_usaha=?,assignment_usaha_dengan_keluarga=?,jumlah_assignment=?,jam_mulai=?,jam_selesai=?,permasalahan=?,pemecahan_masalah=? WHERE id=? AND progress_id=?');
                        $insertSls=$pdo->prepare('INSERT INTO progress_sls(progress_id,petugas_id,kecamatan_id,tanggal,nama_desa,nama_sls,nama_sub_sls,assignment_keluarga,assignment_usaha,assignment_usaha_dengan_keluarga,jumlah_assignment,jam_mulai,jam_selesai,permasalahan,pemecahan_masalah) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                        $keptSlsIds=[];

                        foreach($cleanBlocks as $i=>$b){
                            $slsId=$b['progress_sls_id'];
                            if($slsId && in_array($slsId,$oldSlsIds,true)){
                                /*
                                 * Edit parsial: jam tidak perlu diketik ulang. Jika request
                                 * membawa jam kosong, ambil kembali jam yang tersimpan untuk
                                 * SLS tersebut agar tidak tertimpa NULL.
                                 */
                                $oldTimeQ=$pdo->prepare('SELECT jam_mulai,jam_selesai FROM progress_sls WHERE id=? AND progress_id=?');
                                $oldTimeQ->execute([$slsId,$id]);
                                $oldTime=$oldTimeQ->fetch() ?: [];
                                $jamMulaiUpdate=$b['jam_mulai']!==null && $b['jam_mulai']!=='' ? $b['jam_mulai'] : ($oldTime['jam_mulai']??null);
                                $jamSelesaiUpdate=$b['jam_selesai']!==null && $b['jam_selesai']!=='' ? $b['jam_selesai'] : ($oldTime['jam_selesai']??null);
                                $updateSls->execute([$pid,$kid,$date,$b['nama_desa'],$b['nama_sls'],$b['nama_sub_sls'],$b['assignment_keluarga'],$b['assignment_usaha'],$b['assignment_usaha_dengan_keluarga'],$b['assignment_keluarga']+$b['assignment_usaha']+$b['assignment_usaha_dengan_keluarga'],$jamMulaiUpdate,$jamSelesaiUpdate,$b['permasalahan'],$b['pemecahan_masalah'],$slsId,$id]);
                            }else{
                                $insertSls->execute([$id,$pid,$kid,$date,$b['nama_desa'],$b['nama_sls'],$b['nama_sub_sls'],$b['assignment_keluarga'],$b['assignment_usaha'],$b['assignment_usaha_dengan_keluarga'],$b['assignment_keluarga']+$b['assignment_usaha']+$b['assignment_usaha_dengan_keluarga'],$b['jam_mulai'],$b['jam_selesai'],$b['permasalahan'],$b['pemecahan_masalah']]);
                                $slsId=(int)$pdo->lastInsertId();
                            }
                            $keptSlsIds[]=$slsId;

                            foreach($b['delete_doc_ids'] as $docId){
                                $dq=$pdo->prepare('SELECT file_path FROM dokumentasi WHERE id=? AND progress_sls_id=?');
                                $dq->execute([$docId,$slsId]);
                                if($doc=$dq->fetch()){
                                    delete_documentation_file((string)$doc['file_path']);
                                    $pdo->prepare('DELETE FROM dokumentasi WHERE id=? AND progress_sls_id=?')->execute([$docId,$slsId]);
                                }
                            }

                            $fileInsert=$pdo->prepare('INSERT INTO dokumentasi(progress_sls_id,petugas_id,file_path,file_name,catatan,created_at) VALUES(?,?,?,?,?,NOW())');
                            $files=$b['uploads'];
                            foreach($files as $j=>$tmp){
                                $err=(int)($b['upload_errors'][$j]??UPLOAD_ERR_NO_FILE);
                                if($err!==UPLOAD_ERR_OK) continue;
                                $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
                                $ext=$allowedMime[$mime];
                                $safeBase=bin2hex(random_bytes(12));
                                $relative='uploads/dokumentasi/'.$date.'/petugas_'.$pid.'/sls_'.$slsId.'/'.$safeBase.'.'.$ext;
                                $target=__DIR__.'/../'.$relative;
                                if(!is_dir(dirname($target)) && !mkdir(dirname($target),0775,true) && !is_dir(dirname($target))) throw new RuntimeException('Folder dokumentasi tidak dapat dibuat.');
                                if(!move_uploaded_file($tmp,$target)) throw new RuntimeException('Foto dokumentasi tidak dapat disimpan.');
                                $newFiles[]=$target;
                                $fileInsert->execute([$slsId,$pid,$relative,basename((string)$b['upload_names'][$j]),'']);
                            }
                        }

                        $staleSls=array_values(array_diff($oldSlsIds,$keptSlsIds));
                        if($staleSls){
                            $ph=implode(',',array_fill(0,count($staleSls),'?'));
                            $dq=$pdo->prepare("SELECT file_path FROM dokumentasi WHERE progress_sls_id IN ($ph)");
                            $dq->execute($staleSls);
                            foreach($dq->fetchAll() as $doc) delete_documentation_file((string)$doc['file_path']);
                            $pdo->prepare("DELETE FROM progress_sls WHERE progress_id=? AND id IN ($ph)")->execute(array_merge([$id],$staleSls));
                        }
                        $pdo->commit();
                        flash('success','Progress harian, detail kegiatan, dan dokumentasi berhasil diperbarui.');
                        redirect('petugas/progress.php?petugas_id='.$pid.'&progress_date='.urlencode($date));
                    }
                }
            }else{
                $q=$pdo->prepare('SELECT id FROM progress_harian WHERE petugas_id=? AND tanggal=? AND kecamatan_id=?');
                $q->execute([$pid,$date,$kid]);
                if($q->fetch()){
                    $pdo->rollBack();
                    $errors[]='Progress untuk petugas dan tanggal tersebut sudah tersedia. Data tidak ditimpa. Silakan pilih tanggal tersebut dari filter untuk mengeditnya.';
                }else{
                    $q=$pdo->prepare('INSERT INTO progress_harian(petugas_id,kecamatan_id,tanggal,assignment_keluarga,assignment_usaha,assignment_usaha_dengan_keluarga,jumlah_assignment) VALUES(?,?,?,?,?,?,?)');
                    $q->execute([$pid,$kid,$date,$sumKel,$sumUsaha,$sumUsahaKeluarga,$sumTotal]);
                    $progressId=(int)$pdo->lastInsertId();
                    $ins=$pdo->prepare('INSERT INTO progress_sls(progress_id,petugas_id,kecamatan_id,tanggal,nama_desa,nama_sls,nama_sub_sls,assignment_keluarga,assignment_usaha,assignment_usaha_dengan_keluarga,jumlah_assignment,jam_mulai,jam_selesai,permasalahan,pemecahan_masalah) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                    foreach($cleanBlocks as $i=>$b){
                        $ins->execute([$progressId,$pid,$kid,$date,$b['nama_desa'],$b['nama_sls'],$b['nama_sub_sls'],$b['assignment_keluarga'],$b['assignment_usaha'],$b['assignment_usaha_dengan_keluarga'],$b['assignment_keluarga']+$b['assignment_usaha']+$b['assignment_usaha_dengan_keluarga'],$b['jam_mulai'],$b['jam_selesai'],$b['permasalahan'],$b['pemecahan_masalah']]);
                        $slsId=(int)$pdo->lastInsertId();
                        $fileInsert=$pdo->prepare('INSERT INTO dokumentasi(progress_sls_id,petugas_id,file_path,file_name,catatan,created_at) VALUES(?,?,?,?,?,NOW())');
                        $files=$b['uploads'];
                        foreach($files as $j=>$tmp){
                            $err=(int)($b['upload_errors'][$j]??UPLOAD_ERR_NO_FILE);
                            if($err!==UPLOAD_ERR_OK) continue;
                            $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
                            $ext=$allowedMime[$mime];
                            $safeBase=bin2hex(random_bytes(12));
                            $relative='uploads/dokumentasi/'.$date.'/petugas_'.$pid.'/sls_'.$slsId.'/'.$safeBase.'.'.$ext;
                            $target=__DIR__.'/../'.$relative;
                            if(!is_dir(dirname($target)) && !mkdir(dirname($target),0775,true) && !is_dir(dirname($target))) throw new RuntimeException('Folder dokumentasi tidak dapat dibuat.');
                            if(!move_uploaded_file($tmp,$target)) throw new RuntimeException('Foto dokumentasi tidak dapat disimpan.');
                            $newFiles[]=$target;
                            $fileInsert->execute([$slsId,$pid,$relative,basename((string)$b['upload_names'][$j]),'']);
                        }
                    }
                    $pdo->commit();
                    flash('success','Progress harian, detail kegiatan, dan dokumentasi berhasil disimpan.');
                    redirect('petugas/progress.php?petugas_id='.$pid.'&progress_date='.urlencode($date));
                }
            }
        }catch(Throwable $e){
            if($pdo->inTransaction()) $pdo->rollBack();
            foreach($newFiles as $file) if(is_file($file)) @unlink($file);
            error_log($e->getMessage());
            if(!$errors) $errors[]=progress_save_exception_message($e);
        }
    }

    $formBlocks=$cleanBlocks ?: $blocks;
    if(!$formBlocks) $formBlocks=[['nama_desa'=>'','nama_sls'=>'','nama_sub_sls'=>'','assignment_keluarga'=>0,'assignment_usaha'=>0,'assignment_usaha_dengan_keluarga'=>0,'jam_mulai'=>'00:00','jam_selesai'=>'00:00','permasalahan'=>'','pemecahan_masalah'=>'']];
    $selectedDate=$date;
    if($action==='update' && $id){
        $q=$pdo->prepare('SELECT * FROM progress_harian WHERE id=? AND kecamatan_id=? AND petugas_id=?');
        $q->execute([$id,$kid,$pid]);
        $edit=$q->fetch()?:null;
    }
}

$formTanggal=$edit ? $selectedDate : (valid_date($selectedDate) ? '' : '');
if($errors && valid_date($selectedDate)) $formTanggal=$selectedDate;
if(!$formBlocks) $formBlocks=[['nama_desa'=>'','nama_sls'=>'','nama_sub_sls'=>'','assignment_keluarga'=>0,'assignment_usaha'=>0,'assignment_usaha_dengan_keluarga'=>0,'jam_mulai'=>'00:00','jam_selesai'=>'00:00','permasalahan'=>'','pemecahan_masalah'=>'']];

/* Progress tersimpan menampilkan seluruh progress petugas, tidak dibatasi tanggal. */
$q=$pdo->prepare('SELECT ph.*, COUNT(ps.id) AS jumlah_sls
                  FROM progress_harian ph
                  LEFT JOIN progress_sls ps ON ps.progress_id=ph.id
                  WHERE ph.petugas_id=? AND ph.kecamatan_id=?
                  GROUP BY ph.id ORDER BY ph.tanggal DESC, ph.id DESC');
$q->execute([$pid,$kid]);
$rows=$q->fetchAll();

$detailsByProgress=[];
$docsByProgressSls=[];
if($rows){
    $ids=array_map(fn($r)=>(int)$r['id'],$rows);
    $ph=implode(',',array_fill(0,count($ids),'?'));
    $q=$pdo->prepare("SELECT ps.*,COUNT(d.id) AS jumlah_dokumentasi FROM progress_sls ps LEFT JOIN dokumentasi d ON d.progress_sls_id=ps.id WHERE ps.progress_id IN ($ph) GROUP BY ps.id ORDER BY ps.id ASC");
    $q->execute($ids);
    $details=$q->fetchAll();
    foreach($details as $d) $detailsByProgress[(int)$d['progress_id']][]=$d;
    $q=$pdo->prepare("SELECT d.* FROM dokumentasi d JOIN progress_sls ps ON ps.id=d.progress_sls_id WHERE ps.progress_id IN ($ph) ORDER BY d.id ASC");
    $q->execute($ids);
    foreach($q->fetchAll() as $doc) $docsByProgressSls[(int)$doc['progress_sls_id']][]=$doc;
}

/* Ambil dokumentasi untuk form edit. */
$docsBySls=[];
if($edit){
    $q=$pdo->prepare('SELECT d.* FROM dokumentasi d JOIN progress_sls ps ON ps.id=d.progress_sls_id WHERE ps.progress_id=? ORDER BY d.id');
    $q->execute([$edit['id']]);
    foreach($q->fetchAll() as $doc) $docsBySls[(int)$doc['progress_sls_id']][]=$doc;
}

$pageTitle='Progress Harian';
require '../includes/header.php';
?>
<!-- <section class="container page-heading">
  <div class="breadcrumbs"><a href="<?=e(url('dashboard.php'))?>">Pilih Petugas</a><span>›</span><b>Progress Harian</b></div>
  <span class="eyebrow">SECTION 01 • PROGRESS HARIAN</span>
  <h1>Progress Harian</h1>
  <p>Catat progress petugas berdasarkan tanggal, desa, dan SLS yang dikerjakan.</p>
</section> -->
<section class="container section selected-petugas-banner">
  <div><span class="eyebrow">PETUGAS TERPILIH</span><h2><?=e($selectedPetugas['nama_petugas'])?></h2><p>Kecamatan: <strong><?=e($kecamatanNama)?></strong></p></div>
  <a class="btn" href="<?=e(url('petugas/index.php'))?>">← Ganti Petugas</a>
</section>
<section class="container section progress-intro">
  <div class="progress-intro-copy"><span class="eyebrow">INPUT DATA</span><h2>Progress Harian</h2></div>
  <div class="section-head-actions">
    <button type="button" class="btn btn-primary" id="toggleProgressForm"><?=($edit||$errors)?'Tutup Form':'+ Input Progress Harian'?></button>
  </div>
</section>
<section class="container progress-form <?=($edit||$errors)?'is-open':''?>" id="progressFormSection">
  <?php if($errors):?><div class="alert alert-error"><?php foreach($errors as $er):?><div><?=e(strip_tags($er))?></div><?php endforeach;?></div><?php endif;?>
  <form method="post" enctype="multipart/form-data" class="upload-card" id="progressInputForm">
    <input type="hidden" name="csrf_token" value="<?=e(csrf())?>">
    <input type="hidden" name="action" value="<?=$edit?'update':'save'?>">
    <input type="hidden" name="petugas_id" value="<?=$pid?>">
    <?php if($edit):?><input type="hidden" name="id" value="<?=$edit['id']?>"><?php endif;?>

    <div class="progress-input-date-card">
      <div>
        <!-- <span class="eyebrow">TANGGAL INPUT</span> -->
        <h3>Input Tanggal Progress</h3>
        
      </div>
      <label class="progress-input-date-label">
        <select name="tanggal" id="progressInputDate" data-max-date="<?=e(progress_input_max_date())?>" required>
          <option value="">Pilih tanggal progress</option>
          <?php foreach(dates() as $date): ?>
            <option value="<?=e($date)?>" <?=$formTanggal===$date?'selected':''?> <?=($date>progress_input_max_date())?'disabled':''?>><?=e(date_long($date))?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>

    <div id="slsEntryArea" class="sls-entry-area <?=($edit||$errors)?'is-ready':''?>">
      <div class="selected-petugas-inline"><span>Petugas</span><strong><?=e($selectedPetugas['nama_petugas'])?></strong><small><?=e($kecamatanNama)?><?= $formTanggal ? ' • '.e(date_long($formTanggal)) : '' ?></small></div>
      <div id="slsBlocks" class="sls-blocks">
      <?php foreach($formBlocks as $i=>$block):
        $desa=(string)($block['nama_desa']??$block['desa']??'');
        $sls=(string)($block['nama_sls']??$block['sls']??'');
        $subSls=(string)($block['nama_sub_sls']??$block['sub_sls']??'');
        $kel=(int)($block['assignment_keluarga']??0);
        $usaha=(int)($block['assignment_usaha']??0);
        $usahaK=(int)($block['assignment_usaha_dengan_keluarga']??0);
        $jamMulai=(string)($block['jam_mulai']??'');
        $jamSelesai=(string)($block['jam_selesai']??'');
        if(!$edit && !$errors){
          if($jamMulai==='') $jamMulai='00:00';
          if($jamSelesai==='') $jamSelesai='00:00';
        }
        $progressMeta=progress_meta_decode($block['permasalahan']??'');
        $activityMeta=$progressMeta['aktivitas'];
        // Sinkronisasi tampilan data lama: jika assignment yang sudah tersimpan
        // lebih dari 0, Pendataan lengkap wajib dianggap aktif meskipun pada
        // metadata lama checkbox tersebut belum pernah tersimpan sebagai true.
        if(($kel+$usaha+$usahaK)>0){
            $activityMeta['pendataan_lengkap']=true;
        }
        $problemMeta=$progressMeta['permasalahan'];
        $legacyProblemText=$progressMeta['legacy_text'];
        $pemecahan=(string)($block['pemecahan_masalah']??'');
        $legacy=!empty($block['legacy']);
        $progressSlsId=id_param($block['id']??$block['progress_sls_id']??null);
        $docs=$progressSlsId?($docsBySls[$progressSlsId]??[]):[];
      ?>
      <div class="sls-block" data-index="<?=$i?>">
        <div class="sls-block-head"><div><span class="eyebrow">SLS <?=($i+1)?></span><h3>Detail SLS</h3></div><?php if($i>0):?><button type="button" class="btn btn-small sls-remove">Hapus</button><?php endif;?></div>
        <?php if($legacy):?><div class="alert alert-error legacy-note">Data lama ini belum memiliki detail Desa/SLS. Saat disimpan ulang, pilih Desa dan SLS yang sesuai agar detailnya tersimpan.</div><?php endif;?>
        <?php if($progressSlsId):?><input type="hidden" name="sls[<?=$i?>][progress_sls_id]" value="<?=$progressSlsId?>"><?php endif;?>
        <div class="form-grid sls-form-grid">
          <div class="readonly-field"><span>Nama Kecamatan</span><strong><?=e($kecamatanNama)?></strong></div>
          <label>Desa/Kelurahan<select name="sls[<?=$i?>][desa]" class="desa-select" required><option value="">Pilih Desa</option><?php foreach($desaMap as $desaName=>$slsList):?><option value="<?=e($desaName)?>" <?=($desa===$desaName)?'selected':''?>><?=e($desaName)?></option><?php endforeach;?></select></label>
          <label>SLS/RT/RW<select name="sls[<?=$i?>][sls]" class="sls-select" data-selected="<?=e($sls)?>" required><option value="">Pilih SLS</option></select></label>
          <label>Sub SLS<select name="sls[<?=$i?>][sub_sls]" class="sub-sls-select" data-selected="<?=e($subSls)?>" disabled><option value="">Tidak tersedia untuk SLS ini</option></select></label>
          <label>Jam Mulai<input type="time" name="sls[<?=$i?>][jam_mulai]" value="<?=e($jamMulai)?>" step="60" <?=(!$edit || !$progressSlsId)?'required':''?>></label>
          <label>Jam Selesai<input type="time" name="sls[<?=$i?>][jam_selesai]" value="<?=e($jamSelesai)?>" step="60" <?=(!$edit || !$progressSlsId)?'required':''?>></label>
        </div>
        <div class="sls-activity-section">
          <div class="activity-section-title">Uraian Laporan Kegiatan</div>
          <small class="problem-hint">* Pilihan boleh lebih dari 1</small>

          <div class="activity-question">● Menemui Penguasa Wilayah:</div>
          <div class="activity-choice-list activity-choice-group">
            <label class="activity-check-row standalone">
              <span class="activity-check-main"><input type="checkbox" name="sls[<?=$i?>][menemui_petugas_wilayah][kepala_desa]" value="1" class="activity-wilayah-checkbox" <?=$activityMeta['kepala_desa']?'checked':''?>> <span>Kepala Desa</span></span>
            </label>
            <label class="activity-check-row standalone">
              <span class="activity-check-main"><input type="checkbox" name="sls[<?=$i?>][menemui_petugas_wilayah][lurah]" value="1" class="activity-wilayah-checkbox" <?=$activityMeta['lurah']?'checked':''?>> <span>Lurah</span></span>
            </label>
            <label class="activity-check-row standalone">
              <span class="activity-check-main"><input type="checkbox" name="sls[<?=$i?>][menemui_petugas_wilayah][ketua_sls]" value="1" class="activity-wilayah-checkbox" <?=$activityMeta['ketua_sls']?'checked':''?>> <span>Ketua SLS</span></span>
            </label>
          </div>

          <div class="activity-question activity-question-secondary">● Aktivitas yang dilakukan:</div>
          <div class="activity-choice-list activity-choice-group">
            <label class="activity-check-row standalone">
              <span class="activity-check-main"><input type="checkbox" name="sls[<?=$i?>][aktivitas_dilakukan][penelusuran_wilayah]" value="1" class="activity-wilayah-checkbox" <?=$activityMeta['penelusuran_wilayah']?'checked':''?>> <span>Penelusuran Wilayah untuk menemukan keluarga atau usaha yang terlewat</span></span>
            </label>
            <label class="activity-check-row standalone">
              <span class="activity-check-main"><input type="checkbox" name="sls[<?=$i?>][aktivitas_dilakukan][konfirmasi_ulang_data]" value="1" class="activity-wilayah-checkbox" <?=$activityMeta['konfirmasi_ulang_data']?'checked':''?>> <span>Konfirmasi ulang data SE2026</span></span>
            </label>
            <label class="activity-check-row standalone">
              <span class="activity-check-main"><input type="checkbox" name="sls[<?=$i?>][aktivitas_dilakukan][pendataan_lengkap]" value="1" class="activity-wilayah-checkbox activity-pendataan-lengkap" <?=$activityMeta['pendataan_lengkap']?'checked':''?> <?=($kel+$usaha+$usahaK)>0?'disabled':''?>> <span>Pendataan lengkap SE2026</span></span>
            </label>
          </div>
        </div>
        <div class="sls-activity-grid">
          <div class="problem-field">
            <span class="activity-label">Permasalahan</span>
            <small class="problem-hint">* Pilihan boleh lebih dari 1</small>
            <?php if($legacyProblemText!==''): ?>
              <div class="legacy-problem-note">Data permasalahan lama: <?=e($legacyProblemText)?></div>
              <input type="hidden" name="sls[<?=$i?>][legacy_problem_text]" value="<?=e($legacyProblemText)?>">
            <?php endif; ?>
            <div class="problem-choice-list">
              <label class="problem-check-row"><input type="checkbox" name="sls[<?=$i?>][permasalahan][kendala_aplikasi_fasih]" value="1" <?=$problemMeta['kendala_aplikasi_fasih']?'checked':''?>> <span>Kendala aplikasi FASIH</span></label>
              <label class="problem-check-row"><input type="checkbox" name="sls[<?=$i?>][permasalahan][responden_sulit_ditemui]" value="1" <?=$problemMeta['responden_sulit_ditemui']?'checked':''?>> <span>Responden sulit ditemui</span></label>
              <label class="problem-check-row"><input type="checkbox" name="sls[<?=$i?>][permasalahan][perbedaan_data]" value="1" <?=$problemMeta['perbedaan_data']?'checked':''?>> <span>Perbedaan Data</span></label>
              <label class="problem-check-row"><input type="checkbox" name="sls[<?=$i?>][permasalahan][kendala_lainnya_checked]" value="1" class="problem-other-checkbox" <?=($problemMeta['kendala_lainnya']!=='')?'checked':''?>> <span>Kendala lainnya</span></label>
            </div>
            <div class="problem-other-wrap" <?=($problemMeta['kendala_lainnya']===''?'hidden':'')?>>
              <label class="problem-other-label">Tuliskan kendala lainnya:
                <textarea name="sls[<?=$i?>][permasalahan][kendala_lainnya_text]" maxlength="1000" rows="4" class="counted-textarea problem-other-textarea" data-counter-label="Kendala lainnya" placeholder="Tuliskan kendala lainnya..."><?=e($problemMeta['kendala_lainnya'])?></textarea>
                <small class="char-counter"><span class="char-count">0</span>/1000 karakter</small>
              </label>
            </div>
          </div>
          <label><span class="activity-label">Pemecahan Masalah</span>
            <small class="activity-helper">Solusi yang diambil untuk menyelesaikan kendala tersebut</small>
            <textarea name="sls[<?=$i?>][pemecahan_masalah]" maxlength="1000" rows="5" class="counted-textarea" data-counter-label="Pemecahan Masalah" placeholder="Tuliskan pemecahan masalah yang dilakukan..." required><?=e($pemecahan)?></textarea>
            <small class="char-counter"><span class="char-count">0</span>/1000 karakter</small>
          </label>
        </div>
        <div class="sls-assignment-section">
          <div class="assignment-section-title">Assignment</div>
          <div class="sls-assignment-grid">
            <label>Assignment Keluarga<input type="number" name="sls[<?=$i?>][assignment_keluarga]" min="0" step="1" value="<?=$kel?>" required></label>
            <label>Assignment Usaha (BKU)<input type="number" name="sls[<?=$i?>][assignment_usaha]" min="0" step="1" value="<?=$usaha?>" required></label>
            <label>Assignment Usaha dalam Keluarga<input type="number" name="sls[<?=$i?>][assignment_usaha_dengan_keluarga]" min="0" step="1" value="<?=$usahaK?>" required></label>
            <label>Total Assignment<input class="sls-total" value="<?=($kel+$usaha+$usahaK)?>" readonly></label>
          </div>
        </div>
        <div class="documentation-section">
          <div class="documentation-head"><div><span class="eyebrow">DOKUMENTASI</span><h4>Dokumentasi Foto</h4><p>Setiap foto maksimal 250 KB. Format JPG/JPEG atau PNG.</p></div></div>
          <div class="documentation-list">
            <?php foreach($docs as $doc): ?>
              <div class="documentation-row existing-documentation" data-doc-id="<?=e($doc['id'])?>">
                <div class="documentation-preview"><img src="<?=e(url($doc['file_path']))?>" alt="Dokumentasi"></div>
                <div class="documentation-meta"><strong><?=e($doc['file_name'])?></strong></div>
                <input type="hidden" name="sls[<?=$i?>][existing_doc_ids][]" value="<?=e($doc['id'])?>">
                <button type="button" class="btn btn-small documentation-delete-existing">Hapus</button>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="new-documentation-list"></div>
          <button type="button" class="btn btn-add-documentation">+ Tambah Foto</button>
        </div>
      </div>
      <?php endforeach; ?>
      </div>
      <button type="button" class="btn btn-add-sls" id="addSls">+ Tambah SLS</button>
      <div class="form-actions"><a class="btn" href="<?=e(url('petugas/progress.php'))?>?petugas_id=<?=$pid?>&progress_date=<?=e($selectedDate)?>">Batal</a><button class="btn btn-primary"><?=$edit?'Simpan Perubahan':'Simpan Progress'?></button></div>
    </div>
  </form>
</section>
<section class="container section">
  <div class="section-head">
    <div><span class="eyebrow">PROGRESS PETUGAS</span><h2>Riwayat Progress Harian</h2></div>
    <div class="section-head-actions progress-count-actions">
      <span><?=count($rows)?> progress</span>
    </div>
  </div>
  <div class="table-wrap simple-table progress-history-table"><table><thead><tr><th>Aksi</th><th>Hari</th><th>Tanggal</th><th>Desa</th><th>SLS</th><th>Jam Mulai</th><th>Jam Selesai</th><th>Assignment Keluarga</th><th>Assignment Usaha (BKU)</th><th>Assignment Usaha dalam Keluarga</th><th>Total Assignment</th></tr></thead><tbody>
  <?php if(!$rows):?><tr><td colspan="11" class="table-empty">Belum ada input progress. Klik <strong>+ Input Progress Harian</strong> untuk memasukkan data.</td></tr><?php else:foreach($rows as $r):
      $details=$detailsByProgress[(int)$r['id']]??[];
      if(!$details) $details=[['id'=>null,'nama_desa'=>'—','nama_sls'=>'—','assignment_keluarga'=>$r['assignment_keluarga'],'assignment_usaha'=>$r['assignment_usaha'],'assignment_usaha_dengan_keluarga'=>$r['assignment_usaha_dengan_keluarga']??0,'jumlah_assignment'=>(int)$r['assignment_keluarga']+(int)$r['assignment_usaha']+(int)($r['assignment_usaha_dengan_keluarga']??0),'jam_mulai'=>'','jam_selesai'=>'','permasalahan'=>'','pemecahan_masalah'=>'']];
      foreach($details as $d):
        $detailDocs=$docsByProgressSls[(int)($d['id']??0)]??[];
        $viewData=[
          'tanggal'=>date_long($r['tanggal']),
          'kecamatan'=>$kecamatanNama,
          'desa'=>$d['nama_desa']??'—',
          'sls'=>$d['nama_sls']??'—',
          'jam_mulai'=>$d['jam_mulai']??'',
          'jam_selesai'=>$d['jam_selesai']??'',
          'assignment_keluarga'=>(int)($d['assignment_keluarga']??0),
          'assignment_usaha'=>(int)($d['assignment_usaha']??0),
          'assignment_usaha_dengan_keluarga'=>(int)($d['assignment_usaha_dengan_keluarga']??0),
          'total'=>(int)($d['assignment_keluarga']??0)+(int)($d['assignment_usaha']??0)+(int)($d['assignment_usaha_dengan_keluarga']??0),
          'permasalahan'=>progress_problem_text($d['permasalahan']??''),
          'pemecahan_masalah'=>$d['pemecahan_masalah']??'',
          'uraian_aktivitas'=>progress_meta_decode($d['permasalahan']??'')['aktivitas'],
          'dokumentasi'=>array_map(static fn($doc)=>['url'=>url($doc['file_path']),'file_name'=>$doc['file_name']],$detailDocs),
        ];
        $viewJson=json_encode($viewData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
  ?>
  <?php
    $jamMulaiTersimpan=(string)($d['jam_mulai']??'');
    $jamSelesaiTersimpan=(string)($d['jam_selesai']??'');
    $jamMulaiTampil=$jamMulaiTersimpan!=='' ? substr($jamMulaiTersimpan,0,5) : '—';
    $jamSelesaiTampil=$jamSelesaiTersimpan!=='' ? substr($jamSelesaiTersimpan,0,5) : '—';
  ?>
  <tr>
    <td class="action-cell"><button type="button" class="btn btn-small progress-view-btn" data-detail="<?=e($viewJson)?>">Lihat</button> <a class="btn btn-small" href="<?=e(url('petugas/progress.php'))?>?petugas_id=<?=$pid?>&progress_date=<?=e($r['tanggal'])?>&edit=<?=$r['id']?>">Edit</a></td>
    <td><?=e(day_name($r['tanggal']))?></td>
    <td><?=e(date_id($r['tanggal']))?></td>
    <td><?=e($d['nama_desa']??'—')?></td>
    <td><?=e($d['nama_sls']??'—')?></td>
    <td><?=e($jamMulaiTampil)?></td>
    <td><?=e($jamSelesaiTampil)?></td>
    <td><?=number_format((int)($d['assignment_keluarga']??0))?></td>
    <td><?=number_format((int)($d['assignment_usaha']??0))?></td>
    <td><?=number_format((int)($d['assignment_usaha_dengan_keluarga']??0))?></td>
    <td><strong><?=number_format((int)($d['assignment_keluarga']??0)+(int)($d['assignment_usaha']??0)+(int)($d['assignment_usaha_dengan_keluarga']??0))?></strong></td>
  </tr><?php endforeach;endforeach;endif;?>
  </tbody></table></div>
  <p class="table-scroll-hint">*Geser untuk lihat table selengkapnya</p>
  <div class="progress-download-actions">
    <details class="download-dropdown">
      <summary class="btn btn-primary">Download Kwitansi <span aria-hidden="true">▼</span></summary>
      <div class="download-menu">
        <?php if($kwitansiByDate): ?>
          <a class="kwitansi-download-link" data-download-label="Seluruh Kwitansi" href="<?=e(url('petugas/kwitansi_bundle.php'))?>?petugas_id=<?=$pid?>&amp;all=1">Download Seluruh Kwitansi (ZIP)</a>
        <?php else: ?>
          <span class="download-menu-disabled" aria-disabled="true">Seluruh Kwitansi — Belum tersedia</span>
        <?php endif; ?>
        <?php foreach(dates() as $kwitansiDate): ?>
          <?php if(isset($kwitansiByDate[$kwitansiDate])): ?>
            <a class="kwitansi-download-link" data-download-label="Kwitansi <?=e(date_long($kwitansiDate))?>" href="<?=e(url('kwitansi_pdf.php'))?>?id=<?=(int)$kwitansiByDate[$kwitansiDate]['id']?>"><?=e(preg_replace('/\s+\d{4}$/', '', date_long($kwitansiDate)))?></a>
          <?php else: ?>
            <span class="download-menu-disabled" aria-disabled="true"><?=e(preg_replace('/\s+\d{4}$/', '', date_long($kwitansiDate)))?> — Belum tersedia</span>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </details>
    <details class="download-dropdown">
      <summary class="btn btn-primary">Download LPD <span aria-hidden="true">▼</span></summary>
      <div class="download-menu">
        <a class="lpd-download-link" data-download-label="Seluruh LPD" href="<?=e(url('pdf_lpd.php'))?>?petugas_id=<?=$pid?>&all=1">Download Seluruh LPD</a>
        <?php foreach(dates() as $downloadDate): ?>
          <a class="lpd-download-link" data-download-label="LPD <?=e(date_long($downloadDate))?>" href="<?=e(url('pdf_lpd.php'))?>?petugas_id=<?=$pid?>&progress_date=<?=e($downloadDate)?>"><?=e(preg_replace('/\s+\d{4}$/', '', date_long($downloadDate)))?></a>
        <?php endforeach; ?>
      </div>
    </details>
    <a class="btn btn-primary" href="<?=e(url('pdf_progress.php'))?>?petugas_id=<?=$pid?>">Download Progress Harian</a>
  </div>
</section>

<div class="progress-detail-modal" id="progressDetailModal" aria-hidden="true">
  <div class="progress-detail-backdrop" data-modal-close></div>
  <div class="progress-detail-dialog" role="dialog" aria-modal="true" aria-labelledby="progressDetailTitle">
    <div class="progress-detail-head"><div><span class="eyebrow">DETAIL PROGRESS</span><h3 id="progressDetailTitle">Detail Progress</h3></div><button type="button" class="modal-close-btn" data-modal-close aria-label="Tutup">×</button></div>
    <div class="progress-detail-body" id="progressDetailBody"></div>
  </div>
</div>
<?php require '../includes/footer.php'; ?>
