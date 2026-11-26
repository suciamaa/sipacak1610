<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name', 150); $table->string('email')->unique();
            $table->string('password'); $table->enum('role', ['admin','petugas'])->default('petugas');
            $table->timestamp('email_verified_at')->nullable(); $table->rememberToken(); $table->timestamps();
        });
        Schema::create('kecamatan', function (Blueprint $table) {
            $table->id(); $table->string('nama_kecamatan', 100)->unique(); $table->timestamps();
        });
        Schema::create('petugas', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nama_lengkap', 150); $table->foreignId('kecamatan_asal_id')->nullable()->constrained('kecamatan')->nullOnDelete();
            $table->string('nomor_whatsapp', 25); $table->timestamps();
        });
        Schema::create('kegiatan', function (Blueprint $table) {
            $table->id(); $table->string('nama_kegiatan', 150); $table->date('tanggal_mulai'); $table->date('tanggal_selesai');
            $table->enum('status', ['aktif','nonaktif'])->default('aktif'); $table->text('deskripsi')->nullable();
            $table->enum('tujuan_akhir', ['lpd','kwitansi','keduanya']); $table->timestamps();
        });
        Schema::create('kegiatan_roles', function (Blueprint $table) {
            $table->id(); $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->enum('role', ['PPL','PML','Supervisor']); $table->unique(['kegiatan_id','role']);
        });
        Schema::create('penugasan', function (Blueprint $table) {
            $table->id(); $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->foreignId('petugas_id')->constrained('petugas')->cascadeOnDelete();
            $table->foreignId('kecamatan_id')->constrained('kecamatan')->restrictOnDelete();
            $table->enum('role', ['PPL','PML','Supervisor']); $table->enum('status_penugasan', ['aktif','nonaktif'])->default('aktif');
            $table->timestamps(); $table->unique(['kegiatan_id','petugas_id','kecamatan_id'], 'uq_penugasan_kegiatan_petugas_kecamatan');
            $table->index(['kegiatan_id','role']);
        });
        Schema::create('progress_harian', function (Blueprint $table) {
            $table->id(); $table->foreignId('penugasan_id')->constrained('penugasan')->cascadeOnDelete();
            $table->date('tanggal'); $table->timestamps(); $table->unique(['penugasan_id','tanggal'], 'uq_progress_penugasan_tanggal');
        });
        Schema::create('detail_progress', function (Blueprint $table) {
            $table->id(); $table->foreignId('progress_id')->constrained('progress_harian')->cascadeOnDelete();
            $table->string('desa',150); $table->string('sls',150); $table->time('jam_mulai'); $table->time('jam_selesai');
            $table->text('pemecahan_masalah')->nullable(); $table->unsignedInteger('jumlah_responden')->nullable();
            $table->unsignedInteger('jumlah_responden_diawasi')->nullable(); $table->unsignedInteger('jumlah_dokumen_diperiksa')->nullable();
            $table->timestamps(); $table->index(['desa','sls']);
        });
        Schema::create('master_uraian_lpd', function (Blueprint $table) {
            $table->id(); $table->foreignId('kegiatan_id')->nullable()->constrained('kegiatan')->cascadeOnDelete();
            $table->string('nama_uraian',180); $table->string('tipe_uraian',60)->default('aktivitas');
            $table->boolean('is_default')->default(false); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('master_permasalahan_lpd', function (Blueprint $table) {
            $table->id(); $table->foreignId('kegiatan_id')->nullable()->constrained('kegiatan')->cascadeOnDelete();
            $table->string('nama_permasalahan',180); $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('progress_uraian', function (Blueprint $table) {
            $table->id(); $table->foreignId('progress_id')->constrained('progress_harian')->cascadeOnDelete();
            $table->foreignId('uraian_id')->constrained('master_uraian_lpd')->restrictOnDelete();
            $table->text('keterangan_lainnya')->nullable(); $table->unique(['progress_id','uraian_id']);
        });
        Schema::create('progress_permasalahan', function (Blueprint $table) {
            $table->id(); $table->foreignId('progress_id')->constrained('progress_harian')->cascadeOnDelete();
            $table->foreignId('permasalahan_id')->constrained('master_permasalahan_lpd')->restrictOnDelete();
            $table->text('keterangan_lainnya')->nullable(); $table->unique(['progress_id','permasalahan_id']);
        });
        Schema::create('dokumentasi', function (Blueprint $table) {
            $table->id(); $table->foreignId('detail_progress_id')->constrained('detail_progress')->cascadeOnDelete();
            $table->string('nama_file'); $table->string('file_path',500); $table->string('mime_type',100); $table->unsignedInteger('ukuran');
            $table->timestamps();
        });
        Schema::create('lpd', function (Blueprint $table) {
            $table->id(); $table->foreignId('penugasan_id')->constrained('penugasan')->cascadeOnDelete(); $table->date('tanggal');
            $table->enum('status_lpd',['belum_dibuat','sudah_dibuat'])->default('belum_dibuat'); $table->string('file_pdf',500)->nullable();
            $table->timestamp('generated_at')->nullable(); $table->timestamps(); $table->unique(['penugasan_id','tanggal'],'uq_lpd_penugasan_tanggal');
        });
        Schema::create('kwitansi', function (Blueprint $table) {
            $table->id(); $table->foreignId('penugasan_id')->constrained('penugasan')->cascadeOnDelete(); $table->date('tanggal');
            $table->enum('status_kwitansi',['belum_dibuat','sudah_dibuat'])->default('belum_dibuat'); $table->string('file_pdf',500)->nullable();
            $table->timestamp('generated_at')->nullable(); $table->timestamps(); $table->unique(['penugasan_id','tanggal'],'uq_kwitansi_penugasan_tanggal');
        });
        Schema::create('administrasi_kwitansi', function (Blueprint $table) {
            $table->id(); $table->foreignId('kegiatan_id')->unique()->constrained('kegiatan')->cascadeOnDelete();
            $table->string('nama_bendahara_pengeluaran',150)->nullable(); $table->string('nama_pejabat_pembuat_komitmen',150)->nullable();
            $table->string('file_excel_path',500)->nullable(); $table->timestamp('uploaded_at')->nullable(); $table->timestamps();
        });
        Schema::create('data_excel_kwitansi', function (Blueprint $table) {
            $table->id(); $table->foreignId('administrasi_kwitansi_id')->constrained('administrasi_kwitansi')->cascadeOnDelete();
            $table->foreignId('penugasan_id')->nullable()->constrained('penugasan')->nullOnDelete(); $table->date('tanggal')->nullable();
            $table->string('nomor_kwitansi',150)->nullable(); $table->json('data_administrasi')->nullable();
            $table->enum('status_matching',['belum_dicocokkan','cocok','tidak_cocok'])->default('belum_dicocokkan');
            $table->timestamps(); $table->index(['penugasan_id','tanggal']);
        });
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary(); $table->string('token'); $table->timestamp('created_at')->nullable();
        });
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary(); $table->foreignId('user_id')->nullable()->index(); $table->string('ip_address',45)->nullable();
            $table->text('user_agent')->nullable(); $table->longText('payload'); $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        foreach (['sessions','password_reset_tokens','data_excel_kwitansi','administrasi_kwitansi','kwitansi','lpd','dokumentasi','progress_permasalahan','progress_uraian','master_permasalahan_lpd','master_uraian_lpd','detail_progress','progress_harian','penugasan','kegiatan_roles','kegiatan','petugas','kecamatan','users'] as $table) Schema::dropIfExists($table);
    }
};
