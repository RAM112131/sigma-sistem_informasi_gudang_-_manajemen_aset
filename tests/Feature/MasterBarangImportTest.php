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
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class MasterBarangImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_import_permission_cannot_open_import_screen(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('master.barang.import.index'));

        $response->assertForbidden();
    }

    public function test_legacy_import_url_redirects_to_the_master_barang_import_tab(): void
    {
        [$user] = $this->authorizedImportUser();

        $this->actingAs($user)
            ->get(route('master.barang.import.index'))
            ->assertRedirect(route('master.barang.index', [
                'tab' => 'barang',
                'barang_view' => 'import',
            ]));
    }

    public function test_import_page_uses_only_the_users_assigned_active_warehouse(): void
    {
        [$user, $assignedWarehouse] = $this->authorizedImportUser();
        $otherWarehouse = Gudang::create([
            'kode' => 'OTHER-WH',
            'nama' => 'Gudang Lain',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);

        $this->actingAs($user)
            ->get(route('master.barang.index', [
                'tab' => 'barang',
                'barang_view' => 'import',
                'gudang_id' => $otherWarehouse->id,
            ]))
            ->assertOk()
            ->assertSee('Gudang Impor Test')
            ->assertDontSee('Gudang Lain')
            ->assertDontSee('data-warehouse-option')
            ->assertSee('id="master-barang-import"', false)
            ->assertSee('data-file-feedback', false)
            ->assertSee('Status / catatan pemeriksaan')
            ->assertSee('data-review-results', false)
            ->assertSee('data-confirm-import', false)
            ->assertSee('class="mbi-action-footer"', false)
            ->assertSeeInOrder(['data-grid', 'class="mbi-action-footer"', 'data-clear-grid', 'data-confirm-import'])
            ->assertDontSee('data-undo-paste', false)
            ->assertDontSee('data-add-rows', false)
            ->assertDontSee('Urungkan tempelan')
            ->assertDontSee('Tambah 10 baris')
            ->assertDontSee('<th scope="col">Deskripsi</th>', false)
            ->assertDontSee('Unggah file Excel atau tempel data barang untuk ditinjau sebelum didaftarkan ke gudang tujuan.')
            ->assertDontSee('Belum Terdaftar')
            ->assertDontSee('Belum memiliki gudang')
            ->assertDontSee('data-review-url', false)
            ->assertSee('Periksa file')
            ->assertSee('Simpan')
            ->assertDontSee('Tinjau data tempelan')
            ->assertSeeInOrder(['id="master-tab-add"', '>Tambah Barang</a>', 'id="master-tab-barang"', '>Master Barang</a>'])
            ->assertSee('aria-current="page"', false)
            ->assertDontSee('Pilih cara tambah barang')
            ->assertDontSee('Pilih file Excel')
            ->assertDontSee('Mulai input manual')
            ->assertDontSee('Katalog Barang</a>', false)
            ->assertDontSee('Impor Master Barang</a>', false);
    }

    public function test_add_barang_menu_opens_the_combined_manual_and_excel_import_screen(): void
    {
        [$user] = $this->authorizedImportUser();

        $this->actingAs($user)
            ->get(route('master.barang.index', ['tab' => 'barang', 'barang_view' => 'add']))
            ->assertOk()
            ->assertSee('id="master-tab-add"', false)
            ->assertSee('Unggah file Excel')
            ->assertSee('Tempel dari Excel')
            ->assertSee('data-grid', false)
            ->assertDontSee('master-barang-add-options', false)
            ->assertDontSee('Pilih cara tambah barang');
    }

    public function test_manual_import_grid_suggests_active_categories_and_unit_names_or_codes(): void
    {
        [$user, $warehouse] = $this->authorizedImportUser();
        $this->references($warehouse);
        KategoriBarang::create([
            'gudang_id' => $warehouse->id,
            'nama' => 'Kategori Nonaktif',
            'is_aktif' => false,
        ]);
        Satuan::create([
            'gudang_id' => $warehouse->id,
            'kode' => 'CTN',
            'nama' => 'Karton',
            'is_aktif' => true,
        ]);
        Satuan::create([
            'gudang_id' => $warehouse->id,
            'kode' => 'BOX-INACTIVE',
            'nama' => 'Box Nonaktif',
            'is_aktif' => false,
        ]);

        $this->actingAs($user)
            ->get(route('master.barang.index', ['tab' => 'barang', 'barang_view' => 'import']))
            ->assertOk()
            ->assertSee('id="mbi-category-suggestions"', false)
            ->assertSee('<option value="Kemasan"></option>', false)
            ->assertDontSee('<option value="Kategori Nonaktif"></option>', false)
            ->assertSee('id="mbi-unit-suggestions"', false)
            ->assertSee('<option value="PCS"></option>', false)
            ->assertSee('<option value="Karton"></option>', false)
            ->assertSee('<option value="CTN"></option>', false)
            ->assertDontSee('<option value="BOX-INACTIVE"></option>', false);
    }

    public function test_analysis_and_confirmation_reject_an_unassigned_warehouse(): void
    {
        [$user] = $this->authorizedImportUser();
        $otherWarehouse = Gudang::create([
            'kode' => 'OTHER-WH',
            'nama' => 'Gudang Lain',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $payload = [
            'warehouse_ids' => [$otherWarehouse->id],
            'rows' => [$this->row(2, 'BLOCKED-001', 'Barang Ditolak')],
        ];

        $this->actingAs($user)
            ->postJson(route('master.barang.import.analyze'), $payload)
            ->assertForbidden();

        $this->postJson(route('master.barang.import.confirm'), $payload)
            ->assertForbidden();

        $this->assertDatabaseMissing('master_barangs', ['kode' => 'BLOCKED-001']);
    }

    public function test_excel_upload_rejects_an_unassigned_warehouse(): void
    {
        [$user] = $this->authorizedImportUser();
        $otherWarehouse = Gudang::create([
            'kode' => 'OTHER-WH',
            'nama' => 'Gudang Lain',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $file = $this->excelUpload([
            ['kode_barang', 'nama_barang', 'kategori', 'satuan', 'deskripsi'],
            ['XLS-BLOCKED', 'Barang Ditolak', 'Kemasan', 'PCS', ''],
        ]);

        $this->actingAs($user)
            ->post(route('master.barang.import.excel'), [
                'warehouse_ids' => [$otherWarehouse->id],
                'file' => $file['upload'],
            ])
            ->assertForbidden();

        @unlink($file['path']);
    }

    public function test_unit_business_admin_is_locked_to_the_assigned_sukahati_warehouse(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permission = Permission::findOrCreate('barang.import', 'web');
        $user = User::factory()->create();
        $warehouse = Gudang::create([
            'kode' => 'SUKAHATI',
            'nama' => 'RPA Sukahati',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $user->gudang()->attach($warehouse);
        $user->givePermissionTo($permission);
        $user->syncRoles([Role::findOrCreate('Admin Gudang Umum', 'web')]);
        [$category, $unit] = $this->references($warehouse);

        $this->actingAs($user)
            ->get(route('master.barang.index', ['tab' => 'barang', 'barang_view' => 'import']))
            ->assertOk()
            ->assertSee('RPA Sukahati')
            ->assertDontSee('data-warehouse-option');

        $this->postJson(route('master.barang.import.confirm'), [
            'warehouse_ids' => [$warehouse->id],
            'rows' => [$this->row(2, 'SUK-001', 'Barang Sukahati')],
        ])->assertOk()
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('registered_warehouse_count', 1);

        $this->assertDatabaseHas('barangs', [
            'gudang_id' => $warehouse->id,
            'kode' => 'SUK-001',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
        ]);
    }

    public function test_super_admin_can_register_imported_items_in_multiple_selected_warehouses(): void
    {
        [$user, $firstWarehouse] = $this->authorizedImportUser();
        $secondWarehouse = Gudang::create([
            'kode' => 'SUKAHATI',
            'nama' => 'RPA Sukahati',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $user->syncRoles([Role::findOrCreate('Super Admin', 'web')]);
        $firstCategory = KategoriBarang::create([
            'gudang_id' => $firstWarehouse->id,
            'nama' => 'Kemasan',
            'is_aktif' => true,
        ]);
        $secondCategory = KategoriBarang::create([
            'gudang_id' => $secondWarehouse->id,
            'nama' => 'Kemasan',
            'is_aktif' => true,
        ]);
        $firstUnit = Satuan::create([
            'gudang_id' => $firstWarehouse->id,
            'kode' => 'PCS-A',
            'nama' => 'PCS',
            'is_aktif' => true,
        ]);
        $secondUnit = Satuan::create([
            'gudang_id' => $secondWarehouse->id,
            'kode' => 'PCS-B',
            'nama' => 'PCS',
            'is_aktif' => true,
        ]);

        $this->actingAs($user)
            ->get(route('master.barang.index', ['tab' => 'barang', 'barang_view' => 'import']))
            ->assertOk()
            ->assertSee('data-warehouse-option', false)
            ->assertSee('Gudang Impor Test')
            ->assertSee('RPA Sukahati');

        $this->postJson(route('master.barang.import.confirm'), [
            'warehouse_ids' => [$firstWarehouse->id, $secondWarehouse->id],
            'rows' => [$this->row(2, 'MULTI-001', 'Barang Multi Gudang')],
        ])->assertOk()
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('registered_warehouse_count', 2);

        $this->assertDatabaseHas('barangs', [
            'gudang_id' => $firstWarehouse->id,
            'kode' => 'MULTI-001',
            'kategori_id' => $firstCategory->id,
            'satuan_id' => $firstUnit->id,
        ]);
        $this->assertDatabaseHas('barangs', [
            'gudang_id' => $secondWarehouse->id,
            'kode' => 'MULTI-001',
            'kategori_id' => $secondCategory->id,
            'satuan_id' => $secondUnit->id,
        ]);
    }

    public function test_analysis_reports_new_reused_registered_duplicate_and_incomplete_rows(): void
    {
        [$user, $warehouse] = $this->authorizedImportUser();
        [$category, $unit] = $this->references($warehouse);
        $reusedMaster = $this->master('OLD-REUSE', 'Barang Lama', $category, $unit);
        $registeredMaster = $this->master('OLD-REGISTERED', 'Barang Terdaftar', $category, $unit);
        $this->register($registeredMaster, $warehouse, $category, $unit);

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->postJson(route('master.barang.import.analyze'), [
                'warehouse_ids' => [$warehouse->id],
                'rows' => [
                    $this->row(2, 'NEW-001', 'Barang Baru'),
                    $this->row(3, $reusedMaster->kode, $reusedMaster->nama),
                    $this->row(4, $registeredMaster->kode, $registeredMaster->nama),
                    $this->row(5, 'NEW-001', 'Barang Baru'),
                    ['line' => 6, 'kode_barang' => 'PARTIAL-001', 'nama_barang' => '', 'kategori' => '', 'satuan' => '', 'deskripsi' => ''],
                    ['line' => 7, 'kode_barang' => '', 'nama_barang' => '', 'kategori' => '', 'satuan' => '', 'deskripsi' => ''],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('summary.total', 6)
            ->assertJsonPath('summary.new', 1)
            ->assertJsonPath('summary.reused', 1)
            ->assertJsonPath('summary.registered', 1)
            ->assertJsonPath('summary.duplicate', 1)
            ->assertJsonPath('summary.invalid', 1)
            ->assertJsonPath('summary.ignored', 1)
            ->assertJsonPath('rows.0.status', 'new')
            ->assertJsonPath('rows.1.status', 'reused')
            ->assertJsonPath('rows.2.status', 'registered')
            ->assertJsonPath('rows.3.issues.0', 'Kode barang ini sudah muncul di baris 2.')
            ->assertJsonPath('rows.4.issues', ['Nama barang wajib diisi.'])
            ->assertJsonPath('rows.5.status', 'ignored');

        $this->assertDatabaseMissing('master_barangs', ['kode' => 'NEW-001']);
    }

    public function test_same_name_with_a_different_code_is_a_conflict_and_cannot_be_imported(): void
    {
        [$user, $warehouse] = $this->authorizedImportUser();
        [$category, $unit] = $this->references($warehouse);
        $existingMaster = $this->master('EXISTING-001', 'Barang Sama', $category, $unit);
        $row = $this->row(2, 'NEW-CODE-001', 'barang sama', '', '');

        $analysis = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->postJson(route('master.barang.import.analyze'), [
                'warehouse_ids' => [$warehouse->id],
                'rows' => [$row],
            ]);

        $analysis->assertOk()
            ->assertJsonPath('summary.conflict', 1)
            ->assertJsonPath('summary.new', 0)
            ->assertJsonPath('rows.0.status', 'conflict')
            ->assertJsonPath(
                'rows.0.issues',
                ['Nama barang yang sama ditemukan dengan kode barang berbeda. Periksa data barang sebelum mengimpor.'],
            )
            ->assertJsonPath('rows.0.preparation_notes', []);

        $confirmation = $this->postJson(route('master.barang.import.confirm'), [
            'warehouse_ids' => [$warehouse->id],
            'rows' => [$row],
        ]);

        $confirmation->assertOk()
            ->assertJsonPath('success_count', 0)
            ->assertJsonPath('rows.0.status', 'conflict');
        $this->assertModelExists($existingMaster);
        $this->assertDatabaseMissing('master_barangs', ['kode' => 'NEW-CODE-001']);
        $this->assertDatabaseMissing('barangs', [
            'gudang_id' => $warehouse->id,
            'kode' => 'NEW-CODE-001',
        ]);
    }

    public function test_different_codes_for_the_same_name_in_one_import_are_conflicts(): void
    {
        [$user, $warehouse] = $this->authorizedImportUser();
        $this->references($warehouse);
        $rows = [
            $this->row(2, 'FILE-NAME-001', 'Barang Kembar'),
            $this->row(3, 'FILE-NAME-002', 'barang kembar'),
        ];

        $analysis = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->postJson(route('master.barang.import.analyze'), [
                'warehouse_ids' => [$warehouse->id],
                'rows' => $rows,
            ]);

        $analysis->assertOk()
            ->assertJsonPath('summary.conflict', 2)
            ->assertJsonPath('summary.new', 0)
            ->assertJsonPath('rows.0.status', 'conflict')
            ->assertJsonPath('rows.1.status', 'conflict');

        $confirmation = $this->postJson(route('master.barang.import.confirm'), [
            'warehouse_ids' => [$warehouse->id],
            'rows' => $rows,
        ]);

        $confirmation->assertOk()
            ->assertJsonPath('success_count', 0)
            ->assertJsonPath('rows.0.status', 'conflict')
            ->assertJsonPath('rows.1.status', 'conflict');
        $this->assertDatabaseMissing('master_barangs', ['kode' => 'FILE-NAME-001']);
        $this->assertDatabaseMissing('master_barangs', ['kode' => 'FILE-NAME-002']);
    }

    public function test_analysis_returns_indonesian_message_when_unit_name_is_too_long(): void
    {
        [$user, $warehouse] = $this->authorizedImportUser();

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->postJson(route('master.barang.import.analyze'), [
                'warehouse_ids' => [$warehouse->id],
                'rows' => [$this->row(98, 'LONG-UNIT', 'Barang', 'Kemasan', str_repeat('A', 201))],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('rows.0.satuan')
            ->assertJsonFragment([
                'rows.0.satuan' => ['Satuan pada salah satu baris melebihi batas 200 karakter.'],
            ]);
    }

    public function test_excel_upload_shows_human_indonesian_messages_for_missing_inputs(): void
    {
        [$user] = $this->authorizedImportUser();

        $response = $this->actingAs($user)
            ->postJson(route('master.barang.import.excel'));

        $response->assertUnprocessable()
            ->assertJsonFragment(['warehouse_ids' => ['Pilih setidaknya satu gudang tujuan.']])
            ->assertJsonFragment(['file' => ['Pilih file Excel yang ingin diperiksa.']]);
    }

    public function test_confirmation_shows_human_indonesian_message_for_invalid_unit_name(): void
    {
        [$user, $warehouse] = $this->authorizedImportUser();

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->postJson(route('master.barang.import.confirm'), [
                'warehouse_ids' => [$warehouse->id],
                'rows' => [$this->row(2, 'LONG-UNIT', 'Barang', 'Kemasan', str_repeat('A', 201))],
            ]);

        $response->assertUnprocessable()
            ->assertJsonFragment([
                'rows.0.satuan' => ['Satuan pada salah satu baris melebihi batas 200 karakter.'],
            ]);
    }

    public function test_confirmation_creates_master_and_warehouse_item_and_is_safe_to_retry(): void
    {
        [$user, $warehouse] = $this->authorizedImportUser();
        [$category, $unit] = $this->references($warehouse);
        $existingMaster = $this->master('REUSE-001', 'Barang Dipakai Ulang', $category, $unit);
        $rows = [
            $this->row(2, 'NEW-002', 'Barang Baru'),
            $this->row(3, $existingMaster->kode, $existingMaster->nama),
        ];

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->postJson(route('master.barang.import.confirm'), ['warehouse_ids' => [$warehouse->id], 'rows' => $rows]);

        $response->assertOk()
            ->assertJsonPath('success_count', 2)
            ->assertJsonPath('skipped_count', 0)
            ->assertJsonPath('rows.0.status', 'imported')
            ->assertJsonPath('rows.1.status', 'imported');
        $this->assertDatabaseHas('master_barangs', [
            'kode' => 'NEW-002',
            'nama' => 'Barang Baru',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
        ]);
        $this->assertDatabaseHas('barangs', [
            'gudang_id' => $warehouse->id,
            'master_barang_id' => MasterBarang::query()->where('kode', 'NEW-002')->value('id'),
            'kode' => 'NEW-002',
            'minimum_stok' => 0,
        ]);

        $retry = $this->postJson(route('master.barang.import.confirm'), ['warehouse_ids' => [$warehouse->id], 'rows' => $rows]);

        $retry->assertOk()
            ->assertJsonPath('success_count', 0)
            ->assertJsonPath('skipped_count', 2);
        $this->assertDatabaseCount('master_barangs', 2);
        $this->assertDatabaseCount('barangs', 2);
    }

    public function test_analysis_previews_new_category_and_normalized_unit_without_saving_references(): void
    {
        [$user, $warehouse] = $this->authorizedImportUser();

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->postJson(route('master.barang.import.analyze'), [
                'warehouse_ids' => [$warehouse->id],
                'rows' => [$this->row(2, 'NEW-REF', 'Barang Baru', 'Peralatan Baru', 'liter')],
            ]);

        $response->assertOk()
            ->assertJsonPath('rows.0.status', 'new')
            ->assertJsonPath('rows.0.satuan', 'Liter')
            ->assertJsonPath('rows.0.satuan_kode', 'ltr')
            ->assertJsonPath('rows.0.preparation_notes.0', 'Kategori “Peralatan Baru” akan dibuat saat data disimpan.')
            ->assertJsonPath('rows.0.preparation_notes.1', 'Satuan baru akan dibuat: Liter (ltr).');
        $this->assertDatabaseMissing('kategori_barangs', ['nama' => 'Peralatan Baru']);
        $this->assertDatabaseMissing('satuans', ['kode' => 'ltr']);
        $this->assertDatabaseMissing('master_barangs', ['kode' => 'NEW-REF']);
    }

    public function test_confirmation_creates_missing_category_and_unit_after_review(): void
    {
        [$user, $warehouse] = $this->authorizedImportUser();
        $row = $this->row(2, 'NEW-REF', 'Barang Baru', 'Peralatan Baru', 'ltr');

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->postJson(route('master.barang.import.confirm'), [
                'warehouse_ids' => [$warehouse->id],
                'rows' => [$row],
            ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('rows.0.status', 'imported');
        $this->assertDatabaseHas('kategori_barangs', [
            'gudang_id' => null,
            'nama' => 'Peralatan Baru',
            'is_aktif' => true,
        ]);
        $this->assertDatabaseHas('satuans', [
            'gudang_id' => null,
            'kode' => 'ltr',
            'nama' => 'Liter',
            'is_aktif' => true,
        ]);
        $this->assertDatabaseHas('master_barangs', [
            'kode' => 'NEW-REF',
            'kategori_id' => KategoriBarang::query()->where('nama', 'Peralatan Baru')->value('id'),
            'satuan_id' => Satuan::query()->where('kode', 'ltr')->value('id'),
        ]);
    }

    public function test_confirmation_generates_a_readable_name_for_an_unmapped_unit_code(): void
    {
        [$user, $warehouse] = $this->authorizedImportUser();
        [$category] = $this->references($warehouse);

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->postJson(route('master.barang.import.confirm'), [
                'warehouse_ids' => [$warehouse->id],
                'rows' => [$this->row(2, 'NEW-UNIT', 'Barang Baru', $category->nama, 'XYZ')],
            ])
            ->assertOk()
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('rows.0.satuan', 'Xyz');

        $this->assertDatabaseHas('satuans', [
            'kode' => 'xyz',
            'nama' => 'Xyz',
            'is_aktif' => true,
        ]);
    }

    public function test_confirmation_reuses_new_global_references_for_each_selected_warehouse(): void
    {
        [$user, $firstWarehouse] = $this->authorizedImportUser();
        $secondWarehouse = Gudang::create([
            'kode' => 'IMPORT-WH-2',
            'nama' => 'Gudang Impor Test 2',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $user->syncRoles([Role::findOrCreate('Super Admin', 'web')]);

        $this->actingAs($user)
            ->postJson(route('master.barang.import.confirm'), [
                'warehouse_ids' => [$firstWarehouse->id, $secondWarehouse->id],
                'rows' => [$this->row(2, 'MULTI-NEW-REF', 'Barang Multi Gudang', 'Peralatan Bersama', 'liter')],
            ])
            ->assertOk()
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('registered_warehouse_count', 2);

        $categoryId = KategoriBarang::query()
            ->whereNull('gudang_id')
            ->where('nama', 'Peralatan Bersama')
            ->value('id');
        $unitId = Satuan::query()->where('kode', 'ltr')->value('id');
        $this->assertDatabaseCount('kategori_barangs', 1);
        $this->assertDatabaseCount('satuans', 1);
        $this->assertDatabaseHas('barangs', [
            'gudang_id' => $firstWarehouse->id,
            'kode' => 'MULTI-NEW-REF',
            'kategori_id' => $categoryId,
            'satuan_id' => $unitId,
        ]);
        $this->assertDatabaseHas('barangs', [
            'gudang_id' => $secondWarehouse->id,
            'kode' => 'MULTI-NEW-REF',
            'kategori_id' => $categoryId,
            'satuan_id' => $unitId,
        ]);
    }

    public function test_confirmation_does_not_save_any_rows_when_the_batch_contains_invalid_or_duplicate_rows(): void
    {
        [$user, $warehouse] = $this->authorizedImportUser();
        $this->references($warehouse);

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->postJson(route('master.barang.import.confirm'), [
                'warehouse_ids' => [$warehouse->id],
                'rows' => [
                    $this->row(2, 'ATOMIC-001', 'Barang Valid', 'Kategori Baru', 'liter'),
                    $this->row(3, 'INVALID-001', ''),
                    $this->row(4, 'DUPLICATE-001', 'Barang Duplikat'),
                    $this->row(5, 'DUPLICATE-001', 'Barang Duplikat'),
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 0)
            ->assertJsonPath('skipped_count', 4)
            ->assertJsonPath('rows.0.status', 'new')
            ->assertJsonPath('rows.1.status', 'invalid')
            ->assertJsonPath('rows.2.status', 'new')
            ->assertJsonPath('rows.3.status', 'duplicate');
        $this->assertDatabaseMissing('master_barangs', ['kode' => 'ATOMIC-001']);
        $this->assertDatabaseMissing('master_barangs', ['kode' => 'DUPLICATE-001']);
        $this->assertDatabaseMissing('kategori_barangs', ['nama' => 'Kategori Baru']);
        $this->assertDatabaseMissing('satuans', ['kode' => 'ltr']);
    }

    public function test_confirmation_saves_more_than_one_analysis_batch_in_one_request(): void
    {
        [$user, $warehouse] = $this->authorizedImportUser();
        $this->references($warehouse);
        $rows = [];

        for ($line = 2; $line <= 121; $line++) {
            $rows[] = $this->row($line, "BULK-{$line}", "Barang {$line}");
        }

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->postJson(route('master.barang.import.confirm'), [
                'warehouse_ids' => [$warehouse->id],
                'rows' => $rows,
            ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 120)
            ->assertJsonPath('skipped_count', 0);
        $this->assertDatabaseCount('master_barangs', 120);
        $this->assertDatabaseCount('barangs', 120);
    }

    public function test_excel_upload_maps_columns_by_header_name_regardless_of_order(): void
    {
        [$user, $warehouse] = $this->authorizedImportUser();
        $this->withSession(['active_gudang_id' => $warehouse->id]);
        $validFile = $this->excelUpload([
            ['Kode Barang', 'Satuan', 'Kategori', 'catatan', 'Nama Barang'],
            ['XLS-001', 'PCS', 'Kemasan', 'Kolom tambahan', 'Barang dari Excel'],
        ], 'Master Barang', true);

        $validResponse = $this->actingAs($user)
            ->post(route('master.barang.import.excel'), [
                'warehouse_ids' => [$warehouse->id],
                'file' => $validFile['upload'],
            ]);
        @unlink($validFile['path']);

        $validResponse->assertOk()
            ->assertHeader('content-type', 'application/x-ndjson; charset=UTF-8')
            ->assertHeader('x-import-total', '1');
        $records = array_values(array_filter(explode("\n", $validResponse->streamedContent())));
        $this->assertSame([
            'line' => 3,
            'kode_barang' => 'XLS-001',
            'nama_barang' => 'Barang dari Excel',
            'kategori' => 'Kemasan',
            'satuan' => 'PCS',
        ], json_decode($records[0], true, flags: JSON_THROW_ON_ERROR));
    }

    public function test_excel_upload_rejects_a_file_missing_a_required_header(): void
    {
        [$user, $warehouse] = $this->authorizedImportUser();
        $invalidFile = $this->excelUpload([
            ['kode_barang', 'nama_barang', 'satuan', 'deskripsi'],
            ['XLS-002', 'Barang dari Excel', 'PCS', 'Contoh'],
        ]);

        $invalidResponse = $this->actingAs($user)->withSession(['active_gudang_id' => $warehouse->id])
            ->postJson(route('master.barang.import.excel'), [
                'warehouse_ids' => [$warehouse->id],
                'file' => $invalidFile['upload'],
            ]);
        @unlink($invalidFile['path']);

        $invalidResponse->assertUnprocessable()
            ->assertJsonValidationErrors('file')
            ->assertJsonPath('errors.file.0', 'Header sheet Master Barang harus memiliki kolom wajib: kategori.');
    }

    public function test_excel_upload_accepts_master_barang_sheet_name_with_different_case_and_spacing(): void
    {
        [$user, $warehouse] = $this->authorizedImportUser();
        $file = $this->excelUpload([
            ['kode_barang', 'nama_barang', 'kategori', 'satuan', 'deskripsi'],
            ['XLS-003', 'Barang dari Excel', 'Kemasan', 'PCS', 'Contoh'],
        ], 'MASTER  BARANG');

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->post(route('master.barang.import.excel'), [
                'warehouse_ids' => [$warehouse->id],
                'file' => $file['upload'],
            ]);
        @unlink($file['path']);

        $response->assertOk()
            ->assertHeader('x-import-total', '1');
    }

    public function test_excel_upload_returns_validation_error_when_master_barang_sheet_is_missing(): void
    {
        [$user, $warehouse] = $this->authorizedImportUser();
        $file = $this->excelUpload([
            ['kode_barang', 'nama_barang', 'kategori', 'satuan', 'deskripsi'],
            ['XLS-004', 'Barang dari Excel', 'Kemasan', 'PCS', 'Contoh'],
        ], 'Barang');

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->postJson(route('master.barang.import.excel'), [
                'warehouse_ids' => [$warehouse->id],
                'file' => $file['upload'],
            ]);
        @unlink($file['path']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('file')
            ->assertJsonPath('errors.file.0', 'File tidak memiliki sheet bernama “Master Barang”. Gunakan template resmi.');
    }

    public function test_excel_upload_streams_rows_across_chunk_boundaries(): void
    {
        [$user, $warehouse] = $this->authorizedImportUser();
        $rows = [['kode_barang', 'nama_barang', 'kategori', 'satuan', 'deskripsi']];

        for ($index = 1; $index <= 501; $index++) {
            $rows[] = ["CHUNK-{$index}", "Barang {$index}", 'Kemasan', 'PCS', ''];
        }

        $file = $this->excelUpload($rows);
        $response = $this->actingAs($user)
            ->post(route('master.barang.import.excel'), [
                'warehouse_ids' => [$warehouse->id],
                'file' => $file['upload'],
            ]);
        @unlink($file['path']);

        $records = array_values(array_filter(explode("\n", $response->streamedContent())));
        $response->assertOk()
            ->assertHeader('x-import-total', '501');
        $this->assertCount(501, $records);
        $this->assertSame(2, json_decode($records[0], true, flags: JSON_THROW_ON_ERROR)['line']);
        $this->assertSame(502, json_decode($records[500], true, flags: JSON_THROW_ON_ERROR)['line']);
    }

    public function test_template_download_returns_the_official_xlsx_file(): void
    {
        [$user, $warehouse] = $this->authorizedImportUser();

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $warehouse->id])
            ->get(route('master.barang.import.template'));

        $response->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=template_master_barang.xlsx');

        $spreadsheet = IOFactory::load($response->getFile()->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();

        $this->assertSame(1, $spreadsheet->getSheetCount());
        $this->assertSame('Master Barang', $worksheet->getTitle());
        $this->assertSame(
            'Panduan: isi data mulai baris 3. Header wajib: kode_barang, nama_barang, kategori, satuan. Urutan bebas.',
            $worksheet->getCell('A1')->getValue(),
        );
        $this->assertContains('A1:D1', $worksheet->getMergeCells());
        $this->assertSame(
            ['kode_barang', 'nama_barang', 'kategori', 'satuan'],
            $worksheet->rangeToArray('A2:D2', null, true, false)[0],
        );
    }

    /**
     * @return array{0: User, 1: Gudang}
     */
    private function authorizedImportUser(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permission = Permission::findOrCreate('barang.import', 'web');
        $user = User::factory()->create();
        $warehouse = Gudang::create([
            'kode' => 'IMPORT-WH',
            'nama' => 'Gudang Impor Test',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $user->gudang()->attach($warehouse);
        $user->givePermissionTo($permission);
        $user->syncRoles([Role::findOrCreate('Admin Gudang Store', 'web')]);

        return [$user, $warehouse];
    }

    /** @return array{0: KategoriBarang, 1: Satuan} */
    private function references(Gudang $warehouse): array
    {
        return [
            KategoriBarang::create([
                'gudang_id' => $warehouse->id,
                'nama' => 'Kemasan',
                'is_aktif' => true,
            ]),
            Satuan::create([
                'gudang_id' => $warehouse->id,
                'kode' => 'PCS',
                'nama' => 'PCS',
                'is_aktif' => true,
            ]),
        ];
    }

    private function master(string $code, string $name, KategoriBarang $category, Satuan $unit): MasterBarang
    {
        return MasterBarang::create([
            'kode' => $code,
            'nama' => $name,
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'is_aktif' => true,
        ]);
    }

    private function register(
        MasterBarang $master,
        Gudang $warehouse,
        KategoriBarang $category,
        Satuan $unit,
    ): Barang {
        return Barang::create([
            'master_barang_id' => $master->id,
            'gudang_id' => $warehouse->id,
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
            'kode' => $master->kode,
            'nama' => $master->nama,
            'minimum_stok' => 0,
            'is_aktif' => true,
        ]);
    }

    /** @return array{line: int, kode_barang: string, nama_barang: string, kategori: string, satuan: string, deskripsi: string} */
    private function row(
        int $line,
        string $code,
        string $name,
        string $category = 'Kemasan',
        string $unit = 'PCS',
    ): array {
        return [
            'line' => $line,
            'kode_barang' => $code,
            'nama_barang' => $name,
            'kategori' => $category,
            'satuan' => $unit,
            'deskripsi' => '',
        ];
    }

    /**
     * @param  list<list<string>>  $rows
     * @return array{path: string, upload: UploadedFile}
     */
    private function excelUpload(
        array $rows,
        string $sheetName = 'Master Barang',
        bool $withInstructionRow = false,
    ): array {
        $spreadsheet = new Spreadsheet;
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle($sheetName);

        if ($withInstructionRow) {
            $worksheet->fromArray([['Panduan pengisian data barang.']], null, 'A1');
            $worksheet->fromArray($rows, null, 'A2');
        } else {
            $worksheet->fromArray($rows, null, 'A1');
        }

        $path = tempnam(sys_get_temp_dir(), 'master-barang-import-');
        (new Xlsx($spreadsheet))->save($path);

        return [
            'path' => $path,
            'upload' => new UploadedFile(
                $path,
                'master-barang.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            ),
        ];
    }
}
