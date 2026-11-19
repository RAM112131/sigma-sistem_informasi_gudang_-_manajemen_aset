<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stok_awals', function (Blueprint $table): void {
            $table->decimal('qty', 15, 3)->change();
            $table->decimal('harga_awal', 15, 2)->default(0)->after('qty');
        });
    }

    public function down(): void
    {
        $hasFractionalQuantity = DB::table('stok_awals')
            ->whereRaw('qty <> CAST(qty AS INTEGER)')
            ->exists();

        if ($hasFractionalQuantity) {
            throw new RuntimeException('Saldo awal berisi kuantitas pecahan; migrasi tidak dapat dibatalkan tanpa kehilangan presisi.');
        }

        Schema::table('stok_awals', function (Blueprint $table): void {
            $table->dropColumn('harga_awal');
            $table->integer('qty')->change();
        });
    }
};
