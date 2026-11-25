<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Gudang;
use App\Models\KategoriBarang;
use App\Models\Satuan;
use App\Models\User;
use App\Services\MasterData\MasterBarangService;
use App\Services\MasterData\SatuanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class QuickAddController extends Controller
{
    public function checkSimilarity(Request $request): JsonResponse
    {
        $name = trim((string) $request->query('nama', ''));
        if (mb_strlen($name) < 2) {
            return response()->json(['has_similar' => false, 'similar_items' => []]);
        }

        $gudang = $this->activeWarehouse($request);
        $keywords = array_filter(explode(' ', Str::lower($name)), fn (string $word): bool => mb_strlen($word) >= 3);
        $query = Barang::query()
            ->with(['kategori:id,nama', 'satuan:id,nama'])
            ->where('gudang_id', $gudang->id)
            ->where('is_aktif', true)
            ->where(function (Builder $query) use ($name, $keywords): void {
                $query->where('nama', 'like', "%{$name}%");
                foreach ($keywords as $keyword) {
                    $query->orWhere('nama', 'like', "%{$keyword}%");
                }
            });

        $similarItems = $query->limit(5)->get(['id', 'kode', 'nama', 'kategori_id', 'satuan_id']);

        return response()->json([
            'has_similar' => $similarItems->isNotEmpty(),
            'similar_items' => $similarItems->map(fn (Barang $item): array => [
                'id' => $item->id,
                'kode' => $item->kode,
                'nama' => $item->nama,
                'kategori' => $item->kategori?->nama ?? 'Umum',
                'satuan' => $item->satuan?->nama ?? 'unit',
            ]),
        ]);
    }

    public function storeBarang(Request $request, MasterBarangService $masterBarangService): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['nullable', 'string', 'max:50'],
            'kategori_id' => ['required', 'integer'],
            'satuan_id' => ['required', 'integer'],
            'minimum_stok' => ['nullable', 'integer', 'min:0'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $references = DB::table('kategori_barangs as kategori')
            ->crossJoin('satuans as satuan')
            ->where('kategori.id', (int) $validated['kategori_id'])
            ->where('kategori.is_aktif', true)
            ->whereNull('kategori.deleted_at')
            ->where('satuan.id', (int) $validated['satuan_id'])
            ->where('satuan.is_aktif', true)
            ->whereNull('satuan.deleted_at')
            ->select([
                'kategori.id as kategori_id',
                'kategori.nama as kategori_nama',
                'satuan.id as satuan_id',
                'satuan.kode as satuan_kode',
                'satuan.nama as satuan_nama',
            ])
            ->first();

        if (! $references) {
            $errors = [];

            if (! DB::table('kategori_barangs')
                ->where('id', (int) $validated['kategori_id'])
                ->where('is_aktif', true)
                ->whereNull('deleted_at')
                ->exists()) {
                $errors['kategori_id'] = 'Kategori tidak ditemukan atau tidak aktif.';
            }

            if (! DB::table('satuans')
                ->where('id', (int) $validated['satuan_id'])
                ->where('is_aktif', true)
                ->whereNull('deleted_at')
                ->exists()) {
                $errors['satuan_id'] = 'Satuan tidak ditemukan atau tidak aktif.';
            }

            throw ValidationException::withMessages($errors);
        }

        $gudang = $this->activeWarehouse($request);
        $companyCode = strtoupper(trim($validated['kode'] ?? ''));
        if ($companyCode === '') {
            $companyCode = 'BRG-'.strtoupper(Str::random(10));
        }

        $masterBarang = $masterBarangService->findOrCreateForLocalCode(
            $companyCode,
            trim($validated['nama']),
            (int) $references->kategori_id,
            (int) $references->satuan_id,
            $validated['deskripsi'] ?? null,
            $references->kategori_nama,
            $references->satuan_kode,
        );

        $barang = $masterBarangService->registerInWarehouse(
            $masterBarang,
            $gudang,
            null,
            (int) ($validated['minimum_stok'] ?? 10),
            returnExisting: true,
        );

        $kategoriNama = $references->kategori_nama;
        $satuanNama = $references->satuan_nama;
        if (
            (int) $barang->kategori_id !== (int) $references->kategori_id
            || (int) $barang->satuan_id !== (int) $references->satuan_id
        ) {
            $itemReferences = DB::table('kategori_barangs as kategori')
                ->crossJoin('satuans as satuan')
                ->where('kategori.id', $barang->kategori_id)
                ->where('satuan.id', $barang->satuan_id)
                ->select([
                    'kategori.nama as kategori_nama',
                    'satuan.nama as satuan_nama',
                ])
                ->first();
            $kategoriNama = $itemReferences?->kategori_nama;
            $satuanNama = $itemReferences?->satuan_nama;
        }

        return response()->json([
            'success' => true,
            'message' => "Barang '{$barang->nama}' berhasil didaftarkan ke {$gudang->nama}.",
            'barang' => [
                'id' => $barang->id,
                'kode' => $barang->kode,
                'nama' => $barang->nama,
                'kategori_id' => $barang->kategori_id,
                'kategori_nama' => $kategoriNama,
                'satuan_id' => $barang->satuan_id,
                'satuan_nama' => $satuanNama,
                'minimum_stok' => $barang->minimum_stok,
            ],
        ]);
    }

    public function storeKategori(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
        ]);

        $nama = trim($validated['nama']);
        $kategori = KategoriBarang::query()
            ->whereRaw('LOWER(nama) = ?', [Str::lower($nama)])
            ->where('is_aktif', true)
            ->first();

        if (! $kategori) {
            $kategori = KategoriBarang::create([
                'gudang_id' => null,
                'nama' => $nama,
                'is_aktif' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Kategori '{$kategori->nama}' berhasil dibuat.",
            'kategori' => ['id' => $kategori->id, 'nama' => $kategori->nama],
        ]);
    }

    public function storeSatuan(Request $request, SatuanService $satuanService): JsonResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:100'],
            'nama' => ['nullable', 'string', 'max:100'],
        ]);

        $normalized = SatuanService::normalizeInput($validated['kode']);
        if ($normalized['kode'] === '' || mb_strlen($normalized['kode']) > 20) {
            throw ValidationException::withMessages([
                'kode' => 'Kode satuan otomatis harus berisi 1-20 karakter. Gunakan satuan yang lebih singkat.',
            ]);
        }

        $satuan = Satuan::query()->whereRaw('LOWER(kode) = ?', [$normalized['kode']])->first();
        if (! $satuan) {
            $satuan = $satuanService->create([
                'kode' => $validated['kode'],
                'nama' => $validated['nama'] ?? '',
                'is_aktif' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Satuan '{$satuan->nama}' ({$satuan->kode}) berhasil dibuat.",
            'satuan' => ['id' => $satuan->id, 'kode' => $satuan->kode, 'nama' => $satuan->nama],
        ]);
    }

    private function activeWarehouse(Request $request): Gudang
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $selectedGudangId = (int) $request->session()->get('active_gudang_id', 0);
        $assignedGudangs = $user->gudang()->where('gudangs.is_aktif', true);
        $gudang = $assignedGudangs
            ->when($selectedGudangId > 0, fn ($query) => $query->whereKey($selectedGudangId))
            ->first();

        if (! $gudang) {
            $gudang = $user->gudang()
                ->where('gudangs.is_aktif', true)
                ->orderBy('gudangs.nama')
                ->first();
        }

        if (! $gudang && ($user->hasRole('Super Admin') || $user->hasRole('super_admin'))) {
            $gudang = Gudang::query()
                ->whereIn('jenis', ['holding', 'global_master', 'gudang_store'])
                ->where('is_aktif', true)
                ->first();
        }

        if (! $gudang) {
            throw ValidationException::withMessages([
                'gudang_id' => 'Pilih gudang aktif yang telah ditetapkan ke akun Anda sebelum menggunakan Quick-Add.',
            ]);
        }

        return $gudang;
    }
}
