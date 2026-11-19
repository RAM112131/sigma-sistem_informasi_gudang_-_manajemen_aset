<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Gudang;
use App\Models\KategoriBarang;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    /**
     * Run the database seeds for KategoriBarang (Item Categories).
     */
    public function run(): void
    {
        $gudangSukahati = Gudang::where('kode', 'SUKAHATI')->first();
        $gudangStore = Gudang::where('kode', 'STORE')->first();
        $gudangTamansari = Gudang::where('kode', 'TAMANSARI')->first();

        // Global Categories
        KategoriBarang::firstOrCreate(
            ['nama' => 'Bumbu & Bahan Olahan', 'gudang_id' => null],
            ['is_aktif' => true]
        );

        KategoriBarang::firstOrCreate(
            ['nama' => 'Bahan Pelengkap & Kemasan', 'gudang_id' => null],
            ['is_aktif' => true]
        );

        // Warehouse Specific Categories
        if ($gudangSukahati) {
            KategoriBarang::firstOrCreate(
                ['nama' => 'Karkas Ayam & Parting Sukahati', 'gudang_id' => $gudangSukahati->id],
                ['is_aktif' => true]
            );
        }

        if ($gudangStore) {
            KategoriBarang::firstOrCreate(
                ['nama' => 'Produk Olahan Frozen Ritel', 'gudang_id' => $gudangStore->id],
                ['is_aktif' => true]
            );
        }

        if ($gudangTamansari) {
            KategoriBarang::firstOrCreate(
                ['nama' => 'Ayam Pejantan & Ceker Tamansari', 'gudang_id' => $gudangTamansari->id],
                ['is_aktif' => true]
            );
        }
    }
}
