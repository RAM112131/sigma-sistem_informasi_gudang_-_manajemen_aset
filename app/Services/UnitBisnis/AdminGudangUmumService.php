<?php

declare(strict_types=1);

namespace App\Services\UnitBisnis;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\PenerimaanPemindahanUnit;
use App\Models\PenerimaanUnit;
use App\Models\PenerimaanUnitDetail;
use App\Models\PengeluaranUnit;
use App\Models\PengeluaranUnitDetail;
use App\Models\PengembalianUnit;
use App\Models\PermintaanBarang;
use App\Models\PermintaanBarangDetail;
use App\Models\TransferBarang;
use App\Models\User;
use App\Services\Store\PengeluaranStoreService;
use App\Services\Store\StokService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AdminGudangUmumService
{
    public function __construct(
        private readonly UnitGudangService $unitGudangService,
        private readonly PengeluaranStoreService $pengeluaranStoreService,
        private readonly StokService $stokService,
    ) {}

    /**
     * @return array{
     *     stats: array{
     *         pending_receipt_count: int,
     *         low_stock_count: int,
     *         active_sku_count: int,
     *         minimum_configured_sku_count: int,
     *         received_today_count: int,
     *         estimated_inventory_value: ?float,
     *         unpriced_sku_count: int
     *     },
     *     verificationQueue: array<int, array{document: string, type: string, source: string, task: string}>,
     *     lowStockItems: array<int, array{kode: string, nama: string, stok_aktual: float, minimum_stok: int, satuan: ?string}>,
     *     chartData: array{
     *         movements: array{
     *             labels: array<int, string>,
     *             incoming: array<int, int>,
     *             outgoing: array<int, int>,
     *             incoming_total: int,
     *             outgoing_total: int,
     *             average_outgoing_transactions_per_day: float,
     *             incoming_ratio: ?float,
     *             has_data: bool
     *         },
     *         divisions: array{labels: array<int, string>, values: array<int, int>, has_data: bool}
     *     },
     *     verificationHistory: array<int, array{occurred_at: string, reference: string, type: string, impact: string, status: string}>
     * }
     */
    public function getDashboardData(
        ?Gudang $gudang,
        string $chartStartDate,
        string $chartEndDate,
        string $chartPeriod,
    ): array {
        if ($gudang === null) {
            return [
                'stats' => [
                    'pending_receipt_count' => 0,
                    'low_stock_count' => 0,
                    'active_sku_count' => 0,
                    'minimum_configured_sku_count' => 0,
                    'received_today_count' => 0,
                    'estimated_inventory_value' => null,
                    'unpriced_sku_count' => 0,
                ],
                'verificationQueue' => [],
                'lowStockItems' => [],
                'chartData' => [
                    'movements' => [
                        'labels' => [],
                        'incoming' => [],
                        'outgoing' => [],
                        'incoming_total' => 0,
                        'outgoing_total' => 0,
                        'average_outgoing_transactions_per_day' => 0.0,
                        'incoming_ratio' => null,
                        'has_data' => false,
                    ],
                    'divisions' => ['labels' => [], 'values' => [], 'has_data' => false],
                ],
                'verificationHistory' => [],
            ];
        }

        $barangs = Barang::query()
            ->with(['satuan:id,nama', 'kategori:id,nama'])
            ->where('gudang_id', $gudang->getKey())
            ->where('is_aktif', true)
            ->get(['id', 'kode', 'nama', 'minimum_stok', 'satuan_id', 'kategori_id', 'harga_satuan']);
        $stockTotals = $this->stokService->totals($barangs->modelKeys(), (int) $gudang->getKey());
        $lowStockItems = [];
        $minimumConfiguredSkuCount = 0;
        $unpricedSkuCount = 0;
        $estimatedInventoryValue = 0.0;

        foreach ($barangs as $barang) {
            $stock = max(
                0.0,
                (float) ($stockTotals['awal'][$barang->getKey()] ?? 0)
                    + (float) ($stockTotals['masuk'][$barang->getKey()] ?? 0)
                    - (float) ($stockTotals['keluar'][$barang->getKey()] ?? 0),
            );
            $minimumStock = (int) $barang->minimum_stok;

            if ($minimumStock > 0) {
                $minimumConfiguredSkuCount++;
            }

            if ($stock > 0 && (float) ($barang->harga_satuan ?? 0) <= 0) {
                $unpricedSkuCount++;
            } else {
                $estimatedInventoryValue += $stock * (float) ($barang->harga_satuan ?? 0);
            }

            if ($minimumStock > 0 && $stock <= $minimumStock) {
                $lowStockItems[] = [
                    'kode' => (string) $barang->kode,
                    'nama' => (string) $barang->nama,
                    'stok_aktual' => $stock,
                    'minimum_stok' => $minimumStock,
                    'satuan' => $barang->satuan?->nama,
                ];
            }
        }

        usort($lowStockItems, static fn (array $first, array $second): int => ($first['stok_aktual'] <=> $second['stok_aktual'])
            ?: ($second['minimum_stok'] <=> $first['minimum_stok'])
            ?: strcmp($first['nama'], $second['nama']));

        $pendingTransferQuery = TransferBarang::query()
            ->where('gudang_tujuan_id', $gudang->getKey())
            ->where('status', 'dikirim');

        return [
            'stats' => [
                'pending_receipt_count' => (clone $pendingTransferQuery)->count(),
                'low_stock_count' => count($lowStockItems),
                'active_sku_count' => $barangs->count(),
                'minimum_configured_sku_count' => $minimumConfiguredSkuCount,
                'received_today_count' => TransferBarang::query()
                    ->where('gudang_tujuan_id', $gudang->getKey())
                    ->where('status', 'diterima')
                    ->whereDate('diterima_at', today())
                    ->count(),
                'estimated_inventory_value' => $barangs->isNotEmpty() && $unpricedSkuCount === 0
                    ? round($estimatedInventoryValue, 2)
                    : null,
                'unpriced_sku_count' => $unpricedSkuCount,
            ],
            'verificationQueue' => $this->getVerificationQueue($gudang),
            'lowStockItems' => $lowStockItems,
            'chartData' => $this->getDashboardChartData(
                $gudang,
                $chartStartDate,
                $chartEndDate,
                $chartPeriod,
            ),
            'verificationHistory' => $this->getDashboardVerificationHistory($gudang),
        ];
    }

    /**
     * @return array<int, array{document: string, type: string, source: string, task: string}>
     */
    private function getVerificationQueue(Gudang $gudang): array
    {
        $queue = collect();

        PenerimaanUnit::query()
            ->where('gudang_id', $gudang->getKey())
            ->where('status_verifikasi', 'menunggu_verifikasi')
            ->whereHas('details')
            ->with(['details:id,penerimaan_unit_id,harga_beli'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'nomor_rcv', 'nama_supplier', 'created_at'])
            ->each(static function (PenerimaanUnit $receipt) use ($queue): void {
                $needsPurchasePrice = $receipt->details->contains(
                    static fn (PenerimaanUnitDetail $detail): bool => (float) $detail->harga_beli <= 0,
                );

                $queue->push([
                    'document' => (string) $receipt->nomor_rcv,
                    'type' => 'Penerimaan',
                    'source' => 'Supplier '.$receipt->nama_supplier,
                    'task' => $needsPurchasePrice ? 'Input Harga Beli' : 'Cocokkan Bon',
                    'sort_timestamp' => $receipt->created_at?->getTimestamp() ?? 0,
                    'sort_id' => (int) $receipt->getKey(),
                ]);
            });

        $items = $queue->all();
        usort($items, static fn (array $first, array $second): int => ($second['sort_timestamp'] <=> $first['sort_timestamp'])
            ?: ($second['sort_id'] <=> $first['sort_id']));

        return collect($items)
            ->take(10)
            ->map(static function (array $item): array {
                unset($item['sort_timestamp'], $item['sort_id']);

                return $item;
            })
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     movements: array{
     *         labels: array<int, string>,
     *         incoming: array<int, int>,
     *         outgoing: array<int, int>,
     *         incoming_total: int,
     *         outgoing_total: int,
     *         average_outgoing_transactions_per_day: float,
     *         incoming_ratio: ?float,
     *         has_data: bool
     *     },
     *     divisions: array{labels: array<int, string>, values: array<int, int>, has_data: bool}
     * }
     */
    private function getDashboardChartData(
        Gudang $gudang,
        string $startDate,
        string $endDate,
        string $period,
    ): array {
        $periodStart = Carbon::parse($startDate)->startOfDay();
        $periodEnd = Carbon::parse($endDate)->startOfDay();
        $dateRange = collect();

        for ($date = $periodStart->copy(); $date->lte($periodEnd); $date->addDay()) {
            $dateRange->push($date->copy());
        }

        $supplierReceiptCountsByDate = DB::table('penerimaan_units')
            ->where('gudang_id', $gudang->getKey())
            ->whereBetween('tanggal_terima', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->select('tanggal_terima as movement_date')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('tanggal_terima')
            ->get()
            ->mapWithKeys(static fn (object $row): array => [(string) $row->movement_date => (int) $row->total]);

        $transferReceiptCountsByDate = DB::table('penerimaan_pemindahan_units')
            ->where('gudang_id', $gudang->getKey())
            ->whereBetween('diterima_at', [$periodStart, $periodEnd->copy()->endOfDay()])
            ->selectRaw('DATE(diterima_at) AS movement_date')
            ->selectRaw('COUNT(*) AS total')
            ->groupByRaw('DATE(diterima_at)')
            ->get()
            ->mapWithKeys(static fn (object $row): array => [(string) $row->movement_date => (int) $row->total]);

        $outgoingTransactionCountsByDate = DB::table('pengeluaran_units')
            ->where('gudang_id', $gudang->getKey())
            ->whereBetween('tanggal', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->select('tanggal as movement_date')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('tanggal')
            ->get()
            ->mapWithKeys(static fn (object $row): array => [(string) $row->movement_date => (int) $row->total]);

        $incomingTransactionCounts = $dateRange
            ->map(static fn ($date): int => (int) $supplierReceiptCountsByDate->get($date->toDateString(), 0)
                + (int) $transferReceiptCountsByDate->get($date->toDateString(), 0))
            ->all();
        $outgoingTransactionCounts = $dateRange
            ->map(static fn ($date): int => (int) $outgoingTransactionCountsByDate->get($date->toDateString(), 0))
            ->all();
        $incomingTotal = array_sum($incomingTransactionCounts);
        $outgoingTotal = array_sum($outgoingTransactionCounts);
        $dayCount = max(1, $dateRange->count());
        $averageOutgoingTransactionsPerDay = round($outgoingTotal / $dayCount, 1);
        $divisionCounts = DB::table('pengeluaran_units AS issues')
            ->leftJoin('divisi_pemintas AS divisions', static function (JoinClause $join): void {
                $join->on('divisions.id', '=', 'issues.divisi_peminta_id')
                    ->on('divisions.gudang_id', '=', 'issues.gudang_id');
            })
            ->where('issues.gudang_id', $gudang->getKey())
            ->selectRaw(
                "COALESCE(NULLIF(TRIM(divisions.nama), ''), NULLIF(TRIM(issues.bagian_peminta), ''), 'Tanpa divisi') AS division"
            )
            ->selectRaw('COUNT(*) AS total')
            ->groupByRaw(
                "COALESCE(NULLIF(TRIM(divisions.nama), ''), NULLIF(TRIM(issues.bagian_peminta), ''), 'Tanpa divisi')"
            )
            ->orderByDesc('total')
            ->orderBy('division')
            ->limit(5)
            ->get();

        return [
            'movements' => [
                'labels' => $dateRange
                    ->map(static fn (Carbon $date): string => $period === 'week'
                        ? ucfirst($date->locale('id')->translatedFormat('l'))
                        : $date->format('d/m'))
                    ->all(),
                'incoming' => $incomingTransactionCounts,
                'outgoing' => $outgoingTransactionCounts,
                'incoming_total' => $incomingTotal,
                'outgoing_total' => $outgoingTotal,
                'average_outgoing_transactions_per_day' => $averageOutgoingTransactionsPerDay,
                'incoming_ratio' => $outgoingTotal > 0
                    ? round((($incomingTotal - $outgoingTotal) / $outgoingTotal) * 100, 1)
                    : null,
                'has_data' => $incomingTotal + $outgoingTotal > 0,
            ],
            'divisions' => [
                'labels' => $divisionCounts->pluck('division')->all(),
                'values' => $divisionCounts->pluck('total')
                    ->map(static fn (mixed $total): int => (int) $total)
                    ->all(),
                'has_data' => $divisionCounts->isNotEmpty(),
            ],
        ];
    }

    /**
     * @return array<int, array{occurred_at: string, reference: string, type: string, impact: string, status: string}>
     */
    private function getDashboardVerificationHistory(Gudang $gudang): array
    {
        $verifiedStatuses = ['terverifikasi', 'sesuai'];
        $history = collect();

        PengeluaranUnit::query()
            ->where('gudang_id', $gudang->getKey())
            ->whereIn('status_verifikasi', $verifiedStatuses)
            ->whereDate('diverifikasi_at', today())
            ->with(['details.barang'])
            ->orderByDesc('diverifikasi_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->each(static function (PengeluaranUnit $issue) use ($history): void {
                $impact = $issue->details
                    ->map(static function (PengeluaranUnitDetail $detail): string {
                        $hpp = (float) $detail->harga_hpp > 0
                            ? (float) $detail->harga_hpp
                            : (float) ($detail->barang?->harga_satuan ?? 0);

                        return '-'.self::formatQuantity((float) $detail->qty).' '.$detail->satuan
                            .' | HPP Rp '.number_format($hpp, 0, ',', '.');
                    })
                    ->implode('; ');

                $history->push([
                    'occurred_at' => $issue->diverifikasi_at?->format('d M Y, H:i') ?? 'Waktu tidak tersedia',
                    'reference' => (string) $issue->nomor_bpb,
                    'type' => 'Pengeluaran',
                    'impact' => $impact !== '' ? $impact : 'Dampak stok belum tersedia',
                    'status' => 'Disetujui Admin GU',
                    'sort_timestamp' => $issue->diverifikasi_at?->getTimestamp() ?? 0,
                    'sort_id' => (int) $issue->getKey(),
                ]);
            });

        PengembalianUnit::query()
            ->where('gudang_id', $gudang->getKey())
            ->whereDate('created_at', today())
            ->whereHas('pengeluaranUnit', static fn (Builder $query): Builder => $query
                ->whereIn('status_verifikasi', $verifiedStatuses)
                ->whereDate('diverifikasi_at', today()))
            ->with([
                'pengeluaranUnit:id,nomor_bpb,status_verifikasi,diverifikasi_at',
                'pengeluaranUnitDetail.barang',
                'barang:id,harga_satuan',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->each(static function (PengembalianUnit $return) use ($history): void {
                $hpp = (float) ($return->pengeluaranUnitDetail?->harga_hpp ?? 0);
                if ($hpp <= 0) {
                    $hpp = (float) ($return->pengeluaranUnitDetail?->barang?->harga_satuan
                        ?? $return->barang?->harga_satuan
                        ?? 0);
                }

                $history->push([
                    'occurred_at' => $return->created_at?->format('d M Y, H:i') ?? 'Waktu tidak tersedia',
                    'reference' => (string) ($return->pengeluaranUnit?->nomor_bpb ?? 'BPB tidak tersedia'),
                    'type' => 'Pengembalian',
                    'impact' => '+'.self::formatQuantity((float) $return->qty_kembali).' '.$return->satuan
                        .' | HPP Rp '.number_format($hpp, 0, ',', '.'),
                    'status' => 'Disetujui Admin GU',
                    'sort_timestamp' => $return->created_at?->getTimestamp() ?? 0,
                    'sort_id' => (int) $return->getKey(),
                ]);
            });

        $items = $history->all();
        usort($items, static fn (array $first, array $second): int => ($second['sort_timestamp'] <=> $first['sort_timestamp'])
            ?: ($second['sort_id'] <=> $first['sort_id']));

        return collect($items)
            ->take(20)
            ->map(static function (array $item): array {
                unset($item['sort_timestamp'], $item['sort_id']);

                return $item;
            })
            ->values()
            ->all();
    }

    private static function formatQuantity(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 3, ',', '.'), '0'), ',');
    }

    public function resolveActiveGudangForAdmin(User $user, int|string|null $selectedGudangId): ?Gudang
    {
        return $this->unitGudangService->resolveActiveGudang(
            $user,
            $selectedGudangId,
            ['unit_bisnis', 'gudang_sukahati', 'gudang_umum'],
        );
    }

    /**
     * @param  array{status?: ?string, search?: ?string}  $filters
     * @return array{
     *     gudang: Gudang,
     *     gudangStore: Gudang,
     *     permintaans: LengthAwarePaginator,
     *     barangs: EloquentCollection<int, Barang>,
     *     stokStoreTersedia: Collection<int|string, int>,
     *     status: string,
     *     search: string
     * }
     */
    public function getPermintaanPageData(User $user, int|string|null $selectedGudangId, array $filters): array
    {
        $gudang = $this->unitGudangService->resolveActiveGudang(
            $user,
            $selectedGudangId,
            ['gudang_umum', 'gudang_sukahati', 'unit_bisnis'],
        );
        abort_unless($gudang, 403, 'Pilih gudang unit aktif yang ditugaskan kepada Anda.');
        session(['active_gudang_id' => $gudang->getKey()]);

        $gudangStore = $this->resolveStoreGudang();
        $status = $filters['status'] ?? '';
        $search = trim($filters['search'] ?? '');

        $query = PermintaanBarang::query()
            ->where('gudang_peminta_id', $gudang->getKey())
            ->where('gudang_tujuan_id', $gudangStore->getKey())
            ->with([
                'details' => fn ($details) => $details
                    ->with(['barang:id,kode,nama,satuan_id', 'barang.satuan:id,nama', 'satuan:id,nama'])
                    ->orderBy('id'),
            ]);

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where('nomor_pr', 'LIKE', "%{$search}%");
        }

        $permintaans = $query
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $barangs = Barang::query()
            ->forGudang((int) $gudangStore->getKey())
            ->where('is_aktif', true)
            ->with(['kategori:id,nama', 'satuan:id,nama'])
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama', 'kategori_id', 'satuan_id']);
        $stokStoreTersedia = $this->pengeluaranStoreService->availableStock(
            $barangs->modelKeys(),
            (int) $gudangStore->getKey(),
        );

        return compact('gudang', 'gudangStore', 'permintaans', 'barangs', 'stokStoreTersedia', 'status', 'search');
    }

    /**
     * @return Collection<int, array{id: int, kode: string, nama: string, kategori: ?string, satuan: ?string, stok_tersedia: int}>
     */
    public function searchPermintaanBarang(User $user, int|string|null $selectedGudangId, string $search): Collection
    {
        $gudang = $this->unitGudangService->resolveActiveGudang(
            $user,
            $selectedGudangId,
            ['gudang_umum', 'gudang_sukahati', 'unit_bisnis'],
        );
        abort_unless($gudang, 403, 'Pilih gudang unit aktif yang ditugaskan kepada Anda.');

        $gudangStore = $this->resolveStoreGudang();

        return $this->pengeluaranStoreService->searchBarang(
            trim($search),
            (int) $gudangStore->getKey(),
            limit: 10,
        );
    }

    /**
     * @param  array{catatan?: ?string, items: array<int, array{barang_id: int, qty_diminta: int, catatan?: ?string}>}  $validated
     */
    public function createPermintaanBarang(User $user, array $validated): PermintaanBarang
    {
        $gudang = $this->unitGudangService->resolveActiveGudang(
            $user,
            session('active_gudang_id'),
            ['gudang_umum', 'gudang_sukahati', 'unit_bisnis'],
        );
        abort_unless($gudang, 403, 'Pilih gudang unit aktif yang ditugaskan kepada Anda.');

        $gudangStore = $this->resolveStoreGudang();
        $barangIds = collect($validated['items'])->pluck('barang_id')->unique()->values();
        $barangs = Barang::query()
            ->forGudang((int) $gudangStore->getKey())
            ->where('is_aktif', true)
            ->whereIn('id', $barangIds)
            ->get(['id', 'satuan_id'])
            ->keyBy('id');

        if ($barangIds->count() !== count($validated['items']) || $barangs->count() !== $barangIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'Pilih barang aktif yang tersedia pada katalog Gudang Store.',
            ]);
        }

        return DB::transaction(function () use ($validated, $user, $gudang, $gudangStore, $barangs): PermintaanBarang {
            $permintaan = PermintaanBarang::create([
                'nomor_pr' => PermintaanBarang::generateNomorPr((string) ($gudang->kode ?: $gudang->nama)),
                'gudang_peminta_id' => $gudang->getKey(),
                'gudang_tujuan_id' => $gudangStore->getKey(),
                'status' => 'baru',
                'catatan' => $validated['catatan'] ?? null,
                'dibuat_oleh' => $user->getKey(),
            ]);

            foreach ($validated['items'] as $item) {
                PermintaanBarangDetail::create([
                    'permintaan_barang_id' => $permintaan->getKey(),
                    'barang_id' => $item['barang_id'],
                    'qty_diminta' => $item['qty_diminta'],
                    'qty_diproses' => 0,
                    'satuan_id' => $barangs[$item['barang_id']]->satuan_id,
                    'catatan' => $item['catatan'] ?? null,
                ]);
            }

            return $permintaan;
        });
    }

    private function resolveStoreGudang(): Gudang
    {
        $gudangStore = Gudang::query()
            ->where('is_aktif', true)
            ->where('jenis', 'gudang_store')
            ->orderBy('nama')
            ->first();

        abort_unless($gudangStore, 503, 'Gudang Store aktif belum dikonfigurasi.');

        return $gudangStore;
    }

    public function getTransferListing(Gudang $gudang, string $status): LengthAwarePaginator
    {
        return TransferBarang::query()
            ->select([
                'id',
                'nomor_sj',
                'permintaan_barang_id',
                'gudang_asal_id',
                'gudang_tujuan_id',
                'barang_id',
                'qty_diminta',
                'qty_dikirim',
                'qty_diterima',
                'status',
                'supir_pengantar',
                'dikirim_at',
                'diterima_at',
                'created_at',
            ])
            ->with([
                'permintaanBarang:id,nomor_pr',
                'gudangAsal:id,nama',
                'barang:id,kode,nama,satuan_id',
                'barang.satuan:id,nama',
                'penerimaanUnit',
            ])
            ->where('gudang_tujuan_id', $gudang->getKey())
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @return array{gudang: Gudang, transfers: LengthAwarePaginator, status: string}
     */
    public function getTransferPageData(
        User $user,
        int|string|null $selectedGudangId,
        ?string $status,
    ): array {
        $gudang = $this->resolveActiveGudangForAdmin($user, $selectedGudangId);
        abort_if($gudang === null, 403, 'Gudang aktif tidak ditemukan.');
        session(['active_gudang_id' => $gudang->getKey()]);
        $status ??= 'dikirim';

        return [
            'gudang' => $gudang,
            'transfers' => $this->getTransferListing($gudang, $status),
            'status' => $status,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function confirmTransferForUser(
        array $validated,
        TransferBarang $transfer,
        User $user,
    ): void {
        $gudang = $this->unitGudangService->resolveGudangForTransfer($user);
        session(['active_gudang_id' => $gudang->getKey()]);

        $this->confirmTransfer($validated, $transfer, $gudang, (int) $user->getKey());
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function confirmTransfer(
        array $validated,
        TransferBarang $transfer,
        Gudang $gudang,
        int $userId,
    ): void {
        DB::transaction(function () use ($validated, $transfer, $gudang, $userId): void {
            $lockedTransfer = TransferBarang::query()
                ->whereKey($transfer->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless((int) $lockedTransfer->gudang_tujuan_id === (int) $gudang->getKey(), 404);
            if ($lockedTransfer->status !== 'dikirim') {
                throw ValidationException::withMessages([
                    'transfer' => 'Kiriman ini tidak lagi menunggu konfirmasi penerimaan.',
                ]);
            }

            $qtyDiterima = (int) $validated['qty_diterima'];
            $qtyRusak = (int) $validated['qty_rusak'];
            $catatan = trim((string) ($validated['catatan_discrepancy'] ?? ''));

            if ($qtyDiterima > (int) $lockedTransfer->qty_dikirim) {
                throw ValidationException::withMessages([
                    'qty_diterima' => 'Jumlah diterima tidak boleh melebihi jumlah yang dikirim.',
                ]);
            }
            if ($qtyRusak > $qtyDiterima) {
                throw ValidationException::withMessages([
                    'qty_rusak' => 'Jumlah rusak tidak boleh melebihi jumlah yang diterima.',
                ]);
            }
            if (($qtyDiterima < (int) $lockedTransfer->qty_dikirim || $qtyRusak > 0) && $catatan === '') {
                throw ValidationException::withMessages([
                    'catatan_discrepancy' => 'Catatan wajib diisi jika jumlah atau kondisi barang berbeda.',
                ]);
            }

            $receivedAt = now();
            PenerimaanPemindahanUnit::create([
                'transfer_barang_id' => $lockedTransfer->getKey(),
                'gudang_id' => $gudang->getKey(),
                'barang_id' => $lockedTransfer->barang_id,
                'qty_dikirim' => $lockedTransfer->qty_dikirim,
                'qty_diterima' => $qtyDiterima,
                'qty_rusak' => $qtyRusak,
                'catatan_discrepancy' => $catatan !== '' ? $catatan : null,
                'diterima_oleh' => $userId,
                'diterima_at' => $receivedAt,
            ]);

            $lockedTransfer->update([
                'status' => 'diterima',
                'qty_diterima' => $qtyDiterima,
                'catatan_penerimaan' => $catatan !== '' ? $catatan : null,
                'diterima_oleh' => $userId,
                'diterima_at' => $receivedAt,
            ]);

            if ($lockedTransfer->permintaan_barang_id !== null) {
                $this->completeRequestWhenFulfilled((int) $lockedTransfer->permintaan_barang_id);
            }
        });
    }

    private function completeRequestWhenFulfilled(int $requestId): void
    {
        $permintaan = PermintaanBarang::query()
            ->whereKey($requestId)
            ->lockForUpdate()
            ->first();

        if ($permintaan === null || $permintaan->status !== 'diproses') {
            return;
        }

        $hasOpenTransfer = TransferBarang::query()
            ->where('permintaan_barang_id', $requestId)
            ->whereIn('status', ['diminta', 'dikirim'])
            ->exists();
        $hasUnresolvedDiscrepancy = PenerimaanPemindahanUnit::query()
            ->whereHas('transfer', static fn (Builder $query): Builder => $query->where('permintaan_barang_id', $requestId))
            ->where(static function (Builder $query): void {
                $query->whereColumn('qty_diterima', '<', 'qty_dikirim')
                    ->orWhere('qty_rusak', '>', 0);
            })
            ->exists();

        if ($hasOpenTransfer || $hasUnresolvedDiscrepancy) {
            return;
        }

        $requiredByBarang = $permintaan->details()
            ->select('barang_id')
            ->selectRaw('SUM(qty_diminta) AS total')
            ->groupBy('barang_id')
            ->pluck('total', 'barang_id');
        $receivedByBarang = PenerimaanPemindahanUnit::query()
            ->where('gudang_id', $permintaan->gudang_peminta_id)
            ->whereHas('transfer', static fn (Builder $query): Builder => $query->where('permintaan_barang_id', $requestId))
            ->select('barang_id')
            ->selectRaw('SUM(qty_diterima) AS total')
            ->groupBy('barang_id')
            ->pluck('total', 'barang_id');

        foreach ($requiredByBarang as $barangId => $requiredQty) {
            if ((int) ($receivedByBarang[$barangId] ?? 0) < (int) $requiredQty) {
                return;
            }
        }

        $permintaan->update(['status' => 'selesai']);
    }
}
