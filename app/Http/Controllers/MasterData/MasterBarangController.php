<?php

declare(strict_types=1);

namespace App\Http\Controllers\MasterData;

use App\Exports\MasterData\MasterBarangTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\AnalyzeMasterBarangImportRequest;
use App\Http\Requests\MasterData\ConfirmMasterBarangImportRequest;
use App\Http\Requests\MasterData\ImportMasterBarangExcelRequest;
use App\Http\Requests\MasterData\RegisterMasterBarangGudangRequest;
use App\Http\Requests\MasterData\StoreMasterBarangRequest;
use App\Http\Requests\MasterData\UpdateMasterBarangRequest;
use App\Models\Gudang;
use App\Models\MasterBarang;
use App\Models\User;
use App\Services\MasterData\MasterBarangService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class MasterBarangController extends Controller
{
    public function __construct(private readonly MasterBarangService $masterBarangService) {}

    public function index(Request $request): View
    {
        if ($request->filled('gudang_id') && ! is_array($request->input('gudang_id'))) {
            $request->merge(['gudang_id' => [$request->input('gudang_id')]]);
        }

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'kategori_id' => ['nullable', 'integer'],
            'satuan_id' => ['nullable', 'integer'],
            'gudang_id' => ['nullable', 'array'],
            'gudang_id.*' => ['integer', 'distinct', 'exists:gudangs,id'],
            'status' => ['nullable', 'in:0,1'],
            'registrasi' => ['nullable', 'in:semua,terdaftar,belum'],
            'per_page' => ['nullable', 'in:8,15,20,25,50'],
            'area_gudang_id' => ['nullable', 'integer', 'exists:gudangs,id'],
            'area_periode' => ['nullable', 'date_format:Y-m'],
            'kategori_drawer' => ['nullable', 'integer', 'exists:kategori_barangs,id'],
            'drawer_search' => ['nullable', 'string', 'max:255'],
            'tab' => ['nullable', 'in:barang,kategori,satuan,area'],
            'barang_view' => ['nullable', 'in:catalog,add,import'],
        ]);
        $user = $request->user();

        return view('master-data.index', $this->masterBarangService->getPageData(
            $filters,
            $user instanceof User ? $user : null,
            $request->session()->get('active_gudang_id'),
        ));
    }

    public function importIndex(): RedirectResponse
    {
        return redirect()->route('master.barang.index', [
            'tab' => 'barang',
            'barang_view' => 'import',
        ]);
    }

    public function downloadImportTemplate(): BinaryFileResponse
    {
        return Excel::download(new MasterBarangTemplateExport, 'template_master_barang.xlsx');
    }

    public function parseImportExcel(ImportMasterBarangExcelRequest $request): StreamedResponse
    {
        $this->resolveImportWarehouses($request, $request->validated('warehouse_ids'));
        $file = $request->file('file');
        abort_unless($file instanceof UploadedFile, 422, 'Pilih file template Excel yang sudah diisi.');

        $parsed = $this->masterBarangService->readExcel($file);
        $stream = $parsed['stream'];

        return response()->stream(function () use ($stream): void {
            try {
                while (($line = fgets($stream)) !== false) {
                    echo $line;

                    if (connection_aborted()) {
                        break;
                    }
                }
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }, headers: [
            'Content-Type' => 'application/x-ndjson; charset=UTF-8',
            'Cache-Control' => 'no-store',
            'X-Import-Total' => (string) $parsed['total'],
        ]);
    }

    public function analyzeImport(AnalyzeMasterBarangImportRequest $request): JsonResponse
    {
        $warehouses = $this->resolveImportWarehouses($request, $request->validated('warehouse_ids'));
        $analysis = $this->masterBarangService->analyzeRows($request->validated('rows'), $warehouses);

        return response()->json($analysis);
    }

    public function confirmImport(ConfirmMasterBarangImportRequest $request): JsonResponse
    {
        $warehouses = $this->resolveImportWarehouses($request, $request->validated('warehouse_ids'));
        $result = $this->masterBarangService->confirmRows($request->validated('rows'), $warehouses);

        return response()->json($result);
    }

    /**
     * @param  list<int|string>  $warehouseIds
     * @return EloquentCollection<int, Gudang>
     */
    private function resolveImportWarehouses(Request $request, array $warehouseIds): EloquentCollection
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $this->masterBarangService->resolveImportWarehouses($user, $warehouseIds);
    }

    public function store(StoreMasterBarangRequest $request): RedirectResponse
    {
        $masterBarang = $this->masterBarangService->create(
            $request->validated(),
            $request->file('foto_barang'),
        );
        $redirect = $request->boolean('return_to_add')
            ? route('master.barang.index', ['tab' => 'barang', 'barang_view' => 'add'])
            : route('master.barang.index');

        return redirect($redirect)
            ->with('success', "Master barang '{$masterBarang->nama}' berhasil dibuat. Daftarkan barang ke gudang yang menggunakannya.");
    }

    public function update(UpdateMasterBarangRequest $request, MasterBarang $masterBarang): RedirectResponse
    {
        $this->masterBarangService->update($masterBarang, $request->validated());

        return redirect()->route('master.barang.index')->with('success', 'Master barang berhasil diperbarui.');
    }

    public function register(
        RegisterMasterBarangGudangRequest $request,
        MasterBarang $masterBarang,
    ): RedirectResponse {
        $gudang = $this->masterBarangService->registerInWarehouseById(
            $masterBarang,
            (int) $request->validated('gudang_id'),
            $request->validated('kode_lokal'),
            (int) $request->validated('minimum_stok', 0),
        );

        return redirect()->route('master.barang.index')
            ->with('success', "Barang berhasil didaftarkan ke {$gudang->nama}.");
    }

    public function toggle(MasterBarang $masterBarang): RedirectResponse
    {
        $masterBarang = $this->masterBarangService->toggle($masterBarang);
        $status = $masterBarang->is_aktif ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('master.barang.index')
            ->with('success', "Master barang {$masterBarang->nama} berhasil {$status}.");
    }

    public function destroy(MasterBarang $masterBarang): RedirectResponse
    {
        $name = $this->masterBarangService->delete($masterBarang);

        return redirect()->route('master.barang.index')
            ->with('success', "Master barang '{$name}' berhasil dihapus.");
    }
}
