<?php

declare(strict_types=1);

namespace App\Services\SuperAdmin;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleService
{
    private const DEFAULT_ROLES = [
        'Super Admin',
        'super_admin',
        'Admin Gudang Umum',
        'admin_gudang_umum',
        'Recording Gudang Umum',
        'recording_gudang_umum',
        'Admin Gudang Store',
        'admin_gudang_store',
    ];

    private const SUPER_ADMIN_ROLES = ['Super Admin', 'super_admin'];

    /**
     * @return array{
     *     roles: Collection<int, array<string, mixed>>,
     *     permissionGroups: array<string, array<int, array<string, mixed>>>,
     *     stats: array<string, int|string>
     * }
     */
    public function getListingData(): array
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->withCount([
                'users',
                'users as active_users_count' => static fn ($query) => $query->where('is_aktif', true),
            ])
            ->with('permissions')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'description' => $this->resolveRoleDescription($role),
                'users_count' => $role->users_count,
                'active_users_count' => $role->active_users_count,
                'permissions_count' => $role->permissions_count,
                'permissions' => $role->permissions->pluck('name')->all(),
                'is_default' => in_array($role->name, self::DEFAULT_ROLES, true),
                'is_super_admin' => in_array($role->name, self::SUPER_ADMIN_ROLES, true),
            ]);

        $defaultRoleCount = $roles->where('is_default', true)->count();
        $permissionCacheStore = config('permission.cache.store');
        if (! is_string($permissionCacheStore) || $permissionCacheStore === 'default') {
            $permissionCacheStore = (string) config('cache.default');
        }

        return [
            'roles' => $roles,
            'permissionGroups' => $this->permissionGroups(),
            'stats' => [
                'total_roles' => $roles->count(),
                'default_roles' => $defaultRoleCount,
                'custom_roles' => $roles->count() - $defaultRoleCount,
                'total_permissions' => Permission::query()
                    ->where('guard_name', 'web')
                    ->count(),
                'assigned_users' => User::query()
                    ->where('is_aktif', true)
                    ->whereHas('roles', static fn ($query) => $query->where('roles.guard_name', 'web'))
                    ->count(),
                'permission_cache_store' => $permissionCacheStore,
            ],
        ];
    }

    /**
     * @return array{
     *     permissionGroups: array<string, array<int, array<string, mixed>>>,
     *     roleTemplates: Collection<int, array{id: string, name: string, permissions: array<int, string>}>
     * }
     */
    public function getFormData(?Role $role = null): array
    {
        $role?->loadMissing('permissions');

        $templates = Role::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', self::SUPER_ADMIN_ROLES)
            ->when($role !== null, static fn ($query) => $query->where('id', '!=', $role->getKey()))
            ->with('permissions')
            ->orderBy('name')
            ->get()
            ->map(static fn (Role $template): array => [
                'id' => (string) $template->id,
                'name' => $template->name,
                'permissions' => $template->permissions->pluck('name')->all(),
            ])
            ->values();

        return [
            'permissionGroups' => $this->permissionGroups(),
            'roleTemplates' => $templates,
        ];
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Role
    {
        return DB::transaction(function () use ($attributes): Role {
            $roleAttributes = [
                'name' => trim($attributes['name']),
                'guard_name' => 'web',
            ];

            if (Schema::hasColumn('roles', 'description')) {
                $description = trim((string) ($attributes['description'] ?? ''));
                $roleAttributes['description'] = $description !== '' ? $description : null;
            }

            $role = Role::create($roleAttributes);
            $role->syncPermissions($attributes['permissions'] ?? []);

            return $role;
        });
    }

    /**
     * Returns false when the protected Super Admin role was only restored to full web permissions.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Role $role, array $attributes): bool
    {
        return DB::transaction(function () use ($role, $attributes): bool {
            if (in_array($role->name, self::SUPER_ADMIN_ROLES, true)) {
                $role->syncPermissions(Permission::query()->where('guard_name', 'web')->get());

                return false;
            }

            if (! empty($attributes['name'])) {
                $role->name = trim($attributes['name']);
            }

            if (array_key_exists('description', $attributes) && Schema::hasColumn('roles', 'description')) {
                $description = trim((string) ($attributes['description'] ?? ''));
                $role->description = $description !== '' ? $description : null;
            }

            $role->save();
            $role->syncPermissions($attributes['permissions'] ?? []);

            return true;
        });
    }

    /** @return array{deleted: bool, name: string, reason: ?string} */
    public function delete(Role $role): array
    {
        return DB::transaction(static function () use ($role): array {
            $name = $role->name;

            if (in_array($name, self::DEFAULT_ROLES, true)) {
                return [
                    'deleted' => false,
                    'name' => $name,
                    'reason' => "Role bawaan sistem '{$name}' tidak dapat dihapus!",
                ];
            }

            $assignedUsers = $role->users()->count();
            if ($assignedUsers > 0) {
                return [
                    'deleted' => false,
                    'name' => $name,
                    'reason' => "Role '{$name}' tidak dapat dihapus karena masih digunakan oleh {$assignedUsers} pengguna!",
                ];
            }

            $role->delete();

            return ['deleted' => true, 'name' => $name, 'reason' => null];
        });
    }

    public function clearPermissionCache(PermissionRegistrar $permissionRegistrar): void
    {
        $permissionRegistrar->forgetCachedPermissions();
    }

    private function resolveRoleDescription(Role $role): string
    {
        $storedDescription = $role->hasAttribute('description')
            ? trim((string) ($role->description ?? ''))
            : '';

        if ($storedDescription !== '') {
            return $storedDescription;
        }

        return match ($role->name) {
            'Super Admin', 'super_admin' => 'Akses penuh ke seluruh konfigurasi sistem, akun pengguna, matriks role, dan master lokasi gudang.',
            'Admin Gudang Umum', 'admin_gudang_umum' => 'Verifikasi dan finalisasi stok opname, rekapitulasi harian, buat Berita Acara Kehilangan, dan reminder stok/expired.',
            'Recording Gudang Umum', 'recording_gudang_umum' => 'Input opname fisik harian, penerimaan barang, pengeluaran (tanda tangan digital), cetak label, dan retur.',
            'Admin Gudang Store', 'admin_gudang_store' => 'Kelola katalog barang Gudang Store, stok independen, dan alur penerimaan khusus Gudang Store.',
            default => 'Role kustom dengan kombinasi hak akses dinamis.',
        };
    }

    /**
     * @return array<string, array<int, array{key: string, name: string, label: string, actions: array<int, string>}>>
     */
    private function permissionGroups(): array
    {
        $knownLabels = [
            // Master Pengguna & Sistem
            'user.read' => 'Lihat Daftar Pengguna',
            'user.create' => 'Tambah Pengguna Baru',
            'user.update' => 'Edit Pengguna',
            'user.delete' => 'Hapus Pengguna',
            'user.manage' => 'Kelola Akun Pegawai & Gudang',
            'role.read' => 'Lihat Daftar Role',
            'role.create' => 'Tambah Role Baru',
            'role.update' => 'Edit Role & Matriks Permission',
            'role.delete' => 'Hapus Role',
            'role.manage' => 'Kelola Role & Matriks Permission',
            'gudang.read' => 'Lihat Master Lokasi Gudang',
            'gudang.create' => 'Tambah Lokasi Gudang',
            'gudang.update' => 'Edit Lokasi Gudang',
            'gudang.delete' => 'Hapus Lokasi Gudang',
            'gudang.manage' => 'Kelola Master Lokasi Gudang',
            'audit.read' => 'Lihat Audit Log Aktivitas',
            'audit.delete' => 'Bersihkan Audit Log Aktivitas',
            'audit.view' => 'Lihat Audit Log Aktivitas',

            // Master Data & Katalog
            'barang.read' => 'Lihat Katalog Master Barang',
            'barang.create' => 'Tambah Master Barang (Inline Quick-Add)',
            'barang.update' => 'Edit Master Barang',
            'barang.delete' => 'Hapus / Nonaktifkan Master Barang',
            'barang.manage' => 'Kelola Master Barang (Edit/Hapus)',
            'barang.view' => 'Lihat Katalog Barang (Read-Only)',
            'barang.import' => 'Impor Master Barang dari Excel',
            'kategori.read' => 'Lihat Kategori Barang',
            'kategori.create' => 'Tambah Kategori Barang',
            'kategori.update' => 'Edit Kategori Barang',
            'kategori.delete' => 'Hapus Kategori Barang',
            'kategori.manage' => 'Kelola Kategori Barang',
            'satuan.read' => 'Lihat Satuan Barang',
            'satuan.create' => 'Tambah Satuan Barang',
            'satuan.update' => 'Edit Satuan Barang',
            'satuan.delete' => 'Hapus Satuan Barang',
            'satuan.manage' => 'Kelola Satuan Barang',
            'stok_awal.import' => 'Import Data Awal & Migration Setup',

            // Operasional Gudang Store (Holding / Armada)
            'store_stok.read' => 'Lihat Stok & Kartu Stok Store',
            'store_stok.create' => 'Import Stok Awal Gudang Store',
            'store_penerimaan.read' => 'Lihat Riwayat Penerimaan Store',
            'store_penerimaan.create' => 'Input Penerimaan Supplier Store',
            'store_penerimaan.update' => 'Edit Penerimaan Store',
            'store_penerimaan.delete' => 'Hapus Penerimaan Store',
            'store_pengeluaran.read' => 'Lihat Permintaan & Pengeluaran Store',
            'store_pengeluaran.create' => 'Buat Pemindahan / Distribusi Store',
            'store_pengeluaran.update' => 'Proses & Konfirmasi Kirim Store',
            'store_pengeluaran.delete' => 'Hapus / Tolak Permintaan Store',
            'store_laporan.read' => 'Lihat Laporan Gudang Store',
            'store_laporan.export' => 'Export Laporan Store (PDF/Excel)',

            // Operasional Unit Bisnis
            'pemindahan.read' => 'Lihat Kiriman Transfer dari Store',
            'pemindahan.create' => 'Buat Permintaan Transfer Unit',
            'pemindahan.update' => 'Konfirmasi Penerimaan Transfer Store',
            'pemindahan.delete' => 'Batalkan Transfer Unit',
            'pemindahan.terima' => 'Konfirmasi Penerimaan Transfer Store',
            'stok_opname.read' => 'Lihat Data Stok Opname',
            'stok_opname.create' => 'Input Stok Opname Fisik',
            'stok_opname.update' => 'Edit / Verifikasi Stok Opname',
            'stok_opname.delete' => 'Hapus / Reset Stok Opname',
            'stok_opname.input' => 'Input Stok Opname Fisik (Unit)',
            'penerimaan.read' => 'Lihat Penerimaan Barang Unit',
            'penerimaan.create' => 'Input Penerimaan Barang Unit',
            'penerimaan.update' => 'Edit Penerimaan Barang Unit',
            'penerimaan.delete' => 'Hapus Penerimaan Barang Unit',
            'penerimaan.input' => 'Input Penerimaan Barang Unit',
            'pengeluaran.read' => 'Lihat Pengeluaran Barang Unit',
            'pengeluaran.create' => 'Input Pengeluaran & BPB Unit',
            'pengeluaran.update' => 'Edit Pengeluaran Barang Unit',
            'pengeluaran.delete' => 'Hapus Pengeluaran Barang Unit',
            'pengeluaran.input' => 'Input Pengeluaran & BPB Unit',
            'label.read' => 'Lihat Daftar Label Produk',
            'label.create' => 'Pencetakan Label Produk & Barcode',
            'label.update' => 'Edit Format Label Produk',
            'label.delete' => 'Hapus Label Produk',
            'label.cetak' => 'Pencetakan Label Produk & Barcode',
            'retur.read' => 'Lihat Retur Barang Unit',
            'retur.create' => 'Input Retur Barang Unit',
            'retur.update' => 'Edit Retur Barang Unit',
            'retur.delete' => 'Hapus Retur Barang Unit',
            'retur.input' => 'Input Retur Barang Unit',
            'berita_acara.read' => 'Lihat Berita Acara Kehilangan',
            'berita_acara.create' => 'Buat Berita Acara Kehilangan',
            'berita_acara.update' => 'Edit Berita Acara Kehilangan',
            'berita_acara.delete' => 'Hapus Berita Acara Kehilangan',
            'berita_acara.buat' => 'Buat Berita Acara Kehilangan',
            'laporan.read' => 'Lihat Rekapitulasi Laporan Stok',
            'laporan.export' => 'Export Laporan PDF / Excel',
            'laporan.view' => 'Lihat Rekapitulasi Laporan Stok',
            'stokunitbisnis.view' => 'Lihat Stok Unit Bisnis (Read-Only)',
            'stokunitbisnis.read' => 'Lihat Saldo & Kartu Stok Unit Bisnis',
            'stokunitbisnis.create' => 'Tambah / Inisialisasi Stok Unit Bisnis',
            'stokunitbisnis.edit' => 'Edit / Koreksi Stok Unit Bisnis',
            'stokunitbisnis.update' => 'Update Stok Unit Bisnis',
            'stokunitbisnis.delete' => 'Hapus Data Stok Unit Bisnis',
        ];

        $groups = [];
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['name']);

        // Check if new granular store permissions exist; if so, skip legacy monolithic store.* to avoid duplicate single-row "Store"
        $hasGranularStore = $permissions->contains(fn ($p) => str_starts_with($p->name, 'store_'));

        foreach ($permissions as $permission) {
            $key = $permission->name;

            if ($hasGranularStore && str_starts_with($key, 'store.')) {
                continue;
            }

            $label = $knownLabels[$key] ?? str($key)->replace(['.', '_', '-'], ' ')->title()->toString();
            $action = str($key)->afterLast('.')->toString();
            $prefix = str($key)->before('.')->toString();
            $actions = match ($action) {
                'view', 'read' => ['view'],
                'create', 'input', 'buat', 'import', 'penerimaan', 'distribusi', 'export', 'cetak' => ['create'],
                'manage' => in_array($prefix, ['user', 'role', 'gudang'], true)
                    ? ['create', 'update', 'delete']
                    : ['update', 'delete'],
                'edit', 'update', 'finalisasi', 'terima' => ['update'],
                'delete', 'destroy' => ['delete'],
                default => ['view'],
            };
            $groupKey = match (true) {
                str_starts_with($key, 'user.') || str_starts_with($key, 'users.') || str_starts_with($key, 'role.') || str_starts_with($key, 'roles.') || str_starts_with($key, 'gudang.') || str_starts_with($key, 'audit.') => 'Master Pengguna & Sistem',
                str_starts_with($key, 'barang.') || str_starts_with($key, 'kategori.') || str_starts_with($key, 'satuan.') || str_starts_with($key, 'stok_awal.') => 'Master Data & Katalog',
                str_starts_with($key, 'store_') || str_starts_with($key, 'store.') => 'Gudang Store (Armada Holding)',
                default => 'Operasional Unit Bisnis',
            };

            $groups[$groupKey][] = [
                'key' => $key,
                'name' => $key,
                'label' => $label,
                'actions' => $actions,
            ];
        }

        return $groups;
    }
}
