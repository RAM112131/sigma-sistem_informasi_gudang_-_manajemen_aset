<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\PenerimaanBarangStore;
use App\Models\PenerimaanBarangStoreDetail;
use App\Models\Satuan;
use App\Models\StokAwal;
use App\Models\StokOpnameDetail;
use App\Models\TransferBarang;
use App\Services\Inventory\StockImportService;
use App\Services\MasterData\MasterBarangService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use stdClass;

final class StokService
{
    public function __construct(
        private readonly MasterBarangService $masterBarangService,
        private readonly ?StockImportService $stockImportService = null,
    ) {}

    /**
     * @param  array{search?: ?string, q?: ?string, search_key?: string, kategori_id?: string|int|null, status?: ?string}  $filters
     * @return array{
     *     barangs: LengthAwarePaginator,
     *     kategoriList: \Illuminate\Database\Eloquent\Collection,
     *     satuanList: \Illuminate\Database\Eloquent\Collection
     * }
     */
    public function getListingData(Gudang $gudangStore, array $filters, int $perPage = 15): array
    {
        $query = $this->listingQuery($gudangStore, $filters);
        $statusFilter = $filters['status'] ?? null;

        if (in_array($statusFilter, ['aman', 'warning', 'kritis', 'critical'], true)) {
            $allBarangs = (clone $query)->orderBy('nama')->get(['id', 'minimum_stok']);
            $stokTotals = $this->totals($allBarangs->modelKeys(), (int) $gudangStore->id);
            $filteredIds = $allBarangs
                ->filter(fn (Barang $barang): bool => $this->matchesStatus(
                    $this->presentStockItem($barang, $stokTotals),
                    $statusFilter,
                ))
                ->modelKeys();
            $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
            $pageIds = collect($filteredIds)->forPage($currentPage, $perPage)->values();
            $pageBarangs = $pageIds->isEmpty()
                ? collect()
                : (clone $query)
                    ->with(['kategori:id,nama', 'satuan:id,nama'])
                    ->whereKey($pageIds)
                    ->get()
                    ->keyBy('id');
            $pageItems = $pageIds
                ->map(fn (int|string $id): ?array => ($barang = $pageBarangs->get($id)) instanceof Barang
                    ? $this->presentStockItem($barang, $stokTotals)
                    : null)
                ->filter()
                ->values();

            $barangs = new \Illuminate\Pagination\LengthAwarePaginator(
                $pageItems,
                count($filteredIds),
                $perPage,
                $currentPage,
                ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath()]
            );
            $barangs->withQueryString();
        } else {
            $barangsRaw = $query->with(['kategori', 'satuan'])->orderBy('nama')->paginate($perPage)->withQueryString();
            $stokTotals = $this->totals($barangsRaw->getCollection()->pluck('id'), (int) $gudangStore->id);
            $barangs = $barangsRaw->through(
                fn (Barang $barang): array => $this->presentStockItem($barang, $stokTotals)
            );
        }

        return [
            'barangs' => $barangs,
            'kategoriList' => KategoriBarang::query()
                ->where(function ($q) use ($gudangStore): void {
                    $q->where('gudang_id', $gudangStore->id)->orWhereNull('gudang_id');
                })
                ->where('is_aktif', true)
                ->orderBy('nama')
                ->get(['id', 'nama']),
            'satuanList' => Satuan::query()
                ->where('is_aktif', true)
                ->orderBy('nama')
                ->get(['id', 'kode', 'nama']),
        ];
    }

    /**
     * @return array{total_barang: int, stok_menipis: int, stok_habis: int}
     */
    public function getStockSummary(Gudang $gudang): array
    {
        $barangs = Barang::query()
            ->where('gudang_id', $gudang->id)
            ->where('is_aktif', true)
            ->get(['id', 'minimum_stok']);

        if ($barangs->isEmpty()) {
            return ['total_barang' => 0, 'stok_menipis' => 0, 'stok_habis' => 0];
        }

        $totals = $this->totals($barangs->modelKeys(), (int) $gudang->id);
        $menipis = 0;
        $habis = 0;

        foreach ($barangs as $barang) {
            $saldo = max(
                0.0,
                (float) ($totals['awal'][$barang->id] ?? 0)
                    + (float) ($totals['masuk'][$barang->id] ?? 0)
                    - (float) ($totals['keluar'][$barang->id] ?? 0)
            );

            if ($saldo <= 0) {
                $habis++;
            } elseif ($saldo <= (float) $barang->minimum_stok) {
                $menipis++;
            }
        }

        return [
            'total_barang' => $barangs->count(),
            'stok_menipis' => $menipis,
            'stok_habis' => $habis,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateUnitBarang(Barang $barang, array $attributes): void
    {
        DB::transaction(function () use ($barang, $attributes): void {
            $barang->update($attributes);
        });
    }

    public function deleteUnitBarang(Barang $barang, int $gudangId): void
    {
        DB::transaction(function () use ($barang, $gudangId): void {
            $barang = Barang::query()
                ->whereKey($barang->getKey())
                ->where('gudang_id', $gudangId)
                ->lockForUpdate()
                ->firstOrFail();
            $saldo = $this->saldo($barang, $gudangId)['saldo'];
            $pendingTransactions = $this->pendingUnitTransactions($barang, $gudangId);
            $blockers = [];

            if ($pendingTransactions !== []) {
                $transactions = collect($pendingTransactions)
                    ->map(static fn (array $references, string $type): string => $type.': '.implode(', ', $references))
                    ->implode('; ');
                $blockers[] = "masih ada transaksi yang belum selesai ({$transactions})";
            }

            if ($saldo > 0) {
                $blockers[] = 'saldo fisik masih '.number_format((float) $saldo, 3, ',', '.').' (saldo harus nol)';
            }

            if ($blockers !== []) {
                throw ValidationException::withMessages([
                    'barang' => "Barang '{$barang->nama}' belum dapat dihapus karena ".implode('; ', $blockers).'.',
                ]);
            }

            $barang->delete();
        });
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function pendingUnitTransactions(Barang $barang, int $gudangId): array
    {
        $barangId = (int) $barang->getKey();
        $transactions = [
            'Penerimaan GU' => $this->transactionReferences(
                DB::table('penerimaan_unit_details as details')
                    ->join('penerimaan_units as headers', 'headers.id', '=', 'details.penerimaan_unit_id')
                    ->where('details.barang_id', $barangId)
                    ->where('headers.gudang_id', $gudangId)
                    ->whereNotIn('headers.status_verifikasi', ['terverifikasi', 'sesuai']),
                'headers.nomor_rcv',
            ),
            'Pengeluaran GU' => $this->transactionReferences(
                DB::table('pengeluaran_unit_details as details')
                    ->join('pengeluaran_units as headers', 'headers.id', '=', 'details.pengeluaran_unit_id')
                    ->where('details.barang_id', $barangId)
                    ->where('headers.gudang_id', $gudangId)
                    ->whereNotIn('headers.status_verifikasi', ['terverifikasi', 'sesuai', 'ditolak']),
                'headers.nomor_bpb',
            ),
            'Penerimaan transfer' => $this->transactionReferences(
                DB::table('penerimaan_pemindahan_units as receipts')
                    ->join('transfer_barangs as transfers', 'transfers.id', '=', 'receipts.transfer_barang_id')
                    ->where('receipts.barang_id', $barangId)
                    ->where('receipts.gudang_id', $gudangId)
                    ->whereNotIn('receipts.status_verifikasi', ['terverifikasi', 'sesuai']),
                'transfers.nomor_sj',
            ),
            'Permintaan barang' => $this->transactionReferences(
                DB::table('permintaan_barang_details as details')
                    ->join('permintaan_barangs as headers', 'headers.id', '=', 'details.permintaan_barang_id')
                    ->where('details.barang_id', $barangId)
                    ->whereNotIn('headers.status', ['selesai', 'ditolak']),
                'headers.nomor_pr',
            ),
            'Transfer barang' => $this->transactionReferences(
                DB::table('transfer_barangs')
                    ->where('barang_id', $barangId)
                    ->whereNotIn('status', ['diterima']),
                'nomor_sj',
            ),
            'Stok opname' => $this->transactionReferences(
                DB::table('stok_opname_details as details')
                    ->join('stok_opnames as headers', 'headers.id', '=', 'details.stok_opname_id')
                    ->where('details.barang_id', $barangId)
                    ->where('headers.gudang_id', $gudangId)
                    ->whereNotIn('headers.status', ['diterapkan']),
                'headers.periode',
            ),
        ];

        return array_filter($transactions, static fn (array $references): bool => $references !== []);
    }

    /**
     * @return array<int, string>
     */
    private function transactionReferences(QueryBuilder $query, string $column): array
    {
        return $query
            ->select($column)
            ->distinct()
            ->orderBy($column)
            ->limit(3)
            ->pluck($column)
            ->map(static fn (mixed $reference): string => (string) $reference)
            ->all();
    }

    /**
     * @param  array{search?: ?string, q?: ?string, search_key?: string, kategori_id?: string|int|null, status?: ?string}  $filters
     * @return Collection<int, array{model: Barang, stok_aktual: float, total_masuk: float, total_keluar: float, total_stok_awal: float, status_text: string, badge: string}>
     */
    public function getExportRows(Gudang $gudang, array $filters): Collection
    {
        $barangs = $this->listingQuery($gudang, $filters)
            ->with(['kategori', 'satuan'])
            ->orderBy('nama')
            ->get();

        $stokTotals = $this->totals($barangs->modelKeys(), (int) $gudang->id);

        $rows = $barangs->map(
            fn (Barang $barang): array => $this->presentStockItem($barang, $stokTotals)
        );
        $statusFilter = $filters['status'] ?? null;

        if ($statusFilter === null || $statusFilter === '') {
            return $rows;
        }

        return $rows->filter(
            fn (array $item): bool => $this->matchesStatus($item, $statusFilter)
        )->values();
    }

    /**
     * @param  array{awal: Collection<int|string, float>, masuk: Collection<int|string, float>, keluar: Collection<int|string, float>}  $stokTotals
     * @return array{model: Barang, stok_aktual: float, total_masuk: float, total_keluar: float, total_stok_awal: float, status_text: string, badge: string}
     */
    private function presentStockItem(Barang $barang, array $stokTotals): array
    {
        $totalStokAwal = (float) ($stokTotals['awal'][$barang->id] ?? 0);
        $totalMasuk = $totalStokAwal + (float) ($stokTotals['masuk'][$barang->id] ?? 0);
        $totalKeluar = (float) ($stokTotals['keluar'][$barang->id] ?? 0);
        $stokAktual = max(0.0, $totalMasuk - $totalKeluar);

        [$statusText, $badge] = match (true) {
            $stokAktual <= 0 => ['Habis / Kritis', 'badge-critical'],
            $stokAktual <= $barang->minimum_stok => ['Mendekati Minimum', 'badge-warning'],
            default => ['Aman', 'badge-success'],
        };

        return [
            'model' => $barang,
            'stok_aktual' => $stokAktual,
            'total_masuk' => $totalMasuk,
            'total_keluar' => $totalKeluar,
            'total_stok_awal' => $totalStokAwal,
            'status_text' => $statusText,
            'badge' => $badge,
        ];
    }

    /**
     * @param  array{model: Barang, stok_aktual: float, total_masuk: float, total_keluar: float, total_stok_awal: float, status_text: string, badge: string}  $item
     */
    private function matchesStatus(array $item, string $status): bool
    {
        return match ($status) {
            'kritis' => $item['stok_aktual'] <= 0,
            'warning' => $item['stok_aktual'] > 0
                && $item['stok_aktual'] <= $item['model']->minimum_stok,
            'aman' => $item['stok_aktual'] > $item['model']->minimum_stok,
            'critical' => $item['stok_aktual'] <= $item['model']->minimum_stok,
            default => true,
        };
    }

    /**
     * @param  array{search?: ?string, q?: ?string, search_key?: string, kategori_id?: string|int|null}  $filters
     */
    private function listingQuery(Gudang $gudang, array $filters): Builder
    {
        $searchKey = $filters['search_key'] ?? 'search';
        $search = trim($filters[$searchKey] ?? '');

        $query = Barang::query()
            ->where('gudang_id', $gudang->id)
            ->where('is_aktif', true);

        if ($search !== '') {
            $pattern = '%'.$search.'%';
            $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            $query->where(function (Builder $matching) use ($pattern, $operator): void {
                $matching->where('kode', $operator, $pattern)
                    ->orWhere('nama', $operator, $pattern);
            });
        }

        if (! empty($filters['kategori_id'])) {
            $query->where('kategori_id', $filters['kategori_id']);
        }

        return $query;
    }

    /**
     * @param  array<string, int|string>  $mapping
     * @return array{rows: array<int, array<string, mixed>>, summary: array<string, int>}
     */
    public function analyzeImportFile(
        UploadedFile $file,
        int $sheetIndex,
        int $headingRow,
        array $mapping,
        int $gudangId,
    ): array {
        return $this->importService()->analyzeImportFile($file, $sheetIndex, $headingRow, $mapping, $gudangId);
    }

    /**
     * Process bounded chunks so each transaction has a predictable scope.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{success_count:int, created_count:int, skipped_count:int, error_rows:array<int, array<string, mixed>>}
     */
    public function importRowsInChunks(
        array $rows,
        int $gudangId,
        int $userId,
        string $tanggalAcuan,
        ?string $catatanUmum = null,
    ): array {
        return $this->importService()->importRowsInChunks($rows, $gudangId, $userId, $tanggalAcuan, $catatanUmum);
    }

    private function importService(): StockImportService
    {
        return $this->stockImportService ?? new StockImportService($this->masterBarangService);
    }

    /**
     * Return aggregated stock components for a set of warehouse items.
     * A cutoff date limits each movement to its effective date.
     *
     * @param  Collection<int, int>|array<int, int>  $barangIds
     * @return array{awal: Collection<int|string, float>, masuk: Collection<int|string, float>, keluar: Collection<int|string, float>}
     */
    public function totals(Collection|array $barangIds, int $gudangId, ?Carbon $cutoff = null): array
    {
        $ids = collect($barangIds)->filter()->values();
        $cutoffAt = $cutoff?->copy()->endOfDay();

        if ($ids->isEmpty()) {
            return ['awal' => collect(), 'masuk' => collect(), 'keluar' => collect()];
        }

        $sources = [
            DB::table('stok_awals')
                ->where('gudang_id', $gudangId)
                ->whereNull('deleted_at')
                ->whereIn('barang_id', $ids)
                ->when($cutoffAt, static fn (QueryBuilder $query): QueryBuilder => $query->where('tanggal_acuan', '<=', $cutoffAt->toDateString()))
                ->select('barang_id')
                ->selectRaw('SUM(qty) AS awal, CAST(NULL AS NUMERIC) AS masuk, CAST(NULL AS NUMERIC) AS keluar')
                ->groupBy('barang_id'),
            DB::table('penerimaan_barang_store_details AS details')
                ->join('penerimaan_barang_stores AS headers', 'headers.id', '=', 'details.penerimaan_barang_store_id')
                ->where('headers.gudang_id', $gudangId)
                ->whereIn('details.barang_id', $ids)
                ->when($cutoffAt, static fn (QueryBuilder $query): QueryBuilder => $query->where('headers.tanggal_diterima', '<=', $cutoffAt))
                ->select('details.barang_id')
                ->selectRaw(
                    'CAST(NULL AS NUMERIC) AS awal, SUM(CASE WHEN details.qty_diterima > COALESCE(details.qty_rusak, 0) '.
                    'THEN details.qty_diterima - COALESCE(details.qty_rusak, 0) ELSE 0 END) AS masuk, CAST(NULL AS NUMERIC) AS keluar',
                )
                ->groupBy('details.barang_id'),
            DB::table('penerimaan_barang_stores AS headers')
                ->where('headers.gudang_id', $gudangId)
                ->whereNotNull('headers.barang_id')
                ->whereIn('headers.barang_id', $ids)
                ->when($cutoffAt, static fn (QueryBuilder $query): QueryBuilder => $query->where('headers.tanggal_diterima', '<=', $cutoffAt))
                ->whereNotExists(static function (QueryBuilder $query): void {
                    $query->selectRaw('1')
                        ->from('penerimaan_barang_store_details AS details')
                        ->whereColumn('details.penerimaan_barang_store_id', 'headers.id');
                })
                ->select('headers.barang_id')
                ->selectRaw('CAST(NULL AS NUMERIC) AS awal, SUM(headers.qty_diterima) AS masuk, CAST(NULL AS NUMERIC) AS keluar')
                ->groupBy('headers.barang_id'),
            DB::table('transfer_barangs')
                ->leftJoin(
                    'penerimaan_pemindahan_units AS receipts',
                    'receipts.transfer_barang_id',
                    '=',
                    'transfer_barangs.id',
                )
                ->where('transfer_barangs.gudang_tujuan_id', $gudangId)
                ->whereIn('transfer_barangs.barang_id', $ids)
                ->where('transfer_barangs.status', 'diterima')
                ->when($cutoffAt, static fn (QueryBuilder $query): QueryBuilder => $query->whereRaw(
                    'COALESCE(transfer_barangs.diterima_at, transfer_barangs.updated_at, transfer_barangs.created_at) <= ?',
                    [$cutoffAt],
                ))
                ->select('transfer_barangs.barang_id')
                ->selectRaw(
                    'CAST(NULL AS NUMERIC) AS awal, SUM(CASE WHEN receipts.status_verifikasi IN (?, ?, ?) AND receipts.qty_diperiksa IS NOT NULL '.
                    'THEN CASE WHEN receipts.qty_diperiksa > COALESCE(receipts.qty_rusak_diperiksa, receipts.qty_rusak, 0) '.
                    'THEN receipts.qty_diperiksa - COALESCE(receipts.qty_rusak_diperiksa, receipts.qty_rusak, 0) ELSE 0 END '.
                    'ELSE CASE WHEN COALESCE(receipts.qty_diterima, transfer_barangs.qty_diterima) > COALESCE(receipts.qty_rusak, 0) '.
                    'THEN COALESCE(receipts.qty_diterima, transfer_barangs.qty_diterima) - COALESCE(receipts.qty_rusak, 0) ELSE 0 END END) AS masuk, '.
                    'CAST(NULL AS NUMERIC) AS keluar',
                    ['terverifikasi', 'perlu_tindak_lanjut', 'sesuai'],
                )
                ->groupBy('transfer_barangs.barang_id'),
            DB::table('penerimaan_unit_details AS details')
                ->join('penerimaan_units AS headers', 'headers.id', '=', 'details.penerimaan_unit_id')
                ->where('headers.gudang_id', $gudangId)
                ->whereIn('details.barang_id', $ids)
                ->when($cutoffAt, static fn (QueryBuilder $query): QueryBuilder => $query->where('headers.tanggal_terima', '<=', $cutoffAt->toDateString()))
                ->select('details.barang_id')
                ->selectRaw(
                    'CAST(NULL AS NUMERIC) AS awal, SUM(CASE WHEN headers.status_verifikasi IN (?, ?, ?) AND details.qty_diperiksa IS NOT NULL '.
                    'THEN CASE WHEN details.qty_diperiksa > COALESCE(details.qty_rusak_diperiksa, details.qty_rusak, 0) '.
                    'THEN details.qty_diperiksa - COALESCE(details.qty_rusak_diperiksa, details.qty_rusak, 0) ELSE 0 END '.
                    'ELSE CASE WHEN details.qty_diterima > COALESCE(details.qty_rusak, 0) '.
                    'THEN details.qty_diterima - COALESCE(details.qty_rusak, 0) ELSE 0 END END) AS masuk, CAST(NULL AS NUMERIC) AS keluar',
                    ['terverifikasi', 'perlu_tindak_lanjut', 'sesuai'],
                )
                ->groupBy('details.barang_id'),
            DB::table('pengeluaran_unit_details AS details')
                ->join('pengeluaran_units AS headers', 'headers.id', '=', 'details.pengeluaran_unit_id')
                ->where('headers.gudang_id', $gudangId)
                ->whereIn('details.barang_id', $ids)
                ->when($cutoffAt, static fn (QueryBuilder $query): QueryBuilder => $query->where('headers.tanggal', '<=', $cutoffAt->toDateString()))
                ->select('details.barang_id')
                ->selectRaw('CAST(NULL AS NUMERIC) AS awal, SUM(COALESCE(details.qty_kembali, 0)) AS masuk, CAST(NULL AS NUMERIC) AS keluar')
                ->groupBy('details.barang_id'),
            DB::table('transfer_barangs')
                ->where('gudang_asal_id', $gudangId)
                ->whereIn('barang_id', $ids)
                ->whereIn('status', ['dikirim', 'diterima'])
                ->when($cutoffAt, static fn (QueryBuilder $query): QueryBuilder => $query->whereRaw(
                    'COALESCE(dikirim_at, created_at) <= ?',
                    [$cutoffAt],
                ))
                ->select('barang_id')
                ->selectRaw('CAST(NULL AS NUMERIC) AS awal, CAST(NULL AS NUMERIC) AS masuk, SUM(qty_dikirim) AS keluar')
                ->groupBy('barang_id'),
            DB::table('pengeluaran_unit_details AS details')
                ->join('pengeluaran_units AS headers', 'headers.id', '=', 'details.pengeluaran_unit_id')
                ->where('headers.gudang_id', $gudangId)
                ->whereIn('details.barang_id', $ids)
                ->when($cutoffAt, static fn (QueryBuilder $query): QueryBuilder => $query->where('headers.tanggal', '<=', $cutoffAt->toDateString()))
                ->select('details.barang_id')
                ->selectRaw('CAST(NULL AS NUMERIC) AS awal, CAST(NULL AS NUMERIC) AS masuk, SUM(details.qty) AS keluar')
                ->groupBy('details.barang_id'),
            DB::table('stok_opname_details AS details')
                ->join('stok_opnames AS headers', 'headers.id', '=', 'details.stok_opname_id')
                ->where('headers.gudang_id', $gudangId)
                ->where('headers.status', 'diterapkan')
                ->whereIn('details.barang_id', $ids)
                ->when($cutoffAt, static fn (QueryBuilder $query): QueryBuilder => $query->whereRaw(
                    'COALESCE(DATE(details.qty_sistem_at), headers.tanggal_efektif) <= ?',
                    [$cutoffAt->toDateString()],
                ))
                ->select('details.barang_id')
                ->selectRaw(
                    'CAST(NULL AS NUMERIC) AS awal, '.
                    'SUM(CASE WHEN details.qty_fisik > details.qty_sistem THEN details.qty_fisik - details.qty_sistem ELSE 0 END) AS masuk, '.
                    'SUM(CASE WHEN details.qty_fisik < details.qty_sistem THEN details.qty_sistem - details.qty_fisik ELSE 0 END) AS keluar',
                )
                ->groupBy('details.barang_id'),
        ];

        $sourceQuery = $sources[0];
        foreach (array_slice($sources, 1) as $source) {
            $sourceQuery->unionAll($source);
        }

        $rows = DB::query()
            ->fromSub($sourceQuery, 'stock_sources')
            ->select('barang_id')
            ->selectRaw('SUM(awal) AS awal, SUM(masuk) AS masuk, SUM(keluar) AS keluar')
            ->groupBy('barang_id')
            ->get();

        $awal = $rows
            ->filter(static fn (stdClass $row): bool => $row->awal !== null)
            ->mapWithKeys(static fn (stdClass $row): array => [(int) $row->barang_id => (float) $row->awal]);
        $masuk = $rows
            ->filter(static fn (stdClass $row): bool => $row->masuk !== null)
            ->mapWithKeys(static fn (stdClass $row): array => [(int) $row->barang_id => (float) $row->masuk]);
        $keluar = $rows
            ->filter(static fn (stdClass $row): bool => $row->keluar !== null)
            ->mapWithKeys(static fn (stdClass $row): array => [(int) $row->barang_id => (float) $row->keluar]);

        return compact('awal', 'masuk', 'keluar');
    }

    /**
     * @return array{awal:float, masuk:float, keluar:float, saldo:float}
     */
    public function saldo(Barang $barang, int $gudangId): array
    {
        $totals = $this->totals([$barang->id], $gudangId);
        $awal = (float) ($totals['awal'][$barang->id] ?? 0);
        $masuk = (float) ($totals['masuk'][$barang->id] ?? 0);
        $keluar = (float) ($totals['keluar'][$barang->id] ?? 0);

        return [
            'awal' => $awal,
            'masuk' => $masuk,
            'keluar' => $keluar,
            'saldo' => max(0.0, $awal + $masuk - $keluar),
        ];
    }

    /**
     * Build chronological mutations and cumulative balances for a Store item.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function mutations(Barang $barang, int $gudangId): Collection
    {
        $initial = StokAwal::query()
            ->with('diinputOleh:id,name')
            ->where('gudang_id', $gudangId)
            ->where('barang_id', $barang->id)
            ->get(['id', 'diinput_oleh', 'tanggal_acuan', 'qty'])
            ->map(fn (StokAwal $stock) => [
                'tanggal' => $stock->tanggal_acuan,
                'jenis' => 'STOK AWAL',
                'badge' => 'badge-neutral',
                'ref' => 'STOK-AWAL-'.$stock->id,
                'pihak' => 'Opname Awal',
                'masuk' => (float) $stock->qty,
                'keluar' => 0.0,
                'petugas' => $stock->diinputOleh?->name ?? 'Sistem',
            ]);

        $details = PenerimaanBarangStoreDetail::query()
            ->with('penerimaan:id,diterima_oleh,tanggal_diterima,nomor_penerimaan,sumber_supplier')
            ->with('penerimaan.penerima:id,name')
            ->where('barang_id', $barang->id)
            ->whereHas('penerimaan', fn ($query) => $query->where('gudang_id', $gudangId))
            ->get(['id', 'penerimaan_barang_store_id', 'qty_diterima', 'qty_rusak'])
            ->map(fn (PenerimaanBarangStoreDetail $detail) => [
                'tanggal' => $detail->penerimaan->tanggal_diterima,
                'jenis' => 'BARANG MASUK',
                'badge' => 'badge-success',
                'ref' => $detail->penerimaan->nomor_penerimaan,
                'pihak' => $detail->penerimaan->sumber_supplier ?? 'Supplier',
                'masuk' => max(0.0, (float) $detail->qty_diterima - (float) $detail->qty_rusak),
                'keluar' => 0.0,
                'petugas' => $detail->penerimaan->penerima?->name ?? 'Admin Store',
            ]);

        $legacy = PenerimaanBarangStore::query()
            ->with('penerima:id,name')
            ->where('gudang_id', $gudangId)
            ->where('barang_id', $barang->id)
            ->whereDoesntHave('details')
            ->get(['id', 'diterima_oleh', 'tanggal_diterima', 'nomor_penerimaan', 'sumber_supplier', 'qty_diterima'])
            ->map(fn (PenerimaanBarangStore $receipt) => [
                'tanggal' => $receipt->tanggal_diterima,
                'jenis' => 'BARANG MASUK',
                'badge' => 'badge-success',
                'ref' => $receipt->nomor_penerimaan,
                'pihak' => $receipt->sumber_supplier ?? 'Supplier',
                'masuk' => (float) $receipt->qty_diterima,
                'keluar' => 0.0,
                'petugas' => $receipt->penerima?->name ?? 'Admin Store',
            ]);

        $transfers = TransferBarang::query()
            ->with('gudangTujuan:id,nama')
            ->where('gudang_asal_id', $gudangId)
            ->where('barang_id', $barang->id)
            ->whereIn('status', ['dikirim', 'diterima'])
            ->get(['id', 'gudang_tujuan_id', 'nomor_sj', 'qty_dikirim', 'supir_pengantar', 'dikirim_at', 'created_at'])
            ->map(fn (TransferBarang $transfer) => [
                'tanggal' => $transfer->dikirim_at ?? $transfer->created_at,
                'jenis' => 'BARANG KELUAR',
                'badge' => 'badge-critical',
                'ref' => $transfer->nomor_sj,
                'pihak' => $transfer->gudangTujuan?->nama ?? 'Unit Bisnis',
                'masuk' => 0.0,
                'keluar' => (float) $transfer->qty_dikirim,
                'petugas' => $transfer->supir_pengantar ?? 'Driver Armada',
            ]);

        $transfersIn = TransferBarang::query()
            ->with('gudangAsal:id,nama')
            ->where('gudang_tujuan_id', $gudangId)
            ->where('barang_id', $barang->id)
            ->whereIn('status', ['dikirim', 'diterima'])
            ->get(['id', 'gudang_asal_id', 'nomor_sj', 'qty_dikirim', 'qty_diterima', 'status', 'supir_pengantar', 'diterima_at', 'dikirim_at', 'created_at'])
            ->map(fn (TransferBarang $transfer) => [
                'tanggal' => $transfer->diterima_at ?? $transfer->dikirim_at ?? $transfer->created_at,
                'jenis' => 'KIRIMAN DARI STORE',
                'badge' => 'badge-success',
                'ref' => $transfer->nomor_sj,
                'pihak' => $transfer->gudangAsal?->nama ?? 'Gudang Store',
                'masuk' => (float) ($transfer->status === 'diterima' ? ($transfer->qty_diterima ?? $transfer->qty_dikirim) : $transfer->qty_dikirim),
                'keluar' => 0.0,
                'petugas' => $transfer->supir_pengantar ?? 'Driver Ekspedisi',
            ]);

        $opnameAdjustments = StokOpnameDetail::query()
            ->with('opname:id,diterapkan_oleh,periode,tanggal_efektif')
            ->with('opname.diterapkanOleh:id,name')
            ->where('barang_id', $barang->id)
            ->whereHas('opname', static fn (Builder $query): Builder => $query
                ->where('gudang_id', $gudangId)
                ->where('status', 'diterapkan'))
            ->get(['id', 'stok_opname_id', 'qty_fisik', 'qty_sistem', 'qty_sistem_at'])
            ->map(static function (StokOpnameDetail $detail): array {
                $selisih = (float) $detail->qty_fisik - (float) $detail->qty_sistem;
                $opname = $detail->opname;

                return [
                    'tanggal' => $detail->qty_sistem_at?->toDateString() ?? $opname->tanggal_efektif,
                    'jenis' => 'PENYESUAIAN STOK OPNAME',
                    'badge' => $selisih < 0 ? 'badge-critical' : 'badge-success',
                    'ref' => 'ADJ-SO-'.$opname->periode->format('ym'),
                    'pihak' => 'Stok Opname Bulanan',
                    'masuk' => max(0.0, $selisih),
                    'keluar' => max(0.0, -$selisih),
                    'petugas' => $opname->diterapkanOleh?->name ?? 'Admin Gudang Umum',
                ];
            });

        $saldo = 0.0;

        return $initial->merge($details)->merge($legacy)->merge($transfers)->merge($transfersIn)->merge($opnameAdjustments)
            ->sortBy('tanggal')
            ->values()
            ->map(function (array $mutation) use (&$saldo): array {
                $saldo += $mutation['masuk'] - $mutation['keluar'];
                $mutation['saldo'] = max(0.0, $saldo);

                return $mutation;
            });
    }

    /**
     * @return array{
     *     barang: array{nama: string, kode: string, kategori: string, satuan: string, minimum_stok: int, harga_satuan: float},
     *     gudang: string,
     *     mutasi: Collection<int, array<string, mixed>>
     * }
     */
    public function getStockCardData(Barang $barang, Gudang $gudang): array
    {
        abort_unless((int) $barang->gudang_id === (int) $gudang->getKey(), 404);

        $barang->loadMissing(['kategori', 'satuan']);

        return [
            'barang' => [
                'nama' => $barang->nama,
                'kode' => $barang->kode,
                'kategori' => $barang->kategori?->nama ?? 'Umum',
                'satuan' => $barang->satuan?->nama ?? '—',
                'minimum_stok' => (int) $barang->minimum_stok,
                'harga_satuan' => (float) ($barang->harga_satuan ?? 0),
            ],
            'gudang' => $gudang->nama,
            'mutasi' => $this->mutations($barang, (int) $gudang->getKey())
                ->map(fn (array $mutation): array => [
                    ...$mutation,
                    'tanggal' => Carbon::parse($mutation['tanggal'])->toIso8601String(),
                ])
                ->values(),
        ];
    }

    /**
     * Analyze imported stock rows against the current Store catalog.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{
     *     rows: array<int, array<string, mixed>>,
     *     summary: array{total:int, ok:int, duplicate:int, skip:int, error:int, new_barang:int}
     * }
     */
    /**
     * Search active warehouse & master catalog items for import autocomplete using vector search.
     *
     * @return array<int, array{
     *     kode_barang: string,
     *     nama_barang: string,
     *     kategori: string,
     *     satuan: string,
     *     satuan_nama: string,
     *     source: string,
     *     stok_awal_exists: bool
     * }>
     */
    public function searchImportCatalog(Gudang $gudang, string $search, ?string $field = null, int $limit = 8): array
    {
        return $this->importService()->searchCatalog((int) $gudang->getKey(), $search, $field, $limit);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{
     *     rows: array<int, array<string, mixed>>,
     *     summary: array{total:int, ok:int, duplicate:int, skip:int, error:int, new_barang:int}
     * }
     */
    public function analyzeImportRows(array $rows, int $gudangId): array
    {
        return $this->importService()->analyzeImportRows($rows, $gudangId);
    }

    /**
     * Process validated import rows in a transaction. Intended to be called by a queue job.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{success_count:int, created_count:int, skipped_count:int, error_rows:array<int, array<string, mixed>>}
     */
    public function importRows(array $rows, int $gudangId, int $userId, string $tanggalAcuan, ?string $catatanUmum = null): array
    {
        return $this->importService()->importRows($rows, $gudangId, $userId, $tanggalAcuan, $catatanUmum);
    }
}
