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
        Schema::table('penerimaan_unit_details', function (Blueprint $table) {
            $table->decimal('harga_beli', 14, 2)->default(0)->after('qty_diterima');
            $table->decimal('subtotal_nilai', 14, 2)->default(0)->after('harga_beli');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penerimaan_unit_details', function (Blueprint $table) {
            $table->dropColumn(['harga_beli', 'subtotal_nilai']);
        });
    }
};
