<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DivisiPeminta;
use App\Models\Gudang;
use App\Models\PengeluaranUnit;
use App\Models\PengeluaranUnitDetail;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class MasterDataAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_area_tab_shows_monthly_usage_and_only_the_selected_warehouse(): void
    {
        $warehouse = $this->warehouse('AREA-WH-1', 'Gudang Area 1');
        $otherWarehouse = $this->warehouse('AREA-WH-2', 'Gudang Area 2');
        $user = $this->userWithAreaPermissions($warehouse);
        DivisiPeminta::query()->create([
            'gudang_id' => $warehouse->id,
            'nama' => 'Area Produksi',
            'is_aktif' => true,
        ]);
        DivisiPeminta::query()->create([
            'gudang_id' => $otherWarehouse->id,
            'nama' => 'Area Gudang Lain',
            'is_aktif' => true,
        ]);
        $issue = PengeluaranUnit::query()->create([
            'nomor_bpb' => 'AREA-BPB-001',
            'gudang_id' => $warehouse->id,
            'tanggal' => now()->toDateString(),
            'waktu_keluar' => '10:00',
            'bagian_peminta' => 'Area Produksi',
            'status_verifikasi' => 'menunggu_verifikasi',
        ]);
        PengeluaranUnitDetail::query()->create([
            'pengeluaran_unit_id' => $issue->id,
            'nama_barang' => 'Barang Area',
            'satuan' => 'pcs',
            'qty' => 2,
            'subtotal_nilai' => 175.5,
        ]);

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->get(route('master.barang.index', ['tab' => 'area']))
            ->assertOk()
            ->assertSee('User Peminta / Area')
            ->assertSee('Area Produksi')
            ->assertDontSee('Area Gudang Lain')
            ->assertViewHas('areas', static fn ($areas): bool => $areas->total() === 1
                && (int) $areas->first()->request_count === 1
                && (float) $areas->first()->expenditure_total === 175.5);
    }

    public function test_selected_area_period_filters_usage_and_unregistered_requesters(): void
    {
        $this->travelTo('2026-11-15');
        $warehouse = $this->warehouse('AREA-WH-10', 'Gudang Area 10');
        $user = $this->userWithAreaPermissions($warehouse);
        $area = DivisiPeminta::query()->create([
            'gudang_id' => $warehouse->id,
            'nama' => 'Area Produksi',
            'is_aktif' => true,
        ]);

        foreach ([
            ['AREA-BPB-010', '2026-10-10', 'Area Produksi', $area->id, 175.5],
            ['AREA-BPB-011', '2026-11-10', 'Area Produksi', $area->id, 250],
            ['AREA-BPB-012', '2026-10-12', 'Peminta Oktober', null, 0],
            ['AREA-BPB-013', '2026-11-12', 'Peminta November', null, 0],
        ] as [$number, $date, $requester, $areaId, $amount]) {
            $issue = PengeluaranUnit::query()->create([
                'nomor_bpb' => $number,
                'gudang_id' => $warehouse->id,
                'tanggal' => $date,
                'waktu_keluar' => '10:00',
                'bagian_peminta' => $requester,
                'divisi_peminta_id' => $areaId,
                'status_verifikasi' => 'menunggu_verifikasi',
            ]);

            if ($amount > 0) {
                PengeluaranUnitDetail::query()->create([
                    'pengeluaran_unit_id' => $issue->id,
                    'nama_barang' => 'Barang Area',
                    'satuan' => 'pcs',
                    'qty' => 1,
                    'subtotal_nilai' => $amount,
                ]);
            }
        }

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->get(route('master.barang.index', [
                'tab' => 'area',
                'area_periode' => '2026-10',
            ]))
            ->assertOk()
            ->assertSee('Periode')
            ->assertSee('value="2026-10" selected', false)
            ->assertSee('Oktober 2026')
            ->assertSee('Peminta Oktober')
            ->assertDontSee('Peminta November')
            ->assertViewHas('selectedAreaPeriod', '2026-10')
            ->assertViewHas('areas', static fn ($areas): bool => (int) $areas->first()->request_count === 1
                && (float) $areas->first()->expenditure_total === 175.5)
            ->assertViewHas('orphanedAreaCount', 1);
    }

    public function test_recording_and_admin_gudang_umum_roles_can_view_the_area_tab(): void
    {
        $warehouse = $this->warehouse('AREA-WH-8', 'Gudang Area 8');
        $this->seed(RolePermissionSeeder::class);

        foreach (['Admin Gudang Umum', 'Recording Gudang Umum'] as $roleName) {
            $user = User::factory()->create();
            $user->gudang()->attach($warehouse);
            $user->assignRole($roleName);

            $this->actingAs($user)
                ->withSession(['active_gudang_id' => $warehouse->id])
                ->get(route('master.barang.index'))
                ->assertOk()
                ->assertSee('id="master-tab-area"', false);
        }
    }

    public function test_area_page_uses_one_orphan_scan_and_skips_master_barang_summary_query(): void
    {
        $warehouse = $this->warehouse('AREA-WH-9', 'Gudang Area 9');
        $user = $this->userWithAreaPermissions($warehouse);
        PengeluaranUnit::query()->create([
            'nomor_bpb' => 'AREA-BPB-009',
            'gudang_id' => $warehouse->id,
            'tanggal' => now()->toDateString(),
            'waktu_keluar' => '10:00',
            'bagian_peminta' => 'Area Baru',
            'status_verifikasi' => 'menunggu_verifikasi',
        ]);
        $orphanQueries = [];
        $masterBarangSummaryQueries = [];

        DB::listen(function (QueryExecuted $query) use (&$orphanQueries, &$masterBarangSummaryQueries): void {
            $sql = strtolower($query->sql);

            if (str_contains($sql, 'not exists') && str_contains($sql, 'pengeluaran_units')) {
                $orphanQueries[] = $query->sql;
            }

            if (str_contains($sql, 'master_barangs') && str_contains($sql, 'count(case when not exists')) {
                $masterBarangSummaryQueries[] = $query->sql;
            }
        });

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->get(route('master.barang.index', ['tab' => 'area']))
            ->assertOk()
            ->assertViewHas('orphanedAreaCount', 1)
            ->assertViewHas('orphanedAreaNames', static fn ($names): bool => $names->count() === 1
                && $names->first()->nama === 'Area Baru');

        $this->assertCount(1, $orphanQueries);
        $this->assertSame([], $masterBarangSummaryQueries);
    }

    public function test_user_can_add_area_and_duplicate_names_are_rejected_case_insensitively(): void
    {
        $warehouse = $this->warehouse('AREA-WH-3', 'Gudang Area 3');
        $user = $this->userWithAreaPermissions($warehouse);

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->post(route('master.area.store'), [
                'nama' => 'Area Produksi',
            ])
            ->assertRedirect(route('master.barang.index', [
                'tab' => 'area',
                'area_gudang_id' => $warehouse->id,
            ]));

        $this->assertDatabaseHas('divisi_pemintas', [
            'gudang_id' => $warehouse->id,
            'nama' => 'Area Produksi',
            'is_aktif' => true,
        ]);

        $this->post(route('master.area.store'), [
            'area_gudang_id' => $warehouse->id,
            'nama' => ' area produksi ',
        ])->assertSessionHasErrors('nama');

        $this->assertDatabaseCount('divisi_pemintas', 1);
    }

    public function test_area_creation_cannot_target_an_unassigned_warehouse(): void
    {
        $assignedWarehouse = $this->warehouse('AREA-WH-4', 'Gudang Area 4');
        $otherWarehouse = $this->warehouse('AREA-WH-5', 'Gudang Area 5');
        $user = $this->userWithAreaPermissions($assignedWarehouse);

        $this->actingAs($user)
            ->post(route('master.area.store'), [
                'area_gudang_id' => $otherWarehouse->id,
                'nama' => 'Area Rahasia',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('divisi_pemintas', [
            'gudang_id' => $otherWarehouse->id,
            'nama' => 'Area Rahasia',
        ]);
    }

    public function test_user_can_rename_and_deactivate_an_area(): void
    {
        $warehouse = $this->warehouse('AREA-WH-7', 'Gudang Area 7');
        $user = $this->userWithAreaPermissions($warehouse);
        $area = DivisiPeminta::query()->create([
            'gudang_id' => $warehouse->id,
            'nama' => 'Area Lama',
            'is_aktif' => true,
        ]);

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->put(route('master.area.update', $area), [
                'nama' => 'Area Baru',
            ])
            ->assertRedirect(route('master.barang.index', [
                'tab' => 'area',
                'area_gudang_id' => $warehouse->id,
            ]));

        $this->assertDatabaseHas('divisi_pemintas', [
            'id' => $area->id,
            'nama' => 'Area Baru',
        ]);

        $this->patch(route('master.area.toggle', $area), [
            'area_gudang_id' => $warehouse->id,
        ])->assertRedirect(route('master.barang.index', [
            'tab' => 'area',
            'area_gudang_id' => $warehouse->id,
        ]));

        $this->assertDatabaseHas('divisi_pemintas', [
            'id' => $area->id,
            'is_aktif' => false,
        ]);
    }

    public function test_user_can_delete_an_area(): void
    {
        $warehouse = $this->warehouse('AREA-WH-7B', 'Gudang Area 7B');
        $user = $this->userWithAreaPermissions($warehouse);
        $area = DivisiPeminta::query()->create([
            'gudang_id' => $warehouse->id,
            'nama' => 'Area Dihapus',
            'is_aktif' => true,
        ]);

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->delete(route('master.area.destroy', $area), [
                'area_gudang_id' => $warehouse->id,
            ])
            ->assertRedirect(route('master.barang.index', [
                'tab' => 'area',
                'area_gudang_id' => $warehouse->id,
            ]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('divisi_pemintas', [
            'id' => $area->id,
        ]);
    }

    public function test_user_without_area_create_permission_cannot_add_an_area(): void
    {
        $warehouse = $this->warehouse('AREA-WH-6', 'Gudang Area 6');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('master.area.store'), [
                'area_gudang_id' => $warehouse->id,
                'nama' => 'Area Terlarang',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('divisi_pemintas', 0);
    }

    private function warehouse(string $code, string $name): Gudang
    {
        return Gudang::query()->create([
            'kode' => $code,
            'nama' => $name,
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
    }

    private function userWithAreaPermissions(Gudang $warehouse): User
    {
        $user = User::factory()->create();
        $user->gudang()->attach($warehouse);
        $user->givePermissionTo([
            Permission::findOrCreate('area.read', 'web'),
            Permission::findOrCreate('area.create', 'web'),
            Permission::findOrCreate('area.manage', 'web'),
        ]);

        return $user;
    }
}
