<?php

declare(strict_types=1);

namespace App\Http\Controllers\UnitBisnis\AdminGudangUmum;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitBisnis\AdminGudangUmum\KonfirmasiPemindahanRequest;
use App\Models\TransferBarang;
use App\Models\User;
use App\Services\UnitBisnis\AdminGudangUmumService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PemindahanController extends Controller
{
    public function __construct(
        private readonly AdminGudangUmumService $adminService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $filters = $request->validate([
            'status' => ['nullable', 'in:dikirim,diterima'],
        ]);
        $pageData = $this->adminService->getTransferPageData(
            $user,
            $request->query('gudang_id', session('active_gudang_id')),
            $filters['status'] ?? null,
        );
        $canConfirmReceipt = $request->routeIs('transaksi.*', 'unit-bisnis.pemindahan.*');
        $listingRoute = match (true) {
            $request->routeIs('transaksi.*') => 'transaksi.pemindahan-unit.index',
            $request->routeIs('unit-bisnis.pemindahan.*') => 'unit-bisnis.pemindahan.index',
            default => 'unit-bisnis.admin-gu.pemindahan.index',
        };
        $confirmationRoute = match (true) {
            $request->routeIs('transaksi.*') => 'transaksi.pemindahan-unit.konfirmasi',
            $request->routeIs('unit-bisnis.pemindahan.*') => 'unit-bisnis.pemindahan.konfirmasi',
            default => null,
        };

        return view(
            'unit-bisnis.admin-gudang-umum.pemindahan.index',
            [
                ...$pageData,
                'canConfirmReceipt' => $canConfirmReceipt,
                'listingRoute' => $listingRoute,
                'confirmationRoute' => $confirmationRoute,
            ],
        );
    }

    public function konfirmasi(KonfirmasiPemindahanRequest $request, TransferBarang $transfer): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $this->adminService->confirmTransferForUser($request->validated(), $transfer, $user);

        $redirectRoute = match (true) {
            $request->routeIs('transaksi.*') => 'transaksi.pemindahan-unit.index',
            $request->routeIs('unit-bisnis.pemindahan.*') => 'unit-bisnis.pemindahan.index',
            default => 'unit-bisnis.admin-gu.pemindahan.index',
        };

        return redirect()->route($redirectRoute)
            ->with('success', "Penerimaan surat jalan {$transfer->nomor_sj} berhasil dicatat.");
    }
}
