<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('penerimaan_barang_store_details') && ! Schema::hasColumn('penerimaan_barang_store_details', 'pemakai')) {
            Schema::table('penerimaan_barang_store_details', function (Blueprint $table) {
                $table->string('pemakai')->nullable()->after('kondisi');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('penerimaan_barang_store_details') && Schema::hasColumn('penerimaan_barang_store_details', 'pemakai')) {
            Schema::table('penerimaan_barang_store_details', function (Blueprint $table) {
                $table->dropColumn('pemakai');
            });
        }
    }
};
