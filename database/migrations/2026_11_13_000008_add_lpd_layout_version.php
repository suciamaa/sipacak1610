<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lpd', function (Blueprint $table) {
            $table->string('layout_version', 40)->default('legacy')->after('generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('lpd', function (Blueprint $table) {
            $table->dropColumn('layout_version');
        });
    }
};
