<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\FilterActivityLogRequest;
use App\Models\AuditLog;
use App\Services\SuperAdmin\ActivityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {}

    /**
     * Display a paginated, filterable listing of activity records.
     */
    public function index(FilterActivityLogRequest $request): View
    {
        $filters = $request->normalizedFilters();
        $data = $this->activityLogService->getListingData($filters);
        $viewName = view()->exists('super-admin.activity-log.index') ? 'super-admin.activity-log.index' : 'admin.activity-log.index';

        return view($viewName, [
            ...$data,
            'filters' => $filters,
        ]);
    }

    /**
     * Return the full, read-only details for one audit record.
     */
    public function show(FilterActivityLogRequest $request, AuditLog $auditLog): JsonResponse
    {
        $filters = $request->normalizedFilters();
        $direction = $filters['direction'] ?? null;

        if ($direction !== null) {
            $auditLog = $this->activityLogService->adjacentLog(
                $auditLog,
                $direction,
                $filters,
            );
            abort_if($auditLog === null, 404);
        }

        return response()->json($this->activityLogService->detailData($auditLog));
    }

    /**
     * Stream the currently filtered audit records as CSV or JSON.
     */
    public function export(FilterActivityLogRequest $request): StreamedResponse
    {
        $filters = $request->normalizedFilters();
        $format = $filters['format'];
        $filename = 'log-aktivitas-'.now()->format('Y-m-d').'.'.$format;
        $contentType = $format === 'csv'
            ? 'text/csv; charset=UTF-8'
            : 'application/json; charset=UTF-8';

        return response()->streamDownload(function () use ($filters, $format): void {
            $query = $this->activityLogService
                ->filteredLogs($filters, includeChanges: true)
                ->with('causer')
                ->orderBy('id');

            if ($format === 'csv') {
                $this->streamCsv($query);

                return;
            }

            $this->streamJson($query);
        }, $filename, ['Content-Type' => $contentType]);
    }

    /**
     * Clear all activity audit logs.
     */
    public function destroyAll(): RedirectResponse
    {
        $this->activityLogService->clearAll();

        return redirect()
            ->route('admin.activity-log.index')
            ->with('success', 'Semua catatan log aktivitas berhasil dihapus.');
    }

    /**
     * @param  Builder<AuditLog>  $query
     */
    private function streamCsv(Builder $query): void
    {
        $output = fopen('php://output', 'wb');

        if ($output === false) {
            throw new RuntimeException('Tidak dapat membuka stream ekspor log aktivitas.');
        }

        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, [
            'ID',
            'Waktu',
            'Pengguna',
            'Username',
            'Aksi',
            'Subjek',
            'ID Subjek',
            'Deskripsi',
            'IP Address',
            'User Agent',
            'Perubahan',
        ], ',', '"', '');

        $query->chunkById(500, function ($logs) use ($output): void {
            foreach ($logs as $log) {
                $row = $this->activityLogService->exportRow($log);
                $row['changes'] = json_encode(
                    $row['changes'],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                );

                fputcsv(
                    $output,
                    array_map($this->activityLogService->csvSafe(...), array_values($row)),
                    ',',
                    '"',
                    '',
                );
            }
        });

        fclose($output);
    }

    /**
     * @param  Builder<AuditLog>  $query
     */
    private function streamJson(Builder $query): void
    {
        echo '[';
        $isFirst = true;

        $query->chunkById(500, function ($logs) use (&$isFirst): void {
            foreach ($logs as $log) {
                if (! $isFirst) {
                    echo ',';
                }

                echo json_encode(
                    $this->activityLogService->exportRow($log),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                );
                $isFirst = false;
            }
        });

        echo ']';
    }
}
