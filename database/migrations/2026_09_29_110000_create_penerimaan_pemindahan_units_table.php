<?php

declare(strict_types=1);

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
            $table->unsignedInteger('qty_dikirim');
            $table->unsignedInteger('qty_diterima');
            $table->unsignedInteger('qty_rusak')->default(0);
            $table->text('catatan_discrepancy')->nullable();
            $table->foreignId('diterima_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diterima_at');
            $table->timestamps();

            $table->index(['gudang_id', 'diterima_at']);
            $table->index(['barang_id', 'diterima_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penerimaan_pemindahan_units');
    }
};
