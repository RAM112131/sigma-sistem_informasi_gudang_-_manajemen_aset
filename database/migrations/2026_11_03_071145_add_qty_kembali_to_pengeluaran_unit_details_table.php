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
        Schema::table('pengeluaran_unit_details', function (Blueprint $table) {
            if (! Schema::hasColumn('pengeluaran_unit_details', 'qty_kembali')) {
                $table->decimal('qty_kembali', 14, 3)->default(0)->after('qty');
            }
        });

        if (! Schema::hasTable('pengembalian_units')) {
            Schema::create('pengembalian_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gudang_id')->constrained('gudangs')->cascadeOnDelete();
                $table->foreignId('pengeluaran_unit_id')->nullable()->constrained('pengeluaran_units')->cascadeOnDelete();
                $table->foreignId('pengeluaran_unit_detail_id')->nullable()->constrained('pengeluaran_unit_details')->nullOnDelete();
                $table->foreignId('barang_id')->nullable()->constrained('barangs')->nullOnDelete();
                $table->string('bagian_peminta');
                $table->string('nama_barang');
                $table->string('satuan', 50)->default('unit');
                $table->decimal('qty_kembali', 14, 3);
                $table->date('tanggal_kembali');
                $table->text('catatan')->nullable();
                $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['gudang_id', 'tanggal_kembali']);
                $table->index(['gudang_id', 'bagian_peminta']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengembalian_units');

        Schema::table('pengeluaran_unit_details', function (Blueprint $table) {
            if (Schema::hasColumn('pengeluaran_unit_details', 'qty_kembali')) {
                $table->dropColumn('qty_kembali');
            }
        });
    }
};
