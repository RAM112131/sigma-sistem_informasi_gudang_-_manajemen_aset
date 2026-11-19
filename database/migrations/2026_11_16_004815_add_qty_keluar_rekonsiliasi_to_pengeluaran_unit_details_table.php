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
            $table->decimal('qty_keluar_dilaporkan', 14, 3)->nullable();
            $table->decimal('qty_keluar_rekonsiliasi', 14, 3)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pengeluaran_unit_details', function (Blueprint $table): void {
            $table->dropColumn(['qty_keluar_dilaporkan', 'qty_keluar_rekonsiliasi']);
        });
    }
};
