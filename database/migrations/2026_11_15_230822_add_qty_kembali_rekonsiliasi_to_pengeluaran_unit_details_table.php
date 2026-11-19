<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengeluaran_unit_details', function (Blueprint $table): void {
            $table->decimal('qty_kembali_rekonsiliasi', 14, 3)->nullable()->after('qty_kembali');
        });
    }

    public function down(): void
    {
        Schema::table('pengeluaran_unit_details', function (Blueprint $table): void {
            $table->dropColumn('qty_kembali_rekonsiliasi');
        });
    }
};
