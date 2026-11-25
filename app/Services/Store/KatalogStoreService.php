<?php

declare(strict_types=1);

namespace App\Services\Store;

use App\Models\AuditLog;
use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\PenerimaanBarangStore;
use App\Models\Satuan;
use App\Services\MasterData\MasterBarangService;
use App\Services\SemanticVectorSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final readonly class KatalogStoreService
{
    public function __construct(
        private SemanticVectorSearch $semanticSearch,
        private MasterBarangService $masterBarangService,
    ) {}

    /**
     * @return array{
     *     barangs: LengthAwarePaginator,
     *     kategoriList: Collection,
     *     satuanList: Collection,
     *     auditLogs: Collection
     * }
     */
    public function getListingData(
        string $search,
        string $searchInputKey,
        string|int|null $kategoriId,
        string|int|null $statusFilter,
        int $perPage = 15
    ): array {
        $query = Barang::with(['kategori', 'satuan', 'gudang']);

        if ($search !== '') {
            $this->semanticSearch->apply($query, 'barangs', $search, $searchInputKey);
        }

        if ($kategoriId) {
            $query->where('kategori_id', $kategoriId);
        }

        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('is_aktif', (bool) $statusFilter);
        }

        return [
            'barangs' => $query->orderBy('nama')->paginate($perPage)->withQueryString(),
            'kategoriList' => KategoriBarang::query()
                ->orderBy('nama')
                ->get(['id', 'nama']),
            'satuanList' => Satuan::query()
                ->where('is_aktif', true)
                ->orderBy('nama')
                ->get(['id', 'kode', 'nama']),
            'auditLogs' => AuditLog::where('subject_type', Barang::class)
                ->orWhere('action', 'LIKE', '%BARANG%')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, ?string $fotoPath, int $userId): Barang
    {
        return DB::transaction(function () use ($attributes, $fotoPath, $userId): Barang {
            $gudangStore = Gudang::query()
                ->whereIn('jenis', ['holding', 'global_master', 'gudang_store'])
                ->firstOrFail();
            $kode = strtoupper(trim($attributes['kode']));
            $masterBarang = $this->masterBarangService->findOrCreateForLocalCode(
                $kode,
                trim($attributes['nama']),
                (int) $attributes['kategori_id'],
                (int) $attributes['satuan_id'],
                $attributes['deskripsi'] ?? null,
            );
            if ($fotoPath !== null && $masterBarang->foto_path === null) {
                $masterBarang->update(['foto_path' => $fotoPath]);
            }

            $barang = $this->masterBarangService->registerInWarehouse(
                $masterBarang,
                $gudangStore,
                $kode,
                (int) $attributes['minimum_stok'],
            );
            if ($fotoPath !== null) {
                $barang->update(['foto_path' => $fotoPath]);
            }

            $stokAwal = (int) ($attributes['stok_awal'] ?? 0);
            if ($stokAwal > 0 && $gudangStore) {
                PenerimaanBarangStore::create([
                    'nomor_penerimaan' => 'RCV-INIT-'.now()->format('Ymd').'-'.sprintf('%04d', $barang->id),
                    'gudang_id' => $gudangStore->id,
                    'barang_id' => $barang->id,
                    'qty_diterima' => $stokAwal,
                    'satuan_id' => $attributes['satuan_id'],
                    'sumber_supplier' => 'Stok Awal (Registrasi Barang Baru)',
                    'tanpa_surat_jalan' => true,
                    'alasan_tanpa_sj' => 'Stok awal saat pendaftaran barang baru ke katalog.',
                    'diterima_oleh' => $userId,
                    'tanggal_diterima' => now(),
                ]);
            }

            return $barang->load('satuan');
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Barang $barang, array $attributes): Barang
    {
        return DB::transaction(function () use ($barang, $attributes): Barang {
            if ($barang->masterBarang) {
                $this->masterBarangService->update($barang->masterBarang, [
                    'kode' => $barang->masterBarang->kode,
                    'nama' => $attributes['nama'],
                    'kategori_id' => $attributes['kategori_id'],
                    'satuan_id' => $attributes['satuan_id'],
                    'harga_satuan' => $attributes['harga_satuan'] ?? 0,
                    'deskripsi' => $attributes['deskripsi'] ?? null,
                    'is_aktif' => $barang->masterBarang->is_aktif,
                ]);
            }

            $barang->update([
                'kode' => strtoupper(trim($attributes['kode'])),
                'nama' => trim($attributes['nama']),
                'kategori_id' => $attributes['kategori_id'],
                'satuan_id' => $attributes['satuan_id'],
                'minimum_stok' => $attributes['minimum_stok'],
                'harga_satuan' => $attributes['harga_satuan'] ?? 0,
                'deskripsi' => $attributes['deskripsi'] ?? null,
                'is_aktif' => $attributes['is_aktif'],
            ]);

            return $barang;
        });
    }

    public function toggle(Barang $barang): Barang
    {
        return DB::transaction(function () use ($barang): Barang {
            $barang->update(['is_aktif' => ! $barang->is_aktif]);

            return $barang;
        });
    }

    public function delete(Barang $barang): string
    {
        return DB::transaction(function () use ($barang): string {
            $nama = $barang->nama;
            $barang->delete();

            return $nama;
        });
    }
}
