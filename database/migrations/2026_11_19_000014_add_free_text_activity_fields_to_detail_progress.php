<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Form Input Progress versi baru: setiap SLS/BS memiliki 4 kegiatan
 * (perjalanan, koordinasi penguasa wilayah, jenis kegiatan, output kegiatan)
 * dengan uraian teks bebas. Kolom jam_mulai/jam_selesai lama tetap dipakai
 * sebagai rentang gabungan agar LPD, kwitansi, dan monitoring tidak berubah.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('detail_progress', function (Blueprint $table) {
            $table->time('perjalanan_jam_mulai')->nullable()->after('jam_selesai');
            $table->time('perjalanan_jam_selesai')->nullable()->after('perjalanan_jam_mulai');
            $table->text('perjalanan_uraian')->nullable()->after('perjalanan_jam_selesai');
            $table->time('koordinasi_jam_mulai')->nullable()->after('perjalanan_uraian');
            $table->time('koordinasi_jam_selesai')->nullable()->after('koordinasi_jam_mulai');
            $table->text('koordinasi_uraian')->nullable()->after('koordinasi_jam_selesai');
            $table->time('kegiatan_jam_mulai')->nullable()->after('koordinasi_uraian');
            $table->time('kegiatan_jam_selesai')->nullable()->after('kegiatan_jam_mulai');
            $table->text('kegiatan_uraian')->nullable()->after('kegiatan_jam_selesai');
            $table->text('output_uraian')->nullable()->after('kegiatan_uraian');
            $table->text('permasalahan_uraian')->nullable()->after('output_uraian');
        });
    }

    public function down(): void
    {
        Schema::table('detail_progress', function (Blueprint $table) {
            $table->dropColumn([
                'perjalanan_jam_mulai', 'perjalanan_jam_selesai', 'perjalanan_uraian',
                'koordinasi_jam_mulai', 'koordinasi_jam_selesai', 'koordinasi_uraian',
                'kegiatan_jam_mulai', 'kegiatan_jam_selesai', 'kegiatan_uraian',
                'output_uraian', 'permasalahan_uraian',
            ]);
        });
    }
};
