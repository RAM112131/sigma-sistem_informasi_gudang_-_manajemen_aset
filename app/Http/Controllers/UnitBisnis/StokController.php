<?php

declare(strict_types=1);

namespace App\Http\Controllers\UnitBisnis;

use App\Exports\UnitBisnis\UnitStockImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\UnitBisnis\FilterUnitStockRequest;
use App\Http\Requests\UnitBisnis\RecordingGudangUmum\AnalyzeUnitStockImportRequest;
use App\Http\Requests\UnitBisnis\RecordingGudangUmum\ParseUnitStockExcelRequest;
use App\Http\Requests\UnitBisnis\RecordingGudangUmum\ProcessUnitStockImportRequest;
use App\Http\Requests\UnitBisnis\RecordingGudangUmum\SearchUnitStockImportRequest;
use App\Http\Requests\UnitBisnis\UpdateUnitStockRequest;
use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\Satuan;
use App\Models\User;
use App\Services\Shared\ActiveGudangResolver;
use App\Services\Store\StokService;
use App\Services\UnitBisnis\UnitGudangService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class StokController extends Controller
{
    public function __construct(
        protected readonly StokService $stokService,
        protected readonly UnitGudangService $unitGudangService,
        protected readonly ActiveGudangResolver $gudangResolver,
    ) {}

    public function index(FilterUnitStockRequest $request): View
    {
        $activeGudang = $this->resolveUnitGudang($request);
        $user = $request->user();

        $filters = $request->validated();
        $search = trim($filters['search'] ?? '');
        $searchInputKey = 'search';
        if ($search === '') {
            $search = trim($filters['q'] ?? '');
            $searchInputKey = 'q';
        }
        $kategoriId = $filters['kategori_id'] ?? null;
        $statusFilter = $filters['status'] ?? null;
        $activeTab = $request->query('tab', 'stok_saat_ini');

        $listingData = $this->stokService->getListingData($activeGudang, [
            'search' => $filters['search'] ?? null,
            'q' => $filters['q'] ?? null,
            'search_key' => $searchInputKey,
            'kategori_id' => is_scalar($kategoriId) ? $kategoriId : null,
            'status' => is_string($statusFilter) ? $statusFilter : null,
        ]);

        $gudangUnits = $user instanceof User
            ? $this->unitGudangService->getAssignedGudangUnits($user)
            : collect();

        $stockEditSatuanList = $listingData['satuanList']
            ->filter(static fn (Satuan $satuan): bool => $satuan->gudang_id === null || (int) $satuan->gudang_id === (int) $activeGudang->id)
            ->values();

        return view('unit-bisnis.stok.index', [
            ...$listingData,
            'search' => $search,
            'kategoriId' => $kategoriId,
            'statusFilter' => $statusFilter,
            'activeTab' => $activeTab,
            'activeGudang' => $activeGudang,
            'gudangUnits' => $gudangUnits,
            'importCatalogItems' => $this->stokService->getImportCatalogItems($activeGudang),
            'stockSummary' => $this->stokService->getStockSummary($activeGudang),
            'stockEditSatuanList' => $stockEditSatuanList,
        ]);
    }

    public function edit(Request $request, Barang $barang): View
    {
        $activeGudang = $this->resolveUnitGudang($request);
        $this->authorizeStockMutation(
            $request,
            $barang,
            $activeGudang,
            'stokunitbisnis.edit',
            'stokunitbisnis.update',
            ['Super Admin', 'super_admin', 'Admin Gudang Umum', 'admin_gudang_umum', 'Recording Gudang Umum', 'recording_gudang_umum'],
        );

        return view('unit-bisnis.stok.edit', [
            'activeGudang' => $activeGudang,
            'barang' => $barang->load(['kategori', 'satuan']),
            'kategoriList' => KategoriBarang::query()
                ->forGudang((int) $activeGudang->id)
                ->where('is_aktif', true)
                ->orderBy('nama')
                ->get(['id', 'nama']),
            'satuanList' => Satuan::query()
                ->forGudang((int) $activeGudang->id)
                ->where('is_aktif', true)
                ->orderBy('nama')
                ->get(['id', 'nama']),
        ]);
    }

    public function update(UpdateUnitStockRequest $request, Barang $barang): RedirectResponse
    {
        $activeGudang = $this->resolveUnitGudang($request);
        $this->authorizeStockMutation(
            $request,
            $barang,
            $activeGudang,
            'stokunitbisnis.edit',
            'stokunitbisnis.update',
            ['Super Admin', 'super_admin', 'Admin Gudang Umum', 'admin_gudang_umum', 'Recording Gudang Umum', 'recording_gudang_umum'],
        );

        $validated = $request->validated();

        $this->stokService->updateUnitBarang($barang, $validated);

        return redirect()
            ->route('unit-bisnis.stok.index', ['tab' => 'stok_saat_ini', 'gudang_id' => $activeGudang->id])
            ->with('success', "Data barang {$barang->nama} berhasil diperbarui. Saldo fisik tidak berubah.");
    }

    public function destroy(Request $request, Barang $barang): RedirectResponse
    {
        $activeGudang = $this->resolveUnitGudang($request);
        $this->authorizeStockMutation(
            $request,
            $barang,
            $activeGudang,
            'stokunitbisnis.delete',
            null,
            ['Super Admin', 'super_admin', 'Admin Gudang Umum', 'admin_gudang_umum'],
        );

        $this->stokService->deleteUnitBarang($barang, (int) $activeGudang->id);

        return redirect()
            ->route('unit-bisnis.stok.index', ['tab' => 'stok_saat_ini', 'gudang_id' => $activeGudang->id])
            ->with('success', "{$barang->nama} berhasil dihapus dari daftar stok. Riwayat transaksi tetap disimpan.");
    }

    public function downloadExport(FilterUnitStockRequest $request): StreamedResponse
    {
        $activeGudang = $this->resolveUnitGudang($request);
        $filters = $request->validated();
        $searchInputKey = trim($filters['search'] ?? '') === '' ? 'q' : 'search';
        $rows = $this->stokService->getExportRows($activeGudang, [
            ...$filters,
            'search_key' => $searchInputKey,
        ]);

        return response()->streamDownload(static function () use ($rows): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                throw new \RuntimeException('Tidak dapat membuka output ekspor stok.');
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Kode Barang', 'Nama Barang', 'Kategori', 'Satuan', 'Saldo Fisik', 'Stok Minimum', 'HPP Saat Ini', 'Status']);

            foreach ($rows as $item) {
                $barang = $item['model'];
                $values = [
                    $barang->kode,
                    $barang->nama,
                    $barang->kategori?->nama ?? 'Umum',
                    $barang->satuan?->nama ?? '-',
                    $item['stok_aktual'],
                    $barang->minimum_stok,
                    $barang->harga_satuan,
                    $item['status_text'],
                ];

                fputcsv($handle, array_map(
                    static fn (mixed $value): string|int|float|null => self::escapeCsvCell($value),
                    $values
                ));
            }

            fclose($handle);
        }, 'stok-gudang-'.$activeGudang->id.'-'.now()->format('Ymd').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Download Excel template for Import Data Awal Gudang Unit.
     */
    public function downloadImportTemplate(): BinaryFileResponse
    {
        return Excel::download(new UnitStockImportTemplateExport, 'template_import_stok_awal_unit.xlsx');
    }

    /**
     * Parse uploaded Excel/CSV file into grid rows for Import Data Awal.
     */
    public function parseImportExcel(ParseUnitStockExcelRequest $request): JsonResponse
    {
        $file = $request->file('file');
        abort_unless($file instanceof UploadedFile, 422, 'Pilih file Excel atau CSV yang valid.');

        return response()->json($this->stokService->parseImportExcelRows($file));
    }

    /**
     * Search warehouse & master catalog items via vector search for import grid autocomplete.
     */
    public function searchImportCatalog(SearchUnitStockImportRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $activeGudang = $this->resolveUnitGudang($request);

        $items = $this->stokService->searchImportCatalog(
            $activeGudang,
            $validated['q'],
            $validated['field'] ?? null,
        );

        return response()->json([
            'items' => $items,
        ]);
    }

    /**
     * Analyze uploaded rows before inserting.
     */
    public function analyzeImport(AnalyzeUnitStockImportRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $activeGudang = $this->resolveUnitGudang($request);

        return response()->json(
            $this->stokService->analyzeImportRows(
                $validated['rows'],
                (int) $activeGudang->id,
            )
        );
    }

    /**
     * Import validated rows into database.
     */
    public function processImport(ProcessUnitStockImportRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $activeGudang = $this->resolveUnitGudang($request);

        $result = $this->stokService->importRowsInChunks(
            $validated['rows'],
            (int) $activeGudang->id,
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
     * Display Kartu Stok mutasi chronological for this item.
     */
    public function kartuStok(Request $request, Barang $barang): JsonResponse
    {
        $activeGudang = $this->resolveUnitGudang($request);

        return response()->json($this->stokService->getStockCardData($barang, $activeGudang));
    }

    protected function resolveUnitGudang(Request $request): Gudang
    {
        return $this->gudangResolver->resolveUnit($request);
    }

    /**
     * @param  list<string>  $roles
     */
    private function authorizeStockMutation(
        Request $request,
        Barang $barang,
        Gudang $activeGudang,
        string $permission,
        ?string $alternativePermission,
        array $roles,
    ): void {
        $user = $request->user();
        $authorized = $user instanceof User
            && ($user->hasAnyRole($roles)
                || $user->can($permission)
                || ($alternativePermission !== null && $user->can($alternativePermission)));

        abort_unless($authorized, 403);
        abort_unless(
            (int) $barang->gudang_id === (int) $activeGudang->id && $barang->is_aktif,
            404,
        );
    }

    private static function escapeCsvCell(mixed $value): string|int|float|null
    {
        if (! is_string($value) || preg_match('/^\s*[=+\-@]/u', $value) !== 1) {
            return $value;
        }

        return "'".$value;
    }
}
