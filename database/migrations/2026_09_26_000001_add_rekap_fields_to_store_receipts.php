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
        Schema::table('penerimaan_barang_stores', function (Blueprint $table) {
            if (! Schema::hasColumn('penerimaan_barang_stores', 'pemesan')) {
                $table->string('pemesan')->nullable()->after('sumber_supplier');
            }
            if (! Schema::hasColumn('penerimaan_barang_stores', 'pengambil_barang')) {
                $table->string('pengambil_barang')->nullable()->after('pemesan');
            }
            if (! Schema::hasColumn('penerimaan_barang_stores', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('diterima_oleh')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('penerimaan_barang_stores', 'metode_pembayaran')) {
                $table->string('metode_pembayaran', 30)->default('Tunai')->after('user_id');
            }
            if (! Schema::hasColumn('penerimaan_barang_stores', 'catatan_rekap')) {
                $table->text('catatan_rekap')->nullable()->after('catatan');
            }
        });

        Schema::table('penerimaan_barang_store_details', function (Blueprint $table) {
            if (! Schema::hasColumn('penerimaan_barang_store_details', 'qty_rusak')) {
                $table->integer('qty_rusak')->default(0)->after('qty_diterima');
            }
            if (! Schema::hasColumn('penerimaan_barang_store_details', 'qty_kurang')) {
                $table->integer('qty_kurang')->default(0)->after('qty_rusak');
            }
            if (! Schema::hasColumn('penerimaan_barang_store_details', 'harga_satuan')) {
                $table->decimal('harga_satuan', 15, 2)->default(0)->after('qty_kurang');
            }
            if (! Schema::hasColumn('penerimaan_barang_store_details', 'total_harga')) {
                $table->decimal('total_harga', 15, 2)->default(0)->after('harga_satuan');
            }
            if (! Schema::hasColumn('penerimaan_barang_store_details', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('satuan_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penerimaan_barang_store_details', function (Blueprint $table) {
            $table->dropColumn(['qty_rusak', 'qty_kurang', 'harga_satuan', 'total_harga', 'user_id']);
        });

        Schema::table('penerimaan_barang_stores', function (Blueprint $table) {
            $table->dropColumn(['pemesan', 'pengambil_barang', 'user_id', 'metode_pembayaran', 'catatan_rekap']);
        });
    }
};
