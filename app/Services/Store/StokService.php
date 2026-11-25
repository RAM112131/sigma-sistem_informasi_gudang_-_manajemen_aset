<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Imports\ColumnMappedRowsImport;
use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\MasterBarang;
use App\Models\PenerimaanBarangStore;
use App\Models\PenerimaanBarangStoreDetail;
use App\Models\Satuan;
use App\Models\StokAwal;
use App\Models\StokOpnameDetail;
use App\Models\TransferBarang;
use App\Services\MasterData\MasterBarangService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use stdClass;
use Throwable;

final class StokService
{
    public function __construct(
        private readonly MasterBarangService $masterBarangService,
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
        $import = new ColumnMappedRowsImport(
            $sheetIndex,
            $headingRow,
            array_map('intval', $mapping),
            50000,
        );
        Excel::import($import, $file);
        $rows = $import->rows();

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'Tidak ditemukan baris data pada sheet dan baris header yang dipilih.',
            ]);
        }

        $rows = array_map(static function (array $row): array {
            unset($row['line']);

            return $row;
        }, $rows);

        return $this->analyzeImportRows($rows, $gudangId);
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
        $result = ['success_count' => 0, 'created_count' => 0, 'skipped_count' => 0, 'error_rows' => []];

        foreach (array_chunk($rows, 250) as $chunk) {
            $chunkResult = $this->importRows($chunk, $gudangId, $userId, $tanggalAcuan, $catatanUmum);
            $result['success_count'] += $chunkResult['success_count'];
            $result['created_count'] += $chunkResult['created_count'];
            $result['skipped_count'] += $chunkResult['skipped_count'];
            $result['error_rows'] = [...$result['error_rows'], ...$chunkResult['error_rows']];
        }

        return $result;
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
    public function analyzeImportRows(array $rows, int $gudangId): array
    {
        $results = [];
        $seenKodes = [];
        $seenNamas = [];
        $summary = ['total' => 0, 'ok' => 0, 'duplicate' => 0, 'skip' => 0, 'error' => 0, 'new_barang' => 0];

        foreach (array_chunk($rows, 500, true) as $chunk) {
            $codes = collect($chunk)
                ->map(static fn (array $row): string => strtoupper(trim((string) ($row['kode_barang'] ?? ''))))
                ->filter()
                ->unique()
                ->values();
            $names = collect($chunk)
                ->map(static fn (array $row): string => mb_strtolower(trim((string) ($row['nama_barang'] ?? ''))))
                ->filter()
                ->unique()
                ->values();
            $masterIndexes = $this->indexImportMasterBarangs(
                $this->findImportMasterBarangs($codes, $names)
            );
            $existingBarang = collect();
            if ($codes->isNotEmpty() || $names->isNotEmpty()) {
                $existingBarang = Barang::query()
                    ->where('gudang_id', $gudangId)
                    ->where(function ($query) use ($codes, $names): void {
                        if ($codes->isNotEmpty()) {
                            $query->whereIn(DB::raw('UPPER(TRIM(kode))'), $codes);
                        }

                        if ($names->isNotEmpty()) {
                            $method = $codes->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                            $query->{$method}(DB::raw('LOWER(TRIM(nama))'), $names);
                        }
                    })
                    ->get(['id', 'kode', 'nama', 'satuan_id', 'master_barang_id']);
            }
            $existingKodes = $existingBarang->keyBy(
                static fn (Barang $barang): string => strtoupper(trim($barang->kode))
            );
            $existingNamas = $existingBarang->keyBy(
                static fn (Barang $barang): string => mb_strtolower(trim($barang->nama))
            );
            $existingStokAwalIds = StokAwal::query()
                ->where('gudang_id', $gudangId)
                ->whereIn('barang_id', $existingBarang->pluck('id'))
                ->pluck('barang_id')
                ->flip();
            $existingSatuans = Satuan::query()
                ->whereIn('id', $existingBarang->pluck('satuan_id')->filter()->unique())
                ->get(['id', 'kode'])
                ->keyBy('id');

            foreach ($chunk as $index => $row) {
                $rowNumber = (int) ($row['row'] ?? $index + 1);
                $issues = [];
                $warnings = [];

                $kode = strtoupper(trim((string) ($row['kode_barang'] ?? '')));
                $nama = trim((string) ($row['nama_barang'] ?? ''));
                $lowerNama = mb_strtolower($nama);
                $kategori = trim((string) ($row['kategori'] ?? ''));
                $satuan = strtoupper(trim((string) ($row['satuan'] ?? '')));
                $qtyRaw = trim((string) ($row['qty'] ?? ''));
                $numericQty = is_numeric($qtyRaw) ? (float) $qtyRaw : null;
                $qty = $numericQty !== null && floor($numericQty) === $numericQty
                    ? (int) $numericQty
                    : null;
                $catatan = trim((string) ($row['catatan'] ?? ''));

                if ($kode === '') {
                    $issues[] = 'kode_barang wajib diisi';
                }
                if ($nama === '') {
                    $issues[] = 'nama_barang wajib diisi';
                }
                if ($kategori === '') {
                    $issues[] = 'kategori wajib diisi';
                }
                if ($satuan === '') {
                    $issues[] = 'satuan wajib diisi';
                }
                if ($qty === null) {
                    if ($numericQty === null) {
                        $issues[] = 'qty harus berupa angka';
                    } elseif ($numericQty < 0) {
                        $issues[] = 'qty tidak boleh negatif';
                    } else {
                        $issues[] = 'qty harus berupa bilangan bulat';
                    }
                } elseif ($qty < 0) {
                    $issues[] = 'qty tidak boleh negatif';
                }

                if ($kode !== '' && isset($seenKodes[$kode])) {
                    $issues[] = "Duplikat di file: Kode '{$kode}' sudah ada di baris {$seenKodes[$kode]}";
                } elseif ($kode !== '') {
                    $seenKodes[$kode] = $rowNumber;
                }

                if ($lowerNama !== '' && isset($seenNamas[$lowerNama])) {
                    $issues[] = "Duplikat di file: Nama '{$nama}' sudah ada di baris {$seenNamas[$lowerNama]}";
                } elseif ($lowerNama !== '') {
                    $seenNamas[$lowerNama] = $rowNumber;
                }

                $isNewBarang = false;
                $stokAwalSudahAda = false;

                if ($issues === []) {
                    $byKode = $existingKodes->get($kode);
                    $byNama = $existingNamas->get($lowerNama);
                    $existing = $byKode ?? $byNama;

                    if ($byKode && mb_strtolower(trim($byKode->nama)) !== $lowerNama) {
                        $issues[] = "Kode '{$kode}' sudah digunakan oleh barang '{$byKode->nama}'.";
                    } elseif ($byNama && strtoupper(trim($byNama->kode)) !== $kode) {
                        $issues[] = "Nama '{$nama}' sudah digunakan dengan kode '{$byNama->kode}'.";
                    }

                    if ($issues === []) {
                        $masterConflict = $this->importMasterConflictMessage(
                            $kode,
                            $nama,
                            $masterIndexes['by_code'],
                            $masterIndexes['by_name'],
                            $existing?->master_barang_id === null ? null : (int) $existing->master_barang_id,
                        );

                        if ($masterConflict !== null) {
                            $issues[] = $masterConflict;
                        }
                    }

                    if ($existing) {
                        $stokAwalSudahAda = $existingStokAwalIds->has($existing->id);
                        if ($stokAwalSudahAda) {
                            $issues[] = 'Stok awal untuk barang ini sudah pernah tercatat di gudang Store.';
                        }

                        $satuanExisting = $existingSatuans->get($existing->satuan_id);
                        if ($satuanExisting && strtoupper(trim($satuanExisting->kode)) !== $satuan) {
                            $issues[] = "Satuan tidak cocok: barang existing menggunakan '{$satuanExisting->kode}'.";
                        }
                    } else {
                        $isNewBarang = true;
                        $warnings[] = "Barang baru akan dibuat otomatis: {$kode} — {$nama}";
                    }
                }

                $status = $issues === [] ? 'ok' : 'error';
                $results[] = [
                    'row' => $rowNumber,
                    'kode_barang' => $kode,
                    'nama_barang' => $nama,
                    'kategori' => $kategori,
                    'satuan' => $satuan,
                    'qty' => $qty,
                    'catatan' => $catatan,
                    'status' => $status,
                    'is_duplicate' => false,
                    'dup_reason' => null,
                    'is_new_barang' => $isNewBarang,
                    'stok_awal_exists' => $stokAwalSudahAda,
                    'issues' => $issues,
                    'warnings' => $warnings,
                ];

                $summary['total']++;
                $summary[$status]++;
                if ($isNewBarang && $status === 'ok') {
                    $summary['new_barang']++;
                }
            }
        }

        return [
            'rows' => $results,
            'summary' => $summary,
        ];
    }

    /**
     * Process validated import rows in a transaction. Intended to be called by a queue job.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{success_count:int, created_count:int, skipped_count:int, error_rows:array<int, array<string, mixed>>}
     */
    public function importRows(array $rows, int $gudangId, int $userId, string $tanggalAcuan, ?string $catatanUmum = null): array
    {
        return DB::transaction(function () use ($rows, $gudangId, $userId, $tanggalAcuan, $catatanUmum): array {
            $success = 0;
            $created = 0;
            $errors = [];
            $gudang = Gudang::query()->findOrFail($gudangId);

            foreach (array_chunk($rows, 500, true) as $chunk) {
                $codes = collect($chunk)
                    ->map(static fn (array $row): string => strtoupper(trim((string) ($row['kode_barang'] ?? ''))))
                    ->filter()
                    ->unique()
                    ->values();
                $names = collect($chunk)
                    ->map(static fn (array $row): string => mb_strtolower(trim((string) ($row['nama_barang'] ?? ''))))
                    ->filter()
                    ->unique()
                    ->values();
                $lowerNames = $names;
                $categoryNames = collect($chunk)
                    ->map(static fn (array $row): string => trim((string) ($row['kategori'] ?? '')))
                    ->filter()
                    ->unique()
                    ->values();
                $unitCodes = collect($chunk)
                    ->map(static fn (array $row): string => strtoupper(trim((string) ($row['satuan'] ?? ''))))
                    ->filter()
                    ->unique()
                    ->values();
                $masterIndexes = $this->indexImportMasterBarangs(
                    $this->findImportMasterBarangs($codes, $names, lockForUpdate: true)
                );
                $existingBarangs = $codes->isEmpty() && $lowerNames->isEmpty()
                    ? collect()
                    : Barang::query()
                        ->where('gudang_id', $gudangId)
                        ->where(static function (Builder $query) use ($codes, $lowerNames): void {
                            if ($codes->isNotEmpty()) {
                                $query->whereIn('kode', $codes);
                            }

                            if ($lowerNames->isNotEmpty()) {
                                $method = $codes->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                                $query->{$method}(DB::raw('LOWER(nama)'), $lowerNames);
                            }
                        })
                        ->with(['satuan:id,kode', 'masterBarang:id,nama,is_aktif'])
                        ->orderBy('id')
                        ->get(['id', 'master_barang_id', 'satuan_id', 'kode', 'nama']);
                $barangsByCode = [];
                $barangsByName = [];
                foreach ($existingBarangs as $existingBarang) {
                    $barangsByCode[$existingBarang->kode] ??= $existingBarang;
                    $barangsByName[mb_strtolower($existingBarang->nama)] ??= $existingBarang;
                }

                $existingStokAwalIds = $existingBarangs->isEmpty()
                    ? []
                    : StokAwal::query()
                        ->where('gudang_id', $gudangId)
                        ->whereIn('barang_id', $existingBarangs->pluck('id'))
                        ->pluck('barang_id')
                        ->mapWithKeys(static fn (int|string $id): array => [(int) $id => true])
                        ->all();
                $categoriesByName = $categoryNames->isEmpty()
                    ? []
                    : KategoriBarang::query()
                        ->where('gudang_id', $gudangId)
                        ->whereIn('nama', $categoryNames)
                        ->orderBy('id')
                        ->get(['id', 'gudang_id', 'nama', 'is_aktif'])
                        ->keyBy('nama')
                        ->all();
                $satuansByCode = $unitCodes->isEmpty()
                    ? []
                    : Satuan::query()
                        ->whereIn(DB::raw('UPPER(kode)'), $unitCodes)
                        ->get(['id', 'kode', 'nama', 'is_aktif'])
                        ->mapWithKeys(static fn (Satuan $satuan): array => [strtoupper($satuan->kode) => $satuan])
                        ->all();

                foreach ($chunk as $index => $row) {
                    try {
                        DB::transaction(function () use (
                            $row,
                            $gudangId,
                            $userId,
                            $tanggalAcuan,
                            $catatanUmum,
                            $gudang,
                            $masterIndexes,
                            &$barangsByCode,
                            &$barangsByName,
                            &$existingStokAwalIds,
                            &$categoriesByName,
                            &$satuansByCode,
                            &$success,
                            &$created,
                        ): void {
                            $kode = strtoupper(trim((string) $row['kode_barang']));
                            $nama = trim((string) $row['nama_barang']);
                            $satuanKode = strtoupper(trim((string) $row['satuan']));
                            $barangByKode = $barangsByCode[$kode] ?? null;
                            $barangByNama = $barangsByName[mb_strtolower($nama)] ?? null;
                            $barang = $barangByKode ?? $barangByNama;

                            if ($barangByKode && mb_strtolower(trim($barangByKode->nama)) !== mb_strtolower($nama)) {
                                throw new \RuntimeException("Kode '{$kode}' sudah digunakan oleh barang '{$barangByKode->nama}'.");
                            }
                            if ($barangByNama && strtoupper(trim($barangByNama->kode)) !== $kode) {
                                throw new \RuntimeException("Nama '{$nama}' sudah digunakan dengan kode '{$barangByNama->kode}'.");
                            }

                            $masterConflict = $this->importMasterConflictMessage(
                                $kode,
                                $nama,
                                $masterIndexes['by_code'],
                                $masterIndexes['by_name'],
                                $barang?->master_barang_id === null ? null : (int) $barang->master_barang_id,
                            );

                            if ($masterConflict !== null) {
                                throw new \RuntimeException($masterConflict);
                            }

                            if ($barang && isset($existingStokAwalIds[(int) $barang->id])) {
                                throw new \RuntimeException('Stok awal untuk barang ini sudah pernah tercatat di gudang Store.');
                            }

                            $categoryName = trim((string) $row['kategori']);
                            $kategori = $categoriesByName[$categoryName] ?? null;
                            if (! $kategori) {
                                $kategori = KategoriBarang::firstOrCreate(
                                    ['gudang_id' => $gudangId, 'nama' => $categoryName],
                                    ['is_aktif' => true]
                                );
                            }

                            $satuan = $satuansByCode[$satuanKode] ?? null;
                            if (! $satuan) {
                                $satuan = Satuan::create([
                                    'kode' => $satuanKode,
                                    'nama' => ucfirst(strtolower($satuanKode)),
                                    'is_aktif' => true,
                                ]);
                            }
                            if ($barang && strtoupper(trim($barang->satuan?->kode ?? '')) !== $satuanKode) {
                                throw new \RuntimeException("Satuan tidak cocok: barang existing menggunakan '{$barang->satuan?->kode}'.");
                            }

                            $masterBarang = $barang?->masterBarang;
                            if ($masterBarang && ! $masterBarang->is_aktif) {
                                throw new \RuntimeException("Master barang '{$masterBarang->nama}' sedang nonaktif.");
                            }

                            if (! $masterBarang) {
                                $masterBarang = $this->masterBarangService->findOrCreateForLocalCode(
                                    $kode,
                                    $nama,
                                    (int) $kategori->id,
                                    (int) $satuan->id,
                                );
                            }

                            if (! $barang) {
                                $barang = $this->masterBarangService->registerInWarehouse(
                                    $masterBarang,
                                    $gudang,
                                    $kode,
                                    10,
                                );
                                $created++;
                            } elseif (! $barang->master_barang_id) {
                                $barang->update(['master_barang_id' => $masterBarang->id]);
                            }

                            StokAwal::create([
                                'gudang_id' => $gudangId,
                                'barang_id' => $barang->id,
                                'qty' => (int) $row['qty'],
                                'tanggal_acuan' => $tanggalAcuan,
                                'catatan' => trim((string) ($row['catatan'] ?? '')) ?: ($catatanUmum ?? 'Import Data Awal'),
                                'sumber' => 'import',
                                'diinput_oleh' => $userId,
                            ]);
                            $barangsByCode[$kode] = $barang;
                            $barangsByName[mb_strtolower($nama)] = $barang;
                            $existingStokAwalIds[(int) $barang->id] = true;
                            $categoriesByName[$categoryName] = $kategori;
                            $satuansByCode[$satuanKode] = $satuan;
                            $success++;
                        });
                    } catch (Throwable $exception) {
                        $errors[] = [
                            'row' => (int) ($row['row'] ?? $index + 1),
                            'kode' => $row['kode_barang'] ?? null,
                            'error' => $exception->getMessage(),
                        ];
                    }
                }
            }

            return ['success_count' => $success, 'created_count' => $created, 'skipped_count' => 0, 'error_rows' => $errors];
        });
    }

    /**
     * @return Collection<int, MasterBarang>
     */
    private function findImportMasterBarangs(Collection $codes, Collection $names, bool $lockForUpdate = false): Collection
    {
        if ($codes->isEmpty() && $names->isEmpty()) {
            return collect();
        }

        $query = MasterBarang::withTrashed()
            ->where(function ($query) use ($codes, $names): void {
                if ($codes->isNotEmpty()) {
                    $query->whereIn(DB::raw('UPPER(TRIM(kode))'), $codes);
                }

                if ($names->isNotEmpty()) {
                    $method = $codes->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                    $query->{$method}(DB::raw('LOWER(TRIM(nama))'), $names);
                }
            });

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->get(['id', 'kode', 'nama', 'is_aktif', 'deleted_at']);
    }

    /**
     * @param  Collection<int, MasterBarang>  $masterBarangs
     * @return array{
     *     by_code: Collection<string, Collection<int, MasterBarang>>,
     *     by_name: Collection<string, Collection<int, MasterBarang>>
     * }
     */
    private function indexImportMasterBarangs(Collection $masterBarangs): array
    {
        return [
            'by_code' => $masterBarangs->groupBy(
                static fn (MasterBarang $masterBarang): string => strtoupper(trim($masterBarang->kode))
            ),
            'by_name' => $masterBarangs->groupBy(
                static fn (MasterBarang $masterBarang): string => mb_strtolower(trim($masterBarang->nama))
            ),
        ];
    }

    private function importMasterConflictMessage(
        string $kode,
        string $nama,
        Collection $masterBarangsByCode,
        Collection $masterBarangsByName,
        ?int $linkedMasterBarangId = null,
    ): ?string {
        $lowerNama = mb_strtolower(trim($nama));
        $codeMatches = $masterBarangsByCode->get($kode, collect());
        $nameMatches = $masterBarangsByName->get($lowerNama, collect());

        $masterWithCodeConflict = $codeMatches->first(
            static fn (MasterBarang $masterBarang): bool => mb_strtolower(trim($masterBarang->nama)) !== $lowerNama
                && (int) $masterBarang->id !== $linkedMasterBarangId
        );

        if ($masterWithCodeConflict) {
            return "Kode '{$kode}' sudah digunakan oleh Master Barang '{$masterWithCodeConflict->nama}'.";
        }

        $masterWithNameConflict = $nameMatches->first(
            static fn (MasterBarang $masterBarang): bool => strtoupper(trim($masterBarang->kode)) !== $kode
                && (int) $masterBarang->id !== $linkedMasterBarangId
        );

        if ($masterWithNameConflict) {
            return "Nama '{$nama}' sudah digunakan oleh Master Barang dengan kode '{$masterWithNameConflict->kode}'.";
        }

        $matchingMaster = $codeMatches->first(
            static fn (MasterBarang $masterBarang): bool => mb_strtolower(trim($masterBarang->nama)) === $lowerNama
        ) ?? $nameMatches->first(
            static fn (MasterBarang $masterBarang): bool => (int) $masterBarang->id === $linkedMasterBarangId
        );

        if ($matchingMaster && ($matchingMaster->trashed() || ! $matchingMaster->is_aktif)) {
            return "Master barang '{$matchingMaster->nama}' sedang nonaktif.";
        }

        return null;
    }
}
