<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\MasterBarang;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class QuickAddControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_add_unit_normalizes_a_full_name_before_saving(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('satuan.create', 'web'));

        $response = $this->actingAs($user)->postJson('/api/quick-add/satuan', [
            'kode' => 'Liter',
            'nama' => '',
        ]);

        $response->assertOk()
            ->assertJsonPath('satuan.kode', 'ltr')
            ->assertJsonPath('satuan.nama', 'Liter');
        $this->assertDatabaseHas('satuans', [
            'kode' => 'ltr',
            'nama' => 'Liter',
        ]);
    }

    public function test_quick_add_unit_rejects_a_generated_code_longer_than_the_column_limit(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('satuan.create', 'web'));

        $response = $this->actingAs($user)->postJson('/api/quick-add/satuan', [
            'kode' => 'satuan dengan nama yang terlalu panjang',
            'nama' => '',
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.kode.0', 'Kode satuan otomatis harus berisi 1-20 karakter. Gunakan satuan yang lebih singkat.');
        $this->assertDatabaseCount('satuans', 0);
    }

    public function test_quick_add_creates_master_and_local_barang_for_the_active_warehouse(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('barang.create', 'web'));
        $gudang = Gudang::create([
            'kode' => 'QUICK-ADD-GUDANG',
            'nama' => 'Gudang Sukahati',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $user->gudang()->attach($gudang);
        $category = KategoriBarang::create(['nama' => 'Kemasan', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS-QUICK', 'nama' => 'Pieces', 'is_aktif' => true]);

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->postJson('/api/quick-add/barang', [
                'kode' => 'quick-001',
                'nama' => 'Kardus Quick Add',
                'kategori_id' => $category->id,
                'satuan_id' => $unit->id,
                'minimum_stok' => 6,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('barang.nama', 'Kardus Quick Add')
            ->assertJsonMissingPath('barang.lokasi_rak');

        $masterBarang = MasterBarang::query()->where('kode', 'QUICK-001')->firstOrFail();
        $barang = Barang::query()->where('master_barang_id', $masterBarang->id)->firstOrFail();

        $this->assertSame($gudang->id, $barang->gudang_id);
        $this->assertSame('QUICK-001', $barang->kode);
        $this->assertSame(6, $barang->minimum_stok);
    }

    public function test_quick_add_returns_validation_error_when_user_has_no_active_warehouse_assignment(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('barang.create', 'web'));
        $category = KategoriBarang::create(['nama' => 'Kemasan', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS-UNASSIGNED', 'nama' => 'Pieces', 'is_aktif' => true]);

        $response = $this->actingAs($user)
            ->postJson('/api/quick-add/barang', [
                'nama' => 'Kardus Tanpa Gudang',
                'kategori_id' => $category->id,
                'satuan_id' => $unit->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('gudang_id');
    }

    public function test_quick_add_rejects_a_company_code_with_a_different_master_category(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('barang.create', 'web'));
        $gudang = Gudang::create([
            'kode' => 'QUICK-CATEGORY-GUDANG',
            'nama' => 'Gudang Sukahati',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $user->gudang()->attach($gudang);
        $masterCategory = KategoriBarang::create(['nama' => 'Kemasan', 'is_aktif' => true]);
        $differentCategory = KategoriBarang::create(['nama' => 'Bahan Baku', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS-CONFLICT', 'nama' => 'Pieces', 'is_aktif' => true]);
        MasterBarang::create([
            'kode' => 'QUICK-002',
            'nama' => 'Kardus Sama',
            'kategori_id' => $masterCategory->id,
            'satuan_id' => $unit->id,
            'is_aktif' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $gudang->id])
            ->postJson('/api/quick-add/barang', [
                'kode' => 'QUICK-002',
                'nama' => 'Kardus Sama',
                'kategori_id' => $differentCategory->id,
                'satuan_id' => $unit->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('kode_barang');
        $this->assertSame(0, Barang::query()->count());
        $this->assertSame(1, MasterBarang::query()->count());
    }
}
