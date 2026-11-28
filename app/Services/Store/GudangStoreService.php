<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\Gudang;
use App\Models\User;

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

        $isSuperAdmin = $user->hasAnyRole(['Super Admin', 'super_admin']);

        if (! $isSuperAdmin) {
            $user->loadMissing([
                'gudang' => static fn ($query) => $query->orderBy('gudangs.nama'),
            ]);
            $assigned = $user->gudang
                ->filter(static fn (Gudang $gudang): bool => (bool) $gudang->is_aktif
                    && in_array($gudang->jenis, $warehouseTypes, true))
                ->sortBy('nama')
                ->values();

            $warehouse = null;
            if ($requestedId) {
                $warehouse = $assigned->firstWhere('id', (int) $requestedId);
                abort_unless($warehouse instanceof Gudang, 403, 'Anda belum ditugaskan ke Gudang Store aktif.');
            } elseif ($fallbackId && is_numeric($fallbackId)) {
                $warehouse = $assigned->firstWhere('id', (int) $fallbackId);
            }

            $warehouse ??= $assigned->first();
            abort_unless($warehouse instanceof Gudang, 403, 'Anda belum ditugaskan ke Gudang Store aktif.');

            return $warehouse;
        }

        $query = Gudang::query()
            ->where('is_aktif', true)
            ->whereIn('jenis', $warehouseTypes);

        if ($requestedId) {
            $warehouse = (clone $query)->whereKey((int) $requestedId)->first();
            abort_unless($warehouse instanceof Gudang, 403, 'Anda belum ditugaskan ke Gudang Store aktif.');
        } elseif ($fallbackId && is_numeric($fallbackId)) {
            $fallbackWarehouse = (clone $query)->whereKey((int) $fallbackId)->first();
            if ($fallbackWarehouse instanceof Gudang && in_array($fallbackWarehouse->jenis, $warehouseTypes, true)) {
                $warehouse = $fallbackWarehouse;
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

        $storeTypes = ['holding', 'global_master', 'gudang_store'];
        if (! $isSuperAdmin) {
            $user->loadMissing([
                'gudang' => static fn ($query) => $query->orderBy('gudangs.nama'),
            ]);
            $assigned = $user->gudang
                ->filter(static fn (Gudang $gudang): bool => (bool) $gudang->is_aktif
                    && in_array($gudang->jenis, $storeTypes, true))
                ->sortBy('nama')
                ->values();

            if ($assigned->isNotEmpty()) {
                $warehouse = $requestedId
                    ? $assigned->firstWhere('id', (int) $requestedId)
                    : null;
                $warehouse ??= $assigned->first();

                abort_unless($warehouse instanceof Gudang, 403, 'Gudang Store aktif tidak ditemukan.');

                return [
                    'warehouse' => $warehouse,
                    'remember' => true,
                ];
            }
        }

        $baseQuery = Gudang::query()
            ->where('is_aktif', true)
            ->whereIn('jenis', $storeTypes);

        $warehouse = $requestedId
            ? (clone $baseQuery)->whereKey((int) $requestedId)->first()
            : null;
        $warehouse ??= $baseQuery->orderBy('nama')->first();

        abort_unless($warehouse instanceof Gudang, 403, 'Gudang Store aktif tidak ditemukan.');

        return [
            'warehouse' => $warehouse,
            'remember' => $isSuperAdmin,
        ];
    }
}
