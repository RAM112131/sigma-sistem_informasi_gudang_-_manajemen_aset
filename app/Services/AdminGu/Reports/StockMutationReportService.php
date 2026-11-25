<?php

declare(strict_types=1);

namespace App\Services\AdminGu\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockMutationReportService
{
    private const int PAGE_SIZE = 50;

    private const array VERIFIED_RECEIPT_STATUSES = [
        'terverifikasi',
        'perlu_tindak_lanjut',
        'sesuai',
        'verified',
        'applied',
    ];

    public function __construct(private readonly ReportCache $cache) {}

    /**
     * @param  array{category_id?: int|string|null, search?: string|null, page?: int}  $filters
     * @return array<string, mixed>
     */
    public function report(int $warehouseId, string $period, array $filters, bool $paginate = true): array
    {
        return $this->cache->remember($period, 'mutasi', $warehouseId, [...$filters, 'paginated' => $paginate], function () use ($warehouseId, $period, $filters, $paginate): array {
            $start = $period.'-01';
            $end = Carbon::parse($start)->endOfMonth()->toDateString();
            $aggregate = $this->movementAggregate($warehouseId, $start, $end);
            $query = DB::table('barangs as b')
                ->leftJoin('kategori_barangs as c', 'c.id', '=', 'b.kategori_id')
                ->leftJoin('satuans as u', 'u.id', '=', 'b.satuan_id')
                ->leftJoinSub($aggregate, 'm', 'm.barang_id', '=', 'b.id')
                ->where('b.gudang_id', $warehouseId)
                ->where('b.is_aktif', true)
                ->whereNull('b.deleted_at');

            $this->applyItemFilters($query, $filters);

            $totals = (clone $query)
                ->selectRaw('COALESCE(SUM(COALESCE(m.opening_qty, 0)), 0) AS saldo_awal')
                ->selectRaw('COALESCE(SUM(COALESCE(m.store_qty, 0)), 0) AS masuk_store')
                ->selectRaw('COALESCE(SUM(COALESCE(m.purchase_qty, 0)), 0) AS masuk_beli_umum')
                ->selectRaw('COALESCE(SUM(COALESCE(m.out_qty, 0)), 0) AS keluar_bersih')
                ->selectRaw('COALESCE(SUM(COALESCE(m.opname_qty, 0)), 0) AS selisih_opname')
                ->selectRaw('COALESCE(SUM(COALESCE(m.opening_qty, 0) + COALESCE(m.store_qty, 0) + COALESCE(m.purchase_qty, 0) - COALESCE(m.out_qty, 0) + COALESCE(m.opname_qty, 0)), 0) AS saldo_akhir')
                ->selectRaw('COALESCE(SUM((COALESCE(m.opening_qty, 0) + COALESCE(m.store_qty, 0) + COALESCE(m.purchase_qty, 0) - COALESCE(m.out_qty, 0) + COALESCE(m.opname_qty, 0)) * COALESCE(b.harga_satuan, 0)), 0) AS nilai_stok')
                ->first();

            $itemsQuery = (clone $query)
                ->select([
                    'b.id',
                    'b.kode',
                    'b.nama',
                    'b.harga_satuan',
                    'c.nama as kategori',
                    'u.nama as satuan',
                    'm.opening_qty',
                    'm.store_qty',
                    'm.purchase_qty',
                    'm.out_qty',
                    'm.opname_qty',
                ])
                ->orderBy('b.kode');
            $itemsPage = $paginate
                ? $itemsQuery->paginate(self::PAGE_SIZE)->withQueryString()
                : null;
            $rows = $itemsPage?->getCollection() ?? $itemsQuery->get();

            $items = $rows->map(static function (object $row): array {
                $opening = round((float) ($row->opening_qty ?? 0), 3);
                $store = round((float) ($row->store_qty ?? 0), 3);
                $purchase = round((float) ($row->purchase_qty ?? 0), 3);
                $out = round((float) ($row->out_qty ?? 0), 3);
                $opname = round((float) ($row->opname_qty ?? 0), 3);
                $hpp = (float) ($row->harga_satuan ?? 0);
                $closing = round($opening + $store + $purchase - $out + $opname, 3);

                return [
                    'kode' => $row->kode,
                    'nama' => $row->nama,
                    'satuan' => $row->satuan ?? '—',
                    'kategori' => $row->kategori ?? 'Umum',
                    'saldo_awal' => $opening,
                    'masuk_store' => $store,
                    'masuk_beli_umum' => $purchase,
                    'keluar_bersih' => $out,
                    'selisih_opname' => $opname,
                    'saldo_akhir' => $closing,
                    'hpp' => $hpp,
                    'nilai_stok' => round($closing * $hpp, 2),
                ];
            });
            if ($itemsPage !== null) {
                $itemsPage->setCollection($items);
                $items = $itemsPage;
            } else {
                $items = $items->all();
            }

            return [
                'items' => $items,
                'totals' => [
                    'saldo_awal' => (float) $totals->saldo_awal,
                    'masuk_store' => (float) $totals->masuk_store,
                    'masuk_beli_umum' => (float) $totals->masuk_beli_umum,
                    'keluar_bersih' => (float) $totals->keluar_bersih,
                    'selisih_opname' => (float) $totals->selisih_opname,
                    'saldo_akhir' => (float) $totals->saldo_akhir,
                    'nilai_stok' => (float) $totals->nilai_stok,
                ],
            ];
        });
    }

    private function movementAggregate(int $warehouseId, string $start, string $end): Builder
    {
        $zero = 'CAST(0 AS NUMERIC)';
        $sources = [];

        $sources[] = DB::table('stok_awals')
            ->where('gudang_id', $warehouseId)
            ->whereNull('deleted_at')
            ->where('tanggal_acuan', '<', $start)
            ->select('barang_id')
            ->selectRaw("SUM(qty) AS opening_qty, {$zero} AS store_qty, {$zero} AS purchase_qty, {$zero} AS out_qty, {$zero} AS opname_qty")
            ->groupBy('barang_id');

        $goodReceipt = 'CASE WHEN d.qty_diperiksa IS NOT NULL '.
            'THEN GREATEST(COALESCE(d.qty_diperiksa, 0) - COALESCE(d.qty_rusak_diperiksa, d.qty_rusak, 0), 0) '.
            'ELSE GREATEST(d.qty_diterima - COALESCE(d.qty_rusak, 0), 0) END';
        $sources[] = DB::table('penerimaan_unit_details as d')
            ->join('penerimaan_units as h', 'h.id', '=', 'd.penerimaan_unit_id')
            ->where('h.gudang_id', $warehouseId)
            ->whereIn('h.status_verifikasi', self::VERIFIED_RECEIPT_STATUSES)
            ->where('h.tanggal_terima', '<=', $end)
            ->select('d.barang_id')
            ->selectRaw(
                "SUM(CASE WHEN h.tanggal_terima < ? THEN {$goodReceipt} ELSE 0 END) AS opening_qty, ".
                "{$zero} AS store_qty, ".
                "SUM(CASE WHEN h.tanggal_terima >= ? THEN {$goodReceipt} ELSE 0 END) AS purchase_qty, ".
                "{$zero} AS out_qty, {$zero} AS opname_qty",
                [$start, $start],
            )
            ->groupBy('d.barang_id');

        $goodTransfer = 'GREATEST(COALESCE(h.qty_diperiksa, h.qty_diterima) - '.
            'COALESCE(h.qty_rusak_diperiksa, h.qty_rusak, 0), 0)';
        $sources[] = DB::table('penerimaan_pemindahan_units as h')
            ->where('h.gudang_id', $warehouseId)
            ->whereIn('h.status_verifikasi', self::VERIFIED_RECEIPT_STATUSES)
            ->whereRaw('COALESCE(h.diverifikasi_at::date, h.diterima_at::date) <= ?', [$end])
            ->select('h.barang_id')
            ->selectRaw(
                "SUM(CASE WHEN COALESCE(h.diverifikasi_at::date, h.diterima_at::date) < ? THEN {$goodTransfer} ELSE 0 END) AS opening_qty, ".
                "SUM(CASE WHEN COALESCE(h.diverifikasi_at::date, h.diterima_at::date) >= ? THEN {$goodTransfer} ELSE 0 END) AS store_qty, ".
                "{$zero} AS purchase_qty, {$zero} AS out_qty, {$zero} AS opname_qty",
                [$start, $start],
            )
            ->groupBy('h.barang_id');

        $netIssue = 'GREATEST(COALESCE(d.qty_keluar_rekonsiliasi, d.qty) - '.
            'COALESCE(d.qty_kembali_rekonsiliasi, d.qty_kembali, 0), 0)';
        $sources[] = DB::table('pengeluaran_unit_details as d')
            ->join('pengeluaran_units as h', 'h.id', '=', 'd.pengeluaran_unit_id')
            ->where('h.gudang_id', $warehouseId)
            ->whereIn('h.status_verifikasi', ['terverifikasi', 'verified', 'applied'])
            ->where('h.tanggal', '<=', $end)
            ->select('d.barang_id')
            ->selectRaw(
                "SUM(CASE WHEN h.tanggal < ? THEN -({$netIssue}) ELSE 0 END) AS opening_qty, ".
                "{$zero} AS store_qty, {$zero} AS purchase_qty, ".
                "SUM(CASE WHEN h.tanggal >= ? THEN {$netIssue} ELSE 0 END) AS out_qty, {$zero} AS opname_qty",
                [$start, $start],
            )
            ->groupBy('d.barang_id');

        $sources[] = DB::table('transfer_barangs as h')
            ->where('h.gudang_asal_id', $warehouseId)
            ->whereIn('h.status', ['dikirim', 'diterima'])
            ->whereRaw('COALESCE(h.dikirim_at::date, h.created_at::date) <= ?', [$end])
            ->select('h.barang_id')
            ->selectRaw(
                'SUM(CASE WHEN COALESCE(h.dikirim_at::date, h.created_at::date) < ? THEN -h.qty_dikirim ELSE 0 END) AS opening_qty, '.
                "{$zero} AS store_qty, {$zero} AS purchase_qty, ".
                'SUM(CASE WHEN COALESCE(h.dikirim_at::date, h.created_at::date) >= ? THEN h.qty_dikirim ELSE 0 END) AS out_qty, '.
                "{$zero} AS opname_qty",
                [$start, $start],
            )
            ->groupBy('h.barang_id');

        $opnameDelta = 'COALESCE(d.qty_fisik, d.qty_sistem) - d.qty_sistem';
        $opnameDate = 'COALESCE(d.qty_sistem_at::date, h.tanggal_efektif)';
        $sources[] = DB::table('stok_opname_details as d')
            ->join('stok_opnames as h', 'h.id', '=', 'd.stok_opname_id')
            ->where('h.gudang_id', $warehouseId)
            ->whereIn('h.status', ['diterapkan', 'applied'])
            ->whereRaw("{$opnameDate} <= ?", [$end])
            ->select('d.barang_id')
            ->selectRaw(
                "SUM(CASE WHEN {$opnameDate} < ? THEN {$opnameDelta} ELSE 0 END) AS opening_qty, ".
                "{$zero} AS store_qty, {$zero} AS purchase_qty, {$zero} AS out_qty, ".
                "SUM(CASE WHEN {$opnameDate} >= ? THEN {$opnameDelta} ELSE 0 END) AS opname_qty",
                [$start, $start],
            )
            ->groupBy('d.barang_id');

        $union = array_shift($sources);
        foreach ($sources as $source) {
            $union->unionAll($source);
        }

        return DB::query()
            ->fromSub($union, 'movement_sources')
            ->select('barang_id')
            ->selectRaw('SUM(opening_qty) AS opening_qty')
            ->selectRaw('SUM(store_qty) AS store_qty')
            ->selectRaw('SUM(purchase_qty) AS purchase_qty')
            ->selectRaw('SUM(out_qty) AS out_qty')
            ->selectRaw('SUM(opname_qty) AS opname_qty')
            ->groupBy('barang_id');
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
