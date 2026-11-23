<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_barang_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_barang_id')->constrained('permintaan_barangs')->cascadeOnDelete();
            $table->foreignId('barang_id')->constrained('barangs')->cascadeOnDelete();
            $table->integer('qty_diminta');
            $table->integer('qty_diproses')->default(0);
            $table->foreignId('satuan_id')->nullable()->constrained('satuans')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['permintaan_barang_id', 'barang_id'], 'idx_permintaan_detail_request_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_barang_details');
    }
};
