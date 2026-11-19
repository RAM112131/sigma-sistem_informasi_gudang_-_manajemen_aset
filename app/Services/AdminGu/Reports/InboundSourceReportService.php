<?php

declare(strict_types=1);

namespace App\Services\AdminGu\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InboundSourceReportService
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
     * @param  array{category_id?: int|string|null, search?: string|null, source_type?: string|null, page?: int}  $filters
     * @return array<string, mixed>
     */
    public function report(int $warehouseId, string $period, array $filters, bool $paginate = true): array
    {
        return $this->cache->remember($period, 'pemasukan', $warehouseId, [...$filters, 'paginated' => $paginate], function () use ($warehouseId, $period, $filters, $paginate): array {
            $start = $period.'-01';
            $end = Carbon::parse($start)->endOfMonth()->toDateString();
            $direct = $this->directReceipts($warehouseId, $start, $end, $filters);
            $transfers = $this->transferReceipts($warehouseId, $start, $end, $filters);
            $storeSummary = $this->summarize($transfers);
            $purchaseSummary = $this->summarize($direct);

            $documentsQuery = DB::query()
                ->fromSub($direct->unionAll($transfers), 'documents')
                ->select([
                    'documents.document_number',
                    'documents.verified_at',
                    'documents.source_name',
                    'documents.reference_number',
                    'documents.sku_count',
                    'documents.physical_condition',
                    'documents.total_value',
                    'documents.follow_up',
                    'documents.source_type',
                ]);
            if (! empty($filters['source_type'])) {
                $documentsQuery->where('documents.source_type', $filters['source_type']);
            }
            $documentsQuery->orderByDesc('documents.verified_at')
                ->orderByDesc('documents.document_number');

            if ($paginate) {
                $documents = $documentsQuery
                    ->paginate(self::PAGE_SIZE, ['*'], 'page', (int) ($filters['page'] ?? 1))
                    ->withQueryString();
                $documents->setCollection($documents->getCollection()->map(
                    fn (object $document): array => $this->formatDocument($document),
                ));
            } else {
                $documents = $documentsQuery->get()
                    ->map(fn (object $document): array => $this->formatDocument($document))
                    ->all();
            }

            return [
                'documents' => $documents,
                'kpi' => [
                    'store_value' => $storeSummary['total_value'],
                    'store_documents' => $storeSummary['document_count'],
                    'purchase_value' => $purchaseSummary['total_value'],
                    'purchase_documents' => $purchaseSummary['document_count'],
                    'total_value' => $storeSummary['total_value'] + $purchaseSummary['total_value'],
                ],
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function directReceipts(int $warehouseId, string $start, string $end, array $filters): Builder
    {
        $goodQuantity = 'CASE WHEN d.qty_diperiksa IS NOT NULL '.
            'THEN GREATEST(d.qty_diperiksa - COALESCE(d.qty_rusak_diperiksa, d.qty_rusak, 0), 0) '.
            'ELSE GREATEST(d.qty_diterima - COALESCE(d.qty_rusak, 0), 0) END';
        $damaged = 'COALESCE(d.qty_rusak_diperiksa, d.qty_rusak, 0)';
        $query = DB::table('penerimaan_units as h')
            ->join('penerimaan_unit_details as d', 'd.penerimaan_unit_id', '=', 'h.id')
            ->leftJoin('barangs as b', 'b.id', '=', 'd.barang_id')
            ->where('h.gudang_id', $warehouseId)
            ->whereIn('h.status_verifikasi', self::VERIFIED_STATUSES)
            ->whereBetween('h.diverifikasi_at', [$start.' 00:00:00', $end.' 23:59:59']);
        $this->applyDetailFilters($query, $filters, 'd');

        return $query
            ->select([
                'h.nomor_rcv as document_number',
                'h.diverifikasi_at as verified_at',
                'h.nama_supplier as source_name',
            ])
            ->selectRaw("COALESCE(h.nomor_surat_jalan, h.po_referensi, '—') AS reference_number")
            ->selectRaw('COUNT(DISTINCT d.barang_id) AS sku_count')
            ->selectRaw("CASE WHEN SUM({$damaged}) > 0 THEN 'Rusak' WHEN SUM(d.qty_kurang) > 0 THEN 'Kurang' WHEN SUM(d.qty_lebih) > 0 THEN 'Lebih' ELSE 'Sesuai' END AS physical_condition")
            ->selectRaw("COALESCE(SUM(COALESCE(d.subtotal_nilai, ({$goodQuantity}) * d.harga_beli)), 0) AS total_value")
            ->selectRaw("COALESCE(NULLIF(h.catatan_verifikasi, ''), h.catatan) AS follow_up")
            ->selectRaw("'direct_purchase' AS source_type")
            ->groupBy('h.id', 'h.nomor_rcv', 'h.diverifikasi_at', 'h.nama_supplier', 'h.nomor_surat_jalan', 'h.po_referensi', 'h.catatan_verifikasi', 'h.catatan');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function transferReceipts(int $warehouseId, string $start, string $end, array $filters): Builder
    {
        $goodQuantity = 'GREATEST(COALESCE(h.qty_diperiksa, h.qty_diterima) - '.
            'COALESCE(h.qty_rusak_diperiksa, h.qty_rusak, 0), 0)';
        $damaged = 'COALESCE(h.qty_rusak_diperiksa, h.qty_rusak, 0)';
        $query = DB::table('penerimaan_pemindahan_units as h')
            ->join('transfer_barangs as t', 't.id', '=', 'h.transfer_barang_id')
            ->leftJoin('barangs as b', 'b.id', '=', 'h.barang_id')
            ->leftJoin('gudangs as origin', 'origin.id', '=', 't.gudang_asal_id')
            ->where('h.gudang_id', $warehouseId)
            ->whereIn('h.status_verifikasi', self::VERIFIED_STATUSES)
            ->whereBetween('h.diverifikasi_at', [$start.' 00:00:00', $end.' 23:59:59']);
        $this->applyDetailFilters($query, $filters, null);

        return $query
            ->select([
                't.nomor_sj as document_number',
                'h.diverifikasi_at as verified_at',
                'origin.nama as source_name',
            ])
            ->selectRaw("COALESCE(t.nomor_sj, '—') AS reference_number")
            ->selectRaw('1 AS sku_count')
            ->selectRaw("CASE WHEN {$damaged} > 0 THEN 'Rusak' WHEN COALESCE(h.qty_diperiksa, h.qty_diterima) < h.qty_dikirim THEN 'Kurang' WHEN COALESCE(h.qty_diperiksa, h.qty_diterima) > h.qty_dikirim THEN 'Lebih' ELSE 'Sesuai' END AS physical_condition")
            ->selectRaw("({$goodQuantity}) * COALESCE(b.harga_satuan, 0) AS total_value")
            ->selectRaw("COALESCE(NULLIF(h.catatan_verifikasi, ''), h.catatan_discrepancy) AS follow_up")
            ->selectRaw("'store_transfer' AS source_type");
    }

    /**
     * @return array{document_count: int, total_value: float}
     */
    private function summarize(Builder $receipts): array
    {
        $summary = DB::query()
            ->fromSub(clone $receipts, 'receipt_documents')
            ->selectRaw('COUNT(*) AS document_count')
            ->selectRaw('COALESCE(SUM(total_value), 0) AS total_value')
            ->first();

        return [
            'document_count' => (int) ($summary->document_count ?? 0),
            'total_value' => (float) ($summary->total_value ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDocument(object $document): array
    {
        return [
            'document_number' => $document->document_number,
            'verified_at' => $document->verified_at,
            'source_name' => $document->source_name ?: ($document->source_type === 'store_transfer' ? 'Gudang Store' : '—'),
            'reference_number' => $document->reference_number,
            'sku_count' => (int) $document->sku_count,
            'physical_condition' => $document->physical_condition,
            'total_value' => (float) $document->total_value,
            'follow_up' => $document->follow_up,
            'source_type' => $document->source_type,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyDetailFilters(Builder $query, array $filters, ?string $detailAlias): void
    {
        if (! empty($filters['category_id'])) {
            $query->where('b.kategori_id', (int) $filters['category_id']);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(static function (Builder $matching) use ($operator, $search, $detailAlias): void {
                $matching->where('b.kode', $operator, '%'.$search.'%')
                    ->orWhere('b.nama', $operator, '%'.$search.'%');
                if ($detailAlias !== null) {
                    $matching->orWhere("{$detailAlias}.kode_barang", $operator, '%'.$search.'%')
                        ->orWhere("{$detailAlias}.nama_barang", $operator, '%'.$search.'%');
                }
            });
        }
    }
}
