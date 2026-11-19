<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\MasterBarang;
use App\Models\Satuan;
use App\Models\StokAwal;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class UnitStockInitialImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_recording_gu_sees_the_manual_paste_grid_without_file_upload(): void
    {
        [$recording, $gudang] = $this->makeRecordingContext();

        $this->actingAs($recording)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->get(route('unit-bisnis.stok.index', ['tab' => 'import_data_awal']))
            ->assertOk()
            ->assertSee('Tempel dari Excel / Edit Data')
            ->assertSee('aria-label="Tabel input stok awal"', false)
            ->assertSee('handleGridPaste($event', false)
            ->assertSee('scheduleGridValidation()', false)
            ->assertSee('Unduh Template CSV')
            ->assertDontSee('Unggah file Excel atau CSV')
            ->assertDontSee('accept=".xlsx,.xls,.csv"', false)
            ->assertDontSee('xlsx.full.min.js');
    }

    public function test_recording_gu_can_analyze_rows_from_the_manual_import_grid(): void
    {
        [$recording, $gudang] = $this->makeRecordingContext();

        $this->actingAs($recording)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->postJson(route('unit-bisnis.stok.import-analyze'), [
                'rows' => [[
                    'row' => 1,
                    'kode_barang' => 'NEW-001',
                    'nama_barang' => 'Barang Baru',
                    'kategori' => 'Kemasan',
                    'satuan' => 'pcs',
                    'qty' => '4',
                    'kondisi' => 'baik',
                    'catatan' => '',
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('summary.ok', 1)
            ->assertJsonPath('rows.0.row', 1)
            ->assertJsonPath('rows.0.status', 'ok')
            ->assertJsonPath('rows.0.is_new_barang', true);
    }

    public function test_recording_gu_analysis_rejects_master_barang_name_or_code_conflicts(): void
    {
        [$recording, $gudang] = $this->makeRecordingContext();
        $category = KategoriBarang::create(['nama' => 'Kemasan', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS', 'nama' => 'Pieces', 'is_aktif' => true]);
        MasterBarang::create([
            'kode' => 'MASTER-001',
            'nama' => 'Barang Master',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'is_aktif' => true,
        ]);

        $this->actingAs($recording)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->postJson(route('unit-bisnis.stok.import-analyze'), [
                'rows' => [
                    [
                        'row' => 1,
                        'kode_barang' => 'NEW-001',
                        'nama_barang' => 'Barang Master',
                        'kategori' => 'Kemasan',
                        'satuan' => 'PCS',
                        'qty' => '4',
                    ],
                    [
                        'row' => 2,
                        'kode_barang' => 'MASTER-001',
                        'nama_barang' => 'Nama Berbeda',
                        'kategori' => 'Kemasan',
                        'satuan' => 'PCS',
                        'qty' => '4',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('summary.error', 2)
            ->assertJsonPath('rows.0.status', 'error')
            ->assertJsonPath('rows.0.issues.0', "Nama 'Barang Master' sudah digunakan oleh Master Barang dengan kode 'MASTER-001'.")
            ->assertJsonPath('rows.1.status', 'error')
            ->assertJsonPath('rows.1.issues.0', "Kode 'MASTER-001' sudah digunakan oleh Master Barang 'Barang Master'.");
    }

    public function test_recording_gu_import_does_not_create_a_duplicate_master_name(): void
    {
        [$recording, $gudang] = $this->makeRecordingContext();
        $category = KategoriBarang::create(['nama' => 'Kemasan', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS', 'nama' => 'Pieces', 'is_aktif' => true]);
        MasterBarang::create([
            'kode' => 'MASTER-001',
            'nama' => 'Barang Master',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'is_aktif' => true,
        ]);

        $this->actingAs($recording)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->postJson(route('unit-bisnis.stok.import-process'), [
                'tanggal_acuan' => '2026-10-01',
                'rows' => [[
                    'row' => 1,
                    'kode_barang' => 'NEW-001',
                    'nama_barang' => 'Barang Master',
                    'kategori' => 'Kemasan',
                    'satuan' => 'PCS',
                    'qty' => 4,
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('success_count', 0)
            ->assertJsonPath('created_count', 0)
            ->assertJsonPath('error_rows.0.error', "Nama 'Barang Master' sudah digunakan oleh Master Barang dengan kode 'MASTER-001'.");

        $this->assertDatabaseMissing('barangs', ['kode' => 'NEW-001']);
        $this->assertDatabaseMissing('stok_awals', ['gudang_id' => $gudang->id]);
        $this->assertSame(1, MasterBarang::query()->count());
    }

    public function test_recording_gu_import_accepts_an_exact_active_master_barang_match(): void
    {
        [$recording, $gudang] = $this->makeRecordingContext();
        $category = KategoriBarang::create(['nama' => 'Kemasan', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS', 'nama' => 'Pieces', 'is_aktif' => true]);
        $masterBarang = MasterBarang::create([
            'kode' => 'MASTER-001',
            'nama' => 'Barang Master',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'is_aktif' => true,
        ]);

        $this->actingAs($recording)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->postJson(route('unit-bisnis.stok.import-process'), [
                'tanggal_acuan' => '2026-10-01',
                'rows' => [[
                    'row' => 1,
                    'kode_barang' => 'MASTER-001',
                    'nama_barang' => 'Barang Master',
                    'kategori' => 'Kemasan',
                    'satuan' => 'PCS',
                    'qty' => 4,
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('created_count', 1);

        $this->assertDatabaseHas('barangs', [
            'gudang_id' => $gudang->id,
            'kode' => 'MASTER-001',
            'master_barang_id' => $masterBarang->id,
        ]);
        $this->assertSame(1, MasterBarang::query()->count());
    }

    public function test_recording_gu_analysis_allows_a_different_local_code_linked_to_the_same_master_barang(): void
    {
        [$recording, $gudang] = $this->makeRecordingContext();
        $category = KategoriBarang::create(['nama' => 'Kemasan', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS', 'nama' => 'Pieces', 'is_aktif' => true]);
        $masterBarang = MasterBarang::create([
            'kode' => 'MASTER-001',
            'nama' => 'Barang Master',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'is_aktif' => true,
        ]);
        Barang::create([
            'master_barang_id' => $masterBarang->id,
            'gudang_id' => $gudang->id,
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'kode' => 'LOCAL-001',
            'nama' => 'Barang Master',
            'is_aktif' => true,
        ]);

        $this->actingAs($recording)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->postJson(route('unit-bisnis.stok.import-analyze'), [
                'rows' => [[
                    'row' => 1,
                    'kode_barang' => 'LOCAL-001',
                    'nama_barang' => 'Barang Master',
                    'kategori' => 'Kemasan',
                    'satuan' => 'PCS',
                    'qty' => '4',
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('summary.ok', 1)
            ->assertJsonPath('rows.0.status', 'ok')
            ->assertJsonPath('rows.0.is_new_barang', false);
    }

    public function test_recording_gu_can_save_rows_from_the_manual_import_grid(): void
    {
        [$recording, $gudang] = $this->makeRecordingContext();

        $this->actingAs($recording)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->postJson(route('unit-bisnis.stok.import-process'), [
                'tanggal_acuan' => '2026-10-01',
                'rows' => [[
                    'row' => 1,
                    'kode_barang' => 'NEW-001',
                    'nama_barang' => 'Barang Baru',
                    'kategori' => 'Kemasan',
                    'satuan' => 'pcs',
                    'qty' => 4,
                    'catatan' => '',
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('created_count', 1);

        $this->assertDatabaseHas('stok_awals', [
            'gudang_id' => $gudang->id,
            'qty' => 4,
            'sumber' => 'import',
            'diinput_oleh' => $recording->id,
        ]);
        $this->assertSame(1, StokAwal::query()->where('gudang_id', $gudang->id)->count());
    }

    public function test_stock_overview_summarizes_entire_warehouse_and_filters_status_before_pagination(): void
    {
        [$recording, $gudang] = $this->makeRecordingContext();
        $satuan = Satuan::create(['kode' => 'PCS', 'nama' => 'Pieces', 'is_aktif' => true]);

        for ($index = 1; $index <= 15; $index++) {
            $barang = Barang::create([
                'gudang_id' => $gudang->id,
                'satuan_id' => $satuan->id,
                'kode' => sprintf('SAFE-%03d', $index),
                'nama' => sprintf('Aman %03d', $index),
                'minimum_stok' => 5,
                'is_aktif' => true,
            ]);

            StokAwal::create([
                'gudang_id' => $gudang->id,
                'barang_id' => $barang->id,
                'qty' => 20,
                'tanggal_acuan' => '2026-10-01',
                'sumber' => 'manual',
            ]);
        }

        $barangMenipis = Barang::create([
            'gudang_id' => $gudang->id,
            'satuan_id' => $satuan->id,
            'kode' => 'LOW-001',
            'nama' => 'Z Barang Menipis',
            'minimum_stok' => 10,
            'harga_satuan' => 1500,
            'is_aktif' => true,
        ]);
        StokAwal::create([
            'gudang_id' => $gudang->id,
            'barang_id' => $barangMenipis->id,
            'qty' => 2,
            'tanggal_acuan' => '2026-10-01',
            'sumber' => 'manual',
        ]);

        Barang::create([
            'gudang_id' => $gudang->id,
            'satuan_id' => $satuan->id,
            'kode' => 'EMPTY-001',
            'nama' => 'Z Barang Habis',
            'minimum_stok' => 5,
            'is_aktif' => true,
        ]);

        $otherWarehouse = Gudang::create([
            'kode' => 'OTHER-UNIT',
            'nama' => 'Gudang Unit Lain',
            'jenis' => 'unit_bisnis',
            'is_aktif' => true,
        ]);
        Barang::create([
            'gudang_id' => $otherWarehouse->id,
            'satuan_id' => $satuan->id,
            'kode' => 'OTHER-001',
            'nama' => 'Barang Gudang Lain',
            'is_aktif' => true,
        ]);

        $this->actingAs($recording)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->get(route('unit-bisnis.stok.index'))
            ->assertOk()
            ->assertViewHas('stockSummary', [
                'total_barang' => 17,
                'stok_menipis' => 1,
                'stok_habis' => 1,
            ])
            ->assertSee('Stok Barang Fisik')
            ->assertSee('Export Daftar Stok')
            ->assertSee('Semua Status Stok')
            ->assertSee('HPP Berlaku (Hari Ini)')
            ->assertDontSee('Barang Gudang Lain');

        $this->get(route('unit-bisnis.stok.index', ['status' => 'warning']))
            ->assertOk()
            ->assertViewHas('barangs', fn ($barangs): bool => $barangs->total() === 1
                && $barangs->getCollection()->first()['model']->is($barangMenipis))
            ->assertSee('LOW-001');

        $this->get(route('unit-bisnis.stok.index', ['status' => 'critical']))
            ->assertOk()
            ->assertViewHas('barangs', fn ($barangs): bool => $barangs->total() === 2);

        $exportResponse = $this->get(route('unit-bisnis.stok.export', ['status' => 'warning']));

        $exportResponse->assertOk();
        $csv = $exportResponse->streamedContent();

        $this->assertStringContainsString('LOW-001', $csv);
        $this->assertStringNotContainsString('SAFE-001', $csv);
        $this->assertStringNotContainsString('OTHER-001', $csv);
    }

    public function test_stock_card_is_loaded_for_the_modal_and_cannot_cross_warehouse_boundaries(): void
    {
        [$recording, $gudang] = $this->makeRecordingContext();
        $satuan = Satuan::create(['kode' => 'PCS', 'nama' => 'Pieces', 'is_aktif' => true]);
        $barang = Barang::create([
            'gudang_id' => $gudang->id,
            'satuan_id' => $satuan->id,
            'kode' => 'CARD-001',
            'nama' => 'Barang Kartu Modal',
            'minimum_stok' => 5,
            'harga_satuan' => 2500,
            'is_aktif' => true,
        ]);
        $stokAwal = StokAwal::create([
            'gudang_id' => $gudang->id,
            'barang_id' => $barang->id,
            'qty' => 12,
            'tanggal_acuan' => '2026-10-01',
            'sumber' => 'manual',
        ]);

        $this->actingAs($recording)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->get(route('unit-bisnis.stok.index'))
            ->assertOk()
            ->assertSee('role="dialog"', false)
            ->assertSee('openStockCard(', false)
            ->assertDontSee('href="'.route('unit-bisnis.stok.kartu', $barang).'"', false);

        $this->get(route('unit-bisnis.stok.kartu', ['barang' => $barang->id, 'gudang_id' => $gudang->id]))
            ->assertOk()
            ->assertJsonPath('barang.nama', 'Barang Kartu Modal')
            ->assertJsonPath('barang.kode', 'CARD-001')
            ->assertJsonPath('barang.satuan', 'Pieces')
            ->assertJsonPath('gudang', $gudang->nama)
            ->assertJsonPath('mutasi.0.ref', 'STOK-AWAL-'.$stokAwal->id)
            ->assertJsonPath('mutasi.0.saldo', 12);

        $otherWarehouse = Gudang::create([
            'kode' => 'OTHER-CARD',
            'nama' => 'Gudang Kartu Lain',
            'jenis' => 'unit_bisnis',
            'is_aktif' => true,
        ]);
        $otherBarang = Barang::create([
            'gudang_id' => $otherWarehouse->id,
            'satuan_id' => $satuan->id,
            'kode' => 'CARD-OTHER',
            'nama' => 'Barang Bukan Gudang Ini',
            'is_aktif' => true,
        ]);

        $this->getJson(route('unit-bisnis.stok.kartu', $otherBarang))
            ->assertNotFound();
    }

    public function test_recording_gu_can_edit_item_details_without_changing_the_physical_balance(): void
    {
        [$recording, $gudang] = $this->makeRecordingContext();
        $satuan = Satuan::create(['kode' => 'PCS', 'nama' => 'Pieces', 'is_aktif' => true]);
        $barang = Barang::create([
            'gudang_id' => $gudang->id,
            'satuan_id' => $satuan->id,
            'kode' => 'EDIT-001',
            'nama' => 'Barang Sebelum Edit',
            'minimum_stok' => 5,
            'harga_satuan' => 2500,
            'is_aktif' => true,
        ]);
        StokAwal::create([
            'gudang_id' => $gudang->id,
            'barang_id' => $barang->id,
            'qty' => 12,
            'tanggal_acuan' => '2026-10-01',
            'sumber' => 'manual',
        ]);

        $this->actingAs($recording)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->get(route('unit-bisnis.stok.edit', ['barang' => $barang->id, 'gudang_id' => $gudang->id]))
            ->assertOk()
            ->assertSee('Edit Data Barang')
            ->assertSee('Saldo fisik tidak diedit langsung');

        $this->put(route('unit-bisnis.stok.update', ['barang' => $barang->id, 'gudang_id' => $gudang->id]), [
            'kode' => ' edit-002 ',
            'nama' => 'Barang Setelah Edit',
            'minimum_stok' => 8,
            'harga_satuan' => 3200,
        ])
            ->assertRedirect(route('unit-bisnis.stok.index', ['tab' => 'stok_saat_ini', 'gudang_id' => $gudang->id]));

        $this->assertDatabaseHas('barangs', [
            'id' => $barang->id,
            'kode' => 'EDIT-002',
            'nama' => 'Barang Setelah Edit',
            'minimum_stok' => 8,
            'harga_satuan' => 3200,
        ]);
        $this->assertDatabaseHas('stok_awals', [
            'barang_id' => $barang->id,
            'qty' => 12,
        ]);
    }

    public function test_admin_gu_can_delete_an_item_while_preserving_its_stock_history(): void
    {
        [$admin, $gudang] = $this->makeRecordingContext('Admin Gudang Umum');
        $barang = Barang::create([
            'gudang_id' => $gudang->id,
            'kode' => 'DEL-001',
            'nama' => 'Barang Dinonaktifkan',
            'minimum_stok' => 0,
            'is_aktif' => true,
        ]);
        StokAwal::create([
            'gudang_id' => $gudang->id,
            'barang_id' => $barang->id,
            'qty' => 0,
            'tanggal_acuan' => '2026-10-01',
            'sumber' => 'manual',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->get(route('unit-bisnis.stok.index', ['gudang_id' => $gudang->id]))
            ->assertOk()
            ->assertSee('stock-delete-confirm-modal', false)
            ->assertSee('Riwayat transaksi yang sudah ada tetap tersimpan');

        $this->actingAs($admin)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->delete(route('unit-bisnis.stok.destroy', ['barang' => $barang->id, 'gudang_id' => $gudang->id]))
            ->assertRedirect(route('unit-bisnis.stok.index', ['tab' => 'stok_saat_ini', 'gudang_id' => $gudang->id]))
            ->assertSessionHas('success', 'Barang Dinonaktifkan berhasil dihapus dari daftar stok. Riwayat transaksi tetap disimpan.');

        $this->assertSoftDeleted('barangs', ['id' => $barang->id]);
        $this->assertDatabaseHas('stok_awals', ['barang_id' => $barang->id, 'qty' => 0]);

        $this->get(route('unit-bisnis.stok.index', ['gudang_id' => $gudang->id]))
            ->assertOk()
            ->assertViewHas('barangs', fn ($barangs): bool => $barangs->total() === 0);
    }

    public function test_admin_gu_cannot_delete_an_item_with_remaining_physical_stock(): void
    {
        [$admin, $gudang] = $this->makeRecordingContext('Admin Gudang Umum');
        $barang = Barang::create([
            'gudang_id' => $gudang->id,
            'kode' => 'HAS-STOCK-001',
            'nama' => 'Barang Masih Ada Stok',
            'is_aktif' => true,
        ]);
        StokAwal::create([
            'gudang_id' => $gudang->id,
            'barang_id' => $barang->id,
            'qty' => 3,
            'tanggal_acuan' => '2026-10-01',
            'sumber' => 'manual',
        ]);

        $this->from(route('unit-bisnis.stok.index', ['gudang_id' => $gudang->id]))
            ->actingAs($admin)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->delete(route('unit-bisnis.stok.destroy', ['barang' => $barang->id, 'gudang_id' => $gudang->id]))
            ->assertRedirect(route('unit-bisnis.stok.index', ['gudang_id' => $gudang->id]))
            ->assertSessionHasErrors('barang');

        $this->assertDatabaseHas('barangs', ['id' => $barang->id, 'is_aktif' => true, 'deleted_at' => null]);
    }

    public function test_admin_gu_cannot_delete_an_item_with_a_pending_receipt_to_verify(): void
    {
        [$admin, $gudang] = $this->makeRecordingContext('Admin Gudang Umum');
        $barang = Barang::create([
            'gudang_id' => $gudang->id,
            'kode' => 'PENDING-001',
            'nama' => 'Barang Pending',
            'is_aktif' => true,
        ]);
        $receiptId = DB::table('penerimaan_units')->insertGetId([
            'nomor_rcv' => 'RCV-PENDING-001',
            'gudang_id' => $gudang->id,
            'nama_supplier' => 'Supplier Pending',
            'tanggal_terima' => '2026-10-01',
            'waktu_terima' => '10:00:00',
            'status_verifikasi' => 'menunggu_verifikasi',
        ]);
        DB::table('penerimaan_unit_details')->insert([
            'penerimaan_unit_id' => $receiptId,
            'barang_id' => $barang->id,
            'nama_barang' => $barang->nama,
            'satuan' => 'PCS',
            'qty_diterima' => 1,
            'kondisi' => 'Baik',
            'qty_rusak' => 0,
            'qty_kurang' => 0,
            'qty_lebih' => 0,
        ]);

        $this->from(route('unit-bisnis.stok.index', ['gudang_id' => $gudang->id]))
            ->actingAs($admin)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->delete(route('unit-bisnis.stok.destroy', ['barang' => $barang->id, 'gudang_id' => $gudang->id]))
            ->assertRedirect(route('unit-bisnis.stok.index', ['gudang_id' => $gudang->id]))
            ->assertSessionHasErrors([
                'barang' => "Barang 'Barang Pending' belum dapat dihapus karena masih ada transaksi yang belum selesai (Penerimaan GU: RCV-PENDING-001); saldo fisik masih 1,000 (saldo harus nol).",
            ]);

        $this->assertDatabaseHas('barangs', ['id' => $barang->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('penerimaan_units', [
            'id' => $receiptId,
            'status_verifikasi' => 'menunggu_verifikasi',
        ]);
    }

    public function test_recording_gu_cannot_delete_items_or_edit_items_from_another_warehouse(): void
    {
        [$recording, $gudang] = $this->makeRecordingContext();
        $otherWarehouse = Gudang::create([
            'kode' => 'OTHER-EDIT',
            'nama' => 'Gudang Lain',
            'jenis' => 'unit_bisnis',
            'is_aktif' => true,
        ]);
        $otherBarang = Barang::create([
            'gudang_id' => $otherWarehouse->id,
            'kode' => 'OTHER-EDIT-001',
            'nama' => 'Barang Gudang Lain',
            'is_aktif' => true,
        ]);

        $this->actingAs($recording)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->delete(route('unit-bisnis.stok.destroy', ['barang' => $otherBarang->id, 'gudang_id' => $gudang->id]))
            ->assertForbidden();

        $this->get(route('unit-bisnis.stok.edit', ['barang' => $otherBarang->id, 'gudang_id' => $gudang->id]))
            ->assertNotFound();

        $this->assertDatabaseHas('barangs', ['id' => $otherBarang->id, 'is_aktif' => true]);
    }

    /**
     * @return array{User, Gudang}
     */
    private function makeRecordingContext(string $role = 'Recording Gudang Umum'): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $recording = User::withoutEvents(static fn (): User => User::create([
            'name' => 'Petugas Recording',
            'username' => 'recording-unit',
            'password' => 'password',
            'is_aktif' => true,
        ]));
        $recording->assignRole(Role::findOrCreate($role, 'web'));

        $gudang = Gudang::create([
            'kode' => 'UNIT-TEST',
            'nama' => 'Gudang Unit Test',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $recording->gudang()->attach($gudang);

        return [$recording, $gudang];
    }
}
