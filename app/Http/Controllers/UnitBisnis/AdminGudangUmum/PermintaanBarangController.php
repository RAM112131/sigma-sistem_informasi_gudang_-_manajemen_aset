<?php

declare(strict_types=1);

namespace App\Http\Controllers\UnitBisnis\AdminGudangUmum;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitBisnis\AdminGudangUmum\IndexPermintaanBarangRequest;
use App\Http\Requests\UnitBisnis\AdminGudangUmum\SearchPermintaanBarangRequest;
use App\Http\Requests\UnitBisnis\AdminGudangUmum\StorePermintaanBarangRequest;
use App\Models\User;
use App\Services\UnitBisnis\AdminGudangUmumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class PermintaanBarangController extends Controller
{
    public function __construct(
        private readonly AdminGudangUmumService $adminService,
    ) {}

    public function index(IndexPermintaanBarangRequest $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $filters = $request->validated();

        return view('unit-bisnis.admin-gudang-umum.permintaan.index', [
            ...$this->adminService->getPermintaanPageData(
                $user,
                $filters['gudang_id'] ?? session('active_gudang_id'),
                $filters,
            ),
            'canCreate' => $user->can('permintaan_barang.create'),
        ]);
    }

    public function cariBarang(SearchPermintaanBarangRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $validated = $request->validated();

        $barangs = $this->adminService->searchPermintaanBarang(
            $user,
            session('active_gudang_id'),
            $validated['q'],
        );

        return response()->json(['data' => $barangs]);
    }

    public function store(StorePermintaanBarangRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $permintaan = $this->adminService->createPermintaanBarang($user, $request->validated());

        return redirect()
            ->route('unit-bisnis.admin-gu.permintaan.index', ['tab' => 'riwayat_permintaan'])
            ->with('success', "Permintaan {$permintaan->nomor_pr} berhasil diajukan ke Gudang Store.");
    }
}
