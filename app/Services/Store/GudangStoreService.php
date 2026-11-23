<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\Gudang;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class GudangStoreService
{
    /**
     * @param  array<int, string>  $warehouseTypes
     * @param  array<int, string>  $permissions
     */
    public function resolveAssignedWarehouse(
        User $user,
        int|string|null $requestedId,
        array $warehouseTypes,
        array $permissions = [],
        int|string|null $fallbackId = null,
    ): Gudang {
        if ($permissions !== []) {
            abort_unless(
                collect($permissions)->contains(fn (string $permission): bool => $user->can($permission)),
                403,
            );
        }

        $query = Gudang::query()
            ->where('is_aktif', true)
            ->whereIn('jenis', $warehouseTypes);

        if (! $user->hasAnyRole(['Super Admin', 'super_admin'])) {
            $query->whereHas(
                'users',
                fn (Builder $users): Builder => $users->whereKey($user->getKey()),
            );
        }

        $warehouse = $requestedId
            ? (clone $query)->whereKey((int) $requestedId)->first()
            : null;

        if ($requestedId) {
            abort_unless($warehouse instanceof Gudang, 403, 'Anda belum ditugaskan ke Gudang Store aktif.');
        } elseif ($fallbackId && is_numeric($fallbackId)) {
            $fallbackWarehouseType = Gudang::query()
                ->whereKey((int) $fallbackId)
                ->value('jenis');

            if (in_array($fallbackWarehouseType, $warehouseTypes, true)) {
                $warehouse = (clone $query)->whereKey((int) $fallbackId)->first();
                abort_unless($warehouse instanceof Gudang, 403, 'Anda belum ditugaskan ke Gudang Store aktif.');
            }
        }

        $warehouse ??= $query->orderBy('nama')->first();

        abort_unless($warehouse instanceof Gudang, 403, 'Anda belum ditugaskan ke Gudang Store aktif.');

        return $warehouse;
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array{warehouse: Gudang, remember: bool}
     */
    public function resolveStockWarehouse(
        User $user,
        int|string|null $requestedId,
        array $permissions,
    ): array {
        $isSuperAdmin = $user->hasAnyRole(['Super Admin', 'super_admin']);
        $hasPermission = $isSuperAdmin
            || collect($permissions)->contains(fn (string $permission): bool => $user->can($permission));

        abort_unless($hasPermission, 403);

        $baseQuery = Gudang::query()
            ->where('is_aktif', true)
            ->whereIn('jenis', ['holding', 'global_master', 'gudang_store']);
        $userWarehouseQuery = (clone $baseQuery)
            ->whereHas('users', fn (Builder $users): Builder => $users->whereKey($user->getKey()));
        $hasAssignedWarehouse = $userWarehouseQuery->exists();
        $query = $hasAssignedWarehouse && ! $isSuperAdmin
            ? $userWarehouseQuery
            : $baseQuery;

        $warehouse = $requestedId
            ? (clone $query)->whereKey((int) $requestedId)->first()
            : null;
        $warehouse ??= $query->orderBy('nama')->first();

        abort_unless($warehouse instanceof Gudang, 403, 'Gudang Store aktif tidak ditemukan.');

        return [
            'warehouse' => $warehouse,
            'remember' => $hasAssignedWarehouse || $isSuperAdmin,
        ];
    }
}
