<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\MasterBarang;
use App\Models\PenerimaanBarangStore;
use App\Models\Satuan;
use App\Models\User;
use App\Services\Store\KatalogStoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class KatalogStoreServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_catalog_item_and_initial_stock_are_persisted_together(): void
    {
        $store = Gudang::create([
            'kode' => 'STORE-CATALOG',
            'nama' => 'Gudang Store',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $category = KategoriBarang::create([
            'gudang_id' => $store->id,
            'nama' => 'Kemasan',
            'is_aktif' => true,
        ]);
        $unit = Satuan::create(['kode' => 'PCS-CATALOG', 'nama' => 'Pieces', 'is_aktif' => true]);
        $user = User::factory()->create();

        $barang = app(KatalogStoreService::class)->create([
            'kode' => ' item-001 ',
            'nama' => ' Barang Uji ',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'minimum_stok' => 3,
            'stok_awal' => 12,
        ], null, $user->id);

        self::assertSame('ITEM-001', $barang->kode);
        self::assertSame('Barang Uji', $barang->nama);
        self::assertSame($store->id, $barang->gudang_id);
        self::assertNotNull($barang->master_barang_id);
        self::assertSame('Pieces', $barang->satuan->nama);
        $this->assertDatabaseHas('penerimaan_barang_stores', [
            'gudang_id' => $store->id,
            'barang_id' => $barang->id,
            'qty_diterima' => 12,
            'diterima_oleh' => $user->id,
            'sumber_supplier' => 'Stok Awal (Registrasi Barang Baru)',
        ]);
        self::assertSame(1, PenerimaanBarangStore::query()->count());
        self::assertSame(1, Barang::query()->count());
    }

    public function test_catalog_item_can_be_updated_toggled_and_soft_deleted(): void
    {
        $category = KategoriBarang::create(['nama' => 'Kemasan', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS-EDIT', 'nama' => 'Pieces', 'is_aktif' => true]);
        $masterBarang = MasterBarang::create([
            'kode' => 'MASTER-EDIT',
            'nama' => 'Barang Lama',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'is_aktif' => true,
        ]);
        $barang = Barang::create([
            'master_barang_id' => $masterBarang->id,
            'kode' => 'ITEM-EDIT',
            'nama' => 'Barang Lama',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'minimum_stok' => 2,
            'is_aktif' => true,
        ]);
        $service = app(KatalogStoreService::class);

        $service->update($barang, [
            'kode' => 'item-baru',
            'nama' => 'Barang Baru',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'minimum_stok' => 4,
            'is_aktif' => true,
        ]);
        $toggledBarang = $service->toggle($barang);

        self::assertSame('ITEM-BARU', $toggledBarang->kode);
        self::assertFalse($toggledBarang->is_aktif);

        $deletedName = $service->delete($toggledBarang);

        self::assertSame('Barang Baru', $deletedName);
        $this->assertSoftDeleted('barangs', ['id' => $barang->id]);
    }
}
