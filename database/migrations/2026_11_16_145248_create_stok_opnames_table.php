<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stok_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->constrained('gudangs')->restrictOnDelete();
            $table->date('periode');
            $table->date('tanggal_efektif');
            $table->string('status', 20)->default('draft');
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diterapkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diterapkan_at')->nullable();
            $table->timestamps();

            $table->unique(['gudang_id', 'periode']);
            $table->index(['gudang_id', 'status', 'tanggal_efektif']);
        });

        Schema::create('stok_opname_details', function (Blueprint $table): void {
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
            $table->timestamps();

            $table->unique(['stok_opname_id', 'barang_id']);
            $table->index('barang_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_opname_details');
        Schema::dropIfExists('stok_opnames');
    }
};
