<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\PenerimaanBarangStore;
use App\Models\PenerimaanBarangStoreDetail;
use App\Models\PenerimaanPemindahanUnit;
use App\Models\Satuan;
use App\Models\StokAwal;
use App\Models\TransferBarang;
use App\Models\User;
use App\Services\Store\StokService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class StokServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_saldo_uses_initial_stock_receipt_details_and_store_transfers(): void
    {
        $store = Gudang::create(['kode' => 'STORE', 'nama' => 'Gudang Store', 'jenis' => 'gudang_store', 'is_aktif' => true]);
        $unitGudang = Gudang::create(['kode' => 'UNIT', 'nama' => 'Unit', 'jenis' => 'gudang_umum', 'is_aktif' => true]);
        $category = KategoriBarang::create(['gudang_id' => $store->id, 'nama' => 'Kemasan', 'is_aktif' => true]);
        $satuan = Satuan::create(['kode' => 'PCS', 'nama' => 'Pieces', 'is_aktif' => true]);
        $barang = Barang::create([
            'gudang_id' => $store->id,
            'kategori_id' => $category->id,
            'satuan_id' => $satuan->id,
            'kode' => 'STR-001',
            'nama' => 'Kardus',
            'minimum_stok' => 1,
            'is_aktif' => true,
        ]);

        StokAwal::create(['gudang_id' => $store->id, 'barang_id' => $barang->id, 'qty' => 10.25, 'tanggal_acuan' => '2026-09-01', 'sumber' => 'manual']);
        $receipt = PenerimaanBarangStore::create([
            'nomor_penerimaan' => 'RCV-001', 'gudang_id' => $store->id, 'qty_diterima' => 0,
            'sumber_supplier' => 'Supplier', 'diterima_oleh' => null, 'tanggal_diterima' => '2026-09-02',
        ]);
        PenerimaanBarangStoreDetail::create([
            'penerimaan_barang_store_id' => $receipt->id, 'barang_id' => $barang->id,
            'qty_diterima' => 7, 'qty_rusak' => 2, 'satuan_id' => $satuan->id, 'kondisi' => 'Rusak',
        ]);
        TransferBarang::create([
            'nomor_sj' => 'SJ-001', 'gudang_asal_id' => $store->id, 'gudang_tujuan_id' => $unitGudang->id,
            'barang_id' => $barang->id, 'qty_diminta' => 4, 'qty_dikirim' => 4,
            'qty_diterima' => 0, 'status' => 'dikirim',
        ]);

        $saldo = app(StokService::class)->saldo($barang, $store->id);

        self::assertSame(10.25, $saldo['awal']);
        self::assertSame(5.0, $saldo['masuk']);
        self::assertSame(4.0, $saldo['keluar']);
        self::assertSame(11.25, $saldo['saldo']);
    }

    public function test_stock_totals_preserve_balances_with_one_database_query(): void
    {
        $store = Gudang::create([
            'kode' => 'STORE-AGGREGATE',
            'nama' => 'Gudang Store',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $barang = Barang::create([
            'gudang_id' => $store->id,
            'kode' => 'STR-AGGREGATE',
            'nama' => 'Barang Agregasi',
            'minimum_stok' => 1,
            'is_aktif' => true,
        ]);
        StokAwal::create([
            'gudang_id' => $store->id,
            'barang_id' => $barang->id,
            'qty' => 4.5,
            'tanggal_acuan' => '2026-09-01',
            'sumber' => 'manual',
        ]);

        $connection = DB::connection();
        $connection->flushQueryLog();
        $connection->enableQueryLog();
        $totals = app(StokService::class)->totals([$barang->id], $store->id);
        $queries = $connection->getQueryLog();
        $connection->disableQueryLog();

        self::assertCount(1, $queries);
        self::assertSame(4.5, $totals['awal'][$barang->id]);
        self::assertSame([], $totals['masuk']->all());
        self::assertSame([], $totals['keluar']->all());
    }

    public function test_unit_stock_counts_only_confirmed_received_quantity_after_damaged_items(): void
    {
        $store = Gudang::create(['kode' => 'STORE-INBOUND', 'nama' => 'Gudang Store', 'jenis' => 'gudang_store', 'is_aktif' => true]);
        $unit = Gudang::create(['kode' => 'UNIT-INBOUND', 'nama' => 'Gudang Unit', 'jenis' => 'gudang_umum', 'is_aktif' => true]);
        $barang = Barang::create([
            'gudang_id' => $unit->id,
            'kode' => 'UNIT-STOCK-001',
            'nama' => 'Barang Transfer',
            'minimum_stok' => 2,
            'is_aktif' => true,
        ]);
        StokAwal::create([
            'gudang_id' => $unit->id,
            'barang_id' => $barang->id,
            'qty' => 1,
            'tanggal_acuan' => '2026-09-01',
            'sumber' => 'manual',
        ]);

        $pendingTransfer = TransferBarang::create([
            'nomor_sj' => 'SJ-UNIT-PENDING',
            'gudang_asal_id' => $store->id,
            'gudang_tujuan_id' => $unit->id,
            'barang_id' => $barang->id,
            'qty_diminta' => 5,
            'qty_dikirim' => 5,
            'qty_diterima' => 0,
            'status' => 'dikirim',
        ]);
        $receivedTransfer = TransferBarang::create([
            'nomor_sj' => 'SJ-UNIT-RECEIVED',
            'gudang_asal_id' => $store->id,
            'gudang_tujuan_id' => $unit->id,
            'barang_id' => $barang->id,
            'qty_diminta' => 8,
            'qty_dikirim' => 8,
            'qty_diterima' => 6,
            'status' => 'diterima',
        ]);
        PenerimaanPemindahanUnit::create([
            'transfer_barang_id' => $receivedTransfer->id,
            'gudang_id' => $unit->id,
            'barang_id' => $barang->id,
            'qty_dikirim' => 8,
            'qty_diterima' => 6,
            'qty_rusak' => 2,
            'diterima_at' => now(),
        ]);

        $saldo = app(StokService::class)->saldo($barang, $unit->id);

        self::assertSame(1.0, $saldo['awal']);
        self::assertSame(4.0, $saldo['masuk']);
        self::assertSame(0.0, $saldo['keluar']);
        self::assertSame(5.0, $saldo['saldo']);
        self::assertDatabaseHas('transfer_barangs', [
            'id' => $pendingTransfer->id,
            'status' => 'dikirim',
        ]);
    }

    public function test_store_receipt_preserves_purchase_recap_metadata_and_detail_totals(): void
    {
        $store = Gudang::create(['kode' => 'STORE', 'nama' => 'Gudang Store', 'jenis' => 'gudang_store', 'is_aktif' => true]);
        $category = KategoriBarang::create(['gudang_id' => $store->id, 'nama' => 'Kemasan', 'is_aktif' => true]);
        $satuan = Satuan::create(['kode' => 'PCS', 'nama' => 'Pieces', 'is_aktif' => true]);
        $user = User::factory()->create();
        $barang = Barang::create([
            'gudang_id' => $store->id,
            'kategori_id' => $category->id,
            'satuan_id' => $satuan->id,
            'kode' => 'STR-010',
            'nama' => 'Botol Plastik',
            'minimum_stok' => 5,
            'is_aktif' => true,
        ]);

        $receipt = PenerimaanBarangStore::create([
            'nomor_penerimaan' => 'RCV-010',
            'gudang_id' => $store->id,
            'barang_id' => $barang->id,
            'qty_diterima' => 25,
            'satuan_id' => $satuan->id,
            'pemesan' => 'Bagian Operasional',
            'pengambil_barang' => 'Ekspedisi Jaya',
            'pemakai' => 'Unit Operasional Sukahati',
            'metode_pembayaran' => 'Transfer',
            'catatan_rekap' => 'Rekap manual ekspedisi',
            'diterima_oleh' => $user->id,
            'tanggal_diterima' => '2026-09-05',
        ]);

        $detail = PenerimaanBarangStoreDetail::create([
            'penerimaan_barang_store_id' => $receipt->id,
            'barang_id' => $barang->id,
            'qty_diterima' => 25,
            'qty_rusak' => 2,
            'satuan_id' => $satuan->id,
            'kondisi' => 'Baik',
            'pemakai' => 'TMS',
            'harga_satuan' => 15000,
            'total_harga' => 375000,
        ]);

        self::assertSame('Bagian Operasional', $receipt->pemesan);
        self::assertSame('Ekspedisi Jaya', $receipt->pengambil_barang);
        self::assertSame('Unit Operasional Sukahati', $receipt->pemakai);
        self::assertSame('Transfer', $receipt->metode_pembayaran);
        self::assertSame('TMS', $detail->pemakai);
        self::assertSame(2, $detail->qty_rusak);
        self::assertSame(375000.0, (float) $detail->total_harga);
    }

    public function test_listing_calculates_stock_status_and_applies_requested_filter(): void
    {
        $store = Gudang::create(['kode' => 'STORE-LIST', 'nama' => 'Gudang Store', 'jenis' => 'gudang_store', 'is_aktif' => true]);
        $category = KategoriBarang::create(['gudang_id' => $store->id, 'nama' => 'Kemasan', 'is_aktif' => true]);
        $satuan = Satuan::create(['kode' => 'PCS-LIST', 'nama' => 'Pieces', 'is_aktif' => true]);
        $barangKritis = Barang::create([
            'gudang_id' => $store->id,
            'kategori_id' => $category->id,
            'satuan_id' => $satuan->id,
            'kode' => 'STOCK-CRITICAL',
            'nama' => 'Barang Kritis',
            'minimum_stok' => 5,
            'is_aktif' => true,
        ]);
        $barangWarning = Barang::create([
            'gudang_id' => $store->id,
            'kategori_id' => $category->id,
            'satuan_id' => $satuan->id,
            'kode' => 'STOCK-WARNING',
            'nama' => 'Barang Warning',
            'minimum_stok' => 5,
            'is_aktif' => true,
        ]);
        $barangAman = Barang::create([
            'gudang_id' => $store->id,
            'kategori_id' => $category->id,
            'satuan_id' => $satuan->id,
            'kode' => 'STOCK-SAFE',
            'nama' => 'Barang Aman',
            'minimum_stok' => 5,
            'is_aktif' => true,
        ]);
        StokAwal::create(['gudang_id' => $store->id, 'barang_id' => $barangWarning->id, 'qty' => 5, 'tanggal_acuan' => '2026-09-01', 'sumber' => 'manual']);
        StokAwal::create(['gudang_id' => $store->id, 'barang_id' => $barangAman->id, 'qty' => 20, 'tanggal_acuan' => '2026-09-01', 'sumber' => 'manual']);

        $service = app(StokService::class);
        $listing = $service->getListingData($store, ['search_key' => 'search', 'status' => null]);
        $warningListing = $service->getListingData($store, ['search_key' => 'search', 'status' => 'warning']);

        self::assertSame(
            ['Aman', 'Habis / Kritis', 'Mendekati Minimum'],
            $listing['barangs']->getCollection()->pluck('status_text')->all(),
        );
        self::assertSame([$barangWarning->id], $warningListing['barangs']->getCollection()->pluck('model.id')->all());
    }
}
