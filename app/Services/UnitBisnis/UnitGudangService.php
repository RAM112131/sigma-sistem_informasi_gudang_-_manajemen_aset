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
        return $user->gudang()
            ->select(['gudangs.id', 'gudangs.kode', 'gudangs.nama', 'gudangs.jenis'])
            ->where('gudangs.is_aktif', true)
            ->whereIn('gudangs.jenis', ['unit_bisnis', 'gudang_sukahati', 'gudang_umum'])
            ->orderBy('gudangs.nama')
            ->get();
    }

    /**
     * @param  array<int, string>  $preferredTypes
     */
    public function resolveActiveGudang(
        User $user,
        int|string|null $selectedGudangId,
        array $preferredTypes,
    ): ?Gudang {
        $query = Gudang::query()
            ->select(['id', 'kode', 'nama', 'jenis', 'is_aktif'])
            ->where('is_aktif', true)
            ->whereIn('jenis', ['unit_bisnis', 'gudang_sukahati', 'gudang_umum']);

        if (! $user->hasAnyRole(['Super Admin', 'super_admin'])) {
            $query->whereHas('users', static fn (Builder $users): Builder => $users->whereKey($user->getKey()));
        }

        if (is_numeric($selectedGudangId)) {
            $selectedGudang = (clone $query)->whereKey((int) $selectedGudangId)->first();
            if ($selectedGudang !== null) {
                return $selectedGudang;
            }
        }

        $preferredGudang = (clone $query)
            ->whereIn('jenis', $preferredTypes)
            ->orderBy('nama')
            ->first();

        return $preferredGudang ?? $query->orderBy('nama')->first();
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
