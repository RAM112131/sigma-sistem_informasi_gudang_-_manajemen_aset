<?php

declare(strict_types=1);

namespace App\Http\Controllers\UnitBisnis\RecordingGudangUmum;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UnitBisnis\RecordingGudangUmumService;
use App\Services\UnitBisnis\UnitGudangService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly RecordingGudangUmumService $recordingService,
        private readonly UnitGudangService $unitGudangService,
    ) {}

    /**
     * Display the operational daily task dashboard for Recording Gudang Umum.
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        if (in_array($request->query('panel'), ['cek-stok', 'stok-barang', 'stok'], true)) {
            return redirect()->route('unit-bisnis.recording-gu.stok.index');
        }

        $activeGudang = $this->unitGudangService->resolveActiveGudang(
            $user,
            $request->query('gudang_id', session('active_gudang_id')),
            ['unit_bisnis', 'gudang_sukahati', 'gudang_umum'],
        );

        if ($activeGudang !== null) {
            session(['active_gudang_id' => $activeGudang->getKey()]);
        }

        return view('unit-bisnis.recording-gudang-umum.dashboard', [
            'user' => $user,
            'activeGudang' => $activeGudang,
            ...$this->recordingService->getDashboardData(),
        ]);
    }
}
