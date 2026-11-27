<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\FilterPenerimaanStoreRequest;
use App\Http\Requests\Store\StorePenerimaanStoreRequest;
use App\Models\Gudang;
use App\Services\Shared\ActiveGudangResolver;
use App\Services\Store\PenerimaanStoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class PenerimaanStoreController extends Controller
{
    public function __construct(
        private readonly PenerimaanStoreService $penerimaanService,
        private readonly ActiveGudangResolver $gudangResolver,
    ) {}

    /**
     * Display listing & input form for Penerimaan Barang Supplier (Gudang Store).
     */
    public function index(FilterPenerimaanStoreRequest $request): View
    {
        $activeTab = $request->query('tab');
        $activeTab = is_string($activeTab) ? $activeTab : 'penerimaan_baru';
        $supplier = $request->query('supplier');
        $supplier = is_string($supplier) ? trim($supplier) : '';

        $dateFilters = $request->validated();

        $search = trim($dateFilters['search'] ?? ($dateFilters['q'] ?? ''));
        $gudangStore = $this->resolveStoreGudang($request, [
            'store_penerimaan.read',
            'store_penerimaan.create',
            'store.view',
            'store.read',
            'store.penerimaan',
        ]);

        $listingData = $this->penerimaanService->getListingData(
            $gudangStore,
            [
                'search' => $search,
                'supplier' => $supplier,
                'date_from' => $dateFilters['date_from'] ?? null,
                'date_to' => $dateFilters['date_to'] ?? null,
            ]
        );

        return view('store.penerimaan.index', array_merge($listingData, [
            'gudangStore' => $gudangStore,
            'activeTab' => $activeTab,
            'search' => $search,
        ]));
    }

    /**
     * Store a daily goods receipt document that may contain multiple source groups.
     */
    public function store(StorePenerimaanStoreRequest $request): RedirectResponse
    {
        $gudangStore = $this->resolveStoreGudang($request, [
            'store_penerimaan.create',
            'store.penerimaan',
            'store.create',
        ]);
        $tanpaSj = $request->boolean('tanpa_surat_jalan');
        $validated = $request->validated();

        $sanitizedSourceGroups = $this->penerimaanService->sanitizeSourceGroups($tanpaSj, $validated);

        $fotoPath = null;
        if ($request->hasFile('foto_dokumen')) {
            $fotoPath = $request->file('foto_dokumen')->store('penerimaan_store', 'public');
        }

        $receiptResult = $this->penerimaanService->createReceipts(
            $validated,
            $sanitizedSourceGroups,
            $gudangStore,
            $fotoPath,
            (int) Auth::id()
        );

        $nomorPenerimaan = $receiptResult['numbers'];
        $itemCount = $receiptResult['item_count'];

        return redirect()->route('store.penerimaan.index', ['tab' => 'riwayat_penerimaan'])
            ->with('success', 'Penerimaan Barang berhasil disimpan dengan nomor RCV: '.implode(', ', $nomorPenerimaan)." ({$itemCount} baris barang); stok ter-update!");
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function resolveStoreGudang(Request $request, array $permissions): Gudang
    {
        return $this->gudangResolver->resolveStore($request, $permissions);
    }
}
