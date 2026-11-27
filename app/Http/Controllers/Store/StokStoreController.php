<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\AnalyzeStoreStockImportRequest;
use App\Http\Requests\Store\FilterStokStoreRequest;
use App\Http\Requests\Store\ProcessStoreStockImportRequest;
use App\Models\Barang;
use App\Models\Gudang;
use App\Services\Shared\ActiveGudangResolver;
use App\Services\Store\StokService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class StokStoreController extends Controller
{
    public function __construct(
        private readonly StokService $stokService,
        private readonly ActiveGudangResolver $gudangResolver,
    ) {}

    public function index(FilterStokStoreRequest $request): View
    {
        $gudangStore = $this->resolveStoreGudang($request, ['store_stok.read', 'store.view', 'store.read']);
        $filters = $request->validated();
        $search = trim($filters['search'] ?? '');
        $searchInputKey = 'search';
        if ($search === '') {
            $search = trim($filters['q'] ?? '');
            $searchInputKey = 'q';
        }
        $kategoriId = $request->query('kategori_id');
        $statusFilter = $request->query('status');
        $activeTab = $request->query('tab', 'stok_saat_ini');
        $listingData = $this->stokService->getListingData($gudangStore, [
            'search' => $filters['search'] ?? null,
            'q' => $filters['q'] ?? null,
            'search_key' => $searchInputKey,
            'kategori_id' => is_scalar($kategoriId) ? $kategoriId : null,
            'status' => is_string($statusFilter) ? $statusFilter : null,
        ]);

        return view('store.stok.index', [
            ...$listingData,
            'search' => $search,
            'kategoriId' => $kategoriId,
            'statusFilter' => $statusFilter,
            'activeTab' => $activeTab,
            'gudangStore' => $gudangStore,
        ]);
    }

    /**
     * Download CSV template for Import Data Awal.
     */
    public function downloadImportTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_import_stok_awal_store.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['kode_barang', 'nama_barang', 'kategori', 'satuan', 'qty', 'kondisi', 'catatan']);
            fputcsv($handle, ['STR-001', 'Kardus Box Store 30x20', 'Kemasan Store', 'pcs', '250', 'baik', 'Stok fisik opname awal']);
            fputcsv($handle, ['STR-002', 'Plastik HD Bening Roll', 'Kemasan Store', 'roll', '40', 'baik', 'Stok fisik opname awal']);
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Analyze the current grid rows without writing to the database.
     */
    public function analyzeImport(AnalyzeStoreStockImportRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $gudangStore = $this->resolveStoreGudang($request, ['store_stok.create', 'stok_awal.import']);

        return response()->json(
            $this->stokService->analyzeImportRows(
                $validated['rows'],
                (int) $gudangStore->id,
            )
        );
    }

    /**
     * Step 9 of wizard: Import validated JSON rows into database.
     * Only processes valid non-duplicate rows; existing items are skipped.
     */
    public function processImport(ProcessStoreStockImportRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $gudangStore = $this->resolveStoreGudang($request, ['store_stok.create', 'stok_awal.import']);

        $result = $this->stokService->importRowsInChunks(
            $validated['rows'],
            (int) $gudangStore->id,
            (int) Auth::id(),
            $validated['tanggal_acuan'],
            $validated['catatan_umum'] ?? null,
        );

        return response()->json([
            'success' => true,
            'queued' => false,
            ...$result,
        ]);
    }

    /**
     * Display Kartu Stok mutasi per barang.
     */
    public function kartuStok(Request $request, Barang $barang): JsonResponse
    {
        $gudangStore = $this->resolveStoreGudang($request, ['store_stok.read', 'store.view', 'store.read']);

        return response()->json($this->stokService->getStockCardData($barang, $gudangStore));
    }

    /**
     * @param  string|array<int, string>  $permission
     */
    private function resolveStoreGudang(Request $request, string|array $permission): Gudang
    {
        return $this->gudangResolver->resolveStoreStock($request, $permission);
    }
}
