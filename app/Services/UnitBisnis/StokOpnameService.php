<?php

declare(strict_types=1);

namespace App\Services\UnitBisnis;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\StokOpname;
use App\Models\StokOpnameDetail;
use App\Models\User;
use App\Services\Store\StokService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StokOpnameService
{
    public const array PAGE_SIZES = [10, 20];

    public const int DEFAULT_PAGE_SIZE = 20;

    public function __construct(
        private readonly UnitGudangService $unitGudangService,
        private readonly StokService $stokService,
    ) {}

    public static function defaultPeriod(): string
    {
        return now()->startOfMonth()->format('Y-m');
    }

    /**
     * @return array<int, string>
     */
    public function availablePeriods(): array
    {
        $currentMonth = now()->startOfMonth();

        return collect(range(0, 11))
            ->map(static fn (int $monthsBack): string => $currentMonth
                ->copy()
                ->subMonthsNoOverflow($monthsBack)
                ->format('Y-m'))
            ->all();
    }

    /**
     * @param  array{gudang_id?: int|string|null, periode?: string|null, status?: string|null, q?: string|null, per_page?: int|string|null}  $filters
     * @return array<string, mixed>
     */
    public function getPageData(User $user, array $filters): array
    {
        $periode = $filters['periode'] ?? self::defaultPeriod();
        $periodStart = $this->periodStart($periode);
        $gudangUnits = $this->unitGudangService->getAssignedGudangUnits($user);
        $gudang = $this->unitGudangService->resolveActiveGudang(
            $user,
            $filters['gudang_id'] ?? session('active_gudang_id'),
            ['gudang_umum', 'gudang_sukahati'],
        );

        if ($gudang === null) {
            return [
                'activeGudang' => null,
                'gudangUnits' => $gudangUnits,
                'opname' => null,
                'items' => collect(),
                'metrics' => $this->emptyMetrics(),
                'period' => $periode,
                'periodLabel' => $periodStart->locale('id')->translatedFormat('F Y'),
                'periodOptions' => $this->periodOptionLabels(),
                'pageSizes' => self::PAGE_SIZES,
                'filters' => [
                    'status' => 'semua',
                    'q' => '',
                    'per_page' => (int) ($filters['per_page'] ?? self::DEFAULT_PAGE_SIZE),
                ],
            ];
        }

        $opname = StokOpname::query()
            ->where('gudang_id', $gudang->getKey())
            ->where('periode', $periodStart->toDateString())
            ->first();

        $query = Barang::query()
            ->with('satuan:id,nama')
            ->select('barangs.*')
            ->where('barangs.gudang_id', $gudang->getKey())
            ->where('barangs.is_aktif', true);

        if ($opname !== null) {
            $query->leftJoin('stok_opname_details as opname_detail', function (JoinClause $join) use ($opname): void {
                $join->on('opname_detail.barang_id', '=', 'barangs.id')
                    ->where('opname_detail.stok_opname_id', '=', $opname->getKey());
            })->addSelect([
                'opname_detail.qty_sistem as opname_qty_sistem',
                'opname_detail.qty_sistem_at as opname_qty_sistem_at',
                'opname_detail.qty_fisik as opname_qty_fisik',
                'opname_detail.catatan as opname_catatan',
            ]);
        }

        $search = trim($filters['q'] ?? '');
        if ($search !== '') {
            $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $pattern = '%'.$search.'%';
            $query->where(static function (Builder $matching) use ($operator, $pattern): void {
                $matching->where('barangs.kode', $operator, $pattern)
                    ->orWhere('barangs.nama', $operator, $pattern);
            });
        }

        $status = $filters['status'] ?? 'semua';
        if ($status !== 'semua') {
            if ($opname === null) {
                if ($status !== 'belum') {
                    $query->whereRaw('1 = 0');
                }
            } else {
                $query->whereExists(function ($details) use ($opname, $status): void {
                    $details->selectRaw('1')
                        ->from('stok_opname_details as filter_detail')
                        ->whereColumn('filter_detail.barang_id', 'barangs.id')
                        ->where('filter_detail.stok_opname_id', $opname->getKey());

                    $requiresSnapshot = $opname->status === 'draft';

                    match ($status) {
                        'belum' => $details->where(static function ($missing) use ($requiresSnapshot): void {
                            $missing->whereNull('filter_detail.qty_fisik');
                            if ($requiresSnapshot) {
                                $missing->orWhereNull('filter_detail.qty_sistem_at');
                            }
                        }),
                        'sesuai' => $details->whereNotNull('filter_detail.qty_fisik')
                            ->when($requiresSnapshot, static fn ($query) => $query->whereNotNull('filter_detail.qty_sistem_at'))
                            ->whereColumn('filter_detail.qty_fisik', '=', 'filter_detail.qty_sistem'),
                        'kurang' => $details->whereNotNull('filter_detail.qty_fisik')
                            ->when($requiresSnapshot, static fn ($query) => $query->whereNotNull('filter_detail.qty_sistem_at'))
                            ->whereColumn('filter_detail.qty_fisik', '<', 'filter_detail.qty_sistem'),
                        'lebih' => $details->whereNotNull('filter_detail.qty_fisik')
                            ->when($requiresSnapshot, static fn ($query) => $query->whereNotNull('filter_detail.qty_sistem_at'))
                            ->whereColumn('filter_detail.qty_fisik', '>', 'filter_detail.qty_sistem'),
                        default => null,
                    };
                });
            }
        }

        $query->orderBy('barangs.nama')->orderBy('barangs.id');
        $perPage = (int) ($filters['per_page'] ?? self::DEFAULT_PAGE_SIZE);
        $items = $query->paginate($perPage)->withQueryString();
        $liveBalanceIds = $items->getCollection()
            ->filter(static fn (Barang $barang): bool => $opname === null
                || ($opname->status === 'draft'
                    && ($barang->opname_qty_fisik === null || $barang->opname_qty_sistem_at === null)))
            ->modelKeys();
        $liveStockTotals = $liveBalanceIds !== []
            ? $this->stokService->totals($liveBalanceIds, (int) $gudang->getKey())
            : ['awal' => collect(), 'masuk' => collect(), 'keluar' => collect()];

        $items->through(function (Barang $barang) use ($opname, $liveStockTotals): array {
            $isApplied = $opname?->status === 'diterapkan';
            $isSnapshotReady = $isApplied
                || ($barang->opname_qty_fisik !== null && $barang->opname_qty_sistem_at !== null);
            $saldoSistem = $opname !== null && $barang->opname_qty_sistem !== null && $isSnapshotReady
                ? (float) $barang->opname_qty_sistem
                : $this->balanceFor($barang->getKey(), $liveStockTotals);
            $qtyFisik = $opname !== null && $barang->opname_qty_fisik !== null
                ? (float) $barang->opname_qty_fisik
                : null;
            $qtySelisih = $qtyFisik === null || ! $isSnapshotReady
                ? null
                : round($qtyFisik - $saldoSistem, 3);
            $snapshotAt = $barang->opname_qty_sistem_at !== null
                ? Carbon::parse($barang->opname_qty_sistem_at)
                : null;

            return [
                'barang' => $barang,
                'saldo_sistem' => $saldoSistem,
                'qty_fisik' => $qtyFisik,
                'qty_sistem_at' => $snapshotAt,
                'snapshot_fresh' => $isApplied || $snapshotAt !== null,
                'needs_snapshot' => ! $isApplied && $qtyFisik !== null && $snapshotAt === null,
                'qty_selisih' => $qtySelisih,
                'nilai_selisih' => $qtySelisih === null
                    ? null
                    : round($qtySelisih * (float) ($barang->harga_satuan ?? 0), 2),
                'catatan' => $opname !== null ? (string) ($barang->opname_catatan ?? '') : '',
                'status' => $this->statusFor($qtySelisih),
            ];
        });

        return [
            'activeGudang' => $gudang,
            'gudangUnits' => $gudangUnits,
            'opname' => $opname,
            'items' => $items,
            'metrics' => $this->metrics($opname, $gudang),
            'period' => $periode,
            'periodLabel' => $periodStart->locale('id')->translatedFormat('F Y'),
            'periodOptions' => $this->periodOptionLabels(),
            'pageSizes' => self::PAGE_SIZES,
            'filters' => [
                'status' => $status,
                'q' => $search,
                'per_page' => $perPage,
            ],
        ];
    }

    /**
     * @return array{drafts: LengthAwarePaginator}
     */
    public function getDraftsPageData(User $user): array
    {
        if ($user->hasAnyRole(['Super Admin', 'super_admin'])) {
            $gudangUnits = Gudang::query()
                ->select(['id', 'nama'])
                ->where('is_aktif', true)
                ->whereIn('jenis', ['gudang_umum', 'gudang_sukahati'])
                ->orderBy('nama')
                ->get();
        } else {
            $gudangUnits = $this->unitGudangService->getAssignedGudangUnits($user)
                ->whereIn('jenis', ['gudang_umum', 'gudang_sukahati'])
                ->values();
        }

        $drafts = StokOpname::query()
            ->with('gudang:id,nama')
            ->withCount([
                'details',
                'details as counted_details_count' => static fn (Builder $query): Builder => $query
                    ->whereNotNull('qty_fisik')
                    ->whereNotNull('qty_sistem_at'),
            ])
            ->where('status', 'draft')
            ->whereIn('gudang_id', $gudangUnits->modelKeys())
            ->orderByDesc('periode')
            ->orderByDesc('updated_at')
            ->paginate(15);

        return ['drafts' => $drafts];
    }

    /**
     * @param  array<int, array{barang_id: int, qty_fisik?: numeric-string|int|float|null, catatan?: string|null}>  $items
     */
    public function saveDraft(
        User $user,
        int|string|null $selectedGudangId,
        string $periode,
        array $items,
        ?int $snapshotBarangId = null,
    ): StokOpname {
        $gudang = $this->resolveGudang($user, $selectedGudangId);
        $periodStart = $this->periodStart($periode);

        return DB::transaction(function () use ($user, $gudang, $periodStart, $items, $snapshotBarangId): StokOpname {
            Gudang::query()->whereKey($gudang->getKey())->lockForUpdate()->firstOrFail();
            $opname = $this->draftFor($gudang, $periodStart, (int) $user->getKey());
            $systemBalances = [];
            if ($snapshotBarangId !== null) {
                $stockTotals = $this->stokService->totals([$snapshotBarangId], (int) $gudang->getKey());
                $systemBalances[$snapshotBarangId] = $this->balanceFor($snapshotBarangId, $stockTotals);
            }
            $this->saveItems($opname, $items, $snapshotBarangId, $systemBalances);

            return $opname;
        }, attempts: 3);
    }

    /**
     * @param  array<int, array{barang_id: int, qty_fisik?: numeric-string|int|float|null, catatan?: string|null}>  $items
     */
    public function apply(User $user, int|string|null $selectedGudangId, string $periode, array $items): StokOpname
    {
        $gudang = $this->resolveGudang($user, $selectedGudangId);
        $periodStart = $this->periodStart($periode);

        return DB::transaction(function () use ($user, $gudang, $periodStart, $items): StokOpname {
            Gudang::query()->whereKey($gudang->getKey())->lockForUpdate()->firstOrFail();
            $opname = $this->draftFor($gudang, $periodStart, (int) $user->getKey());
            $this->saveItems($opname, $items);

            if ($opname->details()
                ->where(static fn (Builder $query): Builder => $query
                    ->whereNull('qty_fisik')
                    ->orWhereNull('qty_sistem_at'))
                ->exists()) {
                throw ValidationException::withMessages([
                    'items' => 'Simpan ulang hitungan setiap barang agar memakai patokan stok sistem saat barang tersebut dihitung.',
                ]);
            }

            $details = $opname->details()->lockForUpdate()->get();
            foreach ($details as $detail) {
                if ((float) $detail->qty_fisik !== (float) $detail->qty_sistem && trim((string) $detail->catatan) === '') {
                    throw ValidationException::withMessages([
                        'items' => "Catatan selisih wajib diisi untuk barang {$detail->kode_barang}.",
                    ]);
                }
            }

            $opname->update([
                'status' => 'diterapkan',
                'diterapkan_oleh' => $user->getKey(),
                'diterapkan_at' => now(),
            ]);

            return $opname->fresh();
        }, attempts: 3);
    }

    public function resetDraft(User $user, int|string|null $selectedGudangId, string $periode): void
    {
        $gudang = $this->resolveGudang($user, $selectedGudangId);
        $periodStart = $this->periodStart($periode);

        DB::transaction(function () use ($gudang, $periodStart): void {
            $opname = StokOpname::query()
                ->where('gudang_id', $gudang->getKey())
                ->where('periode', $periodStart->toDateString())
                ->lockForUpdate()
                ->first();

            if ($opname === null) {
                return;
            }

            $this->ensureDraft($opname);
            $opname->details()->update([
                'qty_fisik' => null,
                'qty_sistem_at' => null,
                'catatan' => null,
            ]);
        }, attempts: 3);
    }

    public function appliedOpname(User $user, int|string|null $selectedGudangId, string $periode): StokOpname
    {
        $gudang = $this->resolveGudang($user, $selectedGudangId);
        $periodStart = $this->periodStart($periode);
        $opname = StokOpname::query()
            ->with(['details' => static fn ($details) => $details->orderBy('kode_barang')])
            ->where('gudang_id', $gudang->getKey())
            ->where('periode', $periodStart->toDateString())
            ->where('status', 'diterapkan')
            ->first();

        abort_if($opname === null, 404, 'Berita acara tersedia setelah penyesuaian diterapkan.');

        return $opname;
    }

    private function resolveGudang(User $user, int|string|null $selectedGudangId): Gudang
    {
        $gudang = $this->unitGudangService->resolveActiveGudang(
            $user,
            $selectedGudangId,
            ['gudang_umum', 'gudang_sukahati'],
        );

        abort_unless($gudang !== null, 403, 'Pilih gudang umum aktif yang ditugaskan kepada Anda.');

        return $gudang;
    }

    private function periodStart(string $periode): Carbon
    {
        abort_unless(in_array($periode, $this->availablePeriods(), true), 422, 'Periode opname tidak valid.');

        return Carbon::createFromFormat('Y-m-d', $periode.'-01')->startOfDay();
    }

    private function draftFor(Gudang $gudang, Carbon $periodStart, int $userId): StokOpname
    {
        $opname = StokOpname::query()
            ->where('gudang_id', $gudang->getKey())
            ->where('periode', $periodStart->toDateString())
            ->lockForUpdate()
            ->first();

        if ($opname !== null) {
            $this->ensureDraft($opname);

            return $opname;
        }

        $barangs = Barang::query()
            ->with('satuan:id,nama')
            ->where('gudang_id', $gudang->getKey())
            ->where('is_aktif', true)
            ->orderBy('id')
            ->get(['id', 'kode', 'nama', 'satuan_id', 'harga_satuan']);
        $stockTotals = $this->stokService->totals($barangs->modelKeys(), (int) $gudang->getKey());

        $opname = StokOpname::query()->create([
            'gudang_id' => $gudang->getKey(),
            'periode' => $periodStart->toDateString(),
            'tanggal_efektif' => $periodStart->copy()->endOfMonth()->toDateString(),
            'status' => 'draft',
            'dibuat_oleh' => $userId,
        ]);

        foreach ($barangs->chunk(250) as $chunk) {
            $rows = $chunk->map(fn (Barang $barang): array => [
                'stok_opname_id' => $opname->getKey(),
                'barang_id' => $barang->getKey(),
                'kode_barang' => $barang->kode,
                'nama_barang' => $barang->nama,
                'satuan' => $barang->satuan?->nama,
                'qty_sistem' => $this->balanceFor($barang->getKey(), $stockTotals),
                'qty_sistem_at' => null,
                'harga_satuan' => (float) ($barang->harga_satuan ?? 0),
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            if ($rows !== []) {
                StokOpnameDetail::query()->insert($rows);
            }
        }

        return $opname;
    }

    /**
     * @param  array<int, array{barang_id: int, qty_fisik?: numeric-string|int|float|null, catatan?: string|null}>  $items
     */
    private function saveItems(
        StokOpname $opname,
        array $items,
        ?int $snapshotBarangId = null,
        array $systemBalances = [],
    ): void {
        $barangIds = collect($items)->pluck('barang_id')->map(static fn (mixed $id): int => (int) $id);
        $details = $opname->details()
            ->whereIn('barang_id', $barangIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('barang_id');

        if ($details->count() !== $barangIds->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Ada barang yang tidak termasuk dalam lembar opname periode dan gudang ini.',
            ]);
        }

        if ($snapshotBarangId !== null && ! $details->has($snapshotBarangId)) {
            throw ValidationException::withMessages([
                'items' => 'Barang yang dipilih untuk menyimpan snapshot tidak termasuk dalam draf ini.',
            ]);
        }

        foreach ($items as $item) {
            $barangId = (int) $item['barang_id'];
            $detail = $details->get($barangId);
            $qtyFisik = $item['qty_fisik'] ?? null;
            $attributes = [
                'qty_fisik' => $qtyFisik,
                'catatan' => trim((string) ($item['catatan'] ?? '')) ?: null,
            ];

            if ($snapshotBarangId === $barangId) {
                if (! array_key_exists($barangId, $systemBalances)) {
                    throw ValidationException::withMessages([
                        'items' => 'Snapshot stok sistem tidak dapat dibuat. Silakan coba simpan kembali.',
                    ]);
                }

                $attributes['qty_sistem'] = $systemBalances[$barangId];
                $attributes['qty_sistem_at'] = now();
            } elseif ($qtyFisik === null || $this->quantityChanged($detail->qty_fisik, $qtyFisik)) {
                $attributes['qty_sistem_at'] = null;
            }

            $detail->update($attributes);
        }
    }

    private function quantityChanged(string|int|float|null $currentQuantity, string|int|float|null $newQuantity): bool
    {
        if ($currentQuantity === null || $newQuantity === null) {
            return $currentQuantity !== $newQuantity;
        }

        return round((float) $currentQuantity, 3) !== round((float) $newQuantity, 3);
    }

    private function ensureDraft(StokOpname $opname): void
    {
        if ($opname->status !== 'draft') {
            throw ValidationException::withMessages([
                'periode' => 'Opname periode ini sudah diterapkan dan tidak dapat diubah.',
            ]);
        }
    }

    /**
     * @param  array{awal: Collection<int|string, float>, masuk: Collection<int|string, float>, keluar: Collection<int|string, float>}  $totals
     */
    private function balanceFor(int|string $barangId, array $totals): float
    {
        return round(max(
            0,
            (float) ($totals['awal'][$barangId] ?? 0)
                + (float) ($totals['masuk'][$barangId] ?? 0)
                - (float) ($totals['keluar'][$barangId] ?? 0),
        ), 3);
    }

    /**
     * @return array<string, int|float>
     */
    private function metrics(?StokOpname $opname, Gudang $gudang): array
    {
        if ($opname === null) {
            return [
                ...$this->emptyMetrics(),
                'total' => Barang::query()
                    ->where('gudang_id', $gudang->getKey())
                    ->where('is_aktif', true)
                    ->count(),
            ];
        }

        $isDraft = $opname->status === 'draft';
        $countedCondition = $isDraft
            ? 'qty_fisik IS NOT NULL AND qty_sistem_at IS NOT NULL'
            : 'qty_fisik IS NOT NULL';
        $row = DB::table('stok_opname_details')
            ->where('stok_opname_id', $opname->getKey())
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("COUNT(CASE WHEN {$countedCondition} THEN 1 END) AS dihitung")
            ->selectRaw("SUM(CASE WHEN {$countedCondition} AND qty_fisik = qty_sistem THEN 1 ELSE 0 END) AS sesuai")
            ->selectRaw("SUM(CASE WHEN {$countedCondition} AND qty_fisik < qty_sistem THEN 1 ELSE 0 END) AS kurang")
            ->selectRaw("SUM(CASE WHEN {$countedCondition} AND qty_fisik > qty_sistem THEN 1 ELSE 0 END) AS lebih")
            ->selectRaw("SUM(CASE WHEN {$countedCondition} AND qty_fisik < qty_sistem THEN (qty_fisik - qty_sistem) * harga_satuan ELSE 0 END) AS nilai_kurang")
            ->selectRaw("SUM(CASE WHEN {$countedCondition} AND qty_fisik > qty_sistem THEN (qty_fisik - qty_sistem) * harga_satuan ELSE 0 END) AS nilai_lebih")
            ->selectRaw("SUM(CASE WHEN {$countedCondition} THEN (qty_fisik - qty_sistem) * harga_satuan ELSE 0 END) AS nilai_bersih")
            ->selectRaw(
                'SUM(CASE WHEN qty_fisik IS NOT NULL AND qty_sistem_at IS NULL THEN 1 ELSE 0 END) AS needs_snapshot',
            )
            ->first();

        $total = (int) $row->total;
        $dihitung = (int) $row->dihitung;

        return [
            'total' => $total,
            'dihitung' => $dihitung,
            'belum' => $total - $dihitung,
            'needs_snapshot' => $isDraft ? (int) $row->needs_snapshot : 0,
            'sesuai' => (int) $row->sesuai,
            'kurang' => (int) $row->kurang,
            'lebih' => (int) $row->lebih,
            'nilai_kurang' => (float) $row->nilai_kurang,
            'nilai_lebih' => (float) $row->nilai_lebih,
            'nilai_bersih' => (float) $row->nilai_bersih,
            'progress' => $total === 0 ? 0.0 : round($dihitung / $total * 100, 1),
            'akurasi' => $dihitung === 0 ? 0.0 : round((int) $row->sesuai / $dihitung * 100, 1),
        ];
    }

    /**
     * @return array<string, int|float>
     */
    private function emptyMetrics(): array
    {
        return [
            'total' => 0,
            'dihitung' => 0,
            'belum' => 0,
            'needs_snapshot' => 0,
            'sesuai' => 0,
            'kurang' => 0,
            'lebih' => 0,
            'nilai_kurang' => 0.0,
            'nilai_lebih' => 0.0,
            'nilai_bersih' => 0.0,
            'progress' => 0.0,
            'akurasi' => 0.0,
        ];
    }

    private function statusFor(?float $selisih): string
    {
        return match (true) {
            $selisih === null => 'belum',
            $selisih < 0 => 'kurang',
            $selisih > 0 => 'lebih',
            default => 'sesuai',
        };
    }

    /**
     * @return array<string, string>
     */
    private function periodOptionLabels(): array
    {
        return collect($this->availablePeriods())
            ->mapWithKeys(static function (string $period): array {
                $date = Carbon::createFromFormat('Y-m-d', $period.'-01');

                return [$period => $date->locale('id')->translatedFormat('F Y')];
            })
            ->all();
    }
}
