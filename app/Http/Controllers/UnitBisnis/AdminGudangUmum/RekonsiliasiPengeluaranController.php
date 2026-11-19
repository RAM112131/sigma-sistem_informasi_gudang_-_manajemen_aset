<?php

declare(strict_types=1);

namespace App\Http\Controllers\UnitBisnis\AdminGudangUmum;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitBisnis\AdminGudangUmum\IndexRekonsiliasiPengeluaranRequest;
use App\Http\Requests\UnitBisnis\AdminGudangUmum\SaveRekonsiliasiPengeluaranRequest;
use App\Models\User;
use App\Services\UnitBisnis\RekonsiliasiPengeluaranService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class RekonsiliasiPengeluaranController extends Controller
{
    public function __construct(private readonly RekonsiliasiPengeluaranService $service) {}

    public function index(IndexRekonsiliasiPengeluaranRequest $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $filters = $request->validated();
        $selectedGudangId = $filters['gudang_id'] ?? session('active_gudang_id');
        $pageData = $this->service->getPageData($user, $selectedGudangId, $filters);

        if ($pageData['activeGudang'] !== null) {
            session(['active_gudang_id' => $pageData['activeGudang']->getKey()]);
        }

        return view('unit-bisnis.admin-gudang-umum.rekonsiliasi-pengeluaran.index', $pageData);
    }

    public function saveDraft(
        SaveRekonsiliasiPengeluaranRequest $request,
        int $pengeluaran,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $validated = $request->validated();
        $selectedGudangId = $validated['gudang_id'] ?? session('active_gudang_id');
        $this->service->saveDraft($user, $selectedGudangId, $pengeluaran, $validated);

        return $this->redirectToIssue($selectedGudangId, $pengeluaran)
            ->with('success', 'Draf rekonsiliasi berhasil disimpan. Stok belum berubah.');
    }

    public function verify(
        SaveRekonsiliasiPengeluaranRequest $request,
        int $pengeluaran,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $validated = $request->validated();
        $selectedGudangId = $validated['gudang_id'] ?? session('active_gudang_id');
        $this->service->verify($user, $selectedGudangId, $pengeluaran, $validated);

        return $this->redirectToIssue($selectedGudangId, $pengeluaran)
            ->with('success', 'BPB berhasil diverifikasi. Qty keluar, retur, dan pemakaian bersih telah diperbarui.');
    }

    private function redirectToIssue(int|string|null $selectedGudangId, int $pengeluaran): RedirectResponse
    {
        return redirect()->route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.index', [
            'gudang_id' => $selectedGudangId,
            'pengeluaran_id' => $pengeluaran,
            'status' => 'semua',
        ]);
    }
}
