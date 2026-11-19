<?php

declare(strict_types=1);

namespace App\Services\Laporan;

use App\Models\Barang;
use App\Models\Gudang;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LaporanQueryService
{
    public function jumlahHariTersediaDalamPeriode(string $periode): int
    {
        $awalBulan = Carbon::parse($periode.'-01')->startOfMonth();
        $bulanIni = now()->startOfMonth();

        if ($awalBulan->greaterThan($bulanIni)) {
            return 0;
        }

        return $awalBulan->isSameMonth($bulanIni) ? now()->day : $awalBulan->daysInMonth;
    }

    /**
     * Get each active item's balance at the end of a calendar day.
     *
     * @return Collection<int, object{barang: Barang, saldo: int, di_bawah_minimum: bool}>
     */
    public function stokPerTanggal(
        Gudang $gudang,
        string $periode,
        int $tanggal,
        bool $hanyaPerluRestock = false,
    ): Collection {
        $awalBulan = Carbon::parse($periode.'-01')->startOfMonth();
        if ($tanggal < 1 || $tanggal > $awalBulan->daysInMonth) {
            throw new InvalidArgumentException('Tanggal laporan tidak valid untuk periode yang dipilih.');
        }

        $tanggalCutoff = $awalBulan->copy()->day($tanggal)->endOfDay();
        $saldoPerBarang = $this->saldoPerBarang($gudang, $tanggalCutoff);

        return Barang::query()
            ->with(['kategori:id,nama', 'satuan:id,nama'])
            ->where('gudang_id', $gudang->id)
            ->where('is_aktif', true)
            ->select(['id', 'kode', 'nama', 'kategori_id', 'satuan_id', 'minimum_stok'])
            ->orderBy('nama')
            ->get()
            ->map(function (Barang $barang) use ($saldoPerBarang): object {
                $saldo = (int) ($saldoPerBarang[$barang->id] ?? 0);
                $diBawahMinimum = $saldo <= (int) $barang->minimum_stok;

                return (object) [
                    'barang' => $barang,
                    'saldo' => $saldo,
                    'di_bawah_minimum' => $diBawahMinimum,
                ];
            })
            ->when(
                $hanyaPerluRestock,
                fn (Collection $rows): Collection => $rows->filter(
                    fn (object $row): bool => $row->di_bawah_minimum
                )->values(),
            );
    }

    /**
     * Return a bounded set of stock rows and the exact number of matching rows.
     *
     * @return array{baris: array<int, object>, jumlah_data: int}
     */
    public function stokPerTanggalPreview(
        Gudang $gudang,
        string $periode,
        int $tanggal,
        bool $hanyaPerluRestock,
        int $limit,
    ): array {
        if ($limit < 1) {
            throw new InvalidArgumentException('Batas preview harus lebih besar dari nol.');
        }

        $awalBulan = Carbon::parse($periode.'-01')->startOfMonth();
        if ($tanggal < 1 || $tanggal > $awalBulan->daysInMonth) {
            throw new InvalidArgumentException('Tanggal laporan tidak valid untuk periode yang dipilih.');
        }

        $tanggalCutoff = $awalBulan->copy()->day($tanggal)->endOfDay();
        $saldoPerBarang = $this->saldoPerBarang($gudang, $tanggalCutoff);
        $query = Barang::query()
            ->with(['kategori:id,nama', 'satuan:id,nama'])
            ->where('gudang_id', $gudang->id)
            ->where('is_aktif', true)
            ->select(['id', 'kode', 'nama', 'kategori_id', 'satuan_id', 'minimum_stok'])
            ->orderBy('nama')
            ->orderBy('id');

        if (! $hanyaPerluRestock) {
            $jumlahData = (clone $query)->count();
            $baris = $query->limit($limit)->get()
                ->map(fn (Barang $barang): object => $this->stokRow($barang, $saldoPerBarang[$barang->id] ?? 0))
                ->all();

            return ['baris' => $baris, 'jumlah_data' => $jumlahData];
        }

        $baris = [];
        $jumlahData = 0;

        foreach ($query->lazy(200) as $barang) {
            $row = $this->stokRow($barang, $saldoPerBarang[$barang->id] ?? 0);
            if (! $row->di_bawah_minimum) {
                continue;
            }

            $jumlahData++;
            if (count($baris) < $limit) {
                $baris[] = $row;
            }
        }

        return ['baris' => $baris, 'jumlah_data' => $jumlahData];
    }

    private function stokRow(Barang $barang, int|string $saldo): object
    {
        $saldo = (int) $saldo;

        return (object) [
            'barang' => $barang,
            'saldo' => $saldo,
            'di_bawah_minimum' => $saldo <= (int) $barang->minimum_stok,
        ];
    }

    /**
     * Build a query-backed stock sheet so Excel can fetch rows in bounded chunks.
     */
    public function stokPerTanggalQuery(
        Gudang $gudang,
        string $periode,
        int $tanggal,
        bool $hanyaPerluRestock = false,
    ): EloquentBuilder {
        $awalBulan = Carbon::parse($periode.'-01')->startOfMonth();
        if ($tanggal < 1 || $tanggal > $awalBulan->daysInMonth) {
            throw new InvalidArgumentException('Tanggal laporan tidak valid untuk periode yang dipilih.');
        }

        $tanggalCutoff = $awalBulan->copy()->day($tanggal)->endOfDay();
        $saldoPerBarang = DB::query()
            ->fromSub($this->pergerakanStok($gudang, $tanggalCutoff), 'pergerakan')
            ->select('barang_id')
            ->selectRaw('SUM(delta) AS saldo')
            ->groupBy('barang_id');

        $query = Barang::query()
            ->leftJoinSub($saldoPerBarang, 'saldo_barang', 'saldo_barang.barang_id', '=', 'barangs.id')
            ->with(['kategori:id,nama', 'satuan:id,nama'])
            ->where('barangs.gudang_id', $gudang->id)
            ->where('barangs.is_aktif', true)
            ->select([
                'barangs.id',
                'barangs.kode',
                'barangs.nama',
                'barangs.kategori_id',
                'barangs.satuan_id',
                'barangs.minimum_stok',
            ])
            ->selectRaw('COALESCE(saldo_barang.saldo, 0) AS saldo')
            ->selectRaw(
                'CASE WHEN COALESCE(saldo_barang.saldo, 0) <= barangs.minimum_stok THEN 1 ELSE 0 END AS di_bawah_minimum'
            )
            ->orderBy('barangs.nama')
            ->orderBy('barangs.id');

        if ($hanyaPerluRestock) {
            $query->whereRaw('COALESCE(saldo_barang.saldo, 0) <= barangs.minimum_stok');
        }

        return $query;
    }

    /**
     * @return array<int|string, int|string>
     */
    private function saldoPerBarang(Gudang $gudang, Carbon $tanggalCutoff): array
    {
        $cacheKey = sprintf(
            'laporan:stok:v2:%s:%s:%02d:%s',
            $gudang->id,
            $tanggalCutoff->format('Y-m'),
            $tanggalCutoff->day,
            'semua',
        );

        $hitungSaldo = function () use ($gudang, $tanggalCutoff): array {
            return DB::query()
                ->fromSub($this->pergerakanStok($gudang, $tanggalCutoff), 'pergerakan')
                ->select('barang_id')
                ->selectRaw('SUM(delta) AS saldo')
                ->groupBy('barang_id')
                ->pluck('saldo', 'barang_id')
                ->all();
        };

        $fileCache = Cache::store('file');

        return $tanggalCutoff->isToday()
            ? $fileCache->remember($cacheKey, now()->addMinutes(5), $hitungSaldo)
            : $fileCache->rememberForever($cacheKey, $hitungSaldo);
    }

    private function pergerakanStok(Gudang $gudang, Carbon $tanggalCutoff): QueryBuilder
    {
        $stokAwal = DB::table('stok_awals')
            ->whereNull('deleted_at')
            ->where('gudang_id', $gudang->id)
            ->where('tanggal_acuan', '<=', $tanggalCutoff->toDateString())
            ->select('barang_id')
            ->selectRaw('qty AS delta');

        $penerimaanDetail = DB::table('penerimaan_barang_store_details AS detail')
            ->join('penerimaan_barang_stores AS header', 'header.id', '=', 'detail.penerimaan_barang_store_id')
            ->where('header.gudang_id', $gudang->id)
            ->where('header.tanggal_diterima', '<=', $tanggalCutoff)
            ->select('detail.barang_id')
            ->selectRaw(
                'CASE WHEN detail.qty_diterima > COALESCE(detail.qty_rusak, 0) '.
                'THEN detail.qty_diterima - COALESCE(detail.qty_rusak, 0) ELSE 0 END AS delta'
            );

        $penerimaanLegacy = DB::table('penerimaan_barang_stores AS header')
            ->where('header.gudang_id', $gudang->id)
            ->whereNotNull('header.barang_id')
            ->where('header.tanggal_diterima', '<=', $tanggalCutoff)
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('penerimaan_barang_store_details AS detail')
                    ->whereColumn('detail.penerimaan_barang_store_id', 'header.id');
            })
            ->select('header.barang_id')
            ->selectRaw('header.qty_diterima AS delta');

        $transferMasuk = DB::table('transfer_barangs')
            ->where('gudang_tujuan_id', $gudang->id)
            ->where('status', 'diterima')
            ->whereNotNull('diterima_at')
            ->where('diterima_at', '<=', $tanggalCutoff)
            ->select('barang_id')
            ->selectRaw('qty_diterima AS delta');

        $transferKeluar = DB::table('transfer_barangs')
            ->where('gudang_asal_id', $gudang->id)
            ->whereIn('status', ['dikirim', 'diterima'])
            ->whereNotNull('dikirim_at')
            ->where('dikirim_at', '<=', $tanggalCutoff)
            ->select('barang_id')
            ->selectRaw('-qty_dikirim AS delta');

        return $stokAwal
            ->unionAll($penerimaanDetail)
            ->unionAll($penerimaanLegacy)
            ->unionAll($transferMasuk)
            ->unionAll($transferKeluar);
    }

    public function barangMasuk(Gudang $gudang, string $periode, int $tanggal): QueryBuilder
    {
        return $this->penerimaanRows($gudang, $periode, $tanggal);
    }

    public function barangKeluar(Gudang $gudang, string $periode, int $tanggal): QueryBuilder
    {
        [$awalHari, $akhirHari] = $this->batasHari($periode, $tanggal);

        return DB::table('transfer_barangs AS transfer')
            ->leftJoin('barangs AS barang', 'barang.id', '=', 'transfer.barang_id')
            ->leftJoin('satuans AS satuan', 'satuan.id', '=', 'barang.satuan_id')
            ->leftJoin('gudangs AS tujuan', 'tujuan.id', '=', 'transfer.gudang_tujuan_id')
            ->leftJoin('users AS pengirim', 'pengirim.id', '=', 'transfer.dikirim_oleh')
            ->where('transfer.gudang_asal_id', $gudang->id)
            ->whereIn('transfer.status', ['dikirim', 'diterima'])
            ->whereBetween('transfer.dikirim_at', [$awalHari, $akhirHari])
            ->select([
                'transfer.id',
                'transfer.nomor_sj',
                'transfer.dikirim_at',
                'transfer.status',
                'transfer.qty_dikirim',
                'barang.kode AS kode_barang',
                'barang.nama AS nama_barang',
                'satuan.nama AS satuan',
                'tujuan.nama AS gudang_tujuan',
                'pengirim.name AS dikirim_oleh',
            ])
            ->orderBy('transfer.dikirim_at')
            ->orderBy('transfer.id');
    }

    public function pembelianTunaiTransfer(Gudang $gudang, string $periode, int $tanggal): QueryBuilder
    {
        return $this->penerimaanRows($gudang, $periode, $tanggal, ['Tunai', 'Transfer']);
    }

    /**
     * Build a single, flat query for detailed receipts and legacy header-only receipts.
     *
     * @param  array<int, string>|null  $metodePembayaran
     */
    private function penerimaanRows(
        Gudang $gudang,
        string $periode,
        int $tanggal,
        ?array $metodePembayaran = null,
    ): QueryBuilder {
        [$awalHari, $akhirHari] = $this->batasHari($periode, $tanggal);

        $details = DB::table('penerimaan_barang_store_details AS detail')
            ->join('penerimaan_barang_stores AS header', 'header.id', '=', 'detail.penerimaan_barang_store_id')
            ->leftJoin('barangs AS barang', 'barang.id', '=', 'detail.barang_id')
            ->leftJoin('satuans AS satuan', 'satuan.id', '=', 'detail.satuan_id')
            ->leftJoin('users AS penerima', 'penerima.id', '=', 'header.diterima_oleh')
            ->where('header.gudang_id', $gudang->id)
            ->whereBetween('header.tanggal_diterima', [$awalHari, $akhirHari])
            ->when(
                $metodePembayaran !== null,
                fn (QueryBuilder $query): QueryBuilder => $query->whereIn('header.metode_pembayaran', $metodePembayaran),
            )
            ->select([
                'detail.id',
                'header.nomor_penerimaan AS nomor_dokumen',
                'header.tanggal_diterima',
                'barang.kode AS kode_barang',
                'barang.nama AS nama_barang',
                'detail.qty_diterima AS qty',
                'satuan.nama AS satuan',
                'header.sumber_supplier AS sumber',
                'detail.harga_satuan',
                'detail.total_harga',
                'penerima.name AS diterima_oleh',
                'header.metode_pembayaran',
            ]);

        $legacy = DB::table('penerimaan_barang_stores AS header')
            ->leftJoin('barangs AS barang', 'barang.id', '=', 'header.barang_id')
            ->leftJoin('satuans AS header_satuan', 'header_satuan.id', '=', 'header.satuan_id')
            ->leftJoin('satuans AS barang_satuan', 'barang_satuan.id', '=', 'barang.satuan_id')
            ->leftJoin('users AS penerima', 'penerima.id', '=', 'header.diterima_oleh')
            ->where('header.gudang_id', $gudang->id)
            ->whereNotNull('header.barang_id')
            ->whereBetween('header.tanggal_diterima', [$awalHari, $akhirHari])
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('penerimaan_barang_store_details AS detail')
                    ->whereColumn('detail.penerimaan_barang_store_id', 'header.id');
            })
            ->when(
                $metodePembayaran !== null,
                fn (QueryBuilder $query): QueryBuilder => $query->whereIn('header.metode_pembayaran', $metodePembayaran),
            )
            ->select([
                'header.id',
                'header.nomor_penerimaan AS nomor_dokumen',
                'header.tanggal_diterima',
                'barang.kode AS kode_barang',
                'barang.nama AS nama_barang',
                'header.qty_diterima AS qty',
                DB::raw('COALESCE(header_satuan.nama, barang_satuan.nama) AS satuan'),
                'header.sumber_supplier AS sumber',
                DB::raw('NULL AS harga_satuan'),
                DB::raw('NULL AS total_harga'),
                'penerima.name AS diterima_oleh',
                'header.metode_pembayaran',
            ]);

        return DB::query()
            ->fromSub($details->unionAll($legacy), 'penerimaan_rows')
            ->select([
                'id',
                'nomor_dokumen',
                'tanggal_diterima',
                'kode_barang',
                'nama_barang',
                'qty',
                'satuan',
                'sumber',
                'harga_satuan',
                'total_harga',
                'diterima_oleh',
                'metode_pembayaran',
            ])
            ->orderBy('tanggal_diterima')
            ->orderBy('id');
    }

    /**
     * @return array{Carbon, Carbon}
     */
    private function batasHari(string $periode, int $tanggal): array
    {
        $awalBulan = Carbon::parse($periode.'-01')->startOfMonth();
        if ($tanggal < 1 || $tanggal > $awalBulan->daysInMonth) {
            throw new InvalidArgumentException('Tanggal laporan tidak valid untuk periode yang dipilih.');
        }

        $awalHari = $awalBulan->copy()->day($tanggal)->startOfDay();

        return [$awalHari, $awalHari->copy()->endOfDay()];
    }
}
