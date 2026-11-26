<?php
/* Master data jenis wilayah tujuan perjalanan dinas. */
function kw_region_key($s) { $s = preg_replace('/\s+/u', ' ', trim((string)$s)); return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s); }
function kw_region_master(): array { return [
    "Muara Kuang" => [
        "Desa" => ["Kelampadu", "Kasah", "Kuang Anyar", "Munggu", "Nagasari", "Ramakasih", "Rantau Sialang", "Seri Kembang", "Seri Menanti", "Suka Cinta", "Sukajadi", "Tanabang Ilir", "Tanabang Ulu"],
        "Kelurahan" => ["Muara Kuang"],
    ],
    "Tanjung Batu" => [
        "Desa" => ["Bangun Jaya", "Burai", "Limbang Jaya I", "Limbang Jaya II", "Pajar Bulan", "Sentul", "Senuro Barat", "Senuro Timur", "Seri Bandung", "Seri Tanjung", "Tanjung Atap", "Tanjung Atap Barat", "Tanjung Baru Petai", "Tanjung Batu Seberang", "Tanjung Laut", "Tanjung Pinang I", "Tanjung Pinang II", "Tanjung Tambak", "Tanjung Tambak Baru"],
        "Kelurahan" => ["Tanjung Batu", "Tanjung Batu Timur"],
    ],
    "Tanjung Raja" => [
        "Desa" => ["Belanti", "Kerinjing", "Siring Alam", "Skonjing", "Seri Dalam", "Suka Pindah", "Talang Balai Baru I", "Talang Balai Baru II", "Talang Balai Lama", "Tanjung Agas", "Tanjung Harapan", "Tanjung Raja Selatan", "Tanjung Temiang", "Ulak Kerbau Baru", "Ulak Kerbau Lama"],
        "Kelurahan" => ["Tanjung Raja", "Tanjung Raja Barat", "Tanjung Raja Timur", "Tanjung Raja Utara"],
    ],
    "Indralaya" => [
        "Desa" => ["Lubuk Sakti", "Muara Penimbung Ilir", "Muara Penimbung Ulu", "Penyandingan", "Sakatiga", "Sakatiga Seberang", "Sejaro Sakti", "Sudimampir", "Talang Aur", "Tanjung Agung", "Tanjung Gelam", "Tanjung Sejaro", "Tanjung Seteko", "Tunas Aur", "Ulak Banding", "Ulak Bedil", "Ulak Segelung"],
        "Kelurahan" => ["Indralaya Indah", "Indralaya Mulya", "Indralaya Raya"],
    ],
    "Pemulutan" => [
        "Desa" => ["Aurstanding", "Babatan Saudagar", "Harapan", "Ibul Besar I", "Ibul Besar II", "Ibul Besar III", "Kedukan Bujang", "Lebung Jangkar", "Mekar Jaya", "Muara Baru", "Muara Dua", "Palu", "Pegayut", "Pelabuhan Dalam", "Pemulutan Ilir", "Pemulutan Ulu", "Pipa Putih", "Rawa Jaya", "Sembadak", "Simpang Pelabuhan Dalam", "Sukarami", "Sungai Buaya", "Sungai Rasau", "Tanjung Pasir", "Teluk Kecapi"],
    ],
    "Rantau Alai" => [
        "Desa" => ["Kelampaian", "Kerta Bayang", "Lebung Bandung", "Mekar Sari", "Rantau Alai", "Sanding Marga", "Sirah Pulau Kilip", "Sukamarga", "Sukamaju", "Sukananti", "Sukananti Baru", "Talang Sari", "Tanjung Mas"],
    ],
    "Indralaya Utara" => [
        "Desa" => ["Bakung", "Lorok", "Palem Raya", "Parit", "Payakabung", "Permata Baru", "Pulau Kabai", "Pulau Semambu", "Purnajaya", "Soak Batok", "Suka Mulia", "Sungai Rambutan", "Tanjung Baru", "Tanjung Pering", "Tanjung Pule"],
        "Kelurahan" => ["Timbangan"],
    ],
    "Indralaya Selatan" => [
        "Desa" => ["Arisan Gading", "Beti", "Mandi Angin", "Meranjat I", "Meranjat II", "Meranjat III", "Meranjat Ilir", "Sukaraja Baru", "Sukaraja Lama", "Tanjung Dayang Selatan", "Tanjung Dayang Utara", "Tanjung Lubuk", "Tebing Gerinting Selatan", "Tebing Gerinting Utara"],
    ],
    "Pemulutan Selatan" => [
        "Desa" => ["Cahaya Marga", "Harimau Tandang", "Kapuk", "Lebak Pering", "Maju Jaya", "Mayapati", "Naikan Tembakang", "Pematang Bangsal", "Pematang Bungur", "Segayam", "Sungai Keli", "Sungai Lebung", "Sungai Lebung Ulu", "Sungai Ondok", "Ulak Aurstanding"],
    ],
    "Pemulutan Barat" => [
        "Desa" => ["Arisan Jaya", "Kamal", "Pulau Negara", "Saranglang", "Sribanding", "Suka Merindu", "Talang Pangeran Ilir", "Talang Pangeran Ulu", "Ulak Kembahang I", "Ulak Kembahang II", "Ulak Petangisan"],
    ],
    "Rantau Panjang" => [
        "Desa" => ["Arisan Deras", "Jagalano", "Jagaraja", "Ketapang I", "Ketapang II", "Kota Daro I", "Kota Daro II", "Rantau Panjang Ilir", "Rantau Panjang Ulu", "Sejangko I", "Sejangko II", "Sungai Rotan"],
    ],
    "Sungai Pinang" => [
        "Desa" => ["Penyandingan", "Pinang Jaya", "Pinang Mas", "Serijabo", "Serijabo Baru", "Sungai Pinang I", "Sungai Pinang II", "Sungai Pinang III", "Sungai Pinang Lagati", "Sungai Pinang Nibung", "Talang Dukun", "Tanjung Serian"],
        "Kelurahan" => ["Sungai Pinang"],
    ],
    "Kandis" => [
        "Desa" => ["Kandis I", "Kandis II", "Kumbang Ilir", "Kumbang Ulu", "Lubuk Rukam", "Lubuk Segonang", "Miji", "Muara Kumbang", "Pandan Arang", "Santapan Barat", "Santapan Timur", "Tanjung Alai"],
    ],
    "Rambang Kuang" => [
        "Desa" => ["Beringin Dalam", "Ibul Dalam", "Kayu Ara", "Kuang Dalam Barat", "Kuang Dalam Timur", "Lubuk Tunggal", "Sukananti", "Sunur", "Tambang Rambang", "Tangai", "Tanjung Bulan", "Tanjung Miring", "Ulak Segara"],
    ],
    "Lubuk Keliat" => [
        "Desa" => ["Betung I", "Betung II", "Embacang", "Kasih Raja", "Ketiau", "Lubuk Keliat", "Payalingkung", "Talang Tengah Darat", "Talang Tengah Laut", "Ulak Kembahang"],
    ],
    "Payaraman" => [
        "Desa" => ["Lubuk Bandung", "Paya Besar", "Rengas I", "Rengas II", "Seri Kembang I", "Seri Kembang II", "Seri Kembang III", "Talang Seleman", "Tanjung Lalang", "Tebedak I", "Tebedak II"],
        "Kelurahan" => ["Payaraman Barat", "Payaraman Timur"],
    ],
]; }
function kw_region_label(string $kecamatan, string $wilayah): ?string {
    $master=kw_region_master();
    $kk=kw_region_key($kecamatan);
    $foundK=null;
    foreach($master as $name=>$groups){if(kw_region_key($name)===$kk){$foundK=$name;break;}}
    if($foundK===null)return null;
    // Data Progress Harian kadang sudah menyimpan prefix jenis wilayah.
    // Lepaskan prefix hanya untuk pencocokan; nama asli di database tidak diubah.
    $candidate=trim((string)$wilayah);
    $candidate=preg_replace('/^(?:desa|kelurahan)\s+/iu','',$candidate);
    $wk=kw_region_key($candidate);
    // Alias untuk variasi nama yang pernah ditemukan pada Progress Harian.
    // Pencocokan mengabaikan perbedaan kapitalisasi dan spasi berlebih.
    $aliases = [
        'Indralaya' => ['SUDI MAMPIR' => 'Sudimampir'],
        'Indralaya Utara' => ['SUAK BATOK' => 'Soak Batok', 'Pulau Kabal' => 'Pulau Kabai'],
        'Pemulutan' => ['SUKA RAMI' => 'Sukarami'],
        'Pemulutan Barat' => ['SERIBANDING' => 'Sribanding'],
        'Muara Kuang' => ['NAGA SARI' => 'Nagasari', 'RAMA KASIH' => 'Ramakasih'],
        'Rantau Alai' => ['SUKA NANTI' => 'Sukananti', 'KERTABAYANG' => 'Kerta Bayang'],
        'Rantau Panjang' => ['JAGALANA' => 'Jagalano'],
        'Lubuk Keliat' => ['TALANG TENGAH' => 'Talang Tengah Darat'],
    ];
    if (isset($aliases[$foundK])) {
        foreach ($aliases[$foundK] as $variant => $official) {
            if ($wk === kw_region_key($variant)) {
                $wk = kw_region_key($official);
                break;
            }
        }
    }
    // Cocokkan lagi dengan nama resmi tanpa membedakan spasi, agar variasi
    // seperti "NAGA SARI" dan "Nagasari" tetap merujuk ke satu nama resmi.
    $compact = static function(string $value): string {
        $value = preg_replace('/\s+/u', '', trim($value));
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    };
    foreach($master[$foundK] as $type=>$names){foreach($names as $official){if(kw_region_key($official)===$wk)return $type.' '.$official;}}
    foreach($master[$foundK] as $type=>$names){foreach($names as $official){if($compact($official)===$compact($candidate))return $type.' '.$official;}}
    return null;
}
function kw_region_destination(array $wilayah, string $kecamatan): ?string { $master=kw_region_master(); $kecamatanResmi=null; foreach(array_keys($master) as $namaKecamatan){if(kw_region_key($namaKecamatan)===kw_region_key($kecamatan)){$kecamatanResmi=$namaKecamatan;break;}} if($kecamatanResmi===null)return null; $labels=[]; foreach($wilayah as $name){$label=kw_region_label($kecamatanResmi,(string)$name); if($label===null)return null; if(!in_array($label,$labels,true))$labels[]=$label;} if(!$labels)return null; $n=count($labels); $dest=$n===1?$labels[0]:($n===2?$labels[0].' dan '.$labels[1]:implode(', ',array_slice($labels,0,-1)).' dan '.end($labels)); return $dest.' Kecamatan '.$kecamatanResmi; }

/**
 * Resolve the current destination from the authoritative daily progress details.
 * The progress schema has no kegiatan_id column; the caller must validate that the
 * task date is inside the requested activity period before calling this helper.
 * Returns [progress_id, villages, destination, error].
 */
function kw_region_current_progress(PDO $pdo, int $petugasId, int $kecamatanId, string $tanggal, string $kecamatan, ?int $kegiatanId = null): array {
    // Keep the cached DB field aligned, but update only the destination field for this exact receipt key.
    $syncDestination = static function (?string $destination) use ($pdo, $kegiatanId, $petugasId, $tanggal): void {
        if ($kegiatanId !== null && $kegiatanId > 0) {
            $update = $pdo->prepare('UPDATE kwitansi SET daerah_tujuan=? WHERE kegiatan_id=? AND petugas_id=? AND tanggal=?');
            $update->execute([$destination, $kegiatanId, $petugasId, $tanggal]);
        }
    };
    $q = $pdo->prepare('SELECT ph.id FROM progress_harian ph WHERE ph.petugas_id=? AND ph.kecamatan_id=? AND ph.tanggal=? LIMIT 1');
    $q->execute([$petugasId, $kecamatanId, $tanggal]);
    $progressId = $q->fetchColumn();
    if (!$progressId) { $syncDestination(null); return ['progress_id'=>null, 'villages'=>[], 'destination'=>null, 'error'=>'Progress Harian untuk petugas, kecamatan, dan tanggal ini tidak ditemukan.']; }

    $q = $pdo->prepare('SELECT TRIM(nama_desa) AS nama_desa FROM progress_sls WHERE progress_id=? AND petugas_id=? AND kecamatan_id=? AND tanggal=? AND TRIM(nama_desa)<>"" ORDER BY id ASC');
    $q->execute([(int)$progressId, $petugasId, $kecamatanId, $tanggal]);
    $raw = $q->fetchAll(PDO::FETCH_COLUMN);
    $villages = []; $seen = [];
    foreach ($raw as $name) {
        $name = trim((string)$name);
        if ($name === '') continue;
        $key = kw_region_key($name);
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        $villages[] = $name;
    }
    if (!$villages) { $syncDestination(null); return ['progress_id'=>(int)$progressId, 'villages'=>[], 'destination'=>null, 'error'=>'Progress Harian tidak memiliki detail Desa/Kelurahan yang valid.']; }
    foreach ($villages as $name) {
        if (kw_region_label($kecamatan, $name) === null) {
            $syncDestination(null); return ['progress_id'=>(int)$progressId, 'villages'=>$villages, 'destination'=>null, 'error'=>'Nama wilayah "'.$name.'" tidak ditemukan pada master Desa/Kelurahan untuk Kecamatan '.$kecamatan.'.'];
        }
    }
    $destination = kw_region_destination($villages, $kecamatan);
    if ($destination === null) { $syncDestination(null); return ['progress_id'=>(int)$progressId, 'villages'=>$villages, 'destination'=>null, 'error'=>'Tujuan perjalanan tidak dapat ditentukan dari master wilayah.']; }
    $syncDestination($destination);
    return ['progress_id'=>(int)$progressId, 'villages'=>$villages, 'destination'=>$destination, 'error'=>null];
}
