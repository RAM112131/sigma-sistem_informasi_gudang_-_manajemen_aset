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
        Schema::create('kwh_catatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->constrained('gudangs')->cascadeOnDelete();
            $table->date('tanggal');
            $table->decimal('lwbp_meter', 12, 2)->default(0);
            $table->decimal('wbp_meter', 12, 2)->default(0);
            $table->decimal('selisih_lwbp', 12, 2)->default(0);
            $table->decimal('selisih_wbp', 12, 2)->default(0);
            $table->decimal('ct_factor', 8, 2)->default(60.00);
            $table->decimal('tarif_lwbp', 10, 2)->default(972.00);
            $table->decimal('tarif_wbp', 10, 2)->default(1458.00);
            $table->decimal('nominal_lwbp', 14, 2)->default(0);
            $table->decimal('nominal_wbp', 14, 2)->default(0);
            $table->decimal('total_biaya', 14, 2)->default(0);
            $table->string('keterangan')->nullable();
            $table->foreignId('pencatat_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['gudang_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kwh_catatans');
    }
};
