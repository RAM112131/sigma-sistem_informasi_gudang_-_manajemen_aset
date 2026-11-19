<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

require_once __DIR__.'/UnitBisnisSeeder.php';

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database in modular sequence.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            GudangSeeder::class,
            UserSeeder::class,
            SatuanSeeder::class,
            KategoriSeeder::class,
            BarangSeeder::class,
            StoreTransaksiSeeder::class,
            UnitBisnisSeeder::class,
        ]);
    }
}
