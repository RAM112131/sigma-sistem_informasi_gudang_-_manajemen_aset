<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penerimaan_barang_store_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penerimaan_barang_store_id')->constrained('penerimaan_barang_stores')->cascadeOnDelete();
            $table->foreignId('barang_id')->constrained('barangs')->cascadeOnDelete();
            $table->integer('qty_diterima');
            $table->foreignId('satuan_id')->nullable()->constrained('satuans')->nullOnDelete();
            $table->string('kondisi', 50)->default('Baik');
            $table->text('catatan')->nullable();
            $table->integer('qty_rusak')->default(0);
            $table->integer('qty_kurang')->default(0);
            $table->integer('qty_lebih')->default(0);
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->decimal('total_harga', 15, 2)->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('pemakai')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penerimaan_barang_store_details');
    }
};
