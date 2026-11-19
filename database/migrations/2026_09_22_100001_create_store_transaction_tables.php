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
        // 1. Permintaan Barang dari Unit Bisnis ke Gudang Store
        Schema::create('permintaan_barangs', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pr')->unique();
            $table->foreignId('gudang_peminta_id')->constrained('gudangs')->onDelete('cascade');
            $table->foreignId('gudang_tujuan_id')->constrained('gudangs')->onDelete('cascade');
            $table->enum('status', ['baru', 'diproses', 'selesai', 'ditolak'])->default('baru');
            $table->text('catatan')->nullable();
            $table->text('alasan_penolakan')->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Detail Permintaan Barang
        Schema::create('permintaan_barang_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_barang_id')->constrained('permintaan_barangs')->onDelete('cascade');
            $table->foreignId('barang_id')->constrained('barangs')->onDelete('cascade');
            $table->integer('qty_diminta');
            $table->integer('qty_diproses')->default(0);
            $table->foreignId('satuan_id')->nullable()->constrained('satuans')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        // 4. Transfer / Pemindahan Barang (Store -> Unit Bisnis)
        Schema::create('transfer_barangs', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_sj')->unique();
            $table->foreignId('permintaan_barang_id')->nullable()->constrained('permintaan_barangs')->nullOnDelete();
            $table->foreignId('gudang_asal_id')->constrained('gudangs')->onDelete('cascade');
            $table->foreignId('gudang_tujuan_id')->constrained('gudangs')->onDelete('cascade');
            $table->foreignId('barang_id')->constrained('barangs')->onDelete('cascade');
            $table->integer('qty_diminta')->default(0);
            $table->integer('qty_dikirim')->default(0);
            $table->integer('qty_diterima')->default(0);
            $table->enum('status', ['diminta', 'dikirim', 'diterima'])->default('diminta');
            $table->string('foto_bukti_path')->nullable();
            $table->string('supir_pengantar')->nullable();
            $table->foreignId('dikirim_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diterima_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dikirim_at')->nullable();
            $table->timestamp('diterima_at')->nullable();
            $table->timestamps();
        });

        // 5. Penerimaan Barang Store dari Supplier Eksternal
        Schema::create('penerimaan_barang_stores', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_penerimaan')->unique();
            $table->foreignId('gudang_id')->constrained('gudangs')->onDelete('cascade');
            $table->foreignId('barang_id')->constrained('barangs')->onDelete('cascade');
            $table->integer('qty_diterima');
            $table->foreignId('satuan_id')->nullable()->constrained('satuans')->nullOnDelete();
            $table->string('po_referensi')->nullable();
            $table->string('sumber_supplier')->nullable();
            $table->string('foto_dokumen_path')->nullable();
            $table->boolean('tanpa_surat_jalan')->default(false);
            $table->text('alasan_tanpa_sj')->nullable();
            $table->foreignId('diterima_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_diterima')->useCurrent();
            $table->timestamps();
        });

        // 6. Satuan Konversi Bertingkat per Barang
        Schema::create('satuan_konversis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barangs')->onDelete('cascade');
            $table->foreignId('satuan_turunan_id')->constrained('satuans')->onDelete('cascade');
            $table->decimal('rasio_konversi', 10, 2)->default(1);
            $table->string('label_konversi')->nullable(); // misal: "1 Dus = 24 PCS"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('satuan_konversis');
        Schema::dropIfExists('penerimaan_barang_stores');
        Schema::dropIfExists('transfer_barangs');
        Schema::dropIfExists('permintaan_barang_details');
        Schema::dropIfExists('permintaan_barangs');
    }
};
