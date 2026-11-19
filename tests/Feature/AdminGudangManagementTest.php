<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Gudang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AdminGudangManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_paginates_warehouse_records(): void
    {
        $admin = $this->createSuperAdmin();

        for ($index = 1; $index <= 13; $index++) {
            $this->createWarehouse("WH-{$index}", "Gudang {$index}");
        }

        $response = $this->actingAs($admin)->get(route('admin.gudang.index'));

        $response->assertViewIs('super-admin.gudang.index')
            ->assertViewHas('gudangs', fn (LengthAwarePaginator $gudangs): bool => $gudangs->total() === 13
                && $gudangs->perPage() === 12
                && $gudangs->currentPage() === 1);
        $response->assertSee('Menampilkan 1–12')
            ->assertSee('dari 13 gudang');
        $response->assertSee(route('admin.gudang.create'), false);
    }

    public function test_index_filters_warehouses_by_name_search(): void
    {
        $admin = $this->createSuperAdmin();
        $matchingWarehouse = $this->createWarehouse('WH-BOGOR', 'Gudang Cibinong');
        $this->createWarehouse('WH-BANDUNG', 'Gudang Bandung');

        $response = $this->actingAs($admin)->get(route('admin.gudang.index', [
            'search' => 'cibinong',
            'status' => 'active',
        ]));

        $response->assertViewHas('gudangs', fn (LengthAwarePaginator $gudangs): bool => $gudangs->total() === 1
            && $gudangs->getCollection()->first()->is($matchingWarehouse));
        $response->assertSee('Gudang Cibinong');
        $response->assertDontSee('Gudang Bandung');
    }

    public function test_create_page_renders_only_core_warehouse_fields(): void
    {
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)->get(route('admin.gudang.create'));

        $response->assertViewIs('super-admin.gudang.form');
        $response->assertSee('Registrasi Fasilitas Gudang');
        $response->assertDontSee('name="alamat"', false);
        $response->assertDontSee('name="kapasitas_maksimum"', false);
        $response->assertDontSee('name="penanggung_jawab"', false);
        $response->assertDontSee('name="telepon_operasional"', false);
        $response->assertSee('name="jenis" value="gudang_store"', false);
        $response->assertSee('class="app-sidebar"', false);
        $response->assertDontSee('x-show="addGudangModal"', false);
    }

    public function test_edit_page_displays_core_warehouse_data(): void
    {
        $admin = $this->createSuperAdmin();
        $warehouse = $this->createWarehouse('WH-EDIT', 'Gudang Sukahati', [
            'jenis' => 'gudang_store',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.gudang.edit', $warehouse));

        $response->assertViewIs('super-admin.gudang.form');
        $response->assertSee('Edit Lokasi Gudang');
        $response->assertSee('value="WH-EDIT"', false);
        $response->assertSee('GUDANG STORE');
    }

    public function test_store_persists_core_warehouse_fields(): void
    {
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)->post(route('admin.gudang.store'), [
            'kode' => ' wh-bks-03 ',
            'nama' => 'Hub Logistik Cibinong',
            'jenis' => 'gudang_umum',
            'is_aktif' => '1',
        ]);

        $response->assertRedirect(route('admin.gudang.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('gudangs', [
            'kode' => 'WH-BKS-03',
            'nama' => 'Hub Logistik Cibinong',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
    }

    public function test_store_rejects_a_duplicate_code_after_normalization(): void
    {
        $admin = $this->createSuperAdmin();
        $this->createWarehouse('WH-DUP', 'Gudang Lama');

        $response = $this->actingAs($admin)
            ->from(route('admin.gudang.create'))
            ->post(route('admin.gudang.store'), [
                'kode' => 'wh-dup',
                'nama' => 'Gudang Baru',
                'jenis' => 'gudang_umum',
                'is_aktif' => '1',
            ]);

        $response->assertRedirect(route('admin.gudang.create'))
            ->assertSessionHasErrors('kode');
        $this->assertDatabaseCount('gudangs', 1);
    }

    public function test_update_changes_core_fields_and_inactive_status(): void
    {
        $admin = $this->createSuperAdmin();
        $warehouse = $this->createWarehouse('WH-UPDATE', 'Gudang Lama');

        $response = $this->actingAs($admin)->put(route('admin.gudang.update', $warehouse), [
            'kode' => 'wh-update',
            'nama' => 'Gudang Baru',
            'jenis' => 'gudang_store',
            'is_aktif' => '0',
        ]);

        $response->assertRedirect(route('admin.gudang.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('gudangs', [
            'id' => $warehouse->id,
            'kode' => 'WH-UPDATE',
            'nama' => 'Gudang Baru',
            'jenis' => 'gudang_store',
            'is_aktif' => false,
        ]);
    }

    public function test_gudang_pages_forbid_users_without_super_admin_role(): void
    {
        $operator = User::withoutEvents(fn (): User => User::factory()->create([
            'username' => 'operator',
            'is_aktif' => true,
        ]));
        $warehouse = $this->createWarehouse('WH-PRIVATE', 'Gudang Privat');

        $this->actingAs($operator)
            ->get(route('admin.gudang.create'))
            ->assertForbidden();

        $this->actingAs($operator)
            ->get(route('admin.gudang.edit', $warehouse))
            ->assertForbidden();
    }

    private function createSuperAdmin(): User
    {
        $role = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $user = User::withoutEvents(fn (): User => User::factory()->create([
            'username' => 'super-admin',
            'is_aktif' => true,
        ]));
        $user->assignRole($role);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createWarehouse(string $kode, string $nama, array $attributes = []): Gudang
    {
        return Gudang::withoutEvents(fn (): Gudang => Gudang::query()->create(array_merge([
            'kode' => $kode,
            'nama' => $nama,
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ], $attributes)));
    }
}
