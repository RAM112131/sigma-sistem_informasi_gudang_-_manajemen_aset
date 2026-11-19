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
        Schema::create('pengeluaran_units', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_bpb')->unique();
            $table->foreignId('gudang_id')->constrained('gudangs')->restrictOnDelete();
            $table->date('tanggal');
            $table->time('waktu_keluar');
            $table->string('bagian_peminta');
            $table->text('catatan')->nullable();
            $table->string('entry_mode', 20)->default('live');
            $table->string('status_verifikasi', 30)->default('menunggu_verifikasi');
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_at')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();

            $table->index(['gudang_id', 'status_verifikasi', 'tanggal']);
        });

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
            $table->timestamps();

            $table->index(['pengeluaran_unit_id', 'barang_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengeluaran_unit_details');
        Schema::dropIfExists('pengeluaran_units');
    }
};
