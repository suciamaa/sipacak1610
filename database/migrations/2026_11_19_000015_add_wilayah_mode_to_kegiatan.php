<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->string('jenis_data_wilayah', 20)->default('sls')->after('tujuan_akhir');
        });

        // Pertahankan mode Blok Sensus pada kegiatan lama yang sudah memiliki data tersebut.
        $activitiesWithBlocks = DB::table('penugasan')
            ->join('progress_harian', 'progress_harian.penugasan_id', '=', 'penugasan.id')
            ->join('detail_progress', 'detail_progress.progress_id', '=', 'progress_harian.id')
            ->where('detail_progress.jenis_data_wilayah', 'blok_sensus')
            ->select('penugasan.kegiatan_id');
        DB::table('kegiatan')->whereIn('id', $activitiesWithBlocks)->update(['jenis_data_wilayah' => 'blok_sensus']);
    }

    public function down(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->dropColumn('jenis_data_wilayah');
        });
    }
};
