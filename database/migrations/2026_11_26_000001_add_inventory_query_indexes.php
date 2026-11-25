<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barangs', function (Blueprint $table): void {
            $table->index(
                ['gudang_id', 'is_aktif', 'deleted_at', 'nama'],
                'barangs_gudang_active_deleted_name_idx',
            );
        });

        Schema::table('kategori_barangs', function (Blueprint $table): void {
            $table->index(
                ['gudang_id', 'is_aktif', 'deleted_at', 'nama'],
                'kategori_gudang_active_deleted_name_idx',
            );
        });

        Schema::table('penerimaan_barang_stores', function (Blueprint $table): void {
            $table->index(
                ['gudang_id', 'tanggal_diterima'],
                'penerimaan_store_gudang_received_idx',
            );
        });

        Schema::table('transfer_barangs', function (Blueprint $table): void {
            $table->index(
                ['gudang_asal_id', 'dikirim_at'],
                'transfer_store_sent_at_idx',
            );
        });

        Schema::table('penerimaan_units', function (Blueprint $table): void {
            $table->index(
                ['gudang_id', 'status_verifikasi', 'diverifikasi_at'],
                'penerimaan_gudang_verified_at_idx',
            );
        });

        Schema::table('penerimaan_pemindahan_units', function (Blueprint $table): void {
            $table->index(
                ['gudang_id', 'status_verifikasi', 'diverifikasi_at'],
                'pemindahan_gudang_verified_at_idx',
            );
        });

        Schema::table('penerimaan_barang_store_details', function (Blueprint $table): void {
            $table->index(
                'penerimaan_barang_store_id',
                'penerimaan_store_details_header_idx',
            );
            $table->index(
                ['barang_id', 'penerimaan_barang_store_id'],
                'penerimaan_store_details_barang_header_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('penerimaan_barang_store_details', function (Blueprint $table): void {
            $table->dropIndex('penerimaan_store_details_barang_header_idx');
            $table->dropIndex('penerimaan_store_details_header_idx');
        });

        Schema::table('penerimaan_barang_stores', function (Blueprint $table): void {
            $table->dropIndex('penerimaan_store_gudang_received_idx');
        });

        Schema::table('transfer_barangs', function (Blueprint $table): void {
            $table->dropIndex('transfer_store_sent_at_idx');
        });

        Schema::table('penerimaan_pemindahan_units', function (Blueprint $table): void {
            $table->dropIndex('pemindahan_gudang_verified_at_idx');
        });

        Schema::table('penerimaan_units', function (Blueprint $table): void {
            $table->dropIndex('penerimaan_gudang_verified_at_idx');
        });

        Schema::table('kategori_barangs', function (Blueprint $table): void {
            $table->dropIndex('kategori_gudang_active_deleted_name_idx');
        });

        Schema::table('barangs', function (Blueprint $table): void {
            $table->dropIndex('barangs_gudang_active_deleted_name_idx');
        });
    }
};
