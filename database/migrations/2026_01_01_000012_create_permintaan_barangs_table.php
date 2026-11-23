<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_barangs', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pr')->unique();
            $table->foreignId('gudang_peminta_id')->constrained('gudangs')->cascadeOnDelete();
            $table->foreignId('gudang_tujuan_id')->constrained('gudangs')->cascadeOnDelete();
            $table->string('status')->default('baru');
            $table->text('catatan')->nullable();
            $table->text('alasan_penolakan')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status', 'idx_permintaan_status');
            $table->index('gudang_peminta_id', 'idx_permintaan_gudang_peminta');
            $table->index(['gudang_tujuan_id', 'status', 'created_at'], 'idx_permintaan_store_status_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_barangs');
    }
};
