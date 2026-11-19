<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Gudang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class LaporanStoreUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_store_user_sees_report_choices_and_empty_preview_state(): void
    {
        $permission = Permission::create(['name' => 'store_laporan.export', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Admin Gudang Store', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $store = Gudang::create([
            'kode' => 'STORE',
            'nama' => 'Gudang Store',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $user = User::create([
            'name' => 'Store User',
            'username' => 'store-user',
            'password' => 'password',
            'is_aktif' => true,
        ]);
        $user->assignRole($role);
        $user->gudang()->attach($store);

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $store->id])
            ->get(route('store.laporan.index'))
            ->assertOk()
            ->assertSee('Laporan Stok')
            ->assertSee('Barang Masuk')
            ->assertSee('Barang Keluar')
            ->assertSee('Pembelian Tunai / Transfer')
            ->assertSee('Belum ada laporan dipilih')
            ->assertSee('Pratinjau laporan')
            ->assertSee('Hanya stok yang perlu restock')
            ->assertSee(':disabled="laporanAktif !== \'stok\'"', false);
    }
}
