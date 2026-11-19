<?php

declare(strict_types=1);

namespace App\Services\MasterData;

use App\Imports\MasterBarangRowsImport;
use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\MasterBarang;
use App\Models\Satuan;
use App\Models\User;
use App\Services\SemanticVectorSearch;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Exceptions\SheetNotFoundException;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class MasterBarangService
{
    private const array IMPORT_WAREHOUSE_TYPES = [
        'gudang_store',
        'unit_bisnis',
        'gudang_sukahati',
        'gudang_umum',
    ];

    public function __construct(
        private readonly SemanticVectorSearch $semanticSearch,
        private readonly KategoriService $kategoriService,
        private readonly SatuanService $satuanService,
        private readonly DivisiPemintaService $divisiPemintaService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getPageData(array $filters, ?User $user, int|string|null $activeWarehouseId = null): array
    {
        $filters['gudang_id'] = array_values($filters['gudang_id'] ?? []);
        $canImportMasterBarang = $user?->can('barang.import') ?? false;
        $canManageMasterBarang = $user?->can('barang.manage') ?? false;
        $canCreateMasterBarang = $user?->can('barang.create') || $canManageMasterBarang;

        $userCanBarang = ! $user
            || $user->can('barang.read')
            || $user->can('barang.view')
            || $user->can('barang.manage')
            || $canImportMasterBarang;
        $userCanKategori = ! $user || $user->can('kategori.read') || $user->can('kategori.manage');
        $userCanSatuan = ! $user || $user->can('satuan.read') || $user->can('satuan.manage');
        $userCanArea = ! $user || $user->can('area.read') || $user->can('area.manage');
        $canCreateArea = $user?->can('area.create') || $user?->can('area.manage') || false;
        $canManageArea = $user?->can('area.manage') ?? false;

        $activeTab = $filters['tab'] ?? null;
        if ($activeTab === 'barang' && ! $userCanBarang) {
            $activeTab = null;
        } elseif ($activeTab === 'kategori' && ! $userCanKategori) {
            $activeTab = null;
        } elseif ($activeTab === 'satuan' && ! $userCanSatuan) {
            $activeTab = null;
        } elseif ($activeTab === 'area' && ! $userCanArea) {
            $activeTab = null;
        }

        if (empty($activeTab)) {
            if ($userCanBarang) {
                $activeTab = 'barang';
            } elseif ($userCanKategori) {
                $activeTab = 'kategori';
            } elseif ($userCanSatuan) {
                $activeTab = 'satuan';
            } elseif ($userCanArea) {
                $activeTab = 'area';
            } else {
                $activeTab = 'barang';
            }
        }

        $requestedBarangView = $filters['barang_view'] ?? 'catalog';
        $activeBarangView = 'catalog';

        if ($activeTab === 'barang') {
            if (in_array($requestedBarangView, ['add', 'import'], true) && $canImportMasterBarang) {
                $activeBarangView = 'import';
            } elseif ($requestedBarangView === 'add' && ($canImportMasterBarang || $canManageMasterBarang || $canCreateMasterBarang)) {
                $activeBarangView = 'add';
            }
        }
        $filters['per_page'] = $activeTab === 'satuan'
            ? 20
            : (int) ($filters['per_page'] ?? 8);

        $importWarehouses = new EloquentCollection;
        $canSelectImportWarehouses = false;
        $fixedWarehouseIds = [];
        $fixedWarehouseNames = collect();
        $categorySuggestions = collect();
        $unitSuggestions = collect();

        if ($activeBarangView === 'import' && $user instanceof User) {
            $importWarehouses = $this->availableWarehouses($user);
            $canSelectImportWarehouses = $this->canSelectImportWarehouses($user);

            $categorySuggestions = KategoriBarang::query()
                ->where('is_aktif', true)
                ->where(function (Builder $query) use ($importWarehouses): void {
                    $query->whereNull('gudang_id')
                        ->orWhereIn('gudang_id', $importWarehouses->modelKeys());
                })
                ->orderBy('nama')
                ->pluck('nama')
                ->unique()
                ->values();
            $unitSuggestions = Satuan::query()
                ->where('is_aktif', true)
                ->orderBy('nama')
                ->get(['kode', 'nama'])
                ->flatMap(static fn (Satuan $unit): array => [$unit->nama, $unit->kode])
                ->filter()
                ->unique()
                ->values();

            if (! $canSelectImportWarehouses && $importWarehouses->count() === 1) {
                $fixedWarehouseIds = $importWarehouses->modelKeys();
                $fixedWarehouseNames = $importWarehouses->pluck('nama')->values();
            }
        }

        $listingData = match ($activeTab) {
            'barang' => $this->getListingData(
                search: trim($filters['search'] ?? ''),
                kategoriId: $filters['kategori_id'] ?? null,
                gudangIds: $filters['gudang_id'],
                status: $filters['status'] ?? null,
                perPage: $filters['per_page'],
                satuanId: $filters['satuan_id'] ?? null,
                registrasi: $filters['registrasi'] ?? 'semua',
            ),
            'kategori' => $this->kategoriService->getListingData(
                search: trim($filters['search'] ?? ''),
                status: $filters['status'] ?? null,
                perPage: 20,
                drawerCategoryId: isset($filters['kategori_drawer']) ? (int) $filters['kategori_drawer'] : null,
                drawerSearch: trim($filters['drawer_search'] ?? ''),
            ),
            'satuan' => [
                'satuans' => $this->satuanService->getListingData(
                    search: trim($filters['search'] ?? ''),
                    status: $filters['status'] ?? null,
                    perPage: $filters['per_page'],
                ),
            ],
            'area' => $this->divisiPemintaService->getPageData(
                $user,
                $filters['area_gudang_id'] ?? $activeWarehouseId,
                trim($filters['search'] ?? ''),
                $filters['area_periode'] ?? null,
            ),
        };

        return [
            ...$listingData,
            ...($activeTab === 'barang' ? $this->getSummaryData() : []),
            'filters' => $filters,
            'activeTab' => $activeTab,
            'activeBarangView' => $activeBarangView,
            'canImportMasterBarang' => $canImportMasterBarang,
            'canManageMasterBarang' => $canManageMasterBarang,
            'canCreateMasterBarang' => $canCreateMasterBarang,
            'canViewBarang' => $userCanBarang,
            'canViewKategori' => $userCanKategori,
            'canViewSatuan' => $userCanSatuan,
            'canViewArea' => $userCanArea,
            'canCreateArea' => $canCreateArea,
            'canManageArea' => $canManageArea,
            'importWarehouses' => $importWarehouses,
            'canSelectImportWarehouses' => $canSelectImportWarehouses,
            'fixedWarehouseIds' => $fixedWarehouseIds,
            'fixedWarehouseNames' => $fixedWarehouseNames,
            'categorySuggestions' => $categorySuggestions,
            'unitSuggestions' => $unitSuggestions,
            'unitAliases' => SatuanService::aliases(),
        ];
    }

    /**
     * @param  list<int|string>  $gudangIds
     * @return array{
     *     masterBarangs: LengthAwarePaginator,
     *     kategoriList: Collection<int, KategoriBarang>,
     *     satuanList: Collection<int, Satuan>,
     *     gudangList: Collection<int, Gudang>
     * }
     */
    public function getListingData(
        string $search = '',
        string|int|null $kategoriId = null,
        array $gudangIds = [],
        string|int|null $status = null,
        int $perPage = 8,
        string|int|null $satuanId = null,
        ?string $registrasi = null,
    ): array {
        $query = MasterBarang::query()
            ->select([
                'id',
                'kode',
                'nama',
                'kategori_id',
                'satuan_id',
                'harga_satuan',
                'deskripsi',
                'is_aktif',
            ])
            ->with([
                'kategori:id,nama',
                'satuan:id,nama,kode',
                'barangs:id,master_barang_id,gudang_id',
                'barangs.gudang:id,nama',
            ])
            ->withCount('barangs');

        if ($search !== '') {
            $this->semanticSearch->apply($query, 'master_barangs', $search, 'search');
        }

        if ($kategoriId !== null && $kategoriId !== '') {
            $query->where('kategori_id', $kategoriId);
        }

        if ($satuanId !== null && $satuanId !== '') {
            $query->where('satuan_id', $satuanId);
        }

        if ($gudangIds !== []) {
            if ($registrasi === 'belum') {
                $query->whereDoesntHave('barangs', fn (Builder $barangs): Builder => $barangs
                    ->whereIn('gudang_id', $gudangIds));
            } else {
                $query->whereHas('barangs', fn (Builder $barangs): Builder => $barangs
                    ->whereIn('gudang_id', $gudangIds));
            }
        } elseif ($registrasi === 'terdaftar') {
            $query->whereHas('barangs');
        } elseif ($registrasi === 'belum') {
            $query->whereDoesntHave('barangs');
        }

        if ($status !== null && $status !== '') {
            $query->where('is_aktif', (bool) $status);
        }

        return [
            'masterBarangs' => $query->orderBy('nama')->paginate($perPage)->withQueryString(),
            'kategoriList' => KategoriBarang::query()->where('is_aktif', true)->orderBy('nama')->get(['id', 'nama']),
            'satuanList' => Satuan::query()->where('is_aktif', true)->orderBy('nama')->get(['id', 'kode', 'nama']),
            'gudangList' => Gudang::query()->where('is_aktif', true)->orderBy('nama')->get(['id', 'nama']),
        ];
    }

    /**
     * @return array{totalMasterBarang: int, unregisteredMasterBarang: int}
     */
    public function getSummaryData(): array
    {
        $summary = MasterBarang::query()
            ->toBase()
            ->selectRaw('COUNT(*) AS total_master_barang')
            ->selectRaw(
                'COUNT(CASE WHEN NOT EXISTS (
                    SELECT 1 FROM barangs
                    WHERE barangs.master_barang_id = master_barangs.id
                      AND barangs.deleted_at IS NULL
                ) THEN 1 END) AS unregistered_master_barang',
            )
            ->first();

        return [
            'totalMasterBarang' => (int) $summary->total_master_barang,
            'unregisteredMasterBarang' => (int) $summary->unregistered_master_barang,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, ?UploadedFile $photo = null): MasterBarang
    {
        $attributes['foto_path'] = $photo?->store('barang_foto', 'public')
            ?? $attributes['foto_path']
            ?? null;

        return DB::transaction(fn (): MasterBarang => MasterBarang::create([
            'kode' => strtoupper(trim((string) $attributes['kode'])),
            'nama' => trim((string) $attributes['nama']),
            'kategori_id' => $attributes['kategori_id'],
            'satuan_id' => $attributes['satuan_id'],
            'harga_satuan' => $attributes['harga_satuan'] ?? 0,
            'deskripsi' => $attributes['deskripsi'] ?? null,
            'foto_path' => $attributes['foto_path'] ?? null,
            'is_aktif' => true,
        ]));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(MasterBarang $masterBarang, array $attributes): MasterBarang
    {
        return DB::transaction(function () use ($masterBarang, $attributes): MasterBarang {
            $masterBarang->update([
                'kode' => strtoupper(trim((string) $attributes['kode'])),
                'nama' => trim((string) $attributes['nama']),
                'kategori_id' => $attributes['kategori_id'],
                'satuan_id' => $attributes['satuan_id'],
                'harga_satuan' => $attributes['harga_satuan'] ?? 0,
                'deskripsi' => $attributes['deskripsi'] ?? null,
                'is_aktif' => $attributes['is_aktif'],
            ]);

            $masterBarang->barangs()->update([
                'nama' => $masterBarang->nama,
                'kategori_id' => $masterBarang->kategori_id,
                'satuan_id' => $masterBarang->satuan_id,
                'harga_satuan' => $masterBarang->harga_satuan,
                'deskripsi' => $masterBarang->deskripsi,
                'foto_path' => $masterBarang->foto_path,
            ]);

            if (array_key_exists('gudang_ids', $attributes)) {
                $targetGudangIds = array_map('intval', (array) ($attributes['gudang_ids'] ?? []));
                $currentBarangs = $masterBarang->barangs()->with('gudang')->get();
                $currentGudangIds = $currentBarangs->pluck('gudang_id')->map(static fn ($id): int => (int) $id)->all();

                // 1. Remove from warehouses that are not in target
                $toRemove = array_diff($currentGudangIds, $targetGudangIds);
                foreach ($toRemove as $removeGudangId) {
                    $barang = $currentBarangs->firstWhere('gudang_id', $removeGudangId);
                    if ($barang) {
                        $barang->delete();
                    }
                }

                // 2. Add to newly selected warehouses
                $toAdd = array_diff($targetGudangIds, $currentGudangIds);
                foreach ($toAdd as $addGudangId) {
                    $gudang = Gudang::query()->find($addGudangId);
                    if ($gudang && $gudang->is_aktif) {
                        $this->registerInWarehouse($masterBarang, $gudang, null, 0);
                    }
                }
            }

            return $masterBarang->fresh();
        });
    }

    public function registerInWarehouse(
        MasterBarang $masterBarang,
        Gudang $gudang,
        ?string $localCode,
        int $minimumStock = 0,
        ?int $kategoriId = null,
        ?int $satuanId = null,
    ): Barang {
        return DB::transaction(function () use ($masterBarang, $gudang, $localCode, $minimumStock, $kategoriId, $satuanId): Barang {
            if (! $masterBarang->is_aktif || ! $gudang->is_aktif) {
                throw ValidationException::withMessages([
                    'gudang_id' => 'Master barang dan gudang harus berstatus aktif untuk didaftarkan.',
                ]);
            }

            $existingBarang = $masterBarang->barangs()
                ->withTrashed()
                ->where('gudang_id', $gudang->id)
                ->first();

            if ($existingBarang && ! $existingBarang->trashed()) {
                throw ValidationException::withMessages([
                    'gudang_id' => 'Barang ini sudah terdaftar di gudang tersebut.',
                ]);
            }

            $code = strtoupper(trim($localCode ?? $existingBarang?->kode ?? $masterBarang->kode));
            $codeExists = Barang::withTrashed()
                ->where('gudang_id', $gudang->id)
                ->where('kode', $code)
                ->when($existingBarang, fn (Builder $query): Builder => $query->where('id', '!=', $existingBarang->id))
                ->exists();

            if ($codeExists) {
                throw ValidationException::withMessages([
                    'kode_lokal' => 'Kode barang tersebut sudah digunakan di gudang yang dipilih.',
                ]);
            }

            $attributes = [
                'gudang_id' => $gudang->id,
                'master_barang_id' => $masterBarang->id,
                'kategori_id' => $kategoriId ?? $masterBarang->kategori_id,
                'satuan_id' => $satuanId ?? $masterBarang->satuan_id,
                'kode' => $code,
                'nama' => $masterBarang->nama,
                'minimum_stok' => $minimumStock,
                'harga_satuan' => $masterBarang->harga_satuan,
                'deskripsi' => $masterBarang->deskripsi,
                'foto_path' => $masterBarang->foto_path,
                'is_aktif' => true,
            ];

            if ($existingBarang) {
                $existingBarang->restore();
                $existingBarang->update($attributes);

                return $existingBarang;
            }

            return Barang::create($attributes);
        });
    }

    public function registerInWarehouseById(
        MasterBarang $masterBarang,
        int $warehouseId,
        ?string $localCode,
        int $minimumStock,
    ): Gudang {
        $warehouse = Gudang::query()->findOrFail($warehouseId);
        $this->registerInWarehouse($masterBarang, $warehouse, $localCode, $minimumStock);

        return $warehouse;
    }

    public function findOrCreateForLocalCode(
        string $companyCode,
        string $name,
        int $categoryId,
        int $unitId,
        ?string $description = null,
    ): MasterBarang {
        $masterBarang = MasterBarang::query()->where('kode', strtoupper(trim($companyCode)))->first();

        if ($masterBarang) {
            if (mb_strtolower(trim($masterBarang->nama)) !== mb_strtolower(trim($name))) {
                throw ValidationException::withMessages([
                    'kode_barang' => "Kode perusahaan '{$companyCode}' sudah digunakan oleh barang '{$masterBarang->nama}'.",
                ]);
            }

            $masterBarang->loadMissing(['kategori:id,nama', 'satuan:id,kode']);
            $categoryName = KategoriBarang::query()->whereKey($categoryId)->value('nama');
            $unitCode = Satuan::query()->whereKey($unitId)->value('kode');

            if (
                ($masterBarang->kategori && mb_strtolower($masterBarang->kategori->nama) !== mb_strtolower((string) $categoryName))
                || ($masterBarang->satuan && strtoupper($masterBarang->satuan->kode) !== strtoupper((string) $unitCode))
            ) {
                throw ValidationException::withMessages([
                    'kode_barang' => "Kategori atau satuan tidak cocok dengan Master Barang '{$masterBarang->nama}'.",
                ]);
            }

            if (! $masterBarang->is_aktif) {
                throw ValidationException::withMessages([
                    'kode_barang' => "Master barang '{$masterBarang->nama}' sedang nonaktif.",
                ]);
            }

            return $masterBarang;
        }

        return $this->create([
            'kode' => $companyCode,
            'nama' => $name,
            'kategori_id' => $categoryId,
            'satuan_id' => $unitId,
            'deskripsi' => $description,
        ]);
    }

    public function toggle(MasterBarang $masterBarang): MasterBarang
    {
        return DB::transaction(function () use ($masterBarang): MasterBarang {
            $masterBarang->update(['is_aktif' => ! $masterBarang->is_aktif]);

            return $masterBarang;
        });
    }

    public function delete(MasterBarang $masterBarang): string
    {
        return DB::transaction(function () use ($masterBarang): string {
            if ($masterBarang->barangs()->exists()) {
                throw ValidationException::withMessages([
                    'master_barang' => 'Master barang yang sudah digunakan oleh gudang tidak dapat dihapus. Nonaktifkan master tersebut.',
                ]);
            }

            $name = $masterBarang->nama;
            $masterBarang->delete();

            return $name;
        });
    }

    /**
     * @return EloquentCollection<int, Gudang>
     */
    public function availableWarehouses(User $user): EloquentCollection
    {
        $types = match (true) {
            $this->canSelectImportWarehouses($user) => self::IMPORT_WAREHOUSE_TYPES,
            $user->hasRole('Admin Gudang Store') => ['gudang_store'],
            $user->hasRole('Admin Gudang Umum') => ['unit_bisnis', 'gudang_sukahati', 'gudang_umum'],
            default => [],
        };

        return Gudang::query()
            ->where('is_aktif', true)
            ->whereIn('jenis', $types)
            ->when(
                ! $this->canSelectImportWarehouses($user),
                fn (Builder $query): Builder => $query->whereHas(
                    'users',
                    fn (Builder $users): Builder => $users->whereKey($user->getKey()),
                ),
            )
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama', 'jenis', 'is_aktif']);
    }

    public function canSelectImportWarehouses(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'super_admin']);
    }

    /**
     * @return EloquentCollection<int, Gudang>
     */
    public function defaultImportWarehouses(User $user): EloquentCollection
    {
        if ($this->canSelectImportWarehouses($user)) {
            throw new AuthorizationException('Role Super Admin harus memilih satu atau beberapa gudang tujuan.');
        }

        $warehouses = $this->availableWarehouses($user);

        if ($warehouses->count() !== 1) {
            throw new AuthorizationException(
                'Akun harus memiliki tepat satu gudang aktif yang sesuai dengan role untuk melakukan impor.',
            );
        }

        return $warehouses;
    }

    /**
     * @param  list<int|string>  $warehouseIds
     * @return EloquentCollection<int, Gudang>
     */
    public function resolveImportWarehouses(User $user, array $warehouseIds): EloquentCollection
    {
        $requestedIds = collect($warehouseIds)
            ->map(static fn (int|string $id): int|false => filter_var($id, FILTER_VALIDATE_INT))
            ->filter(static fn (int|false $id): bool => is_int($id))
            ->map(static fn (int $id): int => $id)
            ->unique()
            ->values();

        if ($this->canSelectImportWarehouses($user)) {
            if ($requestedIds->isEmpty()) {
                throw ValidationException::withMessages([
                    'warehouse_ids' => 'Pilih setidaknya satu gudang tujuan.',
                ]);
            }

            $warehouses = $this->availableWarehouses($user);

            if (
                $requestedIds->diff($warehouses->modelKeys())->isNotEmpty()
                || $requestedIds->count() !== count($warehouseIds)
            ) {
                throw new AuthorizationException(
                    'Satu atau beberapa gudang tujuan tidak aktif atau tidak tersedia untuk impor.',
                );
            }

            return $warehouses->whereIn('id', $requestedIds)->values();
        }

        $warehouses = $this->defaultImportWarehouses($user);
        $defaultIds = $warehouses->modelKeys();
        $requestedIds = $requestedIds->sort()->values();

        if ($requestedIds->isNotEmpty() && $requestedIds->all() !== collect($defaultIds)->sort()->values()->all()) {
            throw new AuthorizationException('Gudang tujuan impor ditentukan oleh role dan penugasan akun Anda.');
        }

        return $warehouses;
    }

    /** @return array{stream: resource, total: int} */
    public function readExcel(UploadedFile $file): array
    {
        $filePath = $file->getPathname();
        $worksheetNames = IOFactory::createReaderForFile($filePath)->listWorksheetNames($filePath);
        $worksheetName = collect($worksheetNames)->first(
            static fn (string $name): bool => Str::lower(Str::squish($name))
                === Str::lower(MasterBarangRowsImport::SHEET_NAME),
        );

        if (! is_string($worksheetName)) {
            throw ValidationException::withMessages([
                'file' => 'File tidak memiliki sheet bernama “Master Barang”. Gunakan template resmi.',
            ]);
        }

        $stream = tmpfile();

        if ($stream === false) {
            throw new \RuntimeException('Penyimpanan sementara untuk membaca file Excel tidak tersedia.');
        }

        $import = new MasterBarangRowsImport($stream, $worksheetName);
        $successful = false;

        try {
            Excel::import($import, $file);

            if ($import->rowCount() === 0) {
                throw ValidationException::withMessages([
                    'file' => 'Belum ada baris barang pada sheet Master Barang.',
                ]);
            }

            rewind($stream);
            $successful = true;

            return ['stream' => $stream, 'total' => $import->rowCount()];
        } catch (SheetNotFoundException) {
            throw ValidationException::withMessages([
                'file' => 'File tidak memiliki sheet bernama “Master Barang”. Gunakan template resmi.',
            ]);
        } finally {
            if (! $successful && is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{rows: list<array<string, mixed>>, summary: array<string, int>}
     */
    public function analyzeRows(array $rows, EloquentCollection $warehouses): array
    {
        $analysisByWarehouse = $warehouses
            ->map(fn (Gudang $warehouse): array => $this->analyzeRowsForWarehouse($rows, $warehouse)['rows'])
            ->values();
        $firstRows = $analysisByWarehouse->first() ?? [];
        $summary = [
            'total' => count($firstRows),
            'new' => 0,
            'reused' => 0,
            'registered' => 0,
            'duplicate' => 0,
            'conflict' => 0,
            'invalid' => 0,
            'ignored' => 0,
        ];
        $results = [];

        foreach ($firstRows as $index => $firstRow) {
            $targetRows = $analysisByWarehouse
                ->map(static fn (array $warehouseRows): array => $warehouseRows[$index])
                ->all();
            $statuses = array_column($targetRows, 'status');
            $identityBlockedRows = array_values(array_filter(
                $targetRows,
                static fn (array $row): bool => $row['_identity_blocked'],
            ));
            $rowsForNotes = $identityBlockedRows !== [] ? $identityBlockedRows : $targetRows;
            $issues = array_values(array_unique(array_merge(...array_column($rowsForNotes, 'issues'))));
            $preparationNotes = $identityBlockedRows === []
                ? array_values(array_unique(array_merge(...array_column($targetRows, 'preparation_notes'))))
                : [];
            $targetReferences = [];

            foreach ($warehouses->values() as $targetIndex => $warehouse) {
                $targetReferences[] = [
                    'warehouse_id' => $warehouse->id,
                    'kategori_id' => $targetRows[$targetIndex]['kategori_id'],
                    'satuan_id' => $targetRows[$targetIndex]['satuan_id'],
                ];
            }

            $status = match (true) {
                in_array('conflict', $statuses, true) => 'conflict',
                in_array('invalid', $statuses, true) => 'invalid',
                in_array('duplicate', $statuses, true) => 'duplicate',
                in_array('ignored', $statuses, true) => 'ignored',
                count(array_filter($statuses, static fn (string $itemStatus): bool => $itemStatus !== 'registered')) === 0 => 'registered',
                in_array('new', $statuses, true) => 'new',
                default => 'reused',
            };

            $summary[$status]++;
            unset($firstRow['_identity_blocked']);
            $results[] = [
                ...$firstRow,
                'target_references' => $targetReferences,
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'issues' => array_values(array_unique($issues)),
                'preparation_notes' => $preparationNotes,
            ];
        }

        return ['rows' => $results, 'summary' => $summary];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{rows: list<array<string, mixed>>, success_count: int, skipped_count: int, registered_warehouse_count: int}
     */
    public function confirmRows(array $rows, EloquentCollection $warehouses): array
    {
        $analysis = $this->analyzeRows($rows, $warehouses);
        $resultRows = $analysis['rows'];
        $successCount = 0;
        $registeredWarehouseCount = 0;

        if (collect($resultRows)->contains(
            static fn (array $row): bool => ! in_array($row['status'], ['new', 'reused'], true),
        )) {
            return [
                'rows' => $resultRows,
                'success_count' => 0,
                'skipped_count' => count($resultRows),
                'registered_warehouse_count' => 0,
            ];
        }

        DB::transaction(function () use ($warehouses, &$resultRows, &$successCount, &$registeredWarehouseCount): void {
            $warehousesById = $warehouses->keyBy('id');
            $createdCategories = [];
            $createdUnits = [];

            foreach ($resultRows as &$row) {
                if (! in_array($row['status'], ['new', 'reused'], true)) {
                    continue;
                }

                $firstResolvedUnit = null;
                foreach ($row['target_references'] as &$targetReference) {
                    $warehouse = $warehousesById->get($targetReference['warehouse_id']);
                    if (! $warehouse instanceof Gudang) {
                        throw ValidationException::withMessages([
                            'rows' => "Gudang tujuan pada baris {$row['line']} tidak tersedia. Tidak ada data yang disimpan.",
                        ]);
                    }
                    $category = $targetReference['kategori_id'] !== null
                        ? KategoriBarang::query()->findOrFail($targetReference['kategori_id'])
                        : $this->resolveImportCategory($row['kategori'], $createdCategories);
                    $unit = $targetReference['satuan_id'] !== null
                        ? Satuan::query()->findOrFail($targetReference['satuan_id'])
                        : $this->resolveImportUnit($row['satuan'], $row['satuan_kode'], $createdUnits);
                    $firstResolvedUnit ??= $unit;
                    $targetReference['kategori_id'] = $category->id;
                    $targetReference['satuan_id'] = $unit->id;
                }
                unset($targetReference);

                $firstTargetReference = $row['target_references'][0];
                $row['kategori_id'] = $firstTargetReference['kategori_id'];
                $row['satuan_id'] = $firstTargetReference['satuan_id'];
                $row['satuan'] = $firstResolvedUnit?->nama ?? $row['satuan'];
                $row['satuan_kode'] = $firstResolvedUnit?->kode ?? $row['satuan_kode'];
                $row['preparation_notes'] = [];

                $master = MasterBarang::withTrashed()
                    ->with(['kategori:id,nama', 'satuan:id,nama'])
                    ->where('kode', $row['kode_barang'])
                    ->lockForUpdate()
                    ->first();

                if ($master instanceof MasterBarang && ($master->trashed() || ! $master->is_aktif)) {
                    throw ValidationException::withMessages([
                        'rows' => "Barang pada baris {$row['line']} berubah setelah diperiksa. Tidak ada data yang disimpan; periksa ulang seluruh data.",
                    ]);
                }

                if ($master instanceof MasterBarang) {
                    $sameDetails = $this->normalizeName($master->nama) === $this->normalizeName($row['nama_barang'])
                        && $this->normalizeName((string) $master->kategori?->nama) === $this->normalizeName($row['kategori'])
                        && $this->normalizeName((string) $master->satuan?->nama) === $this->normalizeName($row['satuan']);

                    if (! $sameDetails) {
                        throw ValidationException::withMessages([
                            'rows' => "Data barang pada baris {$row['line']} berubah setelah diperiksa. Tidak ada data yang disimpan; periksa ulang seluruh data.",
                        ]);
                    }
                }

                $codeUsedByAnotherItem = $warehouses->contains(
                    fn (Gudang $warehouse): bool => Barang::withTrashed()
                        ->where('gudang_id', $warehouse->id)
                        ->where('kode', $row['kode_barang'])
                        ->when(
                            $master instanceof MasterBarang,
                            fn (Builder $query): Builder => $query->where('master_barang_id', '!=', $master->id),
                        )
                        ->exists(),
                );

                if ($codeUsedByAnotherItem) {
                    throw ValidationException::withMessages([
                        'rows' => "Kode barang pada baris {$row['line']} sudah digunakan di gudang tujuan. Tidak ada data yang disimpan; periksa ulang seluruh data.",
                    ]);
                }

                if (! $master instanceof MasterBarang) {
                    $master = $this->create([
                        'kode' => $row['kode_barang'],
                        'nama' => $row['nama_barang'],
                        'kategori_id' => $row['kategori_id'],
                        'satuan_id' => $row['satuan_id'],
                        'deskripsi' => $row['deskripsi'] !== '' ? $row['deskripsi'] : null,
                    ]);
                }

                $registeredForRow = 0;

                foreach ($warehouses as $warehouse) {
                    $existingBarang = Barang::withTrashed()
                        ->where('gudang_id', $warehouse->id)
                        ->where('master_barang_id', $master->id)
                        ->first();

                    if ($existingBarang instanceof Barang && ! $existingBarang->trashed()) {
                        continue;
                    }

                    $targetReference = collect($row['target_references'])
                        ->firstWhere('warehouse_id', $warehouse->id);

                    $this->registerInWarehouse(
                        $master,
                        $warehouse,
                        null,
                        0,
                        (int) $targetReference['kategori_id'],
                        (int) $targetReference['satuan_id'],
                    );
                    $registeredForRow++;
                }

                if ($registeredForRow > 0) {
                    $row['status'] = 'imported';
                    $row['status_label'] = 'Berhasil diimpor';
                    $row['issues'] = [];
                    $successCount++;
                    $registeredWarehouseCount += $registeredForRow;
                } else {
                    throw ValidationException::withMessages([
                        'rows' => "Barang pada baris {$row['line']} sudah terdaftar di semua gudang tujuan. Tidak ada data yang disimpan; periksa ulang seluruh data.",
                    ]);
                }
            }
            unset($row);
        });

        return [
            'rows' => $resultRows,
            'success_count' => $successCount,
            'skipped_count' => count($resultRows) - $successCount,
            'registered_warehouse_count' => $registeredWarehouseCount,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{rows: list<array<string, mixed>>, summary: array<string, int>}
     */
    private function analyzeRowsForWarehouse(array $rows, Gudang $warehouse): array
    {
        $normalizedRows = $this->normalizeRows($rows);
        $codesByName = [];

        foreach ($normalizedRows as $candidateRow) {
            if ($this->isBlank($candidateRow) || $this->validateIdentityRow($candidateRow) !== []) {
                continue;
            }

            $normalizedName = $this->normalizeName($candidateRow['nama_barang']);
            $normalizedCode = strtoupper(trim($candidateRow['kode_barang']));
            $codesByName[$normalizedName][$normalizedCode] = true;
        }

        $codes = collect($normalizedRows)
            ->pluck('kode_barang')
            ->filter()
            ->unique()
            ->values();
        $names = collect(array_keys($codesByName));

        $categories = KategoriBarang::withTrashed()
            ->where(function (Builder $query) use ($warehouse): void {
                $query->whereNull('gudang_id')->orWhere('gudang_id', $warehouse->id);
            })
            ->orderByRaw('CASE WHEN gudang_id = ? THEN 0 ELSE 1 END', [$warehouse->id])
            ->get(['id', 'nama', 'is_aktif', 'deleted_at'])
            ->groupBy(fn (KategoriBarang $category): string => $this->normalizeName($category->nama));

        $units = Satuan::withTrashed()
            ->orderByRaw('CASE WHEN gudang_id = ? THEN 0 ELSE 1 END', [$warehouse->id])
            ->get(['id', 'gudang_id', 'kode', 'nama', 'is_aktif', 'deleted_at']);

        $masters = $codes->isEmpty()
            ? collect()
            : MasterBarang::withTrashed()
                ->with(['kategori:id,nama', 'satuan:id,nama'])
                ->whereIn('kode', $codes)
                ->get()
                ->keyBy(static fn (MasterBarang $master): string => strtoupper(trim((string) $master->kode)));
        $mastersByName = $names->isEmpty()
            ? collect()
            : MasterBarang::withTrashed()
                ->whereIn(DB::raw('LOWER(TRIM(nama))'), $names)
                ->get(['id', 'kode', 'nama'])
                ->groupBy(fn (MasterBarang $master): string => $this->normalizeName($master->nama));
        $masterIds = $masters->pluck('id');

        $registeredBarangs = $masterIds->isEmpty()
            ? collect()
            : Barang::withTrashed()
                ->where('gudang_id', $warehouse->id)
                ->whereIn('master_barang_id', $masterIds)
                ->get(['id', 'master_barang_id', 'kode', 'deleted_at'])
                ->keyBy('master_barang_id');

        $localCodes = $codes->isEmpty()
            ? collect()
            : Barang::withTrashed()
                ->where('gudang_id', $warehouse->id)
                ->whereIn('kode', $codes)
                ->get(['kode', 'master_barang_id'])
                ->keyBy(static fn (Barang $barang): string => strtoupper($barang->kode));

        $seenCodes = [];
        $results = [];
        $summary = [
            'total' => count($normalizedRows),
            'new' => 0,
            'reused' => 0,
            'registered' => 0,
            'duplicate' => 0,
            'conflict' => 0,
            'invalid' => 0,
            'ignored' => 0,
        ];

        foreach ($normalizedRows as $row) {
            $code = $row['kode_barang'];
            $issues = $this->validateIdentityRow($row);
            $identityBlocked = false;
            $master = null;
            $category = null;
            $unit = null;
            $unitCandidate = ['kode' => '', 'nama' => $row['satuan']];
            if ($this->isBlank($row)) {
                $status = 'ignored';
            } elseif ($issues !== []) {
                $status = 'invalid';
                $identityBlocked = true;
            } elseif (isset($seenCodes[$code])) {
                $status = 'duplicate';
                $issues[] = "Kode barang ini sudah muncul di baris {$seenCodes[$code]}.";
                $identityBlocked = true;
            } else {
                $seenCodes[$code] = $row['line'];
                $master = $masters->get($code);
                $localCode = $localCodes->get($code);

                if ($localCode instanceof Barang && (! $master || (int) $localCode->master_barang_id !== (int) $master->id)) {
                    $status = 'conflict';
                    $issues[] = 'Kode ini sudah digunakan oleh barang lain di gudang tujuan.';
                    $identityBlocked = true;
                } else {
                    $normalizedName = $this->normalizeName($row['nama_barang']);
                    $sameNameMasterExists = $mastersByName
                        ->get($normalizedName)
                        ?->contains(
                            fn (MasterBarang $candidate): bool => strtoupper(trim((string) $candidate->kode))
                                !== strtoupper(trim($code)),
                        ) ?? false;
                    $differentCodeInImport = count($codesByName[$normalizedName] ?? []) > 1;

                    if ($sameNameMasterExists || $differentCodeInImport) {
                        $status = 'conflict';
                        $issues[] = 'Nama barang yang sama ditemukan dengan kode barang berbeda. Periksa data barang sebelum mengimpor.';
                        $identityBlocked = true;
                    } elseif ($master instanceof MasterBarang && ($master->trashed() || ! $master->is_aktif)) {
                        $status = 'conflict';
                        $issues[] = 'Kode ini sudah digunakan oleh Master Barang yang nonaktif atau sudah dihapus.';
                        $identityBlocked = true;
                    } elseif (
                        $master instanceof MasterBarang
                        && $this->normalizeName($master->nama) !== $this->normalizeName($row['nama_barang'])
                    ) {
                        $status = 'conflict';
                        $issues[] = 'Nama barang berbeda dengan Master Barang yang sudah memakai kode ini. Periksa kode atau nama barang.';
                        $identityBlocked = true;
                    } else {
                        $status = 'new';
                    }
                }
            }

            $preparationNotes = [];
            if (! $this->isBlank($row) && ! $identityBlocked && $status === 'new') {
                $issues = [...$issues, ...$this->validateDetailsRow($row)];
                $category = $categories->get($this->normalizeName($row['kategori']))
                    ?->first(fn (KategoriBarang $candidate): bool => $candidate->is_aktif && ! $candidate->trashed());
                $inactiveCategory = $categories->get($this->normalizeName($row['kategori']))
                    ?->first(fn (KategoriBarang $candidate): bool => ! $candidate->is_aktif || $candidate->trashed());
                $unitCandidate = $this->normalizeImportUnit($row['satuan']);
                $unit = $units->first(fn (Satuan $candidate): bool => $candidate->is_aktif
                    && ! $candidate->trashed()
                    && (
                        $this->normalizeName($candidate->nama) === $this->normalizeName($row['satuan'])
                        || mb_strtoupper(trim($candidate->kode)) === mb_strtoupper(trim($row['satuan']))
                    )) ?? $units->first(fn (Satuan $candidate): bool => $candidate->is_aktif
                        && ! $candidate->trashed()
                        && mb_strtoupper(trim($candidate->kode)) === mb_strtoupper($unitCandidate['kode'])
                        && $this->normalizeName($candidate->nama) === $this->normalizeName($unitCandidate['nama']));
                $inactiveUnit = $units->first(fn (Satuan $candidate): bool => (
                    ! $candidate->is_aktif
                    || $candidate->trashed()
                ) && (
                    $this->normalizeName($candidate->nama) === $this->normalizeName($row['satuan'])
                    || mb_strtoupper(trim($candidate->kode)) === mb_strtoupper(trim($row['satuan']))
                    || (
                        mb_strtoupper(trim($candidate->kode)) === mb_strtoupper($unitCandidate['kode'])
                        && $this->normalizeName($candidate->nama) === $this->normalizeName($unitCandidate['nama'])
                    )
                ));
                $unitCodeConflict = $units->first(fn (Satuan $candidate): bool => $candidate->is_aktif
                    && ! $candidate->trashed()
                    && mb_strtoupper(trim($candidate->kode)) === mb_strtoupper($unitCandidate['kode'])
                    && $this->normalizeName($candidate->nama) !== $this->normalizeName($unitCandidate['nama'])
                    && $this->normalizeName($candidate->nama) !== $this->normalizeName($row['satuan'])
                    && mb_strtoupper(trim($candidate->kode)) !== mb_strtoupper(trim($row['satuan'])));

                if ($issues === [] && $inactiveCategory instanceof KategoriBarang && ! $category instanceof KategoriBarang) {
                    $issues[] = "Kategori “{$row['kategori']}” sudah ada tetapi nonaktif. Aktifkan kategori tersebut sebelum mengimpor.";
                }

                if ($issues === [] && $inactiveUnit instanceof Satuan && ! $unit instanceof Satuan) {
                    $issues[] = "Satuan “{$row['satuan']}” sudah ada tetapi nonaktif. Aktifkan satuan tersebut sebelum mengimpor.";
                }

                if ($issues === [] && $unitCodeConflict instanceof Satuan && ! $unit instanceof Satuan) {
                    $issues[] = "Kode satuan “{$unitCandidate['kode']}” sudah digunakan oleh satuan “{$unitCodeConflict->nama}”. Gunakan nama atau kode satuan yang berbeda.";
                }

                if ($issues === [] && $unitCandidate['kode'] === '') {
                    $issues[] = 'Kode satuan otomatis tidak dapat dibuat dari nilai tersebut. Masukkan nama atau singkatan satuan.';
                }

                if ($issues === [] && ! $unit instanceof Satuan && mb_strlen($unitCandidate['kode']) > 20) {
                    $issues[] = 'Kode satuan otomatis melebihi 20 karakter. Gunakan nama atau singkatan satuan yang lebih pendek.';
                }

                if ($issues !== []) {
                    $status = 'invalid';
                } elseif ($master instanceof MasterBarang) {
                    $unitName = $unit instanceof Satuan ? $unit->nama : $unitCandidate['nama'];
                    if (
                        $this->normalizeName((string) $master->kategori?->nama) !== $this->normalizeName($row['kategori'])
                        || $this->normalizeName((string) $master->satuan?->nama) !== $this->normalizeName($unitName)
                    ) {
                        $status = 'conflict';
                        $issues[] = 'Rincian berbeda dengan Master Barang yang sudah memakai kode ini. Perbaiki datanya atau ubah master secara terpisah.';
                    } else {
                        $registered = $registeredBarangs->get($master->id);
                        $status = $registered instanceof Barang && ! $registered->trashed()
                            ? 'registered'
                            : 'reused';
                    }
                }
            }

            if ($category instanceof KategoriBarang) {
                $categoryId = $category->id;
                $categoryName = $category->nama;
            } else {
                $categoryId = null;
                $categoryName = null;
                if ($issues === [] && ! $this->isBlank($row)) {
                    $preparationNotes[] = "Kategori “{$row['kategori']}” akan dibuat saat data disimpan.";
                }
            }
            if ($unit instanceof Satuan) {
                $unitId = $unit->id;
                $unitName = $unit->nama;
                $unitCode = $unit->kode;
            } else {
                $unitId = null;
                $unitName = $unitCandidate['nama'];
                $unitCode = $unitCandidate['kode'];
                if ($issues === [] && ! $this->isBlank($row)) {
                    $preparationNotes[] = "Satuan baru akan dibuat: {$unitName} ({$unitCode}).";
                }
            }
            $summary[$status]++;
            $results[] = [
                ...$row,
                'satuan' => $unitName,
                'kategori_id' => $categoryId,
                'satuan_id' => $unitId,
                'satuan_kode' => $unitCode,
                'kategori_nama' => $categoryName,
                'satuan_nama' => $unitName,
                'preparation_notes' => $preparationNotes,
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'issues' => $issues,
                '_identity_blocked' => $identityBlocked,
            ];
        }

        return ['rows' => $results, 'summary' => $summary];
    }

    /**
     * @param  array<string, KategoriBarang>  $createdCategories
     */
    private function resolveImportCategory(string $name, array &$createdCategories): KategoriBarang
    {
        $normalizedName = $this->normalizeName($name);
        if (isset($createdCategories[$normalizedName])) {
            return $createdCategories[$normalizedName];
        }

        $category = KategoriBarang::withTrashed()
            ->whereNull('gudang_id')
            ->whereRaw('LOWER(nama) = ?', [$normalizedName])
            ->first();

        if ($category instanceof KategoriBarang) {
            if (! $category->is_aktif || $category->trashed()) {
                throw ValidationException::withMessages([
                    'rows' => "Kategori “{$name}” sudah tidak aktif. Tidak ada data yang disimpan; aktifkan kategori lalu periksa ulang.",
                ]);
            }

            return $createdCategories[$normalizedName] = $category;
        }

        return $createdCategories[$normalizedName] = KategoriBarang::create([
            'gudang_id' => null,
            'nama' => trim($name),
            'is_aktif' => true,
        ]);
    }

    /**
     * @param  array<string, Satuan>  $createdUnits
     */
    private function resolveImportUnit(string $name, string $code, array &$createdUnits): Satuan
    {
        $normalizedCode = mb_strtolower(trim($code));
        if (isset($createdUnits[$normalizedCode])) {
            return $createdUnits[$normalizedCode];
        }

        $unit = Satuan::withTrashed()
            ->whereRaw('UPPER(kode) = ?', [mb_strtoupper($normalizedCode)])
            ->first();

        if ($unit instanceof Satuan) {
            if (
                ! $unit->is_aktif
                || $unit->trashed()
                || $this->normalizeName($unit->nama) !== $this->normalizeName($name)
            ) {
                throw ValidationException::withMessages([
                    'rows' => "Kode satuan “{$code}” sudah digunakan atau tidak aktif. Tidak ada data yang disimpan; periksa ulang satuan.",
                ]);
            }

            return $createdUnits[$normalizedCode] = $unit;
        }

        $sameName = Satuan::withTrashed()
            ->whereRaw('LOWER(nama) = ?', [$this->normalizeName($name)])
            ->first();

        if ($sameName instanceof Satuan) {
            if (! $sameName->is_aktif || $sameName->trashed()) {
                throw ValidationException::withMessages([
                    'rows' => "Satuan “{$name}” sudah tidak aktif. Tidak ada data yang disimpan; aktifkan satuan lalu periksa ulang.",
                ]);
            }

            return $createdUnits[$normalizedCode] = $sameName;
        }

        return $createdUnits[$normalizedCode] = Satuan::create([
            'gudang_id' => null,
            'kode' => $normalizedCode,
            'nama' => trim($name),
            'is_aktif' => true,
        ]);
    }

    /**
     * @return array{kode: string, nama: string}
     */
    private function normalizeImportUnit(string $value): array
    {
        return SatuanService::normalizeInput($value);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{line: int, kode_barang: string, nama_barang: string, kategori: string, satuan: string, deskripsi: string}>
     */
    private function normalizeRows(array $rows): array
    {
        return array_map(static fn (array $row, int $index): array => [
            'line' => (int) ($row['line'] ?? $index + 1),
            'kode_barang' => strtoupper(trim((string) ($row['kode_barang'] ?? ''))),
            'nama_barang' => trim((string) ($row['nama_barang'] ?? '')),
            'kategori' => trim((string) ($row['kategori'] ?? '')),
            'satuan' => trim((string) ($row['satuan'] ?? '')),
            'deskripsi' => trim((string) ($row['deskripsi'] ?? '')),
        ], array_values($rows), array_keys($rows));
    }

    /**
     * @param  array{line: int, kode_barang: string, nama_barang: string, kategori: string, satuan: string, deskripsi: string}  $row
     * @return list<string>
     */
    private function validateIdentityRow(array $row): array
    {
        if ($this->isBlank($row)) {
            return [];
        }

        $messages = [
            'kode_barang.required' => 'Kode barang wajib diisi.',
            'kode_barang.max' => 'Kode barang maksimal 50 karakter.',
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'nama_barang.max' => 'Nama barang maksimal 255 karakter.',
        ];

        $validator = Validator::make($row, [
            'kode_barang' => ['required', 'string', 'max:50'],
            'nama_barang' => ['required', 'string', 'max:255'],
        ], $messages);

        return array_values($validator->errors()->all());
    }

    /**
     * @param  array{line: int, kode_barang: string, nama_barang: string, kategori: string, satuan: string, deskripsi: string}  $row
     * @return list<string>
     */
    private function validateDetailsRow(array $row): array
    {
        $messages = [
            'kategori.required' => 'Kategori wajib diisi.',
            'kategori.max' => 'Nama kategori maksimal 100 karakter.',
            'satuan.required' => 'Satuan wajib diisi.',
            'satuan.max' => 'Nama satuan maksimal 100 karakter.',
            'deskripsi.max' => 'Deskripsi maksimal 10.000 karakter.',
        ];

        $validator = Validator::make($row, [
            'kategori' => ['required', 'string', 'max:100'],
            'satuan' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:10000'],
        ], $messages);

        return array_values($validator->errors()->all());
    }

    /** @param array<string, mixed> $row */
    private function isBlank(array $row): bool
    {
        return collect($row)
            ->except('line')
            ->every(static fn (mixed $value): bool => trim((string) $value) === '');
    }

    private function normalizeName(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'new' => 'Master baru',
            'reused' => 'Master dipakai ulang',
            'registered' => 'Sudah terdaftar',
            'duplicate' => 'Duplikat dalam file',
            'conflict' => 'Perlu diperiksa',
            'invalid' => 'Data belum lengkap',
            'ignored' => 'Baris kosong',
            default => 'Status tidak diketahui',
        };
    }
}
