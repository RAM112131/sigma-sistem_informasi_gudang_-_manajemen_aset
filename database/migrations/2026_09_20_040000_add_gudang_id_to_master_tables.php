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
        // 1. Create barangs table with nullable gudang_id for Global vs Unit Business scoping
        if (! Schema::hasTable('barangs')) {
            Schema::create('barangs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gudang_id')->nullable()->constrained('gudangs')->nullOnDelete();
                $table->foreignId('kategori_id')->nullable()->constrained('kategori_barangs')->nullOnDelete();
                $table->foreignId('satuan_id')->nullable()->constrained('satuans')->nullOnDelete();
                $table->string('kode', 50)->unique();
                $table->string('nama');
                $table->integer('minimum_stok')->default(0);
                $table->decimal('harga_satuan', 15, 2)->default(0);
                $table->text('deskripsi')->nullable();
                $table->boolean('is_aktif')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Add nullable gudang_id to satuans table
        if (Schema::hasTable('satuans') && ! Schema::hasColumn('satuans', 'gudang_id')) {
            Schema::table('satuans', function (Blueprint $table) {
                $table->foreignId('gudang_id')->nullable()->after('id')->constrained('gudangs')->nullOnDelete();
            });
        }

        // 3. Make gudang_id in kategori_barangs table nullable
        if (Schema::hasTable('kategori_barangs') && Schema::hasColumn('kategori_barangs', 'gudang_id')) {
            Schema::table('kategori_barangs', function (Blueprint $table) {
                $table->foreignId('gudang_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barangs');

        if (Schema::hasTable('satuans') && Schema::hasColumn('satuans', 'gudang_id')) {
            Schema::table('satuans', function (Blueprint $table) {
                $table->dropForeign(['gudang_id']);
                $table->dropColumn('gudang_id');
            });
        }
    }
};
