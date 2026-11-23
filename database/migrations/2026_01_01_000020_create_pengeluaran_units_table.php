<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengeluaran_units', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_bpb')->unique();
            $table->foreignId('gudang_id')->constrained('gudangs')->restrictOnDelete();
            $table->foreignId('divisi_peminta_id')->nullable()->constrained('divisi_pemintas')->nullOnDelete();
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
            $table->index(['gudang_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluaran_units');
    }
};
