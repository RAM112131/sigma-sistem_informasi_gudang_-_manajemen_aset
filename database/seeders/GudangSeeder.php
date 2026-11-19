<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Gudang;
use Illuminate\Database\Seeder;

class GudangSeeder extends Seeder
{
    /**
     * Run the database seeds for Gudangs (Warehouses).
     * Gudang Armada and Gudang Store Armada are unified into a single warehouse entry.
     */
    public function run(): void
    {
        // 1. Gudang Store Armada (Holding & Global Master)
        Gudang::firstOrCreate(
            ['kode' => 'STORE'],
            [
                'nama' => 'Gudang Store Armada',
                'jenis' => 'gudang_store',
                'is_aktif' => true,
            ]
        );

        // 2. RPA Sukahati (Unit Bisnis / Gudang Umum)
        Gudang::firstOrCreate(
            ['kode' => 'SUKAHATI'],
            [
                'nama' => 'RPA Sukahati',
                'jenis' => 'gudang_umum',
                'is_aktif' => true,
            ]
        );
    }
}
