<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kecamatan;
use App\Models\MasterUraianLpd;
use App\Models\MasterPermasalahanLpd;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Indralaya','Indralaya Utara','Indralaya Selatan','Pemulutan','Pemulutan Barat','Pemulutan Selatan','Tanjung Raja','Tanjung Batu','Payaraman','Rantau Panjang','Sungai Pinang','Kandis','Rantau Alai','Lubuk Keliat','Muara Kuang','Rambang Kuang'] as $nama) {
            Kecamatan::firstOrCreate(['nama_kecamatan' => $nama]);
        }
        foreach ([
            ['nama_uraian'=>'Kepala Desa','tipe_uraian'=>'penguasa_wilayah'],
            ['nama_uraian'=>'Lurah','tipe_uraian'=>'penguasa_wilayah'],
            ['nama_uraian'=>'Ketua SLS','tipe_uraian'=>'penguasa_wilayah'],
            ['nama_uraian'=>'Penelusuran Wilayah','tipe_uraian'=>'aktivitas'],
            ['nama_uraian'=>'Konfirmasi Ulang Data','tipe_uraian'=>'aktivitas'],
            ['nama_uraian'=>'Pendataan Lengkap','tipe_uraian'=>'aktivitas'],
            ['nama_uraian'=>'Aktivitas Lainnya','tipe_uraian'=>'aktivitas'],
        ] as $item) {
            MasterUraianLpd::firstOrCreate(['kegiatan_id'=>null,'nama_uraian'=>$item['nama_uraian']], $item + ['is_default'=>true,'is_active'=>true]);
        }
        foreach (['Kendala Aplikasi FASIH','Responden Sulit Ditemui','Perbedaan Data','Kendala Lainnya'] as $nama) {
            MasterPermasalahanLpd::firstOrCreate(['kegiatan_id'=>null,'nama_permasalahan'=>$nama], ['is_default'=>true,'is_active'=>true]);
        }
        $this->call(MasterWilayahSlsSeeder::class);
    }
}
