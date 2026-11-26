<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void { Schema::create('activity_logs',function(Blueprint $t){$t->id();$t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();$t->string('aksi',40);$t->string('entitas',80);$t->unsignedBigInteger('entitas_id')->nullable();$t->json('data_sebelum')->nullable();$t->json('data_sesudah')->nullable();$t->string('ip_address',45)->nullable();$t->timestamps();$t->index(['entitas','entitas_id']);}); } public function down():void {Schema::dropIfExists('activity_logs');}};
