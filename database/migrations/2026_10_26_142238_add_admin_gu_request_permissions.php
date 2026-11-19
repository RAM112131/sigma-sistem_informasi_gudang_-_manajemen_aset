<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            Permission::findOrCreate('permintaan_barang.read', 'web'),
            Permission::findOrCreate('permintaan_barang.create', 'web'),
        ];

        $adminGU = Role::query()
            ->where('name', 'Admin Gudang Umum')
            ->where('guard_name', 'web')
            ->first();

        if ($adminGU !== null) {
            $adminGU->givePermissionTo($permissions);
            $adminGU->revokePermissionTo([
                'pemindahan.terima',
                'penerimaan.input',
                'penerimaan.read',
                'penerimaan.create',
                'penerimaan.update',
                'penerimaan.delete',
            ]);
        }

        $superAdmin = Role::query()
            ->where('name', 'Super Admin')
            ->where('guard_name', 'web')
            ->first();
        $superAdmin?->givePermissionTo($permissions);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['permintaan_barang.read', 'permintaan_barang.create'])
            ->get();

        foreach (Role::query()->where('guard_name', 'web')->get() as $role) {
            foreach ($permissions as $permission) {
                $role->revokePermissionTo($permission);
            }
        }

        $adminGU = Role::query()
            ->where('name', 'Admin Gudang Umum')
            ->where('guard_name', 'web')
            ->first();
        $adminGU?->givePermissionTo([
            'pemindahan.terima',
            'penerimaan.input',
            'penerimaan.read',
            'penerimaan.create',
            'penerimaan.update',
            'penerimaan.delete',
        ]);

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['permintaan_barang.read', 'permintaan_barang.create'])
            ->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
