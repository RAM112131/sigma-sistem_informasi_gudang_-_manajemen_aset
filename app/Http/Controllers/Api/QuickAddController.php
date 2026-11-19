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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class QuickAddController extends Controller
{
    public function __construct(
        private readonly MasterBarangService $masterBarangService,
        private readonly SatuanService $satuanService,
    ) {}

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

    public function storeBarang(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['nullable', 'string', 'max:50'],
            'kategori_id' => ['required', Rule::exists('kategori_barangs', 'id')->where('is_aktif', true)],
            'satuan_id' => ['required', Rule::exists('satuans', 'id')->where('is_aktif', true)],
            'minimum_stok' => ['nullable', 'integer', 'min:0'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $gudang = $this->activeWarehouse($request);
        $companyCode = strtoupper(trim($validated['kode'] ?? ''));
        if ($companyCode === '') {
            $companyCode = 'BRG-'.strtoupper(Str::random(10));
        }

        $masterBarang = $this->masterBarangService->findOrCreateForLocalCode(
            $companyCode,
            trim($validated['nama']),
            (int) $validated['kategori_id'],
            (int) $validated['satuan_id'],
            $validated['deskripsi'] ?? null,
        );

        $barang = $masterBarang->barangs()->where('gudang_id', $gudang->id)->first();
        if ($barang && ! $barang->is_aktif) {
            throw ValidationException::withMessages([
                'kode' => "Barang '{$barang->nama}' telah terdaftar tetapi dinonaktifkan di {$gudang->nama}.",
            ]);
        }

        if (! $barang) {
            $barang = $this->masterBarangService->registerInWarehouse(
                $masterBarang,
                $gudang,
                null,
                (int) ($validated['minimum_stok'] ?? 10),
            );
        }

        $barang->load(['kategori', 'satuan']);

        return response()->json([
            'success' => true,
            'message' => "Barang '{$barang->nama}' berhasil didaftarkan ke {$gudang->nama}.",
            'barang' => [
                'id' => $barang->id,
                'kode' => $barang->kode,
                'nama' => $barang->nama,
                'kategori_id' => $barang->kategori_id,
                'kategori_nama' => $barang->kategori?->nama,
                'satuan_id' => $barang->satuan_id,
                'satuan_nama' => $barang->satuan?->nama,
                'minimum_stok' => $barang->minimum_stok,
            ],
        ]);
    }

    public function storeKategori(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
        ]);

        $kategori = KategoriBarang::create([
            'gudang_id' => null,
            'nama' => trim($validated['nama']),
            'is_aktif' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Kategori '{$kategori->nama}' berhasil dibuat.",
            'kategori' => ['id' => $kategori->id, 'nama' => $kategori->nama],
        ]);
    }

    public function storeSatuan(Request $request): JsonResponse
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
            $satuan = $this->satuanService->create([
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
