<?php

declare(strict_types=1);

namespace App\Services\Shared;

use App\Models\Gudang;
use App\Models\User;
use App\Services\Store\GudangStoreService;
use App\Services\UnitBisnis\UnitGudangService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final readonly class ActiveGudangResolver
{
    public function __construct(
        private GudangStoreService $gudangStoreService,
        private UnitGudangService $unitGudangService,
    ) {}

    /**
     * Resolve the active Store warehouse for standard Store workflows and persist it in session.
     *
     * @param  array<int, string>  $permissions
     * @param  array<int, string>  $warehouseTypes
     */
    public function resolveStore(
        Request $request,
        array $permissions,
        array $warehouseTypes = ['holding', 'global_master', 'gudang_store'],
        bool $allowQueryOverride = true,
    ): Gudang {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $requestedGudangId = $allowQueryOverride ? $request->query('gudang_id') : null;
        $sessionGudangId = $request->session()->get('active_gudang_id');
        $superAdminFallbackId = ! $requestedGudangId
            && $user->hasAnyRole(['Super Admin', 'super_admin'])
                ? $sessionGudangId
                : null;
        $selectedGudangId = $requestedGudangId
            ?: ($superAdminFallbackId === null ? $sessionGudangId : null);

        $gudang = $this->gudangStoreService->resolveAssignedWarehouse(
            $user,
            $selectedGudangId,
            $warehouseTypes,
            $permissions,
            $superAdminFallbackId,
        );

        $request->session()->put('active_gudang_id', $gudang->id);

        return $gudang;
    }

    /**
     * Resolve the active Store warehouse for Stock/Catalog views where global/holding fallback may apply.
     *
     * @param  string|array<int, string>  $permissions
     */
    public function resolveStoreStock(Request $request, string|array $permissions): Gudang
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $context = $this->gudangStoreService->resolveStockWarehouse(
            $user,
            $request->query('gudang_id', $request->session()->get('active_gudang_id')),
            (array) $permissions,
        );

        $gudang = $context['warehouse'];
        if ($context['remember']) {
            $request->session()->put('active_gudang_id', $gudang->id);
        }

        return $gudang;
    }

    /**
     * Resolve the active Unit Bisnis warehouse and persist it in session.
     */
    public function resolveUnit(Request $request): Gudang
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $activeGudang = $this->unitGudangService->resolveActiveGudang(
            $user,
            $request->query('gudang_id', $request->session()->get('active_gudang_id')),
            ['unit_bisnis', 'gudang_sukahati', 'gudang_umum'],
        );

        abort_unless($activeGudang !== null, 403, 'Anda belum ditugaskan ke Gudang Unit aktif.');
        $request->session()->put('active_gudang_id', $activeGudang->getKey());

        return $activeGudang;
    }

    /**
     * Resolve any active assigned warehouse for Quick-Add operations.
     *
     * @throws ValidationException
     */
    public function resolveForQuickAdd(Request $request): Gudang
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $selectedGudangId = (int) $request->session()->get('active_gudang_id', 0);
        $assignedGudangs = $user->gudang()
            ->where('gudangs.is_aktif', true)
            ->orderBy('gudangs.nama')
            ->get(['gudangs.id', 'gudangs.nama', 'gudangs.jenis', 'gudangs.is_aktif']);

        $gudang = $selectedGudangId > 0
            ? $assignedGudangs->firstWhere('id', $selectedGudangId)
            : null;
        $gudang ??= $assignedGudangs->first();

        if (! $gudang && $user->hasAnyRole(['Super Admin', 'super_admin'])) {
            $gudang = Gudang::query()
                ->whereIn('jenis', ['holding', 'global_master', 'gudang_store'])
                ->where('is_aktif', true)
                ->first();
        }

        if (! $gudang) {
            throw ValidationException::withMessages([
                'gudang_id' => 'Pilih gudang aktif yang telah ditetapkan ke akun Anda sebelum menggunakan Quick-Add.',
            ]);
        }

        return $gudang;
    }
}
