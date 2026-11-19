<?php

declare(strict_types=1);

namespace App\Services\AdminGu\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OpnameDiscrepancyReportService
{
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
    public function report(int $warehouseId, string $period, array $filters): array
    {
        return $this->cache->remember($period, 'selisih', $warehouseId, $filters, function () use ($warehouseId, $period, $filters): array {
            $start = $period.'-01';
            $end = Carbon::parse($start)->endOfMonth()->toDateString();
            $opnameQuery = DB::table('stok_opname_details as d')
                ->join('stok_opnames as h', 'h.id', '=', 'd.stok_opname_id')
                ->join('barangs as b', 'b.id', '=', 'd.barang_id')
                ->where('h.gudang_id', $warehouseId)
                ->whereIn('h.status', ['diterapkan', 'applied'])
                ->whereDate('h.periode', $start)
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
                ->selectRaw('((d.qty_fisik - d.qty_sistem) * d.harga_satuan) AS selisih_nilai')
                ->orderBy('d.kode_barang')
                ->get()
                ->map(static fn (object $row): array => [
                    'kode' => $row->kode,
                    'nama' => $row->nama,
                    'satuan' => $row->satuan ?? '—',
                    'saldo_sistem' => (float) $row->qty_sistem,
                    'stok_fisik' => (float) $row->qty_fisik,
                    'selisih_qty' => (float) $row->selisih_qty,
                    'hpp' => (float) $row->harga_satuan,
                    'selisih_nilai' => (float) $row->selisih_nilai,
                    'catatan' => $row->catatan,
                ])->all();

            $damaged = [
                ...$this->damagedSupplierItems($warehouseId, $start, $end, $filters),
                ...$this->damagedTransferItems($warehouseId, $start, $end, $filters),
            ];

            return [
                'opname_items' => $opnameRows,
                'damaged_items' => $damaged,
                'totals' => [
                    'difference_qty' => array_sum(array_column($opnameRows, 'selisih_qty')),
                    'difference_value' => array_sum(array_column($opnameRows, 'selisih_nilai')),
                    'damaged_qty' => array_sum(array_column($damaged, 'damaged_qty')),
                    'damaged_value' => array_sum(array_column($damaged, 'impact_value')),
                ],
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function damagedSupplierItems(int $warehouseId, string $start, string $end, array $filters): array
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
            ->orderByDesc('h.diverifikasi_at')
            ->get()
            ->map(static fn (object $row): array => [
                'document_number' => $row->document_number,
                'source_name' => $row->source_name,
                'kode' => $row->kode ?? '—',
                'nama' => $row->nama,
                'satuan' => $row->satuan ?? '—',
                'damaged_qty' => (float) $row->damaged_qty,
                'hpp' => (float) $row->hpp,
                'impact_value' => (float) $row->impact_value,
                'handling_status' => $row->handling_status === 'perlu_tindak_lanjut'
                    ? 'Perlu tindak lanjut'
                    : 'Penanganan belum tercatat',
                'note' => $row->note,
                'source_type' => 'Penerimaan Supplier',
            ])->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function damagedTransferItems(int $warehouseId, string $start, string $end, array $filters): array
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
                't.nomor_sj as document_number',
                'origin.nama as source_name',
                'b.kode',
                'b.nama',
                'h.status_verifikasi as handling_status',
                'h.catatan_verifikasi',
                'h.catatan_discrepancy',
            ])
            ->selectRaw("{$damaged} AS damaged_qty")
            ->selectRaw('COALESCE(b.harga_satuan, 0) AS hpp')
            ->selectRaw("({$damaged}) * COALESCE(b.harga_satuan, 0) AS impact_value")
            ->orderByDesc('h.diverifikasi_at')
            ->get()
            ->map(static fn (object $row): array => [
                'document_number' => $row->document_number,
                'source_name' => $row->source_name ?: 'Gudang Store',
                'kode' => $row->kode ?? '—',
                'nama' => $row->nama ?? 'Barang tidak tersedia',
                'satuan' => '—',
                'damaged_qty' => (float) $row->damaged_qty,
                'hpp' => (float) $row->hpp,
                'impact_value' => (float) $row->impact_value,
                'handling_status' => $row->handling_status === 'perlu_tindak_lanjut'
                    ? 'Perlu tindak lanjut'
                    : 'Penanganan belum tercatat',
                'note' => $row->catatan_verifikasi ?: $row->catatan_discrepancy,
                'source_type' => 'Transfer Store',
            ])->all();
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
