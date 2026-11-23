<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stok_opname_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stok_opname_id')->constrained('stok_opnames')->cascadeOnDelete();
            $table->foreignId('barang_id')->constrained('barangs')->restrictOnDelete();
            $table->string('kode_barang', 50);
            $table->string('nama_barang');
            $table->string('satuan', 100)->nullable();
            $table->decimal('qty_sistem', 14, 3);
            $table->decimal('qty_fisik', 14, 3)->nullable();
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamp('qty_sistem_at')->nullable();
            $table->timestamps();

            $table->unique(['stok_opname_id', 'barang_id'], 'stok_opname_details_stok_opname_id_barang_id_unique');
            $table->index('barang_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_opname_details');
    }
};
