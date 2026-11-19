<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\PermintaanBarang;
use App\Models\PermintaanBarangDetail;
use App\Models\Satuan;
use App\Models\TransferBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class KonfirmasiPemindahanUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_unit_can_confirm_receipt_and_complete_fully_received_request(): void
    {
        [$user, $unit, $barang, $satuan] = $this->unitUserAndCatalog();
        $store = Gudang::create([
            'kode' => 'STORE',
            'nama' => 'Gudang Store',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $permintaan = PermintaanBarang::create([
            'nomor_pr' => 'PR-UNIT-001',
            'gudang_peminta_id' => $unit->id,
            'gudang_tujuan_id' => $store->id,
            'status' => 'diproses',
            'dibuat_oleh' => $user->id,
        ]);
        $detail = PermintaanBarangDetail::create([
            'permintaan_barang_id' => $permintaan->id,
            'barang_id' => $barang->id,
            'qty_diminta' => 5,
            'qty_diproses' => 5,
            'satuan_id' => $satuan->id,
        ]);
        $transfer = $this->sentTransfer($store, $unit, $barang, $permintaan, 5);

        $this->actingAs($user)
            ->get(route('transaksi.pemindahan-unit.index'))
            ->assertOk()
            ->assertSee($transfer->nomor_sj)
            ->assertSee('Periksa & Terima');

        $this->post(route('transaksi.pemindahan-unit.konfirmasi', $transfer), [
            'qty_diterima' => 5,
            'qty_rusak' => 0,
            'catatan_discrepancy' => '',
        ])->assertRedirect(route('transaksi.pemindahan-unit.index'));

        $this->assertSame('diterima', $transfer->fresh()->status);
        $this->assertSame(5, $transfer->fresh()->qty_diterima);
        $this->assertSame('selesai', $permintaan->fresh()->status);
        $this->assertDatabaseHas('penerimaan_pemindahan_units', [
            'transfer_barang_id' => $transfer->id,
            'gudang_id' => $unit->id,
            'barang_id' => $barang->id,
            'qty_dikirim' => 5,
            'qty_diterima' => 5,
            'qty_rusak' => 0,
            'diterima_oleh' => $user->id,
        ]);
        $this->assertSame(5, $detail->fresh()->qty_diproses);
    }

    public function test_short_or_damaged_receipt_requires_note_and_leaves_request_open(): void
    {
        [$user, $unit, $barang, $satuan] = $this->unitUserAndCatalog();
        $store = Gudang::create([
            'kode' => 'STORE',
            'nama' => 'Gudang Store',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $permintaan = PermintaanBarang::create([
            'nomor_pr' => 'PR-UNIT-002',
            'gudang_peminta_id' => $unit->id,
            'gudang_tujuan_id' => $store->id,
            'status' => 'diproses',
            'dibuat_oleh' => $user->id,
        ]);
        PermintaanBarangDetail::create([
            'permintaan_barang_id' => $permintaan->id,
            'barang_id' => $barang->id,
            'qty_diminta' => 5,
            'qty_diproses' => 5,
            'satuan_id' => $satuan->id,
        ]);
        $transfer = $this->sentTransfer($store, $unit, $barang, $permintaan, 5);

        $this->actingAs($user)
            ->post(route('transaksi.pemindahan-unit.konfirmasi', $transfer), [
                'qty_diterima' => 4,
                'qty_rusak' => 1,
                'catatan_discrepancy' => '',
            ])
            ->assertSessionHasErrors('catatan_discrepancy');

        $this->assertSame('dikirim', $transfer->fresh()->status);
        $this->assertDatabaseCount('penerimaan_pemindahan_units', 0);

        $this->post(route('transaksi.pemindahan-unit.konfirmasi', $transfer), [
            'qty_diterima' => 4,
            'qty_rusak' => 1,
            'catatan_discrepancy' => 'Satu unit rusak saat diterima.',
        ])->assertRedirect(route('transaksi.pemindahan-unit.index'));

        $this->assertSame('diterima', $transfer->fresh()->status);
        $this->assertSame('diproses', $permintaan->fresh()->status);
        $this->assertDatabaseHas('penerimaan_pemindahan_units', [
            'transfer_barang_id' => $transfer->id,
            'qty_diterima' => 4,
            'qty_rusak' => 1,
            'catatan_discrepancy' => 'Satu unit rusak saat diterima.',
        ]);
    }

    public function test_user_cannot_confirm_transfer_for_unassigned_unit(): void
    {
        [$user, $unit, $barang] = $this->unitUserAndCatalog();
        $otherUnit = Gudang::create([
            'kode' => 'UNIT-OTHER',
            'nama' => 'Unit Lain',
            'jenis' => 'unit_bisnis',
            'is_aktif' => true,
        ]);
        $store = Gudang::create([
            'kode' => 'STORE',
            'nama' => 'Gudang Store',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $transfer = $this->sentTransfer($store, $otherUnit, $barang, null, 2);

        $this->actingAs($user)
            ->post(route('transaksi.pemindahan-unit.konfirmasi', $transfer), [
                'qty_diterima' => 2,
                'qty_rusak' => 0,
                'catatan_discrepancy' => '',
            ])
            ->assertNotFound();

        $this->assertSame('dikirim', $transfer->fresh()->status);
        $this->assertDatabaseCount('penerimaan_pemindahan_units', 0);
    }

    /**
     * @return array{User, Gudang, Barang, Satuan}
     */
    private function unitUserAndCatalog(): array
    {
        $permission = Permission::create(['name' => 'pemindahan.terima', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Recording Gudang Umum', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user = User::withoutEvents(fn () => User::create([
            'name' => 'Petugas Unit',
            'username' => 'petugas-unit',
            'password' => 'password',
            'is_aktif' => true,
        ]));
        $user->assignRole($role);

        $unit = Gudang::create([
            'kode' => 'UNIT',
            'nama' => 'Gudang Unit',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $user->gudang()->attach($unit);
        session(['active_gudang_id' => $unit->id]);
        $satuan = Satuan::create(['kode' => 'PCS', 'nama' => 'pcs', 'is_aktif' => true]);
        $barang = Barang::create([
            'kode' => 'ITEM-UNIT',
            'nama' => 'Barang Unit',
            'satuan_id' => $satuan->id,
            'is_aktif' => true,
        ]);

        return [$user, $unit, $barang, $satuan];
    }

    private function sentTransfer(Gudang $store, Gudang $unit, Barang $barang, ?PermintaanBarang $permintaan, int $qty): TransferBarang
    {
        return TransferBarang::create([
            'nomor_sj' => 'SJ-UNIT-'.uniqid(),
            'permintaan_barang_id' => $permintaan?->id,
            'gudang_asal_id' => $store->id,
            'gudang_tujuan_id' => $unit->id,
            'barang_id' => $barang->id,
            'qty_diminta' => $qty,
            'qty_dikirim' => $qty,
            'qty_diterima' => 0,
            'status' => 'dikirim',
            'supir_pengantar' => 'Driver Store',
            'dikirim_at' => now(),
        ]);
    }
}
