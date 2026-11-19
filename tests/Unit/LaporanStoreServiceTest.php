<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\PenerimaanBarangStore;
use App\Models\PenerimaanBarangStoreDetail;
use App\Models\Satuan;
use App\Models\StokAwal;
use App\Models\TransferBarang;
use App\Services\Store\LaporanStoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LaporanStoreServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_data_builds_stock_mutation_and_distribution_reports(): void
    {
        $this->freezeTime();
        [$store, $unit, $barang] = $this->storeInventory();

        StokAwal::create([
            'gudang_id' => $store->id,
            'barang_id' => $barang->id,
            'qty' => 10,
            'tanggal_acuan' => today()->toDateString(),
            'sumber' => 'manual',
        ]);
        $receipt = PenerimaanBarangStore::create([
            'nomor_penerimaan' => 'RCV-001',
            'gudang_id' => $store->id,
            'barang_id' => $barang->id,
            'qty_diterima' => 5,
            'satuan_id' => $barang->satuan_id,
            'sumber_supplier' => 'Supplier Uji',
            'tanggal_diterima' => today()->setTime(8, 0),
        ]);
        PenerimaanBarangStoreDetail::create([
            'penerimaan_barang_store_id' => $receipt->id,
            'barang_id' => $barang->id,
            'qty_diterima' => 5,
            'qty_rusak' => 0,
            'satuan_id' => $barang->satuan_id,
            'kondisi' => 'Baik',
            'pemakai' => 'Unit Produksi',
        ]);
        TransferBarang::create([
            'nomor_sj' => 'SJ-001',
            'gudang_asal_id' => $store->id,
            'gudang_tujuan_id' => $unit->id,
            'barang_id' => $barang->id,
            'qty_diminta' => 3,
            'qty_dikirim' => 3,
            'qty_diterima' => 0,
            'status' => 'dikirim',
            'dikirim_at' => today()->setTime(9, 0),
        ]);

        $service = app(LaporanStoreService::class);
        $start = today()->toDateString();
        $end = today()->toDateString();
        [$stockRows, $stockHeadings, $stockTitle] = $service->exportData($store, 'stok_akhir', null, $start, $end);
        [$mutationRows, $mutationHeadings, $mutationTitle] = $service->exportData($store, 'mutasi', null, $start, $end);
        [$distributionRows, $distributionHeadings, $distributionTitle] = $service->exportData($store, 'distribusi', null, $start, $end);

        $this->assertSame([
            [$barang->kode, $barang->nama, 'Kemasan', 15, 3, 12, 2, 'Pieces'],
        ], $stockRows->all());
        $this->assertSame(['Kode', 'Nama Barang', 'Kategori', 'Total Masuk', 'Total Keluar', 'Stok Akhir', 'Minimum', 'Satuan'], $stockHeadings);
        $this->assertSame('Laporan Stok Akhir Gudang Store', $stockTitle);

        $this->assertCount(2, $mutationRows);
        $this->assertSame('Pemindahan Keluar', $mutationRows[0][2]);
        $this->assertSame('Barang Masuk', $mutationRows[1][2]);
        $this->assertSame('Laporan Mutasi Gudang Store', $mutationTitle);
        $this->assertSame('No. Dokumen', $mutationHeadings[1]);

        $this->assertCount(1, $distributionRows);
        $this->assertSame('SJ-001', $distributionRows[0][0]);
        $this->assertSame(today()->setTime(9, 0)->toDateTimeString(), $distributionRows[0][1]->toDateTimeString());
        $this->assertSame($unit->nama, $distributionRows[0][2]);
        $this->assertSame($barang->nama, $distributionRows[0][3]);
        $this->assertSame(3, $distributionRows[0][4]);
        $this->assertSame('dikirim', $distributionRows[0][5]);
        $this->assertSame('-', $distributionRows[0][6]);
        $this->assertSame('No. Surat Jalan', $distributionHeadings[0]);
        $this->assertSame('Laporan Distribusi Gudang Store', $distributionTitle);
    }

    /**
     * @return array{Gudang, Gudang, Barang}
     */
    private function storeInventory(): array
    {
        $store = Gudang::create([
            'kode' => 'STORE',
            'nama' => 'Gudang Store',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $unit = Gudang::create([
            'kode' => 'UNIT',
            'nama' => 'Gudang Unit',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $category = KategoriBarang::create([
            'gudang_id' => $store->id,
            'nama' => 'Kemasan',
            'is_aktif' => true,
        ]);
        $satuan = Satuan::create(['kode' => 'PCS', 'nama' => 'Pieces', 'is_aktif' => true]);
        $barang = Barang::create([
            'gudang_id' => $store->id,
            'kategori_id' => $category->id,
            'satuan_id' => $satuan->id,
            'kode' => 'ITEM-001',
            'nama' => 'Barang Uji',
            'minimum_stok' => 2,
            'is_aktif' => true,
        ]);

        return [$store, $unit, $barang];
    }
}
