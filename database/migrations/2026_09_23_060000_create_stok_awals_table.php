<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stok_awals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->nullable()->constrained('gudangs')->nullOnDelete();
            $table->foreignId('barang_id')->constrained('barangs')->cascadeOnDelete();
            $table->integer('qty');
            $table->date('tanggal_acuan');
            $table->text('catatan')->nullable();
            $table->enum('sumber', ['manual', 'import'])->default('manual');
            $table->string('file_asal_path')->nullable();
            $table->foreignId('diinput_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Guardrail: Each item in a warehouse can only have ONE initial stock record
            $table->unique(['gudang_id', 'barang_id'], 'unique_gudang_barang_stok_awal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_awals');
    }
};
