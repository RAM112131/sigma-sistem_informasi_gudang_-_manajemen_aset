<?php

declare(strict_types=1);

namespace Tests\Feature\UnitBisnis;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\TransferBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AdminGURemovedModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_removed_admin_gu_modules_are_not_routable(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin Gudang Umum', 'web'));

        $this->actingAs($admin);

        foreach ([
            '/unit-bisnis/admin-gu/opname',
            '/unit-bisnis/admin-gu/verifikasi-pengeluaran',
            '/unit-bisnis/admin-gu/mutasi-stok',
            '/unit-bisnis/admin-gu/penerimaan',
            '/unit-bisnis/admin-gu/rekapitulasi',
            '/unit-bisnis/admin-gu/kwh',
        ] as $uri) {
            $this->get($uri)->assertNotFound();
        }

        foreach ([
            '/unit-bisnis/admin-gu/opname/verifikasi',
            '/unit-bisnis/admin-gu/verifikasi-pengeluaran/batch-verify',
            '/unit-bisnis/admin-gu/verifikasi-pengeluaran/tutup-hari',
            '/unit-bisnis/admin-gu/verifikasi-pengeluaran/1/verifikasi',
            '/unit-bisnis/admin-gu/verifikasi-pengeluaran/1/tolak',
            '/unit-bisnis/admin-gu/penerimaan/1',
            '/unit-bisnis/admin-gu/penerimaan/1/verifikasi',
            '/unit-bisnis/admin-gu/kwh',
        ] as $uri) {
            $this->post($uri)->assertNotFound();
        }
    }

    public function test_admin_gu_can_monitor_transfers_without_receiving_them(): void
    {
        [$admin, $unit, $barang] = $this->setupBaseContext();
        $store = Gudang::create([
            'kode' => 'STORE-MONITOR',
            'nama' => 'Gudang Store',
            'jenis' => 'gudang_store',
            'is_aktif' => true,
        ]);
        $transfer = TransferBarang::create([
            'nomor_sj' => 'SJ-MONITOR-001',
            'gudang_asal_id' => $store->id,
            'gudang_tujuan_id' => $unit->id,
            'barang_id' => $barang->id,
            'qty_diminta' => 4,
            'qty_dikirim' => 4,
            'qty_diterima' => 0,
            'status' => 'dikirim',
            'dikirim_at' => now(),
        ]);

        $this->actingAs($admin)
            ->withSession(['active_gudang_id' => $unit->id])
            ->get(route('unit-bisnis.admin-gu.pemindahan.index'))
            ->assertOk()
            ->assertSee('Status Kiriman Store')
            ->assertSee('SJ-MONITOR-001')
            ->assertSee('hanya untuk melihat')
            ->assertDontSee('Periksa &amp; Terima')
            ->assertDontSee('Simpan Penerimaan');

        $this->actingAs($admin)
            ->withSession(['active_gudang_id' => $unit->id])
            ->post("/unit-bisnis/admin-gu/pemindahan/{$transfer->id}/konfirmasi", [
                'qty_diterima' => 4,
                'qty_rusak' => 0,
                'catatan_discrepancy' => '',
            ])
            ->assertNotFound();

        $this->assertSame('dikirim', $transfer->fresh()->status);
    }

    public function test_admin_gu_recording_history_has_no_bpb_verification_controls(): void
    {
        [$admin, $unit] = $this->setupBaseContext();

        $this->actingAs($admin)
            ->withSession(['active_gudang_id' => $unit->id])
            ->get(route('unit-bisnis.recording-gu.pengeluaran.index'))
            ->assertOk()
            ->assertDontSee('Periksa, HPP &amp; Sahkan')
            ->assertDontSee('verifikasi-pengeluaran')
            ->assertSee('Catat Pengembalian Sisa');
    }

    /**
     * @return array{User, Gudang, Barang}
     */
    private function setupBaseContext(): array
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('Admin Gudang Umum', 'web'));

        $unit = Gudang::create([
            'kode' => 'SHT',
            'nama' => 'RPA Sukahati',
            'jenis' => 'gudang_umum',
            'is_aktif' => true,
        ]);
        $admin->gudang()->attach($unit);

        $barang = Barang::create([
            'gudang_id' => $unit->id,
            'kode' => 'STO14165',
            'nama' => 'PLASTIK 17 X 36 X 0.7',
            'harga_satuan' => 35000,
            'is_aktif' => true,
        ]);

        return [$admin, $unit, $barang];
    }
}
