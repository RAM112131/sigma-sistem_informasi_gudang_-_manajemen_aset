<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penerimaan_barang_stores', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_penerimaan')->unique();
            $table->foreignId('gudang_id')->constrained('gudangs')->cascadeOnDelete();
            $table->foreignId('barang_id')->nullable()->constrained('barangs')->cascadeOnDelete();
            $table->integer('qty_diterima')->default(0);
            $table->foreignId('satuan_id')->nullable()->constrained('satuans')->nullOnDelete();
            $table->string('po_referensi')->nullable();
            $table->string('sumber_supplier')->nullable();
            $table->string('foto_dokumen_path')->nullable();
            $table->boolean('tanpa_surat_jalan')->default(false);
            $table->text('alasan_tanpa_sj')->nullable();
            $table->foreignId('diterima_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_diterima')->useCurrent();
            $table->text('catatan')->nullable();
            $table->string('pemesan')->nullable();
            $table->string('pengambil_barang')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('metode_pembayaran', 30)->default('Tunai');
            $table->text('catatan_rekap')->nullable();
            $table->string('pemakai')->nullable();
            $table->json('source_groups')->nullable();
            $table->timestamp('follow_up_completed_at')->nullable();
            $table->foreignId('follow_up_completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('follow_up_note')->nullable();
            $table->timestamps();

            $table->index('barang_id', 'idx_penerimaan_barang_id');
            $table->index('gudang_id', 'idx_penerimaan_gudang_id');
            $table->index('tanggal_diterima', 'idx_penerimaan_tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penerimaan_barang_stores');
    }
};
