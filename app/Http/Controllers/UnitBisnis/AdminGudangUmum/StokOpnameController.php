<?php

declare(strict_types=1);

namespace App\Http\Controllers\UnitBisnis\AdminGudangUmum;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitBisnis\AdminGudangUmum\IndexStokOpnameRequest;
use App\Http\Requests\UnitBisnis\AdminGudangUmum\SaveStokOpnameRequest;
use App\Models\StokOpname;
use App\Models\User;
use App\Services\UnitBisnis\StokOpnameService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class StokOpnameController extends Controller
{
    public function __construct(private readonly StokOpnameService $service) {}

    public function index(IndexStokOpnameRequest $request): View
    {
        $filters = $request->validated();
        $filters['periode'] ??= StokOpnameService::defaultPeriod();
        $pageData = $this->service->getPageData($this->user($request), $filters);

        if ($pageData['activeGudang'] !== null) {
            session(['active_gudang_id' => $pageData['activeGudang']->getKey()]);
        }

        return view('unit-bisnis.admin-gudang-umum.stok-opname.index', $pageData);
    }

    public function drafts(IndexStokOpnameRequest $request): View
    {
        $pageData = $this->service->getDraftsPageData($this->user($request));

        return view('unit-bisnis.admin-gudang-umum.stok-opname.drafts', $pageData);
    }

    public function saveDraft(SaveStokOpnameRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $opname = $this->service->saveDraft(
            $this->user($request),
            $validated['gudang_id'] ?? session('active_gudang_id'),
            $validated['periode'],
            $validated['items'],
            isset($validated['snapshot_barang_id']) ? (int) $validated['snapshot_barang_id'] : null,
        );

        return $this->redirectToIndex($opname, $validated)
            ->with('success', 'Draf opname berhasil disimpan. Saldo stok belum berubah.');
    }

    public function apply(SaveStokOpnameRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $opname = $this->service->apply(
            $this->user($request),
            $validated['gudang_id'] ?? session('active_gudang_id'),
            $validated['periode'],
            $validated['items'],
        );

        return $this->redirectToIndex($opname, $validated)
            ->with('success', 'Penyesuaian stok berhasil diterapkan dan tercatat pada kartu stok.');
    }

    public function reset(IndexStokOpnameRequest $request): RedirectResponse
    {
        $filters = $request->validated();
        $periode = $filters['periode'] ?? StokOpnameService::defaultPeriod();
        $gudangId = $filters['gudang_id'] ?? session('active_gudang_id');
        $this->service->resetDraft($this->user($request), $gudangId, $periode);

        return redirect()->route('unit-bisnis.admin-gu.stok-opname.index', [
            'periode' => $periode,
            'gudang_id' => $gudangId,
            'status' => $filters['status'] ?? null,
            'q' => $filters['q'] ?? null,
            'per_page' => $filters['per_page'] ?? null,
            'page' => $filters['page'] ?? null,
        ])->with('success', 'Hitungan draf berhasil dikosongkan.');
    }

    public function pdf(IndexStokOpnameRequest $request): Response
    {
        $filters = $request->validated();
        $periode = $filters['periode'] ?? StokOpnameService::defaultPeriod();
        $opname = $this->service->appliedOpname(
            $this->user($request),
            $filters['gudang_id'] ?? session('active_gudang_id'),
            $periode,
        )->load(['gudang', 'diterapkanOleh']);

        return Pdf::loadView('unit-bisnis.admin-gudang-umum.stok-opname.pdf', compact('opname'))
            ->setPaper('a4', 'landscape')
            ->download('berita-acara-opname-'.$opname->periode->format('Ym').'.pdf');
    }

    private function user(IndexStokOpnameRequest|SaveStokOpnameRequest $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function redirectToIndex(StokOpname $opname, array $filters): RedirectResponse
    {
        return redirect()->route('unit-bisnis.admin-gu.stok-opname.index', array_filter([
            'periode' => $filters['periode'] ?? $opname->periode->format('Y-m'),
            'gudang_id' => $opname->gudang_id,
            'status' => $filters['status'] ?? null,
            'q' => $filters['q'] ?? null,
            'per_page' => $filters['per_page'] ?? null,
            'page' => $filters['page'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== ''));
    }
}
