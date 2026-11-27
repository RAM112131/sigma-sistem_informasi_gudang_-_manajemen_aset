<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MasterBarang;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class SatuanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_filters_units_and_shows_the_global_master_item_count(): void
    {
        $user = $this->userWithPermission('satuan.read');
        $kilogram = Satuan::create(['kode' => 'kg', 'nama' => 'Kilogram', 'is_aktif' => true]);
        Satuan::create(['kode' => 'pcs', 'nama' => 'Pieces', 'is_aktif' => false]);
        MasterBarang::create([
            'kode' => 'MASTER-KG-01',
            'nama' => 'Beras',
            'satuan_id' => $kilogram->id,
            'is_aktif' => true,
        ]);

        $response = $this->actingAs($user)->get(route('master.barang.index', [
            'tab' => 'satuan',
            'search' => 'kg',
            'status' => '1',
        ]));

        $response->assertOk()
            ->assertSeeText('Satuan bersifat global, dipakai semua gudang, dan dapat ditambah kapan pun.')
            ->assertSee('Kilogram')
            ->assertSeeText('1 barang')
            ->assertDontSee('Pieces')
            ->assertDontSee('Hapus')
            ->assertViewHas('satuans', fn (LengthAwarePaginator $satuans): bool => $satuans->total() === 1
                && $satuans->perPage() === 20);
    }

    public function test_store_normalizes_the_code_and_persists_the_selected_status(): void
    {
        $user = $this->userWithPermission('satuan.create');

        $response = $this->actingAs($user)->post(route('master.satuan.store', ['tab' => 'satuan']), [
            '_modal' => 'satuan-create',
            'kode' => ' KG ',
            'nama' => 'Kilogram',
            'is_aktif' => '0',
        ]);

        $response->assertRedirect(route('master.barang.index', ['tab' => 'satuan']));
        $this->assertDatabaseHas('satuans', [
            'kode' => 'kg',
            'nama' => 'Kilogram',
            'is_aktif' => false,
        ]);
    }

    public function test_entering_a_full_unit_name_generates_its_short_code(): void
    {
        $user = $this->userWithPermission('satuan.create');

        $response = $this->actingAs($user)->post(route('master.satuan.store', ['tab' => 'satuan']), [
            '_modal' => 'satuan-create',
            'satuan_input' => 'Liter',
            'kode' => '',
            'nama' => '',
        ]);

        $response->assertRedirect(route('master.barang.index', ['tab' => 'satuan']));
        $this->assertDatabaseHas('satuans', [
            'kode' => 'ltr',
            'nama' => 'Liter',
        ]);
    }

    public function test_entering_a_known_short_unit_code_generates_its_full_name(): void
    {
        $user = $this->userWithPermission('satuan.create');

        $response = $this->actingAs($user)->post(route('master.satuan.store', ['tab' => 'satuan']), [
            '_modal' => 'satuan-create',
            'satuan_input' => 'ltr',
            'kode' => '',
            'nama' => '',
        ]);

        $response->assertRedirect(route('master.barang.index', ['tab' => 'satuan']));
        $this->assertDatabaseHas('satuans', [
            'kode' => 'ltr',
            'nama' => 'Liter',
        ]);
    }

    public function test_duplicate_code_is_rejected_with_the_inline_validation_message(): void
    {
        $user = $this->userWithPermission('satuan.create');
        Satuan::create(['kode' => 'kg', 'nama' => 'Kilogram', 'is_aktif' => true]);

        $response = $this->actingAs($user)->post(route('master.satuan.store', ['tab' => 'satuan']), [
            '_modal' => 'satuan-create',
            'kode' => 'KG',
            'nama' => 'Kilogram',
        ]);

        $response->assertRedirect()
            ->assertSessionHasErrors(['kode' => 'Satuan sudah terdaftar.']);
        $this->assertDatabaseCount('satuans', 1);
    }

    public function test_used_unit_code_cannot_be_changed_during_edit(): void
    {
        $user = $this->userWithPermission('satuan.manage');
        $satuan = Satuan::create(['kode' => 'kg', 'nama' => 'Kilogram', 'is_aktif' => true]);
        MasterBarang::create([
            'kode' => 'MASTER-KG-02',
            'nama' => 'Tepung',
            'satuan_id' => $satuan->id,
            'is_aktif' => true,
        ]);

        $response = $this->actingAs($user)->put(route('master.satuan.update', [
            'satuan' => $satuan,
            'tab' => 'satuan',
        ]), [
            '_modal' => 'satuan-edit-'.$satuan->id,
            'kode' => 'kilo',
            'nama' => 'Kilogram Baru',
            'is_aktif' => '1',
        ]);

        $response->assertRedirect()
            ->assertSessionHasErrors(['kode' => 'Satuan tidak dapat diubah karena sudah dipakai barang.']);
        $this->assertDatabaseHas('satuans', [
            'id' => $satuan->id,
            'kode' => 'kg',
            'nama' => 'Kilogram',
            'is_aktif' => true,
        ]);
    }

    public function test_deactivation_keeps_the_unit_record_and_existing_master_item(): void
    {
        $user = $this->userWithPermission('satuan.manage');
        $satuan = Satuan::create(['kode' => 'pcs', 'nama' => 'Pieces', 'is_aktif' => true]);
        $masterBarang = MasterBarang::create([
            'kode' => 'MASTER-PCS-01',
            'nama' => 'Gelas',
            'satuan_id' => $satuan->id,
            'is_aktif' => true,
        ]);

        $response = $this->actingAs($user)->patch(route('master.satuan.toggle', [
            'satuan' => $satuan,
            'tab' => 'satuan',
        ]));

        $response->assertRedirect(route('master.barang.index', ['tab' => 'satuan']));
        $this->assertDatabaseHas('satuans', [
            'id' => $satuan->id,
            'kode' => 'pcs',
            'is_aktif' => false,
            'deleted_at' => null,
        ]);
        $this->assertModelExists($masterBarang);
    }

    public function test_user_without_management_permission_cannot_toggle_a_unit(): void
    {
        $user = User::factory()->create();
        $satuan = Satuan::create(['kode' => 'ltr', 'nama' => 'Liter', 'is_aktif' => true]);

        $this->actingAs($user)
            ->patch(route('master.satuan.toggle', $satuan))
            ->assertForbidden();

        $this->assertDatabaseHas('satuans', [
            'id' => $satuan->id,
            'is_aktif' => true,
        ]);
    }

    private function userWithPermission(string $permissionName): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permission = Permission::findOrCreate($permissionName, 'web');

        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        return $user;
    }
}
