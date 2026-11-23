<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\PermintaanBarang;
use App\Models\PermintaanBarangDetail;
use App\Models\TransferBarang;
use App\Models\User;
use App\Services\SemanticVectorSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class PengeluaranStoreService
{
    public function __construct(
        private StokService $stokService,
        private SemanticVectorSearch $semanticSearch,
    ) {}

    public function canCreateTransfers(User $user): bool
    {
        return $user->hasRole('Super Admin')
            || $user->can('store_pengeluaran.create')
            || $user->can('store.distribusi');
    }

    /**
     * @param  Collection<int, PermintaanBarangDetail>  $details
     * @return Collection<int, PermintaanBarangDetail>
     */
    public function withAvailableStock(Collection $details, int $gudangId): Collection
    {
        $availableStock = $this->availableStock($details->pluck('barang_id'), $gudangId);

        return $details->each(function (PermintaanBarangDetail $detail) use ($availableStock): void {
            $barangId = $detail->barang_id;
            $detail->qty_sisa = max(0, (int) $detail->qty_diminta - (int) $detail->qty_diproses);
            $detail->stok_store_tersedia = (int) ($availableStock[$barangId] ?? 0);
        });
    }

    /**
     * @param  Collection<int, int|string>|array<int, int|string>  $barangIds
     * @return Collection<int|string, int>
     */
    public function availableStock(Collection|array $barangIds, int $gudangId): Collection
    {
        $ids = collect($barangIds)->unique()->filter()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $stockTotals = $this->stokService->totals($ids, $gudangId);
        $pendingReservations = TransferBarang::query()
            ->where('gudang_asal_id', $gudangId)
            ->whereIn('barang_id', $ids)
            ->where('status', 'diminta')
            ->select('barang_id')
            ->selectRaw('SUM(qty_diminta) AS total')
            ->groupBy('barang_id')
            ->pluck('total', 'barang_id');

        return $ids->mapWithKeys(function ($barangId) use ($stockTotals, $pendingReservations): array {
            $physicalStock = max(
                0,
                (int) ($stockTotals['awal'][$barangId] ?? 0)
                    + (int) ($stockTotals['masuk'][$barangId] ?? 0)
                    - (int) ($stockTotals['keluar'][$barangId] ?? 0)
            );

            return [
                $barangId => max(0, $physicalStock - (int) ($pendingReservations[$barangId] ?? 0)),
            ];
        });
    }

    /**
     * Returns notification badges for incoming requests.
     *
     * @return array{countMenunggu: int, countSiapProses: int}
     */
    public function getNoticeCounters(int $gudangStoreId): array
    {
        $countMenunggu = PermintaanBarang::where('gudang_tujuan_id', $gudangStoreId)
            ->where('status', 'baru')
            ->count();

        $countSiapProses = PermintaanBarang::where('gudang_tujuan_id', $gudangStoreId)
            ->where('status', 'diproses')
            ->whereHas('details', fn (Builder $d) => $d->whereColumn('qty_diproses', '<', 'qty_diminta'))
            ->count();

        return compact('countMenunggu', 'countSiapProses');
    }

    /**
     * Fetch paginated Permintaan Barang targeted at the Store.
     *
     * @param  array{status?: ?string, search?: ?string}  $filters
     */
    public function getPermintaanListing(int $gudangStoreId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $status = $filters['status'] ?? '';
        $search = trim($filters['search'] ?? '');

        $permintaanQuery = PermintaanBarang::query()
            ->select([
                'id',
                'nomor_pr',
                'gudang_peminta_id',
                'gudang_tujuan_id',
                'status',
                'catatan',
                'alasan_penolakan',
                'dibuat_oleh',
                'created_at',
            ])
            ->withCount('details')
            ->withExists([
                'details as has_unallocated_details' => fn (Builder $details): Builder => $details
                    ->whereColumn('qty_diproses', '<', 'qty_diminta'),
            ])
            ->with([
                'gudangPeminta:id,nama',
                'peminta:id,name',
                'transfers' => fn ($t) => $t->select([
                    'id', 'permintaan_barang_id', 'nomor_sj', 'status', 'qty_diminta', 'qty_dikirim', 'supir_pengantar', 'dikirim_at',
                ])->orderBy('id'),
                'details' => function (HasMany $details): void {
                    $details->select([
                        'id',
                        'permintaan_barang_id',
                        'barang_id',
                        'qty_diminta',
                        'qty_diproses',
                        'satuan_id',
                    ])->with([
                        'barang:id,nama,satuan_id,kategori_id',
                        'barang.satuan:id,nama',
                        'barang.kategori:id,nama',
                        'satuan:id,nama',
                    ]);
                },
            ])
            ->where('gudang_tujuan_id', $gudangStoreId);

        if ($search !== '') {
            $permintaanQuery->where(function (Builder $q) use ($search): void {
                $q->where('nomor_pr', 'LIKE', "%{$search}%")
                    ->orWhereHas('gudangPeminta', fn (Builder $g) => $g->where('nama', 'LIKE', "%{$search}%"))
                    ->orWhereHas('peminta', fn (Builder $u) => $u->where('name', 'LIKE', "%{$search}%"))
                    ->orWhereHas('details.barang', fn (Builder $b) => $b->where('nama', 'LIKE', "%{$search}%"));
            });
        }

        if ($status !== '') {
            $permintaanQuery->where('status', $status);
        }

        $paginated = $permintaanQuery
            ->orderByRaw("CASE 
                WHEN status = 'baru' THEN 1 
                WHEN status = 'diproses' THEN 2 
                WHEN status = 'selesai' THEN 3 
                ELSE 4 
            END ASC")
            ->orderBy('created_at', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        $allDetails = $paginated->getCollection()->pluck('details')->flatten();
        $this->withAvailableStock($allDetails, $gudangStoreId);

        return $paginated;
    }

    /**
     * Get data required to render the direct transfer / allocation form tab.
     *
     * @return array{
     *     unitGudangs: Collection<int, Gudang>,
     *     unitUsersMap: Collection<string, Collection<int, User>>,
     *     approvedPermintaans: Collection<int, PermintaanBarang>,
     *     activePermintaan: ?PermintaanBarang,
     *     allocationDetails: Collection<int, PermintaanBarangDetail>,
     *     nomorKeluar: string,
     *     nomorReqLangsung: string
     * }
     */
    public function getPemindahanFormData(int $gudangStoreId, ?int $activePermintaanId = null): array
    {
        $unitGudangs = Gudang::query()
            ->where('is_aktif', true)
            ->whereIn('jenis', ['unit_bisnis', 'gudang_umum', 'gudang_sukahati'])
            ->orderBy('nama')
            ->get(['id', 'nama']);

        $relevantUsers = User::query()
            ->whereHas('roles', fn (Builder $q) => $q->whereIn('name', [
                'Admin Gudang Umum',
                'Recording Gudang Umum',
            ]))
            ->where('is_aktif', true)
            ->with(['gudang:id'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $unitUsersMap = $unitGudangs->mapWithKeys(function (Gudang $gudang) use ($relevantUsers): array {
            $users = $relevantUsers->filter(
                fn (User $u) => $u->gudang->contains('id', $gudang->id)
            )->values();

            return [(string) $gudang->id => $users];
        });

        $approvedPermintaans = PermintaanBarang::query()
            ->where('gudang_tujuan_id', $gudangStoreId)
            ->whereIn('status', ['baru', 'diproses'])
            ->whereHas('details', fn (Builder $d) => $d->whereColumn('qty_diproses', '<', 'qty_diminta'))
            ->with([
                'gudangPeminta:id,nama',
                'peminta:id,name',
                'details' => fn (HasMany $d) => $d->with(['barang:id,nama,satuan_id', 'barang.satuan:id,nama', 'satuan:id,nama']),
            ])
            ->orderByRaw("CASE WHEN status = 'baru' THEN 1 ELSE 2 END ASC")
            ->orderBy('created_at', 'asc')
            ->get();

        $activePermintaan = null;
        $allocationDetails = collect();

        if ($activePermintaanId !== null) {
            $activePermintaan = PermintaanBarang::query()
                ->whereKey($activePermintaanId)
                ->where('gudang_tujuan_id', $gudangStoreId)
                ->whereIn('status', ['baru', 'diproses'])
                ->with([
                    'gudangPeminta:id,nama',
                    'peminta:id,name',
                    'details' => fn (HasMany $details) => $details
                        ->with([
                            'barang:id,nama,satuan_id,kategori_id',
                            'barang.satuan:id,nama',
                            'barang.kategori:id,nama',
                            'satuan:id,nama',
                        ])
                        ->orderBy('id'),
                ])
                ->first();

            abort_unless($activePermintaan, 404);

            $allocationDetails = $this->withAvailableStock(
                $activePermintaan->details,
                $gudangStoreId
            );
        }

        $nomorKeluar = $this->generateNomorKeluar();

        return [
            'unitGudangs' => $unitGudangs,
            'unitUsersMap' => $unitUsersMap,
            'approvedPermintaans' => $approvedPermintaans,
            'activePermintaan' => $activePermintaan,
            'allocationDetails' => $allocationDetails,
            'nomorKeluar' => $nomorKeluar,
            'nomorReqLangsung' => $nomorKeluar,
        ];
    }

    /**
     * Get transfers waiting to be shipped.
     */
    public function getTransfersMenungguKirim(int $gudangStoreId, int $perPage = 15): LengthAwarePaginator
    {
        return TransferBarang::query()
            ->with([
                'barang:id,nama,satuan_id',
                'barang.satuan:id,nama',
                'gudangTujuan:id,nama',
                'permintaanBarang:id,nomor_pr',
            ])
            ->where('gudang_asal_id', $gudangStoreId)
            ->where('status', 'diminta')
            ->orderBy('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get historical goods issue documents (dikirim/diterima).
     *
     * @param  array{search?: ?string, tgl_awal?: ?string, tgl_akhir?: ?string}  $filters
     * @return array{paginatedDocs: LengthAwarePaginator, allTransfers: Collection}
     */
    public function getHistoriDocuments(int $gudangStoreId, array $filters, int $perPage = 15): array
    {
        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? '';
        $tglAwal = $filters['tgl_awal'] ?? null;
        $tglAkhir = $filters['tgl_akhir'] ?? null;

        $docQuery = TransferBarang::query()
            ->select(
                'permintaan_barang_id',
                DB::raw("REGEXP_REPLACE(nomor_sj, '-\\d+$', '') as nomor_keluar"),
                DB::raw('MAX(dikirim_at) as max_dikirim_at'),
                DB::raw('MAX(created_at) as max_created_at')
            )
            ->where('gudang_asal_id', $gudangStoreId)
            ->whereIn('status', ['diminta', 'dikirim', 'diterima'])
            ->groupBy('permintaan_barang_id', DB::raw("REGEXP_REPLACE(nomor_sj, '-\\d+$', '')"));

        if ($status !== '' && in_array($status, ['diminta', 'dikirim', 'diterima'], true)) {
            $docQuery->where('status', $status);
        }

        if ($search !== '') {
            $docQuery->where(function (Builder $q) use ($search): void {
                $q->where('nomor_sj', 'LIKE', "%{$search}%")
                    ->orWhereHas('barang', fn (Builder $b) => $b->where('nama', 'LIKE', "%{$search}%")->orWhere('kode', 'LIKE', "%{$search}%"))
                    ->orWhereHas('gudangTujuan', fn (Builder $g) => $g->where('nama', 'LIKE', "%{$search}%"))
                    ->orWhereHas('permintaanBarang', fn (Builder $p) => $p->where('nomor_pr', 'LIKE', "%{$search}%"));
            });
        }

        if (! empty($tglAwal)) {
            $docQuery->whereDate(DB::raw('COALESCE(dikirim_at, created_at)'), '>=', $tglAwal);
        }

        if (! empty($tglAkhir)) {
            $docQuery->whereDate(DB::raw('COALESCE(dikirim_at, created_at)'), '<=', $tglAkhir);
        }

        $paginatedDocs = $docQuery
            ->orderByDesc(DB::raw('COALESCE(MAX(dikirim_at), MAX(created_at))'))
            ->paginate($perPage)
            ->withQueryString();

        if ($paginatedDocs->isEmpty()) {
            return [
                'paginatedDocs' => $paginatedDocs,
                'allTransfers' => collect(),
            ];
        }

        $prIds = $paginatedDocs->pluck('permintaan_barang_id')->filter()->all();
        $nomorKelurs = $paginatedDocs->pluck('nomor_keluar')->all();

        $allTransfers = TransferBarang::query()
            ->select([
                'id', 'nomor_sj', 'permintaan_barang_id', 'gudang_asal_id', 'gudang_tujuan_id',
                'barang_id', 'qty_diminta', 'qty_dikirim', 'qty_diterima',
                'status', 'dikirim_at', 'dikirim_oleh', 'diterima_oleh', 'created_at', 'supir_pengantar',
            ])
            ->with([
                'permintaanBarang:id,nomor_pr,catatan,dibuat_oleh,gudang_peminta_id',
                'permintaanBarang.peminta:id,name',
                'permintaanBarang.gudangPeminta:id,nama',
                'gudangTujuan:id,nama',
                'barang:id,nama,satuan_id,kategori_id,kode',
                'barang.satuan:id,nama',
                'barang.kategori:id,nama',
                'pengirim:id,name',
                'penerima:id,name',
            ])
            ->where('gudang_asal_id', $gudangStoreId)
            ->where(function (Builder $q) use ($prIds, $nomorKelurs): void {
                if (! empty($prIds)) {
                    $q->whereIn('permintaan_barang_id', $prIds);
                }
                foreach ($nomorKelurs as $prefix) {
                    if ($prefix) {
                        $q->orWhere('nomor_sj', 'LIKE', $prefix.'%');
                    }
                }
            })
            ->whereIn('status', ['diminta', 'dikirim', 'diterima'])
            ->orderBy('nomor_sj')
            ->get()
            ->groupBy(fn (TransferBarang $t) => $t->permintaan_barang_id
                ? (string) $t->permintaan_barang_id
                : (string) preg_replace('/-\d+$/', '', (string) $t->nomor_sj)
            );

        return compact('paginatedDocs', 'allTransfers');
    }

    /**
     * Search Store inventory with semantic vector search and available stock.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function searchBarang(string $term, int $gudangStoreId, int $limit = 20): Collection
    {
        $query = Barang::query()
            ->forGudang($gudangStoreId)
            ->where('is_aktif', true)
            ->with(['satuan:id,nama', 'kategori:id,nama']);

        if ($term !== '') {
            $this->semanticSearch->apply($query, 'barangs', $term, 'q');
        }

        $barangs = $query->limit($limit)
            ->get(['id', 'kode', 'nama', 'satuan_id', 'kategori_id']);

        $availableStock = $this->availableStock($barangs->pluck('id'), $gudangStoreId);

        return $barangs->map(fn (Barang $barang): array => [
            'id' => $barang->id,
            'kode' => $barang->kode,
            'nama' => $barang->nama,
            'kategori' => $barang->kategori?->nama,
            'satuan' => $barang->satuan?->nama,
            'stok_tersedia' => (int) ($availableStock[$barang->id] ?? 0),
        ]);
    }

    /**
     * Process allocation from incoming PermintaanBarang.
     *
     * @param  array<string|int, int>  $qtyDialokasikan
     *
     * @throws ValidationException
     */
    public function allocateRequest(
        PermintaanBarang $permintaan,
        array $qtyDialokasikan,
        ?string $customNomorKeluar,
        Gudang $gudangStore
    ): string {
        $nomorKeluar = ! empty($customNomorKeluar)
            ? strtoupper(trim($customNomorKeluar))
            : $this->generateNomorKeluar();

        DB::transaction(function () use ($qtyDialokasikan, $permintaan, $gudangStore, $nomorKeluar): void {
            $lockedRequest = PermintaanBarang::query()
                ->whereKey($permintaan->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless((int) $lockedRequest->gudang_tujuan_id === (int) $gudangStore->id, 404);

            if (! in_array($lockedRequest->status, ['baru', 'diproses'], true)) {
                throw ValidationException::withMessages([
                    'permintaan' => 'Permintaan ini sudah tidak dapat diproses.',
                ]);
            }

            $details = $lockedRequest->details()->with('barang')->lockForUpdate()->get();
            $requestedIds = array_map('strval', array_keys($qtyDialokasikan));
            $detailIds = $details->pluck('id')->map(fn ($id) => (string) $id)->all();

            if (array_diff($requestedIds, $detailIds) !== []) {
                throw ValidationException::withMessages([
                    'qty_dialokasikan' => 'Rincian yang diproses tidak sesuai dengan permintaan.',
                ]);
            }

            $allocationByDetail = collect($qtyDialokasikan)
                ->mapWithKeys(fn ($qty, $detailId) => [(string) $detailId => (int) $qty]);
            $allocationByBarang = [];

            foreach ($details as $detail) {
                $qty = $allocationByDetail->get((string) $detail->id, 0);
                $sisa = max(0, (int) $detail->qty_diminta - (int) $detail->qty_diproses);

                if ($qty > $sisa) {
                    throw ValidationException::withMessages([
                        'qty_dialokasikan' => "Jumlah untuk {$detail->barang?->nama} melebihi sisa permintaan ({$sisa}).",
                    ]);
                }

                $allocationByBarang[$detail->barang_id] = ($allocationByBarang[$detail->barang_id] ?? 0) + $qty;
            }

            if (array_sum($allocationByBarang) < 1) {
                throw ValidationException::withMessages([
                    'qty_dialokasikan' => 'Masukkan jumlah minimal satu barang untuk dibuatkan pemindahan.',
                ]);
            }

            Barang::query()
                ->whereIn('id', array_keys($allocationByBarang))
                ->lockForUpdate()
                ->get(['id']);

            $stockTotals = $this->stokService->totals(array_keys($allocationByBarang), $gudangStore->id);
            $reserved = TransferBarang::query()
                ->where('gudang_asal_id', $gudangStore->id)
                ->whereIn('barang_id', array_keys($allocationByBarang))
                ->where('status', 'diminta')
                ->select('barang_id')
                ->selectRaw('SUM(qty_diminta) AS total')
                ->groupBy('barang_id')
                ->pluck('total', 'barang_id');

            foreach ($allocationByBarang as $barangId => $qty) {
                $physicalStock = (int) ($stockTotals['awal'][$barangId] ?? 0)
                    + (int) ($stockTotals['masuk'][$barangId] ?? 0)
                    - (int) ($stockTotals['keluar'][$barangId] ?? 0);
                $available = max(0, $physicalStock - (int) ($reserved[$barangId] ?? 0));

                if ($qty > $available) {
                    throw ValidationException::withMessages([
                        'qty_dialokasikan' => "Stok {$details->firstWhere('barang_id', $barangId)?->barang?->nama} yang tersedia untuk dialokasikan hanya {$available}.",
                    ]);
                }
            }

            $lineIdx = 1;
            foreach ($details as $detail) {
                $qty = $allocationByDetail->get((string) $detail->id, 0);
                if ($qty === 0) {
                    continue;
                }

                TransferBarang::create([
                    'nomor_sj' => $nomorKeluar.'-'.str_pad((string) $lineIdx, 2, '0', STR_PAD_LEFT),
                    'permintaan_barang_id' => $lockedRequest->id,
                    'gudang_asal_id' => $gudangStore->id,
                    'gudang_tujuan_id' => $lockedRequest->gudang_peminta_id,
                    'barang_id' => $detail->barang_id,
                    'qty_diminta' => $qty,
                    'qty_dikirim' => $qty,
                    'qty_diterima' => 0,
                    'status' => 'dikirim',
                    'dikirim_oleh' => Auth::id(),
                    'dikirim_at' => now(),
                    'supir_pengantar' => null,
                ]);

                $detail->increment('qty_diproses', $qty);
                $lineIdx++;
            }

            if ($lockedRequest->status === 'baru') {
                $lockedRequest->update(['status' => 'diproses']);
            }
        });

        return $nomorKeluar;
    }

    /**
     * Create direct transfer items (Pemindahan Langsung).
     *
     * @param  array{
     *     nomor_keluar?: ?string,
     *     gudang_tujuan_id?: ?int,
     *     unit_peminta_teks?: ?string,
     *     items?: array<int, array{barang_id: int, qty: int}>,
     *     barang_id?: ?int,
     *     qty_diminta?: ?int
     * }  $validated
     *
     * @throws ValidationException
     */
    public function createDirectTransfer(array $validated, Gudang $gudangStore): string
    {
        $nomorKeluar = ! empty($validated['nomor_keluar'])
            ? strtoupper(trim($validated['nomor_keluar']))
            : $this->generateNomorKeluar();

        $items = $validated['items'] ?? [[
            'barang_id' => $validated['barang_id'],
            'qty' => $validated['qty_diminta'],
        ]];
        $unitName = trim((string) ($validated['unit_peminta_teks'] ?? ''));

        DB::transaction(function () use ($validated, $gudangStore, $nomorKeluar, $items, $unitName): void {
            $gudangTujuanId = $validated['gudang_tujuan_id'] ?? null;
            if (! $gudangTujuanId && $unitName !== '') {
                $gudangTujuanId = Gudang::query()
                    ->where('is_aktif', true)
                    ->whereIn('jenis', ['unit_bisnis', 'gudang_umum', 'gudang_sukahati'])
                    ->where('nama', $unitName)
                    ->value('id');
            }

            if ($gudangTujuanId) {
                Gudang::query()
                    ->whereKey($gudangTujuanId)
                    ->where('is_aktif', true)
                    ->whereIn('jenis', ['unit_bisnis', 'gudang_umum', 'gudang_sukahati'])
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $lineIdx = 1;
            foreach ($items as $item) {
                $barangId = (int) $item['barang_id'];
                $qty = (int) $item['qty'];

                $barang = Barang::query()
                    ->forGudang((int) $gudangStore->id)
                    ->where('is_aktif', true)
                    ->whereKey($barangId)
                    ->lockForUpdate()
                    ->first();

                if (! $barang) {
                    throw ValidationException::withMessages([
                        'items' => 'Terdapat barang yang tidak ditemukan atau tidak aktif untuk Gudang Store.',
                    ]);
                }

                $availableStock = $this->availableStock([$barang->id], (int) $gudangStore->id);
                $available = (int) ($availableStock[$barang->id] ?? 0);

                if ($qty > $available) {
                    throw ValidationException::withMessages([
                        'items' => "Jumlah untuk {$barang->nama} melebihi stok yang tersedia ({$available}).",
                    ]);
                }

                TransferBarang::create([
                    'nomor_sj' => $nomorKeluar.'-'.str_pad((string) $lineIdx, 2, '0', STR_PAD_LEFT),
                    'permintaan_barang_id' => null,
                    'gudang_asal_id' => $gudangStore->id,
                    'gudang_tujuan_id' => $gudangTujuanId,
                    'barang_id' => $barang->id,
                    'qty_diminta' => $qty,
                    'qty_dikirim' => $qty,
                    'qty_diterima' => 0,
                    'status' => 'dikirim',
                    'dikirim_oleh' => Auth::id(),
                    'dikirim_at' => now(),
                    'supir_pengantar' => null,
                ]);

                $lineIdx++;
            }
        });

        return $nomorKeluar;
    }

    /**
     * Reject incoming PermintaanBarang.
     *
     * @throws ValidationException
     */
    public function rejectRequest(PermintaanBarang $permintaan, string $alasanPenolakan, Gudang $gudangStore): void
    {
        DB::transaction(function () use ($permintaan, $gudangStore, $alasanPenolakan): void {
            $lockedRequest = PermintaanBarang::query()
                ->whereKey($permintaan->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless((int) $lockedRequest->gudang_tujuan_id === (int) $gudangStore->id, 404);

            if ($lockedRequest->status !== 'baru' || $lockedRequest->details()->where('qty_diproses', '>', 0)->exists()) {
                throw ValidationException::withMessages([
                    'permintaan' => 'Permintaan hanya dapat ditolak sebelum ada jumlah yang dialokasikan.',
                ]);
            }

            $lockedRequest->update([
                'status' => 'ditolak',
                'alasan_penolakan' => $alasanPenolakan,
            ]);
        });
    }

    /**
     * Confirm dispatch of transfer item and decrement allocated quantity.
     *
     * @throws ValidationException
     */
    public function confirmShipment(
        TransferBarang $transfer,
        int $qtyDikirim,
        ?string $supirPengantar,
        ?string $fotoPath,
        int $userId,
        Gudang $gudangStore
    ): string {
        DB::transaction(function () use ($transfer, $gudangStore, $qtyDikirim, $supirPengantar, $fotoPath, $userId): void {
            $lockedTransfer = TransferBarang::query()
                ->whereKey($transfer->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless((int) $lockedTransfer->gudang_asal_id === (int) $gudangStore->id, 404);

            if ($lockedTransfer->status !== 'diminta') {
                throw ValidationException::withMessages([
                    'transfer' => 'Pemindahan ini sudah dikonfirmasi atau tidak lagi menunggu pengiriman.',
                ]);
            }

            if ($qtyDikirim > (int) $lockedTransfer->qty_diminta) {
                throw ValidationException::withMessages([
                    'qty_dikirim' => 'Jumlah kirim tidak boleh melebihi jumlah yang dialokasikan.',
                ]);
            }

            Barang::query()->whereKey($lockedTransfer->barang_id)->lockForUpdate()->firstOrFail();
            $stockTotals = $this->stokService->totals([$lockedTransfer->barang_id], $gudangStore->id);
            $physicalStock = (int) ($stockTotals['awal'][$lockedTransfer->barang_id] ?? 0)
                + (int) ($stockTotals['masuk'][$lockedTransfer->barang_id] ?? 0)
                - (int) ($stockTotals['keluar'][$lockedTransfer->barang_id] ?? 0);

            $otherReservations = (int) TransferBarang::query()
                ->where('gudang_asal_id', $gudangStore->id)
                ->where('barang_id', $lockedTransfer->barang_id)
                ->where('status', 'diminta')
                ->where('id', '<>', $lockedTransfer->id)
                ->sum('qty_diminta');

            if ($qtyDikirim > max(0, $physicalStock - $otherReservations)) {
                throw ValidationException::withMessages([
                    'qty_dikirim' => 'Stok tersedia tidak cukup setelah memperhitungkan pemindahan lain yang menunggu.',
                ]);
            }

            $savedFotoPath = $fotoPath ?? $lockedTransfer->foto_bukti_path;

            $lockedTransfer->update([
                'qty_dikirim' => $qtyDikirim,
                'status' => 'dikirim',
                'supir_pengantar' => $supirPengantar,
                'foto_bukti_path' => $savedFotoPath,
                'dikirim_oleh' => $userId,
                'dikirim_at' => now(),
            ]);

            if ($lockedTransfer->permintaan_barang_id) {
                $parentRequest = $lockedTransfer->permintaanBarang()->lockForUpdate()->first();
                if ($parentRequest) {
                    $releasedQty = max(0, (int) $lockedTransfer->qty_diminta - $qtyDikirim);
                    $details = $parentRequest->details()
                        ->where('barang_id', $lockedTransfer->barang_id)
                        ->lockForUpdate()
                        ->get();

                    foreach ($details as $detail) {
                        if ($releasedQty < 1) {
                            break;
                        }
                        $releaseFromDetail = min($releasedQty, (int) $detail->qty_diproses);
                        if ($releaseFromDetail > 0) {
                            $detail->decrement('qty_diproses', $releaseFromDetail);
                            $releasedQty -= $releaseFromDetail;
                        }
                    }
                }
            }
        });

        return (string) preg_replace('/-\d+$/', '', (string) $transfer->nomor_sj);
    }

    /**
     * Look up transfers matching an outgoing document prefix and format the print payload.
     *
     * @return array<string, mixed>|null
     */
    public function getPrintDocument(string $sjTarget, int $gudangStoreId): ?array
    {
        $sjTransfers = TransferBarang::query()
            ->where('gudang_asal_id', $gudangStoreId)
            ->where('nomor_sj', 'LIKE', $sjTarget.'%')
            ->with([
                'permintaanBarang:id,nomor_pr,catatan,gudang_peminta_id',
                'permintaanBarang.peminta:id,name',
                'permintaanBarang.gudangPeminta:id,nama',
                'gudangTujuan:id,nama',
                'barang:id,nama,satuan_id,kategori_id,kode',
                'barang.satuan:id,nama',
                'barang.kategori:id,nama',
                'pengirim:id,name',
                'penerima:id,name',
            ])
            ->orderBy('nomor_sj')
            ->get();

        if ($sjTransfers->isEmpty()) {
            return null;
        }

        return $this->buildDocPayload($sjTarget, $sjTransfers);
    }

    /**
     * @param  Collection<int, TransferBarang>  $transfers
     * @return array<string, mixed>
     */
    public function buildDocPayload(string $nomorKeluar, Collection $transfers): array
    {
        $first = $transfers->first();
        $catatan = optional(optional($first)->permintaanBarang)->catatan ?? '';
        $nomorPrRaw = optional(optional($first)->permintaanBarang)->nomor_pr;
        $isLangsung = empty($nomorPrRaw) || str_starts_with((string) $nomorPrRaw, 'REQLANG-');
        $nomorPr = $isLangsung ? null : $nomorPrRaw;

        $pemohon = optional(optional($first)->permintaanBarang?->peminta)->name;
        if (! $pemohon && $first?->penerima) {
            $pemohon = $first->penerima->name;
        }
        if (! $pemohon && preg_match('/Pemohon:\s*([^,]+)/i', $catatan, $m)) {
            $pemohon = trim($m[1]);
        }
        $pemohon = $pemohon ?: '—';

        $petugas = optional(optional($first)->pengirim)->name ?? 'Admin Store';
        if (preg_match('/Petugas:\s*([^,]+)/i', $catatan, $m2)) {
            $petugas = trim($m2[1]);
        }

        $tujuan = null;
        if ($first?->gudang_tujuan_id && (int) $first->gudang_tujuan_id !== (int) $first->gudang_asal_id) {
            $tujuan = optional($first->gudangTujuan)->nama;
        }
        if (! $tujuan && $first?->permintaanBarang?->gudang_peminta_id && (int) $first->permintaanBarang->gudang_peminta_id !== (int) $first->gudang_asal_id) {
            $tujuan = optional($first->permintaanBarang->gudangPeminta)->nama;
        }
        if ((! $tujuan || $tujuan === 'Gudang Store') && preg_match('/Keterangan:\s*([^,]+)/i', $catatan, $mk)) {
            $tujuan = trim($mk[1]);
        }
        $hasTujuan = ! empty($tujuan) && $tujuan !== '—' && $tujuan !== 'Gudang Store';
        $tujuan = $hasTujuan ? $tujuan : '—';
        $waktu = $first?->dikirim_at ?? $first?->created_at;

        return [
            'nomorKeluar' => $nomorKeluar,
            'waktu' => $waktu ? Carbon::parse($waktu)->translatedFormat('d F Y') : '—',
            'nomorPr' => $nomorPr,
            'isLangsung' => $isLangsung,
            'showKepada' => $hasTujuan,
            'petugas' => $petugas,
            'tujuan' => $tujuan,
            'pemohon' => $pemohon,
            'catatan' => $catatan,
            'totalJenis' => $transfers->count(),
            'totalQty' => (int) $transfers->sum(fn (TransferBarang $t) => $t->qty_dikirim > 0 ? $t->qty_dikirim : $t->qty_diminta),
            'items' => $transfers->values()->map(function (TransferBarang $it, int $idx): array {
                return [
                    'no' => $idx + 1,
                    'kode' => optional($it->barang)->kode ?? '—',
                    'nama' => optional($it->barang)->nama ?? '—',
                    'kategori' => optional(optional($it->barang)->kategori)->nama ?? '—',
                    'satuan' => optional(optional($it->barang)->satuan)->nama ?? '—',
                    'qty' => (int) ($it->qty_dikirim > 0 ? $it->qty_dikirim : $it->qty_diminta),
                ];
            })->all(),
        ];
    }

    public function generateNomorKeluar(): string
    {
        // Format: OUT-STR-{DDMMYYYY}{3_angka_acak}-{0001}
        $date = now()->format('dmY');
        $rand = str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT);
        $prefix = 'OUT-STR-'.$date;

        $todayCount = TransferBarang::query()
            ->where('nomor_sj', 'like', $prefix.'%-01')
            ->count();

        $seq = str_pad((string) ($todayCount + 1), 4, '0', STR_PAD_LEFT);

        return $prefix.$rand.'-'.$seq;
    }
}
