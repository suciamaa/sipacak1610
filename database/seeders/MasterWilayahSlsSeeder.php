<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kecamatan;
use Illuminate\Support\Facades\DB;

class MasterWilayahSlsSeeder extends Seeder
{
    private function key(string $value): string
    {
        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper(trim($value)));
    }

    public function run(): void
    {
        // Ejaan sumber lama yang jelas merupakan variasi penulisan dari master wilayah pada prompt.
        $villageAliases = [
            'PULAU KABAL' => 'Pulau Kabai',
            'SUAK BATOK' => 'Soak Batok',
            'SERIBANDING' => 'Sribanding',
            'JAGALANA' => 'Jagalano',
        ];
        $districts = json_decode(file_get_contents(database_path('data/wilayah_ogan_ilir.json')), true);
        $legacySls = json_decode(file_get_contents(database_path('data/desa_sls.json')), true) ?: [];
        $legacySub = json_decode(file_get_contents(database_path('data/sub_sls.json')), true) ?: [];
        $districtsWithSlsReference = [
            'Indralaya', 'Indralaya Selatan', 'Indralaya Utara', 'Lubuk Keliat',
            'Muara Kuang', 'Payaraman', 'Pemulutan', 'Rambang Kuang',
            'Sungai Pinang', 'Tanjung Batu', 'Tanjung Raja',
        ];

        foreach ($districts as $districtName => $groups) {
            $kecamatan = Kecamatan::firstOrCreate(['nama_kecamatan' => $districtName]);
            $slsDistrict = [];
            foreach ($legacySls as $name => $villages) {
                if (!in_array($districtName, $districtsWithSlsReference, true)) break;
                if ($this->key($name) === $this->key($districtName)) { $slsDistrict = $villages; break; }
            }
            $subDistrict = [];
            foreach ($legacySub as $name => $villages) {
                if (!in_array($districtName, $districtsWithSlsReference, true)) break;
                if ($this->key($name) === $this->key($districtName)) { $subDistrict = $villages; break; }
            }

            foreach (['desa' => $groups['desa'], 'kelurahan' => $groups['kelurahan']] as $type => $names) {
                foreach ($names as $name) {
                    $village = DB::table('master_desa_kelurahan')->updateOrInsert(
                        ['kecamatan_id' => $kecamatan->id, 'nama_wilayah' => $name],
                        ['jenis_wilayah' => $type, 'updated_at' => now(), 'created_at' => now()]
                    );
                }
            }

            foreach ($slsDistrict as $villageName => $slsNames) {
                $village = DB::table('master_desa_kelurahan')
                    ->where('kecamatan_id', $kecamatan->id)
                    ->get()->first(fn($v) => $this->key($v->nama_wilayah) === $this->key($villageAliases[mb_strtoupper($villageName)] ?? $villageName));
                if (!$village) continue;
                foreach ($slsNames as $slsName) {
                    DB::table('master_sls')->updateOrInsert(
                        ['desa_kelurahan_id' => $village->id, 'nmsls' => $slsName],
                        ['updated_at' => now(), 'created_at' => now()]
                    );
                }
            }

            foreach ($subDistrict as $villageName => $slsRows) {
                $village = DB::table('master_desa_kelurahan')
                    ->where('kecamatan_id', $kecamatan->id)
                    ->get()->first(fn($v) => $this->key($v->nama_wilayah) === $this->key($villageAliases[mb_strtoupper($villageName)] ?? $villageName));
                if (!$village) continue;
                foreach ($slsRows as $slsName => $subNames) {
                    $sls = DB::table('master_sls')->where('desa_kelurahan_id', $village->id)->where('nmsls', $slsName)->first();
                    if (!$sls) {
                        DB::table('master_sls')->insert(['desa_kelurahan_id'=>$village->id,'nmsls'=>$slsName,'created_at'=>now(),'updated_at'=>now()]);
                        $sls = DB::table('master_sls')->where('desa_kelurahan_id', $village->id)->where('nmsls', $slsName)->first();
                    }
                    foreach ($subNames as $subName) {
                        DB::table('master_sub_sls')->updateOrInsert(
                            ['sls_id'=>$sls->id,'nmsubsls'=>(string)$subName],
                            ['updated_at'=>now(),'created_at'=>now()]
                        );
                    }
                }
            }
            foreach (array_unique(array_merge(array_keys($slsDistrict), array_keys($subDistrict))) as $sourceVillage) {
                $targetName = $villageAliases[mb_strtoupper($sourceVillage)] ?? $sourceVillage;
                $exists = DB::table('master_desa_kelurahan')->where('kecamatan_id', $kecamatan->id)
                    ->get()->contains(fn($v) => $this->key($v->nama_wilayah) === $this->key($targetName));
                if (!$exists && $this->command) {
                    $this->command->warn("Data SLS untuk '{$districtName} / {$sourceVillage}' belum dipetakan karena nama desa tidak cocok dengan master wilayah.");
                }
            }
        }
    }
}
