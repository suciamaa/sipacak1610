<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 80)->nullable()->unique()->after('name');
        });
        foreach (DB::table('users')->orderBy('id')->get(['id', 'email']) as $user) {
            $base = strtolower(preg_replace('/[^a-zA-Z0-9._-]/', '', explode('@', $user->email)[0] ?? 'user'));
            if (strlen($base) < 8) $base = str_pad($base ?: 'user', 8, '0');
            $candidate = substr($base, 0, 65) . $user->id;
            while (DB::table('users')->where('username', $candidate)->exists()) $candidate = substr($base, 0, 60) . $user->id . random_int(100, 999);
            DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
        }
        Schema::create('detail_progress_uraian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detail_progress_id')->constrained('detail_progress')->cascadeOnDelete();
            $table->foreignId('uraian_id')->constrained('master_uraian_lpd')->restrictOnDelete();
            $table->text('keterangan_lainnya')->nullable();
            $table->unique(['detail_progress_id', 'uraian_id'], 'uq_detail_uraian');
        });
        Schema::create('detail_progress_permasalahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detail_progress_id')->constrained('detail_progress')->cascadeOnDelete();
            $table->foreignId('permasalahan_id')->constrained('master_permasalahan_lpd')->restrictOnDelete();
            $table->text('keterangan_lainnya')->nullable();
            $table->unique(['detail_progress_id', 'permasalahan_id'], 'uq_detail_permasalahan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_progress_permasalahan');
        Schema::dropIfExists('detail_progress_uraian');
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
