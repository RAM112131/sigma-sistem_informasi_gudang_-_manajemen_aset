<?php

declare(strict_types=1);

namespace Tests\Feature\UnitBisnis;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\PenerimaanPemindahanUnit;
use App\Models\PenerimaanUnit;
use App\Models\PenerimaanUnitDetail;
use App\Models\PermintaanBarang;
use App\Models\TransferBarang;
use App\Models\User;
use App\Services\Store\StokService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AdminGURekonsiliasiPenerimaanTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_tabs_only_show_receipts_for_the_active_warehouse_and_selected_status(): void
    {
        [$admin, $gudang] = $this->makeAdminContext('GU-RECEIPT-TABS');
        $this->makeReceipt($gudang, 'RCV-PENDING');
        $this->makeReceipt($gudang, 'RCV-VERIFIED', 'terverifikasi', [
            'qty_diperiksa' => 10,
            'qty_rusak_diperiksa' => 0,
        ]);
        [$followUp] = $this->makeReceipt($gudang, 'RCV-FOLLOWUP', 'perlu_tindak_lanjut', [
            'qty_diperiksa' => 8,
            'qty_rusak_diperiksa' => 1,
        ]);
        $otherWarehouse = $this->makeGudang('GU-RECEIPT-OTHER');
        $this->makeReceipt($otherWarehouse, 'RCV-OTHER');

        $response = $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index', [
                'status' => 'tindak_lanjut',
            ]));

        $response->assertOk()
            ->assertSee('Rekonsiliasi Penerimaan')
            ->assertSee('No. Dokumen')
            ->assertSee('Jenis')
            ->assertSee('Sumber')
            ->assertSee('Langsung')
            ->assertSee('PT Supplier Contoh')
            ->assertSee('Menunggu Diverifikasi')
            ->assertSee('Sudah Diverifikasi')
            ->assertSee('Tindak Lanjut & Klaim')
            ->assertSee('RCV-FOLLOWUP')
            ->assertDontSee('RCV-PENDING')
            ->assertDontSee('RCV-VERIFIED')
            ->assertDontSee('RCV-OTHER');

        self::assertSame(
            [$followUp->getKey()],
            $response->viewData('receipts')->getCollection()->pluck('id')->all(),
        );
        self::assertSame(1, $response->viewData('metrics')['pending_count']);
        self::assertSame(1, $response->viewData('metrics')['verified_count']);
        self::assertSame(1, $response->viewData('metrics')['follow_up_count']);
    }

    public function test_pagination_clears_search_query_and_preserves_other_filters(): void
    {
        [$admin, $gudang] = $this->makeAdminContext('GU-RECEIPT-PAGINATION');

        for ($index = 1; $index <= 13; $index++) {
            $this->makeReceipt($gudang, sprintf('RCV-PAGINATION-%02d', $index));
        }

        $response = $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index', [
                'gudang_id' => $gudang->getKey(),
                'status' => 'menunggu_verifikasi',
                'q' => 'RCV-PAGINATION',
            ]));

        $response->assertOk();

        $receipts = $response->viewData('receipts');
        self::assertSame(true, $receipts->hasPages());

        $pageQuery = [];
        parse_str((string) parse_url($receipts->url(2), PHP_URL_QUERY), $pageQuery);

        self::assertSame('2', $pageQuery['page']);
        self::assertSame('menunggu_verifikasi', $pageQuery['status']);
        self::assertSame((string) $gudang->getKey(), $pageQuery['gudang_id']);
        self::assertSame(null, $pageQuery['q'] ?? null);
    }

    public function test_verification_saves_physical_quantities_price_and_routes_discrepancies_to_follow_up(): void
    {
        [$admin, $gudang] = $this->makeAdminContext('GU-RECEIPT-VERIFY');
        $barang = $this->makeBarang($gudang, 'RECEIPT-VERIFY-001');
        [$receipt, $detail] = $this->makeReceipt($gudang, 'RCV-VERIFY', 'menunggu_verifikasi', [
            'barang_id' => $barang->getKey(),
            'qty_diterima' => 10,
            'qty_rusak' => 0,
        ]);

        $stokService = app(StokService::class);
        self::assertSame(
            10.0,
            (float) $stokService->totals([$barang->getKey()], (int) $gudang->getKey())['masuk'][$barang->getKey()],
        );

        $response = $this->actingAs($admin)->post(
            route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.verify', $receipt->getKey()),
            [
                'gudang_id' => $gudang->getKey(),
                'sumber_penerimaan' => 'supplier',
                'items' => [[
                    'detail_id' => $detail->getKey(),
                    'qty_diperiksa' => 8,
                    'qty_rusak_diperiksa' => 1,
                    'harga_beli' => 2500,
                ]],
                'catatan_verifikasi' => 'Satu unit rusak, jumlah fisik kurang.',
            ],
        );

        $response->assertRedirect(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index', [
            'gudang_id' => $gudang->getKey(),
            'penerimaan_id' => $receipt->getKey(),
            'sumber_penerimaan' => 'supplier',
            'status' => 'tindak_lanjut',
        ]))
            ->assertSessionHas('success', 'Penerimaan diverifikasi dan masuk antrean tindak lanjut karena ditemukan selisih atau barang rusak.');

        $this->assertDatabaseHas('penerimaan_units', [
            'id' => $receipt->getKey(),
            'status_verifikasi' => 'perlu_tindak_lanjut',
            'diverifikasi_oleh' => $admin->getKey(),
            'catatan_verifikasi' => 'Satu unit rusak, jumlah fisik kurang.',
        ]);
        $this->assertDatabaseHas('penerimaan_unit_details', [
            'id' => $detail->getKey(),
            'qty_diterima' => 10,
            'qty_diperiksa' => 8,
            'qty_rusak_diperiksa' => 1,
            'harga_beli' => 2500,
            'subtotal_nilai' => 17500,
        ]);
        self::assertSame(
            7.0,
            (float) $stokService->totals([$barang->getKey()], (int) $gudang->getKey())['masuk'][$barang->getKey()],
        );
    }

    public function test_store_request_receipt_recorded_by_recording_gu_is_reconciled_without_double_counting_stock(): void
    {
        [$admin, $gudang] = $this->makeAdminContext('GU-REQUEST-RECONCILIATION');
        $recording = User::factory()->create();
        $recording->assignRole(Role::findOrCreate('Recording Gudang Umum', 'web'));
        $recording->gudang()->attach($gudang);

        $store = Gudang::query()->create([
            'kode' => 'STORE-REQUEST-RECONCILIATION',
            'nama' => 'Gudang Store',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $barang = $this->makeBarang($gudang, 'REQUEST-RECONCILIATION-001');
        $permintaan = PermintaanBarang::query()->create([
            'nomor_pr' => 'REQ-RECONCILIATION-001',
            'gudang_peminta_id' => $gudang->getKey(),
            'gudang_tujuan_id' => $store->getKey(),
            'status' => 'diproses',
            'dibuat_oleh' => $recording->getKey(),
        ]);
        $transfer = TransferBarang::query()->create([
            'nomor_sj' => 'OUT-RECONCILIATION-001',
            'permintaan_barang_id' => $permintaan->getKey(),
            'gudang_asal_id' => $store->getKey(),
            'gudang_tujuan_id' => $gudang->getKey(),
            'barang_id' => $barang->getKey(),
            'qty_diminta' => 50,
            'qty_dikirim' => 50,
            'qty_diterima' => 0,
            'status' => 'dikirim',
        ]);

        $this->actingAs($recording)
            ->postJson(route('unit-bisnis.recording-gu.penerimaan.confirm-transfer', [
                'gudang_id' => $gudang->getKey(),
            ]), [
                'request_id' => $permintaan->nomor_pr,
                'items' => [[
                    'transfer_id' => $transfer->getKey(),
                    'qty_diterima' => 48,
                    'qty_rusak' => 2,
                    'catatan_discrepancy' => 'Dua unit rusak.',
                ]],
            ])
            ->assertOk();

        $receipt = PenerimaanPemindahanUnit::query()
            ->where('transfer_barang_id', $transfer->getKey())
            ->firstOrFail();
        $stokService = app(StokService::class);
        self::assertSame(
            46.0,
            (float) $stokService->totals([$barang->getKey()], (int) $gudang->getKey())['masuk'][$barang->getKey()],
        );

        $queueResponse = $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index', [
                'gudang_id' => $gudang->getKey(),
                'status' => 'menunggu_verifikasi',
                'penerimaan_id' => $receipt->getKey(),
                'sumber_penerimaan' => 'permintaan',
            ]))
            ->assertOk()
            ->assertSee($transfer->nomor_sj)
            ->assertSee($permintaan->nomor_pr)
            ->assertSee('Dari Store')
            ->assertSee('Gudang Store')
            ->assertSee('Verifikasi Penerimaan');
        self::assertSame(1, $queueResponse->viewData('metrics')['pending_count']);

        $this->actingAs($admin)
            ->post(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.verify', $receipt->getKey()), [
                'gudang_id' => $gudang->getKey(),
                'sumber_penerimaan' => 'permintaan',
                'items' => [[
                    'detail_id' => $receipt->getKey(),
                    'qty_diperiksa' => 47,
                    'qty_rusak_diperiksa' => 3,
                ]],
                'catatan_verifikasi' => 'Pemeriksaan ulang menemukan satu unit tambahan rusak.',
            ])
            ->assertRedirect(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index', [
                'gudang_id' => $gudang->getKey(),
                'penerimaan_id' => $receipt->getKey(),
                'sumber_penerimaan' => 'permintaan',
                'status' => 'tindak_lanjut',
            ]));

        $this->assertDatabaseHas('penerimaan_pemindahan_units', [
            'id' => $receipt->getKey(),
            'qty_diperiksa' => 47,
            'qty_rusak_diperiksa' => 3,
            'status_verifikasi' => 'perlu_tindak_lanjut',
            'diverifikasi_oleh' => $admin->getKey(),
        ]);
        self::assertSame(
            44.0,
            (float) $stokService->totals([$barang->getKey()], (int) $gudang->getKey())['masuk'][$barang->getKey()],
        );

        $this->actingAs($admin)
            ->post(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.follow-up', $receipt->getKey()), [
                'gudang_id' => $gudang->getKey(),
                'sumber_penerimaan' => 'permintaan',
                'catatan_tindak_lanjut' => 'Konfirmasi penggantian diminta dari Store.',
            ])
            ->assertRedirect(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index', [
                'gudang_id' => $gudang->getKey(),
                'penerimaan_id' => $receipt->getKey(),
                'sumber_penerimaan' => 'permintaan',
                'status' => 'tindak_lanjut',
            ]));

        self::assertStringContainsString(
            'Konfirmasi penggantian diminta dari Store.',
            (string) $receipt->fresh()->catatan_verifikasi,
        );
    }

    public function test_store_request_receipt_without_discrepancy_moves_to_verified_tab(): void
    {
        [$admin, $gudang] = $this->makeAdminContext('GU-REQUEST-VERIFIED');
        $store = Gudang::query()->create([
            'kode' => 'STORE-REQUEST-VERIFIED',
            'nama' => 'Gudang Store',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $barang = $this->makeBarang($gudang, 'REQUEST-VERIFIED-001');
        $permintaan = PermintaanBarang::query()->create([
            'nomor_pr' => 'REQ-VERIFIED-001',
            'gudang_peminta_id' => $gudang->getKey(),
            'gudang_tujuan_id' => $store->getKey(),
            'status' => 'selesai',
        ]);
        $transfer = TransferBarang::query()->create([
            'nomor_sj' => 'OUT-VERIFIED-001',
            'permintaan_barang_id' => $permintaan->getKey(),
            'gudang_asal_id' => $store->getKey(),
            'gudang_tujuan_id' => $gudang->getKey(),
            'barang_id' => $barang->getKey(),
            'qty_diminta' => 5,
            'qty_dikirim' => 5,
            'qty_diterima' => 5,
            'status' => 'diterima',
        ]);
        $receipt = PenerimaanPemindahanUnit::query()->create([
            'transfer_barang_id' => $transfer->getKey(),
            'gudang_id' => $gudang->getKey(),
            'barang_id' => $barang->getKey(),
            'qty_dikirim' => 5,
            'qty_diterima' => 5,
            'qty_rusak' => 0,
            'diterima_at' => now(),
        ]);
        $stokService = app(StokService::class);

        $this->actingAs($admin)
            ->post(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.verify', $receipt->getKey()), [
                'gudang_id' => $gudang->getKey(),
                'sumber_penerimaan' => 'permintaan',
                'items' => [[
                    'detail_id' => $receipt->getKey(),
                    'qty_diperiksa' => 5,
                    'qty_rusak_diperiksa' => 0,
                ]],
            ])
            ->assertRedirect(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index', [
                'gudang_id' => $gudang->getKey(),
                'penerimaan_id' => $receipt->getKey(),
                'sumber_penerimaan' => 'permintaan',
                'status' => 'sudah_diverifikasi',
            ]));

        $this->assertDatabaseHas('penerimaan_pemindahan_units', [
            'id' => $receipt->getKey(),
            'status_verifikasi' => 'terverifikasi',
            'qty_diperiksa' => 5,
            'qty_rusak_diperiksa' => 0,
        ]);
        $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index', [
                'gudang_id' => $gudang->getKey(),
                'status' => 'sudah_diverifikasi',
            ]))
            ->assertOk()
            ->assertSee($transfer->nomor_sj)
            ->assertSee($permintaan->nomor_pr);
        self::assertSame(
            5.0,
            (float) $stokService->totals([$barang->getKey()], (int) $gudang->getKey())['masuk'][$barang->getKey()],
        );
    }

    public function test_damaged_quantity_above_physical_quantity_is_rejected_without_writing(): void
    {
        [$admin, $gudang] = $this->makeAdminContext('GU-RECEIPT-INVALID');
        [$receipt, $detail] = $this->makeReceipt($gudang, 'RCV-INVALID');

        $this->actingAs($admin)
            ->from(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index'))
            ->post(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.verify', $receipt->getKey()), [
                'gudang_id' => $gudang->getKey(),
                'sumber_penerimaan' => 'supplier',
                'items' => [[
                    'detail_id' => $detail->getKey(),
                    'qty_diperiksa' => 5,
                    'qty_rusak_diperiksa' => 6,
                    'harga_beli' => 1000,
                ]],
            ])
            ->assertRedirect(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index'))
            ->assertSessionHasErrors('items');

        $this->assertDatabaseHas('penerimaan_units', [
            'id' => $receipt->getKey(),
            'status_verifikasi' => 'menunggu_verifikasi',
        ]);
        $this->assertDatabaseHas('penerimaan_unit_details', [
            'id' => $detail->getKey(),
            'qty_diperiksa' => null,
            'qty_rusak_diperiksa' => null,
        ]);
    }

    public function test_follow_up_notes_are_appended_without_removing_verification_notes(): void
    {
        [$admin, $gudang] = $this->makeAdminContext('GU-RECEIPT-CLAIM');
        [$receipt] = $this->makeReceipt($gudang, 'RCV-CLAIM', 'perlu_tindak_lanjut', [
            'qty_diperiksa' => 8,
            'qty_rusak_diperiksa' => 1,
        ]);
        $receipt->update(['catatan_verifikasi' => 'Ditemukan barang rusak.']);
        $this->travelTo('2026-11-16 08:00:00');

        $this->actingAs($admin)
            ->post(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.follow-up', $receipt->getKey()), [
                'gudang_id' => $gudang->getKey(),
                'sumber_penerimaan' => 'supplier',
                'catatan_tindak_lanjut' => 'Klaim supplier diajukan: KLM-22.',
            ])
            ->assertRedirect(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index', [
                'gudang_id' => $gudang->getKey(),
                'penerimaan_id' => $receipt->getKey(),
                'sumber_penerimaan' => 'supplier',
                'status' => 'tindak_lanjut',
            ]));

        $receipt->refresh();
        self::assertSame(
            "Ditemukan barang rusak.\n[2026-11-16 08:00 · {$admin->name}] Klaim supplier diajukan: KLM-22.",
            $receipt->catatan_verifikasi,
        );
        self::assertSame('perlu_tindak_lanjut', $receipt->status_verifikasi);
    }

    public function test_guest_and_recording_role_cannot_open_admin_reconciliation(): void
    {
        $this->get(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index'))
            ->assertRedirect(route('login'));

        $recording = User::factory()->create();
        $recording->assignRole(Role::findOrCreate('Recording Gudang Umum', 'web'));

        $this->actingAs($recording)
            ->get(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index'))
            ->assertForbidden();
    }

    public function test_admin_cannot_reconcile_a_receipt_from_an_unassigned_warehouse(): void
    {
        [$admin, $assignedGudang] = $this->makeAdminContext('GU-RECEIPT-ASSIGNED');
        $otherWarehouse = $this->makeGudang('GU-RECEIPT-UNASSIGNED');
        [$receipt, $detail] = $this->makeReceipt($otherWarehouse, 'RCV-UNASSIGNED');

        $this->actingAs($admin)
            ->post(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.verify', $receipt->getKey()), [
                'gudang_id' => $assignedGudang->getKey(),
                'sumber_penerimaan' => 'supplier',
                'items' => [[
                    'detail_id' => $detail->getKey(),
                    'qty_diperiksa' => 5,
                    'qty_rusak_diperiksa' => 0,
                    'harga_beli' => 1000,
                ]],
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('penerimaan_units', [
            'id' => $receipt->getKey(),
            'status_verifikasi' => 'menunggu_verifikasi',
        ]);
    }

    /**
     * @return array{0: User, 1: Gudang}
     */
    private function makeAdminContext(string $kode): array
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin Gudang Umum', 'web'));
        $gudang = $this->makeGudang($kode);
        $admin->gudang()->attach($gudang);

        return [$admin, $gudang];
    }

    public function test_store_transfer_receipts_are_grouped_and_verified_by_surat_jalan_number(): void
    {
        [$admin, $gudang] = $this->makeAdminContext('GU-SJ-GROUP');
        $store = Gudang::query()->create([
            'kode' => 'STORE-SJ-GROUP',
            'nama' => 'Gudang Store Armada',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $barang1 = $this->makeBarang($gudang, 'SJ-ITEM-01');
        $barang2 = $this->makeBarang($gudang, 'SJ-ITEM-02');
        $permintaan = PermintaanBarang::query()->create([
            'nomor_pr' => 'REQ-SHT-30092026615-0002',
            'gudang_peminta_id' => $gudang->getKey(),
            'gudang_tujuan_id' => $store->getKey(),
            'status' => 'selesai',
        ]);

        $transfer1 = TransferBarang::query()->create([
            'nomor_sj' => 'OUT-STR-28112026831-0002-01',
            'permintaan_barang_id' => $permintaan->getKey(),
            'gudang_asal_id' => $store->getKey(),
            'gudang_tujuan_id' => $gudang->getKey(),
            'barang_id' => $barang1->getKey(),
            'qty_diminta' => 10,
            'qty_dikirim' => 10,
            'qty_diterima' => 10,
            'status' => 'diterima',
        ]);
        $transfer2 = TransferBarang::query()->create([
            'nomor_sj' => 'OUT-STR-28112026831-0002-02',
            'permintaan_barang_id' => $permintaan->getKey(),
            'gudang_asal_id' => $store->getKey(),
            'gudang_tujuan_id' => $gudang->getKey(),
            'barang_id' => $barang2->getKey(),
            'qty_diminta' => 5,
            'qty_dikirim' => 5,
            'qty_diterima' => 5,
            'status' => 'diterima',
        ]);

        $receipt1 = PenerimaanPemindahanUnit::query()->create([
            'transfer_barang_id' => $transfer1->getKey(),
            'gudang_id' => $gudang->getKey(),
            'barang_id' => $barang1->getKey(),
            'qty_dikirim' => 10,
            'qty_diterima' => 10,
            'qty_rusak' => 0,
            'diterima_at' => now(),
        ]);
        $receipt2 = PenerimaanPemindahanUnit::query()->create([
            'transfer_barang_id' => $transfer2->getKey(),
            'gudang_id' => $gudang->getKey(),
            'barang_id' => $barang2->getKey(),
            'qty_dikirim' => 5,
            'qty_diterima' => 5,
            'qty_rusak' => 0,
            'diterima_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index', [
                'gudang_id' => $gudang->getKey(),
                'status' => 'menunggu_verifikasi',
            ]));

        $response->assertOk()
            ->assertSee('<span>Rekonsiliasi Penerimaan</span>', false)
            ->assertDontSee('<span>Verifikasi Penerimaan</span>', false)
            ->assertSee('OUT-STR-28112026831-0002')
            ->assertDontSee('OUT-STR-28112026831-0002-01')
            ->assertDontSee('OUT-STR-28112026831-0002-02')
            ->assertSee('Pilih penerimaan untuk mulai rekonsiliasi')
            ->assertDontSee('Aktif Dibuka')
            ->assertDontSee('SJ-ITEM-01');

        self::assertCount(1, $response->viewData('receipts')->getCollection());
        self::assertSame(2, $response->viewData('receipts')->first()->details_count);
        self::assertSame(1, $response->viewData('metrics')['pending_count']);

        $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index', [
                'gudang_id' => $gudang->getKey(),
                'status' => 'menunggu_verifikasi',
                'penerimaan_id' => $receipt1->getKey(),
                'sumber_penerimaan' => 'permintaan',
            ]))
            ->assertOk()
            ->assertSeeText('Aktif Dibuka')
            ->assertSee('SJ-ITEM-01')
            ->assertSee('SJ-ITEM-02');

        $this->actingAs($admin)
            ->post(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.verify', $receipt1->getKey()), [
                'gudang_id' => $gudang->getKey(),
                'sumber_penerimaan' => 'permintaan',
                'items' => [
                    [
                        'detail_id' => $receipt1->getKey(),
                        'qty_diperiksa' => 10,
                        'qty_rusak_diperiksa' => 0,
                    ],
                    [
                        'detail_id' => $receipt2->getKey(),
                        'qty_diperiksa' => 5,
                        'qty_rusak_diperiksa' => 0,
                    ],
                ],
            ])
            ->assertRedirect(route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index', [
                'gudang_id' => $gudang->getKey(),
                'penerimaan_id' => $receipt1->getKey(),
                'sumber_penerimaan' => 'permintaan',
                'status' => 'sudah_diverifikasi',
            ]));

        $this->assertDatabaseHas('penerimaan_pemindahan_units', [
            'id' => $receipt1->getKey(),
            'status_verifikasi' => 'terverifikasi',
            'qty_diperiksa' => 10,
        ]);
        $this->assertDatabaseHas('penerimaan_pemindahan_units', [
            'id' => $receipt2->getKey(),
            'status_verifikasi' => 'terverifikasi',
            'qty_diperiksa' => 5,
        ]);
    }

    private function makeGudang(string $kode): Gudang
    {
        return Gudang::query()->create([
            'kode' => $kode,
            'nama' => "Gudang {$kode}",
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
    }

    private function makeBarang(Gudang $gudang, string $kode): Barang
    {
        return Barang::query()->create([
            'gudang_id' => $gudang->getKey(),
            'kode' => $kode,
            'nama' => "Barang {$kode}",
            'harga_satuan' => 2500,
            'is_aktif' => true,
        ]);
    }

    /**
     * @param  array<string, int|float|string|null>  $detailAttributes
     * @return array{0: PenerimaanUnit, 1: PenerimaanUnitDetail}
     */
    private function makeReceipt(
        Gudang $gudang,
        string $nomorRcv,
        string $status = 'menunggu_verifikasi',
        array $detailAttributes = [],
    ): array {
        $receipt = PenerimaanUnit::query()->create([
            'nomor_rcv' => $nomorRcv,
            'gudang_id' => $gudang->getKey(),
            'nama_supplier' => 'PT Supplier Contoh',
            'tanggal_terima' => today(),
            'waktu_terima' => '10:00',
            'status_verifikasi' => $status,
        ]);
        $detail = $receipt->details()->create(array_merge([
            'barang_id' => null,
            'kode_barang' => 'RCV-ITEM-001',
            'nama_barang' => 'Barang Penerimaan',
            'kategori' => 'Bahan Baku',
            'satuan' => 'kg',
            'qty_diterima' => 10,
            'qty_rusak' => 0,
            'qty_kurang' => 0,
            'qty_lebih' => 0,
            'harga_beli' => 0,
            'subtotal_nilai' => 0,
        ], $detailAttributes));

        return [$receipt, $detail];
    }
}
