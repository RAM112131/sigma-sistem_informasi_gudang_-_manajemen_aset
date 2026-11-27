<?php

declare(strict_types=1);

namespace App\Http\Controllers\UnitBisnis\RecordingGudangUmum;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitBisnis\RecordingGudangUmum\ConfirmStoreTransferReceiptRequest;
use App\Http\Requests\UnitBisnis\RecordingGudangUmum\StoreDirectReceiptRequest;
use App\Models\User;
use App\Services\UnitBisnis\RecordingGudangUmumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PenerimaanController extends Controller
{
    public function __construct(
        private readonly RecordingGudangUmumService $recordingService,
    ) {}

    /**
     * Display goods receipt listing and input form for Recording Gudang Umum.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $data = $this->recordingService->getPenerimaanPageData(
            $user,
            $request->query('gudang_id', session('active_gudang_id')),
        );

        if ($data['activeGudang'] !== null) {
            session(['active_gudang_id' => $data['activeGudang']->getKey()]);
        }

        return view('unit-bisnis.recording-gudang-umum.penerimaan.index', $data);
    }

    /**
     * Store new operational goods receipt directly from external supplier.
     */
    public function store(StoreDirectReceiptRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $selectedGudangId = $request->input('gudang_id', session('active_gudang_id'));
        $receipt = $this->recordingService->saveDirectReceipt($user, $selectedGudangId, $request->validated());

        $message = "Penerimaan barang dari supplier {$receipt->nama_supplier} berhasil dicatat dengan No. RCV {$receipt->nomor_rcv}. Stok fisik unit telah diperbarui.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'id' => $receipt->getKey(),
                    'nomor_rcv' => $receipt->nomor_rcv,
                ],
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Confirm receipts from Store transfer.
     */
    public function confirmTransfer(ConfirmStoreTransferReceiptRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $selectedGudangId = $request->input('gudang_id', session('active_gudang_id'));
        $result = $this->recordingService->confirmStoreTransfer($user, $selectedGudangId, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'confirmed_count' => $result['confirmed_count'],
            ]);
        }

        return redirect()->back()->with('success', $result['message']);
    }
}
