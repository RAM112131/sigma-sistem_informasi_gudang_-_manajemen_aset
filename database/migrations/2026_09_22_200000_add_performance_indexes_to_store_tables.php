<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for high performance indexes.
     */
    public function up(): void
    {
        Schema::table('penerimaan_barang_stores', function (Blueprint $table) {
            $table->index('barang_id', 'idx_penerimaan_barang_id');
            $table->index('gudang_id', 'idx_penerimaan_gudang_id');
            $table->index('tanggal_diterima', 'idx_penerimaan_tanggal');
        });

        Schema::table('transfer_barangs', function (Blueprint $table) {
            $table->index(['barang_id', 'status'], 'idx_transfer_barang_status');
            $table->index('gudang_asal_id', 'idx_transfer_gudang_asal');
            $table->index('gudang_tujuan_id', 'idx_transfer_gudang_tujuan');
            $table->index('status', 'idx_transfer_status');
        });

        Schema::table('permintaan_barangs', function (Blueprint $table) {
            $table->index('status', 'idx_permintaan_status');
            $table->index('gudang_peminta_id', 'idx_permintaan_gudang_peminta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penerimaan_barang_stores', function (Blueprint $table) {
            $table->dropIndex('idx_penerimaan_barang_id');
            $table->dropIndex('idx_penerimaan_gudang_id');
            $table->dropIndex('idx_penerimaan_tanggal');
        });

        Schema::table('transfer_barangs', function (Blueprint $table) {
            $table->dropIndex('idx_transfer_barang_status');
            $table->dropIndex('idx_transfer_gudang_asal');
            $table->dropIndex('idx_transfer_gudang_tujuan');
            $table->dropIndex('idx_transfer_status');
        });

        Schema::table('permintaan_barangs', function (Blueprint $table) {
            $table->dropIndex('idx_permintaan_status');
            $table->dropIndex('idx_permintaan_gudang_peminta');
        });
    }
};
