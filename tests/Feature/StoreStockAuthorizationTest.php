<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\Satuan;
use App\Models\StokAwal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class StoreStockAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_user_can_open_stock_page_only_when_assigned_to_store(): void
    {
        $permission = Permission::create(['name' => 'store.view', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Admin Gudang Store', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $store = Gudang::create(['kode' => 'STORE', 'nama' => 'Gudang Store', 'jenis' => 'gudang_store', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS', 'nama' => 'Pieces', 'is_aktif' => true]);
        $barang = Barang::create([
            'gudang_id' => $store->id,
            'satuan_id' => $unit->id,
            'kode' => 'STOCK-001',
            'nama' => 'Barang Store',
            'minimum_stok' => 5,
            'is_aktif' => true,
        ]);
        StokAwal::create([
            'gudang_id' => $store->id,
            'barang_id' => $barang->id,
            'qty' => 20,
            'tanggal_acuan' => '2026-10-01',
            'sumber' => 'manual',
        ]);
        $user = User::create(['name' => 'Store User', 'username' => 'store-user', 'password' => 'password', 'is_aktif' => true]);
        $user->assignRole($role);
        $user->gudang()->attach($store);

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $store->id])
            ->get('/store/stok?search=STO')
            ->assertOk()
            ->assertSee('Lihat Kartu Stok')
            ->assertSee('role="dialog"', false)
            ->assertSee('[tabindex]:not([tabindex=&quot;-1&quot;])', false)
            ->assertSee('<mark class="store-stock-match">STO</mark>CK-001', false)
            ->assertSee('<mark class="store-stock-match">Sto</mark>re', false)
            ->assertSee(route('store.stok.kartu', $barang), false);
    }

    public function test_user_without_store_permission_is_forbidden(): void
    {
        $user = User::create(['name' => 'Unit User', 'username' => 'unit-user', 'password' => 'password', 'is_aktif' => true]);

        $this->actingAs($user)->get('/store/stok')->assertForbidden();
    }

    public function test_unit_user_can_view_fallback_store_without_changing_active_unit(): void
    {
        $permission = Permission::create(['name' => 'store.view', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Admin Gudang Umum', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $store = Gudang::create(['kode' => 'STORE', 'nama' => 'Gudang Store', 'jenis' => 'gudang_store', 'is_aktif' => true]);
        $unit = Gudang::create(['kode' => 'UNIT', 'nama' => 'Gudang Unit', 'jenis' => 'unit_bisnis', 'is_aktif' => true]);
        $user = User::create(['name' => 'Unit Admin', 'username' => 'unit-admin', 'password' => 'password', 'is_aktif' => true]);
        $user->assignRole($role);
        $user->gudang()->attach($unit);

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $unit->id])
            ->get('/store/stok')
            ->assertOk()
            ->assertViewHas('gudangStore', fn (Gudang $activeStore): bool => $activeStore->is($store))
            ->assertSessionHas('active_gudang_id', $unit->id);
    }

    public function test_stock_search_returns_only_items_matching_code_or_name(): void
    {
        $permission = Permission::create(['name' => 'store.view', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Admin Gudang Store', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $store = Gudang::create(['kode' => 'STORE', 'nama' => 'Gudang Store', 'jenis' => 'gudang_store', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS', 'nama' => 'Pieces', 'is_aktif' => true]);
        Barang::create([
            'gudang_id' => $store->id,
            'satuan_id' => $unit->id,
            'kode' => 'STR-ABC-001',
            'nama' => 'Kardus Operasional',
            'is_aktif' => true,
        ]);
        Barang::create([
            'gudang_id' => $store->id,
            'satuan_id' => $unit->id,
            'kode' => 'LAIN-002',
            'nama' => 'Barang Tidak Terkait',
            'is_aktif' => true,
        ]);
        $user = User::create(['name' => 'Store User', 'username' => 'store-user', 'password' => 'password', 'is_aktif' => true]);
        $user->assignRole($role);
        $user->gudang()->attach($store);

        $codeSearchResponse = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $store->id])
            ->get('/store/stok?search=ABC');

        $codeSearchResponse->assertOk()
            ->assertViewHas('barangs', fn ($barangs): bool => $barangs->total() === 1)
            ->assertSee('<mark class="store-stock-match">ABC</mark>', false)
            ->assertDontSee('LAIN-002')
            ->assertDontSee('Barang Tidak Terkait');

        $nameSearchResponse = $this->get('/store/stok?search=Kar');

        $nameSearchResponse
            ->assertOk()
            ->assertViewHas('barangs', fn ($barangs): bool => $barangs->total() === 1)
            ->assertSee('<mark class="store-stock-match">Kar</mark>dus Operasional', false)
            ->assertDontSee('Barang Tidak Terkait');
    }

    public function test_stock_card_is_returned_as_modal_data_to_authorized_store_users(): void
    {
        $permission = Permission::create(['name' => 'store.view', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Admin Gudang Store', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $store = Gudang::create(['kode' => 'STORE', 'nama' => 'Gudang Store', 'jenis' => 'gudang_store', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS', 'nama' => 'Pieces', 'is_aktif' => true]);
        $barang = Barang::create([
            'gudang_id' => $store->id,
            'satuan_id' => $unit->id,
            'kode' => 'STOCK-001',
            'nama' => 'Barang Kartu Stok',
            'minimum_stok' => 5,
            'is_aktif' => true,
        ]);
        StokAwal::create([
            'gudang_id' => $store->id,
            'barang_id' => $barang->id,
            'qty' => 20,
            'tanggal_acuan' => '2026-10-01',
            'sumber' => 'manual',
        ]);
        $user = User::create(['name' => 'Store User', 'username' => 'store-user', 'password' => 'password', 'is_aktif' => true]);
        $user->assignRole($role);
        $user->gudang()->attach($store);

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $store->id])
            ->get(route('store.stok.kartu', $barang))
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath('barang.nama', 'Barang Kartu Stok')
            ->assertJsonPath('barang.kode', 'STOCK-001')
            ->assertJsonPath('barang.satuan', 'Pieces')
            ->assertJsonPath('barang.minimum_stok', 5)
            ->assertJsonPath('mutasi.0.jenis', 'STOK AWAL')
            ->assertJsonPath('mutasi.0.masuk', 20)
            ->assertJsonPath('mutasi.0.saldo', 20);
    }

    public function test_stock_card_modal_data_cannot_cross_store_boundaries(): void
    {
        $permission = Permission::create(['name' => 'store.view', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Admin Gudang Store', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $assignedStore = Gudang::create(['kode' => 'STORE-A', 'nama' => 'Gudang Store A', 'jenis' => 'gudang_store', 'is_aktif' => true]);
        $otherStore = Gudang::create(['kode' => 'STORE-B', 'nama' => 'Gudang Store B', 'jenis' => 'gudang_store', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS', 'nama' => 'Pieces', 'is_aktif' => true]);
        $otherBarang = Barang::create([
            'gudang_id' => $otherStore->id,
            'satuan_id' => $unit->id,
            'kode' => 'STOCK-OTHER',
            'nama' => 'Barang Store Lain',
            'is_aktif' => true,
        ]);
        $user = User::create(['name' => 'Store User', 'username' => 'store-user', 'password' => 'password', 'is_aktif' => true]);
        $user->assignRole($role);
        $user->gudang()->attach($assignedStore);

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $assignedStore->id])
            ->getJson(route('store.stok.kartu', $otherBarang))
            ->assertNotFound();
    }
}
