<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('petugas', 'desa_kelurahan_asal_id')) {
            return;
        }

        Schema::table('petugas', function (Blueprint $table) {
            $table->foreignId('desa_kelurahan_asal_id')
                ->nullable()
                ->after('kecamatan_asal_id')
                ->constrained('master_desa_kelurahan')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('petugas', 'desa_kelurahan_asal_id')) {
            return;
        }

        Schema::table('petugas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('desa_kelurahan_asal_id');
        });
    }
};
