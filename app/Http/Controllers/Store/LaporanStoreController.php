<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Exports\StoreReportExport;
use App\Http\Controllers\Controller;
use App\Models\Gudang;
use App\Models\User;
use App\Services\Store\GudangStoreService;
use App\Services\Store\LaporanStoreService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

final class LaporanStoreController extends Controller
{
    public function __construct(
        private readonly LaporanStoreService $laporanService,
        private readonly GudangStoreService $gudangStoreService,
    ) {}

    /**
     * Display the monthly export menu for Store reports.
     */
    public function index(Request $request): View
    {
        $periodeDefault = now()->format('Y-m');
        $periodeDiminta = $request->query('periode');
        $periode = is_string($periodeDiminta)
            && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $periodeDiminta) === 1
            && $periodeDiminta <= $periodeDefault
                ? $periodeDiminta
                : $periodeDefault;
        $jenisDiminta = $request->query('jenis');
        $jenisAktif = is_string($jenisDiminta)
            && in_array($jenisDiminta, ['stok', 'barang_masuk', 'barang_keluar', 'pembelian_tunai_transfer'], true)
                ? $jenisDiminta
                : null;
        $tanggalDiminta = $request->query('tanggal');
        $tanggalAktif = is_scalar($tanggalDiminta) && filter_var($tanggalDiminta, FILTER_VALIDATE_INT) !== false
            ? (int) $tanggalDiminta
            : null;
        $hanyaPerluRestock = $request->boolean('hanya_perlu_restock');

        $this->resolveStoreGudang($request);

        return view('store.laporan.index', compact('periode', 'jenisAktif', 'tanggalAktif', 'hanyaPerluRestock'));
    }

    public function exportExcel(Request $request)
    {
        [$query, $headings] = $this->exportQueryData($request);
        $filename = 'laporan-store-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new StoreReportExport($query, $headings), $filename);
    }

    public function exportPdf(Request $request)
    {
        [$rows, $headings, $title] = $this->exportData($request);

        return Pdf::loadView('store.laporan.pdf', compact('rows', 'headings', 'title'))
            ->setPaper('a4', 'landscape')
            ->download('laporan-store-'.now()->format('Ymd-His').'.pdf');
    }

    /**
     * @return array{0: Collection<int, array<int, mixed>>, 1: array<int, string>, 2: string}
     */
    private function exportData(Request $request): array
    {
        $type = $request->query('tipe', 'stok_akhir');
        $categoryId = $request->query('kategori_id');
        $start = $request->query('tanggal_mulai', date('Y-m-01'));
        $end = $request->query('tanggal_selesai', date('Y-m-d'));
        $store = $this->resolveStoreGudang($request);

        return $this->laporanService->exportData($store, $type, $categoryId, $start, $end);
    }

    /**
     * @return array{0: Builder, 1: array<int, string>}
     */
    private function exportQueryData(Request $request): array
    {
        $type = $request->query('tipe', 'stok_akhir');
        $categoryId = $request->query('kategori_id');
        $start = $request->query('tanggal_mulai', date('Y-m-01'));
        $end = $request->query('tanggal_selesai', date('Y-m-d'));
        $store = $this->resolveStoreGudang($request);

        return $this->laporanService->exportQueryData($store, $type, $categoryId, $start, $end);
    }

    private function resolveStoreGudang(Request $request): Gudang
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $gudang = $this->gudangStoreService->resolveAssignedWarehouse(
            $user,
            $request->query('gudang_id', session('active_gudang_id')),
            ['holding', 'global_master', 'gudang_store'],
        );
        session(['active_gudang_id' => $gudang->id]);

        return $gudang;
    }
}
