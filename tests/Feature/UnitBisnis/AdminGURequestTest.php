<?php

declare(strict_types=1);

namespace Tests\Feature\UnitBisnis;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\PermintaanBarang;
use App\Models\StokAwal;
use App\Models\TransferBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AdminGURequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_gu_can_create_a_request_using_items_from_the_store_catalog(): void
    {
        [$user, $unit, $store, $barang] = $this->makeAdminContext();

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $unit->id])
            ->post(route('unit-bisnis.admin-gu.permintaan.store'), [
                'catatan' => 'Kebutuhan operasional',
                'items' => [
                    ['barang_id' => $barang->id, 'qty_diminta' => 12, 'catatan' => 'Prioritas'],
                ],
            ])
            ->assertRedirect(route('unit-bisnis.admin-gu.permintaan.index', ['tab' => 'riwayat_permintaan']));

        $this->assertDatabaseHas('permintaan_barangs', [
            'gudang_peminta_id' => $unit->id,
            'gudang_tujuan_id' => $store->id,
            'status' => 'baru',
            'catatan' => 'Kebutuhan operasional',
            'dibuat_oleh' => $user->id,
        ]);
        $this->assertDatabaseHas('permintaan_barang_details', [
            'barang_id' => $barang->id,
            'qty_diminta' => 12,
            'qty_diproses' => 0,
            'satuan_id' => $barang->satuan_id,
            'catatan' => 'Prioritas',
        ]);
    }

    public function test_request_listing_is_limited_to_the_active_assigned_warehouse(): void
    {
        [$user, $unit, $store, $barang] = $this->makeAdminContext();
        $otherUnit = $this->makeWarehouse('UNIT-OTHER', 'Gudang Unit Lain', 'gudang_umum');

        $this->makeRequest($unit, $store, $user, 'REQ-VISIBLE');
        $this->makeRequest($otherUnit, $store, $user, 'REQ-HIDDEN');

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $unit->id])
            ->get(route('unit-bisnis.admin-gu.permintaan.index'));

        $response
            ->assertOk()
            ->assertViewIs('unit-bisnis.admin-gudang-umum.permintaan.index')
            ->assertSee('Permintaan Barang')
            ->assertSee(route('unit-bisnis.admin-gu.permintaan.index'))
            ->assertSee('barang-search')
            ->assertSee('Cari nama/kode barang')
            ->assertSee('role="combobox"', false)
            ->assertSee('role="listbox"', false)
            ->assertSee('x-teleport="body"', false)
            ->assertSee('@scroll.window.capture="updateSearchPosition()"', false)
            ->assertSee("position: 'fixed'", false)
            ->assertSee('x-model="items[0].query"', false)
            ->assertSee('name="items[0][barang_id]"', false)
            ->assertSee('encodeURIComponent(query)')
            ->assertSee('item.searchTimer = setTimeout(() => this.searchBarang(index), 200)', false)
            ->assertSee('Sedang mencari barang...')
            ->assertSee('String.fromCharCode(38) + entities[character]')
            ->assertSee('highlightBarangMatch(barang.kode, items[activeItemIndex].query)', false)
            ->assertSee('highlightBarangMatch(barang.nama, items[activeItemIndex].query)', false)
            ->assertSee("class='gu-request-match'", false)
            ->assertSee('REQ-VISIBLE')
            ->assertDontSee('REQ-HIDDEN');

        $previousLibxmlErrorSetting = libxml_use_internal_errors(true);

        try {
            $document = new \DOMDocument;
            $document->loadHTML($response->getContent());

            $this->assertStringNotContainsString('const matcher = new RegExp', $document->textContent);
            $this->assertStringNotContainsString('@scroll.window.capture=', $document->textContent);
            $this->assertStringNotContainsString('@resize.window=', $document->textContent);
            $this->assertStringNotContainsString('@click.window=', $document->textContent);

        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousLibxmlErrorSetting);
        }

        $this->assertDatabaseHas('barangs', ['id' => $barang->id]);
    }

    public function test_admin_gu_barang_search_returns_store_catalog_and_available_stock(): void
    {
        [$user, $unit, $store, $barang] = $this->makeAdminContext();
        $numericBarang = Barang::create([
            'gudang_id' => $store->id,
            'kode' => '123-ITEM',
            'nama' => 'Barang Numerik',
            'is_aktif' => true,
        ]);
        $similarBarang = Barang::create([
            'gudang_id' => $store->id,
            'kode' => 'STO11040',
            'nama' => 'ASAHAN',
            'is_aktif' => true,
        ]);
        StokAwal::create([
            'gudang_id' => $store->id,
            'barang_id' => $barang->id,
            'qty' => 25,
            'tanggal_acuan' => '2026-10-01',
            'sumber' => 'manual',
        ]);
        TransferBarang::create([
            'nomor_sj' => 'SJ-STORE-RESERVED',
            'gudang_asal_id' => $store->id,
            'gudang_tujuan_id' => $unit->id,
            'barang_id' => $barang->id,
            'qty_diminta' => 6,
            'qty_dikirim' => 0,
            'qty_diterima' => 0,
            'status' => 'diminta',
        ]);

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $unit->id])
            ->getJson(route('unit-bisnis.admin-gu.permintaan.barang-search', ['q' => 'STORE-ITEM']))
            ->assertOk()
            ->assertJsonPath('data.0.kode', 'STORE-ITEM')
            ->assertJsonPath('data.0.nama', 'Barang Store')
            ->assertJsonPath('data.0.stok_tersedia', 19);

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $unit->id])
            ->getJson(route('unit-bisnis.admin-gu.permintaan.barang-search', ['q' => 'asa']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $similarBarang->id)
            ->assertJsonPath('data.0.nama', 'ASAHAN');

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $unit->id])
            ->getJson(route('unit-bisnis.admin-gu.permintaan.barang-search', ['q' => '123']))
            ->assertOk()
            ->assertJsonPath('data.0.id', $numericBarang->id);

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $unit->id])
            ->getJson(route('unit-bisnis.admin-gu.permintaan.barang-search', ['q' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_admin_gu_cannot_request_an_item_outside_the_store_catalog(): void
    {
        [$user, $unit, $store] = $this->makeAdminContext();
        $unitBarang = Barang::create([
            'gudang_id' => $unit->id,
            'kode' => 'UNIT-ONLY-ITEM',
            'nama' => 'Barang khusus unit',
            'is_aktif' => true,
        ]);

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $unit->id])
            ->from(route('unit-bisnis.admin-gu.permintaan.index'))
            ->post(route('unit-bisnis.admin-gu.permintaan.store'), [
                'items' => [
                    ['barang_id' => $unitBarang->id, 'qty_diminta' => 1],
                ],
            ])
            ->assertSessionHasErrors('items');

        $this->assertDatabaseMissing('permintaan_barangs', [
            'gudang_peminta_id' => $unit->id,
            'gudang_tujuan_id' => $store->id,
        ]);
    }

    public function test_recording_role_cannot_open_the_admin_gu_request_menu(): void
    {
        $user = $this->makeUser('recording-request');
        $user->assignRole(Role::create([
            'name' => 'Recording Gudang Umum',
            'guard_name' => 'web',
        ]));

        $this->actingAs($user)
            ->get(route('unit-bisnis.admin-gu.permintaan.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('unit-bisnis.admin-gu.permintaan.barang-search', ['q' => 'barang']))
            ->assertForbidden();
    }

    public function test_request_number_is_displayed_and_increments_sequence_for_subsequent_requests(): void
    {
        [$user, $unit, $store, $barang] = $this->makeAdminContext();

        // 1. Initial page load should display sequence 0001
        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $unit->id])
            ->get(route('unit-bisnis.admin-gu.permintaan.index'));

        $response->assertOk();
        $response->assertSee('No. Permintaan / Request');
        $response->assertSee('-0001');

        // 2. Submit first request
        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $unit->id])
            ->post(route('unit-bisnis.admin-gu.permintaan.store'), [
                'catatan' => 'Permintaan pertama',
                'items' => [
                    ['barang_id' => $barang->id, 'qty_diminta' => 5],
                ],
            ])
            ->assertRedirect();

        $first = PermintaanBarang::firstOrFail();
        $this->assertStringEndsWith('-0001', $first->nomor_pr);

        // 3. Opening page again should show sequence 0002
        $response2 = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $unit->id])
            ->get(route('unit-bisnis.admin-gu.permintaan.index'));

        $response2->assertOk();
        $response2->assertSee('-0002');

        // 4. Submit second request
        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $unit->id])
            ->post(route('unit-bisnis.admin-gu.permintaan.store'), [
                'catatan' => 'Permintaan kedua',
                'items' => [
                    ['barang_id' => $barang->id, 'qty_diminta' => 3],
                ],
            ])
            ->assertRedirect();

        $second = PermintaanBarang::where('id', '>', $first->id)->firstOrFail();
        $this->assertStringEndsWith('-0002', $second->nomor_pr);
    }

    /**
     * @return array{User, Gudang, Gudang, Barang}
     */
    private function makeAdminContext(): array
    {
        $user = $this->makeUser('admin-request');
        $role = Role::create(['name' => 'Admin Gudang Umum', 'guard_name' => 'web']);
        $role->givePermissionTo([
            Permission::findOrCreate('permintaan_barang.read', 'web'),
            Permission::findOrCreate('permintaan_barang.create', 'web'),
        ]);
        $user->assignRole($role);

        $unit = $this->makeWarehouse('UNIT-REQUEST', 'Gudang Unit', 'gudang_umum');
        $store = $this->makeWarehouse('STORE-REQUEST', 'Gudang Store', 'gudang_store');
        $user->gudang()->attach($unit);
        $barang = Barang::create([
            'gudang_id' => $store->id,
            'kode' => 'STORE-ITEM',
            'nama' => 'Barang Store',
            'is_aktif' => true,
        ]);

        return [$user, $unit, $store, $barang];
    }

    private function makeUser(string $username): User
    {
        return User::withoutEvents(static fn (): User => User::create([
            'name' => 'Pengguna '.$username,
            'username' => $username,
            'password' => 'password',
            'is_aktif' => true,
        ]));
    }

    private function makeWarehouse(string $code, string $name, string $type): Gudang
    {
        return Gudang::create([
            'kode' => $code,
            'nama' => $name,
            'jenis' => $type,
            'is_aktif' => true,
        ]);
    }

    private function makeRequest(Gudang $unit, Gudang $store, User $user, string $number): PermintaanBarang
    {
        return PermintaanBarang::create([
            'nomor_pr' => $number,
            'gudang_peminta_id' => $unit->id,
            'gudang_tujuan_id' => $store->id,
            'status' => 'baru',
            'dibuat_oleh' => $user->id,
        ]);
    }
}
