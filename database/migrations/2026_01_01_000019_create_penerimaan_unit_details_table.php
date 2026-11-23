<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penerimaan_unit_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penerimaan_unit_id')->constrained('penerimaan_units')->cascadeOnDelete();
            $table->foreignId('barang_id')->nullable()->constrained('barangs')->nullOnDelete();
            $table->string('kode_barang')->nullable();
            $table->string('nama_barang');
            $table->string('kategori')->nullable();
            $table->string('satuan', 50);
            $table->decimal('qty_diterima', 14, 3);
            $table->string('kondisi', 50)->default('Baik');
            $table->decimal('qty_rusak', 14, 3)->default(0);
            $table->decimal('qty_kurang', 14, 3)->default(0);
            $table->decimal('qty_lebih', 14, 3)->default(0);
            $table->text('catatan')->nullable();
            $table->decimal('harga_beli', 14, 2)->default(0);
            $table->decimal('subtotal_nilai', 14, 2)->default(0);
            $table->decimal('qty_diperiksa', 14, 3)->nullable();
            $table->decimal('qty_rusak_diperiksa', 14, 3)->nullable();
            $table->timestamps();

            $table->index(['penerimaan_unit_id', 'barang_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penerimaan_unit_details');
    }
};
