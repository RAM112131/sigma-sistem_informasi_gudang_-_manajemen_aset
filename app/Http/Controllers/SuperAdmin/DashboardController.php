<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SuperAdmin\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $data = $this->dashboardService->getDashboardData($filters);

        $viewName = view()->exists('super-admin.dashboard') ? 'super-admin.dashboard' : 'admin.dashboard';

        return view($viewName, [
            ...$data,
            'filters' => $filters,
            'recentSystemLogsCount' => $data['recentSystemLogs']->count(),
        ]);
    }
}
