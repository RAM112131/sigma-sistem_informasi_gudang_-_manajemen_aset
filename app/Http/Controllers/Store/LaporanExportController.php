<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Exports\BarangKeluarExport;
use App\Exports\BarangMasukExport;
use App\Exports\PembelianTunaiTransferExport;
use App\Exports\StokExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\LaporanExportRequest;
use App\Http\Requests\Store\LaporanPreviewRequest;
use App\Models\Gudang;
use App\Models\User;
use App\Services\Laporan\LaporanPreviewService;
use App\Services\Laporan\LaporanQueryService;
use App\Services\Store\GudangStoreService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class LaporanExportController extends Controller
{
    private const JENIS_LAPORAN = [
        'stok',
        'barang_masuk',
        'barang_keluar',
        'pembelian_tunai_transfer',
    ];

    public function __construct(private readonly GudangStoreService $gudangStoreService) {}

    public function preview(
        LaporanPreviewRequest $request,
        string $jenis,
        LaporanPreviewService $previewService,
        LaporanQueryService $queryService,
    ): View|RedirectResponse {
        abort_unless(in_array($jenis, self::JENIS_LAPORAN, true), 404);

        $data = $request->validated();
        if (! $request->ajax()) {
            return redirect()->route('store.laporan.index', [
                'periode' => $data['periode'],
                'jenis' => $jenis,
                'tanggal' => (int) $data['tanggal'],
                'hanya_perlu_restock' => $request->boolean('hanya_perlu_restock') ? 1 : null,
            ]);
        }

        $gudang = $this->resolveStoreGudang($request);
        $preview = $previewService->preview(
            $gudang,
            $jenis,
            $data['periode'],
            (int) $data['tanggal'],
            $request->boolean('hanya_perlu_restock'),
            $queryService,
        );
        $jumlahHari = $jenis === 'stok'
            ? $queryService->jumlahHariTersediaDalamPeriode($data['periode'])
            : Carbon::parse($data['periode'].'-01')->daysInMonth;

        return view('store.laporan._preview', [
            ...$preview,
            'jenis' => $jenis,
            'periode' => $data['periode'],
            'tanggal' => (int) $data['tanggal'],
            'jumlahHari' => $jumlahHari,
            'hanyaPerluRestock' => $request->boolean('hanya_perlu_restock'),
            'gudang' => $gudang,
            'namaFile' => sprintf('laporan-%s-%s-%s.xlsx', $jenis, $data['periode'], $gudang->id),
        ]);
    }

    public function export(
        LaporanExportRequest $request,
        string $jenis,
    ): BinaryFileResponse {
        abort_unless(in_array($jenis, self::JENIS_LAPORAN, true), 404);

        $data = $request->validated();
        $gudang = $this->resolveStoreGudang($request);
        $periode = $data['periode'];
        $export = match ($jenis) {
            'stok' => new StokExport(
                $gudang,
                $periode,
                $request->boolean('hanya_perlu_restock'),
            ),
            'barang_masuk' => new BarangMasukExport($gudang, $periode),
            'barang_keluar' => new BarangKeluarExport($gudang, $periode),
            'pembelian_tunai_transfer' => new PembelianTunaiTransferExport($gudang, $periode),
        };

        $namaFile = sprintf('laporan-%s-%s-%s.xlsx', $jenis, $periode, $gudang->id);

        return Excel::download($export, $namaFile);
    }

    private function resolveStoreGudang(Request $request): Gudang
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $gudang = $this->gudangStoreService->resolveAssignedWarehouse(
            $user,
            $request->query('gudang_id'),
            ['holding', 'global_master', 'gudang_store'],
            [],
            session('active_gudang_id'),
        );
        session(['active_gudang_id' => $gudang->id]);

        return $gudang;
    }
}
