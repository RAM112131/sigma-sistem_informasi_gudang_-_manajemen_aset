<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\IndexDashboardRequest;
use App\Services\SuperAdmin\DashboardService;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function __invoke(IndexDashboardRequest $request): View
    {
        $filters = $request->validated();

        $data = $this->dashboardService->getDashboardData($filters);

        return view('super-admin.dashboard', [
            ...$data,
            'filters' => $filters,
            'recentSystemLogsCount' => $data['recentSystemLogs']->count(),
        ]);
    }
}
