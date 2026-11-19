<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Gudang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class StoreMutationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_read_only_store_user_can_view_supplier_receipts_but_cannot_submit_one(): void
    {
        $user = $this->readOnlyStoreUser('store_penerimaan.read');

        $this->actingAs($user)
            ->get(route('store.penerimaan.index'))
            ->assertOk();

        $this->post(route('store.penerimaan.store'))
            ->assertForbidden();

        $this->assertDatabaseCount('penerimaan_barang_stores', 0);
    }

    public function test_read_only_store_user_can_view_outgoing_requests_but_cannot_create_transfer(): void
    {
        $user = $this->readOnlyStoreUser('store_pengeluaran.read');

        $this->actingAs($user)
            ->get(route('store.pengeluaran.index'))
            ->assertOk();

        $this->post(route('store.pengeluaran.pemindahan.store'))
            ->assertForbidden();

        $this->assertDatabaseCount('transfer_barangs', 0);
    }

    private function readOnlyStoreUser(string $permissionName): User
    {
        $permission = Permission::create([
            'name' => $permissionName,
            'guard_name' => 'web',
        ]);
        $role = Role::create([
            'name' => 'Store Read Only '.$permissionName,
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($permission);

        $user = User::create([
            'name' => 'Store Read Only',
            'username' => 'store-read-only-'.str_replace('.', '-', $permissionName),
            'password' => 'password',
            'is_aktif' => true,
        ]);
        $user->assignRole($role);

        $store = Gudang::create([
            'kode' => 'STORE-'.str_replace('.', '-', $permissionName),
            'nama' => 'Gudang Store',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $user->gudang()->attach($store);

        return $user;
    }
}
