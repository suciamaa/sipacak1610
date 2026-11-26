<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('detail_progress', function (Blueprint $table) {
            $table->string('jenis_data_wilayah', 20)->default('sls')->after('jenis_wilayah');
            $table->string('blok_sensus', 20)->nullable()->after('sls_id');
        });
    }

    public function down(): void
    {
        Schema::table('detail_progress', function (Blueprint $table) {
            $table->dropColumn(['jenis_data_wilayah', 'blok_sensus']);
        });
    }
};
