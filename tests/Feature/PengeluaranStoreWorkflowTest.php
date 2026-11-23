<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\PermintaanBarang;
use App\Models\PermintaanBarangDetail;
use App\Models\Satuan;
use App\Models\StokAwal;
use App\Models\TransferBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class PengeluaranStoreWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_allocation_form_is_rendered_on_transfer_page_and_scoped_to_the_active_store(): void
    {
        [$user, $store, $unit, $barang, $satuan] = $this->storeUserAndCatalog();
        $request = $this->createRequest($store, $unit, $user, $barang, $satuan);
        foreach (range(2, 5) as $itemNumber) {
            $additionalItem = Barang::create([
                'kode' => "ITEM-{$itemNumber}",
                'nama' => "Barang Uji {$itemNumber}",
                'satuan_id' => $satuan->id,
                'is_aktif' => true,
            ]);
            PermintaanBarangDetail::create([
                'permintaan_barang_id' => $request->id,
                'barang_id' => $additionalItem->id,
                'qty_diminta' => 2,
                'qty_diproses' => 0,
                'satuan_id' => $satuan->id,
            ]);
        }

        $this->actingAs($user)
            ->get('/store/permintaan')
            ->assertOk()
            ->assertSee('Proses Pengeluaran (Otomatis Isi)')
            ->assertSee(route('store.pengeluaran.index', ['tab' => 'pemindahan', 'permintaan_id' => $request->id]))
            ->assertDontSee('"qty_diproses":');

        $this->actingAs($user)
            ->get("/store/pengeluaran?tab=pemindahan&permintaan_id={$request->id}")
            ->assertOk()
            ->assertSee('Rincian Barang yang Dikeluarkan')
            ->assertSee($request->nomor_pr)
            ->assertSee('name="qty_dialokasikan['.$request->details->first()->id.']"', false)
            ->assertSee('Barang Uji 5')
            ->assertDontSee('modalProsesOpen');

        $otherStore = Gudang::create([
            'kode' => 'STORE-OTHER',
            'nama' => 'Gudang Store Lain',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $otherRequest = $this->createRequest($otherStore, $unit, $user, $barang, $satuan);

        $this->actingAs($user)
            ->get("/store/pengeluaran?tab=pemindahan&permintaan_id={$otherRequest->id}")
            ->assertNotFound();
    }

    public function test_dispatch_does_not_complete_request_before_unit_receipt_confirmation(): void
    {
        [$user, $store, $unit, $barang, $satuan] = $this->storeUserAndCatalog();
        $request = $this->createRequest($store, $unit, $user, $barang, $satuan);
        StokAwal::create([
            'gudang_id' => $store->id,
            'barang_id' => $barang->id,
            'qty' => 10,
            'tanggal_acuan' => now()->toDateString(),
            'sumber' => 'manual',
            'diinput_oleh' => $user->id,
        ]);

        $this->actingAs($user)
            ->post("/store/pengeluaran/permintaan/{$request->id}/proses", [
                'qty_dialokasikan' => [$request->details->first()->id => 5],
            ])
            ->assertRedirect(route('store.pengeluaran.index', ['tab' => 'histori']));

        $transfer = TransferBarang::query()->sole();
        $this->assertSame('diproses', $request->fresh()->status);
        $this->assertSame('dikirim', $transfer->status);
        $this->assertSame(5, $transfer->qty_dikirim);
        $this->assertNotNull($transfer->dikirim_at);

        $this->get(route('store.pengeluaran.index', ['tab' => 'histori']))
            ->assertOk()
            ->assertSee('Dikirim');

        $this->get(route('store.pengeluaran.barang-search', ['q' => 'ITEM']))
            ->assertOk()
            ->assertJsonPath('data.0.stok_tersedia', 5);
    }

    public function test_partial_dispatch_releases_unshipped_allocation_back_to_request(): void
    {
        [$user, $store, $unit, $barang, $satuan] = $this->storeUserAndCatalog();
        $request = $this->createRequest($store, $unit, $user, $barang, $satuan);
        StokAwal::create([
            'gudang_id' => $store->id,
            'barang_id' => $barang->id,
            'qty' => 10,
            'tanggal_acuan' => now()->toDateString(),
            'sumber' => 'manual',
            'diinput_oleh' => $user->id,
        ]);

        $this->post("/store/pengeluaran/permintaan/{$request->id}/proses", [
            'qty_dialokasikan' => [$request->details->first()->id => 3],
        ])->assertRedirect(route('store.pengeluaran.index', ['tab' => 'histori']));

        $transfer = TransferBarang::query()->sole();
        $this->assertSame('dikirim', $transfer->fresh()->status);
        $this->assertSame(3, $transfer->fresh()->qty_dikirim);
        $this->assertSame(3, $request->details->first()->fresh()->qty_diproses);
        $this->assertSame('diproses', $request->fresh()->status);
        $this->get(route('store.pengeluaran.barang-search', ['q' => 'ITEM']))
            ->assertOk()
            ->assertJsonPath('data.0.stok_tersedia', 7);
    }

    public function test_store_can_create_direct_transfer_and_search_reflects_the_reservation(): void
    {
        [$user, $store, $unit, $barang, $satuan] = $this->storeUserAndCatalog();
        StokAwal::create([
            'gudang_id' => $store->id,
            'barang_id' => $barang->id,
            'qty' => 10,
            'tanggal_acuan' => now()->toDateString(),
            'sumber' => 'manual',
            'diinput_oleh' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('store.pengeluaran.index', ['tab' => 'pemindahan']))
            ->assertOk()
            ->assertSee('Pemindahan tanpa Permintaan')
            ->assertSee('Cari nama/kode barang');

        $this->actingAs($user)
            ->post(route('store.pengeluaran.pemindahan.store'), [
                'gudang_tujuan_id' => $unit->id,
                'items' => [
                    ['barang_id' => $barang->id, 'qty' => 4],
                ],
            ])
            ->assertRedirect(route('store.pengeluaran.index', ['tab' => 'histori']));

        $transfer = TransferBarang::query()->sole();
        $this->assertNull($transfer->permintaan_barang_id);
        $this->assertSame((int) $store->id, (int) $transfer->gudang_asal_id);
        $this->assertSame((int) $unit->id, (int) $transfer->gudang_tujuan_id);
        $this->assertSame(4, $transfer->qty_diminta);
        $this->assertSame('dikirim', $transfer->status);
        $this->assertSame(4, $transfer->qty_dikirim);

        $this->get(route('store.pengeluaran.barang-search', ['q' => 'ITEM']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $barang->id)
            ->assertJsonPath('data.0.stok_tersedia', 6);

        $this->get(route('store.pengeluaran.index', ['tab' => 'histori']))
            ->assertOk()
            ->assertSee('Langsung')
            ->assertSee(preg_replace('/-\d+$/', '', (string) $transfer->nomor_sj));
    }

    public function test_direct_transfer_rejects_invalid_destination_and_quantity_above_available_stock(): void
    {
        [$user, $store, $unit, $barang] = $this->storeUserAndCatalog();
        StokAwal::create([
            'gudang_id' => $store->id,
            'barang_id' => $barang->id,
            'qty' => 5,
            'tanggal_acuan' => now()->toDateString(),
            'sumber' => 'manual',
            'diinput_oleh' => $user->id,
        ]);
        $otherStore = Gudang::create([
            'kode' => 'STORE-OTHER',
            'nama' => 'Gudang Store Lain',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);

        $this->actingAs($user)
            ->post(route('store.pengeluaran.pemindahan.store'), [
                'gudang_tujuan_id' => $otherStore->id,
                'items' => [
                    ['barang_id' => $barang->id, 'qty' => 1],
                ],
            ])
            ->assertSessionHasErrors('gudang_tujuan_id');

        $this->post(route('store.pengeluaran.pemindahan.store'), [
            'gudang_tujuan_id' => $unit->id,
            'items' => [
                ['barang_id' => $barang->id, 'qty' => 6],
            ],
        ])->assertSessionHasErrors('items');

        $this->assertDatabaseCount('transfer_barangs', 0);
    }

    public function test_direct_transfer_can_be_created_without_destination_unit_for_personal_request(): void
    {
        [$user, $store, $unit, $barang] = $this->storeUserAndCatalog();
        StokAwal::create([
            'gudang_id' => $store->id,
            'barang_id' => $barang->id,
            'qty' => 10,
            'tanggal_acuan' => now()->toDateString(),
            'sumber' => 'manual',
            'diinput_oleh' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('store.pengeluaran.pemindahan.store'), [
                'pemohon' => 'Karyawan Perorangan',
                'petugas' => 'Petugas Store',
                'items' => [
                    ['barang_id' => $barang->id, 'qty' => 2],
                ],
            ])
            ->assertRedirect(route('store.pengeluaran.index', ['tab' => 'histori']));

        $transfer = TransferBarang::query()->sole();
        $this->assertNull($transfer->gudang_tujuan_id);
        $this->assertSame((int) $store->id, (int) $transfer->gudang_asal_id);
        $this->assertSame(2, $transfer->qty_diminta);
    }

    public function test_direct_transfer_resolves_destination_by_unit_name(): void
    {
        [$user, $store, $unit, $barang] = $this->storeUserAndCatalog();
        StokAwal::create([
            'gudang_id' => $store->id,
            'barang_id' => $barang->id,
            'qty' => 10,
            'tanggal_acuan' => now()->toDateString(),
            'sumber' => 'manual',
            'diinput_oleh' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('store.pengeluaran.pemindahan.store'), [
                'unit_peminta_teks' => $unit->nama,
                'items' => [
                    ['barang_id' => $barang->id, 'qty' => 2],
                ],
            ])
            ->assertRedirect(route('store.pengeluaran.index', ['tab' => 'histori']));

        $transfer = TransferBarang::query()->sole();
        $this->assertSame((int) $unit->id, (int) $transfer->gudang_tujuan_id);
        $this->assertSame(2, $transfer->qty_diminta);
    }

    public function test_dispatch_and_allocation_flash_print_sj_for_automatic_printing(): void
    {
        [$user, $store, $unit, $barang, $satuan] = $this->storeUserAndCatalog();
        StokAwal::create([
            'gudang_id' => $store->id,
            'barang_id' => $barang->id,
            'qty' => 10,
            'tanggal_acuan' => now()->toDateString(),
            'sumber' => 'manual',
            'diinput_oleh' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->post(route('store.pengeluaran.pemindahan.store'), [
                'pemohon' => 'Karyawan Test',
                'petugas' => 'Petugas Store',
                'items' => [
                    ['barang_id' => $barang->id, 'qty' => 3],
                ],
            ]);

        $response->assertSessionHas('print_sj');
        $sjNomor = session('print_sj');
        $this->assertNotEmpty($sjNomor);

        // Akses halaman dengan session print_sj harus menyiapkan printDoc
        $viewResponse = $this->actingAs($user)
            ->withSession(['print_sj' => $sjNomor])
            ->get(route('store.pengeluaran.index', ['tab' => 'pemindahan']));

        $viewResponse->assertOk();
        $viewResponse->assertViewHas('printDoc');
        $printDoc = $viewResponse->viewData('printDoc');
        $this->assertNotNull($printDoc);
        $this->assertSame($sjNomor, $printDoc['nomorKeluar']);
        $this->assertCount(1, $printDoc['items']);
        $this->assertSame(3, $printDoc['items'][0]['qty']);
    }

    public function test_histori_pengeluaran_uses_the_same_card_layout_as_permintaan(): void
    {
        [$user, $store, $unit, $barang] = $this->storeUserAndCatalog();
        StokAwal::create([
            'gudang_id' => $store->id,
            'barang_id' => $barang->id,
            'qty' => 10,
            'tanggal_acuan' => now()->toDateString(),
            'sumber' => 'manual',
            'diinput_oleh' => $user->id,
        ]);

        $this->post(route('store.pengeluaran.pemindahan.store'), [
            'gudang_tujuan_id' => $unit->id,
            'items' => [
                ['barang_id' => $barang->id, 'qty' => 4],
            ],
        ])->assertRedirect(route('store.pengeluaran.index', ['tab' => 'histori']));

        $transfer = TransferBarang::query()->sole();

        $this->get(route('store.pengeluaran.index', ['tab' => 'histori']))
            ->assertOk()
            ->assertViewIs('store.pengeluaran.histori')
            ->assertSee('expense-history-card', false)
            ->assertDontSee('Dokumen Pengeluaran')
            ->assertSee('Detail Barang')
            ->assertSee('Cetak Surat Jalan')
            ->assertSee(preg_replace('/-\d+$/', '', (string) $transfer->nomor_sj));
    }

    /**
     * @return array{User, Gudang, Gudang, Barang, Satuan}
     */
    private function storeUserAndCatalog(): array
    {
        $permission = Permission::create(['name' => 'store.distribusi', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Admin Gudang Store', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $user = User::withoutEvents(fn () => User::create([
            'name' => 'Admin Store',
            'username' => 'admin-store',
            'password' => 'password',
            'is_aktif' => true,
        ]));
        $user->assignRole($role);
        $this->actingAs($user);

        $store = Gudang::create([
            'kode' => 'STORE',
            'nama' => 'Gudang Store',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $unit = Gudang::create([
            'kode' => 'UNIT',
            'nama' => 'Gudang Unit',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $user->gudang()->attach($store);

        $satuan = Satuan::create(['kode' => 'PCS', 'nama' => 'pcs', 'is_aktif' => true]);
        $barang = Barang::create([
            'kode' => 'ITEM-1',
            'nama' => 'Barang Uji',
            'satuan_id' => $satuan->id,
            'is_aktif' => true,
        ]);

        return [$user, $store, $unit, $barang, $satuan];
    }

    private function createRequest(
        Gudang $store,
        Gudang $unit,
        User $user,
        Barang $barang,
        Satuan $satuan,
    ): PermintaanBarang {
        $request = PermintaanBarang::create([
            'nomor_pr' => 'PR-'.uniqid(),
            'gudang_peminta_id' => $unit->id,
            'gudang_tujuan_id' => $store->id,
            'status' => 'baru',
            'dibuat_oleh' => $user->id,
        ]);
        PermintaanBarangDetail::create([
            'permintaan_barang_id' => $request->id,
            'barang_id' => $barang->id,
            'qty_diminta' => 5,
            'qty_diproses' => 0,
            'satuan_id' => $satuan->id,
        ]);

        return $request->load('details');
    }
}
