<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('detail_progress', function (Blueprint $table) {
            $table->json('kegiatan_rincian')->nullable()->after('kegiatan_uraian');
            $table->json('output_rincian')->nullable()->after('kegiatan_rincian');
        });
    }

    public function down(): void
    {
        Schema::table('detail_progress', function (Blueprint $table) {
            $table->dropColumn(['kegiatan_rincian', 'output_rincian']);
        });
    }
};
