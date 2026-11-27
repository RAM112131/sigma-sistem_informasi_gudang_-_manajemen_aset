<?php

declare(strict_types=1);

namespace Tests\Feature\UnitBisnis;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\StokAwal;
use App\Models\TransferBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class RecordingGudangControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_form_uses_only_the_users_assigned_unit_warehouse(): void
    {
        $user = User::withoutEvents(static fn (): User => User::create([
            'name' => 'Petugas Recording',
            'username' => 'petugas-recording',
            'password' => 'password',
            'is_aktif' => true,
        ]));
        $user->assignRole(Role::create(['name' => 'Recording Gudang Umum', 'guard_name' => 'web']));
        $assignedGudang = Gudang::create([
            'kode' => 'UNIT-ASSIGNED',
            'nama' => 'Gudang Ditugaskan',
            'jenis' => 'unit_bisnis',
            'is_aktif' => true,
        ]);
        $unassignedGudang = Gudang::create([
            'kode' => 'UNIT-OTHER',
            'nama' => 'Gudang Tidak Ditugaskan',
            'jenis' => 'unit_bisnis',
            'is_aktif' => true,
        ]);
        $user->gudang()->attach($assignedGudang);

        $assignedBarang = Barang::create([
            'gudang_id' => $assignedGudang->id,
            'kode' => 'ITEM-ASSIGNED',
            'nama' => 'Barang Unit Saya',
            'is_aktif' => true,
        ]);
        $unassignedBarang = Barang::create([
            'gudang_id' => $unassignedGudang->id,
            'kode' => 'ITEM-OTHER',
            'nama' => 'Barang Unit Lain',
            'is_aktif' => true,
        ]);

        $this->actingAs($user)
            ->get(route('unit-bisnis.recording-gu.penerimaan.index', [
                'gudang_id' => $unassignedGudang->id,
            ]))
            ->assertOk()
            ->assertViewIs('unit-bisnis.recording-gudang-umum.penerimaan.index')
            ->assertViewHas('activeGudang', static fn (?Gudang $activeGudang): bool => $activeGudang?->is($assignedGudang) ?? false)
            ->assertViewHas('barangs', static fn ($barangs): bool => $barangs->contains('id', $assignedBarang->id)
                && ! $barangs->contains('id', $unassignedBarang->id))
            ->assertDontSee('Perkiraan masuk stok:')
            ->assertSeeText('Nilai persediaan belum ditetapkan pada pencatatan fisik')
            ->assertSessionHas('active_gudang_id', $assignedGudang->id);
    }

    public function test_recording_dashboard_links_match_its_operational_menus(): void
    {
        $user = User::withoutEvents(static fn (): User => User::create([
            'name' => 'Petugas Recording',
            'username' => 'petugas-recording-menu',
            'password' => 'password',
            'is_aktif' => true,
        ]));
        $user->assignRole(Role::create(['name' => 'Recording Gudang Umum', 'guard_name' => 'web']));
        $gudang = Gudang::create([
            'kode' => 'UNIT-RECORDING-DASHBOARD',
            'nama' => 'Gudang Recording Dashboard',
            'jenis' => 'unit_bisnis',
            'is_aktif' => true,
        ]);
        $user->gudang()->attach($gudang);

        $this->actingAs($user)
            ->get(route('unit-bisnis.recording-gu.dashboard'))
            ->assertOk()
            ->assertViewIs('unit-bisnis.recording-gudang-umum.dashboard')
            ->assertSee(route('unit-bisnis.stok.index'), false)
            ->assertSee(route('unit-bisnis.recording-gu.penerimaan.index'), false)
            ->assertSee(route('unit-bisnis.recording-gu.pengeluaran.index'), false)
            ->assertSee('Stok Barang')
            ->assertSee('Penerimaan Fisik Barang')
            ->assertSee('Pengeluaran & Pengembalian (BPB)')
            ->assertDontSee('Penerimaan Hari Ini')
            ->assertDontSee('Opname Hari Ini')
            ->assertDontSee('Label Dicetak');
    }

    public function test_admin_dashboard_uses_live_data_for_its_assigned_warehouse(): void
    {
        $user = User::withoutEvents(static fn (): User => User::create([
            'name' => 'Admin Gudang',
            'username' => 'admin-gudang',
            'password' => 'password',
            'is_aktif' => true,
        ]));
        $user->assignRole(Role::create(['name' => 'Admin Gudang Umum', 'guard_name' => 'web']));
        $gudang = Gudang::create([
            'kode' => 'GU-DASHBOARD',
            'nama' => 'Gudang Umum Dashboard',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $store = Gudang::create([
            'kode' => 'STORE-DASHBOARD',
            'nama' => 'Gudang Store',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $user->gudang()->attach($gudang);

        $lowStockBarang = Barang::create([
            'gudang_id' => $gudang->id,
            'kode' => 'GU-LOW',
            'nama' => 'Barang Stok Rendah',
            'minimum_stok' => 5,
            'is_aktif' => true,
        ]);
        $regularBarang = Barang::create([
            'gudang_id' => $gudang->id,
            'kode' => 'GU-REGULAR',
            'nama' => 'Barang Normal',
            'minimum_stok' => 5,
            'is_aktif' => true,
        ]);
        StokAwal::create([
            'gudang_id' => $gudang->id,
            'barang_id' => $lowStockBarang->id,
            'qty' => 2,
            'tanggal_acuan' => today(),
        ]);
        StokAwal::create([
            'gudang_id' => $gudang->id,
            'barang_id' => $regularBarang->id,
            'qty' => 10,
            'tanggal_acuan' => today(),
        ]);
        TransferBarang::create([
            'nomor_sj' => 'SJ-GU-PENDING',
            'gudang_asal_id' => $store->id,
            'gudang_tujuan_id' => $gudang->id,
            'barang_id' => $regularBarang->id,
            'qty_diminta' => 3,
            'qty_dikirim' => 3,
            'qty_diterima' => 0,
            'status' => 'dikirim',
            'dikirim_at' => now(),
        ]);
        TransferBarang::create([
            'nomor_sj' => 'SJ-GU-RECEIVED',
            'gudang_asal_id' => $store->id,
            'gudang_tujuan_id' => $gudang->id,
            'barang_id' => $regularBarang->id,
            'qty_diminta' => 4,
            'qty_dikirim' => 4,
            'qty_diterima' => 4,
            'status' => 'diterima',
            'dikirim_at' => now()->subHour(),
            'diterima_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('unit-bisnis.admin-gu.dashboard'))
            ->assertOk()
            ->assertViewIs('unit-bisnis.admin-gudang-umum.dashboard')
            ->assertViewHas('stats.pending_receipt_count', 1)
            ->assertViewHas('stats.low_stock_count', 1)
            ->assertViewHas('stats.active_sku_count', 2)
            ->assertViewHas('stats.received_today_count', 1)
            ->assertViewHas('lowStockItems', static fn (array $items): bool => $items[0]['kode'] === 'GU-LOW')
            ->assertSee('Kiriman menunggu penerimaan')
            ->assertSee('Barang Stok Rendah');
    }

    public function test_admin_dashboard_does_not_show_example_metrics_without_an_assigned_warehouse(): void
    {
        $user = User::withoutEvents(static fn (): User => User::create([
            'name' => 'Admin Tanpa Gudang',
            'username' => 'admin-tanpa-gudang',
            'password' => 'password',
            'is_aktif' => true,
        ]));
        $user->assignRole(Role::create(['name' => 'Admin Gudang Umum', 'guard_name' => 'web']));

        $this->actingAs($user)
            ->get(route('unit-bisnis.admin-gu.dashboard'))
            ->assertOk()
            ->assertViewHas('activeGudang', null)
            ->assertViewHas('stats.pending_receipt_count', 0)
            ->assertViewHas('stats.low_stock_count', 0)
            ->assertSee('Akun ini belum memiliki gudang aktif yang ditugaskan');
    }

    public function test_recording_role_cannot_open_admin_gudang_umum_dashboard(): void
    {
        $user = User::withoutEvents(static fn (): User => User::create([
            'name' => 'Petugas Recording',
            'username' => 'petugas-recording-dashboard',
            'password' => 'password',
            'is_aktif' => true,
        ]));
        $user->assignRole(Role::create(['name' => 'Recording Gudang Umum', 'guard_name' => 'web']));

        $this->actingAs($user)
            ->get(route('unit-bisnis.admin-gu.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_role_cannot_open_recording_gudang_umum_dashboard(): void
    {
        $user = User::withoutEvents(static fn (): User => User::create([
            'name' => 'Admin Gudang',
            'username' => 'admin-gudang-dashboard',
            'password' => 'password',
            'is_aktif' => true,
        ]));
        $user->assignRole(Role::create(['name' => 'Admin Gudang Umum', 'guard_name' => 'web']));

        $this->actingAs($user)
            ->get(route('unit-bisnis.recording-gu.dashboard'))
            ->assertForbidden();
    }
}
