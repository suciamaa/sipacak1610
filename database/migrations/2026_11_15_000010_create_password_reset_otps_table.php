<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('password_reset_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('otp_hash')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('request_window_started_at')->nullable();
            $table->unsignedTinyInteger('request_count')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_otps');
    }
};
