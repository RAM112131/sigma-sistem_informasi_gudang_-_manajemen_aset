<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Satuan;
use Illuminate\Database\Seeder;

class SatuanSeeder extends Seeder
{
    /**
     * Run the database seeds for Satuan (Units of Measure).
     */
    public function run(): void
    {
        $satuans = [
            ['kode' => 'BH', 'nama' => 'Buah'],
            ['kode' => 'BKS', 'nama' => 'Bungkus'],
            ['kode' => 'BOX', 'nama' => 'Box'],
            ['kode' => 'BTL', 'nama' => 'Botol'],
            ['kode' => 'BUKU', 'nama' => 'Buku'],
            ['kode' => 'DUS', 'nama' => 'Dus'],
            ['kode' => 'EKOR', 'nama' => 'Ekor'],
            ['kode' => 'GLN', 'nama' => 'Galon'],
            ['kode' => 'KG', 'nama' => 'Kilogram'],
            ['kode' => 'KLG', 'nama' => 'Kaleng'],
            ['kode' => 'LITER', 'nama' => 'Liter'],
            ['kode' => 'MTR', 'nama' => 'Meter'],
            ['kode' => 'PACK', 'nama' => 'Pack'],
            ['kode' => 'PCS', 'nama' => 'Pieces'],
            ['kode' => 'PSG', 'nama' => 'Pasang'],
            ['kode' => 'RIM', 'nama' => 'Rim'],
            ['kode' => 'ROLL', 'nama' => 'Roll'],
            ['kode' => 'SET', 'nama' => 'Set'],
            ['kode' => 'TABUNG', 'nama' => 'Tabung'],
        ];

        foreach ($satuans as $s) {
            Satuan::firstOrCreate(
                ['kode' => $s['kode']],
                ['nama' => $s['nama'], 'is_aktif' => true]
            );
        }
    }
}
