<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds for Roles & Permissions.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define Permissions
        $permissions = [
            // Master Data & Katalog
            'barang.view',
            'barang.read',
            'barang.create',
            'barang.update',
            'barang.delete',
            'barang.manage',
            'barang.import',
            'satuan.read',
            'satuan.create',
            'satuan.update',
            'satuan.delete',
            'satuan.manage',
            'kategori.read',
            'kategori.create',
            'kategori.update',
            'kategori.delete',
            'kategori.manage',
            'area.read',
            'area.create',
            'area.manage',
            'stok_awal.import',

            // Operasional Gudang Store (Holding / Armada)
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
            'store.view',
            'store.penerimaan',
            'store.distribusi',
            'pemindahan.terima',
            'permintaan_barang.read',
            'permintaan_barang.create',

            // Operasional Unit Bisnis (RPA Sukahati / Gudang Umum)
            'stokunitbisnis.view',
            'stokunitbisnis.read',
            'stokunitbisnis.create',
            'stokunitbisnis.edit',
            'stokunitbisnis.update',
            'stokunitbisnis.delete',
            'pemindahan.read',
            'pemindahan.create',
            'pemindahan.update',
            'pemindahan.delete',
            'stok_opname.read',
            'stok_opname.create',
            'stok_opname.update',
            'stok_opname.delete',
            'stok_opname.input',
            'penerimaan.read',
            'penerimaan.create',
            'penerimaan.update',
            'penerimaan.delete',
            'penerimaan.input',
            'pengeluaran.read',
            'pengeluaran.create',
            'pengeluaran.update',
            'pengeluaran.delete',
            'pengeluaran.input',
            'label.read',
            'label.create',
            'label.update',
            'label.delete',
            'label.cetak',
            'retur.read',
            'retur.create',
            'retur.update',
            'retur.delete',
            'retur.input',
            'berita_acara.read',
            'berita_acara.create',
            'berita_acara.update',
            'berita_acara.delete',
            'berita_acara.buat',

            // Laporan & Analytics
            'laporan.read',
            'laporan.view',
            'laporan.export',

            // Manajemen Sistem & Audit
            'user.read',
            'user.create',
            'user.update',
            'user.delete',
            'user.manage',
            'role.read',
            'role.create',
            'role.update',
            'role.delete',
            'role.manage',
            'gudang.read',
            'gudang.create',
            'gudang.update',
            'gudang.delete',
            'gudang.manage',
            'audit.read',
            'audit.delete',
            'audit.view',
        ];

        foreach ($permissions as $permName) {
            Permission::findOrCreate($permName, 'web');
        }

        // 2. Create Core Roles & Sync Permissions

        // Role 1: Super Admin
        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        $superAdminRole->syncPermissions(Permission::query()->get());

        // Role 2: Admin Gudang Store (Armada / Holding)
        $adminStoreRole = Role::findOrCreate('Admin Gudang Store', 'web');
        $adminStoreRole->syncPermissions([
            'barang.view',
            'barang.create',
            'barang.import',
            'kategori.create',
            'satuan.create',
            'stok_awal.import',
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
            'store.view',
            'store.penerimaan',
            'store.distribusi',
            'penerimaan.input',
            'pengeluaran.input',
            'pemindahan.terima',
            'laporan.view',
            'laporan.export',
        ]);

        // Role 3: Admin Gudang Umum (RPA Sukahati / Unit Bisnis)
        $adminGURole = Role::findOrCreate('Admin Gudang Umum', 'web');
        $adminGURole->syncPermissions([
            'barang.view',
            'barang.read',
            'barang.create',
            'barang.import',
            'kategori.read',
            'kategori.create',
            'satuan.read',
            'satuan.create',
            'area.read',
            'area.create',
            'area.manage',
            'stok_opname.input',
            'stok_opname.read',
            'stok_opname.create',
            'stok_opname.update',
            'stok_opname.delete',
            'pengeluaran.input',
            'pengeluaran.read',
            'pengeluaran.create',
            'pengeluaran.update',
            'pengeluaran.delete',
            'pemindahan.read',
            'pemindahan.create',
            'pemindahan.update',
            'pemindahan.delete',
            'permintaan_barang.read',
            'permintaan_barang.create',
            'label.cetak',
            'label.read',
            'label.create',
            'label.update',
            'label.delete',
            'retur.input',
            'retur.read',
            'retur.create',
            'retur.update',
            'retur.delete',
            'berita_acara.buat',
            'berita_acara.read',
            'berita_acara.create',
            'berita_acara.update',
            'berita_acara.delete',
            'laporan.view',
            'laporan.read',
            'laporan.export',
            'penerimaan.read',
            'penerimaan.create',
            'penerimaan.input',
            'penerimaan.update',
            'stokunitbisnis.view',
            'stokunitbisnis.read',
            'stokunitbisnis.create',
            'stokunitbisnis.edit',
            'stokunitbisnis.update',
            'stokunitbisnis.delete',
        ]);

        // Role 4: Recording Gudang Umum
        $recordingGURole = Role::findOrCreate('Recording Gudang Umum', 'web');
        $recordingGURole->syncPermissions([
            'stokunitbisnis.view',
            'stokunitbisnis.read',
            'barang.view',
            'barang.read',
            'area.read',
            'area.create',
            'area.manage',
            'stok_opname.input',
            'stok_opname.read',
            'stok_opname.create',
            'stok_opname.update',
            'penerimaan.input',
            'penerimaan.read',
            'penerimaan.create',
            'penerimaan.update',
            'pengeluaran.input',
            'pengeluaran.read',
            'pengeluaran.create',
            'pengeluaran.update',
            'pemindahan.terima',
            'pemindahan.read',
            'pemindahan.update',
            'label.cetak',
            'label.read',
            'label.create',
            'retur.input',
            'retur.read',
            'retur.create',
            'retur.update',
            'laporan.view',
            'laporan.read',
        ]);
    }
}
