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
        // 1. Standarisasi Divisi / Bagian Peminta (dari Sheet DATA USER Gudang Umum)
        if (! Schema::hasTable('divisi_pemintas')) {
            Schema::create('divisi_pemintas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('gudang_id')->constrained('gudangs')->cascadeOnDelete();
                $table->string('kode', 30)->nullable();
                $table->string('nama', 100);
                $table->string('penanggung_jawab', 100)->nullable();
                $table->boolean('is_aktif')->default(true);
                $table->timestamps();

                $table->unique(['gudang_id', 'nama']);
            });
        }

        // 2. Tambah relasi divisi_peminta_id di header pengeluaran_units
        if (Schema::hasTable('pengeluaran_units') && ! Schema::hasColumn('pengeluaran_units', 'divisi_peminta_id')) {
            Schema::table('pengeluaran_units', function (Blueprint $table) {
                $table->foreignId('divisi_peminta_id')->nullable()->after('bagian_peminta')->constrained('divisi_pemintas')->nullOnDelete();
            });
        }

        // 3. Tambah kolom harga_hpp dan subtotal_nilai di pengeluaran_unit_details
        if (Schema::hasTable('pengeluaran_unit_details')) {
            Schema::table('pengeluaran_unit_details', function (Blueprint $table) {
                if (! Schema::hasColumn('pengeluaran_unit_details', 'harga_hpp')) {
                    $table->decimal('harga_hpp', 15, 2)->default(0)->after('qty');
                }
                if (! Schema::hasColumn('pengeluaran_unit_details', 'subtotal_nilai')) {
                    $table->decimal('subtotal_nilai', 15, 2)->default(0)->after('harga_hpp');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pengeluaran_unit_details')) {
            Schema::table('pengeluaran_unit_details', function (Blueprint $table) {
                if (Schema::hasColumn('pengeluaran_unit_details', 'subtotal_nilai')) {
                    $table->dropColumn('subtotal_nilai');
                }
                if (Schema::hasColumn('pengeluaran_unit_details', 'harga_hpp')) {
                    $table->dropColumn('harga_hpp');
                }
            });
        }

        if (Schema::hasTable('pengeluaran_units') && Schema::hasColumn('pengeluaran_units', 'divisi_peminta_id')) {
            Schema::table('pengeluaran_units', function (Blueprint $table) {
                $table->dropConstrainedForeignId('divisi_peminta_id');
            });
        }

        Schema::dropIfExists('divisi_pemintas');
    }
};
