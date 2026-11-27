<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreQuickAddBarangRequest;
use App\Http\Requests\Api\StoreQuickAddKategoriRequest;
use App\Http\Requests\Api\StoreQuickAddSatuanRequest;
use App\Services\Shared\ActiveGudangResolver;
use App\Services\Shared\QuickAddService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class QuickAddController extends Controller
{
    public function __construct(
        private readonly ActiveGudangResolver $gudangResolver,
        private readonly QuickAddService $quickAddService,
    ) {}

    public function checkSimilarity(Request $request): JsonResponse
    {
        $name = trim((string) $request->query('nama', ''));
        if (mb_strlen($name) < 2) {
            return response()->json(['has_similar' => false, 'similar_items' => []]);
        }

        $gudang = $this->gudangResolver->resolveForQuickAdd($request);
        $similarItems = $this->quickAddService->findSimilarItems($gudang, $name);

        return response()->json([
            'has_similar' => $similarItems !== [],
            'similar_items' => $similarItems,
        ]);
    }

    public function storeBarang(StoreQuickAddBarangRequest $request): JsonResponse
    {
        $gudang = $this->gudangResolver->resolveForQuickAdd($request);
        $barang = $this->quickAddService->registerBarang($gudang, $request->validated());

        return response()->json([
            'success' => true,
            'message' => "Barang '{$barang['nama']}' berhasil didaftarkan ke {$gudang->nama}.",
            'barang' => $barang,
        ]);
    }

    public function storeKategori(StoreQuickAddKategoriRequest $request): JsonResponse
    {
        $kategori = $this->quickAddService->findOrCreateKategori($request->validated('nama'));

        return response()->json([
            'success' => true,
            'message' => "Kategori '{$kategori->nama}' berhasil dibuat.",
            'kategori' => ['id' => $kategori->id, 'nama' => $kategori->nama],
        ]);
    }

    public function storeSatuan(StoreQuickAddSatuanRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $satuan = $this->quickAddService->findOrCreateSatuan($validated['kode'], $validated['nama'] ?? null);

        return response()->json([
            'success' => true,
            'message' => "Satuan '{$satuan->nama}' ({$satuan->kode}) berhasil dibuat.",
            'satuan' => ['id' => $satuan->id, 'kode' => $satuan->kode, 'nama' => $satuan->nama],
        ]);
    }
}
