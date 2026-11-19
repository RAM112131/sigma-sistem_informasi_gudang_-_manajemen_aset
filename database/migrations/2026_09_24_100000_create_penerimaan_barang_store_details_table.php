<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Multi-Item Store Goods Receipts.
     */
    public function up(): void
    {
        // 1. Modify header table columns for flexibility
        Schema::table('penerimaan_barang_stores', function (Blueprint $table) {
            $table->foreignId('barang_id')->nullable()->change();
            $table->integer('qty_diterima')->default(0)->change();
            if (! Schema::hasColumn('penerimaan_barang_stores', 'catatan')) {
                $table->text('catatan')->nullable()->after('alasan_tanpa_sj');
            }
        });

        // 2. Create Details Table for multi-line receipt items
        if (! Schema::hasTable('penerimaan_barang_store_details')) {
            Schema::create('penerimaan_barang_store_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('penerimaan_barang_store_id')
                    ->constrained('penerimaan_barang_stores')
                    ->onDelete('cascade');
                $table->foreignId('barang_id')
                    ->constrained('barangs')
                    ->onDelete('cascade');
                $table->integer('qty_diterima');
                $table->foreignId('satuan_id')
                    ->nullable()
                    ->constrained('satuans')
                    ->nullOnDelete();
                $table->string('kondisi', 50)->default('Baik'); // Baik, Rusak, Kurang
                $table->text('catatan')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penerimaan_barang_store_details');
    }
};
