<?php

declare(strict_types=1);

namespace App\Services\AdminGu\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DivisionMatrixReportService
{
    private const int PAGE_SIZE = 50;

    public function __construct(private readonly ReportCache $cache) {}

    /**
     * @param  array{category_id?: int|string|null, search?: string|null, page?: int}  $filters
     * @return array<string, mixed>
     */
    public function report(int $warehouseId, string $period, array $filters, bool $paginate = true): array
    {
        return $this->cache->remember($period, 'matriks', $warehouseId, [...$filters, 'paginated' => $paginate], function () use ($warehouseId, $period, $filters, $paginate): array {
            $start = $period.'-01';
            $end = Carbon::parse($start)->endOfMonth()->toDateString();
            $divisions = DB::table('divisi_pemintas')
                ->where('gudang_id', $warehouseId)
                ->where('is_aktif', true)
                ->orderBy('nama')
                ->get(['id', 'nama'])
                ->map(static fn (object $division): array => [
                    'id' => (int) $division->id,
                    'nama' => $division->nama,
                ])
                ->all();

            $netQuantity = 'GREATEST(COALESCE(d.qty_keluar_rekonsiliasi, d.qty) - '.
                'COALESCE(d.qty_kembali_rekonsiliasi, d.qty_kembali, 0), 0)';
            $unitCost = 'COALESCE(NULLIF(d.harga_hpp, 0), b.harga_satuan, 0)';
            $aggregates = DB::table('pengeluaran_unit_details as d')
                ->join('pengeluaran_units as h', 'h.id', '=', 'd.pengeluaran_unit_id')
                ->leftJoin('barangs as b', 'b.id', '=', 'd.barang_id')
                ->where('h.gudang_id', $warehouseId)
                ->whereIn('h.status_verifikasi', ['terverifikasi', 'verified', 'applied'])
                ->whereBetween('h.tanggal', [$start, $end])
                ->whereNotNull('d.barang_id')
                ->select('d.barang_id');

            foreach ($divisions as $division) {
                $id = $division['id'];
                $divisionMatch = '(h.divisi_peminta_id = ? OR (h.divisi_peminta_id IS NULL AND LOWER(TRIM(h.bagian_peminta)) = LOWER(?)))';
                $aggregates->selectRaw(
                    "COALESCE(SUM(CASE WHEN {$divisionMatch} THEN {$netQuantity} ELSE 0 END), 0) AS division_{$id}_qty",
                    [$id, $division['nama']],
                )->selectRaw(
                    "COALESCE(SUM(CASE WHEN {$divisionMatch} THEN ({$netQuantity}) * ({$unitCost}) ELSE 0 END), 0) AS division_{$id}_value",
                    [$id, $division['nama']],
                );
            }

            $aggregates->selectRaw("COALESCE(SUM({$netQuantity}), 0) AS total_qty")
                ->selectRaw("COALESCE(SUM(({$netQuantity}) * ({$unitCost})), 0) AS total_value")
                ->groupBy('d.barang_id');

            $itemsQuery = DB::table('barangs as b')
                ->leftJoin('satuans as u', 'u.id', '=', 'b.satuan_id')
                ->leftJoinSub($aggregates, 'm', 'm.barang_id', '=', 'b.id')
                ->where('b.gudang_id', $warehouseId)
                ->where('b.is_aktif', true)
                ->whereNull('b.deleted_at');
            $this->applyItemFilters($itemsQuery, $filters);
            $totalsQuery = clone $itemsQuery;
            $totalsQuery->selectRaw('COALESCE(SUM(COALESCE(m.total_qty, 0)), 0) AS total_qty')
                ->selectRaw('COALESCE(SUM(COALESCE(m.total_value, 0)), 0) AS total_value');
            foreach ($divisions as $division) {
                $id = $division['id'];
                $totalsQuery->selectRaw("COALESCE(SUM(COALESCE(m.division_{$id}_qty, 0)), 0) AS division_{$id}_qty")
                    ->selectRaw("COALESCE(SUM(COALESCE(m.division_{$id}_value, 0)), 0) AS division_{$id}_value");
            }
            $totalsRow = $totalsQuery->first();

            $itemsQuery->select(['b.id', 'b.kode', 'b.nama', 'u.nama as satuan'])
                ->selectRaw('COALESCE(m.total_qty, 0) AS total_qty')
                ->selectRaw('COALESCE(m.total_value, 0) AS total_value');

            foreach ($divisions as $division) {
                $id = $division['id'];
                $itemsQuery->selectRaw("COALESCE(m.division_{$id}_qty, 0) AS division_{$id}_qty")
                    ->selectRaw("COALESCE(m.division_{$id}_value, 0) AS division_{$id}_value");
            }

            $itemsQuery->orderBy('b.kode');
            $itemsPage = $paginate
                ? $itemsQuery->paginate(self::PAGE_SIZE)->withQueryString()
                : null;
            $rows = $itemsPage?->getCollection() ?? $itemsQuery->get();
            $totals = [
                'qty' => (float) ($totalsRow->total_qty ?? 0),
                'value' => (float) ($totalsRow->total_value ?? 0),
                'divisions' => [],
            ];
            foreach ($divisions as $division) {
                $id = $division['id'];
                $totals['divisions'][$id] = [
                    'qty' => (float) ($totalsRow->{'division_'.$id.'_qty'} ?? 0),
                    'value' => (float) ($totalsRow->{'division_'.$id.'_value'} ?? 0),
                ];
            }

            $items = $rows->map(static function (object $row) use ($divisions): array {
                $divisionValues = [];
                foreach ($divisions as $division) {
                    $id = $division['id'];
                    $qty = (float) ($row->{'division_'.$id.'_qty'} ?? 0);
                    $value = (float) ($row->{'division_'.$id.'_value'} ?? 0);
                    $divisionValues[$id] = ['qty' => $qty, 'value' => $value];
                }

                $qty = (float) $row->total_qty;
                $value = (float) $row->total_value;

                return [
                    'kode' => $row->kode,
                    'nama' => $row->nama,
                    'satuan' => $row->satuan ?? '—',
                    'divisions' => $divisionValues,
                    'total_qty' => $qty,
                    'total_value' => $value,
                ];
            });
            if ($itemsPage !== null) {
                $itemsPage->setCollection($items);
                $items = $itemsPage;
            } else {
                $items = $items->all();
            }

            return ['divisions' => $divisions, 'items' => $items, 'totals' => $totals];
        });
    }

    /**
     * @param  array{category_id?: int|string|null, search?: string|null}  $filters
     */
    private function applyItemFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['category_id'])) {
            $query->where('b.kategori_id', (int) $filters['category_id']);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(static function (Builder $matching) use ($operator, $search): void {
                $matching->where('b.kode', $operator, '%'.$search.'%')
                    ->orWhere('b.nama', $operator, '%'.$search.'%');
            });
        }
    }
}
