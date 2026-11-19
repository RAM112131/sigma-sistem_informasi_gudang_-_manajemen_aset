<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Hindari mengotori database testing isolasi saat testing
        if (app()->runningUnitTests()) {
            return;
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $menuPermissions = [
            // 1. Gudang Store (Armada Holding)
            'store_stok.read',
            'store_stok.create',
            'store_penerimaan.read',
            'store_penerimaan.create',
            'store_penerimaan.update',
            'store_penerimaan.delete',
            'store_pengeluaran.read',
            'store_pengeluaran.create',
            'store_pengeluaran.update',
            'store_pengeluaran.delete',
            'store_laporan.read',
            'store_laporan.export',

            // 2. Operasional Unit Bisnis
            'pemindahan.read',
            'pemindahan.create',
            'pemindahan.update',
            'pemindahan.delete',
            'stok_opname.read',
            'stok_opname.create',
            'stok_opname.update',
            'stok_opname.delete',
            'stok_opname.finalisasi',
            'penerimaan.read',
            'penerimaan.create',
            'penerimaan.update',
            'penerimaan.delete',
            'pengeluaran.read',
            'pengeluaran.create',
            'pengeluaran.update',
            'pengeluaran.delete',
            'retur.read',
            'retur.create',
            'retur.update',
            'retur.delete',
            'label.read',
            'label.create',
            'label.update',
            'label.delete',
            'berita_acara.read',
            'berita_acara.create',
            'berita_acara.update',
            'berita_acara.delete',
            'laporan.read',
            'laporan.export',

            // 3. Master Data & Katalog
            'barang.read',
            'barang.create',
            'barang.update',
            'barang.delete',
            'barang.import',
            'kategori.read',
            'kategori.create',
            'kategori.update',
            'kategori.delete',
            'satuan.read',
            'satuan.create',
            'satuan.update',
            'satuan.delete',

            // 4. Master Pengguna & Sistem
            'user.read',
            'user.create',
            'user.update',
            'user.delete',
            'role.read',
            'role.create',
            'role.update',
            'role.delete',
            'gudang.read',
            'gudang.create',
            'gudang.update',
            'gudang.delete',
            'audit.read',
            'audit.delete',
        ];

        foreach ($menuPermissions as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        // Sync with Super Admin (Full access to all permissions)
        $superAdmin = Role::where('name', 'Super Admin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo(Permission::where('guard_name', 'web')->get());
        }

        // Sync with Admin Gudang Store
        $adminStore = Role::where('name', 'Admin Gudang Store')->where('guard_name', 'web')->first();
        if ($adminStore) {
            $adminStore->givePermissionTo([
                'barang.read',
                'barang.create',
                'barang.import',
                'kategori.read',
                'kategori.create',
                'satuan.read',
                'satuan.create',
                'store_stok.read',
                'store_stok.create',
                'store_penerimaan.read',
                'store_penerimaan.create',
                'store_penerimaan.update',
                'store_penerimaan.delete',
                'store_pengeluaran.read',
                'store_pengeluaran.create',
                'store_pengeluaran.update',
                'store_pengeluaran.delete',
                'store_laporan.read',
                'store_laporan.export',
            ]);
        }

        // Sync with Admin Gudang Umum
        $adminGU = Role::where('name', 'Admin Gudang Umum')->where('guard_name', 'web')->first();
        if ($adminGU) {
            $adminGU->givePermissionTo([
                'barang.read',
                'barang.create',
                'barang.import',
                'kategori.read',
                'kategori.create',
                'satuan.read',
                'satuan.create',
                'stok_opname.read',
                'stok_opname.create',
                'stok_opname.update',
                'stok_opname.delete',
                'stok_opname.finalisasi',
                'penerimaan.read',
                'penerimaan.create',
                'penerimaan.update',
                'penerimaan.delete',
                'pengeluaran.read',
                'pengeluaran.create',
                'pengeluaran.update',
                'pengeluaran.delete',
                'pemindahan.read',
                'pemindahan.create',
                'pemindahan.update',
                'pemindahan.delete',
                'label.read',
                'label.create',
                'label.update',
                'label.delete',
                'retur.read',
                'retur.create',
                'retur.update',
                'retur.delete',
                'berita_acara.read',
                'berita_acara.create',
                'berita_acara.update',
                'berita_acara.delete',
                'laporan.read',
                'laporan.export',
            ]);
        }

        // Sync with Recording Gudang Umum
        $recordingGU = Role::where('name', 'Recording Gudang Umum')->where('guard_name', 'web')->first();
        if ($recordingGU) {
            $recordingGU->givePermissionTo([
                'barang.read',
                'stok_opname.read',
                'stok_opname.create',
                'stok_opname.update',
                'penerimaan.read',
                'penerimaan.create',
                'penerimaan.update',
                'pengeluaran.read',
                'pengeluaran.create',
                'pengeluaran.update',
                'pemindahan.read',
                'pemindahan.update',
                'label.read',
                'label.create',
                'retur.read',
                'retur.create',
                'retur.update',
                'laporan.read',
            ]);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe rollback
    }
};
