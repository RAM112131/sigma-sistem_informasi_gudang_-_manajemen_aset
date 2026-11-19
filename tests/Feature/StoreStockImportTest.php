<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\Satuan;
use App\Models\StokAwal;
use App\Models\User;
use App\Services\Store\StokService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class StoreStockImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_stock_import_page_renders_the_excel_paste_grid(): void
    {
        [$user, $store] = $this->storeImportUser();
        $user->givePermissionTo(Permission::findOrCreate('store.view', 'web'));

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $store->id])
            ->get(route('store.stok.index', ['tab' => 'import_data_awal']));

        $response->assertOk()
            ->assertSee('Tempel dari Excel')
            ->assertSee('aria-label="Grid tempel stok awal"', false)
            ->assertSee('scheduleGridValidation()', false)
            ->assertSeeText('Data divalidasi otomatis')
            ->assertSee('Simpan')
            ->assertSee('File Excel/CSV dipetakan otomatis ke grid')
            ->assertSee('Pilih sheet data:')
            ->assertSee('@input="scheduleGridValidation()"', false)
            ->assertDontSee('Grid &amp; Validasi')
            ->assertDontSee('Konfigurasi Kolom')
            ->assertDontSee('Atur sheet &amp; mapping manual');
    }

    public function test_import_analysis_reports_valid_new_item_and_existing_stock_conflict(): void
    {
        [$user, $store] = $this->storeImportUser();
        $satuan = Satuan::create(['kode' => 'PCS', 'nama' => 'Pieces', 'is_aktif' => true]);
        $existingBarang = Barang::create([
            'gudang_id' => $store->id,
            'satuan_id' => $satuan->id,
            'kode' => 'OLD-001',
            'nama' => 'Barang Lama',
            'is_aktif' => true,
        ]);
        StokAwal::create([
            'gudang_id' => $store->id,
            'barang_id' => $existingBarang->id,
            'qty' => 10,
            'tanggal_acuan' => '2026-10-01',
            'sumber' => 'manual',
            'diinput_oleh' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $store->id])
            ->postJson(route('store.stok.import-analyze'), [
                'rows' => [
                    [
                        'row' => 1,
                        'kode_barang' => 'NEW-001',
                        'nama_barang' => 'Barang Baru',
                        'kategori' => 'Kemasan',
                        'satuan' => 'pcs',
                        'qty' => '4',
                        'catatan' => '',
                    ],
                    [
                        'row' => 2,
                        'kode_barang' => 'OLD-001',
                        'nama_barang' => 'Barang Lama',
                        'kategori' => 'Kemasan',
                        'satuan' => 'pcs',
                        'qty' => '10',
                        'catatan' => '',
                    ],
                    [
                        'row' => 3,
                        'kode_barang' => 'NEW-001',
                        'nama_barang' => 'Barang Baru',
                        'kategori' => 'Kemasan',
                        'satuan' => 'pcs',
                        'qty' => '4',
                        'catatan' => '',
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('summary.total', 3)
            ->assertJsonPath('summary.ok', 1)
            ->assertJsonPath('summary.error', 2)
            ->assertJsonPath('summary.new_barang', 1)
            ->assertJsonPath('rows.0.status', 'ok')
            ->assertJsonPath('rows.0.is_new_barang', true)
            ->assertJsonPath('rows.1.stok_awal_exists', true)
            ->assertJsonPath('rows.1.issues.0', 'Stok awal untuk barang ini sudah pernah tercatat di gudang Store.')
            ->assertJsonPath('rows.2.issues.0', "Duplikat di file: Kode 'NEW-001' sudah ada di baris 1");
        $this->assertArrayNotHasKey('lokasi_rak', $response->json('rows.0'));
        $this->assertDatabaseCount('barangs', 1);
    }

    public function test_grid_analysis_marks_fractional_quantities_as_row_errors(): void
    {
        [$user, $store] = $this->storeImportUser();

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $store->id])
            ->postJson(route('store.stok.import-analyze'), [
                'rows' => [[
                    'row' => 7,
                    'kode_barang' => 'NEW-001',
                    'nama_barang' => 'Barang Baru',
                    'kategori' => 'Kemasan',
                    'satuan' => 'pcs',
                    'qty' => '1.5',
                    'catatan' => '',
                ]],
            ]);

        $response->assertOk()
            ->assertJsonPath('summary.error', 1)
            ->assertJsonPath('rows.0.row', 7)
            ->assertJsonPath('rows.0.status', 'error')
            ->assertJsonPath('rows.0.issues.0', 'qty harus berupa bilangan bulat');
        $this->assertDatabaseCount('barangs', 0);
    }

    public function test_import_request_processes_without_dispatching_a_queue_job(): void
    {
        Queue::fake();
        [$user, $store] = $this->storeImportUser();

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $store->id])
            ->postJson(route('store.stok.import-process'), [
                'tanggal_acuan' => '2026-10-01',
                'catatan_umum' => 'Stok awal',
                'rows' => [[
                    'kode_barang' => 'NEW-001',
                    'nama_barang' => 'Barang Baru',
                    'kategori' => 'Kemasan',
                    'satuan' => 'pcs',
                    'qty' => 4,
                    'catatan' => '',
                ]],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('queued', false)
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('created_count', 1);

        Queue::assertNothingPushed();
        $this->assertDatabaseHas('stok_awals', [
            'gudang_id' => $store->id,
            'qty' => 4,
            'sumber' => 'import',
        ]);
    }

    public function test_synchronous_import_keeps_the_completed_status(): void
    {
        [$user, $store] = $this->storeImportUser();

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $store->id])
            ->postJson(route('store.stok.import-process'), [
                'tanggal_acuan' => '2026-10-01',
                'rows' => [[
                    'kode_barang' => 'NEW-001',
                    'nama_barang' => 'Barang Baru',
                    'kategori' => 'Kemasan',
                    'satuan' => 'pcs',
                    'qty' => 4,
                    'catatan' => '',
                ]],
            ]);

        $response->assertOk()
            ->assertJsonPath('success_count', 1)
            ->assertJsonPath('created_count', 1)
            ->assertJsonPath('skipped_count', 0);
        $this->assertDatabaseHas('barangs', [
            'gudang_id' => $store->id,
            'kode' => 'NEW-001',
            'nama' => 'Barang Baru',
        ]);
        $this->assertNotNull(Barang::query()->where('kode', 'NEW-001')->firstOrFail()->master_barang_id);
        $this->assertDatabaseHas('stok_awals', [
            'gudang_id' => $store->id,
            'qty' => 4,
            'catatan' => 'Import Data Awal',
            'sumber' => 'import',
        ]);
    }

    public function test_import_processing_persists_valid_rows_and_reports_duplicate_stock_rows(): void
    {
        [$user, $store] = $this->storeImportUser();
        $satuan = Satuan::create(['kode' => 'PCS', 'nama' => 'Pieces', 'is_aktif' => true]);
        $existingBarang = Barang::create([
            'gudang_id' => $store->id,
            'satuan_id' => $satuan->id,
            'kode' => 'OLD-001',
            'nama' => 'Barang Lama',
            'is_aktif' => true,
        ]);
        StokAwal::create([
            'gudang_id' => $store->id,
            'barang_id' => $existingBarang->id,
            'qty' => 10,
            'tanggal_acuan' => '2026-10-01',
            'sumber' => 'manual',
            'diinput_oleh' => $user->id,
        ]);

        $result = app(StokService::class)->importRows([
            [
                'kode_barang' => 'NEW-001',
                'nama_barang' => 'Barang Baru',
                'kategori' => 'Kemasan',
                'satuan' => 'pcs',
                'qty' => 4,
                'catatan' => '',
            ],
            [
                'kode_barang' => 'OLD-001',
                'nama_barang' => 'Barang Lama',
                'kategori' => 'Kemasan',
                'satuan' => 'pcs',
                'qty' => 10,
                'catatan' => '',
            ],
        ], $store->id, $user->id, '2026-10-01', 'Stok awal');

        $this->assertSame(1, $result['success_count']);
        $this->assertSame(1, $result['created_count']);
        $this->assertSame(0, $result['skipped_count']);
        $this->assertSame(1, count($result['error_rows']));
        $this->assertSame(2, $result['error_rows'][0]['row']);
        $this->assertSame('OLD-001', $result['error_rows'][0]['kode']);
        $this->assertSame(2, Barang::query()->where('gudang_id', $store->id)->count());
        $this->assertDatabaseHas('stok_awals', [
            'gudang_id' => $store->id,
            'qty' => 4,
            'catatan' => 'Stok awal',
            'sumber' => 'import',
            'diinput_oleh' => $user->id,
        ]);
        $this->assertSame(2, StokAwal::query()->where('gudang_id', $store->id)->count());
    }

    public function test_import_processing_endpoint_rejects_invalid_payload_without_queueing(): void
    {
        Queue::fake();
        [$user, $store] = $this->storeImportUser();

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $store->id])
            ->postJson(route('store.stok.import-process'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tanggal_acuan', 'rows']);

        Queue::assertNothingPushed();
    }

    /**
     * @return array{User, Gudang}
     */
    private function storeImportUser(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permission = Permission::findOrCreate('stok_awal.import', 'web');
        $role = Role::findOrCreate('Admin Gudang Store', 'web');
        $role->givePermissionTo($permission);

        $user = User::withoutEvents(fn (): User => User::create([
            'name' => 'Admin Store',
            'username' => 'admin-store',
            'password' => 'password',
            'is_aktif' => true,
        ]));
        $user->assignRole($role);

        $store = Gudang::create([
            'kode' => 'STORE',
            'nama' => 'Gudang Store',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $user->gudang()->attach($store);

        return [$user, $store];
    }
}
