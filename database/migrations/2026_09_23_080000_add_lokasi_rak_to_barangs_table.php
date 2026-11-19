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
        if (Schema::hasTable('barangs') && ! Schema::hasColumn('barangs', 'lokasi_rak')) {
            Schema::table('barangs', function (Blueprint $table) {
                $table->string('lokasi_rak', 100)->nullable()->after('foto_path');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('barangs') && Schema::hasColumn('barangs', 'lokasi_rak')) {
            Schema::table('barangs', function (Blueprint $table) {
                $table->dropColumn('lokasi_rak');
            });
        }
    }
};
