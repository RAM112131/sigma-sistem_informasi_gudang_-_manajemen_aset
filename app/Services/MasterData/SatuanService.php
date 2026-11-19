<?php

declare(strict_types=1);

namespace App\Services\MasterData;

use App\Models\Satuan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SatuanService
{
    private const array UNIT_ALIASES = [
        'liter' => ['kode' => 'ltr', 'nama' => 'Liter'],
        'litre' => ['kode' => 'ltr', 'nama' => 'Liter'],
        'liters' => ['kode' => 'ltr', 'nama' => 'Liter'],
        'litres' => ['kode' => 'ltr', 'nama' => 'Liter'],
        'ltr' => ['kode' => 'ltr', 'nama' => 'Liter'],
        'kilogram' => ['kode' => 'kg', 'nama' => 'Kilogram'],
        'kilograms' => ['kode' => 'kg', 'nama' => 'Kilogram'],
        'kg' => ['kode' => 'kg', 'nama' => 'Kilogram'],
        'gram' => ['kode' => 'g', 'nama' => 'Gram'],
        'gr' => ['kode' => 'g', 'nama' => 'Gram'],
        'g' => ['kode' => 'g', 'nama' => 'Gram'],
        'meter' => ['kode' => 'm', 'nama' => 'Meter'],
        'metre' => ['kode' => 'm', 'nama' => 'Meter'],
        'm' => ['kode' => 'm', 'nama' => 'Meter'],
        'centimeter' => ['kode' => 'cm', 'nama' => 'Centimeter'],
        'centimetre' => ['kode' => 'cm', 'nama' => 'Centimeter'],
        'cm' => ['kode' => 'cm', 'nama' => 'Centimeter'],
        'millimeter' => ['kode' => 'mm', 'nama' => 'Millimeter'],
        'millimetre' => ['kode' => 'mm', 'nama' => 'Millimeter'],
        'mm' => ['kode' => 'mm', 'nama' => 'Millimeter'],
        'mililiter' => ['kode' => 'ml', 'nama' => 'Mililiter'],
        'milliliter' => ['kode' => 'ml', 'nama' => 'Mililiter'],
        'ml' => ['kode' => 'ml', 'nama' => 'Mililiter'],
        'buah' => ['kode' => 'pcs', 'nama' => 'Pcs'],
        'piece' => ['kode' => 'pcs', 'nama' => 'Pcs'],
        'pieces' => ['kode' => 'pcs', 'nama' => 'Pcs'],
        'pc' => ['kode' => 'pcs', 'nama' => 'Pcs'],
        'pcs' => ['kode' => 'pcs', 'nama' => 'Pcs'],
        'box' => ['kode' => 'box', 'nama' => 'Box'],
        'bx' => ['kode' => 'box', 'nama' => 'Box'],
        'dus' => ['kode' => 'dus', 'nama' => 'Dus'],
        'dos' => ['kode' => 'dus', 'nama' => 'Dus'],
        'pak' => ['kode' => 'pak', 'nama' => 'Pak'],
        'pack' => ['kode' => 'pak', 'nama' => 'Pak'],
        'roll' => ['kode' => 'roll', 'nama' => 'Roll'],
        'rol' => ['kode' => 'roll', 'nama' => 'Roll'],
        'lembar' => ['kode' => 'lbr', 'nama' => 'Lembar'],
        'lbr' => ['kode' => 'lbr', 'nama' => 'Lembar'],
        'batang' => ['kode' => 'btg', 'nama' => 'Batang'],
        'btg' => ['kode' => 'btg', 'nama' => 'Batang'],
        'botol' => ['kode' => 'btl', 'nama' => 'Botol'],
        'btl' => ['kode' => 'btl', 'nama' => 'Botol'],
        'set' => ['kode' => 'set', 'nama' => 'Set'],
    ];

    /**
     * @return array<string, array{kode: string, nama: string}>
     */
    public static function aliases(): array
    {
        return self::UNIT_ALIASES;
    }

    /**
     * @return array{kode: string, nama: string}
     */
    public static function normalizeInput(string $value): array
    {
        $value = trim($value);
        $normalizedValue = mb_strtolower($value);

        if (isset(self::UNIT_ALIASES[$normalizedValue])) {
            return self::UNIT_ALIASES[$normalizedValue];
        }

        return [
            'kode' => mb_strtolower(Str::slug($value, '')),
            'nama' => mb_convert_case($value, MB_CASE_TITLE, 'UTF-8'),
        ];
    }

    public static function isKnownAlias(string $value): bool
    {
        return isset(self::UNIT_ALIASES[mb_strtolower(trim($value))]);
    }

    public function getListingData(
        string $search = '',
        string|int|null $status = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = Satuan::query()
            ->withCount('masterBarangs');

        if ($search !== '') {
            $pattern = '%'.mb_strtolower($search).'%';
            $query->where(function (Builder $matching) use ($pattern): void {
                $matching->whereRaw('LOWER(kode) LIKE ?', [$pattern])
                    ->orWhereRaw('LOWER(nama) LIKE ?', [$pattern]);
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('is_aktif', (bool) $status);
        }

        return $query->latest()->paginate($perPage)->withQueryString();
    }

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Satuan
    {
        $normalized = self::normalizeInput($attributes['kode']);
        $name = self::isKnownAlias($attributes['kode'])
            ? $normalized['nama']
            : (trim($attributes['nama'] ?? '') ?: $normalized['nama']);

        return DB::transaction(static fn (): Satuan => Satuan::create([
            'kode' => $normalized['kode'],
            'nama' => $name,
            'is_aktif' => $attributes['is_aktif'] ?? true,
        ]));
    }

    /** @param array<string, mixed> $attributes */
    public function update(Satuan $satuan, array $attributes): Satuan
    {
        return DB::transaction(static function () use ($satuan, $attributes): Satuan {
            $updates = [
                'kode' => strtolower($attributes['kode']),
                'nama' => $attributes['nama'],
                'is_aktif' => $attributes['is_aktif'] ?? $satuan->is_aktif,
            ];

            if ($satuan->masterBarangs()->exists()) {
                $updates['kode'] = $satuan->kode;
            }

            $satuan->update($updates);

            return $satuan;
        });
    }

    public function toggle(Satuan $satuan): Satuan
    {
        return DB::transaction(static function () use ($satuan): Satuan {
            $satuan->update(['is_aktif' => ! $satuan->is_aktif]);

            return $satuan;
        });
    }

    public function delete(Satuan $satuan): void
    {
        DB::transaction(static fn () => $satuan->delete());
    }
}
