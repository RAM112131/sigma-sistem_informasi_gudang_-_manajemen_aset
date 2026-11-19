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
        Schema::table('penerimaan_pemindahan_units', function (Blueprint $table) {
            $table->decimal('qty_diperiksa', 14, 3)->nullable();
            $table->decimal('qty_rusak_diperiksa', 14, 3)->nullable();
            $table->string('status_verifikasi', 30)->default('menunggu_verifikasi');
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diverifikasi_at')->nullable();
            $table->text('catatan_verifikasi')->nullable();

            $table->index(
                ['gudang_id', 'status_verifikasi', 'diterima_at'],
                'ppu_gudang_status_diterima_idx',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penerimaan_pemindahan_units', function (Blueprint $table) {
            $table->dropForeign(['diverifikasi_oleh']);
            $table->dropIndex('ppu_gudang_status_diterima_idx');
            $table->dropColumn([
                'qty_diperiksa',
                'qty_rusak_diperiksa',
                'status_verifikasi',
                'diverifikasi_oleh',
                'diverifikasi_at',
                'catatan_verifikasi',
            ]);
        });
    }
};
