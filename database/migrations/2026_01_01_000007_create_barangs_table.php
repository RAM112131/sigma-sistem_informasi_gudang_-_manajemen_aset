<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->nullable()->constrained('gudangs')->nullOnDelete();
            $table->foreignId('master_barang_id')->nullable()->constrained('master_barangs')->nullOnDelete();
            $table->foreignId('kategori_id')->nullable()->constrained('kategori_barangs')->nullOnDelete();
            $table->foreignId('satuan_id')->nullable()->constrained('satuans')->nullOnDelete();
            $table->string('kode', 50);
            $table->string('nama');
            $table->integer('minimum_stok')->default(0);
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->text('deskripsi')->nullable();
            $table->string('foto_path')->nullable();
            $table->string('lokasi_rak', 100)->nullable();
            $table->boolean('is_aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['gudang_id', 'kode'], 'barangs_gudang_id_kode_unique');
            $table->unique(['master_barang_id', 'gudang_id'], 'barangs_master_barang_gudang_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barangs');
    }
};
