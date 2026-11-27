<?php

declare(strict_types=1);

/**
 * PengeluaranStoreController
 */

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\BuatPemindahanLangsungRequest;
use App\Http\Requests\Store\FilterPengeluaranStoreRequest;
use App\Http\Requests\Store\KonfirmasiKirimStoreRequest;
use App\Http\Requests\Store\ProsesPermintaanStoreRequest;
use App\Http\Requests\Store\SearchBarangStoreRequest;
use App\Http\Requests\Store\TolakPermintaanStoreRequest;
use App\Models\Gudang;
use App\Models\PermintaanBarang;
use App\Models\TransferBarang;
use App\Models\User;
use App\Services\Shared\ActiveGudangResolver;
use App\Services\Store\PengeluaranStoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class PengeluaranStoreController extends Controller
{
    public function __construct(
        private readonly PengeluaranStoreService $pengeluaranService,
        private readonly ActiveGudangResolver $gudangResolver,
    ) {}

    public function permintaanIndex(FilterPengeluaranStoreRequest $request): View
    {
        $gudangStore = $this->resolveStoreGudang([
            'store_pengeluaran.read',
            'store_pengeluaran.create',
            'store.distribusi',
        ]);
        $filters = $request->validated();

        $status = $filters['status'] ?? '';
        $search = trim($filters['search'] ?? '');

        $countMenunggu = 0;
        $countSiapProses = 0;

        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $canCreatePengeluaran = $this->pengeluaranService->canCreateTransfers($user);

        $permintaans = $this->pengeluaranService->getPermintaanListing(
            (int) $gudangStore->id,
            compact('status', 'search')
        );

        return view('store.permintaan.index', compact(
            'gudangStore',
            'status',
            'search',
            'countMenunggu',
            'countSiapProses',
            'permintaans',
            'canCreatePengeluaran'
        ));
    }

    public function index(FilterPengeluaranStoreRequest $request): View
    {
        $gudangStore = $this->resolveStoreGudang([
            'store_pengeluaran.read',
            'store_pengeluaran.create',
            'store.distribusi',
        ]);
        $filters = $request->validated();

        $tab = $filters['tab'] ?? 'pemindahan';
        if (! in_array($tab, ['pemindahan', 'permintaan', 'langsung', 'histori'], true)) {
            $tab = 'pemindahan';
        }
        $status = $filters['status'] ?? '';
        $search = trim($filters['search'] ?? ($filters['q'] ?? ''));

        $countMenunggu = 0;
        $countSiapProses = 0;

        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $canCreatePengeluaran = $this->pengeluaranService->canCreateTransfers($user);

        if (in_array($tab, ['pemindahan', 'permintaan', 'langsung'], true) && ! $canCreatePengeluaran) {
            $tab = 'histori';
        }

        $printDoc = null;
        if (session('print_sj')) {
            $printDoc = $this->pengeluaranService->getPrintDocument(
                (string) session('print_sj'),
                (int) $gudangStore->id
            );
        }

        if ($tab === 'histori') {
            $tglAwal = $filters['tgl_awal'] ?? null;
            $tglAkhir = $filters['tgl_akhir'] ?? null;

            $historiData = $this->pengeluaranService->getHistoriDocuments(
                (int) $gudangStore->id,
                compact('search', 'status', 'tglAwal', 'tglAkhir')
            );

            $paginatedDocs = $historiData['paginatedDocs'];
            $allTransfers = $historiData['allTransfers'];

            return view('store.pengeluaran.histori', compact(
                'gudangStore',
                'tab',
                'status',
                'search',
                'tglAwal',
                'tglAkhir',
                'countMenunggu',
                'countSiapProses',
                'paginatedDocs',
                'allTransfers',
                'printDoc',
                'canCreatePengeluaran'
            ));
        }

        $formData = $this->pengeluaranService->getPemindahanFormData(
            (int) $gudangStore->id,
            isset($filters['permintaan_id']) ? (int) $filters['permintaan_id'] : null
        );

        return view('store.pengeluaran.pemindahan', [
            'gudangStore' => $gudangStore,
            'tab' => $tab,
            'status' => $status,
            'countMenunggu' => $countMenunggu,
            'countSiapProses' => $countSiapProses,
            'canCreatePengeluaran' => $canCreatePengeluaran,
            'activePermintaan' => $formData['activePermintaan'],
            'allocationDetails' => $formData['allocationDetails'],
            'unitGudangs' => $formData['unitGudangs'],
            'unitUsersMap' => $formData['unitUsersMap'],
            'approvedPermintaans' => $formData['approvedPermintaans'],
            'nomorKeluar' => $formData['nomorKeluar'],
            'nomorReqLangsung' => $formData['nomorReqLangsung'],
            'transfers' => null,
            'printDoc' => $printDoc,
        ]);
    }

    public function prosesPermintaan(ProsesPermintaanStoreRequest $request, PermintaanBarang $permintaan): RedirectResponse
    {
        $gudangStore = $this->resolveStoreGudang([
            'store_pengeluaran.create',
            'store.distribusi',
        ]);
        $validated = $request->validated();

        $nomorKeluar = $this->pengeluaranService->allocateRequest(
            $permintaan,
            $validated['qty_dialokasikan'],
            $validated['nomor_keluar'] ?? null,
            $gudangStore,
            (int) Auth::id(),
        );

        return redirect()->route('store.pengeluaran.index', ['tab' => 'histori'])
            ->with('success', "Pengiriman barang untuk permintaan {$permintaan->nomor_pr} berhasil diproses dan dikirim (No. Dokumen: {$nomorKeluar}).")
            ->with('print_sj', $nomorKeluar);
    }

    public function cariBarangPemindahan(SearchBarangStoreRequest $request): JsonResponse
    {
        $gudangStore = $this->resolveStoreGudang([
            'store_pengeluaran.read',
            'store_pengeluaran.create',
            'store.distribusi',
        ]);
        $validated = $request->validated();

        $results = $this->pengeluaranService->searchBarang(trim($validated['q']), (int) $gudangStore->id);

        return response()->json(['data' => $results]);
    }

    public function buatPemindahanLangsung(BuatPemindahanLangsungRequest $request): RedirectResponse
    {
        $gudangStore = $this->resolveStoreGudang([
            'store_pengeluaran.create',
            'store.distribusi',
        ]);
        $validated = $request->validated();

        $nomorKeluar = $this->pengeluaranService->createDirectTransfer($validated, $gudangStore, (int) Auth::id());

        return redirect()->route('store.pengeluaran.index', ['tab' => 'histori'])
            ->with('success', "Pengiriman barang dokumen {$nomorKeluar} berhasil diproses dan dikirim.")
            ->with('print_sj', $nomorKeluar);
    }

    public function tolakPermintaan(TolakPermintaanStoreRequest $request, PermintaanBarang $permintaan): RedirectResponse
    {
        $gudangStore = $this->resolveStoreGudang([
            'store_pengeluaran.update',
            'store.distribusi',
        ]);
        $validated = $request->validated();

        $this->pengeluaranService->rejectRequest($permintaan, $validated['alasan_penolakan'], $gudangStore);

        return redirect()->route('store.permintaan.index')
            ->with('success', "Permintaan {$permintaan->nomor_pr} ditolak.");
    }

    public function konfirmasiKirim(KonfirmasiKirimStoreRequest $request, TransferBarang $transfer): RedirectResponse
    {
        $gudangStore = $this->resolveStoreGudang([
            'store_pengeluaran.update',
            'store.distribusi',
        ]);
        $validated = $request->validated();

        $fotoPath = null;
        if ($request->hasFile('foto_bukti')) {
            $fotoPath = $request->file('foto_bukti')->store('transfer_bukti', 'public');
        }

        $nomorBase = $this->pengeluaranService->confirmShipment(
            $transfer,
            (int) $validated['qty_dikirim'],
            $validated['supir_pengantar'] ?? null,
            $fotoPath,
            (int) Auth::id(),
            $gudangStore
        );

        return redirect()->route('store.pengeluaran.index', ['tab' => 'histori'])
            ->with('success', "Pengiriman {$transfer->nomor_sj} berhasil dikonfirmasi dikirim.")
            ->with('print_sj', $nomorBase);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function resolveStoreGudang(array $permissions): Gudang
    {
        return $this->gudangResolver->resolveStore(
            request(),
            $permissions,
            ['gudang_store'],
            false,
        );
    }
}
