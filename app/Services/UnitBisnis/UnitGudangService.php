<?php

declare(strict_types=1);

namespace App\Services\UnitBisnis;

use App\Models\Gudang;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class UnitGudangService
{
    /** @return Collection<int, Gudang> */
    public function getAssignedGudangUnits(User $user): Collection
    {
        $user->loadMissing([
            'gudang' => static fn ($query) => $query->orderBy('gudangs.nama'),
        ]);

        return $user->gudang
            ->filter(static fn (Gudang $gudang): bool => (bool) $gudang->is_aktif
                && in_array($gudang->jenis, ['unit_bisnis', 'gudang_sukahati', 'gudang_umum'], true))
            ->sortBy('nama')
            ->values();
    }

    /**
     * @param  array<int, string>  $preferredTypes
     */
    public function resolveActiveGudang(
        User $user,
        int|string|null $selectedGudangId,
        array $preferredTypes,
    ): ?Gudang {
        $unitTypes = ['unit_bisnis', 'gudang_sukahati', 'gudang_umum'];

        if (! $user->hasAnyRole(['Super Admin', 'super_admin'])) {
            $assignedUnits = $user->relationLoaded('gudang')
                ? $user->gudang
                    ->filter(static fn (Gudang $g): bool => (bool) $g->is_aktif && in_array($g->jenis, $unitTypes, true))
                    ->sortBy('nama')
                    ->values()
                : $this->getAssignedGudangUnits($user);

            if (is_numeric($selectedGudangId)) {
                $selected = $assignedUnits->firstWhere('id', (int) $selectedGudangId);
                if ($selected !== null) {
                    return $selected;
                }
            }

            $preferred = $assignedUnits->first(
                static fn (Gudang $g): bool => in_array($g->jenis, $preferredTypes, true),
            );

            return $preferred ?? $assignedUnits->first();
        }

        $candidates = Gudang::query()
            ->select(['id', 'kode', 'nama', 'jenis', 'is_aktif'])
            ->where('is_aktif', true)
            ->whereIn('jenis', $unitTypes)
            ->orderBy('nama')
            ->get();

        if (is_numeric($selectedGudangId)) {
            $selectedGudang = $candidates->firstWhere('id', (int) $selectedGudangId);
            if ($selectedGudang !== null) {
                return $selectedGudang;
            }
        }

        return $candidates->first(
            static fn (Gudang $g): bool => in_array($g->jenis, $preferredTypes, true),
        ) ?? $candidates->first();
    }

    public function resolveGudangForTransfer(User $user): Gudang
    {
        abort_unless($user->can('pemindahan.terima'), 403);

        $query = Gudang::query()
            ->select(['id', 'kode', 'nama', 'jenis', 'is_aktif'])
            ->where('is_aktif', true)
            ->whereIn('jenis', ['unit_bisnis', 'gudang_umum', 'gudang_sukahati']);

        if (! $user->hasAnyRole(['Super Admin', 'super_admin'])) {
            $query->whereHas('users', static fn (Builder $users): Builder => $users->whereKey($user->getKey()));
        }

        $activeGudangId = session('active_gudang_id');
        $gudang = is_numeric($activeGudangId)
            ? (clone $query)->whereKey((int) $activeGudangId)->first()
            : null;

        $gudang ??= $query->orderBy('nama')->first();

        abort_unless($gudang, 403, 'Pilih gudang unit aktif yang ditugaskan kepada Anda.');

        return $gudang;
    }
}
