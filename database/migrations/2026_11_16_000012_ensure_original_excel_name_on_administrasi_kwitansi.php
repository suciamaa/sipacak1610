<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('administrasi_kwitansi')
            && ! Schema::hasColumn('administrasi_kwitansi', 'file_excel_original_name')) {
            Schema::table('administrasi_kwitansi', function (Blueprint $table) {
                $table->string('file_excel_original_name', 255)->nullable()->after('file_excel_path');
            });
        }
    }

    public function down(): void
    {
        // Deliberately retain this metadata column to avoid breaking existing imports.
    }
};
