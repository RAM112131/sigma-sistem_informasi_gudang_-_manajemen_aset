<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengeluaran_unit_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengeluaran_unit_id')->constrained('pengeluaran_units')->cascadeOnDelete();
            $table->foreignId('barang_id')->nullable()->constrained('barangs')->nullOnDelete();
            $table->string('kode_barang')->nullable();
            $table->string('nama_barang');
            $table->string('kategori')->nullable();
            $table->string('satuan', 50);
            $table->decimal('qty', 14, 3);
            $table->text('catatan')->nullable();
            $table->decimal('harga_hpp', 15, 2)->default(0);
            $table->decimal('subtotal_nilai', 15, 2)->default(0);
            $table->decimal('qty_kembali', 14, 3)->default(0);
            $table->decimal('qty_kembali_rekonsiliasi', 14, 3)->nullable();
            $table->decimal('qty_keluar_dilaporkan', 14, 3)->nullable();
            $table->decimal('qty_keluar_rekonsiliasi', 14, 3)->nullable();
            $table->timestamps();

            $table->index(['pengeluaran_unit_id', 'barang_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluaran_unit_details');
    }
};
