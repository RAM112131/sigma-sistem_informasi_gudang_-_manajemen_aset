<?php

declare(strict_types=1);

namespace App\Services\UnitBisnis;

use App\Models\Gudang;
use App\Models\PengeluaranUnit;
use App\Models\PengeluaranUnitDetail;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RekonsiliasiPengeluaranService
{
    public function __construct(private readonly UnitGudangService $unitGudangService) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     activeGudang: ?Gudang,
     *     issues: LengthAwarePaginator,
     *     selectedIssue: ?PengeluaranUnit,
     *     workspaceItems: array<int, array<string, mixed>>,
     *     pemintas: array<int, string>,
     *     filters: array<string, mixed>,
     *     metrics: array<string, int|float|string|null>
     * }
     */
    public function getPageData(User $user, int|string|null $selectedGudangId, array $filters): array
    {
        $activeGudang = $this->unitGudangService->resolveActiveGudang(
            $user,
            $selectedGudangId,
            ['gudang_umum', 'gudang_sukahati', 'unit_bisnis'],
        );

        if (! $activeGudang instanceof Gudang) {
            return $this->emptyPageData($filters);
        }

        $normalizedFilters = [
            'gudang_id' => $activeGudang->getKey(),
            'status' => $filters['status'] ?? 'menunggu_verifikasi',
            'q' => trim((string) ($filters['q'] ?? '')),
            'bagian_peminta' => isset($filters['bagian_peminta']) && $filters['bagian_peminta'] !== ''
                ? trim((string) $filters['bagian_peminta'])
                : null,
            'tanggal' => $filters['tanggal'] ?? null,
        ];

        $issues = $this->filteredIssuesQuery($activeGudang, $normalizedFilters)
            ->with('details.barang:id,harga_satuan')
            ->withCount([
                'details',
                'details as draft_details_count' => static fn (Builder $query): Builder => $query
                    ->whereNotNull('qty_kembali_rekonsiliasi'),
            ])
            ->orderByDesc('tanggal')
            ->orderByDesc('waktu_keluar')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $requesterFilters = $normalizedFilters;
        $requesterFilters['bagian_peminta'] = null;
        $pemintas = $this->filteredIssuesQuery($activeGudang, $requesterFilters)
            ->whereNotNull('bagian_peminta')
            ->select('bagian_peminta')
            ->distinct();
        $pemintas = $pemintas->orderBy('bagian_peminta')->pluck('bagian_peminta')->all();

        $issues->getCollection()->each(function (PengeluaranUnit $issue): void {
            $currentValue = $issue->details->sum(
                fn (PengeluaranUnitDetail $detail): float => $this->currentNetUsageValue($detail),
            );
            $issue->setAttribute('total_nilai', round((float) $currentValue, 2));
        });

        $selectedIssueId = $filters['pengeluaran_id'] ?? $issues->first()?->getKey();
        $selectedIssue = null;

        if ($selectedIssueId !== null) {
            $selectedIssue = PengeluaranUnit::query()
                ->where('gudang_id', $activeGudang->getKey())
                ->with([
                    'details' => static fn (HasMany $query): HasMany => $query->orderBy('id'),
                    'details.barang:id,harga_satuan',
                    'details.pengembalians:id,pengeluaran_unit_detail_id,qty_kembali',
                    'pencatat:id,name',
                    'verifier:id,name',
                ])
                ->find($selectedIssueId);

            abort_unless($selectedIssue instanceof PengeluaranUnit, 404);
        }

        return [
            'activeGudang' => $activeGudang,
            'issues' => $issues,
            'selectedIssue' => $selectedIssue,
            'workspaceItems' => $selectedIssue instanceof PengeluaranUnit
                ? $this->mapWorkspaceItems($selectedIssue)
                : [],
            'pemintas' => $pemintas,
            'filters' => $normalizedFilters,
            'metrics' => $this->metricsForGudang($activeGudang),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function saveDraft(
        User $user,
        int|string|null $selectedGudangId,
        int $pengeluaranId,
        array $validated,
    ): void {
        $activeGudang = $this->requireActiveGudang($user, $selectedGudangId);

        DB::transaction(function () use ($activeGudang, $pengeluaranId, $validated): void {
            [, $details] = $this->lockPendingIssue($activeGudang, $pengeluaranId);
            $quantities = $this->normalizeReconciliationQuantities($details, $validated['items']);

            foreach ($details as $detail) {
                $detail->update([
                    'qty_keluar_rekonsiliasi' => $quantities[$detail->getKey()]['qty_keluar'],
                    'qty_kembali_rekonsiliasi' => $quantities[$detail->getKey()]['qty_kembali'],
                ]);
            }
        }, attempts: 3);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function verify(
        User $user,
        int|string|null $selectedGudangId,
        int $pengeluaranId,
        array $validated,
    ): void {
        $activeGudang = $this->requireActiveGudang($user, $selectedGudangId);

        DB::transaction(function () use ($user, $activeGudang, $pengeluaranId, $validated): void {
            [$issue, $details] = $this->lockPendingIssue($activeGudang, $pengeluaranId);
            $quantities = $this->normalizeReconciliationQuantities($details, $validated['items']);

            foreach ($details as $detail) {
                $quantityOut = $quantities[$detail->getKey()]['qty_keluar'];
                $quantityReturned = $quantities[$detail->getKey()]['qty_kembali'];
                $hpp = (float) $detail->harga_hpp;

                if ($hpp <= 0) {
                    $hpp = (float) ($detail->barang?->harga_satuan ?? 0);
                }

                $netUsed = max(0.0, round($quantityOut - $quantityReturned, 3));

                $detail->update([
                    'qty' => $quantityOut,
                    'qty_keluar_dilaporkan' => $detail->qty_keluar_dilaporkan ?? $detail->qty,
                    'qty_keluar_rekonsiliasi' => $quantityOut,
                    'qty_kembali' => $quantityReturned,
                    'qty_kembali_rekonsiliasi' => $quantityReturned,
                    'harga_hpp' => $hpp,
                    'subtotal_nilai' => round($netUsed * $hpp, 2),
                ]);
            }

            $issue->update([
                'status_verifikasi' => 'terverifikasi',
                'diverifikasi_oleh' => $user->getKey(),
                'diverifikasi_at' => now(),
                'catatan_verifikasi' => $validated['catatan_verifikasi'] ?? null,
            ]);
        }, attempts: 3);
    }

    private function requireActiveGudang(User $user, int|string|null $selectedGudangId): Gudang
    {
        $activeGudang = $this->unitGudangService->resolveActiveGudang(
            $user,
            $selectedGudangId,
            ['gudang_umum', 'gudang_sukahati', 'unit_bisnis'],
        );

        abort_unless($activeGudang instanceof Gudang, 403, 'Akun ini belum memiliki gudang aktif.');

        return $activeGudang;
    }

    /**
     * @return array{0: PengeluaranUnit, 1: Collection<int, PengeluaranUnitDetail>}
     */
    private function lockPendingIssue(Gudang $gudang, int $pengeluaranId): array
    {
        $issue = PengeluaranUnit::query()
            ->where('gudang_id', $gudang->getKey())
            ->whereKey($pengeluaranId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($issue->status_verifikasi !== 'menunggu_verifikasi') {
            throw ValidationException::withMessages([
                'items' => 'BPB ini sudah diproses dan tidak dapat direkonsiliasi ulang.',
            ]);
        }

        $details = $issue->details()
            ->with('barang:id,harga_satuan')
            ->lockForUpdate()
            ->orderBy('id')
            ->get();

        if ($details->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'BPB ini tidak memiliki rincian barang yang dapat direkonsiliasi.',
            ]);
        }

        return [$issue, $details];
    }

    /**
     * @param  Collection<int, PengeluaranUnitDetail>  $details
     * @param  array<int, array{detail_id: int, qty_keluar: int|float|string, qty_kembali: int|float|string}>  $items
     * @return array<int, array{qty_keluar: float, qty_kembali: float}>
     */
    private function normalizeReconciliationQuantities(Collection $details, array $items): array
    {
        $submittedQuantities = [];

        foreach ($items as $item) {
            $submittedQuantities[(int) $item['detail_id']] = [
                'qty_keluar' => round((float) $item['qty_keluar'], 3),
                'qty_kembali' => round((float) $item['qty_kembali'], 3),
            ];
        }

        $expectedIds = $details->modelKeys();
        $submittedIds = array_keys($submittedQuantities);

        if (count($submittedQuantities) !== count($expectedIds)
            || array_diff($expectedIds, $submittedIds) !== []
            || array_diff($submittedIds, $expectedIds) !== []) {
            throw ValidationException::withMessages([
                'items' => 'Rincian barang yang dikirim tidak sesuai dengan BPB terpilih.',
            ]);
        }

        foreach ($details as $detail) {
            $quantities = $submittedQuantities[$detail->getKey()];

            if ($quantities['qty_kembali'] > $quantities['qty_keluar']) {
                throw ValidationException::withMessages([
                    'items' => "Jumlah pengembalian {$detail->nama_barang} tidak boleh melebihi jumlah keluar yang disesuaikan.",
                ]);
            }
        }

        return $submittedQuantities;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<PengeluaranUnit>
     */
    private function filteredIssuesQuery(Gudang $gudang, array $filters): Builder
    {
        $query = PengeluaranUnit::query()->where('gudang_id', $gudang->getKey());

        match ($filters['status']) {
            'terverifikasi' => $query->whereIn('status_verifikasi', ['terverifikasi', 'sesuai']),
            'ditolak' => $query->where('status_verifikasi', 'ditolak'),
            'semua' => null,
            default => $query->where('status_verifikasi', 'menunggu_verifikasi'),
        };

        if ($filters['bagian_peminta'] !== null) {
            $query->where('bagian_peminta', $filters['bagian_peminta']);
        }

        if ($filters['tanggal'] !== null) {
            $query->whereDate('tanggal', $filters['tanggal']);
        }

        if ($filters['q'] !== '') {
            $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $searchTerm = '%'.$filters['q'].'%';

            $query->where(static function (Builder $searchQuery) use ($operator, $searchTerm): void {
                $searchQuery
                    ->where('nomor_bpb', $operator, $searchTerm)
                    ->orWhere('bagian_peminta', $operator, $searchTerm);
            });
        }

        return $query;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapWorkspaceItems(PengeluaranUnit $issue): array
    {
        return $issue->details->map(function (PengeluaranUnitDetail $detail): array {
            $reportedReturn = $detail->pengembalians->isNotEmpty()
                ? (float) $detail->pengembalians->sum('qty_kembali')
                : (float) ($detail->qty_kembali ?? 0);
            $returnQuantity = (float) ($detail->qty_kembali_rekonsiliasi ?? $reportedReturn);
            $hpp = (float) $detail->harga_hpp;

            if ($hpp <= 0) {
                $hpp = (float) ($detail->barang?->harga_satuan ?? 0);
            }

            return [
                'id' => (int) $detail->getKey(),
                'kode_barang' => $detail->kode_barang,
                'nama_barang' => $detail->nama_barang,
                'kategori' => $detail->kategori ?: 'Umum',
                'satuan' => $detail->satuan ?: 'unit',
                'qty_keluar' => (float) ($detail->qty_keluar_rekonsiliasi ?? $detail->qty),
                'qty_keluar_dilaporkan' => (float) ($detail->qty_keluar_dilaporkan ?? $detail->qty),
                'qty_kembali_dilaporkan' => $reportedReturn,
                'qty_kembali' => $returnQuantity,
                'hpp' => $hpp,
                'hpp_tersedia' => $hpp > 0,
                'subtotal' => $this->currentNetUsageValue($detail),
                'catatan' => $detail->catatan,
            ];
        })->values()->all();
    }

    private function currentNetUsageValue(PengeluaranUnitDetail $detail): float
    {
        $returnQuantity = (float) ($detail->qty_kembali_rekonsiliasi ?? $detail->qty_kembali ?? 0);
        $hpp = (float) $detail->harga_hpp;

        if ($hpp <= 0) {
            $hpp = (float) ($detail->barang?->harga_satuan ?? 0);
        }

        $quantityOut = (float) ($detail->qty_keluar_rekonsiliasi ?? $detail->qty);
        $netUsed = max(0.0, round($quantityOut - $returnQuantity, 3));

        return round($netUsed * $hpp, 2);
    }

    /**
     * @return array<string, int|float|string|null>
     */
    private function metricsForGudang(Gudang $gudang): array
    {
        $pendingIssues = PengeluaranUnit::query()
            ->where('gudang_id', $gudang->getKey())
            ->where('status_verifikasi', 'menunggu_verifikasi');

        $today = today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $topDepartment = DB::table('pengeluaran_unit_details as details')
            ->join('pengeluaran_units as headers', 'headers.id', '=', 'details.pengeluaran_unit_id')
            ->where('headers.gudang_id', $gudang->getKey())
            ->whereIn('headers.status_verifikasi', ['terverifikasi', 'sesuai'])
            ->whereDate('headers.tanggal', $today)
            ->groupBy('headers.bagian_peminta')
            ->orderByRaw('SUM(details.subtotal_nilai) DESC')
            ->value('headers.bagian_peminta');

        $pendingValue = DB::table('pengeluaran_unit_details as details')
            ->join('pengeluaran_units as headers', 'headers.id', '=', 'details.pengeluaran_unit_id')
            ->leftJoin('barangs as items', 'items.id', '=', 'details.barang_id')
            ->where('headers.gudang_id', $gudang->getKey())
            ->where('headers.status_verifikasi', 'menunggu_verifikasi')
            ->selectRaw('SUM(CASE WHEN COALESCE(details.qty_keluar_rekonsiliasi, details.qty) > COALESCE(details.qty_kembali_rekonsiliasi, details.qty_kembali, 0) THEN (COALESCE(details.qty_keluar_rekonsiliasi, details.qty) - COALESCE(details.qty_kembali_rekonsiliasi, details.qty_kembali, 0)) * COALESCE(NULLIF(details.harga_hpp, 0), items.harga_satuan, 0) ELSE 0 END) AS total')
            ->value('total');

        return [
            'pending_count' => (int) (clone $pendingIssues)->count(),
            'pending_value' => (float) ($pendingValue ?? 0),
            'verified_today_count' => (int) PengeluaranUnit::query()
                ->where('gudang_id', $gudang->getKey())
                ->whereIn('status_verifikasi', ['terverifikasi', 'sesuai'])
                ->whereDate('diverifikasi_at', $today)
                ->count(),
            'month_value' => (float) PengeluaranUnitDetail::query()
                ->whereHas('pengeluaranUnit', static fn (Builder $query): Builder => $query
                    ->where('gudang_id', $gudang->getKey())
                    ->whereIn('status_verifikasi', ['terverifikasi', 'sesuai'])
                    ->whereBetween('tanggal', [$monthStart, $monthEnd]))
                ->sum('subtotal_nilai'),
            'top_department' => $topDepartment,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     activeGudang: null,
     *     issues: LengthAwarePaginator,
     *     selectedIssue: null,
     *     workspaceItems: array{},
     *     pemintas: array{},
     *     filters: array<string, mixed>,
     *     metrics: array<string, int|float|string|null>
     * }
     */
    private function emptyPageData(array $filters): array
    {
        return [
            'activeGudang' => null,
            'issues' => new Paginator([], 0, 12, 1, ['path' => Paginator::resolveCurrentPath()]),
            'selectedIssue' => null,
            'workspaceItems' => [],
            'pemintas' => [],
            'filters' => [
                'gudang_id' => $filters['gudang_id'] ?? null,
                'status' => $filters['status'] ?? 'menunggu_verifikasi',
                'q' => trim((string) ($filters['q'] ?? '')),
                'bagian_peminta' => isset($filters['bagian_peminta']) && $filters['bagian_peminta'] !== ''
                    ? trim((string) $filters['bagian_peminta'])
                    : null,
                'tanggal' => $filters['tanggal'] ?? null,
            ],
            'metrics' => [
                'pending_count' => 0,
                'pending_value' => 0.0,
                'verified_today_count' => 0,
                'month_value' => 0.0,
                'top_department' => null,
            ],
        ];
    }
}
