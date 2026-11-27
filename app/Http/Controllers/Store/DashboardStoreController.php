<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Shared\ActiveGudangResolver;
use App\Services\Store\DashboardStoreService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardStoreController extends Controller
{
    public function __construct(
        private readonly DashboardStoreService $dashboardService,
        private readonly ActiveGudangResolver $gudangResolver,
    ) {}

    /**
     * Display Dashboard Gudang Store (Armada / Holding).
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $gudangStore = $this->gudangResolver->resolveStore($request, [
            'store_stok.read',
            'store_penerimaan.read',
            'store_pengeluaran.read',
            'store_laporan.read',
            'store.view',
            'store.read',
        ]);

        $dashboardData = $this->dashboardService->getDashboardData($gudangStore);

        return view('store.dashboard', [
            'user' => $user,
            'gudangStore' => $gudangStore,
            ...$dashboardData,
        ]);
    }
}
