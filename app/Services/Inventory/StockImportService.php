<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Imports\ColumnMappedRowsImport;
use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\MasterBarang;
use App\Models\Satuan;
use App\Models\StokAwal;
use App\Services\MasterData\MasterBarangService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;

final readonly class StockImportService
{
    public function __construct(
        private MasterBarangService $masterBarangService,
    ) {}

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
     * Process validated import rows in a transaction.
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
                                throw new RuntimeException("Kode '{$kode}' sudah digunakan oleh barang '{$barangByKode->nama}'.");
                            }
                            if ($barangByNama && strtoupper(trim($barangByNama->kode)) !== $kode) {
                                throw new RuntimeException("Nama '{$nama}' sudah digunakan dengan kode '{$barangByNama->kode}'.");
                            }

                            $masterConflict = $this->importMasterConflictMessage(
                                $kode,
                                $nama,
                                $masterIndexes['by_code'],
                                $masterIndexes['by_name'],
                                $barang?->master_barang_id === null ? null : (int) $barang->master_barang_id,
                            );

                            if ($masterConflict !== null) {
                                throw new RuntimeException($masterConflict);
                            }

                            if ($barang && isset($existingStokAwalIds[(int) $barang->id])) {
                                throw new RuntimeException('Stok awal untuk barang ini sudah pernah tercatat di gudang Store.');
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
                                throw new RuntimeException("Satuan tidak cocok: barang existing menggunakan '{$barang->satuan?->kode}'.");
                            }

                            $masterBarang = $barang?->masterBarang;
                            if ($masterBarang && ! $masterBarang->is_aktif) {
                                throw new RuntimeException("Master barang '{$masterBarang->nama}' sedang nonaktif.");
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
                    } catch (RuntimeException $exception) {
                        $errors[] = [
                            'row' => (int) ($row['row'] ?? $index + 1),
                            'kode' => $row['kode_barang'] ?? null,
                            'error' => $exception->getMessage(),
                        ];
                    } catch (Throwable $exception) {
                        Log::error('Unexpected error importing stock row.', [
                            'row' => (int) ($row['row'] ?? $index + 1),
                            'kode' => $row['kode_barang'] ?? null,
                            'error' => $exception->getMessage(),
                        ]);

                        $errors[] = [
                            'row' => (int) ($row['row'] ?? $index + 1),
                            'kode' => $row['kode_barang'] ?? null,
                            'error' => 'Terjadi kesalahan saat memproses baris data.',
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
