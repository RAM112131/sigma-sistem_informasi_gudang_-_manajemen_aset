<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\MasterBarang;
use App\Models\Satuan;
use App\Models\StokAwal;
use App\Models\User;
use Illuminate\Database\Seeder;

class UnitBisnisSeeder extends Seeder
{
    /**
     * Seed realistic master items and initial stock for Unit Bisnis (RPA Sukahati & RPA Tamansari).
     */
    public function run(): void
    {
        $gudangSukahati = Gudang::where('kode', 'SUKAHATI')->first();
        $gudangTamansari = Gudang::where('kode', 'TAMANSARI')->first();

        if (! $gudangSukahati) {
            return;
        }

        $userAdmin = User::where('username', 'admingu')->first()
            ?? User::where('username', 'superadmin')->first()
            ?? User::first();

        // Bersihkan data sampah test jika ada
        Barang::whereIn('kode', ['ADSFWEE', 'TEST BARANG'])->forceDelete();

        // Satuan
        $satuanKg = Satuan::firstOrCreate(['kode' => 'KG'], ['nama' => 'Kilogram', 'is_aktif' => true]);
        $satuanEkor = Satuan::firstOrCreate(['kode' => 'EKOR'], ['nama' => 'Ekor', 'is_aktif' => true]);
        $satuanPack = Satuan::firstOrCreate(['kode' => 'PACK'], ['nama' => 'Pack', 'is_aktif' => true]);
        $satuanRoll = Satuan::firstOrCreate(['kode' => 'ROLL'], ['nama' => 'Roll', 'is_aktif' => true]);
        $satuanPcs = Satuan::firstOrCreate(['kode' => 'PCS'], ['nama' => 'Pieces', 'is_aktif' => true]);
        $satuanLiter = Satuan::firstOrCreate(['kode' => 'LITER'], ['nama' => 'Liter', 'is_aktif' => true]);
        $satuanBalok = Satuan::firstOrCreate(['kode' => 'BALOK'], ['nama' => 'Balok', 'is_aktif' => true]);

        // Kategori
        $katKarkas = KategoriBarang::firstOrCreate(
            ['nama' => 'Karkas Ayam & Parting Sukahati'],
            ['gudang_id' => $gudangSukahati->id, 'is_aktif' => true]
        );
        $katBumbu = KategoriBarang::firstOrCreate(
            ['nama' => 'Bumbu & Bahan Olahan'],
            ['is_aktif' => true]
        );
        $katKemasan = KategoriBarang::firstOrCreate(
            ['nama' => 'Bahan Pelengkap & Kemasan'],
            ['is_aktif' => true]
        );
        $katBakar = KategoriBarang::firstOrCreate(
            ['nama' => 'Bahan Bakar & Energi'],
            ['is_aktif' => true]
        );

        $sukahatiItems = [
            [
                'kode' => 'SKH-001',
                'nama' => 'Ayam Broiler Karkas Frozen',
                'kategori_id' => $katKarkas->id,
                'satuan_id' => $satuanEkor->id,
                'minimum_stok' => 100,
                'stok_awal' => 120,
            ],
            [
                'kode' => 'SKH-002',
                'nama' => 'Daging Fillet Dada Ayam',
                'kategori_id' => $katKarkas->id,
                'satuan_id' => $satuanKg->id,
                'minimum_stok' => 50,
                'stok_awal' => 45,
            ],
            [
                'kode' => 'SKH-003',
                'nama' => 'Sayap Ayam Segar Parting',
                'kategori_id' => $katKarkas->id,
                'satuan_id' => $satuanKg->id,
                'minimum_stok' => 40,
                'stok_awal' => 85,
            ],
            [
                'kode' => 'SKH-004',
                'nama' => 'Hati & Ampela Ayam Pack',
                'kategori_id' => $katKarkas->id,
                'satuan_id' => $satuanPack->id,
                'minimum_stok' => 30,
                'stok_awal' => 15,
            ],
            [
                'kode' => 'TMS-002',
                'nama' => 'Ceker Ayam Olahan Bersih',
                'kategori_id' => $katKarkas->id,
                'satuan_id' => $satuanKg->id,
                'minimum_stok' => 35,
                'stok_awal' => 60,
            ],
            [
                'kode' => 'BMB-MRN-001',
                'nama' => 'Bumbu Marinasi Original',
                'kategori_id' => $katBumbu->id,
                'satuan_id' => $satuanKg->id,
                'minimum_stok' => 10,
                'stok_awal' => 24,
            ],
            [
                'kode' => 'KMS-VAC-021',
                'nama' => 'Plastik Vacuum Roll 20x30 cm',
                'kategori_id' => $katKemasan->id,
                'satuan_id' => $satuanRoll->id,
                'minimum_stok' => 10,
                'stok_awal' => 18,
            ],
            [
                'kode' => 'KMS-KRD-005',
                'nama' => 'Kardus Packing Sukahati',
                'kategori_id' => $katKemasan->id,
                'satuan_id' => $satuanPcs->id,
                'minimum_stok' => 50,
                'stok_awal' => 150,
            ],
            [
                'kode' => 'BBM-SLR-012',
                'nama' => 'Solar Industri Genset RPA',
                'kategori_id' => $katBakar->id,
                'satuan_id' => $satuanLiter->id,
                'minimum_stok' => 30,
                'stok_awal' => 85,
            ],
            [
                'kode' => 'ES-BLK-001',
                'nama' => 'Es Balok Pendingin Karkas',
                'kategori_id' => $katKemasan->id,
                'satuan_id' => $satuanBalok->id,
                'minimum_stok' => 5,
                'stok_awal' => 12,
            ],
            [
                'kode' => 'BMB-TPG-010',
                'nama' => 'Tepung Bumbu Serbaguna',
                'kategori_id' => $katBumbu->id,
                'satuan_id' => $satuanKg->id,
                'minimum_stok' => 15,
                'stok_awal' => 36,
            ],
        ];

        foreach ($sukahatiItems as $item) {
            $master = MasterBarang::firstOrCreate(
                ['kode' => $item['kode']],
                [
                    'nama' => $item['nama'],
                    'kategori_id' => $item['kategori_id'],
                    'satuan_id' => $item['satuan_id'],
                    'is_aktif' => true,
                ]
            );

            $barang = Barang::updateOrCreate(
                [
                    'gudang_id' => $gudangSukahati->id,
                    'kode' => $item['kode'],
                ],
                [
                    'master_barang_id' => $master->id,
                    'nama' => $item['nama'],
                    'kategori_id' => $item['kategori_id'],
                    'satuan_id' => $item['satuan_id'],
                    'minimum_stok' => $item['minimum_stok'],
                    'is_aktif' => true,
                ]
            );

            StokAwal::updateOrCreate(
                [
                    'gudang_id' => $gudangSukahati->id,
                    'barang_id' => $barang->id,
                ],
                [
                    'qty' => $item['stok_awal'],
                    'tanggal_acuan' => '2026-10-01',
                    'catatan' => 'Saldo awal opname fisik gudang unit',
                    'diinput_oleh' => $userAdmin?->id,
                ]
            );
        }

        if ($gudangTamansari) {
            $tamansariItems = [
                [
                    'kode' => 'TMS-001',
                    'nama' => 'Ayam Pejantan Segar Tamansari',
                    'kategori_id' => $katKarkas->id,
                    'satuan_id' => $satuanEkor->id,
                    'minimum_stok' => 60,
                    'stok_awal' => 80,
                ],
                [
                    'kode' => 'TMS-002',
                    'nama' => 'Ceker Ayam Olahan Bersih',
                    'kategori_id' => $katKarkas->id,
                    'satuan_id' => $satuanKg->id,
                    'minimum_stok' => 35,
                    'stok_awal' => 50,
                ],
                [
                    'kode' => 'BMB-MRN-001',
                    'nama' => 'Bumbu Marinasi Original',
                    'kategori_id' => $katBumbu->id,
                    'satuan_id' => $satuanKg->id,
                    'minimum_stok' => 10,
                    'stok_awal' => 20,
                ],
            ];

            foreach ($tamansariItems as $item) {
                $master = MasterBarang::firstOrCreate(
                    ['kode' => $item['kode']],
                    [
                        'nama' => $item['nama'],
                        'kategori_id' => $item['kategori_id'],
                        'satuan_id' => $item['satuan_id'],
                        'is_aktif' => true,
                    ]
                );

                $barang = Barang::updateOrCreate(
                    [
                        'gudang_id' => $gudangTamansari->id,
                        'kode' => $item['kode'],
                    ],
                    [
                        'master_barang_id' => $master->id,
                        'nama' => $item['nama'],
                        'kategori_id' => $item['kategori_id'],
                        'satuan_id' => $item['satuan_id'],
                        'minimum_stok' => $item['minimum_stok'],
                        'is_aktif' => true,
                    ]
                );

                StokAwal::updateOrCreate(
                    [
                        'gudang_id' => $gudangTamansari->id,
                        'barang_id' => $barang->id,
                    ],
                    [
                        'qty' => $item['stok_awal'],
                        'tanggal_acuan' => '2026-10-01',
                        'catatan' => 'Saldo awal opname fisik unit Tamansari',
                        'diinput_oleh' => $userAdmin?->id,
                    ]
                );
            }
        }
    }
}
