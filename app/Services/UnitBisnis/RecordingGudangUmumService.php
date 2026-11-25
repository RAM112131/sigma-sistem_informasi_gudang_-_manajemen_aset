<?php

declare(strict_types=1);

namespace App\Services\UnitBisnis;

use App\Models\Barang;
use App\Models\DivisiPeminta;
use App\Models\Gudang;
use App\Models\PenerimaanPemindahanUnit;
use App\Models\PenerimaanUnit;
use App\Models\PengeluaranUnit;
use App\Models\PengeluaranUnitDetail;
use App\Models\PengembalianUnit;
use App\Models\PermintaanBarang;
use App\Models\TransferBarang;
use App\Models\User;
use App\Services\MasterData\DivisiPemintaService;
use App\Services\Store\StokService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RecordingGudangUmumService
{
    public function __construct(
        private readonly UnitGudangService $unitGudangService,
        private readonly StokService $stokService,
        private readonly DivisiPemintaService $divisiPemintaService,
    ) {}

    /**
     * @return array{stats: array<string, int|string>}
     */
    public function getDashboardData(): array
    {
        return [
            'stats' => [
                'stok_minimum_alert' => 3,
                'penerimaan_hari_ini' => 5,
                'pengeluaran_hari_ini' => 8,
                'opname_status' => 'Pending Input Harian',
            ],
        ];
    }

    /**
     * @return array{
     *     activeGudang: ?Gudang,
     *     gudangUnits: Collection<int, Gudang>
     * }
     */
    public function getGudangContext(User $user, int|string|null $selectedGudangId): array
    {
        $activeGudang = $this->resolveActiveGudang($user, $selectedGudangId);

        return [
            'activeGudang' => $activeGudang,
            'gudangUnits' => $this->unitGudangService->getAssignedGudangUnits($user),
        ];
    }

    public function resolveActiveGudang(User $user, int|string|null $selectedGudangId): ?Gudang
    {
        return $this->unitGudangService->resolveActiveGudang(
            $user,
            $selectedGudangId,
            ['unit_bisnis', 'gudang_sukahati', 'gudang_umum'],
        );
    }

    public function isAdminGudangUmum(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'super_admin', 'Admin Gudang Umum', 'admin_gudang_umum']);
    }

    /**
     * @return array{
     *     penerimaans: array<int, array<string, mixed>>,
     *     directItemCatalog: array<int, array<string, mixed>>,
     *     storeRequests: array<int, array<string, mixed>>,
     *     barangs: Collection<int, Barang>,
     *     gudangUnits: Collection<int, Gudang>,
     *     activeGudang: ?Gudang,
     *     isStore: bool,
     *     nextDirectReceiptNumber: string
     * }
     */
    public function getPenerimaanPageData(User $user, int|string|null $selectedGudangId): array
    {
        $context = $this->getGudangContext($user, $selectedGudangId);
        $activeGudang = $context['activeGudang'];

        $identifier = strtoupper(preg_replace(
            '/[^A-Z0-9]/',
            '',
            (string) ($activeGudang?->kode ?: $activeGudang?->nama),
        ) ?? '');
        $unitPrefix = match (true) {
            str_contains($identifier, 'SUKAHATI') => 'SHT',
            str_contains($identifier, 'TAMANSARI') => 'TMS',
            default => substr($identifier, 0, 3) ?: 'UNT',
        };

        $dateNum = now()->format('Ymd');
        $todayCount = $activeGudang !== null
            ? PenerimaanUnit::query()
                ->where('gudang_id', $activeGudang->getKey())
                ->where('tanggal_terima', today()->toDateString())
                ->count() + 1
            : 1;
        $nextDirectReceiptNumber = sprintf('RCV-%s-%s001-%04d', $unitPrefix, $dateNum, $todayCount);

        $directCatalog = $this->barangOptionsWithStock($activeGudang);
        $storeRequests = $this->getStoreRequestsForGudang($activeGudang);
        $storeReceipts = $this->getStoreReceiptsHistoryForGudang($activeGudang);
        $directReceipts = $this->getDirectReceiptsForGudang($activeGudang);

        return [
            'penerimaans' => $directReceipts,
            'directItemCatalog' => $directCatalog,
            'storeRequests' => $storeRequests,
            'storeReceipts' => $storeReceipts,
            'barangs' => $this->barangOptions($activeGudang),
            'gudangUnits' => $context['gudangUnits'],
            'activeGudang' => $activeGudang,
            'isStore' => false,
            'nextDirectReceiptNumber' => $nextDirectReceiptNumber,
        ];
    }

    /**
     * @param  array{
     *     nama_supplier: string,
     *     has_surat_jalan: bool|int|string,
     *     alasan_tanpa_sj?: ?string,
     *     po_referensi?: ?string,
     *     nomor_surat_jalan?: ?string,
     *     tanggal_terima?: ?string,
     *     waktu_terima?: ?string,
     *     catatan?: ?string,
     *     items: array<int, array{
     *         barang_id: int,
     *         qty_diterima: int|float|string,
     *         satuan?: ?string,
     *         kondisi: string,
     *         qty_rusak?: int|float|string|null,
     *         qty_kurang?: int|float|string|null,
     *         qty_lebih?: int|float|string|null,
     *         catatan?: ?string
     *     }>
     * }  $validated
     */
    public function saveDirectReceipt(
        User $user,
        int|string|null $selectedGudangId,
        array $validated,
    ): PenerimaanUnit {
        $context = $this->getGudangContext($user, $selectedGudangId);
        $activeGudang = $context['activeGudang'];
        abort_unless($activeGudang, 403, 'Pilih gudang unit aktif yang ditugaskan kepada Anda.');

        $barangIds = collect($validated['items'])
            ->pluck('barang_id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->unique()
            ->values();

        $barangs = Barang::query()
            ->where('gudang_id', $activeGudang->getKey())
            ->where('is_aktif', true)
            ->whereKey($barangIds)
            ->with(['kategori:id,nama', 'satuan:id,nama'])
            ->get()
            ->keyBy('id');

        if ($barangs->count() !== $barangIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'Barang yang dipilih tidak tersedia di gudang aktif.',
            ]);
        }

        return DB::transaction(function () use ($activeGudang, $barangs, $validated, $user): PenerimaanUnit {
            $lockedGudang = Gudang::query()
                ->whereKey($activeGudang->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $identifier = strtoupper(preg_replace(
                '/[^A-Z0-9]/',
                '',
                (string) ($lockedGudang->kode ?: $lockedGudang->nama),
            ) ?? '');
            $unitPrefix = match (true) {
                str_contains($identifier, 'SUKAHATI') => 'SHT',
                str_contains($identifier, 'TAMANSARI') => 'TMS',
                default => substr($identifier, 0, 3) ?: 'UNT',
            };

            $tanggalTerima = $validated['tanggal_terima'] ?? now()->format('Y-m-d');
            $waktuTerima = $validated['waktu_terima'] ?? now()->format('H:i:s');
            if (strlen((string) $waktuTerima) === 5) {
                $waktuTerima .= ':00';
            }

            $dateNum = str_replace('-', '', (string) $tanggalTerima);
            $sequence = PenerimaanUnit::query()
                ->where('gudang_id', $lockedGudang->getKey())
                ->whereDate('tanggal_terima', $tanggalTerima)
                ->count() + 1;

            $nomorRcv = sprintf('RCV-%s-%s001-%04d', $unitPrefix, $dateNum, $sequence);

            $penerimaan = PenerimaanUnit::create([
                'nomor_rcv' => $nomorRcv,
                'gudang_id' => $lockedGudang->getKey(),
                'nama_supplier' => trim($validated['nama_supplier']),
                'tanggal_terima' => $tanggalTerima,
                'waktu_terima' => $waktuTerima,
                'po_referensi' => $validated['po_referensi'] ?? null,
                'nomor_surat_jalan' => $validated['nomor_surat_jalan'] ?? null,
                'has_surat_jalan' => (bool) $validated['has_surat_jalan'],
                'alasan_tanpa_sj' => ! (bool) $validated['has_surat_jalan'] ? ($validated['alasan_tanpa_sj'] ?? null) : null,
                'foto_surat_jalan_path' => null,
                'catatan' => $validated['catatan'] ?? null,
                'diterima_oleh' => $user->getKey(),
                'status_verifikasi' => 'menunggu_verifikasi',
            ]);

            foreach ($validated['items'] as $item) {
                $barang = $barangs->get((int) $item['barang_id']);
                if (! $barang instanceof Barang) {
                    continue;
                }

                $satuan = $item['satuan'] ?? $barang->satuan?->nama ?? 'unit';
                $penerimaan->details()->create([
                    'barang_id' => $barang->getKey(),
                    'kode_barang' => $barang->kode,
                    'nama_barang' => $barang->nama,
                    'kategori' => $barang->kategori?->nama,
                    'satuan' => $satuan,
                    'qty_diterima' => round((float) $item['qty_diterima'], 3),
                    'kondisi' => $item['kondisi'],
                    'qty_rusak' => round((float) ($item['qty_rusak'] ?? 0), 3),
                    'qty_kurang' => round((float) ($item['qty_kurang'] ?? 0), 3),
                    'qty_lebih' => round((float) ($item['qty_lebih'] ?? 0), 3),
                    'catatan' => $item['catatan'] ?? null,
                ]);
            }

            return $penerimaan->load(['details', 'penerima']);
        }, attempts: 3);
    }

    /**
     * Confirm receipt from Store transfers.
     *
     * @param  array{
     *     transfer_id?: ?int,
     *     request_id?: ?string,
     *     items: array<int, array{
     *         transfer_id: int,
     *         qty_diterima: int|float|string,
     *         qty_rusak?: int|float|string|null,
     *         catatan_discrepancy?: ?string
     *     }>
     * }  $validated
     * @return array{confirmed_count: int, message: string}
     */
    public function confirmStoreTransfer(
        User $user,
        int|string|null $selectedGudangId,
        array $validated,
    ): array {
        $context = $this->getGudangContext($user, $selectedGudangId);
        $activeGudang = $context['activeGudang'];
        abort_unless($activeGudang, 403, 'Pilih gudang unit aktif yang ditugaskan kepada Anda.');

        return DB::transaction(function () use ($activeGudang, $user, $validated): array {
            $confirmedCount = 0;
            $transferIds = collect($validated['items'])
                ->pluck('transfer_id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            $transfers = TransferBarang::query()
                ->whereIn('id', $transferIds)
                ->where('gudang_tujuan_id', $activeGudang->getKey())
                ->where('status', 'dikirim')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($transfers->isEmpty()) {
                return [
                    'confirmed_count' => 0,
                    'message' => 'Tidak ada kiriman berstatus dikirim yang diproses.',
                ];
            }

            $existingReceipts = PenerimaanPemindahanUnit::query()
                ->whereIn('transfer_barang_id', $transfers->keys())
                ->get()
                ->keyBy('transfer_barang_id');

            $receivedAt = now();
            $permintaanIdsToComplete = [];

            foreach ($validated['items'] as $item) {
                $transferId = (int) $item['transfer_id'];
                $transfer = $transfers->get($transferId);

                if (! $transfer instanceof TransferBarang) {
                    continue;
                }

                $qtyDiterima = (int) round((float) $item['qty_diterima']);
                $qtyRusak = (int) round((float) ($item['qty_rusak'] ?? 0));
                $catatan = trim((string) ($item['catatan_discrepancy'] ?? ''));

                if ($qtyDiterima < 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Jumlah diterima tidak boleh negatif.',
                    ]);
                }

                if ($qtyRusak > $qtyDiterima) {
                    throw ValidationException::withMessages([
                        'items' => 'Jumlah rusak tidak boleh melebihi jumlah diterima.',
                    ]);
                }

                $existing = $existingReceipts->get($transfer->getKey());
                if ($existing instanceof PenerimaanPemindahanUnit) {
                    $existing->update([
                        'gudang_id' => $activeGudang->getKey(),
                        'barang_id' => $transfer->barang_id,
                        'qty_dikirim' => $transfer->qty_dikirim,
                        'qty_diterima' => $qtyDiterima,
                        'qty_rusak' => $qtyRusak,
                        'catatan_discrepancy' => $catatan !== '' ? $catatan : null,
                        'diterima_oleh' => $user->getKey(),
                        'diterima_at' => $receivedAt,
                    ]);
                } else {
                    PenerimaanPemindahanUnit::create([
                        'transfer_barang_id' => $transfer->getKey(),
                        'gudang_id' => $activeGudang->getKey(),
                        'barang_id' => $transfer->barang_id,
                        'qty_dikirim' => $transfer->qty_dikirim,
                        'qty_diterima' => $qtyDiterima,
                        'qty_rusak' => $qtyRusak,
                        'catatan_discrepancy' => $catatan !== '' ? $catatan : null,
                        'diterima_oleh' => $user->getKey(),
                        'diterima_at' => $receivedAt,
                    ]);
                }

                $transfer->update([
                    'status' => 'diterima',
                    'qty_diterima' => $qtyDiterima,
                    'diterima_oleh' => $user->getKey(),
                    'diterima_at' => $receivedAt,
                ]);

                $confirmedCount++;

                if ($transfer->permintaan_barang_id !== null) {
                    $permintaanIdsToComplete[$transfer->permintaan_barang_id] = true;
                }
            }

            foreach (array_keys($permintaanIdsToComplete) as $permintaanId) {
                $this->completeRequestWhenFulfilled((int) $permintaanId);
            }

            return [
                'confirmed_count' => $confirmedCount,
                'message' => $confirmedCount > 0
                    ? "Berhasil mencatat penerimaan {$confirmedCount} item dari Gudang Store. Stok fisik unit telah bertambah."
                    : 'Tidak ada kiriman berstatus dikirim yang diproses.',
            ];
        }, attempts: 3);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDirectReceiptsForGudang(?Gudang $activeGudang): array
    {
        if ($activeGudang === null) {
            return [];
        }

        return PenerimaanUnit::query()
            ->where('gudang_id', $activeGudang->getKey())
            ->with(['details', 'penerima'])
            ->orderByDesc('tanggal_terima')
            ->orderByDesc('waktu_terima')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(function (PenerimaanUnit $rcv): array {
                $firstDetail = $rcv->details->first();
                $totalQty = (float) $rcv->details->sum('qty_diterima');
                $detailCount = $rcv->details->count();
                $barangLabel = $firstDetail ? $firstDetail->nama_barang : '—';
                if ($detailCount > 1) {
                    $barangLabel .= ' (+'.($detailCount - 1).' barang lainnya)';
                }

                return [
                    'id' => (string) $rcv->nomor_rcv,
                    'tanggal' => $rcv->tanggal_terima->format('d M Y').', '.substr((string) $rcv->waktu_terima, 0, 5),
                    'jenis_masuk' => 'Supplier Langsung',
                    'badge_jenis' => 'badge-neutral',
                    'po_ref' => $rcv->po_referensi ?: ($rcv->nomor_surat_jalan ? 'SJ: '.$rcv->nomor_surat_jalan : '—'),
                    'barang' => $barangLabel,
                    'qty' => $totalQty,
                    'satuan' => $firstDetail?->satuan ?? 'unit',
                    'sumber_asal' => $rcv->nama_supplier,
                    'tanpa_sj' => ! $rcv->has_surat_jalan,
                    'alasan' => $rcv->alasan_tanpa_sj,
                    'petugas' => $rcv->penerima?->name ?? 'Petugas Recording',
                    'status_verifikasi' => $rcv->status_verifikasi,
                    'items_count' => $detailCount,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getStoreRequestsForGudang(?Gudang $activeGudang): array
    {
        if ($activeGudang === null) {
            return [];
        }

        // Hanya muat pengiriman barang yang berstatus 'dikirim' (menunggu diterima secara fisik)
        $transfers = TransferBarang::query()
            ->where('gudang_tujuan_id', $activeGudang->getKey())
            ->where('status', 'dikirim')
            ->with([
                'permintaanBarang.peminta',
                'gudangAsal',
                'barang.satuan',
                'penerimaanUnit',
            ])
            ->orderByDesc('dikirim_at')
            ->orderByDesc('created_at')
            ->get();

        $result = [];

        $groupedTransfers = $transfers->groupBy(static function (TransferBarang $t): string {
            if ($t->nomor_sj) {
                return (string) preg_replace('/-\d+$/', '', (string) $t->nomor_sj);
            }

            return $t->permintaan_barang_id ? 'REQ_'.$t->permintaan_barang_id : 'TRF_'.$t->id;
        });

        foreach ($groupedTransfers as $items) {
            $first = $items->first();
            if (! $first instanceof TransferBarang) {
                continue;
            }

            $permintaan = $first->permintaanBarang;
            $deliveryNote = (string) ($first->nomor_sj ? preg_replace('/-\d+$/', '', (string) $first->nomor_sj) : '—');
            $reqId = $deliveryNote !== '—' ? $deliveryNote : ($permintaan?->nomor_pr ?? 'TRF-'.$first->id);
            $vendorName = $first->gudangAsal?->nama ?? 'Gudang Store Pusat';
            $poNumber = $first->permintaanBarang?->nomor_pr ?? $deliveryNote;

            $status = 'menunggu_diterima';
            $statusLabel = 'Menunggu Diterima';

            $reqDate = ($first->dikirim_at ?? $first->created_at)?->format('d/m/Y H:i') ?? '';

            $itemRows = $items->map(static function (TransferBarang $t): array {
                $penerimaan = $t->penerimaanUnit;
                $receivedQty = (int) $t->qty_dikirim;
                $damagedQty = 0;
                $requestQty = (int) ($t->qty_diminta ?: $t->qty_dikirim);
                $diff = $receivedQty - $requestQty;
                $shortQty = max(0, -$diff);
                $moreQty = max(0, $diff);

                $condition = 'Baik';
                $matches = ($diff === 0);

                return [
                    'id' => (int) $t->id,
                    'name' => $t->barang?->nama ?? 'Item #'.$t->barang_id,
                    'sku' => $t->barang?->kode ?? 'SKU-'.$t->barang_id,
                    'unit' => $t->barang?->satuan?->nama ?? 'pcs',
                    'requestQty' => $requestQty,
                    'receivedQty' => $receivedQty,
                    'condition' => $condition,
                    'damagedQty' => $damagedQty,
                    'shortQty' => $shortQty,
                    'moreQty' => $moreQty,
                    'note' => $penerimaan?->catatan_discrepancy ?? '',
                    'matchesRequest' => $matches,
                    'initialReceivedQty' => $receivedQty,
                    'initialCondition' => $condition,
                    'initialDamagedQty' => $damagedQty,
                    'initialShortQty' => $shortQty,
                    'initialMoreQty' => $moreQty,
                    'initialNote' => $penerimaan?->catatan_discrepancy ?? '',
                    'initialMatchesRequest' => $matches,
                ];
            })->values()->all();

            $result[] = [
                'id' => $reqId,
                'poNumber' => $poNumber,
                'vendor' => $vendorName,
                'deliveryNote' => $deliveryNote,
                'status' => $status,
                'statusLabel' => $statusLabel,
                'requestDate' => $reqDate,
                'receivedAt' => null,
                'requestedBy' => $permintaan?->peminta?->name ?? 'Admin Gudang',
                'destinationUnit' => $activeGudang->nama,
                'notes' => $permintaan?->catatan ?? ($first->supir_pengantar ? 'Supir: '.$first->supir_pengantar : 'Kiriman Store ke Unit'),
                'items' => $itemRows,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     nomor_sj: string,
     *     nomor_pr: ?string,
     *     tanggal_terima: string,
     *     gudang_asal: string,
     *     barang: string,
     *     qty_dikirim: int,
     *     qty_diterima: int,
     *     qty_rusak: int,
     *     satuan: string,
     *     catatan: string,
     *     supir: string,
     *     penerima: string
     * }>
     */
    public function getStoreReceiptsHistoryForGudang(?Gudang $activeGudang, int $limit = 50): array
    {
        if ($activeGudang === null) {
            return [];
        }

        return TransferBarang::query()
            ->where('gudang_tujuan_id', $activeGudang->getKey())
            ->where('status', 'diterima')
            ->with([
                'permintaanBarang:id,nomor_pr',
                'gudangAsal:id,nama',
                'barang:id,nama,satuan_id',
                'barang.satuan:id,nama',
                'penerima:id,name',
                'penerimaanUnit',
            ])
            ->orderByDesc('diterima_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (TransferBarang $t): array {
                $penerimaan = $t->penerimaanUnit;
                $receivedQty = (int) ($penerimaan?->qty_diterima ?? $t->qty_diterima);
                $damagedQty = (int) ($penerimaan?->qty_rusak ?? 0);
                $satuanName = $t->barang?->satuan?->nama ?? 'pcs';

                return [
                    'id' => (int) $t->id,
                    'nomor_sj' => (string) $t->nomor_sj,
                    'nomor_pr' => $t->permintaanBarang?->nomor_pr,
                    'tanggal_terima' => ($t->diterima_at ?? $t->updated_at)?->format('d M Y, H:i') ?? '—',
                    'gudang_asal' => $t->gudangAsal?->nama ?? 'Gudang Store Pusat',
                    'barang' => $t->barang?->nama ?? '—',
                    'qty_dikirim' => (int) $t->qty_dikirim,
                    'qty_diterima' => $receivedQty,
                    'qty_rusak' => $damagedQty,
                    'satuan' => $satuanName,
                    'catatan' => $penerimaan?->catatan_discrepancy ?? '',
                    'supir' => $t->supir_pengantar ?? '',
                    'penerima' => $t->penerima?->name ?? 'Petugas Gudang',
                ];
            })
            ->all();
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

        if ($hasOpenTransfer) {
            return;
        }

        $permintaan->update(['status' => 'selesai']);
    }

    /**
     * @return array{
     *     pengeluarans: array<int, array<string, mixed>>,
     *     barangs: Collection<int, Barang>,
     *     gudangUnits: Collection<int, Gudang>,
     *     activeGudang: ?Gudang,
     *     isStore: bool,
     *     transactions: array<int, array<string, mixed>>,
     *     knownRequesters: array<int, string>
     * }
     */
    public function getPengeluaranPageData(User $user, int|string|null $selectedGudangId): array
    {
        $context = $this->getGudangContext($user, $selectedGudangId);
        $activeGudang = $context['activeGudang'];

        $pengeluaranQuery = $activeGudang === null
            ? null
            : PengeluaranUnit::query()
                ->where('gudang_id', $activeGudang->getKey())
                ->with(['details.barang', 'pencatat', 'verifier'])
                ->orderByDesc('tanggal')
                ->orderByDesc('waktu_keluar')
                ->orderByDesc('id')
                ->limit(100)
                ->get();

        $transactions = $pengeluaranQuery === null
            ? []
            : $pengeluaranQuery
                ->map(fn (PengeluaranUnit $transaction): array => $this->mapIssueRecord($transaction))
                ->all();

        /** @var array<int, string> $knownRequesters */
        $knownRequesters = $activeGudang === null
            ? []
            : DivisiPeminta::query()
                ->where('gudang_id', $activeGudang->getKey())
                ->where('is_aktif', true)
                ->orderBy('nama')
                ->pluck('nama')
                ->merge(PengeluaranUnit::query()
                    ->where('gudang_id', $activeGudang->getKey())
                    ->distinct()
                    ->orderBy('bagian_peminta')
                    ->pluck('bagian_peminta')
                )
                ->filter(static fn (string $name): bool => trim($name) !== '')
                ->unique(static fn (string $name): string => mb_strtolower(trim($name)))
                ->sort()
                ->values()
                ->all();

        return [
            'pengeluarans' => [],
            'barangs' => $this->barangOptions($activeGudang),
            'dynamicItems' => $this->barangOptionsWithStock($activeGudang),
            'gudangUnits' => $context['gudangUnits'],
            'activeGudang' => $activeGudang,
            'isStore' => false,
            'transactions' => $transactions,
            'knownRequesters' => $knownRequesters,
            'returnDepartmentOptions' => $activeGudang instanceof Gudang
                ? $this->returnDepartmentOptionsForGudang($activeGudang)
                : [],
            'initialReturnRecords' => $this->getRecentReturns($activeGudang),
        ];
    }

    public function quickAddRequester(User $user, int|string|null $selectedGudangId, string $name): DivisiPeminta
    {
        $context = $this->getGudangContext($user, $selectedGudangId);
        $activeGudang = $context['activeGudang'];
        abort_unless($activeGudang instanceof Gudang, 403, 'Pilih gudang unit aktif yang ditugaskan kepada Anda.');

        return DB::transaction(function () use ($activeGudang, $name): DivisiPeminta {
            $lockedGudang = Gudang::query()
                ->whereKey($activeGudang->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            return $this->divisiPemintaService->findOrCreateForRecording($lockedGudang, $name);
        }, attempts: 3);
    }

    /**
     * @param  array{
     *     tanggal: string,
     *     waktu_keluar: string,
     *     entry_mode: string,
     *     bagian_peminta: string,
     *     catatan?: ?string,
     *     items: array<int, array{barang_id: int, qty: int|float|string, catatan?: ?string}>
     * }  $validated
     * @return array<string, mixed>
     */
    public function storePengeluaran(
        User $user,
        int|string|null $selectedGudangId,
        array $validated,
    ): array {
        $context = $this->getGudangContext($user, $selectedGudangId);
        $activeGudang = $context['activeGudang'];
        abort_unless($activeGudang, 403, 'Pilih gudang unit aktif yang ditugaskan kepada Anda.');

        $barangIds = collect($validated['items'])
            ->pluck('barang_id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->unique()
            ->values();
        $barangs = Barang::query()
            ->where('gudang_id', $activeGudang->getKey())
            ->where('is_aktif', true)
            ->whereKey($barangIds)
            ->with(['kategori:id,nama', 'satuan:id,nama'])
            ->get()
            ->keyBy('id');

        if ($barangs->count() !== $barangIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'Barang yang dipilih tidak tersedia di gudang aktif.',
            ]);
        }

        $transaction = DB::transaction(function () use ($activeGudang, $barangs, $barangIds, $validated, $user): PengeluaranUnit {
            $lockedGudang = Gudang::query()
                ->whereKey($activeGudang->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $area = $this->divisiPemintaService->findOrCreateForRecording(
                $lockedGudang,
                trim($validated['bagian_peminta']),
            );
            $department = $area->nama;
            $normalizedDepartment = mb_strtolower($department);

            $transaction = PengeluaranUnit::query()
                ->where('gudang_id', $lockedGudang->getKey())
            ->where('tanggal', $validated['tanggal'])
                ->whereRaw('LOWER(bagian_peminta) = ?', [$normalizedDepartment])
                ->where('status_verifikasi', 'menunggu_verifikasi')
                ->lockForUpdate()
                ->first();

            if ($transaction === null) {
                $identifier = strtoupper(preg_replace(
                    '/[^A-Z0-9]/',
                    '',
                    (string) ($lockedGudang->kode ?: $lockedGudang->nama),
                ) ?? '');
                $unitPrefix = match (true) {
                    str_contains($identifier, 'SUKAHATI') => 'SHT',
                    str_contains($identifier, 'TAMANSARI') => 'TMS',
                    default => substr($identifier, 0, 3) ?: 'UNT',
                };
                $tanggal = $validated['tanggal'];
                $dateNum = str_replace('-', '', (string) $tanggal);
                $nomorPrefix = sprintf(
                    'OUT-%s-%s001-',
                    $unitPrefix,
                    $dateNum,
                );
                $sequence = PengeluaranUnit::query()
                    ->where('gudang_id', $lockedGudang->getKey())
                    ->where('tanggal', $tanggal)
                    ->count() + 1;
                $usedNumbers = PengeluaranUnit::query()
                    ->where('nomor_bpb', 'like', $nomorPrefix.'%')
                    ->pluck('nomor_bpb')
                    ->flip();
                $nomorBpb = sprintf('%s%04d', $nomorPrefix, $sequence);

                while (isset($usedNumbers[$nomorBpb])) {
                    $sequence++;
                    $nomorBpb = sprintf('%s%04d', $nomorPrefix, $sequence);
                }

                $transaction = PengeluaranUnit::query()->create([
                    'nomor_bpb' => $nomorBpb,
                    'gudang_id' => $lockedGudang->getKey(),
                    'tanggal' => $tanggal,
                    'waktu_keluar' => $validated['waktu_keluar'],
                    'bagian_peminta' => $department,
                    'divisi_peminta_id' => $area->getKey(),
                    'catatan' => $validated['catatan'] ?? null,
                    'entry_mode' => $validated['entry_mode'],
                    'status_verifikasi' => 'menunggu_verifikasi',
                    'dicatat_oleh' => $user->getKey(),
                ]);
            } else {
                $transaction->update([
                    'waktu_keluar' => $validated['waktu_keluar'],
                    'bagian_peminta' => $department,
                    'divisi_peminta_id' => $area->getKey(),
                    'catatan' => $this->appendNote($transaction->catatan, $validated['catatan'] ?? null),
                ]);
            }

            $detailsByItemAndUnit = [];
            foreach ($transaction->details()
                ->whereIn('barang_id', $barangIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get() as $existingDetail) {
                $key = $existingDetail->barang_id.'|'.$existingDetail->satuan;
                $detailsByItemAndUnit[$key] ??= $existingDetail;
            }

            foreach ($validated['items'] as $item) {
                $barang = $barangs->get((int) $item['barang_id']);
                if (! $barang instanceof Barang) {
                    throw ValidationException::withMessages([
                        'items' => 'Barang yang dipilih tidak tersedia di gudang aktif.',
                    ]);
                }

                $satuan = $barang->satuan?->nama ?? 'unit';
                $detailKey = $barang->getKey().'|'.$satuan;
                $detail = $detailsByItemAndUnit[$detailKey] ?? null;
                $quantity = round((float) $item['qty'], 3);

                if ($detail instanceof PengeluaranUnitDetail) {
                    $hpp = (float) $detail->harga_hpp;
                    if ($hpp <= 0) {
                        $hpp = (float) $barang->harga_satuan;
                    }

                    $newQuantity = round((float) $detail->qty + $quantity, 3);
                    $returnedQuantity = (float) ($detail->qty_kembali ?? 0);

                    $detail->update([
                        'qty' => $newQuantity,
                        'qty_keluar_dilaporkan' => $newQuantity,
                        'qty_keluar_rekonsiliasi' => null,
                        'qty_kembali_rekonsiliasi' => null,
                        'harga_hpp' => $hpp,
                        'subtotal_nilai' => round(max(0.0, $newQuantity - $returnedQuantity) * $hpp, 2),
                        'catatan' => $this->appendNote($detail->catatan, $item['catatan'] ?? null),
                    ]);

                    continue;
                }

                $hpp = (float) $barang->harga_satuan;

                $detail = $transaction->details()->create([
                    'barang_id' => $barang->getKey(),
                    'kode_barang' => $barang->kode,
                    'nama_barang' => $barang->nama,
                    'kategori' => $barang->kategori?->nama,
                    'satuan' => $satuan,
                    'qty' => $quantity,
                    'qty_keluar_dilaporkan' => $quantity,
                    'qty_keluar_rekonsiliasi' => null,
                    'qty_kembali_rekonsiliasi' => null,
                    'harga_hpp' => $hpp,
                    'subtotal_nilai' => round($quantity * $hpp, 2),
                    'catatan' => $item['catatan'] ?? null,
                ]);
                $detailsByItemAndUnit[$detailKey] = $detail;
            }

            return $transaction->load(['details', 'pencatat']);
        }, attempts: 3);

        return $this->mapIssueRecord($transaction);
    }

    /**
     * @return array{opnames: array<int, array<string, mixed>>}
     */
    public function getOpnamePageData(): array
    {
        return [
            'opnames' => [
                [
                    'id' => 'opn-01',
                    'periode' => '2026-09-16',
                    'jenis' => 'harian',
                    'recording' => 'Budi Santoso',
                    'items_count' => 24,
                    'status' => 'submitted',
                    'status_badge' => 'badge-warning',
                    'status_label' => 'Menunggu Verifikasi Admin GU',
                ],
                [
                    'id' => 'opn-02',
                    'periode' => '2026-09-15',
                    'jenis' => 'harian',
                    'recording' => 'Siti Nurhaliza',
                    'items_count' => 24,
                    'status' => 'finalized',
                    'status_badge' => 'badge-success',
                    'status_label' => 'Terverifikasi & Dikunci',
                ],
            ],
        ];
    }

    /**
     * @return array{returs: array<int, array<string, mixed>>}
     */
    public function getReturPageData(): array
    {
        return [
            'returs' => [
                [
                    'id' => 'rtr-001',
                    'tanggal' => '2026-09-16 15:30',
                    'barang' => 'Bumbu Marinasi Ayam Sisa Produksi',
                    'qty' => 5,
                    'satuan' => 'kg',
                    'kondisi' => 'baik',
                    'kondisi_label' => 'Baik (Stok Kembali Masuk)',
                    'petugas' => 'Budi Santoso',
                ],
                [
                    'id' => 'rtr-002',
                    'tanggal' => '2026-09-15 16:00',
                    'barang' => 'Plastik Vacuum Cacat Cetak',
                    'qty' => 30,
                    'satuan' => 'pcs',
                    'kondisi' => 'rusak',
                    'kondisi_label' => 'Rusak (Afval Produksi)',
                    'petugas' => 'Siti Nurhaliza',
                ],
            ],
        ];
    }

    /**
     * @return array{labels: array<int, array<string, string>>}
     */
    public function getLabelPageData(): array
    {
        return [
            'labels' => [
                [
                    'id' => 'lbl-001',
                    'nomor_seri' => 'LBL-20260916-001',
                    'pelanggan' => 'PT Resto Kuliner Sejahtera',
                    'produk' => 'Daging Ayam Parting Frozen 10 Cut',
                    'tgl_produksi' => '2026-09-16',
                    'tgl_expired' => '2026-12-16',
                    'kode_area' => 'AREA-A1 (Frozen Vault)',
                    'petugas' => 'Budi Santoso',
                ],
                [
                    'id' => 'lbl-002',
                    'nomor_seri' => 'LBL-20260916-002',
                    'pelanggan' => 'CV Supermarket Fresh',
                    'produk' => 'Ayam Karkas Segar 1.0 - 1.2 kg',
                    'tgl_produksi' => '2026-09-16',
                    'tgl_expired' => '2026-09-18',
                    'kode_area' => 'AREA-B2 (Fresh Chiller)',
                    'petugas' => 'Budi Santoso',
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function receiptPreviewMessage(array $validated): string
    {
        return 'Simulasi penerimaan barang masuk ('.$validated['jenis_masuk'].') selesai. Data belum disimpan ke database.';
    }

    public function opnamePreviewMessage(): string
    {
        return 'Simulasi input stok opname selesai. Data belum disimpan ke database.';
    }

    public function returPreviewMessage(): string
    {
        return 'Pencatatan pengembalian barang berhasil dicatat. Stok fisik gudang telah bertambah.';
    }

    /** @return array<int, array{id: int, code: string, name: string, category: string, unit: string, stock: int}> */
    public function barangOptionsWithStock(?Gudang $activeGudang): array
    {
        if ($activeGudang === null) {
            return [];
        }

        $barangs = Barang::query()
            ->select(['id', 'gudang_id', 'master_barang_id', 'kategori_id', 'satuan_id', 'kode', 'nama', 'minimum_stok'])
            ->with(['kategori:id,nama', 'satuan:id,nama'])
            ->where('gudang_id', $activeGudang->getKey())
            ->where('is_aktif', true)
            ->orderBy('nama')
            ->get();

        if ($barangs->isEmpty()) {
            return [];
        }

        $totals = $this->stokService->totals($barangs->pluck('id'), (int) $activeGudang->getKey());

        return $barangs->map(function (Barang $b) use ($totals) {
            $awal = (int) ($totals['awal'][$b->id] ?? 0);
            $masuk = $awal + (int) ($totals['masuk'][$b->id] ?? 0);
            $keluar = (int) ($totals['keluar'][$b->id] ?? 0);
            $stock = max(0, $masuk - $keluar);

            return [
                'id' => (int) $b->id,
                'code' => $b->kode,
                'name' => $b->nama,
                'category' => $b->kategori?->nama ?? 'Umum',
                'unit' => $b->satuan?->nama ?? 'pcs',
                'stock' => $stock,
            ];
        })->values()->all();
    }

    /** @return Collection<int, Barang> */
    private function barangOptions(?Gudang $activeGudang): Collection
    {
        if ($activeGudang === null) {
            return new Collection;
        }

        return Barang::query()
            ->select(['id', 'gudang_id', 'kategori_id', 'satuan_id', 'kode', 'nama'])
            ->with(['kategori:id,nama', 'satuan:id,nama'])
            ->where('gudang_id', $activeGudang->getKey())
            ->where('is_aktif', true)
            ->orderBy('nama')
            ->get();
    }

    /**
     * @return array<int, array{name: string, label: string}>
     */
    public function getReturnDepartmentOptions(User $user, int|string|null $selectedGudangId): array
    {
        $activeGudang = $this->resolveActiveGudang($user, $selectedGudangId);
        if (! $activeGudang instanceof Gudang) {
            return [];
        }

        return $this->returnDepartmentOptionsForGudang($activeGudang);
    }

    /**
     * @return array<int, array{name: string, label: string}>
     */
    private function returnDepartmentOptionsForGudang(Gudang $activeGudang): array
    {
        $transactions = PengeluaranUnit::query()
            ->where('gudang_id', $activeGudang->getKey())
            ->with(['details'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        $allDepts = DivisiPeminta::query()
            ->where('gudang_id', $activeGudang->getKey())
            ->where('is_aktif', true)
            ->pluck('nama')
            ->merge($transactions->pluck('bagian_peminta'))
            ->filter(static fn (?string $name): bool => trim((string) $name) !== '')
            ->unique(static fn (string $name): string => mb_strtolower(trim($name)))
            ->sort()
            ->values();

        $options = [];

        foreach ($allDepts as $deptName) {
            $deptKey = mb_strtolower(trim($deptName));
            $matchingTxs = $transactions->filter(static fn (PengeluaranUnit $tx): bool => mb_strtolower(trim((string) $tx->bagian_peminta)) === $deptKey);
            $openItemsCount = $matchingTxs
                ->flatMap(static fn (PengeluaranUnit $tx) => $tx->details)
                ->filter(static fn (PengeluaranUnitDetail $detail): bool => (float) $detail->qty > (float) $detail->qty_kembali)
                ->count();

            $options[] = [
                'name' => $deptName,
                'label' => $openItemsCount > 0
                    ? "{$deptName} — {$openItemsCount} baris belum dikembalikan"
                    : "{$deptName} — tidak ada sisa barang",
                'sortOrder' => $openItemsCount > 0 ? 1 : 2,
            ];
        }

        usort($options, static function (array $a, array $b): int {
            if ($a['sortOrder'] !== $b['sortOrder']) {
                return $a['sortOrder'] <=> $b['sortOrder'];
            }

            return strcasecmp($a['name'], $b['name']);
        });

        return $options;
    }

    /**
     * @return array<int, array{
     *     name: string,
     *     category: string,
     *     unit: string,
     *     totalTaken: float,
     *     totalReturned: float,
     *     availableToReturn: float,
     *     finalUsage: float,
     *     hpp: float
     * }>
     */
    public function getBorrowedItemsForDepartment(
        User $user,
        int|string|null $selectedGudangId,
        string $department,
    ): array {
        $activeGudang = $this->resolveActiveGudang($user, $selectedGudangId);
        if (! $activeGudang instanceof Gudang) {
            return [];
        }

        return $this->borrowedItemsForGudang($activeGudang, $department);
    }

    /**
     * @return array<int, array{
     *     name: string,
     *     category: string,
     *     unit: string,
     *     totalTaken: float,
     *     totalReturned: float,
     *     availableToReturn: float,
     *     finalUsage: float,
     *     hpp: float
     * }>
     */
    private function borrowedItemsForGudang(Gudang $activeGudang, string $department): array
    {
        $deptKey = mb_strtolower(trim($department));
        if ($deptKey === '') {
            return [];
        }

        $query = PengeluaranUnit::query()
            ->where('gudang_id', $activeGudang->getKey())
            ->whereRaw('LOWER(bagian_peminta) = ?', [$deptKey])
            ->with(['details.barang']);

        $transactions = $query->orderByDesc('tanggal')->get();

        $itemsMap = [];

        foreach ($transactions as $tx) {
            foreach ($tx->details as $detail) {
                $itemName = $detail->nama_barang;
                if (! isset($itemsMap[$itemName])) {
                    $itemsMap[$itemName] = [
                        'name' => $itemName,
                        'category' => $detail->kategori ?: 'Umum',
                        'unit' => $detail->satuan,
                        'totalTaken' => 0.0,
                        'totalReturned' => 0.0,
                        'hpp' => (float) ($detail->harga_hpp > 0 ? $detail->harga_hpp : ($detail->barang?->harga_satuan ?? 0)),
                        'detailIds' => [],
                    ];
                }

                $itemsMap[$itemName]['totalTaken'] = round($itemsMap[$itemName]['totalTaken'] + (float) $detail->qty, 3);
                $itemsMap[$itemName]['totalReturned'] = round($itemsMap[$itemName]['totalReturned'] + (float) $detail->qty_kembali, 3);
                $itemsMap[$itemName]['detailIds'][] = (int) $detail->id;
            }
        }

        $result = [];
        foreach ($itemsMap as $entry) {
            $available = max(0.0, round($entry['totalTaken'] - $entry['totalReturned'], 3));
            if ($available === 0.0) {
                continue;
            }

            $result[] = [
                'name' => $entry['name'],
                'category' => $entry['category'],
                'unit' => $entry['unit'],
                'totalTaken' => $entry['totalTaken'],
                'totalReturned' => $entry['totalReturned'],
                'availableToReturn' => $available,
                'finalUsage' => $available,
                'hpp' => $entry['hpp'],
            ];
        }

        return $result;
    }

    /**
     * @param array{
     *     bagian_peminta: string,
     *     tanggal_kembali: string,
     *     items: array<int, array{name: string, qty_kembali: float|int|string, catatan?: ?string}>
     * } $validated
     * @return array<string, mixed>
     */
    public function storePengembalian(
        User $user,
        int|string|null $selectedGudangId,
        array $validated,
    ): array {
        $context = $this->getGudangContext($user, $selectedGudangId);
        $activeGudang = $context['activeGudang'];
        abort_unless($activeGudang instanceof Gudang, 403, 'Pilih gudang unit aktif yang ditugaskan kepada Anda.');

        $department = trim($validated['bagian_peminta']);
        $deptKey = mb_strtolower($department);
        $tanggalKembali = $validated['tanggal_kembali'];

        return DB::transaction(function () use ($activeGudang, $deptKey, $department, $tanggalKembali, $validated, $user): array {
            $query = PengeluaranUnit::query()
                ->where('gudang_id', $activeGudang->getKey())
                ->whereRaw('LOWER(bagian_peminta) = ?', [$deptKey])
                ->with(['details.barang'])
                ->lockForUpdate();

            $transactions = $query->orderByDesc('tanggal')->get();
            if ($transactions->isEmpty()) {
                throw ValidationException::withMessages([
                    'bagian_peminta' => 'Tidak ditemukan riwayat pengeluaran untuk bagian peminta tersebut.',
                ]);
            }

            $summary = [];

            foreach ($validated['items'] as $item) {
                $itemName = trim((string) $item['name']);
                $qtyToReturn = round((float) $item['qty_kembali'], 3);
                if ($qtyToReturn <= 0) {
                    continue;
                }

                $remainingToReturn = $qtyToReturn;

                foreach ($transactions as $tx) {
                    if ($remainingToReturn <= 0) {
                        break;
                    }

                    $details = $tx->details->filter(static fn (PengeluaranUnitDetail $d): bool => strcasecmp(trim($d->nama_barang), $itemName) === 0);

                    foreach ($details as $detail) {
                        if ($remainingToReturn <= 0) {
                            break;
                        }

                        $availableOnDetail = max(0.0, round((float) $detail->qty - (float) $detail->qty_kembali, 3));
                        if ($availableOnDetail <= 0) {
                            continue;
                        }

                        $allocated = min($availableOnDetail, $remainingToReturn);

                        if (in_array($tx->status_verifikasi, ['terverifikasi', 'sesuai'], true)) {
                            $tx->update([
                                'status_verifikasi' => 'menunggu_verifikasi',
                                'diverifikasi_oleh' => null,
                                'diverifikasi_at' => null,
                                'catatan_verifikasi' => null,
                            ]);

                            foreach ($tx->details as $transactionDetail) {
                                $transactionDetail->update([
                                    'qty_keluar_rekonsiliasi' => null,
                                    'qty_kembali_rekonsiliasi' => null,
                                ]);
                            }

                            $tx->setAttribute('status_verifikasi', 'menunggu_verifikasi');
                        }

                        $newQtyKembali = round((float) $detail->qty_kembali + $allocated, 3);
                        $pemakaianRiil = max(0.0, round((float) $detail->qty - $newQtyKembali, 3));
                        $hpp = (float) $detail->harga_hpp;
                        if ($hpp <= 0) {
                            $hpp = (float) ($detail->barang?->harga_satuan ?? 0);
                        }
                        $newSubtotal = round($pemakaianRiil * $hpp, 2);

                        $detail->update([
                            'qty_kembali' => $newQtyKembali,
                            'qty_keluar_rekonsiliasi' => null,
                            'qty_kembali_rekonsiliasi' => null,
                            'harga_hpp' => $hpp,
                            'subtotal_nilai' => $newSubtotal,
                        ]);

                        PengembalianUnit::create([
                            'gudang_id' => $activeGudang->getKey(),
                            'pengeluaran_unit_id' => $tx->getKey(),
                            'pengeluaran_unit_detail_id' => $detail->getKey(),
                            'barang_id' => $detail->barang_id,
                            'bagian_peminta' => $department,
                            'nama_barang' => $itemName,
                            'satuan' => $detail->satuan,
                            'qty_kembali' => $allocated,
                            'tanggal_kembali' => $tanggalKembali,
                            'catatan' => $item['catatan'] ?? "Sisa pengembalian {$department} (BPB: {$tx->nomor_bpb})",
                            'dicatat_oleh' => $user->getKey(),
                        ]);

                        $remainingToReturn = round($remainingToReturn - $allocated, 3);
                    }
                }

                if ($remainingToReturn > 0) {
                    throw ValidationException::withMessages([
                        'items' => "Jumlah pengembalian {$itemName} melebihi sisa barang yang masih tercatat.",
                    ]);
                }

                $summary[] = "{$itemName} (+{$qtyToReturn})";
            }

            if (empty($summary)) {
                throw ValidationException::withMessages([
                    'items' => 'Masukkan angka pengembalian lebih dari 0.',
                ]);
            }

            $updatedTransactions = PengeluaranUnit::query()
                ->where('gudang_id', $activeGudang->getKey())
                ->with(['details.barang', 'pencatat', 'verifier'])
                ->orderByDesc('tanggal')
                ->orderByDesc('waktu_keluar')
                ->limit(100)
                ->get()
                ->map(fn (PengeluaranUnit $tx): array => $this->mapIssueRecord($tx))
                ->all();

            return [
                'message' => 'Pengembalian sisa barang berhasil dicatat dan stok gudang telah diperbarui: '.implode(', ', $summary),
                'summary' => $summary,
                'transactions' => $updatedTransactions,
                'borrowedItems' => $this->borrowedItemsForGudang($activeGudang, $department),
                'departmentOptions' => $this->returnDepartmentOptionsForGudang($activeGudang),
                'returnRecords' => $this->getRecentReturns($activeGudang),
            ];
        }, attempts: 3);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRecentReturns(?Gudang $activeGudang): array
    {
        if ($activeGudang === null) {
            return [];
        }

        return PengembalianUnit::query()
            ->where('gudang_id', $activeGudang->getKey())
            ->with(['pencatat', 'pengeluaranUnit'])
            ->orderByDesc('tanggal_kembali')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(static fn (PengembalianUnit $r): array => [
                'id' => (string) $r->id,
                'transactionId' => (int) $r->pengeluaran_unit_id,
                'department' => $r->bagian_peminta,
                'itemName' => $r->nama_barang,
                'qty' => (float) $r->qty_kembali,
                'unit' => $r->satuan,
                'condition' => 'Layak pakai (kembali)',
                'returnedAt' => $r->tanggal_kembali->format('Y-m-d').'T12:00:00.000Z',
                'recordedAt' => $r->created_at?->toIso8601String(),
                'note' => $r->catatan,
                'clerk' => $r->pencatat?->name ?? 'Recording GU',
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapIssueRecord(PengeluaranUnit $transaction): array
    {
        return [
            'id' => (int) $transaction->getKey(),
            'bpb' => $transaction->nomor_bpb,
            'date' => $transaction->tanggal->format('Y-m-d'),
            'occurredTime' => substr($transaction->waktu_keluar, 0, 5),
            'enteredAt' => $transaction->created_at?->toIso8601String(),
            'requester' => $transaction->bagian_peminta,
            'department' => $transaction->bagian_peminta,
            'enteredBy' => $transaction->pencatat?->name ?? 'Petugas Recording',
            'entrySource' => $transaction->entry_mode,
            'status' => match ($transaction->status_verifikasi) {
                'terverifikasi', 'sesuai' => 'Final',
                'ditolak' => 'Ditolak',
                'ada_selisih', 'perlu_revisi' => 'Ada Selisih',
                default => 'Menunggu Rekonsiliasi',
            },
            'verificationStatus' => $transaction->status_verifikasi,
            'verifiedAt' => $transaction->diverifikasi_at?->toIso8601String(),
            'note' => $transaction->catatan,
            'exceptions' => [],
            'items' => $transaction->details
                ->map(static function (PengeluaranUnitDetail $detail): array {
                    $qtyKeluar = (float) $detail->qty;
                    $qtyKembali = (float) ($detail->qty_kembali ?? 0);
                    $pemakaianRiil = max(0.0, round($qtyKeluar - $qtyKembali, 3));
                    $hpp = (float) ($detail->harga_hpp > 0 ? $detail->harga_hpp : ($detail->barang?->harga_satuan ?? 0));
                    $subtotal = round($pemakaianRiil * $hpp, 2);

                    return [
                        'id' => (int) $detail->id,
                        'name' => $detail->nama_barang,
                        'code' => $detail->kode_barang,
                        'category' => $detail->kategori,
                        'qty' => $qtyKeluar,
                        'qty_kembali' => $qtyKembali,
                        'pemakaian_riil' => $pemakaianRiil,
                        'unit' => $detail->satuan,
                        'stock' => 0,
                        'hpp' => $hpp,
                        'subtotal' => $subtotal,
                        'exception' => '',
                        'note' => $detail->catatan,
                        'logs' => [],
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    private function appendNote(?string $currentNote, ?string $newNote): ?string
    {
        $newNote = trim((string) $newNote);
        if ($newNote === '') {
            return $currentNote;
        }

        $currentNote = trim((string) $currentNote);

        return $currentNote === '' ? $newNote : $currentNote.'; '.$newNote;
    }
}
