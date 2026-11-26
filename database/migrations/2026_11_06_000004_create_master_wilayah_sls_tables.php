<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('master_desa_kelurahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kecamatan_id')->constrained('kecamatan')->cascadeOnDelete();
            $table->string('nama_wilayah', 150);
            $table->enum('jenis_wilayah', ['desa', 'kelurahan']);
            $table->timestamps();
            $table->unique(['kecamatan_id', 'nama_wilayah']);
            $table->index(['kecamatan_id', 'jenis_wilayah']);
        });
        Schema::create('master_sls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_kelurahan_id')->constrained('master_desa_kelurahan')->cascadeOnDelete();
            $table->string('nmsls', 180);
            $table->timestamps();
            $table->unique(['desa_kelurahan_id', 'nmsls']);
        });
        Schema::create('master_sub_sls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sls_id')->constrained('master_sls')->cascadeOnDelete();
            $table->string('nmsubsls', 180);
            $table->timestamps();
            $table->unique(['sls_id', 'nmsubsls']);
        });
        Schema::table('detail_progress', function (Blueprint $table) {
            $table->foreignId('desa_kelurahan_id')->nullable()->after('desa')->constrained('master_desa_kelurahan')->nullOnDelete();
            $table->foreignId('sls_id')->nullable()->after('sls')->constrained('master_sls')->nullOnDelete();
            $table->foreignId('sub_sls_id')->nullable()->after('sls_id')->constrained('master_sub_sls')->nullOnDelete();
            $table->string('jenis_wilayah', 20)->nullable()->after('desa_kelurahan_id');
        });
    }

    public function down(): void
    {
        Schema::table('detail_progress', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sub_sls_id');
            $table->dropConstrainedForeignId('sls_id');
            $table->dropConstrainedForeignId('desa_kelurahan_id');
            $table->dropColumn('jenis_wilayah');
        });
        Schema::dropIfExists('master_sub_sls');
        Schema::dropIfExists('master_sls');
        Schema::dropIfExists('master_desa_kelurahan');
    }
};
