<?php

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
        Schema::table('penerimaan_barang_stores', function (Blueprint $table) {
            $table->timestamp('follow_up_completed_at')->nullable();
            $table->foreignId('follow_up_completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('follow_up_note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penerimaan_barang_stores', function (Blueprint $table) {
            $table->dropForeign(['follow_up_completed_by']);
            $table->dropColumn(['follow_up_completed_at', 'follow_up_completed_by', 'follow_up_note']);
        });
    }
};
