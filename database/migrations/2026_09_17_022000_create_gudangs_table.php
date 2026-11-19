<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('nama');
            $table->string('jenis')->default('gudang_sukahati');
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('user_gudang', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('gudang_id')->constrained('gudangs')->cascadeOnDelete();
            $table->primary(['user_id', 'gudang_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_gudang');
        Schema::dropIfExists('gudangs');
    }
};
