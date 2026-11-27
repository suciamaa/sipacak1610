<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dokumentasi', function (Blueprint $table) {
            $table->string('desa', 120)->nullable()->after('capture_source');
            $table->string('kecamatan', 120)->nullable()->after('desa');
            $table->string('kabupaten', 120)->nullable()->after('kecamatan');
            $table->string('provinsi', 120)->nullable()->after('kabupaten');
            $table->decimal('altitude', 8, 2)->nullable()->after('provinsi');
            $table->decimal('speed_kmh', 8, 2)->nullable()->after('altitude');
        });
    }

    public function down(): void
    {
        Schema::table('dokumentasi', function (Blueprint $table) {
            $table->dropColumn(['desa', 'kecamatan', 'kabupaten', 'provinsi', 'altitude', 'speed_kmh']);
        });
    }
};
