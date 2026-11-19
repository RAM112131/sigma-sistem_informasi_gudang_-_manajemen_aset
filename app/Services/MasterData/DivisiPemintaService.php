<?php

declare(strict_types=1);

namespace App\Services\MasterData;

use App\Models\DivisiPeminta;
use App\Models\Gudang;
use App\Models\PengeluaranUnit;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DivisiPemintaService
{
    private const array WAREHOUSE_TYPES = [
        'unit_bisnis',
        'gudang_sukahati',
        'gudang_umum',
    ];

    /**
     * @return array{
     *     areaWarehouses: Collection<int, Gudang>,
     *     activeAreaWarehouse: ?Gudang,
     *     areas: LengthAwarePaginator,
     *     orphanedAreaNames: Collection<int, object>,
     *     orphanedAreaCount: int,
     *     areaMonthLabel: string,
     *     areaPeriods: Collection<int, array{value: string, label: string}>,
     *     selectedAreaPeriod: string
     * }
     */
    public function getPageData(
        ?User $user,
        int|string|null $selectedWarehouseId,
        string $search = '',
        ?string $selectedPeriod = null,
    ): array {
        $warehouses = $user instanceof User
            ? $this->availableWarehouses($user)
            : collect();

        $activeWarehouse = $this->resolveDisplayWarehouse($warehouses, $selectedWarehouseId);
        $selectedPeriod ??= now()->format('Y-m');
        $periodStart = CarbonImmutable::parse($selectedPeriod.'-01')->startOfMonth();
        $monthStart = $periodStart->toDateString();
        $monthEnd = $periodStart->endOfMonth()->toDateString();
        $areaPeriods = collect(range(0, 11))
            ->map(static function (int $monthsAgo): array {
                $month = CarbonImmutable::now()->startOfMonth()->subMonths($monthsAgo);

                return [
                    'value' => $month->format('Y-m'),
                    'label' => $month->locale('id')->translatedFormat('F Y'),
                ];
            });

        if (! $areaPeriods->contains('value', $selectedPeriod)) {
            $areaPeriods->push([
                'value' => $selectedPeriod,
                'label' => $periodStart->locale('id')->translatedFormat('F Y'),
            ]);
            $areaPeriods = $areaPeriods->sortByDesc('value')->values();
        }

        $areas = $activeWarehouse instanceof Gudang
            ? $this->areaListing($activeWarehouse, $monthStart, $monthEnd, $search)
            : $this->emptyPaginator();

        if ($activeWarehouse instanceof Gudang && $areas->getCollection()->isNotEmpty()) {
            $spellings = PengeluaranUnit::query()
                ->whereIn('divisi_peminta_id', $areas->getCollection()->pluck('id'))
                ->select(['divisi_peminta_id', 'bagian_peminta'])
                ->distinct()
                ->get()
                ->groupBy('divisi_peminta_id');

            foreach ($areas as $area) {
                $canonicalName = mb_strtolower(trim((string) $area->nama));
                $area->known_spellings = ($spellings->get($area->id) ?? collect())
                    ->pluck('bagian_peminta')
                    ->filter(static fn (string $name): bool => mb_strtolower(trim($name)) !== $canonicalName)
                    ->unique()
                    ->values();
            }
        }

        $orphanedAreaSummary = $activeWarehouse instanceof Gudang
            ? $this->orphanedAreaSummary($activeWarehouse, $monthStart, $monthEnd)
            : ['names' => collect(), 'count' => 0];

        return [
            'areaWarehouses' => $warehouses,
            'activeAreaWarehouse' => $activeWarehouse,
            'areas' => $areas,
            'orphanedAreaNames' => $orphanedAreaSummary['names'],
            'orphanedAreaCount' => $orphanedAreaSummary['count'],
            'areaMonthLabel' => $periodStart->locale('id')->translatedFormat('F Y'),
            'areaPeriods' => $areaPeriods,
            'selectedAreaPeriod' => $selectedPeriod,
        ];
    }

    public function create(User $user, int|string|null $selectedWarehouseId, string $name): DivisiPeminta
    {
        return DB::transaction(function () use ($user, $selectedWarehouseId, $name): DivisiPeminta {
            $warehouse = $this->resolveWriteWarehouse($user, $selectedWarehouseId);
            Gudang::query()->whereKey($warehouse->getKey())->lockForUpdate()->firstOrFail();

            $normalizedName = mb_strtolower(trim($name));
            $existingArea = DivisiPeminta::query()
                ->where('gudang_id', $warehouse->getKey())
                ->whereRaw('LOWER(TRIM(nama)) = ?', [$normalizedName])
                ->lockForUpdate()
                ->first();

            if ($existingArea instanceof DivisiPeminta) {
                throw ValidationException::withMessages([
                    'nama' => 'Nama area atau peminta tersebut sudah terdaftar.',
                ]);
            }

            $area = DivisiPeminta::query()->create([
                'gudang_id' => $warehouse->getKey(),
                'nama' => trim($name),
                'is_aktif' => true,
            ]);

            $this->linkLegacyTransactions($area);

            return $area;
        }, attempts: 3);
    }

    public function update(
        User $user,
        DivisiPeminta $area,
        int|string|null $selectedWarehouseId,
        string $name,
    ): DivisiPeminta {
        return DB::transaction(function () use ($user, $area, $selectedWarehouseId, $name): DivisiPeminta {
            $warehouse = $this->resolveWriteWarehouse($user, $selectedWarehouseId);
            abort_unless((int) $area->gudang_id === (int) $warehouse->getKey(), 404);
            Gudang::query()->whereKey($warehouse->getKey())->lockForUpdate()->firstOrFail();

            $normalizedName = mb_strtolower(trim($name));
            $duplicate = DivisiPeminta::query()
                ->where('gudang_id', $warehouse->getKey())
                ->where('id', '!=', $area->getKey())
                ->whereRaw('LOWER(TRIM(nama)) = ?', [$normalizedName])
                ->lockForUpdate()
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'nama' => 'Nama area atau peminta tersebut sudah terdaftar.',
                ]);
            }

            $area->update(['nama' => trim($name)]);
            $this->linkLegacyTransactions($area);

            return $area->fresh();
        }, attempts: 3);
    }

    public function toggle(User $user, DivisiPeminta $area, int|string|null $selectedWarehouseId): DivisiPeminta
    {
        DB::transaction(function () use ($user, $area, $selectedWarehouseId): void {
            $warehouse = $this->resolveWriteWarehouse($user, $selectedWarehouseId);
            abort_unless((int) $area->gudang_id === (int) $warehouse->getKey(), 404);
            $area->update(['is_aktif' => ! $area->is_aktif]);
        });

        return $area->refresh();
    }

    public function delete(User $user, DivisiPeminta $area, int|string|null $selectedWarehouseId): void
    {
        DB::transaction(function () use ($user, $area, $selectedWarehouseId): void {
            $warehouse = $this->resolveWriteWarehouse($user, $selectedWarehouseId);
            abort_unless((int) $area->gudang_id === (int) $warehouse->getKey(), 404);
            $area->delete();
        });
    }

    public function findOrCreateForRecording(Gudang $warehouse, string $name): DivisiPeminta
    {
        $normalizedName = mb_strtolower(trim($name));
        $area = DivisiPeminta::query()
            ->where('gudang_id', $warehouse->getKey())
            ->whereRaw('LOWER(TRIM(nama)) = ?', [$normalizedName])
            ->lockForUpdate()
            ->first();

        if (! $area instanceof DivisiPeminta) {
            $area = DivisiPeminta::query()->create([
                'gudang_id' => $warehouse->getKey(),
                'nama' => trim($name),
                'is_aktif' => true,
            ]);
        } elseif (! $area->is_aktif) {
            $area->update(['is_aktif' => true]);
        }

        $this->linkLegacyTransactions($area);

        return $area;
    }

    /**
     * @return Collection<int, Gudang>
     */
    private function availableWarehouses(User $user): Collection
    {
        return Gudang::query()
            ->where('is_aktif', true)
            ->whereIn('jenis', self::WAREHOUSE_TYPES)
            ->when(
                ! $user->hasAnyRole(['Super Admin', 'super_admin']),
                fn (Builder $query): Builder => $query->whereHas(
                    'users',
                    fn (Builder $users): Builder => $users->whereKey($user->getKey()),
                ),
            )
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama', 'jenis']);
    }

    /**
     * @param  Collection<int, Gudang>  $warehouses
     */
    private function resolveDisplayWarehouse(Collection $warehouses, int|string|null $warehouseId): ?Gudang
    {
        if ($warehouseId !== null && $warehouseId !== '') {
            return $warehouses->firstWhere('id', (int) $warehouseId);
        }

        return $warehouses->count() === 1 ? $warehouses->first() : null;
    }

    private function resolveWriteWarehouse(User $user, int|string|null $warehouseId): Gudang
    {
        $warehouses = $this->availableWarehouses($user);
        $warehouse = $this->resolveDisplayWarehouse($warehouses, $warehouseId);

        abort_unless($warehouse instanceof Gudang, 404, 'Gudang area tidak ditemukan atau tidak dapat diakses.');

        return $warehouse;
    }

    private function areaListing(
        Gudang $warehouse,
        string $monthStart,
        string $monthEnd,
        string $search,
    ): LengthAwarePaginator {
        return DB::table('divisi_pemintas as area')
            ->leftJoin('pengeluaran_units as issue', function (JoinClause $join) use ($monthStart, $monthEnd): void {
                $join->on('issue.gudang_id', '=', 'area.gudang_id')
                    ->whereBetween('issue.tanggal', [$monthStart, $monthEnd])
                    ->where(function (JoinClause $join): void {
                        $join->on('issue.divisi_peminta_id', '=', 'area.id')
                            ->orWhereRaw('(issue.divisi_peminta_id IS NULL AND LOWER(TRIM(issue.bagian_peminta)) = LOWER(TRIM(area.nama)))');
                    });
            })
            ->leftJoin('pengeluaran_unit_details as detail', 'detail.pengeluaran_unit_id', '=', 'issue.id')
            ->where('area.gudang_id', $warehouse->getKey())
            ->when($search !== '', fn ($query) => $query->where('area.nama', 'LIKE', "%{$search}%"))
            ->select([
                'area.id',
                'area.gudang_id',
                'area.nama',
                'area.kode',
                'area.penanggung_jawab',
                'area.is_aktif',
            ])
            ->selectRaw('COUNT(DISTINCT issue.id) AS request_count')
            ->selectRaw('COALESCE(SUM(detail.subtotal_nilai), 0) AS expenditure_total')
            ->groupBy([
                'area.id',
                'area.gudang_id',
                'area.nama',
                'area.kode',
                'area.penanggung_jawab',
                'area.is_aktif',
            ])
            ->orderByDesc('request_count')
            ->orderBy('area.nama')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * @return array{names: Collection<int, object>, count: int}
     */
    private function orphanedAreaSummary(Gudang $warehouse, string $monthStart, string $monthEnd): array
    {
        $names = DB::table('pengeluaran_units as issue')
            ->where('issue.gudang_id', $warehouse->getKey())
            ->whereBetween('issue.tanggal', [$monthStart, $monthEnd])
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('divisi_pemintas as area')
                    ->whereColumn('area.gudang_id', 'issue.gudang_id')
                    ->where(function ($query): void {
                        $query->whereColumn('area.id', 'issue.divisi_peminta_id')
                            ->orWhere(function ($query): void {
                                $query->whereNull('issue.divisi_peminta_id')
                                    ->whereRaw('LOWER(TRIM(area.nama)) = LOWER(TRIM(issue.bagian_peminta))');
                            });
                    });
            })
            ->selectRaw('TRIM(issue.bagian_peminta) AS nama, COUNT(*) AS request_count, COUNT(*) OVER () AS orphaned_count')
            ->groupBy('issue.bagian_peminta')
            ->orderByDesc('request_count')
            ->limit(5)
            ->get();

        return [
            'names' => $names,
            'count' => (int) ($names->first()->orphaned_count ?? 0),
        ];
    }

    private function linkLegacyTransactions(DivisiPeminta $area): void
    {
        PengeluaranUnit::query()
            ->where('gudang_id', $area->gudang_id)
            ->whereNull('divisi_peminta_id')
            ->whereRaw('LOWER(TRIM(bagian_peminta)) = ?', [mb_strtolower(trim($area->nama))])
            ->update(['divisi_peminta_id' => $area->getKey()]);
    }

    private function emptyPaginator(): LengthAwarePaginator
    {
        return new \Illuminate\Pagination\LengthAwarePaginator(
            items: [],
            total: 0,
            perPage: 20,
            currentPage: 1,
        );
    }
}
