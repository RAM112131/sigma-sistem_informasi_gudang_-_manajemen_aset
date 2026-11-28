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
use Illuminate\Support\Collection;
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

        $receipts = $this->paginateGroupedReceipts($activeGudang, $normalizedFilters, $filters);

        $receiverFilters = $normalizedFilters;
        $receiverFilters['diterima_oleh'] = null;
        $receiverIds = $this->collectReceiverIds($activeGudang, $receiverFilters);
        $receivers = $receiverIds === []
            ? []
            : User::withTrashed()
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
            $representative = PenerimaanPemindahanUnit::query()
                ->where('gudang_id', $gudang->getKey())
                ->with('transfer:id,nomor_sj')
                ->whereKey($penerimaanId)
                ->lockForUpdate()
                ->firstOrFail();

            $baseSj = $this->baseSuratJalan($representative->transfer?->nomor_sj);
            $receipts = $this->findTransferGroupReceipts($gudang, $baseSj, lockForUpdate: true);

            if ($receipts->isEmpty()) {
                $receipts = collect([$representative]);
            }

            if ($receipts->contains(static fn (PenerimaanPemindahanUnit $r): bool => $r->status_verifikasi !== 'menunggu_verifikasi')) {
                throw ValidationException::withMessages([
                    'items' => 'Penerimaan ini sudah diproses dan tidak dapat diverifikasi ulang.',
                ]);
            }

            $submittedItems = collect($validated['items'])->keyBy(
                static fn (array $item): int => (int) $item['detail_id'],
            );

            if ($submittedItems->count() !== $receipts->count()
                || $receipts->contains(static fn (PenerimaanPemindahanUnit $r): bool => ! $submittedItems->has((int) $r->getKey()))) {
                throw ValidationException::withMessages([
                    'items' => 'Rincian yang dikirim tidak sesuai dengan barang pada surat jalan ini.',
                ]);
            }

            $hasDiscrepancy = false;
            $verifiedAt = now();
            $rowsToUpdate = [];

            foreach ($receipts as $receipt) {
                $item = $submittedItems->get((int) $receipt->getKey());
                $quantityChecked = round((float) $item['qty_diperiksa'], 3);
                $quantityDamaged = round((float) $item['qty_rusak_diperiksa'], 3);

                if ($quantityDamaged > $quantityChecked) {
                    throw ValidationException::withMessages([
                        'items' => 'Jumlah rusak tidak boleh melebihi jumlah fisik yang diperiksa.',
                    ]);
                }

                $itemDiscrepancy = $quantityChecked !== round((float) $receipt->qty_diterima, 3)
                    || $quantityDamaged !== round((float) $receipt->qty_rusak, 3)
                    || (float) $receipt->qty_dikirim !== (float) $receipt->qty_diterima
                    || $quantityDamaged > 0;
                $hasDiscrepancy = $hasDiscrepancy || $itemDiscrepancy;

                $rowsToUpdate[] = [
                    'receipt' => $receipt,
                    'qty_diperiksa' => $quantityChecked,
                    'qty_rusak_diperiksa' => $quantityDamaged,
                ];
            }

            $finalStatus = $hasDiscrepancy ? 'perlu_tindak_lanjut' : 'terverifikasi';

            foreach ($rowsToUpdate as $entry) {
                $entry['receipt']->update([
                    'qty_diperiksa' => $entry['qty_diperiksa'],
                    'qty_rusak_diperiksa' => $entry['qty_rusak_diperiksa'],
                    'status_verifikasi' => $finalStatus,
                    'diverifikasi_oleh' => $user->getKey(),
                    'diverifikasi_at' => $verifiedAt,
                    'catatan_verifikasi' => $validated['catatan_verifikasi'] ?? null,
                ]);
            }

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
                $representative = PenerimaanPemindahanUnit::query()
                    ->where('gudang_id', $activeGudang->getKey())
                    ->with('transfer:id,nomor_sj')
                    ->whereKey($penerimaanId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $baseSj = $this->baseSuratJalan($representative->transfer?->nomor_sj);
                $receipts = $this->findTransferGroupReceipts($activeGudang, $baseSj, lockForUpdate: true);
                if ($receipts->isEmpty()) {
                    $receipts = collect([$representative]);
                }

                $hasOpenFollowUp = $receipts->contains(
                    fn (PenerimaanPemindahanUnit $r): bool => $r->status_verifikasi === 'perlu_tindak_lanjut' || $this->hasTransferDiscrepancy($r)
                );

                if (! $hasOpenFollowUp) {
                    throw ValidationException::withMessages([
                        'catatan_tindak_lanjut' => 'Penerimaan ini tidak memiliki tindak lanjut yang masih terbuka.',
                    ]);
                }

                $updatedNote = $this->appendFollowUpNote(
                    $user,
                    $representative->catatan_verifikasi,
                    $validated['catatan_tindak_lanjut'],
                );

                foreach ($receipts as $receipt) {
                    $receipt->update([
                        'status_verifikasi' => 'perlu_tindak_lanjut',
                        'catatan_verifikasi' => $updatedNote,
                    ]);
                }
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
     * @param  array<string, mixed>  $normalizedFilters
     * @param  array<string, mixed>  $rawFilters
     */
    private function paginateGroupedReceipts(
        Gudang $gudang,
        array $normalizedFilters,
        array $rawFilters,
    ): LengthAwarePaginator {
        $allReceipts = $this->buildGroupedReceipts($gudang, $normalizedFilters);
        $perPage = 12;
        $currentPage = Paginator::resolveCurrentPage();
        $total = $allReceipts->count();
        $slice = $allReceipts->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return (new Paginator(
            $slice,
            $total,
            $perPage,
            $currentPage,
            ['path' => request()->url()],
        ))->appends(array_diff_key($rawFilters, ['q' => true]));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<int>
     */
    private function collectReceiverIds(Gudang $gudang, array $filters): array
    {
        return $this->buildGroupedReceipts($gudang, $filters)
            ->flatMap(static fn (stdClass $receipt): array => $receipt->receiver_ids ?? [$receipt->diterima_oleh])
            ->filter(static fn (mixed $id): bool => $id !== null)
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, stdClass>
     */
    private function buildGroupedReceipts(Gudang $gudang, array $filters): Collection
    {
        $supplierReceipts = $this->supplierReceiptsQuery($gudang, $filters)
            ->get()
            ->map(static function (stdClass $receipt): stdClass {
                $receipt->received_at = Carbon::parse($receipt->received_at);
                $receipt->receiver_ids = $receipt->diterima_oleh !== null ? [(int) $receipt->diterima_oleh] : [];

                return $receipt;
            });

        $transferReceipts = $this->buildGroupedTransferReceipts($gudang, $filters);

        return $supplierReceipts
            ->concat($transferReceipts)
            ->sort(static function (stdClass $a, stdClass $b): int {
                $timeCmp = $b->received_at->getTimestamp() <=> $a->received_at->getTimestamp();
                if ($timeCmp !== 0) {
                    return $timeCmp;
                }

                $idCmp = (int) $b->id <=> (int) $a->id;
                if ($idCmp !== 0) {
                    return $idCmp;
                }

                return strcmp((string) $a->sumber_penerimaan, (string) $b->sumber_penerimaan);
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, stdClass>
     */
    private function buildGroupedTransferReceipts(Gudang $gudang, array $filters): Collection
    {
        $rows = DB::table('penerimaan_pemindahan_units as receipts')
            ->join('transfer_barangs as transfers', 'transfers.id', '=', 'receipts.transfer_barang_id')
            ->leftJoin('permintaan_barangs', 'permintaan_barangs.id', '=', 'transfers.permintaan_barang_id')
            ->leftJoin('gudangs as source_warehouses', 'source_warehouses.id', '=', 'transfers.gudang_asal_id')
            ->leftJoin('barangs', 'barangs.id', '=', 'receipts.barang_id')
            ->where('receipts.gudang_id', $gudang->getKey())
            ->orderBy('transfers.nomor_sj')
            ->orderBy('receipts.id')
            ->get([
                'receipts.id',
                'receipts.status_verifikasi',
                'receipts.diterima_oleh',
                'receipts.diverifikasi_at',
                'receipts.diterima_at',
                'receipts.qty_dikirim',
                'receipts.qty_diterima',
                'receipts.qty_rusak',
                'receipts.qty_diperiksa',
                'receipts.qty_rusak_diperiksa',
                'transfers.nomor_sj',
                DB::raw("COALESCE(source_warehouses.nama, 'Gudang Store') AS nama_supplier"),
                DB::raw("COALESCE(permintaan_barangs.nomor_pr, '') AS po_referensi"),
                DB::raw("COALESCE(barangs.kode, '') AS barang_kode"),
                DB::raw("COALESCE(barangs.nama, '') AS barang_nama"),
            ]);

        if ($rows->isEmpty()) {
            return collect();
        }

        $grouped = $rows->groupBy(fn (stdClass $row): string => $this->baseSuratJalan($row->nomor_sj));
        $results = collect();

        foreach ($grouped as $baseSj => $items) {
            $groupStatus = $this->resolveTransferGroupStatus($items);
            $hasDiscrepancy = $items->contains(fn (stdClass $item): bool => $this->rawTransferItemHasDiscrepancy($item));

            $matchesStatus = match ($filters['status']) {
                'sudah_diverifikasi' => in_array($groupStatus, ['terverifikasi', 'sesuai'], true) && ! $hasDiscrepancy,
                'tindak_lanjut' => $groupStatus === 'perlu_tindak_lanjut'
                    || (in_array($groupStatus, ['terverifikasi', 'sesuai'], true) && $hasDiscrepancy),
                'semua' => true,
                default => $groupStatus === 'menunggu_verifikasi',
            };

            if (! $matchesStatus) {
                continue;
            }

            $receiverIds = $items->pluck('diterima_oleh')
                ->filter(static fn (mixed $id): bool => $id !== null)
                ->map(static fn (mixed $id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            if ($filters['diterima_oleh'] !== null && ! in_array((int) $filters['diterima_oleh'], $receiverIds, true)) {
                continue;
            }

            $latestReceivedRaw = $items->max('diterima_at');
            $receivedAt = Carbon::parse($latestReceivedRaw);

            if ($filters['tanggal'] !== null) {
                $targetDate = Carbon::parse($filters['tanggal'])->toDateString();
                if (! $items->contains(static fn (stdClass $item): bool => Carbon::parse($item->diterima_at)->toDateString() === $targetDate)) {
                    continue;
                }
            }

            if ($filters['q'] !== '') {
                $needle = mb_strtolower($filters['q']);
                $haystackMatches = str_contains(mb_strtolower($baseSj), $needle)
                    || $items->contains(static function (stdClass $item) use ($needle): bool {
                        return str_contains(mb_strtolower((string) $item->nomor_sj), $needle)
                            || str_contains(mb_strtolower((string) $item->po_referensi), $needle)
                            || str_contains(mb_strtolower((string) $item->barang_kode), $needle)
                            || str_contains(mb_strtolower((string) $item->barang_nama), $needle);
                    });

                if (! $haystackMatches) {
                    continue;
                }
            }

            $first = $items->first();
            $latestVerifiedAt = $items->pluck('diverifikasi_at')->filter()->max();

            $results->push((object) [
                'sumber_penerimaan' => 'permintaan',
                'id' => (int) $items->min('id'),
                'nomor_rcv' => $baseSj,
                'nama_supplier' => $first->nama_supplier,
                'po_referensi' => $first->po_referensi,
                'status_verifikasi' => $groupStatus,
                'diterima_oleh' => $receiverIds[0] ?? null,
                'receiver_ids' => $receiverIds,
                'diverifikasi_at' => $latestVerifiedAt,
                'details_count' => $items->count(),
                'received_at' => $receivedAt,
            ]);
        }

        return $results;
    }

    /**
     * @return Collection<int, PenerimaanPemindahanUnit>
     */
    private function findTransferGroupReceipts(Gudang $gudang, string $baseSj, bool $lockForUpdate = false): Collection
    {
        $query = PenerimaanPemindahanUnit::query()
            ->where('gudang_id', $gudang->getKey())
            ->whereHas('transfer', static function (Builder $q) use ($baseSj): void {
                $q->where('nomor_sj', $baseSj)
                    ->orWhere('nomor_sj', 'like', $baseSj.'-%');
            })
            ->with([
                'transfer.permintaanBarang',
                'transfer.gudangAsal',
                'barang' => static function (BelongsTo $q): void {
                    $q->withTrashed();
                },
                'barang.kategori',
                'barang.satuan',
                'penerima:id,name',
                'verifier:id,name',
            ])
            ->orderBy('id');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->get()
            ->filter(fn (PenerimaanPemindahanUnit $item): bool => $this->baseSuratJalan($item->transfer?->nomor_sj) === $baseSj)
            ->values();
    }

    private function baseSuratJalan(?string $nomorSj): string
    {
        $trimmed = trim((string) $nomorSj);
        if ($trimmed === '') {
            return '—';
        }

        return (string) preg_replace('/^(.+-\d{4})-\d{2,}$/', '$1', $trimmed);
    }

    /**
     * @param  Collection<int, mixed>  $items
     */
    private function resolveTransferGroupStatus(Collection $items): string
    {
        if ($items->contains(static fn (mixed $item): bool => $item->status_verifikasi === 'menunggu_verifikasi')) {
            return 'menunggu_verifikasi';
        }

        if ($items->contains(fn (mixed $item): bool => $item->status_verifikasi === 'perlu_tindak_lanjut'
            || ($item instanceof PenerimaanPemindahanUnit
                ? $this->hasTransferDiscrepancy($item)
                : $this->rawTransferItemHasDiscrepancy($item)))) {
            return 'perlu_tindak_lanjut';
        }

        return 'terverifikasi';
    }

    private function rawTransferItemHasDiscrepancy(stdClass $item): bool
    {
        $checkedQuantity = $item->qty_diperiksa === null
            ? (float) $item->qty_diterima
            : (float) $item->qty_diperiksa;
        $checkedDamaged = $item->qty_rusak_diperiksa === null
            ? (float) $item->qty_rusak
            : (float) $item->qty_rusak_diperiksa;

        return round($checkedQuantity, 3) !== round((float) $item->qty_diterima, 3)
            || round($checkedDamaged, 3) !== round((float) $item->qty_rusak, 3)
            || (float) $item->qty_dikirim !== (float) $item->qty_diterima
            || $checkedDamaged > 0;
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
            $representative = PenerimaanPemindahanUnit::query()
                ->where('gudang_id', $gudang->getKey())
                ->with('transfer:id,nomor_sj')
                ->find($receiptId);

            abort_unless($representative instanceof PenerimaanPemindahanUnit, 404);

            $baseSj = $this->baseSuratJalan($representative->transfer?->nomor_sj);
            $groupReceipts = $this->findTransferGroupReceipts($gudang, $baseSj);
            if ($groupReceipts->isEmpty()) {
                $groupReceipts = collect([$representative]);
            }

            $first = $groupReceipts->first();
            $transfer = $first->transfer;
            $details = $groupReceipts->map(static function (PenerimaanPemindahanUnit $item): stdClass {
                $barang = $item->barang;

                return (object) [
                    'id' => (int) $item->getKey(),
                    'kode_barang' => $barang?->kode,
                    'nama_barang' => $barang?->nama ?? 'Barang tidak tersedia',
                    'kategori' => $barang?->kategori?->nama,
                    'satuan' => $barang?->satuan?->nama ?? 'unit',
                    'qty_diterima' => (float) $item->qty_diterima,
                    'qty_rusak' => (float) $item->qty_rusak,
                    'qty_diperiksa' => $item->qty_diperiksa,
                    'qty_rusak_diperiksa' => $item->qty_rusak_diperiksa,
                    'harga_beli' => 0.0,
                    'subtotal_nilai' => 0.0,
                    'catatan' => $item->catatan_discrepancy,
                ];
            })->values();

            $groupStatus = $this->resolveTransferGroupStatus($groupReceipts);
            $notes = $groupReceipts
                ->pluck('catatan_discrepancy')
                ->filter(static fn (?string $note): bool => trim((string) $note) !== '')
                ->unique()
                ->implode("\n");
            $verifiedReceipt = $groupReceipts->firstWhere('diverifikasi_at', '!=', null);

            return (object) [
                'id' => (int) $groupReceipts->min('id'),
                'sumber_penerimaan' => 'permintaan',
                'nomor_rcv' => $baseSj,
                'nama_supplier' => $transfer?->gudangAsal?->nama ?? 'Gudang Store',
                'po_referensi' => $transfer?->permintaanBarang?->nomor_pr,
                'nomor_surat_jalan' => $baseSj,
                'tanggal_terima' => $first->diterima_at,
                'waktu_terima' => $first->diterima_at?->format('H:i:s'),
                'has_surat_jalan' => true,
                'alasan_tanpa_sj' => null,
                'status_verifikasi' => $groupStatus,
                'catatan' => $notes !== '' ? $notes : null,
                'catatan_verifikasi' => $verifiedReceipt?->catatan_verifikasi ?? $first->catatan_verifikasi,
                'diverifikasi_at' => $verifiedReceipt?->diverifikasi_at,
                'penerima' => $first->penerima,
                'verifier' => $verifiedReceipt?->verifier ?? $first->verifier,
                'details' => $details,
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
        $todayDate = $today->toDateString();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $supplierMetrics = $this->supplierReceiptMetricsQuery($gudang, $today)->first();
        $transferGroups = $this->buildGroupedTransferReceipts($gudang, [
            'status' => 'semua',
            'diterima_oleh' => null,
            'tanggal' => null,
            'q' => '',
        ]);

        $transferPending = $transferGroups->where('status_verifikasi', 'menunggu_verifikasi')->count();
        $transferVerified = $transferGroups->whereIn('status_verifikasi', ['terverifikasi', 'sesuai'])->count();
        $transferFollowUp = $transferGroups->where('status_verifikasi', 'perlu_tindak_lanjut')->count();
        $transferVerifiedToday = $transferGroups->filter(
            static fn (stdClass $group): bool => in_array($group->status_verifikasi, ['terverifikasi', 'perlu_tindak_lanjut', 'sesuai'], true)
                && $group->diverifikasi_at !== null
                && Carbon::parse($group->diverifikasi_at)->toDateString() === $todayDate
        )->count();

        return [
            'pending_count' => (int) $supplierMetrics->pending_count + $transferPending,
            'verified_count' => (int) $supplierMetrics->verified_count + $transferVerified,
            'verified_today_count' => (int) $supplierMetrics->verified_today_count + $transferVerifiedToday,
            'follow_up_count' => (int) $supplierMetrics->follow_up_count + $transferFollowUp,
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
