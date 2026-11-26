<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->string('nomor_kegiatan', 150)->nullable()->after('nama_kegiatan');
        });

        Schema::table('administrasi_kwitansi', function (Blueprint $table) {
            $table->string('nip_bendahara_pengeluaran', 30)->nullable()->after('nama_bendahara_pengeluaran');
            $table->string('nip_pejabat_pembuat_komitmen', 30)->nullable()->after('nama_pejabat_pembuat_komitmen');
        });
    }

    public function down(): void
    {
        Schema::table('administrasi_kwitansi', function (Blueprint $table) {
            $table->dropColumn(['nip_bendahara_pengeluaran', 'nip_pejabat_pembuat_komitmen']);
        });
        Schema::table('kegiatan', function (Blueprint $table) {
            $table->dropColumn('nomor_kegiatan');
        });
    }
};
