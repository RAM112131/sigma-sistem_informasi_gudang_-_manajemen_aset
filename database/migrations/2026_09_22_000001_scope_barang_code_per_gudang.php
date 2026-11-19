<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('barangs')) {
            return;
        }

        Schema::table('barangs', function (Blueprint $table): void {
            $table->dropUnique('barangs_kode_unique');
            $table->unique(['gudang_id', 'kode'], 'barangs_gudang_id_kode_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('barangs')) {
            return;
        }

        Schema::table('barangs', function (Blueprint $table): void {
            $table->dropUnique('barangs_gudang_id_kode_unique');
            $table->unique('kode', 'barangs_kode_unique');
        });
    }
};
