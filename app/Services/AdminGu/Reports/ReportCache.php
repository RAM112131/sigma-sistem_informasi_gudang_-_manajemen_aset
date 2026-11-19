<?php

declare(strict_types=1);

namespace App\Services\AdminGu\Reports;

use Closure;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use UnexpectedValueException;

class ReportCache
{
    private const string CACHE_VERSION = 'v2';

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function remember(string $period, string $tab, int $warehouseId, array $filters, Closure $callback): array
    {
        ksort($filters);
        $hash = hash('sha256', serialize([$warehouseId, $filters]));
        $key = 'report:'.self::CACHE_VERSION.":{$period}:{$tab}:{$hash}";
        $cached = Cache::get($key);
        if ($this->hasValidReportShape($cached)) {
            return $cached;
        }

        if ($cached !== null) {
            Cache::forget($key);
        }

        $report = $callback();
        if (! $this->hasValidReportShape($report)) {
            throw new UnexpectedValueException('The Admin GU report returned an invalid data shape.');
        }

        Cache::put($key, $report, now()->addMinutes(10));

        return $report;
    }

    private function hasValidReportShape(mixed $report): bool
    {
        if (! is_array($report)) {
            return false;
        }

        foreach (['items', 'documents', 'opname_items', 'damaged_items'] as $key) {
            if (! array_key_exists($key, $report)) {
                continue;
            }

            $rows = $report[$key];
            if ($rows instanceof LengthAwarePaginator) {
                $rows = $rows->items();
            } elseif ($rows instanceof Collection) {
                $rows = $rows->all();
            }

            if (! is_array($rows)) {
                return false;
            }

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    return false;
                }
            }
        }

        return true;
    }
}
