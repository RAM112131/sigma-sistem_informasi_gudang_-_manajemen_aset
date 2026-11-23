<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penerimaan_pemindahan_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_barang_id')->unique()->constrained('transfer_barangs')->restrictOnDelete();
            $table->foreignId('gudang_id')->constrained('gudangs')->restrictOnDelete();
            $table->foreignId('barang_id')->constrained('barangs')->restrictOnDelete();
            $table->integer('qty_dikirim');
            $table->integer('qty_diterima');
            $table->integer('qty_rusak')->default(0);
            $table->text('catatan_discrepancy')->nullable();
            $table->foreignId('diterima_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diterima_at');
            $table->decimal('qty_diperiksa', 14, 3)->nullable();
            $table->decimal('qty_rusak_diperiksa', 14, 3)->nullable();
            $table->string('status_verifikasi', 30)->default('menunggu_verifikasi');
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_at')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();

            $table->index(['gudang_id', 'diterima_at']);
            $table->index(['barang_id', 'diterima_at']);
            $table->index(['gudang_id', 'status_verifikasi', 'diterima_at'], 'ppu_gudang_status_diterima_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penerimaan_pemindahan_units');
    }
};
