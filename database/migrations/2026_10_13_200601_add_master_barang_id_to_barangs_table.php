<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('barangs') || Schema::hasColumn('barangs', 'master_barang_id')) {
            return;
        }

        Schema::table('barangs', function (Blueprint $table): void {
            $table->foreignId('master_barang_id')
                ->nullable()
                ->constrained('master_barangs')
                ->nullOnDelete();
            $table->unique(['master_barang_id', 'gudang_id'], 'barangs_master_barang_gudang_unique');
        });

        DB::table('barangs')
            ->select([
                'id',
                'nama',
                'kategori_id',
                'satuan_id',
                'harga_satuan',
                'deskripsi',
                'foto_path',
                'is_aktif',
                'created_at',
                'updated_at',
                'deleted_at',
            ])
            ->orderBy('id')
            ->chunkById(200, function ($barangs): void {
                foreach ($barangs as $barang) {
                    $now = now();
                    $masterBarangId = DB::table('master_barangs')->insertGetId([
                        'kode' => null,
                        'nama' => $barang->nama,
                        'kategori_id' => $barang->kategori_id,
                        'satuan_id' => $barang->satuan_id,
                        'harga_satuan' => $barang->harga_satuan ?? 0,
                        'deskripsi' => $barang->deskripsi,
                        'foto_path' => $barang->foto_path,
                        'is_aktif' => $barang->is_aktif,
                        'created_at' => $barang->created_at ?? $now,
                        'updated_at' => $barang->updated_at ?? $now,
                        'deleted_at' => $barang->deleted_at,
                    ]);

                    DB::table('barangs')
                        ->where('id', $barang->id)
                        ->update(['master_barang_id' => $masterBarangId]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('barangs') || ! Schema::hasColumn('barangs', 'master_barang_id')) {
            return;
        }

        Schema::table('barangs', function (Blueprint $table): void {
            $table->dropUnique('barangs_master_barang_gudang_unique');
            $table->dropConstrainedForeignId('master_barang_id');
        });
    }
};
