<?php

declare(strict_types=1);

namespace App\Services\MasterData;

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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        private readonly ?MasterBarangImportService $masterBarangImportService = null,
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

        $kategoris = Cache::remember('ref:kategoris:active', 300, static fn (): array => KategoriBarang::query()
            ->where('is_aktif', true)
            ->orderBy('nama')
            ->get(['id', 'nama'])
            ->map(static fn (KategoriBarang $item): array => $item->getAttributes())
            ->all());
        $satuans = Cache::remember('ref:satuans:active', 300, static fn (): array => Satuan::query()
            ->where('is_aktif', true)
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama'])
            ->map(static fn (Satuan $item): array => $item->getAttributes())
            ->all());
        $gudangs = Cache::remember('ref:gudangs:active', 300, static fn (): array => Gudang::query()
            ->where('is_aktif', true)
            ->orderBy('nama')
            ->get(['id', 'nama'])
            ->map(static fn (Gudang $item): array => $item->getAttributes())
            ->all());

        return [
            'masterBarangs' => $query->orderBy('nama')->paginate($perPage)->withQueryString(),
            'kategoriList' => KategoriBarang::hydrate($kategoris),
            'satuanList' => Satuan::hydrate($satuans),
            'gudangList' => Gudang::hydrate($gudangs),
        ];
    }

    /**
     * @return array{totalMasterBarang: int, unregisteredMasterBarang: int}
     */
    public function getSummaryData(): array
    {
        return Cache::remember('master_barang:summary', 60, static function (): array {
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
        });
    }

    public static function clearReferenceCache(): void
    {
        Cache::forget('ref:kategoris:active');
        Cache::forget('ref:satuans:active');
        Cache::forget('ref:gudangs:active');
        Cache::forget('master_barang:summary');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, ?UploadedFile $photo = null): MasterBarang
    {
        $attributes['foto_path'] = $photo?->store('barang_foto', 'public')
            ?? $attributes['foto_path']
            ?? null;

        $created = DB::transaction(fn (): MasterBarang => MasterBarang::create([
            'kode' => strtoupper(trim((string) $attributes['kode'])),
            'nama' => trim((string) $attributes['nama']),
            'kategori_id' => $attributes['kategori_id'],
            'satuan_id' => $attributes['satuan_id'],
            'harga_satuan' => $attributes['harga_satuan'] ?? 0,
            'deskripsi' => $attributes['deskripsi'] ?? null,
            'foto_path' => $attributes['foto_path'] ?? null,
            'is_aktif' => true,
        ]));

        self::clearReferenceCache();

        return $created;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(MasterBarang $masterBarang, array $attributes): MasterBarang
    {
        $updated = DB::transaction(function () use ($masterBarang, $attributes): MasterBarang {
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

        self::clearReferenceCache();

        return $updated;
    }

    public function registerInWarehouse(
        MasterBarang $masterBarang,
        Gudang $gudang,
        ?string $localCode,
        int $minimumStock = 0,
        ?int $kategoriId = null,
        ?int $satuanId = null,
        bool $returnExisting = false,
    ): Barang {
        return DB::transaction(function () use ($masterBarang, $gudang, $localCode, $minimumStock, $kategoriId, $satuanId, $returnExisting): Barang {
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
                if ($returnExisting && $existingBarang->is_aktif) {
                    return $existingBarang;
                }

                if ($returnExisting) {
                    throw ValidationException::withMessages([
                        'kode' => "Barang '{$existingBarang->nama}' telah terdaftar tetapi dinonaktifkan di {$gudang->nama}.",
                    ]);
                }

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
        ?string $categoryName = null,
        ?string $unitCode = null,
    ): MasterBarang {
        $masterBarang = MasterBarang::query()->where('kode', strtoupper(trim($companyCode)))->first();

        if ($masterBarang) {
            if (mb_strtolower(trim($masterBarang->nama)) !== mb_strtolower(trim($name))) {
                throw ValidationException::withMessages([
                    'kode_barang' => "Kode perusahaan '{$companyCode}' sudah digunakan oleh barang '{$masterBarang->nama}'.",
                ]);
            }

            $masterBarang->loadMissing(['kategori:id,nama', 'satuan:id,kode']);
            $categoryName ??= KategoriBarang::query()->whereKey($categoryId)->value('nama');
            $unitCode ??= Satuan::query()->whereKey($unitId)->value('kode');

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
        return $this->importService()->readExcel($file);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{rows: list<array<string, mixed>>, summary: array<string, int>}
     */
    public function analyzeRows(array $rows, EloquentCollection $warehouses): array
    {
        return $this->importService()->analyzeRows($rows, $warehouses);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{rows: list<array<string, mixed>>, success_count: int, skipped_count: int, registered_warehouse_count: int}
     */
    public function confirmRows(array $rows, EloquentCollection $warehouses): array
    {
        return $this->importService()->confirmRows($rows, $warehouses, $this);
    }

    private function importService(): MasterBarangImportService
    {
        return $this->masterBarangImportService ?? new MasterBarangImportService;
    }
}
