<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\PenerimaanBarangStore;
use App\Models\PenerimaanBarangStoreDetail;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class PenerimaanStoreMoreQuantityTest extends TestCase
{
    use RefreshDatabase;

    public function test_more_condition_requires_a_positive_quantity(): void
    {
        $this->freezeTime();

        $permission = Permission::create(['name' => 'store_penerimaan.create', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Admin Gudang Store', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $store = Gudang::create(['kode' => 'STORE', 'nama' => 'Gudang Store', 'jenis' => 'gudang_store', 'is_aktif' => true]);
        $user = User::create(['name' => 'Store User', 'username' => 'store-user', 'password' => 'password', 'is_aktif' => true]);
        $user->assignRole($role);
        $user->gudang()->attach($store);

        $date = today()->format('dmY');
        $response = $this->actingAs($user)->from('/store/penerimaan')->post('/store/penerimaan', [
            'tanggal_diterima' => today()->toDateString(),
            'source_groups' => [[
                'sumber_supplier' => 'Supplier Uji',
                'nomor_rcv_prefix' => "RCV-STR-{$date}001-",
                'nomor_penerimaan_preview' => "RCV-STR-{$date}001-0001",
                'pemesan' => 'Pemesan Uji',
                'pengambil_barang' => 'Pengambil Uji',
                'metode_pembayaran' => 'Tunai',
                'tanpa_surat_jalan' => 'ada',
                'items' => [[
                    'barang_id' => 1,
                    'qty_diterima' => 5,
                    'qty_rusak' => 0,
                    'qty_kurang' => 0,
                    'qty_lebih' => 0,
                    'satuan_id' => 1,
                    'kondisi' => 'Lebih',
                ]],
            ]],
        ]);

        $response->assertSessionHasErrors([
            'source_groups.0.items.0.qty_lebih' => 'Qty lebih minimal 1.',
        ]);
    }

    public function test_valid_receipt_persists_header_and_item_and_redirects_with_receipt_number(): void
    {
        $this->freezeTime();

        $permission = Permission::create(['name' => 'store_penerimaan.create', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Admin Gudang Store', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $store = Gudang::create(['kode' => 'STORE', 'nama' => 'Gudang Store', 'jenis' => 'gudang_store', 'is_aktif' => true]);
        $user = User::create(['name' => 'Store User', 'username' => 'store-user', 'password' => 'password', 'is_aktif' => true]);
        $user->assignRole($role);
        $user->gudang()->attach($store);
        $satuan = Satuan::create(['kode' => 'PCS', 'nama' => 'pcs', 'is_aktif' => true]);
        $barang = Barang::create([
            'gudang_id' => $store->id,
            'kode' => 'ITEM-001',
            'nama' => 'Barang Uji',
            'satuan_id' => $satuan->id,
            'is_aktif' => true,
        ]);
        $date = today()->format('dmY');
        $receiptNumber = "RCV-STR-{$date}001-0001";
        config(['queue.default' => 'sync']);
        Log::spy();

        $response = $this->actingAs($user)->post(route('store.penerimaan.store'), [
            'tanggal_diterima' => today()->toDateString(),
            'source_groups' => [[
                'sumber_supplier' => 'Supplier Uji',
                'nomor_rcv_prefix' => "RCV-STR-{$date}001-",
                'nomor_penerimaan_preview' => $receiptNumber,
                'pemesan' => 'Pemesan Uji',
                'pengambil_barang' => 'Pengambil Uji',
                'metode_pembayaran' => 'Tunai',
                'tanpa_surat_jalan' => 'ada',
                'items' => [[
                    'barang_id' => $barang->id,
                    'qty_diterima' => 5,
                    'qty_rusak' => 0,
                    'qty_kurang' => 0,
                    'qty_lebih' => 0,
                    'satuan_id' => $satuan->id,
                    'kondisi' => 'Baik',
                    'harga_satuan' => 12.5,
                ]],
            ]],
        ]);

        $response->assertRedirect(route('store.penerimaan.index', ['tab' => 'riwayat_penerimaan']))
            ->assertSessionHas(
                'success',
                "Penerimaan Barang berhasil disimpan dengan nomor RCV: {$receiptNumber} (1 baris barang); stok ter-update!"
            );

        $this->assertDatabaseHas('penerimaan_barang_stores', [
            'nomor_penerimaan' => $receiptNumber,
            'gudang_id' => $store->id,
            'barang_id' => $barang->id,
            'qty_diterima' => 5,
            'satuan_id' => $satuan->id,
            'sumber_supplier' => 'Supplier Uji',
            'diterima_oleh' => $user->id,
        ]);
        $this->assertDatabaseHas('penerimaan_barang_store_details', [
            'penerimaan_barang_store_id' => PenerimaanBarangStore::query()
                ->where('nomor_penerimaan', $receiptNumber)
                ->value('id'),
            'barang_id' => $barang->id,
            'qty_diterima' => 5,
            'qty_rusak' => 0,
            'qty_kurang' => 0,
            'qty_lebih' => 0,
            'satuan_id' => $satuan->id,
            'kondisi' => 'Baik',
            'harga_satuan' => 12.5,
            'total_harga' => 62.5,
        ]);
        $this->assertSame(1, PenerimaanBarangStore::query()->count());
        $this->assertSame(1, PenerimaanBarangStoreDetail::query()->count());
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Store receipt embedding skipped because the queue connection is synchronous.', [
                'receipt_id' => PenerimaanBarangStore::query()->where('nomor_penerimaan', $receiptNumber)->value('id'),
            ]);
    }
}
