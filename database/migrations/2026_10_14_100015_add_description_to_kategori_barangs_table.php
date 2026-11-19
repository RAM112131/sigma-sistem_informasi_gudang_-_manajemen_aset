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
        Schema::table('kategori_barangs', function (Blueprint $table): void {
            if (! Schema::hasColumn('kategori_barangs', 'deskripsi')) {
                $table->string('deskripsi', 200)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kategori_barangs', function (Blueprint $table): void {
            if (Schema::hasColumn('kategori_barangs', 'deskripsi')) {
                $table->dropColumn('deskripsi');
            }
        });
    }
};
