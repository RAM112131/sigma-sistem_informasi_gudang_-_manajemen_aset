<?php

declare(strict_types=1);

/**
 * PengeluaranStoreController
 */

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use App\Models\PermintaanBarang;
use App\Models\TransferBarang;
use App\Models\User;
use App\Services\Store\GudangStoreService;
use App\Services\Store\PengeluaranStoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class PengeluaranStoreController extends Controller
{
    public function __construct(
        private readonly PengeluaranStoreService $pengeluaranService,
        private readonly GudangStoreService $gudangStoreService,
    ) {}

    public function permintaanIndex(Request $request): View
    {
        $gudangStore = $this->resolveStoreGudang([
            'store_pengeluaran.read',
            'store_pengeluaran.create',
            'store.distribusi',
        ]);
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['baru', 'diproses', 'selesai', 'ditolak'])],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

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

    public function index(Request $request): View
    {
        $gudangStore = $this->resolveStoreGudang([
            'store_pengeluaran.read',
            'store_pengeluaran.create',
            'store.distribusi',
        ]);
        $filters = $request->validate([
            'tab' => ['nullable', 'in:pemindahan,permintaan,langsung,histori'],
            'permintaan_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['diminta', 'dikirim', 'diterima', 'baru', 'diproses', 'selesai', 'ditolak'])],
            'search' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'tgl_awal' => ['nullable', 'date'],
            'tgl_akhir' => ['nullable', 'date'],
        ]);

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

    public function prosesPermintaan(Request $request, PermintaanBarang $permintaan): RedirectResponse
    {
        $gudangStore = $this->resolveStoreGudang([
            'store_pengeluaran.create',
            'store.distribusi',
        ]);
        $validated = $request->validate([
            'nomor_keluar' => ['nullable', 'string', 'max:60'],
            'qty_dialokasikan' => ['required', 'array', 'min:1', 'max:100'],
            'qty_dialokasikan.*' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ]);

        $nomorKeluar = $this->pengeluaranService->allocateRequest(
            $permintaan,
            $validated['qty_dialokasikan'],
            $validated['nomor_keluar'] ?? null,
            $gudangStore
        );

        return redirect()->route('store.pengeluaran.index', ['tab' => 'histori'])
            ->with('success', "Alokasi permintaan {$permintaan->nomor_pr} berhasil dibuat (No. Dokumen: {$nomorKeluar}). Silakan konfirmasi pengiriman supir saat barang siap diberangkatkan.")
            ->with('print_sj', $nomorKeluar);
    }

    public function cariBarangPemindahan(Request $request): JsonResponse
    {
        $gudangStore = $this->resolveStoreGudang([
            'store_pengeluaran.read',
            'store_pengeluaran.create',
            'store.distribusi',
        ]);
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
        ]);

        $results = $this->pengeluaranService->searchBarang(trim($validated['q']), (int) $gudangStore->id);

        return response()->json(['data' => $results]);
    }

    public function buatPemindahanLangsung(Request $request): RedirectResponse
    {
        $gudangStore = $this->resolveStoreGudang([
            'store_pengeluaran.create',
            'store.distribusi',
        ]);
        $validated = $request->validate([
            'nomor_keluar' => ['nullable', 'string', 'max:60'],
            'gudang_tujuan_id' => [
                'nullable',
                'integer',
                Rule::exists('gudangs', 'id')->where(fn ($query) => $query
                    ->where('is_aktif', true)
                    ->whereIn('jenis', ['unit_bisnis', 'gudang_umum', 'gudang_sukahati'])),
            ],
            'unit_peminta_teks' => ['nullable', 'string', 'max:255'],
            'pemohon' => ['nullable', 'string', 'max:255'],
            'petugas' => ['nullable', 'string', 'max:255'],
            'items' => ['nullable', 'array', 'min:1', 'max:100'],
            'items.*.barang_id' => ['required_with:items', 'integer'],
            'items.*.qty' => ['required_with:items', 'integer', 'min:1', 'max:2147483647'],
            'barang_id' => ['required_without:items', 'nullable', 'integer'],
            'qty_diminta' => ['required_without:items', 'nullable', 'integer', 'min:1', 'max:2147483647'],
        ]);

        $nomorKeluar = $this->pengeluaranService->createDirectTransfer($validated, $gudangStore);

        return redirect()->route('store.pengeluaran.index', ['tab' => 'histori'])
            ->with('success', "Dokumen pengeluaran {$nomorKeluar} berhasil dibuat. Silakan konfirmasi pengiriman supir saat barang siap diberangkatkan.")
            ->with('print_sj', $nomorKeluar);
    }

    public function tolakPermintaan(Request $request, PermintaanBarang $permintaan): RedirectResponse
    {
        $gudangStore = $this->resolveStoreGudang([
            'store_pengeluaran.update',
            'store.distribusi',
        ]);
        $validated = $request->validate([
            'alasan_penolakan' => ['required', 'string', 'max:2000'],
        ]);

        $this->pengeluaranService->rejectRequest($permintaan, $validated['alasan_penolakan'], $gudangStore);

        return redirect()->route('store.permintaan.index')
            ->with('success', "Permintaan {$permintaan->nomor_pr} ditolak.");
    }

    public function konfirmasiKirim(Request $request, TransferBarang $transfer): RedirectResponse
    {
        $gudangStore = $this->resolveStoreGudang([
            'store_pengeluaran.update',
            'store.distribusi',
        ]);
        $validated = $request->validate([
            'qty_dikirim' => ['required', 'integer', 'min:1'],
            'supir_pengantar' => ['required', 'string', 'max:255'],
            'foto_bukti' => ['nullable', 'image', 'max:5120'],
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto_bukti')) {
            $fotoPath = $request->file('foto_bukti')->store('transfer_bukti', 'public');
        }

        $nomorBase = $this->pengeluaranService->confirmShipment(
            $transfer,
            (int) $validated['qty_dikirim'],
            $validated['supir_pengantar'],
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
        $user = Auth::user();
        abort_unless($user instanceof User, 403);
        $gudangStore = $this->gudangStoreService->resolveAssignedWarehouse(
            $user,
            null,
            ['gudang_store'],
            $permissions,
            session('active_gudang_id'),
        );

        session(['active_gudang_id' => $gudangStore->id]);

        return $gudangStore;
    }
}
