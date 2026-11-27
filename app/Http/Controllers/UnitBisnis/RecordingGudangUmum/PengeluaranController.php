<?php

declare(strict_types=1);

namespace App\Http\Controllers\UnitBisnis\RecordingGudangUmum;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitBisnis\RecordingGudangUmum\StorePengeluaranRequest;
use App\Http\Requests\UnitBisnis\RecordingGudangUmum\StorePengembalianSisaRequest;
use App\Http\Requests\UnitBisnis\RecordingGudangUmum\StoreRequesterQuicklyRequest;
use App\Models\User;
use App\Services\UnitBisnis\RecordingGudangUmumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PengeluaranController extends Controller
{
    public function __construct(
        private readonly RecordingGudangUmumService $recordingService,
    ) {}

    /**
     * Display the goods release form and its persisted history for Recording staff.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $data = $this->recordingService->getPengeluaranPageData(
            $user,
            $request->query('gudang_id', session('active_gudang_id')),
        );

        $isAdminGU = $this->recordingService->isAdminGudangUmum($user);
        $data['isAdminGU'] = $isAdminGU;

        if ($data['activeGudang'] !== null) {
            session(['active_gudang_id' => $data['activeGudang']->getKey()]);
        }

        return view('unit-bisnis.recording-gudang-umum.pengeluaran.index', $data);
    }

    /**
     * Persist a Recording GU issue record for Admin GU verification.
     */
    public function store(StorePengeluaranRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $transaction = $this->recordingService->storePengeluaran(
            $user,
            session('active_gudang_id'),
            $request->validated(),
        );

        if (! $request->expectsJson()) {
            return redirect()->back()->with('success', "Pengeluaran {$transaction['bpb']} berhasil dicatat.");
        }

        return response()->json(['data' => $transaction], 201);
    }

    public function storeRequester(StoreRequesterQuicklyRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $area = $this->recordingService->quickAddRequester(
            $user,
            $request->session()->get('active_gudang_id'),
            $request->validated('bagian_peminta'),
        );

        return response()->json([
            'data' => [
                'id' => $area->getKey(),
                'name' => $area->nama,
            ],
        ]);
    }

    public function borrowedItems(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $department = (string) $request->query('department', '');
        $items = $this->recordingService->getBorrowedItemsForDepartment(
            $user,
            session('active_gudang_id') ?? $request->query('gudang_id'),
            $department,
        );

        return response()->json(['data' => $items]);
    }

    public function returnOptions(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $options = $this->recordingService->getReturnDepartmentOptions(
            $user,
            session('active_gudang_id') ?? $request->query('gudang_id'),
        );

        return response()->json(['data' => $options]);
    }

    public function storePengembalian(StorePengembalianSisaRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $result = $this->recordingService->storePengembalian(
            $user,
            session('active_gudang_id'),
            $request->validated(),
        );

        return response()->json([
            'message' => $result['message'],
            'data' => $result,
        ], 200);
    }
}
