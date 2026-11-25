<?php

namespace App\Services\Store;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\PenerimaanBarangStore;
use App\Models\PenerimaanBarangStoreDetail;
use App\Models\TransferBarang;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class LaporanStoreService
{
    public function __construct(private readonly StokService $stokService) {}

    /**
     * @return array{0: Collection<int, array<int, mixed>>, 1: array<int, string>, 2: string}
     */
    public function exportData(
        Gudang $store,
        string $type,
        string|int|null $categoryId,
        string $start,
        string $end,
    ): array {
        if ($type === 'stok_akhir') {
            $query = Barang::query()
                ->with(['kategori:id,nama', 'satuan:id,nama'])
                ->where('gudang_id', $store->id)
                ->where('is_aktif', true)
                ->select(['id', 'kode', 'nama', 'kategori_id', 'satuan_id', 'minimum_stok']);

            if ($categoryId) {
                $query->where('kategori_id', $categoryId);
            }

            $items = $query->orderBy('nama')->get();
            $totals = $this->stokService->totals($items->pluck('id'), $store->id);
            $rows = $items->map(function (Barang $item) use ($totals): array {
                $awal = (int) ($totals['awal'][$item->id] ?? 0);
                $masuk = (int) ($totals['masuk'][$item->id] ?? 0);
                $keluar = (int) ($totals['keluar'][$item->id] ?? 0);

                return [
                    $item->kode,
                    $item->nama,
                    $item->kategori?->nama ?? '-',
                    $awal + $masuk,
                    $keluar,
                    max(0, $awal + $masuk - $keluar),
                    $item->minimum_stok,
                    $item->satuan?->nama ?? '-',
                ];
            });

            return [
                $rows,
                ['Kode', 'Nama Barang', 'Kategori', 'Total Masuk', 'Total Keluar', 'Stok Akhir', 'Minimum', 'Satuan'],
                'Laporan Stok Akhir Gudang Store',
            ];
        }

        if ($type === 'mutasi') {
            $detailRows = PenerimaanBarangStoreDetail::query()
                ->with([
                    'barang:id,nama',
                    'penerimaan:id,diterima_oleh,tanggal_diterima,nomor_penerimaan,sumber_supplier',
                    'penerimaan.penerima:id,name',
                ])
                ->whereHas('penerimaan', fn ($query) => $query
                    ->where('gudang_id', $store->id)
                    ->whereBetween('tanggal_diterima', [$start.' 00:00:00', $end.' 23:59:59']))
                ->get(['id', 'penerimaan_barang_store_id', 'barang_id', 'qty_diterima', 'pemakai'])
                ->map(fn (PenerimaanBarangStoreDetail $detail): array => [
                    $detail->penerimaan->tanggal_diterima,
                    $detail->penerimaan->nomor_penerimaan,
                    'Barang Masuk',
                    $detail->barang?->nama ?? '-',
                    $detail->qty_diterima,
                    0,
                    $detail->pemakai ?: '—',
                    $detail->penerimaan->sumber_supplier ?? 'Supplier',
                    $detail->penerimaan->penerima?->name ?? '-',
                ]);

            $legacyDetailRows = PenerimaanBarangStore::query()
                ->with(['barang:id,nama', 'penerima:id,name'])
                ->where('gudang_id', $store->id)
                ->whereDoesntHave('details')
                ->whereBetween('tanggal_diterima', [$start.' 00:00:00', $end.' 23:59:59'])
                ->get(['id', 'barang_id', 'diterima_oleh', 'tanggal_diterima', 'nomor_penerimaan', 'qty_diterima', 'sumber_supplier'])
                ->map(fn (PenerimaanBarangStore $receipt): array => [
                    $receipt->tanggal_diterima,
                    $receipt->nomor_penerimaan,
                    'Barang Masuk',
                    $receipt->barang?->nama ?? '-',
                    $receipt->qty_diterima,
                    0,
                    '—',
                    $receipt->sumber_supplier ?? 'Supplier',
                    $receipt->penerima?->name ?? '-',
                ]);

            $transferRows = TransferBarang::query()
                ->with(['barang:id,nama', 'gudangTujuan:id,nama', 'pengirim:id,name'])
                ->where('gudang_asal_id', $store->id)
                ->whereIn('status', ['dikirim', 'diterima'])
                ->whereBetween('dikirim_at', [$start.' 00:00:00', $end.' 23:59:59'])
                ->get(['id', 'barang_id', 'gudang_tujuan_id', 'dikirim_oleh', 'nomor_sj', 'qty_dikirim', 'dikirim_at', 'status'])
                ->map(fn (TransferBarang $transfer): array => [
                    $transfer->dikirim_at,
                    $transfer->nomor_sj,
                    'Pemindahan Keluar',
                    $transfer->barang?->nama ?? '-',
                    0,
                    $transfer->qty_dikirim,
                    '—',
                    $transfer->gudangTujuan?->nama ?? 'Unit Bisnis',
                    $transfer->pengirim?->name ?? '-',
                ]);

            return [
                $detailRows->concat($legacyDetailRows)->concat($transferRows)
                    ->sortByDesc(fn (array $row) => $row[0])
                    ->values(),
                ['Tanggal', 'No. Dokumen', 'Jenis', 'Barang', 'Qty Masuk', 'Qty Keluar', 'User / Pemakai', 'Sumber/Tujuan', 'Petugas'],
                'Laporan Mutasi Gudang Store',
            ];
        }

        $rows = TransferBarang::query()
            ->with(['barang:id,nama', 'gudangTujuan:id,nama', 'pengirim:id,name'])
            ->where('gudang_asal_id', $store->id)
            ->whereBetween('created_at', [$start.' 00:00:00', $end.' 23:59:59'])
            ->orderByDesc('created_at')
            ->get(['id', 'barang_id', 'gudang_tujuan_id', 'dikirim_oleh', 'nomor_sj', 'qty_dikirim', 'dikirim_at', 'created_at', 'status'])
            ->map(fn (TransferBarang $transfer): array => [
                $transfer->nomor_sj,
                $transfer->dikirim_at,
                $transfer->gudangTujuan?->nama ?? '-',
                $transfer->barang?->nama ?? '-',
                $transfer->qty_dikirim,
                $transfer->status,
                $transfer->pengirim?->name ?? '-',
            ]);

        return [
            $rows,
            ['No. Surat Jalan', 'Waktu Kirim', 'Tujuan', 'Barang', 'Qty Dikirim', 'Status', 'Petugas'],
            'Laporan Distribusi Gudang Store',
        ];
    }

    /**
     * @return array{0: Builder, 1: array<int, string>}
     */
    public function exportQueryData(
        Gudang $store,
        string $type,
        string|int|null $categoryId,
        string $start,
        string $end,
    ): array {
        if ($type === 'stok_akhir') {
            $initial = DB::table('stok_awals')
                ->where('gudang_id', $store->id)
                ->whereNull('deleted_at')
                ->select('barang_id')
                ->selectRaw("qty AS quantity, 'awal' AS kind");

            $receiptDetails = DB::table('penerimaan_barang_store_details AS detail')
                ->join('penerimaan_barang_stores AS header', 'header.id', '=', 'detail.penerimaan_barang_store_id')
                ->where('header.gudang_id', $store->id)
                ->select('detail.barang_id')
                ->selectRaw(
                    'CASE WHEN detail.qty_diterima > COALESCE(detail.qty_rusak, 0) '.
                    "THEN detail.qty_diterima - COALESCE(detail.qty_rusak, 0) ELSE 0 END AS quantity, 'masuk' AS kind"
                );

            $legacyReceipts = DB::table('penerimaan_barang_stores AS header')
                ->where('header.gudang_id', $store->id)
                ->whereNotNull('header.barang_id')
                ->whereNotExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('penerimaan_barang_store_details AS detail')
                        ->whereColumn('detail.penerimaan_barang_store_id', 'header.id');
                })
                ->select('header.barang_id')
                ->selectRaw("header.qty_diterima AS quantity, 'masuk' AS kind");

            $dispatchedTransfers = DB::table('transfer_barangs')
                ->where('gudang_asal_id', $store->id)
                ->whereIn('status', ['dikirim', 'diterima'])
                ->select('barang_id')
                ->selectRaw("qty_dikirim AS quantity, 'keluar' AS kind");

            $movements = $initial
                ->unionAll($receiptDetails)
                ->unionAll($legacyReceipts)
                ->unionAll($dispatchedTransfers);

            $balances = DB::query()
                ->fromSub($movements, 'movement')
                ->select('barang_id')
                ->selectRaw("SUM(CASE WHEN kind = 'awal' THEN quantity ELSE 0 END) AS awal")
                ->selectRaw("SUM(CASE WHEN kind = 'masuk' THEN quantity ELSE 0 END) AS masuk")
                ->selectRaw("SUM(CASE WHEN kind = 'keluar' THEN quantity ELSE 0 END) AS keluar")
                ->groupBy('barang_id');

            $query = DB::table('barangs')
                ->leftJoin('kategori_barangs AS kategoris', function (JoinClause $join): void {
                    $join->on('kategoris.id', '=', 'barangs.kategori_id')
                        ->whereNull('kategoris.deleted_at');
                })
                ->leftJoin('satuans', function (JoinClause $join): void {
                    $join->on('satuans.id', '=', 'barangs.satuan_id')
                        ->whereNull('satuans.deleted_at');
                })
                ->leftJoinSub($balances, 'balance', 'balance.barang_id', '=', 'barangs.id')
                ->where('barangs.gudang_id', $store->id)
                ->where('barangs.is_aktif', true)
                ->whereNull('barangs.deleted_at')
                ->when($categoryId, fn (Builder $query) => $query->where('barangs.kategori_id', $categoryId))
                ->select([
                    'barangs.kode',
                    'barangs.nama',
                ])
                ->selectRaw("COALESCE(kategoris.nama, '-') AS kategori")
                ->selectRaw('COALESCE(balance.awal, 0) + COALESCE(balance.masuk, 0) AS total_masuk')
                ->selectRaw('COALESCE(balance.keluar, 0) AS total_keluar')
                ->selectRaw(
                    'GREATEST(0, COALESCE(balance.awal, 0) + COALESCE(balance.masuk, 0) - COALESCE(balance.keluar, 0)) AS stok_akhir'
                )
                ->addSelect('barangs.minimum_stok AS minimum')
                ->selectRaw("COALESCE(satuans.nama, '-') AS satuan")
                ->orderBy('barangs.nama');

            return [
                $query,
                ['Kode', 'Nama Barang', 'Kategori', 'Total Masuk', 'Total Keluar', 'Stok Akhir', 'Minimum', 'Satuan'],
            ];
        }

        if ($type === 'mutasi') {
            $receiptDetails = DB::table('penerimaan_barang_store_details AS detail')
                ->join('penerimaan_barang_stores AS header', 'header.id', '=', 'detail.penerimaan_barang_store_id')
                ->leftJoin('barangs', function (JoinClause $join): void {
                    $join->on('barangs.id', '=', 'detail.barang_id')
                        ->whereNull('barangs.deleted_at');
                })
                ->leftJoin('users AS officer', function (JoinClause $join): void {
                    $join->on('officer.id', '=', 'header.diterima_oleh')
                        ->whereNull('officer.deleted_at');
                })
                ->where('header.gudang_id', $store->id)
                ->whereBetween('header.tanggal_diterima', [$start.' 00:00:00', $end.' 23:59:59'])
                ->selectRaw(
                    'header.tanggal_diterima AS sort_date, header.tanggal_diterima AS tanggal, '.
                    "header.nomor_penerimaan AS dokumen, 'Barang Masuk' AS jenis, ".
                    "COALESCE(barangs.nama, '-') AS barang, detail.qty_diterima AS qty_masuk, ".
                    "0 AS qty_keluar, COALESCE(NULLIF(detail.pemakai, ''), '—') AS pemakai, ".
                    "COALESCE(header.sumber_supplier, 'Supplier') AS sumber_tujuan, ".
                    "COALESCE(officer.name, '-') AS petugas"
                );

            $legacyReceipts = DB::table('penerimaan_barang_stores AS header')
                ->leftJoin('barangs', function (JoinClause $join): void {
                    $join->on('barangs.id', '=', 'header.barang_id')
                        ->whereNull('barangs.deleted_at');
                })
                ->leftJoin('users AS officer', function (JoinClause $join): void {
                    $join->on('officer.id', '=', 'header.diterima_oleh')
                        ->whereNull('officer.deleted_at');
                })
                ->where('header.gudang_id', $store->id)
                ->whereBetween('header.tanggal_diterima', [$start.' 00:00:00', $end.' 23:59:59'])
                ->whereNotExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('penerimaan_barang_store_details AS detail')
                        ->whereColumn('detail.penerimaan_barang_store_id', 'header.id');
                })
                ->selectRaw(
                    'header.tanggal_diterima AS sort_date, header.tanggal_diterima AS tanggal, '.
                    "header.nomor_penerimaan AS dokumen, 'Barang Masuk' AS jenis, ".
                    "COALESCE(barangs.nama, '-') AS barang, header.qty_diterima AS qty_masuk, ".
                    "0 AS qty_keluar, '—' AS pemakai, ".
                    "COALESCE(header.sumber_supplier, 'Supplier') AS sumber_tujuan, ".
                    "COALESCE(officer.name, '-') AS petugas"
                );

            $transfers = DB::table('transfer_barangs AS transfer')
                ->leftJoin('barangs', function (JoinClause $join): void {
                    $join->on('barangs.id', '=', 'transfer.barang_id')
                        ->whereNull('barangs.deleted_at');
                })
                ->leftJoin('gudangs AS destination', 'destination.id', '=', 'transfer.gudang_tujuan_id')
                ->leftJoin('users AS officer', function (JoinClause $join): void {
                    $join->on('officer.id', '=', 'transfer.dikirim_oleh')
                        ->whereNull('officer.deleted_at');
                })
                ->where('transfer.gudang_asal_id', $store->id)
                ->whereIn('transfer.status', ['dikirim', 'diterima'])
                ->whereBetween('transfer.dikirim_at', [$start.' 00:00:00', $end.' 23:59:59'])
                ->selectRaw(
                    'transfer.dikirim_at AS sort_date, transfer.dikirim_at AS tanggal, '.
                    "transfer.nomor_sj AS dokumen, 'Pemindahan Keluar' AS jenis, ".
                    "COALESCE(barangs.nama, '-') AS barang, 0 AS qty_masuk, ".
                    "transfer.qty_dikirim AS qty_keluar, '—' AS pemakai, ".
                    "COALESCE(destination.nama, 'Unit Bisnis') AS sumber_tujuan, ".
                    "COALESCE(officer.name, '-') AS petugas"
                );

            $rows = $receiptDetails->unionAll($legacyReceipts)->unionAll($transfers);

            return [
                DB::query()
                    ->fromSub($rows, 'report')
                    ->select([
                        'tanggal',
                        'dokumen',
                        'jenis',
                        'barang',
                        'qty_masuk',
                        'qty_keluar',
                        'pemakai',
                        'sumber_tujuan',
                        'petugas',
                    ])
                    ->orderByDesc('sort_date'),
                ['Tanggal', 'No. Dokumen', 'Jenis', 'Barang', 'Qty Masuk', 'Qty Keluar', 'User / Pemakai', 'Sumber/Tujuan', 'Petugas'],
            ];
        }

        $query = DB::table('transfer_barangs AS transfer')
            ->leftJoin('barangs', function (JoinClause $join): void {
                $join->on('barangs.id', '=', 'transfer.barang_id')
                    ->whereNull('barangs.deleted_at');
            })
            ->leftJoin('gudangs AS destination', 'destination.id', '=', 'transfer.gudang_tujuan_id')
            ->leftJoin('users AS officer', function (JoinClause $join): void {
                $join->on('officer.id', '=', 'transfer.dikirim_oleh')
                    ->whereNull('officer.deleted_at');
            })
            ->where('transfer.gudang_asal_id', $store->id)
            ->whereBetween('transfer.created_at', [$start.' 00:00:00', $end.' 23:59:59'])
            ->select([
                'transfer.nomor_sj',
                'transfer.dikirim_at',
                'transfer.qty_dikirim',
                'transfer.status',
            ])
            ->selectRaw("COALESCE(destination.nama, '-') AS tujuan")
            ->selectRaw("COALESCE(barangs.nama, '-') AS barang")
            ->selectRaw("COALESCE(officer.name, '-') AS petugas")
            ->orderByDesc('transfer.created_at');

        return [
            $query,
            ['No. Surat Jalan', 'Waktu Kirim', 'Tujuan', 'Barang', 'Qty Dikirim', 'Status', 'Petugas'],
        ];
    }
}
