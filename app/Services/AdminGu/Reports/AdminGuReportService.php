<?php

declare(strict_types=1);

namespace App\Services\AdminGu\Reports;

use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\User;
use App\Services\UnitBisnis\UnitGudangService;
use Illuminate\Support\Carbon;

class AdminGuReportService
{
    public function __construct(
        private readonly UnitGudangService $unitGudangService,
        private readonly StockMutationReportService $stockMutationReportService,
        private readonly DivisionMatrixReportService $divisionMatrixReportService,
        private readonly InboundSourceReportService $inboundSourceReportService,
        private readonly OpnameDiscrepancyReportService $opnameDiscrepancyReportService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function pageData(User $user, int|string|null $selectedGudangId, array $filters): array
    {
        $normalized = $this->normalizeFilters($filters);
        $warehouse = $this->warehouse($user, $selectedGudangId);
        $period = Carbon::createFromFormat('Y-m-d', $normalized['period'].'-01')->startOfDay();
        $report = $warehouse instanceof Gudang ? $this->report($warehouse, $normalized, paginate: true) : [];
        $categories = $warehouse instanceof Gudang
            ? KategoriBarang::query()
                ->where('is_aktif', true)
                ->where(static fn ($query) => $query
                    ->where('gudang_id', $warehouse->getKey())
                    ->orWhereNull('gudang_id'))
                ->orderBy('nama')
                ->get(['id', 'nama'])
            : collect();

        return [
            'activeGudang' => $warehouse,
            'categories' => $categories,
            'filters' => $normalized,
            'periodLabel' => $period->locale('id')->translatedFormat('F Y'),
            'reportStatus' => 'Laporan Berjalan (Belum Tutup Buku)',
            'report' => $report,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function exportData(User $user, int|string|null $selectedGudangId, array $filters): array
    {
        $normalized = $this->normalizeFilters($filters);
        $warehouse = $this->warehouse($user, $selectedGudangId);
        abort_unless($warehouse instanceof Gudang, 403, 'Pilih gudang umum yang ditugaskan kepada Anda.');
        $period = Carbon::createFromFormat('Y-m-d', $normalized['period'].'-01')->startOfDay();

        return [
            'activeGudang' => $warehouse,
            'filters' => $normalized,
            'periodLabel' => $period->locale('id')->translatedFormat('F Y'),
            'reportStatus' => 'Laporan Berjalan (Belum Tutup Buku)',
            'report' => $this->report($warehouse, $normalized, paginate: false),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function report(Gudang $warehouse, array $filters, bool $paginate): array
    {
        $warehouseId = (int) $warehouse->getKey();

        return match ($filters['tab']) {
            'matriks' => $this->divisionMatrixReportService->report($warehouseId, $filters['period'], $filters, $paginate),
            'pemasukan' => $this->inboundSourceReportService->report($warehouseId, $filters['period'], $filters, $paginate),
            'selisih' => $this->opnameDiscrepancyReportService->report($warehouseId, $filters['period'], $filters, $paginate),
            default => $this->stockMutationReportService->report($warehouseId, $filters['period'], $filters, $paginate),
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{period: string, tab: string, category_id: int|null, search: string, source_type: string|null, matrix_mode: string, page: int, opname_page: int, damaged_page: int}
     */
    private function normalizeFilters(array $filters): array
    {
        return [
            'period' => (string) ($filters['period'] ?? now()->format('Y-m')),
            'tab' => (string) ($filters['tab'] ?? 'mutasi'),
            'category_id' => isset($filters['category_id']) && $filters['category_id'] !== ''
                ? (int) $filters['category_id']
                : null,
            'search' => trim((string) ($filters['search'] ?? '')),
            'source_type' => $filters['source_type'] ?? null,
            'matrix_mode' => (string) ($filters['matrix_mode'] ?? 'qty'),
            'page' => max(1, (int) ($filters['page'] ?? 1)),
            'opname_page' => max(1, (int) ($filters['opname_page'] ?? 1)),
            'damaged_page' => max(1, (int) ($filters['damaged_page'] ?? 1)),
        ];
    }

    private function warehouse(User $user, int|string|null $selectedGudangId): ?Gudang
    {
        return $this->unitGudangService->resolveActiveGudang(
            $user,
            $selectedGudangId,
            ['gudang_umum', 'gudang_sukahati'],
        );
    }
}
