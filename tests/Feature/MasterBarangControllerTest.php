<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\MasterBarang;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class MasterBarangControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_sees_registered_warehouses_without_stock_information(): void
    {
        $user = User::factory()->create();
        $category = KategoriBarang::create(['nama' => 'Kemasan', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS-MASTER', 'nama' => 'Pieces', 'is_aktif' => true]);
        $masterBarang = MasterBarang::create([
            'kode' => 'MASTER-001',
            'nama' => 'Kardus Besar',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'is_aktif' => true,
        ]);
        $gudang = Gudang::create([
            'kode' => 'GUDANG-MASTER',
            'nama' => 'Gudang Sukahati',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        Barang::create([
            'master_barang_id' => $masterBarang->id,
            'gudang_id' => $gudang->id,
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'kode' => 'LOCAL-001',
            'nama' => 'Kardus Besar',
            'minimum_stok' => 77,
            'is_aktif' => false,
        ]);

        $response = $this->actingAs($user)->get(route('master.barang.index'));

        $response->assertOk()
            ->assertSee('Kardus Besar')
            ->assertSee('Gudang Sukahati')
            ->assertDontSee('Katalog Master Barang')
            ->assertDontSee('Total Barang')
            ->assertDontSee('Belum Terdaftar')
            ->assertDontSee('Stok Saat Ini')
            ->assertDontSee('Stok Minimum Lokal')
            ->assertDontSee('77');
    }

    public function test_catalog_filters_by_unit_and_registration_state_with_eight_rows_per_page(): void
    {
        $user = User::factory()->create();
        $category = KategoriBarang::create(['nama' => 'Kemasan Filter', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS-FILTER', 'nama' => 'Pieces Filter', 'is_aktif' => true]);
        $otherUnit = Satuan::create(['kode' => 'KG-FILTER', 'nama' => 'Kilogram Filter', 'is_aktif' => true]);
        $gudang = Gudang::create([
            'kode' => 'GUDANG-FILTER',
            'nama' => 'Gudang Filter',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $registeredMaster = MasterBarang::create([
            'kode' => 'MASTER-FILTER-1',
            'nama' => 'Barang Sudah Terdaftar',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'is_aktif' => true,
        ]);
        $unregisteredMaster = MasterBarang::create([
            'kode' => 'MASTER-FILTER-2',
            'nama' => 'Barang Belum Terdaftar',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'is_aktif' => true,
        ]);
        $otherUnitMaster = MasterBarang::create([
            'kode' => 'MASTER-FILTER-3',
            'nama' => 'Barang Satuan Lain',
            'kategori_id' => $category->id,
            'satuan_id' => $otherUnit->id,
            'is_aktif' => true,
        ]);
        $this->localBarang($registeredMaster, $gudang, $category, $unit, 'LOCAL-FILTER-1', 0);
        $this->localBarang($otherUnitMaster, $gudang, $category, $otherUnit, 'LOCAL-FILTER-3', 0);

        $registeredResponse = $this->actingAs($user)->get(route('master.barang.index', [
            'tab' => 'barang',
            'satuan_id' => $unit->id,
            'registrasi' => 'terdaftar',
            'per_page' => 8,
        ]));

        $registeredResponse->assertOk()
            ->assertSee('Barang Sudah Terdaftar')
            ->assertDontSee('Barang Belum Terdaftar')
            ->assertDontSee('Barang Satuan Lain')
            ->assertViewHas('masterBarangs', fn (LengthAwarePaginator $masterBarangs): bool => $masterBarangs->total() === 1
                && $masterBarangs->perPage() === 8);

        $unregisteredResponse = $this->get(route('master.barang.index', [
            'tab' => 'barang',
            'satuan_id' => $unit->id,
            'registrasi' => 'belum',
        ]));

        $unregisteredResponse->assertOk()
            ->assertSee('Barang Belum Terdaftar')
            ->assertDontSee('Barang Sudah Terdaftar')
            ->assertDontSee('Barang Satuan Lain')
            ->assertViewHas('masterBarangs', fn (LengthAwarePaginator $masterBarangs): bool => $masterBarangs->total() === 1
                && $masterBarangs->perPage() === 8);
    }

    public function test_catalog_matches_items_in_any_selected_warehouse_and_preserves_registration_filtering(): void
    {
        $user = User::factory()->create();
        $category = KategoriBarang::create(['nama' => 'Bahan Filter Lokasi', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS-LOKASI', 'nama' => 'Pieces Lokasi', 'is_aktif' => true]);
        $firstGudang = Gudang::create([
            'kode' => 'GUDANG-LOKASI-1',
            'nama' => 'Gudang Sukahati',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $secondGudang = Gudang::create([
            'kode' => 'GUDANG-LOKASI-2',
            'nama' => 'Gudang Tamansari',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $otherGudang = Gudang::create([
            'kode' => 'GUDANG-LOKASI-3',
            'nama' => 'Gudang Cibadak',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $sharedMaster = MasterBarang::create([
            'kode' => 'MASTER-LOKASI-1',
            'nama' => 'Barang Dua Gudang',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'is_aktif' => true,
        ]);
        $firstMaster = MasterBarang::create([
            'kode' => 'MASTER-LOKASI-2',
            'nama' => 'Barang Sukahati',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'is_aktif' => true,
        ]);
        $secondMaster = MasterBarang::create([
            'kode' => 'MASTER-LOKASI-3',
            'nama' => 'Barang Tamansari',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'is_aktif' => true,
        ]);
        $otherMaster = MasterBarang::create([
            'kode' => 'MASTER-LOKASI-4',
            'nama' => 'Barang Cibadak',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'is_aktif' => true,
        ]);
        $unregisteredMaster = MasterBarang::create([
            'kode' => 'MASTER-LOKASI-5',
            'nama' => 'Barang Belum Terdaftar',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'is_aktif' => true,
        ]);
        $this->localBarang($sharedMaster, $firstGudang, $category, $unit, 'LOCAL-LOKASI-1', 0);
        $this->localBarang($sharedMaster, $secondGudang, $category, $unit, 'LOCAL-LOKASI-2', 0);
        $this->localBarang($firstMaster, $firstGudang, $category, $unit, 'LOCAL-LOKASI-3', 0);
        $this->localBarang($secondMaster, $secondGudang, $category, $unit, 'LOCAL-LOKASI-4', 0);
        $this->localBarang($otherMaster, $otherGudang, $category, $unit, 'LOCAL-LOKASI-5', 0);

        $selectedWarehousesResponse = $this->actingAs($user)->get(route('master.barang.index', [
            'tab' => 'barang',
            'gudang_id' => [$firstGudang->id, $secondGudang->id],
        ]));

        $selectedWarehousesResponse->assertOk()
            ->assertSee('Barang Dua Gudang')
            ->assertSee('Barang Sukahati')
            ->assertSee('Barang Tamansari')
            ->assertDontSee('Barang Cibadak')
            ->assertDontSee('Barang Belum Terdaftar')
            ->assertViewHas('masterBarangs', fn (LengthAwarePaginator $masterBarangs): bool => $masterBarangs->total() === 3);

        $notRegisteredInSelectedWarehousesResponse = $this->get(route('master.barang.index', [
            'tab' => 'barang',
            'gudang_id' => [$firstGudang->id, $secondGudang->id],
            'registrasi' => 'belum',
        ]));

        $notRegisteredInSelectedWarehousesResponse->assertOk()
            ->assertSee('Barang Cibadak')
            ->assertSee('Barang Belum Terdaftar')
            ->assertDontSee('Barang Dua Gudang')
            ->assertDontSee('Barang Sukahati')
            ->assertDontSee('Barang Tamansari')
            ->assertViewHas('masterBarangs', fn (LengthAwarePaginator $masterBarangs): bool => $masterBarangs->total() === 2);

        $legacySingleWarehouseResponse = $this->get(route('master.barang.index', [
            'tab' => 'barang',
            'gudang_id' => $firstGudang->id,
        ]));

        $legacySingleWarehouseResponse->assertOk()
            ->assertSee('Barang Dua Gudang')
            ->assertSee('Barang Sukahati')
            ->assertDontSee('Barang Tamansari')
            ->assertViewHas('filters', fn (array $filters): bool => $filters['gudang_id'] === [(string) $firstGudang->id]);
    }

    public function test_user_with_barang_manage_permission_creates_master_and_registers_it_to_a_warehouse(): void
    {
        $user = $this->userWithBarangManagementPermission();
        $category = KategoriBarang::create(['nama' => 'Kemasan', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS-REG', 'nama' => 'Pieces', 'is_aktif' => true]);
        $gudang = Gudang::create([
            'kode' => 'GUDANG-REG',
            'nama' => 'Gudang Tamansari',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);

        $this->actingAs($user)
            ->post(route('master.barang.store'), [
                'kode' => ' master-002 ',
                'nama' => ' Plastik Bening ',
                'kategori_id' => $category->id,
                'satuan_id' => $unit->id,
            ])
            ->assertRedirect(route('master.barang.index'));

        $masterBarang = MasterBarang::query()->where('kode', 'MASTER-002')->firstOrFail();

        $this->actingAs($user)
            ->post(route('master.barang.register', $masterBarang), [
                'gudang_id' => $gudang->id,
                'kode_lokal' => 'tam-002',
                'minimum_stok' => 8,
            ])
            ->assertRedirect(route('master.barang.index'));

        $this->assertDatabaseHas('barangs', [
            'master_barang_id' => $masterBarang->id,
            'gudang_id' => $gudang->id,
            'kode' => 'TAM-002',
            'nama' => 'Plastik Bening',
            'minimum_stok' => 8,
        ]);

        $this->actingAs($user)
            ->post(route('master.barang.register', $masterBarang), [
                'gudang_id' => $gudang->id,
            ])
            ->assertSessionHasErrors('gudang_id');
        $this->assertSame(1, Barang::query()->where('master_barang_id', $masterBarang->id)->count());
    }

    public function test_add_barang_menu_opens_the_manual_form_for_users_without_import_permission(): void
    {
        $user = $this->userWithBarangManagementPermission();

        $this->actingAs($user)
            ->get(route('master.barang.index', ['tab' => 'barang', 'barang_view' => 'add']))
            ->assertOk()
            ->assertSee('id="master-tab-add"', false)
            ->assertSee("modal: 'create'", false)
            ->assertSee('Input Manual Barang')
            ->assertDontSee('master-barang-add-options', false)
            ->assertDontSee('Import Excel');
    }

    public function test_manual_add_from_add_menu_returns_to_the_menu_after_creating_master(): void
    {
        $user = $this->userWithBarangManagementPermission();
        $category = KategoriBarang::create(['nama' => 'Kategori Manual', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS-MANUAL', 'nama' => 'Pieces Manual', 'is_aktif' => true]);

        $this->actingAs($user)
            ->post(route('master.barang.store'), [
                'kode' => 'MANUAL-ADD-001',
                'nama' => 'Barang Manual',
                'kategori_id' => $category->id,
                'satuan_id' => $unit->id,
                'return_to_add' => '1',
            ])
            ->assertRedirect(route('master.barang.index', ['tab' => 'barang', 'barang_view' => 'add']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('master_barangs', [
            'kode' => 'MANUAL-ADD-001',
            'nama' => 'Barang Manual',
        ]);
    }

    public function test_manual_add_rejects_a_name_already_used_with_another_code(): void
    {
        $user = $this->userWithBarangManagementPermission();
        $category = KategoriBarang::create(['nama' => 'Kategori Konflik', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS-CONFLICT', 'nama' => 'Pieces Konflik', 'is_aktif' => true]);
        MasterBarang::create([
            'kode' => 'EXISTING-MANUAL-001',
            'nama' => 'Barang Sama',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'is_aktif' => true,
        ]);

        $this->actingAs($user)
            ->from(route('master.barang.index', ['tab' => 'barang', 'barang_view' => 'add']))
            ->post(route('master.barang.store'), [
                '_modal' => 'create',
                'return_to_add' => '1',
                'kode' => 'NEW-MANUAL-001',
                'nama' => ' barang sama ',
                'kategori_id' => $category->id,
                'satuan_id' => $unit->id,
            ])
            ->assertRedirect(route('master.barang.index', ['tab' => 'barang', 'barang_view' => 'add']))
            ->assertSessionHasErrors([
                'nama' => 'Nama barang sudah digunakan oleh Master Barang. Periksa data barang sebelum menggunakan kode berbeda.',
            ]);

        $this->assertDatabaseMissing('master_barangs', ['kode' => 'NEW-MANUAL-001']);
    }

    public function test_user_without_barang_manage_permission_cannot_create_a_master_barang(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('master.barang.store'), [])
            ->assertForbidden();
    }

    public function test_master_update_propagates_shared_fields_without_changing_local_codes_or_minimums(): void
    {
        $user = $this->userWithBarangManagementPermission();
        $category = KategoriBarang::create(['nama' => 'Kemasan', 'is_aktif' => true]);
        $newCategory = KategoriBarang::create(['nama' => 'Bahan Baku', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS-UPD', 'nama' => 'Pieces', 'is_aktif' => true]);
        $masterBarang = MasterBarang::create([
            'kode' => 'MASTER-003',
            'nama' => 'Barang Lama',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'is_aktif' => true,
        ]);
        $firstGudang = Gudang::create([
            'kode' => 'GUDANG-UPD-1',
            'nama' => 'Gudang Satu',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $secondGudang = Gudang::create([
            'kode' => 'GUDANG-UPD-2',
            'nama' => 'Gudang Dua',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $firstLocalBarang = $this->localBarang($masterBarang, $firstGudang, $category, $unit, 'LOCAL-A', 3);
        $secondLocalBarang = $this->localBarang($masterBarang, $secondGudang, $category, $unit, 'LOCAL-B', 9);

        $this->actingAs($user)
            ->put(route('master.barang.update', $masterBarang), [
                'kode' => 'MASTER-003',
                'nama' => 'Barang Baru',
                'kategori_id' => $newCategory->id,
                'satuan_id' => $unit->id,
                'harga_satuan' => 1250,
                'is_aktif' => true,
            ])
            ->assertRedirect(route('master.barang.index'));

        $this->assertDatabaseHas('barangs', [
            'id' => $firstLocalBarang->id,
            'kode' => 'LOCAL-A',
            'nama' => 'Barang Baru',
            'kategori_id' => $newCategory->id,
            'minimum_stok' => 3,
        ]);
        $this->assertDatabaseHas('barangs', [
            'id' => $secondLocalBarang->id,
            'kode' => 'LOCAL-B',
            'nama' => 'Barang Baru',
            'kategori_id' => $newCategory->id,
            'minimum_stok' => 9,
        ]);
    }

    public function test_registering_previously_deleted_local_barang_restores_its_transaction_id(): void
    {
        $user = $this->userWithBarangManagementPermission();
        $category = KategoriBarang::create(['nama' => 'Kemasan', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS-RESTORE', 'nama' => 'Pieces', 'is_aktif' => true]);
        $masterBarang = MasterBarang::create([
            'kode' => 'MASTER-RESTORE',
            'nama' => 'Barang Dipulihkan',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'is_aktif' => true,
        ]);
        $gudang = Gudang::create([
            'kode' => 'GUDANG-RESTORE',
            'nama' => 'Gudang Pemulihan',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $barang = $this->localBarang($masterBarang, $gudang, $category, $unit, 'LOCAL-RESTORE', 4);
        $barang->delete();

        $this->actingAs($user)
            ->post(route('master.barang.register', $masterBarang), [
                'gudang_id' => $gudang->id,
                'minimum_stok' => 2,
            ])
            ->assertRedirect(route('master.barang.index'));

        $this->assertDatabaseHas('barangs', [
            'id' => $barang->id,
            'master_barang_id' => $masterBarang->id,
            'gudang_id' => $gudang->id,
            'kode' => 'LOCAL-RESTORE',
            'minimum_stok' => 2,
            'deleted_at' => null,
        ]);
    }

    public function test_master_update_can_add_multiple_warehouses_and_remove_existing_ones(): void
    {
        $user = $this->userWithBarangManagementPermission();
        $category = KategoriBarang::create(['nama' => 'Kemasan Sync', 'is_aktif' => true]);
        $unit = Satuan::create(['kode' => 'PCS-SYNC', 'nama' => 'Pieces', 'is_aktif' => true]);
        $masterBarang = MasterBarang::create([
            'kode' => 'MASTER-SYNC-1',
            'nama' => 'Barang Sync',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'is_aktif' => true,
        ]);
        $gudangA = Gudang::create(['kode' => 'GUD-A', 'nama' => 'Gudang A', 'jenis' => 'gudang_umum', 'is_aktif' => true]);
        $gudangB = Gudang::create(['kode' => 'GUD-B', 'nama' => 'Gudang B', 'jenis' => 'gudang_umum', 'is_aktif' => true]);
        $gudangC = Gudang::create(['kode' => 'GUD-C', 'nama' => 'Gudang C', 'jenis' => 'gudang_umum', 'is_aktif' => true]);

        $this->localBarang($masterBarang, $gudangA, $category, $unit, 'LOCAL-A', 0);
        $barangB = $this->localBarang($masterBarang, $gudangB, $category, $unit, 'LOCAL-B', 0);

        $this->actingAs($user)
            ->put(route('master.barang.update', $masterBarang), [
                'kode' => 'MASTER-SYNC-1',
                'nama' => 'Barang Sync Diperbarui',
                'kategori_id' => $category->id,
                'satuan_id' => $unit->id,
                'is_aktif' => true,
                'has_gudang_sync' => '1',
                'gudang_ids' => [$gudangA->id, $gudangC->id],
            ])
            ->assertRedirect(route('master.barang.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('barangs', [
            'master_barang_id' => $masterBarang->id,
            'gudang_id' => $gudangA->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('barangs', [
            'master_barang_id' => $masterBarang->id,
            'gudang_id' => $gudangC->id,
            'deleted_at' => null,
        ]);
        $this->assertSoftDeleted('barangs', [
            'id' => $barangB->id,
        ]);
    }

    private function userWithBarangManagementPermission(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permission = Permission::findOrCreate('barang.manage', 'web');

        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        return $user;
    }

    private function localBarang(
        MasterBarang $masterBarang,
        Gudang $gudang,
        KategoriBarang $category,
        Satuan $unit,
        string $code,
        int $minimumStock,
    ): Barang {
        return Barang::create([
            'master_barang_id' => $masterBarang->id,
            'gudang_id' => $gudang->id,
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'kode' => $code,
            'nama' => $masterBarang->nama,
            'minimum_stok' => $minimumStock,
            'is_aktif' => true,
        ]);
    }
}
