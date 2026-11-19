<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Jobs\GenerateVectorEmbedding;
use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\PenerimaanBarangStore;
use App\Models\PenerimaanBarangStoreDetail;
use App\Models\Satuan;
use App\Services\SemanticVectorSearch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class PenerimaanStoreService
{
    public function __construct(
        private SemanticVectorSearch $semanticSearch,
    ) {}

    /**
     * Prepare listing, options, and filter values for the Store Penerimaan index page.
     *
     * @param  array{
     *     search?: ?string,
     *     supplier?: ?string,
     *     date_from?: ?string,
     *     date_to?: ?string
     * }  $filters
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function getListingData(Gudang $gudangStore, array $filters, int $perPage = 20): array
    {
        $supplier = trim($filters['supplier'] ?? '');
        $search = trim($filters['search'] ?? '');
        $today = today();

        $firstReceiptDate = PenerimaanBarangStore::query()
            ->where('gudang_id', $gudangStore->id)
            ->min('tanggal_diterima');

        $dateFrom = $filters['date_from']
            ?? ($firstReceiptDate ? Carbon::parse($firstReceiptDate)->toDateString() : $today->copy()->startOfYear()->toDateString());
        $dateTo = $filters['date_to'] ?? $today->toDateString();

        if ($dateTo < $dateFrom) {
            throw ValidationException::withMessages([
                'date_to' => 'Tanggal akhir tidak boleh lebih awal dari tanggal awal.',
            ]);
        }

        $now = now();
        $nomorRcvDatePrefix = $this->receiptNumberDatePrefix($now);
        $receiptNumberOptions = $this->receiptNumberOptions(
            PenerimaanBarangStore::query()
                ->whereDate('tanggal_diterima', $today->toDateString())
                ->get(['nomor_penerimaan', 'sumber_supplier'])
                ->all(),
            $now,
        );

        $receiptQuery = PenerimaanBarangStore::query()
            ->where('gudang_id', $gudangStore->id);

        if ($supplier !== '') {
            $receiptQuery->where('sumber_supplier', $supplier);
        }

        $receiptQuery
            ->whereDate('tanggal_diterima', '>=', $dateFrom)
            ->whereDate('tanggal_diterima', '<=', $dateTo);

        if ($search !== '') {
            $this->semanticSearch->apply($receiptQuery, 'penerimaan_barang_stores', $search, 'search');
        }

        $penerimaans = $receiptQuery
            ->with(['details.barang.kategori', 'details.satuan', 'barang.kategori', 'satuan', 'penerima', 'gudang'])
            ->orderByDesc('tanggal_diterima')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $suppliers = PenerimaanBarangStore::query()
            ->where('gudang_id', $gudangStore->id)
            ->whereNotNull('sumber_supplier')
            ->where('sumber_supplier', '<>', '')
            ->distinct()
            ->orderBy('sumber_supplier')
            ->pluck('sumber_supplier');

        $barangs = Barang::with(['kategori', 'satuan'])
            ->where('gudang_id', $gudangStore->id)
            ->where('is_aktif', true)
            ->orderBy('nama')
            ->get();

        $satuans = Satuan::where('is_aktif', true)->orderBy('nama')->get();
        $kategoriList = KategoriBarang::where('gudang_id', $gudangStore->id)
            ->where('is_aktif', true)
            ->orderBy('nama')
            ->get();

        return [
            'penerimaans' => $penerimaans,
            'barangs' => $barangs,
            'satuans' => $satuans,
            'kategoriList' => $kategoriList,
            'nomorRcvDatePrefix' => $nomorRcvDatePrefix,
            'receiptSupplierPrefixes' => $receiptNumberOptions['supplierPrefixes'],
            'receiptNextSequence' => $receiptNumberOptions['nextSequence'],
            'receiptUsedTokens' => $receiptNumberOptions['usedTokens'],
            'suppliers' => $suppliers,
            'supplier' => $supplier,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }

    /**
     * Persist goods receipt headers and line items into database.
     *
     * @param  array<string, mixed>  $validated
     * @param  array<int, array<string, mixed>>  $sourceGroups
     * @return array{numbers: array<int, string>, item_count: int}
     *
     * @throws ValidationException
     */
    public function createReceipts(
        array $validated,
        array $sourceGroups,
        Gudang $gudangStore,
        ?string $fotoPath,
        int $userId
    ): array {
        $itemCount = array_sum(array_map(
            static fn (array $group): int => count($group['items']),
            $sourceGroups,
        ));

        return DB::transaction(function () use ($validated, $sourceGroups, $gudangStore, $fotoPath, $userId, $itemCount): array {
            // RCV tokens are date-wide, so serialize allocation across store warehouses.
            Gudang::query()->orderBy('id')->lockForUpdate()->get(['id']);

            $date = Carbon::parse($validated['tanggal_diterima']);
            $existingReceipts = PenerimaanBarangStore::query()
                ->whereDate('tanggal_diterima', $date->toDateString())
                ->get(['nomor_penerimaan', 'sumber_supplier']);

            $prefixesBySupplier = $this->validateReceiptPrefixesForGroups($sourceGroups, $existingReceipts->all(), $date);
            $sequence = $this->receiptNumberOptions($existingReceipts->all(), $date)['nextSequence'];
            $nomorPenerimaanList = [];

            foreach ($sourceGroups as $sourceGroup) {
                $items = $sourceGroup['items'];
                $firstItem = $items[0];
                $nomorRcvPrefix = $prefixesBySupplier[$sourceGroup['sumber_supplier']];
                $nomorPenerimaan = $nomorRcvPrefix.sprintf('%04d', $sequence++);

                if ($nomorPenerimaan !== $sourceGroup['nomor_penerimaan_preview']) {
                    throw ValidationException::withMessages([
                        'source_groups' => 'Nomor RCV berubah karena penerimaan lain baru saja disimpan. Muat ulang halaman sebelum mencoba lagi.',
                    ]);
                }

                $totalQty = array_sum(array_column($items, 'qty_diterima'));

                $penerimaanHeader = PenerimaanBarangStore::create([
                    'nomor_penerimaan' => $nomorPenerimaan,
                    'gudang_id' => $gudangStore->id,
                    'barang_id' => $firstItem['barang_id'],
                    'qty_diterima' => $totalQty,
                    'satuan_id' => $firstItem['satuan_id'],
                    'po_referensi' => null,
                    'sumber_supplier' => $sourceGroup['sumber_supplier'],
                    'pemesan' => $sourceGroup['pemesan'],
                    'pengambil_barang' => $sourceGroup['pengambil_barang'],
                    'metode_pembayaran' => $sourceGroup['metode_pembayaran'],
                    'foto_dokumen_path' => $fotoPath,
                    'tanpa_surat_jalan' => $sourceGroup['tanpa_surat_jalan'],
                    'alasan_tanpa_sj' => $sourceGroup['tanpa_surat_jalan'] ? $sourceGroup['alasan_tanpa_sj'] : null,
                    'catatan' => $validated['catatan'] ?? null,
                    'catatan_rekap' => $sourceGroup['catatan_rekap'],
                    'source_groups' => [$sourceGroup],
                    'diterima_oleh' => $userId,
                    'tanggal_diterima' => $validated['tanggal_diterima'],
                ]);

                foreach ($items as $item) {
                    PenerimaanBarangStoreDetail::create([
                        'penerimaan_barang_store_id' => $penerimaanHeader->id,
                        'barang_id' => $item['barang_id'],
                        'qty_diterima' => (int) $item['qty_diterima'],
                        'qty_rusak' => in_array(($item['kondisi'] ?? 'Baik'), ['Rusak', 'Rusak dan Kurang', 'Rusak dan Lebih'], true) ? (int) ($item['qty_rusak'] ?? 0) : 0,
                        'qty_kurang' => in_array(($item['kondisi'] ?? 'Baik'), ['Kurang', 'Rusak dan Kurang'], true) ? (int) ($item['qty_kurang'] ?? 0) : 0,
                        'qty_lebih' => in_array(($item['kondisi'] ?? 'Baik'), ['Lebih', 'Rusak dan Lebih'], true) ? (int) ($item['qty_lebih'] ?? 0) : 0,
                        'satuan_id' => $item['satuan_id'],
                        'kondisi' => $item['kondisi'] ?? 'Baik',
                        'pemakai' => $item['pemakai'] ?? null,
                        'harga_satuan' => (float) ($item['harga_satuan'] ?? 0),
                        'total_harga' => (float) ($item['qty_diterima'] ?? 0) * (float) ($item['harga_satuan'] ?? 0),
                        'catatan' => $item['catatan'] ?? null,
                    ]);
                }

                GenerateVectorEmbedding::dispatch($penerimaanHeader, $penerimaanHeader->getSemanticText())
                    ->afterCommit();

                $nomorPenerimaanList[] = $nomorPenerimaan;
            }

            return [
                'numbers' => $nomorPenerimaanList,
                'item_count' => $itemCount,
            ];
        });
    }

    /**
     * Normalize raw request input arrays into structured source groups.
     *
     * @param  Request|array<string, mixed>  $input
     * @return array<int, array<string, mixed>>
     */
    public function normalizeSourceGroups(
        Request|array $input,
        bool $tanpaSj = false,
        ?string $alasanTanpaSj = null,
        array $legacyItems = []
    ): array {
        if ($input instanceof Request) {
            $tanpaSj = $input->boolean('tanpa_surat_jalan');
            $alasanTanpaSj = $input->input('alasan_tanpa_sj');
            $legacyItems = $input->input('items', []);
            $data = $input->all();
        } else {
            $data = $input;
        }

        $sourceGroups = $data['source_groups'] ?? null;

        if (is_array($sourceGroups) && count($sourceGroups) > 0) {
            $sourceGroups = array_values(array_filter($sourceGroups, fn ($group) => is_array($group)));

            foreach ($sourceGroups as $index => &$group) {
                if (! isset($group['items']) && $index === 0 && count($legacyItems) > 0) {
                    $group['items'] = $legacyItems;
                }

                $group['tanpa_surat_jalan'] = $this->normalizeSuratJalanState(
                    $group['tanpa_surat_jalan'] ?? ($index === 0 ? $tanpaSj : false),
                );
                $group['alasan_tanpa_sj'] = $group['alasan_tanpa_sj'] ?? ($index === 0 ? $alasanTanpaSj : null);
                $group['items'] = $this->normalizeReceiptItems($group['items'] ?? []);
            }
            unset($group);

            return $sourceGroups;
        }

        return [[
            'po_referensi' => $data['po_referensi'] ?? null,
            'sumber_supplier' => $data['sumber_supplier'] ?? null,
            'pemesan' => $data['pemesan'] ?? null,
            'pengambil_barang' => $data['pengambil_barang'] ?? null,
            'metode_pembayaran' => $data['metode_pembayaran'] ?? 'Tunai',
            'catatan_rekap' => $data['catatan_rekap'] ?? null,
            'tanpa_surat_jalan' => $tanpaSj ? 'belum_ada' : 'ada',
            'alasan_tanpa_sj' => $alasanTanpaSj,
            'items' => $this->normalizeReceiptItems($data['items'] ?? $legacyItems),
        ]];
    }

    /**
     * Filter out duplicate barang IDs inside one supplier group.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, string>
     */
    public function duplicateBarangIds(array $items): array
    {
        $counts = [];

        foreach ($items as $item) {
            $barangId = trim((string) ($item['barang_id'] ?? ''));
            if ($barangId !== '') {
                $counts[$barangId] = ($counts[$barangId] ?? 0) + 1;
            }
        }

        return array_map(
            'strval',
            array_keys(array_filter($counts, fn (int $count): bool => $count > 1)),
        );
    }

    /**
     * Build receipt validation rules that depend on item condition and supplier grouping.
     *
     * @param  array<int, array<string, mixed>>  $sourceGroups
     * @return array<string, array<int, mixed>>
     */
    public function conditionalValidationRules(array $sourceGroups): array
    {
        $rules = [];

        foreach ($sourceGroups as $groupIndex => $group) {
            if (($group['tanpa_surat_jalan'] ?? 'ada') === 'belum_ada') {
                $rules["source_groups.{$groupIndex}.alasan_tanpa_sj"] = ['required', 'string', 'min:3', 'max:1000'];
            }

            $groupItems = is_array($group['items'] ?? null) ? $group['items'] : [];
            $duplicateBarangIds = $this->duplicateBarangIds($groupItems);

            foreach ($groupItems as $itemIndex => $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (in_array((string) ($item['barang_id'] ?? ''), $duplicateBarangIds, true)) {
                    $rules["source_groups.{$groupIndex}.items.{$itemIndex}.barang_id"][] =
                        static function (string $attribute, mixed $value, \Closure $fail): void {
                            $fail('Barang yang sama hanya boleh dimasukkan satu kali dalam supplier ini.');
                        };
                }

                $condition = $item['kondisi'] ?? 'Baik';
                $quantity = max(0, (int) ($item['qty_diterima'] ?? 0));

                if (in_array($condition, ['Rusak', 'Rusak dan Kurang', 'Rusak dan Lebih'], true)) {
                    $rules["source_groups.{$groupIndex}.items.{$itemIndex}.qty_rusak"] = [
                        'required',
                        'integer',
                        'min:1',
                        'max:'.$quantity,
                    ];
                }

                if (in_array($condition, ['Kurang', 'Rusak dan Kurang'], true)) {
                    $rules["source_groups.{$groupIndex}.items.{$itemIndex}.qty_kurang"] = [
                        'required',
                        'integer',
                        'min:1',
                    ];
                }

                if (in_array($condition, ['Lebih', 'Rusak dan Lebih'], true)) {
                    $rules["source_groups.{$groupIndex}.items.{$itemIndex}.qty_lebih"] = [
                        'required',
                        'integer',
                        'min:1',
                        'max:'.$quantity,
                    ];
                }
            }
        }

        return $rules;
    }

    /**
     * Keep only fields accepted by the receipt workflow before persistence.
     *
     * @param  array<string, mixed>  $validated
     * @return array<int, array<string, mixed>>
     */
    public function sanitizeSourceGroups(bool $tanpaSj, array $validated): array
    {
        return array_map(function (array $group) use ($tanpaSj, $validated): array {
            $itemFields = [
                'barang_id',
                'qty_diterima',
                'qty_rusak',
                'qty_kurang',
                'qty_lebih',
                'satuan_id',
                'harga_satuan',
                'total_harga',
                'kondisi',
                'pemakai',
                'catatan',
            ];
            $items = array_map(
                fn (array $item): array => array_intersect_key($item, array_flip($itemFields)),
                $group['items']
            );

            return [
                'sumber_supplier' => $group['sumber_supplier'] ?? null,
                'nomor_rcv_prefix' => $group['nomor_rcv_prefix'],
                'nomor_penerimaan_preview' => $group['nomor_penerimaan_preview'],
                'pemesan' => $group['pemesan'],
                'pengambil_barang' => $group['pengambil_barang'],
                'metode_pembayaran' => $group['metode_pembayaran'],
                'catatan_rekap' => $group['catatan_rekap'] ?? null,
                'tanpa_surat_jalan' => ($group['tanpa_surat_jalan'] ?? ($tanpaSj ? 'belum_ada' : 'ada')) === 'belum_ada',
                'alasan_tanpa_sj' => $group['alasan_tanpa_sj'] ?? ($tanpaSj ? ($validated['alasan_tanpa_sj'] ?? null) : null),
                'items' => $items,
            ];
        }, $validated['source_groups']);
    }

    public function receiptNumberDatePrefix(Carbon $date): string
    {
        return 'RCV-STR-'.$date->format('dmY');
    }

    /**
     * @param  array<int, object{nomor_penerimaan: string, sumber_supplier: ?string}>  $existingReceipts
     * @return array{
     *     supplierPrefixes: array<string, string>,
     *     nextSequence: int,
     *     usedTokens: array<int, string>
     * }
     */
    public function receiptNumberOptions(array $existingReceipts, Carbon $date): array
    {
        $datePrefix = $this->receiptNumberDatePrefix($date);
        $numberPattern = '/^'.preg_quote($datePrefix, '/').'(\d{3})-(\d{4})$/';
        $tokensBySupplier = [];
        $supplierByToken = [];
        $usedTokens = [];
        $lastSequence = 0;

        foreach ($existingReceipts as $receipt) {
            if (! preg_match($numberPattern, (string) $receipt->nomor_penerimaan, $matches)) {
                continue;
            }

            $supplier = (string) $receipt->sumber_supplier;
            $token = $matches[1];
            $usedTokens[$token] = true;
            $tokensBySupplier[$supplier][$token] = true;
            $supplierByToken[$token][$supplier] = true;
            $lastSequence = max($lastSequence, (int) $matches[2]);
        }

        $supplierPrefixes = [];
        foreach ($tokensBySupplier as $supplier => $tokens) {
            foreach (array_keys($tokens) as $existingToken) {
                if (count($supplierByToken[$existingToken]) === 1) {
                    $supplierPrefixes[$supplier] = $datePrefix.$existingToken.'-';
                    break;
                }
            }
        }

        return [
            'supplierPrefixes' => $supplierPrefixes,
            'nextSequence' => $lastSequence + 1,
            'usedTokens' => array_map('strval', array_keys($usedTokens)),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $sourceGroups
     * @param  array<int, object{nomor_penerimaan: string, sumber_supplier: ?string}>  $existingReceipts
     * @return array<string, string>
     *
     * @throws ValidationException
     */
    public function validateReceiptPrefixesForGroups(array $sourceGroups, array $existingReceipts, Carbon $date): array
    {
        $datePrefix = $this->receiptNumberDatePrefix($date);
        $numberPattern = '/^'.preg_quote($datePrefix, '/').'(\d{3})-(\d{4})$/';
        $supplierByToken = [];
        $usedTokens = [];

        foreach ($existingReceipts as $receipt) {
            if (preg_match($numberPattern, (string) $receipt->nomor_penerimaan, $matches)) {
                $supplierByToken[$matches[1]][(string) $receipt->sumber_supplier] = true;
                $usedTokens[$matches[1]] = true;
            }
        }

        $prefixesBySupplier = [];
        foreach ($sourceGroups as $sourceGroup) {
            $supplier = (string) $sourceGroup['sumber_supplier'];
            $prefix = (string) $sourceGroup['nomor_rcv_prefix'];
            preg_match('/(\d{3})-$/', $prefix, $matches);
            $token = $matches[1] ?? '';

            if (isset($prefixesBySupplier[$supplier]) && $prefixesBySupplier[$supplier] !== $prefix) {
                throw ValidationException::withMessages([
                    'source_groups' => 'Supplier yang sama harus menggunakan nomor RCV yang sama.',
                ]);
            }

            $tokenOwners = $supplierByToken[$token] ?? [];
            if ($tokenOwners !== [] && (count($tokenOwners) !== 1 || ! isset($tokenOwners[$supplier]))) {
                throw ValidationException::withMessages([
                    'source_groups' => '3 digit acak nomor RCV sudah digunakan supplier lain. Muat ulang halaman untuk membuat nomor baru.',
                ]);
            }

            if ($tokenOwners === [] && isset($usedTokens[$token])) {
                throw ValidationException::withMessages([
                    'source_groups' => '3 digit acak nomor RCV sudah digunakan. Muat ulang halaman untuk membuat nomor baru.',
                ]);
            }

            $supplierByToken[$token][$supplier] = true;
            $usedTokens[$token] = true;
            $prefixesBySupplier[$supplier] = $prefix;
        }

        return $prefixesBySupplier;
    }

    private function normalizeReceiptItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, function ($item): bool {
            return ! is_array($item) || ! $this->isBlankReceiptItem($item);
        }));
    }

    private function normalizeSuratJalanState(mixed $value): mixed
    {
        return match (strtolower(trim((string) $value))) {
            'belum_ada', '1', 'true', 'on', 'yes' => 'belum_ada',
            'ada', '0', 'false', 'off', 'no', '' => 'ada',
            default => $value,
        };
    }

    private function isBlankReceiptItem(array $item): bool
    {
        return blank($item['barang_id'] ?? null)
            && blank($item['satuan_id'] ?? null)
            && $this->isZeroOrEmpty($item['qty_diterima'] ?? null)
            && in_array($item['kondisi'] ?? 'Baik', ['', 'Baik'], true)
            && $this->isZeroOrEmpty($item['qty_rusak'] ?? null)
            && $this->isZeroOrEmpty($item['qty_kurang'] ?? null)
            && $this->isZeroOrEmpty($item['qty_lebih'] ?? null)
            && $this->isZeroOrEmpty($item['harga_satuan'] ?? null)
            && $this->isZeroOrEmpty($item['total_harga'] ?? null)
            && blank($item['pemakai'] ?? null)
            && blank($item['catatan'] ?? null);
    }

    private function isZeroOrEmpty(mixed $value): bool
    {
        return blank($value) || (is_numeric($value) && (float) $value === 0.0);
    }
}
