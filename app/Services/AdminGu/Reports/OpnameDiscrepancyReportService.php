<?php

declare(strict_types=1);

namespace App\Services\AdminGu\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OpnameDiscrepancyReportService
{
    private const int PAGE_SIZE = 50;

    private const array VERIFIED_STATUSES = [
        'terverifikasi',
        'perlu_tindak_lanjut',
        'sesuai',
        'verified',
        'applied',
    ];

    public function __construct(private readonly ReportCache $cache) {}

    /**
     * @param  array{category_id?: int|string|null, search?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function report(int $warehouseId, string $period, array $filters, bool $paginate = false): array
    {
        return $this->cache->remember($period, 'selisih', $warehouseId, [...$filters, 'paginated' => $paginate], function () use ($warehouseId, $period, $filters, $paginate): array {
            $start = $period.'-01';
            $end = Carbon::parse($start)->endOfMonth()->toDateString();
            $opnameQuery = DB::table('stok_opname_details as d')
                ->join('stok_opnames as h', 'h.id', '=', 'd.stok_opname_id')
                ->join('barangs as b', 'b.id', '=', 'd.barang_id')
                ->where('h.gudang_id', $warehouseId)
                ->whereIn('h.status', ['diterapkan', 'applied'])
                ->where('h.periode', $start)
                ->whereNotNull('d.qty_fisik')
                ->whereColumn('d.qty_fisik', '<>', 'd.qty_sistem');
            $this->applyItemFilters($opnameQuery, $filters, 'b', 'd');
            $opnameRows = $opnameQuery
                ->select([
                    'd.kode_barang as kode',
                    'd.nama_barang as nama',
                    'd.satuan',
                    'd.qty_sistem',
                    'd.qty_fisik',
                    'd.harga_satuan',
                    'd.catatan',
                ])
                ->selectRaw('(d.qty_fisik - d.qty_sistem) AS selisih_qty')
                ->selectRaw('((d.qty_fisik - d.qty_sistem) * d.harga_satuan) AS selisih_nilai');
            $opnameTotals = DB::query()
                ->fromSub(clone $opnameRows, 'opname_rows')
                ->selectRaw('COALESCE(SUM(selisih_qty), 0) AS difference_qty')
                ->selectRaw('COALESCE(SUM(selisih_nilai), 0) AS difference_value')
                ->first();

            if ($paginate) {
                $opnameItems = $opnameRows
                    ->orderBy('kode')
                    ->paginate(self::PAGE_SIZE, ['*'], 'opname_page', (int) ($filters['opname_page'] ?? 1))
                    ->withQueryString();
                $opnameItems->setCollection($opnameItems->getCollection()->map(
                    fn (object $row): array => $this->formatOpnameItem($row),
                ));
            } else {
                $opnameItems = $opnameRows
                    ->orderBy('kode')
                    ->get()
                    ->map(fn (object $row): array => $this->formatOpnameItem($row))
                    ->all();
            }

            $damagedUnion = $this->damagedSupplierItems($warehouseId, $start, $end, $filters)
                ->unionAll($this->damagedTransferItems($warehouseId, $start, $end, $filters));
            $damagedQuery = DB::query()
                ->fromSub($damagedUnion, 'damaged')
                ->select([
                    'damaged.verified_at',
                    'damaged.document_number',
                    'damaged.source_name',
                    'damaged.kode',
                    'damaged.nama',
                    'damaged.satuan',
                    'damaged.handling_status',
                    'damaged.damaged_qty',
                    'damaged.hpp',
                    'damaged.impact_value',
                    'damaged.note',
                    'damaged.source_type',
                ]);
            $damagedTotals = DB::query()
                ->fromSub(clone $damagedQuery, 'damaged_rows')
                ->selectRaw('COALESCE(SUM(damaged_qty), 0) AS damaged_qty')
                ->selectRaw('COALESCE(SUM(impact_value), 0) AS damaged_value')
                ->first();

            if ($paginate) {
                $damagedItems = $damagedQuery
                    ->orderByDesc('verified_at')
                    ->orderByDesc('document_number')
                    ->paginate(self::PAGE_SIZE, ['*'], 'damaged_page', (int) ($filters['damaged_page'] ?? 1))
                    ->withQueryString();
                $damagedItems->setCollection($damagedItems->getCollection()->map(
                    fn (object $row): array => $this->formatDamagedItem($row),
                ));
            } else {
                $damagedItems = $damagedQuery
                    ->orderByDesc('verified_at')
                    ->orderByDesc('document_number')
                    ->get()
                    ->map(fn (object $row): array => $this->formatDamagedItem($row))
                    ->all();
            }

            return [
                'opname_items' => $opnameItems,
                'damaged_items' => $damagedItems,
                'totals' => [
                    'difference_qty' => (float) ($opnameTotals->difference_qty ?? 0),
                    'difference_value' => (float) ($opnameTotals->difference_value ?? 0),
                    'damaged_qty' => (float) ($damagedTotals->damaged_qty ?? 0),
                    'damaged_value' => (float) ($damagedTotals->damaged_value ?? 0),
                ],
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function damagedSupplierItems(int $warehouseId, string $start, string $end, array $filters): Builder
    {
        $damaged = 'COALESCE(d.qty_rusak_diperiksa, d.qty_rusak, 0)';
        $query = DB::table('penerimaan_units as h')
            ->join('penerimaan_unit_details as d', 'd.penerimaan_unit_id', '=', 'h.id')
            ->leftJoin('barangs as b', 'b.id', '=', 'd.barang_id')
            ->where('h.gudang_id', $warehouseId)
            ->whereIn('h.status_verifikasi', self::VERIFIED_STATUSES)
            ->whereBetween('h.diverifikasi_at', [$start.' 00:00:00', $end.' 23:59:59'])
            ->whereRaw("{$damaged} > 0");
        $this->applyItemFilters($query, $filters, 'b', 'd');

        return $query
            ->select([
                'h.diverifikasi_at as verified_at',
                'h.nomor_rcv as document_number',
                'h.nama_supplier as source_name',
                'd.kode_barang as kode',
                'd.nama_barang as nama',
                'd.satuan',
                'h.status_verifikasi as handling_status',
            ])
            ->selectRaw("{$damaged} AS damaged_qty")
            ->selectRaw('COALESCE(NULLIF(d.harga_beli, 0), b.harga_satuan, 0) AS hpp')
            ->selectRaw("({$damaged}) * COALESCE(NULLIF(d.harga_beli, 0), b.harga_satuan, 0) AS impact_value")
            ->selectRaw('COALESCE(h.catatan_verifikasi, h.catatan, d.catatan) AS note')
            ->selectRaw("'Penerimaan Supplier' AS source_type");
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function damagedTransferItems(int $warehouseId, string $start, string $end, array $filters): Builder
    {
        $damaged = 'COALESCE(h.qty_rusak_diperiksa, h.qty_rusak, 0)';
        $query = DB::table('penerimaan_pemindahan_units as h')
            ->join('transfer_barangs as t', 't.id', '=', 'h.transfer_barang_id')
            ->leftJoin('barangs as b', 'b.id', '=', 'h.barang_id')
            ->leftJoin('gudangs as origin', 'origin.id', '=', 't.gudang_asal_id')
            ->where('h.gudang_id', $warehouseId)
            ->whereIn('h.status_verifikasi', self::VERIFIED_STATUSES)
            ->whereBetween('h.diverifikasi_at', [$start.' 00:00:00', $end.' 23:59:59'])
            ->whereRaw("{$damaged} > 0");
        $this->applyItemFilters($query, $filters, 'b', null);

        return $query
            ->select([
                'h.diverifikasi_at as verified_at',
                't.nomor_sj as document_number',
                'origin.nama as source_name',
                'b.kode',
                'b.nama',
                DB::raw("'—' AS satuan"),
                'h.status_verifikasi as handling_status',
            ])
            ->selectRaw("{$damaged} AS damaged_qty")
            ->selectRaw('COALESCE(b.harga_satuan, 0) AS hpp')
            ->selectRaw("({$damaged}) * COALESCE(b.harga_satuan, 0) AS impact_value")
            ->selectRaw("COALESCE(NULLIF(h.catatan_verifikasi, ''), h.catatan_discrepancy) AS note")
            ->selectRaw("'Transfer Store' AS source_type");
    }

    /**
     * @return array<string, mixed>
     */
    private function formatOpnameItem(object $row): array
    {
        return [
            'kode' => $row->kode,
            'nama' => $row->nama,
            'satuan' => $row->satuan ?? '—',
            'saldo_sistem' => (float) $row->qty_sistem,
            'stok_fisik' => (float) $row->qty_fisik,
            'selisih_qty' => (float) $row->selisih_qty,
            'hpp' => (float) $row->harga_satuan,
            'selisih_nilai' => (float) $row->selisih_nilai,
            'catatan' => $row->catatan,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDamagedItem(object $row): array
    {
        return [
            'document_number' => $row->document_number,
            'source_name' => $row->source_name ?: ($row->source_type === 'Transfer Store' ? 'Gudang Store' : '—'),
            'kode' => $row->kode ?? '—',
            'nama' => $row->nama ?? 'Barang tidak tersedia',
            'satuan' => $row->satuan ?? '—',
            'damaged_qty' => (float) $row->damaged_qty,
            'hpp' => (float) $row->hpp,
            'impact_value' => (float) $row->impact_value,
            'handling_status' => $row->handling_status === 'perlu_tindak_lanjut'
                ? 'Perlu tindak lanjut'
                : 'Penanganan belum tercatat',
            'note' => $row->note,
            'source_type' => $row->source_type,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyItemFilters(Builder $query, array $filters, string $barangAlias, ?string $detailAlias): void
    {
        if (! empty($filters['category_id'])) {
            $query->where("{$barangAlias}.kategori_id", (int) $filters['category_id']);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(static function (Builder $matching) use ($operator, $search, $barangAlias, $detailAlias): void {
                $matching->where("{$barangAlias}.kode", $operator, '%'.$search.'%')
                    ->orWhere("{$barangAlias}.nama", $operator, '%'.$search.'%');
                if ($detailAlias !== null) {
                    $matching->orWhere("{$detailAlias}.kode_barang", $operator, '%'.$search.'%')
                        ->orWhere("{$detailAlias}.nama_barang", $operator, '%'.$search.'%');
                }
            });
        }
    }
}
