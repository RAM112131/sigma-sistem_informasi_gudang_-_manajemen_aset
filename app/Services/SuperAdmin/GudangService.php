<?php

declare(strict_types=1);

namespace App\Services\SuperAdmin;

use App\Models\Gudang;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GudangService
{
    /**
     * @param  array{search?: string, jenis?: string, status?: string}  $filters
     */
    public function paginate(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $search = trim($filters['search'] ?? '');

        return Gudang::query()
            ->select(['id', 'kode', 'nama', 'jenis', 'is_aktif'])
            ->withCount(['users', 'barangs as items_count'])
            ->when($search !== '', static function (Builder $query) use ($search): void {
                $query->where(static function (Builder $matching) use ($search): void {
                    $matching->where('kode', 'like', '%'.mb_strtoupper($search).'%')
                        ->orWhereRaw('LOWER(nama) LIKE ?', ['%'.mb_strtolower($search).'%']);
                });
            })
            ->when(($filters['jenis'] ?? '') !== '', static fn (Builder $query): Builder => $query->where('jenis', $filters['jenis']))
            ->when(($filters['status'] ?? '') !== '', static fn (Builder $query): Builder => $query->where(
                'is_aktif',
                $filters['status'] === 'active',
            ))
            ->orderBy('nama')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Gudang
    {
        return DB::transaction(static fn (): Gudang => Gudang::create($attributes));
    }

    /** @param array<string, mixed> $attributes */
    public function update(Gudang $gudang, array $attributes): Gudang
    {
        return DB::transaction(static function () use ($gudang, $attributes): Gudang {
            $gudang->update($attributes);

            return $gudang;
        });
    }

    /**
     * @return array{deleted: bool, name: string, reason: ?string}
     */
    public function delete(Gudang $gudang): array
    {
        return DB::transaction(static function () use ($gudang): array {
            $name = $gudang->nama;

            if ($gudang->users()->exists()) {
                return [
                    'deleted' => false,
                    'name' => $name,
                    'reason' => "Gudang {$name} tidak dapat dihapus karena masih ditugaskan kepada user aktif.",
                ];
            }

            if ($gudang->barangs()->exists()) {
                return [
                    'deleted' => false,
                    'name' => $name,
                    'reason' => "Gudang {$name} tidak dapat dihapus karena masih memiliki barang terdaftar.",
                ];
            }

            $gudang->delete();

            return ['deleted' => true, 'name' => $name, 'reason' => null];
        });
    }
}
