<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengembalian_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->constrained('gudangs')->cascadeOnDelete();
            $table->foreignId('pengeluaran_unit_id')->nullable()->constrained('pengeluaran_units')->cascadeOnDelete();
            $table->foreignId('pengeluaran_unit_detail_id')->nullable()->constrained('pengeluaran_unit_details')->nullOnDelete();
            $table->foreignId('barang_id')->nullable()->constrained('barangs')->nullOnDelete();
            $table->string('bagian_peminta');
            $table->string('nama_barang');
            $table->string('satuan', 50)->default('unit');
            $table->decimal('qty_kembali', 14, 3);
            $table->date('tanggal_kembali');
            $table->text('catatan')->nullable();
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['gudang_id', 'bagian_peminta']);
            $table->index(['gudang_id', 'tanggal_kembali']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengembalian_units');
    }
};
