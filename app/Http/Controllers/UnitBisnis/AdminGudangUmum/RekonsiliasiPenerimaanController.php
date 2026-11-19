<?php

declare(strict_types=1);

namespace App\Http\Controllers\UnitBisnis\AdminGudangUmum;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitBisnis\AdminGudangUmum\IndexRekonsiliasiPenerimaanRequest;
use App\Http\Requests\UnitBisnis\AdminGudangUmum\RecordTindakLanjutPenerimaanRequest;
use App\Http\Requests\UnitBisnis\AdminGudangUmum\SaveRekonsiliasiPenerimaanRequest;
use App\Models\User;
use App\Services\UnitBisnis\RekonsiliasiPenerimaanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class RekonsiliasiPenerimaanController extends Controller
{
    public function __construct(private readonly RekonsiliasiPenerimaanService $service) {}

    public function index(IndexRekonsiliasiPenerimaanRequest $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $filters = $request->validated();
        $selectedGudangId = $filters['gudang_id'] ?? session('active_gudang_id');
        $pageData = $this->service->getPageData($user, $selectedGudangId, $filters);

        if ($pageData['activeGudang'] !== null) {
            session(['active_gudang_id' => $pageData['activeGudang']->getKey()]);
        }

        return view('unit-bisnis.admin-gudang-umum.rekonsiliasi-penerimaan.index', $pageData);
    }

    public function verify(
        SaveRekonsiliasiPenerimaanRequest $request,
        int $penerimaan,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $validated = $request->validated();
        $selectedGudangId = $validated['gudang_id'] ?? session('active_gudang_id');
        $needsFollowUp = $this->service->verify($user, $selectedGudangId, $penerimaan, $validated);
        $status = $needsFollowUp ? 'tindak_lanjut' : 'sudah_diverifikasi';

        return $this->redirectToReceipt(
            $selectedGudangId,
            $penerimaan,
            $status,
            $validated['sumber_penerimaan'],
        )
            ->with(
                'success',
                $needsFollowUp
                    ? 'Penerimaan diverifikasi dan masuk antrean tindak lanjut karena ditemukan selisih atau barang rusak.'
                    : 'Penerimaan berhasil diverifikasi. Stok menggunakan kuantitas fisik yang telah diperiksa.',
            );
    }

    public function recordFollowUp(
        RecordTindakLanjutPenerimaanRequest $request,
        int $penerimaan,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $validated = $request->validated();
        $selectedGudangId = $validated['gudang_id'] ?? session('active_gudang_id');
        $this->service->recordFollowUp($user, $selectedGudangId, $penerimaan, $validated);

        return $this->redirectToReceipt(
            $selectedGudangId,
            $penerimaan,
            'tindak_lanjut',
            $validated['sumber_penerimaan'],
        )
            ->with('success', 'Catatan tindak lanjut atau klaim berhasil ditambahkan.');
    }

    private function redirectToReceipt(
        int|string|null $selectedGudangId,
        int $penerimaan,
        string $status,
        string $source,
    ): RedirectResponse {
        return redirect()->route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index', [
            'gudang_id' => $selectedGudangId,
            'penerimaan_id' => $penerimaan,
            'sumber_penerimaan' => $source,
            'status' => $status,
        ]);
    }
}
