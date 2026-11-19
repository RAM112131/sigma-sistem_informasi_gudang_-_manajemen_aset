<?php

declare(strict_types=1);

namespace App\Services\MasterData;

use App\Models\KategoriBarang;
use App\Models\MasterBarang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class KategoriService
{
    /**
     * @return array{
     *     kategoris: LengthAwarePaginator,
     *     categoryDrawer: ?KategoriBarang,
     *     categoryBarang: ?LengthAwarePaginator
     * }
     */
    public function getListingData(
        string $search = '',
        ?string $status = null,
        int $perPage = 20,
        ?int $drawerCategoryId = null,
        string $drawerSearch = '',
    ): array {
        $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $categories = KategoriBarang::query()
            ->withCount([
                'masterBarangs',
                'masterBarangs as active_master_barangs_count' => fn (Builder $query): Builder => $query
                    ->where('is_aktif', true),
            ])
            ->when($search !== '', fn (Builder $query): Builder => $query->where('nama', $operator, '%'.$search.'%'))
            ->when($status !== null && $status !== '', fn (Builder $query): Builder => $query->where('is_aktif', (bool) $status))
            ->orderBy('nama')
            ->paginate($perPage, ['*'], 'kategori_page')
            ->withQueryString();

        $categoryDrawer = $drawerCategoryId === null
            ? null
            : KategoriBarang::query()->findOrFail($drawerCategoryId);
        $categoryBarang = null;

        if ($categoryDrawer !== null) {
            $categoryBarangQuery = MasterBarang::query()
                ->with('satuan:id,nama')
                ->where('kategori_id', $categoryDrawer->id);

            if ($drawerSearch !== '') {
                $pattern = '%'.$drawerSearch.'%';
                $categoryBarangQuery->where(function (Builder $query) use ($operator, $pattern): void {
                    $query->where('kode', $operator, $pattern)
                        ->orWhere('nama', $operator, $pattern);
                });
            }

            $categoryBarang = $categoryBarangQuery
                ->orderBy('nama')
                ->paginate(8, ['*'], 'drawer_page')
                ->withQueryString();
        }

        return [
            'kategoris' => $categories,
            'categoryDrawer' => $categoryDrawer,
            'categoryBarang' => $categoryBarang,
        ];
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): KategoriBarang
    {
        return DB::transaction(static fn (): KategoriBarang => KategoriBarang::create([
            'nama' => trim($attributes['nama']),
            'deskripsi' => $attributes['deskripsi'] ?? null,
            'gudang_id' => null,
            'is_aktif' => (bool) ($attributes['is_aktif'] ?? true),
        ]));
    }

    /** @param array<string, mixed> $attributes */
    public function update(KategoriBarang $kategori, array $attributes): KategoriBarang
    {
        return DB::transaction(static function () use ($kategori, $attributes): KategoriBarang {
            $kategori->update([
                'nama' => trim($attributes['nama']),
                'deskripsi' => $attributes['deskripsi'] ?? null,
                'is_aktif' => (bool) ($attributes['is_aktif'] ?? $kategori->is_aktif),
            ]);

            return $kategori;
        });
    }

    public function toggle(KategoriBarang $kategori): KategoriBarang
    {
        return DB::transaction(static function () use ($kategori): KategoriBarang {
            $kategori->update(['is_aktif' => ! $kategori->is_aktif]);

            return $kategori;
        });
    }
}
