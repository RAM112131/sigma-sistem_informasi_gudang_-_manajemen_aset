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
        Schema::create('penerimaan_units', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_rcv')->unique();
            $table->foreignId('gudang_id')->constrained('gudangs')->restrictOnDelete();
            $table->string('nama_supplier');
            $table->date('tanggal_terima');
            $table->time('waktu_terima');
            $table->string('po_referensi')->nullable();
            $table->string('nomor_surat_jalan')->nullable();
            $table->boolean('has_surat_jalan')->default(true);
            $table->text('alasan_tanpa_sj')->nullable();
            $table->string('foto_surat_jalan_path')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('diterima_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status_verifikasi', 30)->default('tercatat');
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_at')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();

            $table->index(['gudang_id', 'tanggal_terima']);
        });

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
            $table->timestamps();

            $table->index(['penerimaan_unit_id', 'barang_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penerimaan_unit_details');
        Schema::dropIfExists('penerimaan_units');
    }
};
