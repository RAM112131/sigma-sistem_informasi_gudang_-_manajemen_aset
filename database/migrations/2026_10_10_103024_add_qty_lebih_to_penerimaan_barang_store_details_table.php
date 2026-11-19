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
        Schema::table('penerimaan_barang_store_details', function (Blueprint $table) {
            if (! Schema::hasColumn('penerimaan_barang_store_details', 'qty_lebih')) {
                $table->unsignedInteger('qty_lebih')->default(0)->after('qty_kurang');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penerimaan_barang_store_details', function (Blueprint $table) {
            if (Schema::hasColumn('penerimaan_barang_store_details', 'qty_lebih')) {
                $table->dropColumn('qty_lebih');
            }
        });
    }
};
