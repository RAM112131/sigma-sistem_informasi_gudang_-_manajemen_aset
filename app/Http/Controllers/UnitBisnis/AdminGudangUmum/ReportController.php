<?php

declare(strict_types=1);

namespace App\Http\Controllers\UnitBisnis\AdminGudangUmum;

use App\Exports\AdminGu\WarehouseMonthlyReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminGu\ReportFilterRequest;
use App\Models\User;
use App\Services\AdminGu\Reports\AdminGuReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ReportController extends Controller
{
    public function __construct(private readonly AdminGuReportService $reportService) {}

    public function index(ReportFilterRequest $request): View
    {
        return view('unit-bisnis.admin-gudang-umum.reports.index', $this->reportService->pageData(
            $this->user($request),
            session('active_gudang_id'),
            $request->validated(),
        ));
    }

    public function exportExcel(ReportFilterRequest $request): BinaryFileResponse
    {
        $data = $this->reportService->exportData(
            $this->user($request),
            session('active_gudang_id'),
            $request->validated(),
        );

        return Excel::download(
            new WarehouseMonthlyReportExport($data['report'], $data['filters']),
            'laporan-gudang-umum-'.$data['filters']['period'].'-'.$data['filters']['tab'].'.xlsx',
        );
    }

    public function exportPdf(ReportFilterRequest $request): Response
    {
        $data = $this->reportService->exportData(
            $this->user($request),
            session('active_gudang_id'),
            $request->validated(),
        );

        return Pdf::loadView('unit-bisnis.admin-gudang-umum.reports.pdf.monthly-report', $data)
            ->setPaper('a4', 'landscape')
            ->download('laporan-gudang-umum-'.$data['filters']['period'].'-'.$data['filters']['tab'].'.pdf');
    }

    private function user(ReportFilterRequest $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
