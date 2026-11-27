<?php

declare(strict_types=1);

namespace App\Services\Shared;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\Satuan;
use App\Services\MasterData\MasterBarangService;
use App\Services\MasterData\SatuanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class QuickAddService
{
    public function __construct(
        private MasterBarangService $masterBarangService,
        private SatuanService $satuanService,
    ) {}

    /**
     * Find up to 5 active items in the given warehouse matching the query name.
     *
     * @return array<int, array{id: int, kode: string, nama: string, kategori: string, satuan: string}>
     */
    public function findSimilarItems(Gudang $gudang, string $name): array
    {
        $trimmedName = trim($name);
        if (mb_strlen($trimmedName) < 2) {
            return [];
        }

        $keywords = array_filter(
            explode(' ', Str::lower($trimmedName)),
            static fn (string $word): bool => mb_strlen($word) >= 3,
        );

        $similarItems = Barang::query()
            ->with(['kategori:id,nama', 'satuan:id,nama'])
            ->where('gudang_id', $gudang->id)
            ->where('is_aktif', true)
            ->where(function (Builder $query) use ($trimmedName, $keywords): void {
                $query->where('nama', 'like', "%{$trimmedName}%");
                foreach ($keywords as $keyword) {
                    $query->orWhere('nama', 'like', "%{$keyword}%");
                }
            })
            ->limit(5)
            ->get(['id', 'kode', 'nama', 'kategori_id', 'satuan_id']);

        return $similarItems->map(static fn (Barang $item): array => [
            'id' => $item->id,
            'kode' => $item->kode,
            'nama' => $item->nama,
            'kategori' => $item->kategori?->nama ?? 'Umum',
            'satuan' => $item->satuan?->nama ?? 'unit',
        ])->all();
    }

    /**
     * Validate active category/unit references, ensure MasterBarang exists, and register local Barang.
     *
     * @param  array{
     *     nama: string,
     *     kode?: ?string,
     *     kategori_id: int|string,
     *     satuan_id: int|string,
     *     minimum_stok?: int|string|null,
     *     deskripsi?: ?string
     * }  $validated
     * @return array{
     *     id: int,
     *     kode: string,
     *     nama: string,
     *     kategori_id: int,
     *     kategori_nama: ?string,
     *     satuan_id: int,
     *     satuan_nama: ?string,
     *     minimum_stok: int
     * }
     *
     * @throws ValidationException
     */
    public function registerBarang(Gudang $gudang, array $validated): array
    {
        $kategori = KategoriBarang::query()
            ->whereKey((int) $validated['kategori_id'])
            ->where('is_aktif', true)
            ->first(['id', 'nama']);

        $satuan = Satuan::query()
            ->whereKey((int) $validated['satuan_id'])
            ->where('is_aktif', true)
            ->first(['id', 'kode', 'nama']);

        if (! $kategori || ! $satuan) {
            $errors = [];
            if (! $kategori) {
                $errors['kategori_id'] = 'Kategori tidak ditemukan atau tidak aktif.';
            }
            if (! $satuan) {
                $errors['satuan_id'] = 'Satuan tidak ditemukan atau tidak aktif.';
            }

            throw ValidationException::withMessages($errors);
        }

        return DB::transaction(function () use ($gudang, $validated, $kategori, $satuan): array {
            $companyCode = strtoupper(trim((string) ($validated['kode'] ?? '')));
            if ($companyCode === '') {
                $companyCode = 'BRG-'.strtoupper(Str::random(10));
            }

            $masterBarang = $this->masterBarangService->findOrCreateForLocalCode(
                $companyCode,
                trim($validated['nama']),
                (int) $kategori->id,
                (int) $satuan->id,
                $validated['deskripsi'] ?? null,
                $kategori->nama,
                $satuan->kode,
            );

            $barang = $this->masterBarangService->registerInWarehouse(
                $masterBarang,
                $gudang,
                null,
                (int) ($validated['minimum_stok'] ?? 10),
                returnExisting: true,
            );

            $kategoriNama = $kategori->nama;
            $satuanNama = $satuan->nama;

            if ((int) $barang->kategori_id !== (int) $kategori->id || (int) $barang->satuan_id !== (int) $satuan->id) {
                $barang->loadMissing(['kategori:id,nama', 'satuan:id,nama']);
                $kategoriNama = $barang->kategori?->nama;
                $satuanNama = $barang->satuan?->nama;
            }

            return [
                'id' => $barang->id,
                'kode' => $barang->kode,
                'nama' => $barang->nama,
                'kategori_id' => $barang->kategori_id,
                'kategori_nama' => $kategoriNama,
                'satuan_id' => $barang->satuan_id,
                'satuan_nama' => $satuanNama,
                'minimum_stok' => $barang->minimum_stok,
            ];
        });
    }

    public function findOrCreateKategori(string $nama): KategoriBarang
    {
        $trimmedNama = trim($nama);

        return DB::transaction(static function () use ($trimmedNama): KategoriBarang {
            $kategori = KategoriBarang::query()
                ->whereRaw('LOWER(nama) = ?', [Str::lower($trimmedNama)])
                ->where('is_aktif', true)
                ->first();

            if ($kategori instanceof KategoriBarang) {
                return $kategori;
            }

            return KategoriBarang::create([
                'gudang_id' => null,
                'nama' => $trimmedNama,
                'is_aktif' => true,
            ]);
        });
    }

    /**
     * @throws ValidationException
     */
    public function findOrCreateSatuan(string $kode, ?string $nama = null): Satuan
    {
        $normalized = SatuanService::normalizeInput($kode);
        if ($normalized['kode'] === '' || mb_strlen($normalized['kode']) > 20) {
            throw ValidationException::withMessages([
                'kode' => 'Kode satuan otomatis harus berisi 1-20 karakter. Gunakan satuan yang lebih singkat.',
            ]);
        }

        return DB::transaction(function () use ($normalized, $kode, $nama): Satuan {
            $satuan = Satuan::query()
                ->where('kode', $normalized['kode'])
                ->first();

            if ($satuan instanceof Satuan) {
                return $satuan;
            }

            return $this->satuanService->create([
                'kode' => $kode,
                'nama' => $nama ?? '',
                'is_aktif' => true,
            ]);
        });
    }
}
