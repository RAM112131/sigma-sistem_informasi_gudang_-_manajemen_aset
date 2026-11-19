<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('penerimaan_barang_stores', 'pemakai')) {
            Schema::table('penerimaan_barang_stores', function (Blueprint $table) {
                $table->string('pemakai')->nullable()->after('pengambil_barang');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('penerimaan_barang_stores', 'pemakai')) {
            Schema::table('penerimaan_barang_stores', function (Blueprint $table) {
                $table->dropColumn('pemakai');
            });
        }
    }
};
