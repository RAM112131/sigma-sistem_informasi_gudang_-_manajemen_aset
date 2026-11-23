<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stok_awals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->nullable()->constrained('gudangs')->nullOnDelete();
            $table->foreignId('barang_id')->constrained('barangs')->cascadeOnDelete();
            $table->decimal('qty', 15, 3);
            $table->decimal('harga_awal', 15, 2)->default(0);
            $table->date('tanggal_acuan');
            $table->text('catatan')->nullable();
            $table->string('sumber', 255)->default('manual');
            $table->string('file_asal_path')->nullable();
            $table->foreignId('diinput_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['gudang_id', 'barang_id'], 'unique_gudang_barang_stok_awal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_awals');
    }
};
