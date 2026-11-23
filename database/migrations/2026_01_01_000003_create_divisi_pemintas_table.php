<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisi_pemintas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->constrained('gudangs')->cascadeOnDelete();
            $table->string('kode', 30)->nullable();
            $table->string('nama', 255);
            $table->string('penanggung_jawab', 100)->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();

            $table->unique(['gudang_id', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('divisi_pemintas');
    }
};
