<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\MasterBarang;
use App\Models\Satuan;
use Illuminate\Database\Seeder;

class BarangSeeder extends Seeder
{
    /**
     * Run the database seeds for Barang (Master Items).
     */
    public function run(): void
    {
        $gudangSukahati = Gudang::where('kode', 'SUKAHATI')->first();
        $gudangStore = Gudang::where('kode', 'STORE')->first();

        $satuanKg = Satuan::where('kode', 'KG')->first();
        $satuanPcs = Satuan::where('kode', 'PCS')->first();
        $satuanEkor = Satuan::where('kode', 'EKOR')->first();
        $satuanRoll = Satuan::where('kode', 'ROLL')->first();
        $satuanTabung = Satuan::where('kode', 'TABUNG')->first();
        $satuanBalok = Satuan::where('kode', 'BALOK')->first();
        $satuanPack = Satuan::where('kode', 'PACK')->first();

        $katOlahan = KategoriBarang::where('nama', 'Bumbu & Bahan Olahan')->first();
        $katKemasan = KategoriBarang::where('nama', 'Bahan Pelengkap & Kemasan')->first();
        $katAyamSukahati = KategoriBarang::where('nama', 'Karkas Ayam & Parting Sukahati')->first();
        $katStoreRitel = KategoriBarang::where('nama', 'Produk Olahan Frozen Ritel')->first();
        $katTamansariAyam = KategoriBarang::where('nama', 'Ayam Pejantan & Ceker Tamansari')->first();

        $itemsData = [
            // Global Master Items (Holding / Shared)
            ['kode' => 'BM-001', 'nama' => 'Bumbu Marinasi Ayam Frozen', 'gudang_id' => null, 'kategori_id' => $katOlahan?->id, 'satuan_id' => $satuanKg?->id, 'minimum_stok' => 30],
            ['kode' => 'PL-012', 'nama' => 'Plastik Vacuum Roll 20x30 cm', 'gudang_id' => null, 'kategori_id' => $katKemasan?->id, 'satuan_id' => $satuanRoll?->id, 'minimum_stok' => 20],
            ['kode' => 'BB-004', 'nama' => 'Gas Elpiji 12 kg Industri', 'gudang_id' => null, 'kategori_id' => $katOlahan?->id, 'satuan_id' => $satuanTabung?->id, 'minimum_stok' => 5],
            ['kode' => 'ES-001', 'nama' => 'Es Batu Kristal Balok', 'gudang_id' => null, 'kategori_id' => $katOlahan?->id, 'satuan_id' => $satuanBalok?->id, 'minimum_stok' => 25],

            // Gudang Store Armada Items (Stocked in Store)
            ['kode' => 'STR-001', 'nama' => 'Paket Ayam Marinasi Ritel 500g', 'gudang_id' => $gudangStore?->id, 'kategori_id' => $katStoreRitel?->id, 'satuan_id' => $satuanPack?->id, 'minimum_stok' => 20],
            ['kode' => 'STR-002', 'nama' => 'Nugget Ayam Taman Hati 250g', 'gudang_id' => $gudangStore?->id, 'kategori_id' => $katStoreRitel?->id, 'satuan_id' => $satuanPack?->id, 'minimum_stok' => 15],
            ['kode' => 'STR-003', 'nama' => 'Sosis Ayam Crispy Premium 500g', 'gudang_id' => $gudangStore?->id, 'kategori_id' => $katStoreRitel?->id, 'satuan_id' => $satuanPack?->id, 'minimum_stok' => 25],
            ['kode' => 'STR-004', 'nama' => 'Beras Super Premium 5Kg', 'gudang_id' => $gudangStore?->id, 'kategori_id' => $katOlahan?->id, 'satuan_id' => $satuanPack?->id, 'minimum_stok' => 30],
            ['kode' => 'STR-005', 'nama' => 'Minyak Goreng Sawit 1L', 'gudang_id' => $gudangStore?->id, 'kategori_id' => $katOlahan?->id, 'satuan_id' => $satuanPcs?->id, 'minimum_stok' => 40],
            ['kode' => 'STR-006', 'nama' => 'Bumbu Marinasi Ayam Special 1Kg', 'gudang_id' => $gudangStore?->id, 'kategori_id' => $katOlahan?->id, 'satuan_id' => $satuanKg?->id, 'minimum_stok' => 50],
            ['kode' => 'STR-007', 'nama' => 'Deterjen Bubuk Sanitasi 800g', 'gudang_id' => $gudangStore?->id, 'kategori_id' => $katKemasan?->id, 'satuan_id' => $satuanPack?->id, 'minimum_stok' => 10],
            ['kode' => 'STR-008', 'nama' => 'Air Mineral Botol 600ml', 'gudang_id' => $gudangStore?->id, 'kategori_id' => $katOlahan?->id, 'satuan_id' => $satuanPcs?->id, 'minimum_stok' => 50],
            ['kode' => 'STR-009', 'nama' => 'Kertas Etiket Label Produk Roll', 'gudang_id' => $gudangStore?->id, 'kategori_id' => $katKemasan?->id, 'satuan_id' => $satuanRoll?->id, 'minimum_stok' => 15],

            // RPA Sukahati Specific Items
            ['kode' => 'SKH-001', 'nama' => 'Ayam Broiler Karkas Frozen', 'gudang_id' => $gudangSukahati?->id, 'kategori_id' => $katAyamSukahati?->id, 'satuan_id' => $satuanEkor?->id, 'minimum_stok' => 100],
            ['kode' => 'SKH-002', 'nama' => 'Daging Fillet Dada Ayam', 'gudang_id' => $gudangSukahati?->id, 'kategori_id' => $katAyamSukahati?->id, 'satuan_id' => $satuanKg?->id, 'minimum_stok' => 50],
            ['kode' => 'SKH-003', 'nama' => 'Sayap Ayam Segar Parting', 'gudang_id' => $gudangSukahati?->id, 'kategori_id' => $katAyamSukahati?->id, 'satuan_id' => $satuanKg?->id, 'minimum_stok' => 40],
            ['kode' => 'SKH-004', 'nama' => 'Hati & Ampela Ayam Pack', 'gudang_id' => $gudangSukahati?->id, 'kategori_id' => $katAyamSukahati?->id, 'satuan_id' => $satuanPack?->id, 'minimum_stok' => 30],

            // RPA Tamansari Specific Items
            ['kode' => 'TMS-001', 'nama' => 'Ayam Pejantan Segar Tamansari', 'gudang_id' => $gudangTamansari?->id, 'kategori_id' => $katTamansariAyam?->id, 'satuan_id' => $satuanEkor?->id, 'minimum_stok' => 60],
            ['kode' => 'TMS-002', 'nama' => 'Ceker Ayam Olahan Bersih', 'gudang_id' => $gudangTamansari?->id, 'kategori_id' => $katTamansariAyam?->id, 'satuan_id' => $satuanKg?->id, 'minimum_stok' => 35],
        ];

        foreach ($itemsData as $item) {
            if ($item['kategori_id'] && $item['satuan_id']) {
                $masterBarang = MasterBarang::updateOrCreate(
                    ['kode' => $item['kode']],
                    [
                        'nama' => $item['nama'],
                        'kategori_id' => $item['kategori_id'],
                        'satuan_id' => $item['satuan_id'],
                        'is_aktif' => true,
                    ],
                );

                if ($item['gudang_id'] !== null) {
                    Barang::updateOrCreate(
                        ['gudang_id' => $item['gudang_id'], 'kode' => $item['kode']],
                        [
                            'master_barang_id' => $masterBarang->id,
                            'nama' => $item['nama'],
                            'kategori_id' => $item['kategori_id'],
                            'satuan_id' => $item['satuan_id'],
                            'minimum_stok' => $item['minimum_stok'],
                            'is_aktif' => true,
                        ],
                    );
                }
            }
        }
    }
}
