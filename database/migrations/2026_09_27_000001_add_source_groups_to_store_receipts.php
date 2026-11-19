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
            if (! Schema::hasColumn('penerimaan_barang_stores', 'source_groups')) {
                $table->json('source_groups')->nullable()->after('sumber_supplier');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penerimaan_barang_stores', function (Blueprint $table) {
            if (Schema::hasColumn('penerimaan_barang_stores', 'source_groups')) {
                $table->dropColumn('source_groups');
            }
        });
    }
};
