<?php

declare(strict_types=1);

namespace App\Http\Controllers\UnitBisnis\AdminGudangUmum;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UnitBisnis\AdminGudangUmumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly AdminGudangUmumService $adminService,
    ) {}

    /**
     * Display the managerial dashboard for Admin Gudang Umum.
     */
    public function __invoke(Request $request): View|JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $activeGudang = $this->adminService->resolveActiveGudangForAdmin(
            $user,
            $request->query('gudang_id', session('active_gudang_id')),
        );

        if ($activeGudang !== null) {
            session(['active_gudang_id' => $activeGudang->getKey()]);
        }

        $validator = Validator::make($request->query(), [
            'period' => ['nullable', 'in:week,custom'],
            'start_date' => ['exclude_unless:period,custom', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'end_date' => ['exclude_unless:period,custom', 'required', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:today'],
        ], [
            'period.in' => 'Pilihan periode grafik tidak valid.',
            'start_date.required' => 'Tanggal awal wajib dipilih untuk rentang kustom.',
            'start_date.date_format' => 'Format tanggal awal tidak valid.',
            'start_date.before_or_equal' => 'Tanggal awal tidak boleh melewati hari ini.',
            'end_date.required' => 'Tanggal akhir wajib dipilih untuk rentang kustom.',
            'end_date.date_format' => 'Format tanggal akhir tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
            'end_date.before_or_equal' => 'Tanggal akhir tidak boleh melewati hari ini.',
        ]);
        $isChartFilterInvalid = $validator->fails();
        $chartFilterErrors = $validator->errors()->all();

        if ($isChartFilterInvalid && $request->expectsJson()) {
            return response()->json([
                'message' => 'Rentang tanggal tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($isChartFilterInvalid) {
            $chartPeriod = 'week';
            $chartStartDate = today()->subDays(6)->toDateString();
            $chartEndDate = today()->toDateString();
        } else {
            $validated = $validator->validated();
            $chartPeriod = $validated['period'] ?? 'week';
            $chartStartDate = $chartPeriod === 'custom'
                ? $validated['start_date']
                : today()->subDays(6)->toDateString();
            $chartEndDate = $chartPeriod === 'custom'
                ? $validated['end_date']
                : today()->toDateString();
        }

        $chartRange = [
            'period' => $chartPeriod,
            'start_date' => $chartStartDate,
            'end_date' => $chartEndDate,
            'errors' => $chartFilterErrors,
        ];
        $dashboardData = $this->adminService->getDashboardData(
            $activeGudang,
            $chartStartDate,
            $chartEndDate,
            $chartPeriod,
        );

        if ($request->expectsJson()) {
            return response()->json([
                'chartRange' => $chartRange,
                'movements' => $dashboardData['chartData']['movements'],
            ]);
        }

        return view('unit-bisnis.admin-gudang-umum.dashboard', [
            'user' => $user,
            'activeGudang' => $activeGudang,
            'chartRange' => $chartRange,
            ...$dashboardData,
        ]);
    }
}
