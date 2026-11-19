<?php

declare(strict_types=1);

namespace Tests\Feature\UnitBisnis;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\PermintaanBarang;
use App\Models\StokAwal;
use App\Models\TransferBarang;
use App\Models\User;
use App\Services\Store\StokService;
use App\Services\UnitBisnis\RecordingGudangUmumService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class RecordingGUPenerimaanPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_supplier_receipt_persists_header_details_and_increases_unit_stock(): void
    {
        $user = User::withoutEvents(static fn (): User => User::create([
            'name' => 'Petugas Recording Sukahati',
            'username' => 'rec-sukahati',
            'password' => 'password',
            'is_aktif' => true,
        ]));
        $user->assignRole(Role::create(['name' => 'Recording Gudang Umum', 'guard_name' => 'web']));

        $gudang = Gudang::create([
            'kode' => 'SHT',
            'nama' => 'RPA Sukahati',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $user->gudang()->attach($gudang);

        $barang = Barang::create([
            'gudang_id' => $gudang->id,
            'kode' => 'BMB-01',
            'nama' => 'Bumbu Marinasi Kuning',
            'is_aktif' => true,
        ]);

        StokAwal::create([
            'gudang_id' => $gudang->id,
            'barang_id' => $barang->id,
            'qty' => 10,
            'tanggal_acuan' => today(),
        ]);

        $stokService = app(StokService::class);
        $initialTotals = $stokService->totals([$barang->id], (int) $gudang->id);
        $initialStock = (int) ($initialTotals['awal'][$barang->id] ?? 0);
        $this->assertSame(10, $initialStock);

        $payload = [
            'nama_supplier' => 'PT Bumbu Harum Mandiri',
            'has_surat_jalan' => true,
            'po_referensi' => 'PO-LOC-9901',
            'nomor_surat_jalan' => 'SJ-BHM-1102',
            'tanggal_terima' => today()->format('Y-m-d'),
            'waktu_terima' => '10:30',
            'items' => [
                [
                    'barang_id' => $barang->id,
                    'qty_diterima' => 25,
                    'satuan' => 'kg',
                    'kondisi' => 'Baik',
                    'qty_rusak' => 0,
                    'qty_kurang' => 0,
                    'qty_lebih' => 0,
                    'catatan' => 'Kemasan utuh dan segel aman',
                ],
            ],
        ];

        $response = $this->actingAs($user)
            ->postJson(route('unit-bisnis.recording-gu.penerimaan.store', [
                'gudang_id' => $gudang->id,
            ]), $payload);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('penerimaan_units', [
            'gudang_id' => $gudang->id,
            'nama_supplier' => 'PT Bumbu Harum Mandiri',
            'has_surat_jalan' => true,
            'diterima_oleh' => $user->id,
        ]);

        $this->assertDatabaseHas('penerimaan_unit_details', [
            'barang_id' => $barang->id,
            'qty_diterima' => 25,
            'kondisi' => 'Baik',
        ]);

        // Stock in totals must now be: initial (10) + received (25) = 35
        $newTotals = $stokService->totals([$barang->id], (int) $gudang->id);
        $masuk = (int) ($newTotals['masuk'][$barang->id] ?? 0);
        $this->assertSame(25, $masuk);
    }

    public function test_confirm_store_transfer_receipt_creates_unit_pemindahan_and_updates_transfer(): void
    {
        $user = User::withoutEvents(static fn (): User => User::create([
            'name' => 'Petugas Recording Sukahati',
            'username' => 'rec-sukahati-2',
            'password' => 'password',
            'is_aktif' => true,
        ]));
        $user->assignRole(Role::create(['name' => 'Recording Gudang Umum', 'guard_name' => 'web']));

        $gudangStore = Gudang::create([
            'kode' => 'STORE-PUSAT',
            'nama' => 'Gudang Store Pusat',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);

        $gudangUnit = Gudang::create([
            'kode' => 'SHT',
            'nama' => 'RPA Sukahati',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $user->gudang()->attach($gudangUnit);

        $barang = Barang::create([
            'gudang_id' => $gudangUnit->id,
            'kode' => 'PLSTK-01',
            'nama' => 'Plastik Vacuum 20x30',
            'is_aktif' => true,
        ]);

        $permintaan = PermintaanBarang::create([
            'nomor_pr' => 'REQ-SHT-20261029-0001',
            'gudang_peminta_id' => $gudangUnit->id,
            'gudang_tujuan_id' => $gudangStore->id,
            'status' => 'diproses',
            'dibuat_oleh' => $user->id,
        ]);

        $transfer = TransferBarang::create([
            'nomor_sj' => 'OUT-STR-20261029-001',
            'permintaan_barang_id' => $permintaan->id,
            'gudang_asal_id' => $gudangStore->id,
            'gudang_tujuan_id' => $gudangUnit->id,
            'barang_id' => $barang->id,
            'qty_diminta' => 50,
            'qty_dikirim' => 50,
            'qty_diterima' => 0,
            'status' => 'dikirim',
            'dikirim_at' => now(),
        ]);

        $payload = [
            'request_id' => $permintaan->nomor_pr,
            'items' => [
                [
                    'transfer_id' => $transfer->id,
                    'qty_diterima' => 48,
                    'qty_rusak' => 2,
                    'catatan_discrepancy' => '2 pcs cacat cetak seal',
                ],
            ],
        ];

        $response = $this->actingAs($user)
            ->postJson(route('unit-bisnis.recording-gu.penerimaan.confirm-transfer', [
                'gudang_id' => $gudangUnit->id,
            ]), $payload);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('transfer_barangs', [
            'id' => $transfer->id,
            'status' => 'diterima',
            'qty_diterima' => 48,
            'diterima_oleh' => $user->id,
        ]);

        $this->assertDatabaseHas('penerimaan_pemindahan_units', [
            'transfer_barang_id' => $transfer->id,
            'gudang_id' => $gudangUnit->id,
            'barang_id' => $barang->id,
            'qty_dikirim' => 50,
            'qty_diterima' => 48,
            'qty_rusak' => 2,
            'catatan_discrepancy' => '2 pcs cacat cetak seal',
            'diterima_oleh' => $user->id,
        ]);

        // Stock in totals must reflect received minus damaged = 48 - 2 = 46
        $stokService = app(StokService::class);
        $totals = $stokService->totals([$barang->id], (int) $gudangUnit->id);
        $masuk = (int) ($totals['masuk'][$barang->id] ?? 0);
        $this->assertSame(46, $masuk);
    }

    public function test_admin_gu_cannot_access_recording_gu_physical_receipt_routes(): void
    {
        $admin = User::withoutEvents(static fn (): User => User::create([
            'name' => 'Admin Gudang Umum',
            'username' => 'admin-gu-no-receipt',
            'password' => 'password',
            'is_aktif' => true,
        ]));
        $admin->assignRole(Role::create(['name' => 'Admin Gudang Umum', 'guard_name' => 'web']));

        $this->actingAs($admin)
            ->get(route('unit-bisnis.recording-gu.penerimaan.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson(route('unit-bisnis.recording-gu.penerimaan.store'), [])
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson(route('unit-bisnis.recording-gu.penerimaan.confirm-transfer'), [])
            ->assertForbidden();
    }

    public function test_store_requests_only_shows_dispatched_transfers_and_excludes_pending_requests(): void
    {
        $user = User::withoutEvents(static fn (): User => User::create([
            'name' => 'Petugas Recording Sukahati',
            'username' => 'rec-filter-test',
            'password' => 'password',
            'is_aktif' => true,
        ]));
        $user->assignRole(Role::create(['name' => 'Recording Gudang Umum', 'guard_name' => 'web']));

        $gudangStore = Gudang::create([
            'kode' => 'STORE-PUSAT-2',
            'nama' => 'Gudang Store Pusat',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);

        $gudangUnit = Gudang::create([
            'kode' => 'SHT-2',
            'nama' => 'RPA Sukahati Unit 2',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $user->gudang()->attach($gudangUnit);

        $barang = Barang::create([
            'gudang_id' => $gudangUnit->id,
            'kode' => 'ITEM-01',
            'nama' => 'Plastik Seal',
            'is_aktif' => true,
        ]);

        // 1. Pending request without transfer (harus TIDAK muncul di dropdown penerimaan fisik)
        PermintaanBarang::create([
            'nomor_pr' => 'REQ-SHT-PENDING-001',
            'gudang_peminta_id' => $gudangUnit->id,
            'gudang_tujuan_id' => $gudangStore->id,
            'status' => 'baru',
            'dibuat_oleh' => $user->id,
        ]);

        // 2. Transfer with status 'diminta' (menunggu konfirmasi kirim store, belum jalan - harus TIDAK muncul)
        TransferBarang::create([
            'nomor_sj' => 'OUT-DRAFT-001',
            'gudang_asal_id' => $gudangStore->id,
            'gudang_tujuan_id' => $gudangUnit->id,
            'barang_id' => $barang->id,
            'qty_diminta' => 10,
            'qty_dikirim' => 0,
            'qty_diterima' => 0,
            'status' => 'diminta',
        ]);

        // 3. Transfer with status 'dikirim' (sedang dikirim supir - HARUS MUNCUL)
        TransferBarang::create([
            'nomor_sj' => 'OUT-ACTIVE-001-1',
            'gudang_asal_id' => $gudangStore->id,
            'gudang_tujuan_id' => $gudangUnit->id,
            'barang_id' => $barang->id,
            'qty_diminta' => 15,
            'qty_dikirim' => 15,
            'qty_diterima' => 0,
            'status' => 'dikirim',
            'dikirim_at' => now(),
        ]);

        // 4. Transfer with status 'diterima' (sudah diterima - HARUS MUNCUL DI HISTORY, BUKAN PICKER)
        TransferBarang::create([
            'nomor_sj' => 'OUT-DONE-001-1',
            'gudang_asal_id' => $gudangStore->id,
            'gudang_tujuan_id' => $gudangUnit->id,
            'barang_id' => $barang->id,
            'qty_diminta' => 20,
            'qty_dikirim' => 20,
            'qty_diterima' => 20,
            'status' => 'diterima',
            'diterima_at' => now(),
        ]);

        $service = app(RecordingGudangUmumService::class);
        $activeRequests = $service->getStoreRequestsForGudang($gudangUnit);
        $history = $service->getStoreReceiptsHistoryForGudang($gudangUnit);

        // Hanya 1 transfer aktif yang dikirim
        $this->assertCount(1, $activeRequests);
        $this->assertSame('OUT-ACTIVE-001', $activeRequests[0]['deliveryNote']);
        $this->assertSame('menunggu_diterima', $activeRequests[0]['status']);

        // 1 transfer di histori
        $this->assertCount(1, $history);
        $this->assertSame('OUT-DONE-001-1', $history[0]['nomor_sj']);
    }
}
