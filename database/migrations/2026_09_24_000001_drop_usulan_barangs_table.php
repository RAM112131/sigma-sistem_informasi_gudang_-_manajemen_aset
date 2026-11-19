<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('usulan_barangs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Table usulan_barangs has been explicitly removed as per PRD specifications.
    }
};
