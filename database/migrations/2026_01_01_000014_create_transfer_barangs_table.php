<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_barangs', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_sj')->unique();
            $table->foreignId('permintaan_barang_id')->nullable()->constrained('permintaan_barangs')->nullOnDelete();
            $table->foreignId('gudang_asal_id')->constrained('gudangs')->cascadeOnDelete();
            $table->foreignId('gudang_tujuan_id')->nullable()->constrained('gudangs')->cascadeOnDelete();
            $table->foreignId('barang_id')->constrained('barangs')->cascadeOnDelete();
            $table->integer('qty_diminta')->default(0);
            $table->integer('qty_dikirim')->default(0);
            $table->integer('qty_diterima')->default(0);
            $table->string('status')->default('diminta');
            $table->string('foto_bukti_path')->nullable();
            $table->string('supir_pengantar')->nullable();
            $table->foreignId('dikirim_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diterima_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dikirim_at')->nullable();
            $table->timestamp('diterima_at')->nullable();
            $table->timestamps();

            $table->index(['barang_id', 'status'], 'idx_transfer_barang_status');
            $table->index('gudang_asal_id', 'idx_transfer_gudang_asal');
            $table->index('gudang_tujuan_id', 'idx_transfer_gudang_tujuan');
            $table->index('status', 'idx_transfer_status');
            $table->index(['gudang_asal_id', 'barang_id', 'status'], 'idx_transfer_store_item_status');
            $table->index(['gudang_asal_id', 'status', 'created_at'], 'idx_transfer_store_status_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_barangs');
    }
};
