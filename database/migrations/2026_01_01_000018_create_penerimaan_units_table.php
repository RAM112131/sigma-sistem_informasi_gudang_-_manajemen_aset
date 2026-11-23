<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
    }

    public function down(): void
    {
        Schema::dropIfExists('penerimaan_units');
    }
};
