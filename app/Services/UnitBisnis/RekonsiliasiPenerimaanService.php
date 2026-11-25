<?php

declare(strict_types=1);

namespace App\Services\UnitBisnis;

use App\Models\Gudang;
use App\Models\PenerimaanPemindahanUnit;
use App\Models\PenerimaanUnit;
use App\Models\PenerimaanUnitDetail;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use stdClass;

final class RekonsiliasiPenerimaanService
{
    public function __construct(private readonly UnitGudangService $unitGudangService) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     activeGudang: ?Gudang,
     *     receipts: LengthAwarePaginator,
     *     selectedReceipt: ?stdClass,
     *     workspaceItems: array<int, array{id: int, qty_diperiksa: float, qty_rusak_diperiksa: float, harga_beli: float}>,
     *     receivers: array<int, User>,
     *     filters: array<string, mixed>,
     *     metrics: array{pending_count: int, verified_count: int, verified_today_count: int, follow_up_count: int, month_value: float}
     * }
     */
    public function getPageData(User $user, int|string|null $selectedGudangId, array $filters): array
    {
        $activeGudang = $this->unitGudangService->resolveActiveGudang(
            $user,
            $selectedGudangId,
            ['gudang_umum', 'gudang_sukahati', 'unit_bisnis'],
        );

        if (! $activeGudang instanceof Gudang) {
            return $this->emptyPageData($filters);
        }

        $normalizedFilters = [
            'gudang_id' => $activeGudang->getKey(),
            'status' => $filters['status'] ?? 'menunggu_verifikasi',
            'q' => trim((string) ($filters['q'] ?? '')),
            'sumber_penerimaan' => $filters['sumber_penerimaan'] ?? null,
            'diterima_oleh' => isset($filters['diterima_oleh']) && $filters['diterima_oleh'] !== ''
                ? (int) $filters['diterima_oleh']
                : null,
            'tanggal' => $filters['tanggal'] ?? null,
        ];

        $receipts = $this->filteredReceiptsQuery($activeGudang, $normalizedFilters)
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->orderBy('sumber_penerimaan')
            ->paginate(12)
            ->appends(array_diff_key($filters, ['q' => true]));
        $receipts->setCollection($receipts->getCollection()->map(static function (stdClass $receipt): stdClass {
            $receipt->received_at = Carbon::parse($receipt->received_at);

            return $receipt;
        }));

        $receiverFilters = $normalizedFilters;
        $receiverFilters['diterima_oleh'] = null;
        $receiverIds = $this->filteredReceiptsQuery($activeGudang, $receiverFilters)
            ->whereNotNull('diterima_oleh')
            ->select('diterima_oleh')
            ->distinct();
        $receivers = User::withTrashed()
            ->whereIn('id', $receiverIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->all();

        $selectedReceiptId = $filters['penerimaan_id'] ?? $receipts->first()?->id;
        $selectedReceiptSource = isset($filters['penerimaan_id'])
            ? ($normalizedFilters['sumber_penerimaan'] ?? 'supplier')
            : $receipts->first()?->sumber_penerimaan;
        $selectedReceipt = null;

        if ($selectedReceiptId !== null && $selectedReceiptSource !== null) {
            $selectedReceipt = $this->findSelectedReceipt(
                $activeGudang,
                (int) $selectedReceiptId,
                (string) $selectedReceiptSource,
            );
        }

        return [
            'activeGudang' => $activeGudang,
            'receipts' => $receipts,
            'selectedReceipt' => $selectedReceipt,
            'workspaceItems' => $selectedReceipt instanceof stdClass
                ? $this->mapWorkspaceItems($selectedReceipt)
                : [],
            'receivers' => $receivers,
            'filters' => $normalizedFilters,
            'metrics' => $this->metricsForGudang($activeGudang),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function verify(
        User $user,
        int|string|null $selectedGudangId,
        int $penerimaanId,
        array $validated,
    ): bool {
        $activeGudang = $this->requireActiveGudang($user, $selectedGudangId);

        return match ($validated['sumber_penerimaan']) {
            'permintaan' => $this->verifyTransferReceipt($user, $activeGudang, $penerimaanId, $validated),
            default => $this->verifySupplierReceipt($user, $activeGudang, $penerimaanId, $validated),
        };
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function verifySupplierReceipt(
        User $user,
        Gudang $gudang,
        int $penerimaanId,
        array $validated,
    ): bool {
        return DB::transaction(function () use ($user, $gudang, $penerimaanId, $validated): bool {
            $receipt = PenerimaanUnit::query()
                ->where('gudang_id', $gudang->getKey())
                ->whereKey($penerimaanId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($receipt->status_verifikasi, ['menunggu_verifikasi', 'tercatat'], true)) {
                throw ValidationException::withMessages([
                    'items' => 'Penerimaan ini sudah diproses dan tidak dapat diverifikasi ulang.',
                ]);
            }

            $details = $receipt->details()
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($details->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Penerimaan tidak memiliki rincian barang untuk diverifikasi.',
                ]);
            }

            $submittedItems = collect($validated['items'])->keyBy(
                static fn (array $item): int => (int) $item['detail_id'],
            );

            if ($submittedItems->count() !== $details->count()
                || $details->contains(static fn (PenerimaanUnitDetail $detail): bool => ! $submittedItems->has((int) $detail->getKey()))) {
                throw ValidationException::withMessages([
                    'items' => 'Rincian yang dikirim tidak sesuai dengan barang pada penerimaan ini.',
                ]);
            }

            $hasDiscrepancy = false;
            foreach ($details as $detail) {
                $item = $submittedItems->get((int) $detail->getKey());
                $quantityChecked = round((float) $item['qty_diperiksa'], 3);
                $quantityDamaged = round((float) $item['qty_rusak_diperiksa'], 3);
                $purchasePrice = round((float) $item['harga_beli'], 2);

                if ($quantityDamaged > $quantityChecked) {
                    throw ValidationException::withMessages([
                        'items' => "Jumlah rusak {$detail->nama_barang} tidak boleh melebihi jumlah fisik yang diperiksa.",
                    ]);
                }

                $hasDiscrepancy = $hasDiscrepancy
                    || round((float) $detail->qty_diterima, 3) !== $quantityChecked
                    || round((float) $detail->qty_rusak, 3) !== $quantityDamaged
                    || (float) $detail->qty_kurang > 0
                    || (float) $detail->qty_lebih > 0
                    || $quantityDamaged > 0;
                $goodQuantity = max(0.0, round($quantityChecked - $quantityDamaged, 3));

                $detail->update([
                    'qty_diperiksa' => $quantityChecked,
                    'qty_rusak_diperiksa' => $quantityDamaged,
                    'harga_beli' => $purchasePrice,
                    'subtotal_nilai' => round($goodQuantity * $purchasePrice, 2),
                ]);
            }

            $receipt->update([
                'status_verifikasi' => $hasDiscrepancy ? 'perlu_tindak_lanjut' : 'terverifikasi',
                'diverifikasi_oleh' => $user->getKey(),
                'diverifikasi_at' => now(),
                'catatan_verifikasi' => $validated['catatan_verifikasi'] ?? null,
            ]);

            return $hasDiscrepancy;
        }, attempts: 3);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function verifyTransferReceipt(
        User $user,
        Gudang $gudang,
        int $penerimaanId,
        array $validated,
    ): bool {
        return DB::transaction(function () use ($user, $gudang, $penerimaanId, $validated): bool {
            $receipt = PenerimaanPemindahanUnit::query()
                ->where('gudang_id', $gudang->getKey())
                ->whereKey($penerimaanId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($receipt->status_verifikasi !== 'menunggu_verifikasi') {
                throw ValidationException::withMessages([
                    'items' => 'Penerimaan ini sudah diproses dan tidak dapat diverifikasi ulang.',
                ]);
            }

            $submittedItems = collect($validated['items'])->keyBy(
                static fn (array $item): int => (int) $item['detail_id'],
            );

            if ($submittedItems->count() !== 1 || ! $submittedItems->has((int) $receipt->getKey())) {
                throw ValidationException::withMessages([
                    'items' => 'Rincian yang dikirim tidak sesuai dengan barang pada penerimaan permintaan ini.',
                ]);
            }

            $item = $submittedItems->first();
            $quantityChecked = round((float) $item['qty_diperiksa'], 3);
            $quantityDamaged = round((float) $item['qty_rusak_diperiksa'], 3);

            if ($quantityDamaged > $quantityChecked) {
                throw ValidationException::withMessages([
                    'items' => 'Jumlah rusak tidak boleh melebihi jumlah fisik yang diperiksa.',
                ]);
            }

            $hasDiscrepancy = $quantityChecked !== round((float) $receipt->qty_diterima, 3)
                || $quantityDamaged !== round((float) $receipt->qty_rusak, 3)
                || (float) $receipt->qty_dikirim !== (float) $receipt->qty_diterima
                || $quantityDamaged > 0;

            $receipt->update([
                'qty_diperiksa' => $quantityChecked,
                'qty_rusak_diperiksa' => $quantityDamaged,
                'status_verifikasi' => $hasDiscrepancy ? 'perlu_tindak_lanjut' : 'terverifikasi',
                'diverifikasi_oleh' => $user->getKey(),
                'diverifikasi_at' => now(),
                'catatan_verifikasi' => $validated['catatan_verifikasi'] ?? null,
            ]);

            return $hasDiscrepancy;
        }, attempts: 3);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function recordFollowUp(
        User $user,
        int|string|null $selectedGudangId,
        int $penerimaanId,
        array $validated,
    ): void {
        $activeGudang = $this->requireActiveGudang($user, $selectedGudangId);

        if ($validated['sumber_penerimaan'] === 'permintaan') {
            DB::transaction(function () use ($user, $activeGudang, $penerimaanId, $validated): void {
                $receipt = PenerimaanPemindahanUnit::query()
                    ->where('gudang_id', $activeGudang->getKey())
                    ->whereKey($penerimaanId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($receipt->status_verifikasi !== 'perlu_tindak_lanjut' && ! $this->hasTransferDiscrepancy($receipt)) {
                    throw ValidationException::withMessages([
                        'catatan_tindak_lanjut' => 'Penerimaan ini tidak memiliki tindak lanjut yang masih terbuka.',
                    ]);
                }

                $receipt->update([
                    'status_verifikasi' => 'perlu_tindak_lanjut',
                    'catatan_verifikasi' => $this->appendFollowUpNote(
                        $user,
                        $receipt->catatan_verifikasi,
                        $validated['catatan_tindak_lanjut'],
                    ),
                ]);
            }, attempts: 3);

            return;
        }

        DB::transaction(function () use ($user, $activeGudang, $penerimaanId, $validated): void {
            $receipt = PenerimaanUnit::query()
                ->where('gudang_id', $activeGudang->getKey())
                ->whereKey($penerimaanId)
                ->with(['details' => static fn (HasMany $query): HasMany => $query->orderBy('id')])
                ->lockForUpdate()
                ->firstOrFail();

            if ($receipt->status_verifikasi !== 'perlu_tindak_lanjut' && ! $this->hasDiscrepancy($receipt)) {
                throw ValidationException::withMessages([
                    'catatan_tindak_lanjut' => 'Penerimaan ini tidak memiliki tindak lanjut yang masih terbuka.',
                ]);
            }

            $receipt->update([
                'status_verifikasi' => 'perlu_tindak_lanjut',
                'catatan_verifikasi' => $this->appendFollowUpNote(
                    $user,
                    $receipt->catatan_verifikasi,
                    $validated['catatan_tindak_lanjut'],
                ),
            ]);
        }, attempts: 3);
    }

    private function appendFollowUpNote(User $user, ?string $existingNotes, string $note): string
    {
        $entry = sprintf(
            '[%s · %s] %s',
            now()->format('Y-m-d H:i'),
            $user->name,
            trim($note),
        );
        $existingNotes = trim((string) $existingNotes);

        return $existingNotes === '' ? $entry : $existingNotes."\n".$entry;
    }

    private function requireActiveGudang(User $user, int|string|null $selectedGudangId): Gudang
    {
        $activeGudang = $this->unitGudangService->resolveActiveGudang(
            $user,
            $selectedGudangId,
            ['gudang_umum', 'gudang_sukahati', 'unit_bisnis'],
        );

        abort_unless($activeGudang instanceof Gudang, 403, 'Akun ini belum memiliki gudang aktif.');

        return $activeGudang;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filteredReceiptsQuery(Gudang $gudang, array $filters): QueryBuilder
    {
        $supplierReceipts = $this->supplierReceiptsQuery($gudang, $filters);
        $requestReceipts = $this->requestReceiptsQuery($gudang, $filters);
        $supplierReceipts->unionAll($requestReceipts);

        return DB::query()->fromSub($supplierReceipts, 'receipts')->select('receipts.*');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function supplierReceiptsQuery(Gudang $gudang, array $filters): QueryBuilder
    {
        $query = DB::table('penerimaan_units as receipts')
            ->where('receipts.gudang_id', $gudang->getKey())
            ->selectRaw(
                "'supplier' AS sumber_penerimaan, receipts.id, receipts.nomor_rcv, receipts.nama_supplier, ".
                'receipts.po_referensi, receipts.status_verifikasi, receipts.diterima_oleh, receipts.diverifikasi_at, '.
                '(SELECT COUNT(*) FROM penerimaan_unit_details AS details '.
                'WHERE details.penerimaan_unit_id = receipts.id) AS details_count, '.
                "CAST(receipts.tanggal_terima AS TEXT) || ' ' || CAST(receipts.waktu_terima AS TEXT) AS received_at",
            );

        match ($filters['status']) {
            'sudah_diverifikasi' => $query
                ->whereIn('receipts.status_verifikasi', ['terverifikasi', 'sesuai'])
                ->whereNotExists($this->supplierDiscrepancyQuery()),
            'tindak_lanjut' => $query->where(static function (QueryBuilder $query): void {
                $query->where('receipts.status_verifikasi', 'perlu_tindak_lanjut')
                    ->orWhere(static function (QueryBuilder $verified): void {
                        $verified->whereIn('receipts.status_verifikasi', ['terverifikasi', 'sesuai'])
                            ->whereExists(self::supplierDiscrepancyQuery());
                    });
            }),
            'semua' => null,
            default => $query->whereIn('receipts.status_verifikasi', ['menunggu_verifikasi', 'tercatat']),
        };

        if ($filters['diterima_oleh'] !== null) {
            $query->where('receipts.diterima_oleh', $filters['diterima_oleh']);
        }

        if ($filters['tanggal'] !== null) {
            $query->where('receipts.tanggal_terima', $filters['tanggal']);
        }

        if ($filters['q'] !== '') {
            $search = '%'.mb_strtolower($filters['q']).'%';
            $query->where(static function (QueryBuilder $query) use ($search): void {
                $query->whereRaw('LOWER(receipts.nomor_rcv) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(receipts.nama_supplier) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(receipts.po_referensi, \'\')) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(receipts.nomor_surat_jalan, \'\')) LIKE ?', [$search]);
            });
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function requestReceiptsQuery(Gudang $gudang, array $filters): QueryBuilder
    {
        $query = DB::table('penerimaan_pemindahan_units as receipts')
            ->join('transfer_barangs as transfers', 'transfers.id', '=', 'receipts.transfer_barang_id')
            ->leftJoin('permintaan_barangs', 'permintaan_barangs.id', '=', 'transfers.permintaan_barang_id')
            ->leftJoin('gudangs as source_warehouses', 'source_warehouses.id', '=', 'transfers.gudang_asal_id')
            ->leftJoin('barangs', 'barangs.id', '=', 'receipts.barang_id')
            ->where('receipts.gudang_id', $gudang->getKey())
            ->selectRaw(
                "'permintaan' AS sumber_penerimaan, receipts.id, transfers.nomor_sj AS nomor_rcv, ".
                "COALESCE(source_warehouses.nama, 'Gudang Store') AS nama_supplier, ".
                "COALESCE(permintaan_barangs.nomor_pr, '') AS po_referensi, receipts.status_verifikasi, ".
                'receipts.diterima_oleh, receipts.diverifikasi_at, 1 AS details_count, '.
                'CAST(receipts.diterima_at AS TEXT) AS received_at',
            );

        match ($filters['status']) {
            'sudah_diverifikasi' => $query
                ->whereIn('receipts.status_verifikasi', ['terverifikasi', 'sesuai'])
                ->where(static function (QueryBuilder $query): void {
                    $query->whereColumn('receipts.qty_dikirim', '=', 'receipts.qty_diterima')
                        ->whereNotNull('receipts.qty_diperiksa')
                        ->whereColumn('receipts.qty_diperiksa', '=', 'receipts.qty_diterima')
                        ->whereColumn('receipts.qty_rusak_diperiksa', '=', 'receipts.qty_rusak')
                        ->where('receipts.qty_rusak', 0);
                }),
            'tindak_lanjut' => $query->where(static function (QueryBuilder $query): void {
                $query->where('receipts.status_verifikasi', 'perlu_tindak_lanjut')
                    ->orWhere(static function (QueryBuilder $verified): void {
                        $verified->whereIn('receipts.status_verifikasi', ['terverifikasi', 'sesuai'])
                            ->where(static function (QueryBuilder $discrepancy): void {
                                $discrepancy->whereColumn('receipts.qty_dikirim', '<>', 'receipts.qty_diterima')
                                    ->orWhereColumn('receipts.qty_diperiksa', '<>', 'receipts.qty_diterima')
                                    ->orWhereColumn('receipts.qty_rusak_diperiksa', '<>', 'receipts.qty_rusak')
                                    ->orWhere('receipts.qty_rusak', '>', 0);
                            });
                    });
            }),
            'semua' => null,
            default => $query->where('receipts.status_verifikasi', 'menunggu_verifikasi'),
        };

        if ($filters['diterima_oleh'] !== null) {
            $query->where('receipts.diterima_oleh', $filters['diterima_oleh']);
        }

        if ($filters['tanggal'] !== null) {
            $dateStart = Carbon::parse($filters['tanggal'])->startOfDay();
            $query->where('receipts.diterima_at', '>=', $dateStart)
                ->where('receipts.diterima_at', '<', $dateStart->copy()->addDay());
        }

        if ($filters['q'] !== '') {
            $search = '%'.mb_strtolower($filters['q']).'%';
            $query->where(static function (QueryBuilder $query) use ($search): void {
                $query->whereRaw('LOWER(transfers.nomor_sj) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(permintaan_barangs.nomor_pr, \'\')) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(barangs.kode, \'\')) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(COALESCE(barangs.nama, \'\')) LIKE ?', [$search]);
            });
        }

        return $query;
    }

    private static function supplierDiscrepancyQuery(): QueryBuilder
    {
        return DB::table('penerimaan_unit_details as details')
            ->selectRaw('1')
            ->whereColumn('details.penerimaan_unit_id', 'receipts.id')
            ->where(static function (QueryBuilder $query): void {
                $query->where(static function (QueryBuilder $query): void {
                    $query->whereNotNull('details.qty_diperiksa')
                        ->whereColumn('details.qty_diperiksa', '<>', 'details.qty_diterima');
                })->orWhere(static function (QueryBuilder $query): void {
                    $query->whereNotNull('details.qty_rusak_diperiksa')
                        ->whereColumn('details.qty_rusak_diperiksa', '<>', 'details.qty_rusak');
                })->orWhere('details.qty_rusak', '>', 0)
                    ->orWhere('details.qty_rusak_diperiksa', '>', 0)
                    ->orWhere('details.qty_kurang', '>', 0)
                    ->orWhere('details.qty_lebih', '>', 0);
            });
    }

    private function findSelectedReceipt(Gudang $gudang, int $receiptId, string $source): stdClass
    {
        if ($source === 'permintaan') {
            $receipt = PenerimaanPemindahanUnit::query()
                ->where('gudang_id', $gudang->getKey())
                ->with([
                    'transfer.permintaanBarang',
                    'transfer.gudangAsal',
                    'barang' => static function (BelongsTo $query): void {
                        $query->withTrashed();
                    },
                    'barang.kategori',
                    'barang.satuan',
                    'penerima:id,name',
                    'verifier:id,name',
                ])
                ->find($receiptId);

            abort_unless($receipt instanceof PenerimaanPemindahanUnit, 404);

            $transfer = $receipt->transfer;
            $barang = $receipt->barang;
            $detail = (object) [
                'id' => (int) $receipt->getKey(),
                'kode_barang' => $barang?->kode,
                'nama_barang' => $barang?->nama ?? 'Barang tidak tersedia',
                'kategori' => $barang?->kategori?->nama,
                'satuan' => $barang?->satuan?->nama ?? 'unit',
                'qty_diterima' => (float) $receipt->qty_diterima,
                'qty_rusak' => (float) $receipt->qty_rusak,
                'qty_diperiksa' => $receipt->qty_diperiksa,
                'qty_rusak_diperiksa' => $receipt->qty_rusak_diperiksa,
                'harga_beli' => 0.0,
                'subtotal_nilai' => 0.0,
                'catatan' => $receipt->catatan_discrepancy,
            ];

            return (object) [
                'id' => (int) $receipt->getKey(),
                'sumber_penerimaan' => 'permintaan',
                'nomor_rcv' => $transfer?->nomor_sj ?? '—',
                'nama_supplier' => $transfer?->gudangAsal?->nama ?? 'Gudang Store',
                'po_referensi' => $transfer?->permintaanBarang?->nomor_pr,
                'nomor_surat_jalan' => $transfer?->nomor_sj,
                'tanggal_terima' => $receipt->diterima_at,
                'waktu_terima' => $receipt->diterima_at?->format('H:i:s'),
                'has_surat_jalan' => true,
                'alasan_tanpa_sj' => null,
                'status_verifikasi' => $receipt->status_verifikasi,
                'catatan' => $receipt->catatan_discrepancy,
                'catatan_verifikasi' => $receipt->catatan_verifikasi,
                'diverifikasi_at' => $receipt->diverifikasi_at,
                'penerima' => $receipt->penerima,
                'verifier' => $receipt->verifier,
                'details' => collect([$detail]),
            ];
        }

        $receipt = PenerimaanUnit::query()
            ->where('gudang_id', $gudang->getKey())
            ->with([
                'details' => static fn (HasMany $query): HasMany => $query->orderBy('id'),
                'penerima:id,name',
                'verifier:id,name',
            ])
            ->find($receiptId);

        abort_unless($receipt instanceof PenerimaanUnit, 404);

        return (object) [
            'id' => (int) $receipt->getKey(),
            'sumber_penerimaan' => 'supplier',
            'nomor_rcv' => $receipt->nomor_rcv,
            'nama_supplier' => $receipt->nama_supplier,
            'po_referensi' => $receipt->po_referensi,
            'nomor_surat_jalan' => $receipt->nomor_surat_jalan,
            'tanggal_terima' => $receipt->tanggal_terima,
            'waktu_terima' => $receipt->waktu_terima,
            'has_surat_jalan' => $receipt->has_surat_jalan,
            'alasan_tanpa_sj' => $receipt->alasan_tanpa_sj,
            'status_verifikasi' => $receipt->status_verifikasi,
            'catatan' => $receipt->catatan,
            'catatan_verifikasi' => $receipt->catatan_verifikasi,
            'diverifikasi_at' => $receipt->diverifikasi_at,
            'penerima' => $receipt->penerima,
            'verifier' => $receipt->verifier,
            'details' => $receipt->details->map(static fn (PenerimaanUnitDetail $detail): stdClass => (object) [
                'id' => (int) $detail->getKey(),
                'kode_barang' => $detail->kode_barang,
                'nama_barang' => $detail->nama_barang,
                'kategori' => $detail->kategori,
                'satuan' => $detail->satuan,
                'qty_diterima' => $detail->qty_diterima,
                'qty_rusak' => $detail->qty_rusak,
                'qty_diperiksa' => $detail->qty_diperiksa,
                'qty_rusak_diperiksa' => $detail->qty_rusak_diperiksa,
                'harga_beli' => $detail->harga_beli,
                'subtotal_nilai' => $detail->subtotal_nilai,
                'catatan' => $detail->catatan,
            ]),
        ];
    }

    /**
     * @return array<int, array{id: int, qty_diperiksa: float, qty_rusak_diperiksa: float, harga_beli: float}>
     */
    private function mapWorkspaceItems(stdClass $receipt): array
    {
        return $receipt->details
            ->map(static fn (stdClass $detail): array => [
                'id' => $detail->id,
                'qty_diperiksa' => (float) ($detail->qty_diperiksa ?? $detail->qty_diterima),
                'qty_rusak_diperiksa' => (float) ($detail->qty_rusak_diperiksa ?? $detail->qty_rusak),
                'harga_beli' => (float) $detail->harga_beli,
            ])
            ->all();
    }

    private function hasDiscrepancy(PenerimaanUnit $receipt): bool
    {
        return $receipt->details->contains(static function (PenerimaanUnitDetail $detail): bool {
            $checkedQuantity = $detail->qty_diperiksa === null
                ? (float) $detail->qty_diterima
                : (float) $detail->qty_diperiksa;
            $checkedDamaged = $detail->qty_rusak_diperiksa === null
                ? (float) $detail->qty_rusak
                : (float) $detail->qty_rusak_diperiksa;

            return round((float) $detail->qty_diterima, 3) !== round($checkedQuantity, 3)
                || round((float) $detail->qty_rusak, 3) !== round($checkedDamaged, 3)
                || (float) $detail->qty_kurang > 0
                || (float) $detail->qty_lebih > 0
                || $checkedDamaged > 0;
        });
    }

    private function hasTransferDiscrepancy(PenerimaanPemindahanUnit $receipt): bool
    {
        $checkedQuantity = $receipt->qty_diperiksa === null
            ? (float) $receipt->qty_diterima
            : (float) $receipt->qty_diperiksa;
        $checkedDamaged = $receipt->qty_rusak_diperiksa === null
            ? (float) $receipt->qty_rusak
            : (float) $receipt->qty_rusak_diperiksa;

        return round($checkedQuantity, 3) !== round((float) $receipt->qty_diterima, 3)
            || round($checkedDamaged, 3) !== round((float) $receipt->qty_rusak, 3)
            || (float) $receipt->qty_dikirim !== (float) $receipt->qty_diterima
            || $checkedDamaged > 0;
    }

    /**
     * @return array{pending_count: int, verified_count: int, verified_today_count: int, follow_up_count: int, month_value: float}
     */
    private function metricsForGudang(Gudang $gudang): array
    {
        $today = today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $receiptMetrics = DB::query()
            ->fromSub(
                $this->supplierReceiptMetricsQuery($gudang, $today)
                    ->unionAll($this->requestReceiptMetricsQuery($gudang, $today)),
                'source_metrics',
            )
            ->selectRaw(
                'SUM(pending_count) AS pending_count, '.
                'SUM(verified_count) AS verified_count, '.
                'SUM(verified_today_count) AS verified_today_count, '.
                'SUM(follow_up_count) AS follow_up_count',
            )
            ->first();

        return [
            'pending_count' => (int) $receiptMetrics->pending_count,
            'verified_count' => (int) $receiptMetrics->verified_count,
            'verified_today_count' => (int) $receiptMetrics->verified_today_count,
            'follow_up_count' => (int) $receiptMetrics->follow_up_count,
            'month_value' => (float) PenerimaanUnitDetail::query()
                ->whereHas('penerimaanUnit', static fn (Builder $query): Builder => $query
                    ->where('gudang_id', $gudang->getKey())
                    ->whereIn('status_verifikasi', ['terverifikasi', 'perlu_tindak_lanjut', 'sesuai'])
                    ->whereBetween('tanggal_terima', [$monthStart, $monthEnd]))
                ->sum('subtotal_nilai'),
        ];
    }

    private function supplierReceiptMetricsQuery(Gudang $gudang, Carbon $today): QueryBuilder
    {
        $receipts = DB::table('penerimaan_units as receipts')
            ->where('receipts.gudang_id', $gudang->getKey())
            ->select([
                'receipts.status_verifikasi',
                'receipts.diverifikasi_at',
            ])
            ->selectSub(self::supplierDiscrepancyQuery(), 'has_discrepancy');

        return DB::query()
            ->fromSub($receipts, 'receipts')
            ->selectRaw(
                "COUNT(CASE WHEN status_verifikasi IN ('menunggu_verifikasi', 'tercatat') THEN 1 END) AS pending_count, ".
                "COUNT(CASE WHEN status_verifikasi IN ('terverifikasi', 'sesuai') AND has_discrepancy IS NULL THEN 1 END) AS verified_count, ".
                "COUNT(CASE WHEN status_verifikasi = 'perlu_tindak_lanjut' OR ".
                    "(status_verifikasi IN ('terverifikasi', 'sesuai') AND has_discrepancy IS NOT NULL) ".
                    'THEN 1 END) AS follow_up_count, '.
                "COUNT(CASE WHEN status_verifikasi IN ('terverifikasi', 'perlu_tindak_lanjut', 'sesuai') ".
                    'AND DATE(diverifikasi_at) = ? THEN 1 END) AS verified_today_count',
                [$today->toDateString()],
            );
    }

    private function requestReceiptMetricsQuery(Gudang $gudang, Carbon $today): QueryBuilder
    {
        return DB::table('penerimaan_pemindahan_units as receipts')
            ->where('receipts.gudang_id', $gudang->getKey())
            ->selectRaw(
                "COUNT(CASE WHEN receipts.status_verifikasi = 'menunggu_verifikasi' THEN 1 END) AS pending_count, ".
                "COUNT(CASE WHEN receipts.status_verifikasi IN ('terverifikasi', 'sesuai') ".
                    'AND receipts.qty_dikirim = receipts.qty_diterima '.
                    'AND receipts.qty_diperiksa IS NOT NULL '.
                    'AND receipts.qty_diperiksa = receipts.qty_diterima '.
                    'AND receipts.qty_rusak_diperiksa = receipts.qty_rusak '.
                    'AND receipts.qty_rusak = 0 THEN 1 END) AS verified_count, '.
                "COUNT(CASE WHEN receipts.status_verifikasi = 'perlu_tindak_lanjut' OR ".
                    "(receipts.status_verifikasi IN ('terverifikasi', 'sesuai') AND (".
                    'receipts.qty_dikirim <> receipts.qty_diterima '.
                    'OR receipts.qty_diperiksa <> receipts.qty_diterima '.
                    'OR receipts.qty_rusak_diperiksa <> receipts.qty_rusak '.
                    'OR receipts.qty_rusak > 0)) THEN 1 END) AS follow_up_count, '.
                "COUNT(CASE WHEN receipts.status_verifikasi IN ('terverifikasi', 'perlu_tindak_lanjut', 'sesuai') ".
                    'AND DATE(receipts.diverifikasi_at) = ? THEN 1 END) AS verified_today_count',
                [$today->toDateString()],
            );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *     activeGudang: null,
     *     receipts: LengthAwarePaginator,
     *     selectedReceipt: null,
     *     workspaceItems: array<never, never>,
     *     receivers: array<never, never>,
     *     filters: array<string, mixed>,
     *     metrics: array{pending_count: int, verified_count: int, verified_today_count: int, follow_up_count: int, month_value: float}
     * }
     */
    private function emptyPageData(array $filters): array
    {
        return [
            'activeGudang' => null,
            'receipts' => new Paginator([], 0, 12, 1, ['path' => request()->url()]),
            'selectedReceipt' => null,
            'workspaceItems' => [],
            'receivers' => [],
            'filters' => [
                'gudang_id' => $filters['gudang_id'] ?? null,
                'status' => $filters['status'] ?? 'menunggu_verifikasi',
                'sumber_penerimaan' => $filters['sumber_penerimaan'] ?? null,
                'q' => trim((string) ($filters['q'] ?? '')),
                'diterima_oleh' => isset($filters['diterima_oleh']) && $filters['diterima_oleh'] !== ''
                    ? (int) $filters['diterima_oleh']
                    : null,
                'tanggal' => $filters['tanggal'] ?? null,
            ],
            'metrics' => [
                'pending_count' => 0,
                'verified_count' => 0,
                'verified_today_count' => 0,
                'follow_up_count' => 0,
                'month_value' => 0.0,
            ],
        ];
    }
}
