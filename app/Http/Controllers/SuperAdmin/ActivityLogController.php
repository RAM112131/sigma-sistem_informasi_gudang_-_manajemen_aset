<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\SuperAdmin\ActivityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
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
    public function show(Request $request, AuditLog $auditLog): JsonResponse
    {
        $direction = $request->validate([
            'direction' => ['nullable', Rule::in(['previous', 'next'])],
        ])['direction'] ?? null;

        if ($direction !== null) {
            $auditLog = $this->activityLogService->adjacentLog(
                $auditLog,
                $direction,
                $this->validatedFilters($request),
            );
            abort_if($auditLog === null, 404);
        }

        return response()->json($this->activityLogService->detailData($auditLog));
    }

    /**
     * Stream the currently filtered audit records as CSV or JSON.
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request, includeFormat: true);
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
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request, bool $includeFormat = false): array
    {
        $rules = [
            'search' => ['nullable', 'string', 'max:100'],
            'operator' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'action' => ['nullable', 'string', 'max:20'],
            'subject' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];

        if ($includeFormat) {
            $rules['format'] = ['required', Rule::in(['csv', 'json'])];
        }

        $filters = $request->validate($rules);
        $filters['search'] = trim($filters['search'] ?? '');
        $filters['subject'] = $filters['subject'] ?? '';

        return $filters;
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
