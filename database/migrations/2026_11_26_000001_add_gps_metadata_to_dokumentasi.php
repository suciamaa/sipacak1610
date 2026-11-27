<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dokumentasi', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('ukuran');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->decimal('gps_accuracy', 10, 2)->nullable()->after('longitude');
            $table->timestamp('captured_at')->nullable()->after('gps_accuracy');
            $table->string('captured_timezone', 64)->nullable()->after('captured_at');
            $table->timestamp('server_received_at')->nullable()->after('captured_timezone');
            $table->string('capture_source', 20)->nullable()->after('server_received_at');
        });
    }

    public function down(): void
    {
        Schema::table('dokumentasi', function (Blueprint $table) {
            $table->dropColumn([
                'latitude', 'longitude', 'gps_accuracy', 'captured_at',
                'captured_timezone', 'server_received_at', 'capture_source',
            ]);
        });
    }
};
