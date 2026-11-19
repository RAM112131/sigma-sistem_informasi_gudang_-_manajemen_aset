<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permintaan_barangs', function (Blueprint $table): void {
            $table->index(
                ['gudang_tujuan_id', 'status', 'created_at'],
                'idx_permintaan_store_status_created'
            );
        });

        Schema::table('permintaan_barang_details', function (Blueprint $table): void {
            $table->index(
                ['permintaan_barang_id', 'barang_id'],
                'idx_permintaan_detail_request_item'
            );
        });

        Schema::table('transfer_barangs', function (Blueprint $table): void {
            $table->index(
                ['gudang_asal_id', 'status', 'created_at'],
                'idx_transfer_store_status_created'
            );
            $table->index(
                ['gudang_asal_id', 'barang_id', 'status'],
                'idx_transfer_store_item_status'
            );
        });
    }

    public function down(): void
    {
        Schema::table('permintaan_barangs', function (Blueprint $table): void {
            $table->dropIndex('idx_permintaan_store_status_created');
        });

        Schema::table('permintaan_barang_details', function (Blueprint $table): void {
            $table->dropIndex('idx_permintaan_detail_request_item');
        });

        Schema::table('transfer_barangs', function (Blueprint $table): void {
            $table->dropIndex('idx_transfer_store_status_created');
            $table->dropIndex('idx_transfer_store_item_status');
        });
    }
};
