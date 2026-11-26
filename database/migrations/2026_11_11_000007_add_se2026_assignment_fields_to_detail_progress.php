<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('detail_progress', function (Blueprint $table) {
            $table->unsignedInteger('assignment_keluarga')->default(0)->after('jam_selesai');
            $table->unsignedInteger('assignment_usaha')->default(0)->after('assignment_keluarga');
            $table->unsignedInteger('assignment_usaha_dengan_keluarga')->default(0)->after('assignment_usaha');
            $table->unsignedInteger('jumlah_assignment')->default(0)->after('assignment_usaha_dengan_keluarga');
        });

        $activities = [
            ['Kepala Desa', 'penguasa_wilayah'],
            ['Lurah', 'penguasa_wilayah'],
            ['Ketua SLS', 'penguasa_wilayah'],
            ['Penelusuran Wilayah', 'aktivitas'],
            ['Konfirmasi Ulang Data', 'aktivitas'],
            ['Pendataan Lengkap', 'aktivitas'],
            ['Aktivitas Lainnya', 'aktivitas'],
        ];
        foreach ($activities as [$name, $type]) {
            $query = DB::table('master_uraian_lpd')->whereNull('kegiatan_id')->where('nama_uraian', $name);
            if ($query->exists()) {
                $query->update(['tipe_uraian' => $type, 'is_default' => true, 'is_active' => true, 'updated_at' => now()]);
            } else {
                DB::table('master_uraian_lpd')->insert([
                    'kegiatan_id' => null, 'nama_uraian' => $name, 'tipe_uraian' => $type,
                    'is_default' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        // Samakan label default lama tanpa menghapus relasi yang mungkin sudah dipakai.
        foreach ([
            'Kendala Aplikasi' => 'Kendala Aplikasi FASIH',
            'Kendala Responden' => 'Responden Sulit Ditemui',
            'Masalah Lainnya' => 'Kendala Lainnya',
        ] as $oldName => $newName) {
            $newExists = DB::table('master_permasalahan_lpd')
                ->whereNull('kegiatan_id')->where('nama_permasalahan', $newName)->exists();
            DB::table('master_permasalahan_lpd')
                ->whereNull('kegiatan_id')->where('nama_permasalahan', $oldName)
                ->update($newExists ? ['is_active' => false, 'updated_at' => now()] : ['nama_permasalahan' => $newName, 'updated_at' => now()]);
        }

        $problems = [
            'Kendala Aplikasi FASIH',
            'Responden Sulit Ditemui',
            'Perbedaan Data',
            'Kendala Lainnya',
        ];
        foreach ($problems as $name) {
            $query = DB::table('master_permasalahan_lpd')->whereNull('kegiatan_id')->where('nama_permasalahan', $name);
            if ($query->exists()) {
                $query->update(['is_default' => true, 'is_active' => true, 'updated_at' => now()]);
            } else {
                DB::table('master_permasalahan_lpd')->insert([
                    'kegiatan_id' => null, 'nama_permasalahan' => $name,
                    'is_default' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('detail_progress', function (Blueprint $table) {
            $table->dropColumn([
                'assignment_keluarga',
                'assignment_usaha',
                'assignment_usaha_dengan_keluarga',
                'jumlah_assignment',
            ]);
        });
    }
};
