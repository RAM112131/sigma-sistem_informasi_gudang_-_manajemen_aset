<?php

declare(strict_types=1);

namespace Tests\Feature\UnitBisnis;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\PengeluaranUnit;
use App\Models\PengeluaranUnitDetail;
use App\Models\StokAwal;
use App\Models\StokOpname;
use App\Models\User;
use App\Services\Store\StokService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class StokOpnameBulananTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_only_assigned_warehouse_items_and_the_current_system_balance(): void
    {
        $this->travelTo('2026-11-16 12:00:00');
        [$admin, $gudang] = $this->makeAdminContext('GU-OPNAME-LIST');
        $barang = $this->makeBarang($gudang, 'OPNAME-001', '<script>alert(1)</script>');
        StokAwal::query()->create([
            'gudang_id' => $gudang->getKey(),
            'barang_id' => $barang->getKey(),
            'qty' => 10,
            'tanggal_acuan' => '2026-10-01',
            'sumber' => 'manual',
        ]);
        $otherGudang = $this->makeGudang('GU-OPNAME-OTHER');
        $this->makeBarang($otherGudang, 'PRIVATE-001', 'Barang Gudang Lain');

        $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.stok-opname.index', ['periode' => '2026-10']))
            ->assertOk()
            ->assertSee('Stok Opname Bulanan')
            ->assertSee('Proses Opname')
            ->assertSee('Draf Opname')
            ->assertSee('btn-primary', false)
            ->assertSee('btn-outline', false)
            ->assertSee(route('unit-bisnis.admin-gu.stok-opname.drafts'))
            ->assertSee('OPNAME-001')
            ->assertDontSee('PRIVATE-001')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertViewHas('items', static function ($items) use ($barang): bool {
                self::assertSame($barang->getKey(), $items->first()['barang']->getKey());
                self::assertSame(10.0, $items->first()['saldo_sistem']);

                return true;
            });
    }

    public function test_current_month_is_available_as_the_default_stocktake_period(): void
    {
        $this->travelTo('2026-11-18 12:00:00');
        [$admin] = $this->makeAdminContext('GU-OPNAME-CURRENT-PERIOD');

        $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.stok-opname.index'))
            ->assertOk()
            ->assertViewHas('period', '2026-11')
            ->assertViewHas('periodOptions', static function (array $periodOptions): bool {
                self::assertArrayHasKey('2026-11', $periodOptions);

                return true;
            });
    }

    public function test_guest_and_recording_role_cannot_open_monthly_stocktake(): void
    {
        $this->get(route('unit-bisnis.admin-gu.stok-opname.index'))
            ->assertRedirect(route('login'));
        $this->get(route('unit-bisnis.admin-gu.stok-opname.drafts'))
            ->assertRedirect(route('login'));

        $recording = User::factory()->create();
        $recording->assignRole(Role::findOrCreate('Recording Gudang Umum', 'web'));

        $this->actingAs($recording)
            ->get(route('unit-bisnis.admin-gu.stok-opname.index'))
            ->assertForbidden();

        $this->get(route('unit-bisnis.admin-gu.stok-opname.drafts'))
            ->assertForbidden();
    }

    public function test_admin_can_view_and_reopen_only_assigned_saved_stocktake_drafts(): void
    {
        $this->travelTo('2026-11-16 12:00:00');
        [$admin, $gudang] = $this->makeAdminContext('GU-OPNAME-DRAFT-LIST');
        $countedBarang = $this->makeBarang($gudang, 'OPNAME-DRAFT-001');
        $this->makeBarang($gudang, 'OPNAME-DRAFT-002');
        $otherGudang = $this->makeGudang('GU-OPNAME-DRAFT-OTHER');
        StokOpname::query()->create([
            'gudang_id' => $otherGudang->getKey(),
            'periode' => '2026-10-01',
            'tanggal_efektif' => '2026-10-31',
            'status' => 'draft',
            'dibuat_oleh' => $admin->getKey(),
        ]);

        $this->actingAs($admin)
            ->post(route('unit-bisnis.admin-gu.stok-opname.draft'), [
                'periode' => '2026-10',
                'gudang_id' => $gudang->getKey(),
                'items' => [
                    ['barang_id' => $countedBarang->getKey(), 'qty_fisik' => 3],
                ],
                'snapshot_barang_id' => $countedBarang->getKey(),
            ])
            ->assertRedirect();

        $ownDraft = StokOpname::query()
            ->where('gudang_id', $gudang->getKey())
            ->whereDate('periode', '2026-10-01')
            ->firstOrFail();

        $response = $this->get(route('unit-bisnis.admin-gu.stok-opname.drafts'));

        $response->assertOk()
            ->assertSee('Draf Stok Opname')
            ->assertSee('Proses Opname')
            ->assertSee('Draf Opname')
            ->assertSee(route('unit-bisnis.admin-gu.stok-opname.index'))
            ->assertSee('Lanjutkan')
            ->assertDontSee($otherGudang->nama)
            ->assertSee(e(route('unit-bisnis.admin-gu.stok-opname.index', [
                'periode' => '2026-10',
                'gudang_id' => $gudang->getKey(),
            ])), false)
            ->assertViewHas('drafts', static function ($drafts) use ($ownDraft, $gudang): bool {
                self::assertSame([$ownDraft->getKey()], $drafts->getCollection()->modelKeys());
                self::assertSame($gudang->nama, $drafts->first()->gudang->nama);
                self::assertSame(1, $drafts->first()->counted_details_count);
                self::assertSame(2, $drafts->first()->details_count);

                return true;
            });
    }

    public function test_admin_sees_an_empty_state_when_no_saved_stocktake_drafts_exist(): void
    {
        [$admin] = $this->makeAdminContext('GU-OPNAME-DRAFT-EMPTY');

        $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.stok-opname.drafts'))
            ->assertOk()
            ->assertSee('Belum ada draf opname');
    }

    public function test_super_admin_can_view_drafts_for_active_general_warehouses_without_assignment(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(Role::findOrCreate('Super Admin', 'web'));
        $gudang = $this->makeGudang('GU-OPNAME-SUPER-DRAFT');
        StokOpname::query()->create([
            'gudang_id' => $gudang->getKey(),
            'periode' => '2026-10-01',
            'tanggal_efektif' => '2026-10-31',
            'status' => 'draft',
            'dibuat_oleh' => $superAdmin->getKey(),
        ]);

        $this->actingAs($superAdmin)
            ->get(route('unit-bisnis.admin-gu.stok-opname.drafts'))
            ->assertOk()
            ->assertSee($gudang->nama);
    }

    public function test_stocktake_page_size_is_capped_at_twenty_items(): void
    {
        $this->travelTo('2026-11-16 12:00:00');
        [$admin, $gudang] = $this->makeAdminContext('GU-OPNAME-PAGINATION');

        for ($number = 1; $number <= 21; $number++) {
            $this->makeBarang($gudang, sprintf('OPNAME-PAGE-%02d', $number));
        }

        $this->actingAs($admin)
            ->get(route('unit-bisnis.admin-gu.stok-opname.index', [
                'periode' => '2026-10',
                'per_page' => 20,
            ]))
            ->assertOk()
            ->assertViewHas('items', static function ($items): bool {
                self::assertSame(20, $items->perPage());
                self::assertCount(20, $items);
                self::assertSame(21, $items->total());

                return true;
            });

        $this->get(route('unit-bisnis.admin-gu.stok-opname.index', [
            'periode' => '2026-10',
            'per_page' => 25,
        ]))->assertSessionHasErrors('per_page');
    }

    public function test_draft_does_not_change_stock_and_apply_records_one_adjustment(): void
    {
        $this->travelTo('2026-11-16 12:00:00');
        [$admin, $gudang] = $this->makeAdminContext('GU-OPNAME-APPLY');
        $barang = $this->makeBarang($gudang, 'OPNAME-APPLY-001', 'Barang Selisih', 1000);
        StokAwal::query()->create([
            'gudang_id' => $gudang->getKey(),
            'barang_id' => $barang->getKey(),
            'qty' => 10,
            'tanggal_acuan' => '2026-10-01',
            'sumber' => 'manual',
        ]);
        $payload = [
            'periode' => '2026-10',
            'gudang_id' => $gudang->getKey(),
            'items' => [
                ['barang_id' => $barang->getKey(), 'qty_fisik' => 8, 'catatan' => 'Barang rusak saat dihitung.'],
            ],
        ];

        $this->actingAs($admin)
            ->post(route('unit-bisnis.admin-gu.stok-opname.draft'), [
                ...$payload,
                'snapshot_barang_id' => $barang->getKey(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Draf opname berhasil disimpan. Saldo stok belum berubah.');

        $this->assertDatabaseHas('stok_opnames', [
            'gudang_id' => $gudang->getKey(),
            'periode' => '2026-10-01',
            'status' => 'draft',
        ]);
        self::assertSame(10.0, app(StokService::class)->saldo($barang, (int) $gudang->getKey())['saldo']);

        $this->post(route('unit-bisnis.admin-gu.stok-opname.apply'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success', 'Penyesuaian stok berhasil diterapkan dan tercatat pada kartu stok.');

        $opname = StokOpname::query()->where('gudang_id', $gudang->getKey())->firstOrFail();
        $this->assertDatabaseHas('stok_opnames', [
            'id' => $opname->getKey(),
            'status' => 'diterapkan',
            'diterapkan_oleh' => $admin->getKey(),
        ]);
        self::assertSame(8.0, app(StokService::class)->saldo($barang, (int) $gudang->getKey())['saldo']);
        self::assertSame(
            'ADJ-SO-2610',
            app(StokService::class)->mutations($barang, (int) $gudang->getKey())->last()['ref'],
        );
        $historicalTotals = app(StokService::class)->totals(
            [$barang->getKey()],
            (int) $gudang->getKey(),
            Carbon::parse('2026-10-31'),
        );
        self::assertSame(
            10.0,
            (float) ($historicalTotals['awal'][$barang->getKey()] ?? 0)
                + (float) ($historicalTotals['masuk'][$barang->getKey()] ?? 0)
                - (float) ($historicalTotals['keluar'][$barang->getKey()] ?? 0),
        );

        $this->post(route('unit-bisnis.admin-gu.stok-opname.apply'), $payload)
            ->assertSessionHasErrors('periode');

        self::assertSame('diterapkan', $opname->fresh()->status);
        self::assertSame(8.0, app(StokService::class)->saldo($barang, (int) $gudang->getKey())['saldo']);
    }

    public function test_saving_one_stocktake_row_preserves_other_saved_rows(): void
    {
        $this->travelTo('2026-11-16 12:00:00');
        [$admin, $gudang] = $this->makeAdminContext('GU-OPNAME-ROW-SAVE');
        $first = $this->makeBarang($gudang, 'OPNAME-ROW-001');
        $second = $this->makeBarang($gudang, 'OPNAME-ROW-002');

        $this->actingAs($admin)
            ->post(route('unit-bisnis.admin-gu.stok-opname.draft'), [
                'periode' => '2026-10',
                'gudang_id' => $gudang->getKey(),
                'items' => [
                    ['barang_id' => $first->getKey(), 'qty_fisik' => 2],
                ],
                'snapshot_barang_id' => $first->getKey(),
            ])
            ->assertRedirect();

        $this->post(route('unit-bisnis.admin-gu.stok-opname.draft'), [
            'periode' => '2026-10',
            'gudang_id' => $gudang->getKey(),
            'items' => [
                ['barang_id' => $second->getKey(), 'qty_fisik' => 3],
            ],
            'snapshot_barang_id' => $second->getKey(),
        ])->assertRedirect();

        $this->post(route('unit-bisnis.admin-gu.stok-opname.draft'), [
            'periode' => '2026-10',
            'gudang_id' => $gudang->getKey(),
            'items' => [
                ['barang_id' => $second->getKey(), 'qty_fisik' => 4],
            ],
            'snapshot_barang_id' => $second->getKey(),
        ])->assertRedirect();

        $this->assertDatabaseHas('stok_opname_details', [
            'barang_id' => $first->getKey(),
            'qty_fisik' => 2,
        ]);
        $this->assertDatabaseHas('stok_opname_details', [
            'barang_id' => $second->getKey(),
            'qty_fisik' => 4,
        ]);
    }

    public function test_apply_requires_every_item_to_be_counted_before_creating_an_opname(): void
    {
        $this->travelTo('2026-11-16 12:00:00');
        [$admin, $gudang] = $this->makeAdminContext('GU-OPNAME-INCOMPLETE');
        $first = $this->makeBarang($gudang, 'OPNAME-INCOMPLETE-001');
        $second = $this->makeBarang($gudang, 'OPNAME-INCOMPLETE-002');

        $this->actingAs($admin)
            ->post(route('unit-bisnis.admin-gu.stok-opname.apply'), [
                'periode' => '2026-10',
                'gudang_id' => $gudang->getKey(),
                'items' => [
                    ['barang_id' => $first->getKey(), 'qty_fisik' => 0, 'catatan' => 'Habis'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('items');

        $this->assertDatabaseMissing('stok_opnames', [
            'gudang_id' => $gudang->getKey(),
            'periode' => '2026-10-01',
        ]);
        self::assertSame(0.0, app(StokService::class)->saldo($first, (int) $gudang->getKey())['saldo']);
        self::assertSame(0.0, app(StokService::class)->saldo($second, (int) $gudang->getKey())['saldo']);
    }

    public function test_apply_requires_a_reason_for_each_stock_difference(): void
    {
        $this->travelTo('2026-11-16 12:00:00');
        [$admin, $gudang] = $this->makeAdminContext('GU-OPNAME-NOTE');
        $barang = $this->makeBarang($gudang, 'OPNAME-NOTE-001');
        StokAwal::query()->create([
            'gudang_id' => $gudang->getKey(),
            'barang_id' => $barang->getKey(),
            'qty' => 10,
            'tanggal_acuan' => '2026-10-01',
            'sumber' => 'manual',
        ]);

        $payload = [
            'periode' => '2026-10',
            'gudang_id' => $gudang->getKey(),
            'items' => [
                ['barang_id' => $barang->getKey(), 'qty_fisik' => 9, 'catatan' => ''],
            ],
        ];

        $this->actingAs($admin)
            ->post(route('unit-bisnis.admin-gu.stok-opname.draft'), [
                ...$payload,
                'snapshot_barang_id' => $barang->getKey(),
            ])
            ->assertRedirect();

        $this->post(route('unit-bisnis.admin-gu.stok-opname.apply'), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors('items');

        $this->assertDatabaseMissing('stok_opnames', [
            'gudang_id' => $gudang->getKey(),
            'status' => 'diterapkan',
        ]);
        self::assertSame(10.0, app(StokService::class)->saldo($barang, (int) $gudang->getKey())['saldo']);
    }

    public function test_count_snapshot_uses_current_stock_after_a_movement_before_count(): void
    {
        $this->travelTo('2026-11-18 10:00:00');
        [$admin, $gudang] = $this->makeAdminContext('GU-OPNAME-MOVEMENT-BEFORE');
        $barang = $this->makeBarang($gudang, 'OPNAME-MOVE-BEFORE');
        StokAwal::query()->create([
            'gudang_id' => $gudang->getKey(),
            'barang_id' => $barang->getKey(),
            'qty' => 10,
            'tanggal_acuan' => '2026-10-01',
            'sumber' => 'manual',
        ]);

        $this->createIssue($gudang, $barang, 2, '2026-11-18');
        $payload = [
            'periode' => '2026-11',
            'gudang_id' => $gudang->getKey(),
            'items' => [
                ['barang_id' => $barang->getKey(), 'qty_fisik' => 8, 'catatan' => ''],
            ],
        ];

        $this->actingAs($admin)
            ->post(route('unit-bisnis.admin-gu.stok-opname.draft'), [
                ...$payload,
                'snapshot_barang_id' => $barang->getKey(),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Draf opname berhasil disimpan. Saldo stok belum berubah.');

        $this->assertDatabaseHas('stok_opname_details', [
            'barang_id' => $barang->getKey(),
            'qty_sistem' => 8,
            'qty_fisik' => 8,
            'qty_sistem_at' => '2026-11-18 10:00:00',
        ]);

        $this->post(route('unit-bisnis.admin-gu.stok-opname.apply'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success', 'Penyesuaian stok berhasil diterapkan dan tercatat pada kartu stok.');

        self::assertSame(8.0, app(StokService::class)->saldo($barang, (int) $gudang->getKey())['saldo']);
    }

    public function test_stock_movement_after_count_remains_separate_from_the_snapshot_adjustment(): void
    {
        $this->travelTo('2026-11-18 09:00:00');
        [$admin, $gudang] = $this->makeAdminContext('GU-OPNAME-MOVEMENT-AFTER');
        $barang = $this->makeBarang($gudang, 'OPNAME-MOVE-AFTER');
        StokAwal::query()->create([
            'gudang_id' => $gudang->getKey(),
            'barang_id' => $barang->getKey(),
            'qty' => 10,
            'tanggal_acuan' => '2026-10-01',
            'sumber' => 'manual',
        ]);
        $payload = [
            'periode' => '2026-11',
            'gudang_id' => $gudang->getKey(),
            'items' => [
                ['barang_id' => $barang->getKey(), 'qty_fisik' => 10, 'catatan' => ''],
            ],
        ];

        $this->actingAs($admin)
            ->post(route('unit-bisnis.admin-gu.stok-opname.draft'), [
                ...$payload,
                'snapshot_barang_id' => $barang->getKey(),
            ])
            ->assertRedirect();

        $this->travelTo('2026-11-18 10:00:00');
        $this->createIssue($gudang, $barang, 2, '2026-11-18');

        $this->post(route('unit-bisnis.admin-gu.stok-opname.apply'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success', 'Penyesuaian stok berhasil diterapkan dan tercatat pada kartu stok.');

        $this->assertDatabaseHas('stok_opname_details', [
            'barang_id' => $barang->getKey(),
            'qty_sistem' => 10,
            'qty_fisik' => 10,
            'qty_sistem_at' => '2026-11-18 09:00:00',
        ]);
        self::assertSame(8.0, app(StokService::class)->saldo($barang, (int) $gudang->getKey())['saldo']);
    }

    /**
     * @return array{0: User, 1: Gudang}
     */
    private function makeAdminContext(string $suffix): array
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin Gudang Umum', 'web'));
        $gudang = $this->makeGudang($suffix);
        $admin->gudang()->attach($gudang);

        return [$admin, $gudang];
    }

    private function makeGudang(string $suffix): Gudang
    {
        return Gudang::query()->create([
            'kode' => $suffix,
            'nama' => 'Gudang '.$suffix,
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
    }

    private function makeBarang(Gudang $gudang, string $kode, string $nama = 'Barang Opname', float $harga = 0): Barang
    {
        return Barang::query()->create([
            'gudang_id' => $gudang->getKey(),
            'kode' => $kode,
            'nama' => $nama,
            'minimum_stok' => 0,
            'harga_satuan' => $harga,
            'is_aktif' => true,
        ]);
    }

    private function createIssue(Gudang $gudang, Barang $barang, float $quantity, string $date): void
    {
        $issue = PengeluaranUnit::query()->create([
            'nomor_bpb' => 'BPB-'.$barang->getKey(),
            'gudang_id' => $gudang->getKey(),
            'tanggal' => $date,
            'waktu_keluar' => '10:30',
            'bagian_peminta' => 'Produksi',
            'status_verifikasi' => 'terverifikasi',
        ]);

        PengeluaranUnitDetail::query()->create([
            'pengeluaran_unit_id' => $issue->getKey(),
            'barang_id' => $barang->getKey(),
            'kode_barang' => $barang->kode,
            'nama_barang' => $barang->nama,
            'satuan' => 'pcs',
            'qty' => $quantity,
            'qty_kembali' => 0,
            'harga_hpp' => $barang->harga_satuan,
            'subtotal_nilai' => $quantity * (float) $barang->harga_satuan,
        ]);
    }
}
