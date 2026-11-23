<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stok_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->constrained('gudangs')->restrictOnDelete();
            $table->date('periode');
            $table->date('tanggal_efektif');
            $table->string('status', 20)->default('draft');
            $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diterapkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diterapkan_at')->nullable();
            $table->timestamps();

            $table->unique(['gudang_id', 'periode'], 'stok_opnames_gudang_id_periode_unique');
            $table->index(['gudang_id', 'status', 'tanggal_efektif'], 'stok_opnames_gudang_id_status_tanggal_efektif_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_opnames');
    }
};
