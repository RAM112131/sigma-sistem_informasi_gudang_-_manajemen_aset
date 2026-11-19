<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\PenerimaanBarangStore;
use App\Models\PermintaanBarang;
use App\Models\StokAwal;
use App\Models\TransferBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class StoreDashboardIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_widgets_are_scoped_to_the_authorized_active_store(): void
    {
        [$user, $activeStore, $otherStore] = $this->storeUserWithTwoStores();
        $activeUnit = $this->createGudang('UNIT-ACTIVE', 'Unit Active', 'gudang_umum');
        $otherUnit = $this->createGudang('UNIT-OTHER', 'Unit Other', 'gudang_umum');
        $activeBarang = $this->createBarang('ACTIVE-ITEM', 'Active Store Item', $activeStore);
        $safeBarang = $this->createBarang('SAFE-ITEM', 'Safe Store Item', $activeStore);
        $otherBarang = $this->createBarang('OTHER-ITEM', 'Other Store Item', $otherStore);
        $globalBarang = $this->createBarang('GLOBAL-ITEM', 'Global Item', null);
        StokAwal::create([
            'gudang_id' => $activeStore->id,
            'barang_id' => $safeBarang->id,
            'qty' => 10,
            'tanggal_acuan' => '2026-09-01',
            'sumber' => 'manual',
        ]);

        $activeRequest = $this->createRequest('PR-ACTIVE', $activeUnit, $activeStore, $user);
        $this->createRequest('PR-OTHER', $otherUnit, $otherStore, $user);
        $activeTransfer = $this->createTransfer('SJ-ACTIVE', $activeStore, $activeUnit, $activeBarang);
        $this->createTransfer('SJ-OTHER', $otherStore, $otherUnit, $otherBarang);
        $this->createReceipt('RCV-ACTIVE', $activeStore, $activeBarang);
        $this->createReceipt('RCV-OTHER', $otherStore, $otherBarang);

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $activeStore->id])
            ->get(route('store.dashboard'));

        $response->assertOk();
        $response->assertSee('data-turbo-track="reload"', false);
        $response->assertViewHas('gudangStore', fn (Gudang $gudang): bool => $gudang->is($activeStore));
        $response->assertViewHas(
            'stokKritis',
            fn ($barangs): bool => $barangs->modelKeys() === [$activeBarang->id]
                && ! $barangs->contains('id', $otherBarang->id)
                && ! $barangs->contains('id', $globalBarang->id)
        );
        $response->assertViewHas('jumlahStokKritis', 1);
        $response->assertSee('Stok Saat Ini');
        $response->assertDontSee('Terima Barang');
        $response->assertDontSee('Proses Permintaan');
        $response->assertDontSee('Proses &rarr;');
        $response->assertDontSee('+ Tambah Stok');
        $response->assertViewHas(
            'permintaanBelumDiproses',
            fn ($requests): bool => $requests->modelKeys() === [$activeRequest->id]
        );
        $response->assertViewHas(
            'pemindahanBerjalan',
            fn ($transfers): bool => $transfers->modelKeys() === [$activeTransfer->id]
        );
        $response->assertViewHas('totalPenerimaanHariIni', 1);
        $response->assertViewHas('totalPemindahanHariIni', 1);
        $this->assertSame($activeStore->id, session('active_gudang_id'));
    }

    public function test_dashboard_limits_widget_rows_and_keeps_total_counts(): void
    {
        [$user, $activeStore] = $this->storeUserWithTwoStores();
        $unit = $this->createGudang('UNIT-LIMIT', 'Unit Limit', 'gudang_umum');
        $barang = $this->createBarang('LIMIT-ITEM', 'Limit Item', $activeStore);
        $requestIds = [];
        $transferIds = [];

        foreach (range(1, 7) as $number) {
            $createdAt = now()->addSeconds($number);
            $request = $this->createRequest(sprintf('PR-LIMIT-%02d', $number), $unit, $activeStore, $user);
            $request->forceFill(['created_at' => $createdAt])->save();
            $requestIds[] = $request->id;

            $transfer = $this->createTransfer(sprintf('SJ-LIMIT-%02d', $number), $activeStore, $unit, $barang);
            $transfer->forceFill(['dikirim_at' => $createdAt])->save();
            $transferIds[] = $transfer->id;
        }

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $activeStore->id])
            ->get(route('store.dashboard'));

        $response->assertOk();
        $response->assertViewHas('permintaanBelumDiproses', fn ($requests): bool => $requests->modelKeys()
            === array_reverse(array_slice($requestIds, -5)));
        $response->assertViewHas('jumlahPermintaanBelumDiproses', 7);
        $response->assertViewHas('pemindahanBerjalan', fn ($transfers): bool => $transfers->modelKeys()
            === array_reverse(array_slice($transferIds, -5)));
        $response->assertViewHas('jumlahPemindahanBerjalan', 7);
    }

    public function test_dashboard_shows_mutation_actions_to_users_with_create_permissions(): void
    {
        [$user, $activeStore] = $this->storeUserWithTwoStores();
        $user->givePermissionTo(
            Permission::create(['name' => 'store_penerimaan.create', 'guard_name' => 'web']),
            Permission::create(['name' => 'store_pengeluaran.create', 'guard_name' => 'web']),
        );

        $response = $this->actingAs($user)
            ->withSession(['active_gudang_id' => $activeStore->id])
            ->get(route('store.dashboard'));

        $response->assertOk()
            ->assertSee('Terima Barang')
            ->assertSee('Proses Permintaan');
    }

    public function test_dashboard_rejects_a_store_not_assigned_to_the_user(): void
    {
        [$user] = $this->storeUserWithTwoStores();
        $unassignedStore = $this->createGudang('STORE-UNASSIGNED', 'Store Unassigned', 'gudang_store');

        $this->actingAs($user)
            ->withSession(['active_gudang_id' => $unassignedStore->id])
            ->get(route('store.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_requires_store_view_permission(): void
    {
        $user = User::withoutEvents(fn (): User => User::create([
            'name' => 'User Tanpa Izin Store',
            'username' => 'no-store-dashboard',
            'password' => 'password',
            'is_aktif' => true,
        ]));

        $this->actingAs($user)
            ->get(route('store.dashboard'))
            ->assertForbidden();
    }

    /**
     * @return array{User, Gudang, Gudang}
     */
    private function storeUserWithTwoStores(): array
    {
        $permission = Permission::create(['name' => 'store.view', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Admin Store Dashboard', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $user = User::withoutEvents(fn (): User => User::create([
            'name' => 'Admin Store Dashboard',
            'username' => 'admin-store-dashboard',
            'password' => 'password',
            'is_aktif' => true,
        ]));
        $user->assignRole($role);

        $activeStore = $this->createGudang('STORE-ACTIVE', 'Store Active', 'gudang_store');
        $otherStore = $this->createGudang('STORE-OTHER', 'Store Other', 'gudang_store');
        $user->gudang()->attach([$activeStore->id, $otherStore->id]);

        return [$user, $activeStore, $otherStore];
    }

    private function createGudang(string $kode, string $nama, string $jenis): Gudang
    {
        return Gudang::create([
            'kode' => $kode,
            'nama' => $nama,
            'jenis' => $jenis,
            'is_aktif' => true,
        ]);
    }

    private function createBarang(string $kode, string $nama, ?Gudang $gudang): Barang
    {
        return Barang::create([
            'gudang_id' => $gudang?->id,
            'kode' => $kode,
            'nama' => $nama,
            'minimum_stok' => 5,
            'is_aktif' => true,
        ]);
    }

    private function createRequest(string $nomor, Gudang $peminta, Gudang $store, User $user): PermintaanBarang
    {
        return PermintaanBarang::create([
            'nomor_pr' => $nomor,
            'gudang_peminta_id' => $peminta->id,
            'gudang_tujuan_id' => $store->id,
            'status' => 'baru',
            'dibuat_oleh' => $user->id,
        ]);
    }

    private function createTransfer(string $nomor, Gudang $store, Gudang $tujuan, Barang $barang): TransferBarang
    {
        return TransferBarang::create([
            'nomor_sj' => $nomor,
            'gudang_asal_id' => $store->id,
            'gudang_tujuan_id' => $tujuan->id,
            'barang_id' => $barang->id,
            'qty_diminta' => 1,
            'qty_dikirim' => 1,
            'status' => 'dikirim',
            'dikirim_at' => now(),
        ]);
    }

    private function createReceipt(string $nomor, Gudang $store, Barang $barang): PenerimaanBarangStore
    {
        return PenerimaanBarangStore::create([
            'nomor_penerimaan' => $nomor,
            'gudang_id' => $store->id,
            'barang_id' => $barang->id,
            'qty_diterima' => 1,
            'tanggal_diterima' => now(),
        ]);
    }
}
