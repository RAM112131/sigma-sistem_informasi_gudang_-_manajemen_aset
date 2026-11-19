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
        Schema::table('penerimaan_unit_details', function (Blueprint $table): void {
            $table->decimal('qty_diperiksa', 14, 3)->nullable()->after('qty_rusak');
            $table->decimal('qty_rusak_diperiksa', 14, 3)->nullable()->after('qty_diperiksa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penerimaan_unit_details', function (Blueprint $table): void {
            $table->dropColumn(['qty_diperiksa', 'qty_rusak_diperiksa']);
        });
    }
};
