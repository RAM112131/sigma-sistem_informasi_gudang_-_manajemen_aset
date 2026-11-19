<?php

declare(strict_types=1);

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreKategoriRequest;
use App\Models\KategoriBarang;
use App\Services\MasterData\KategoriService;
use Illuminate\Http\RedirectResponse;

final class KategoriController extends Controller
{
    public function __construct(
        private readonly KategoriService $kategoriService,
    ) {}

    /**
     * Redirect the legacy category route to the combined master data page.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('master.barang.index', ['tab' => 'kategori']);
    }

    public function store(StoreKategoriRequest $request): RedirectResponse
    {
        $this->kategoriService->create($request->validated());

        return redirect()->route('master.barang.index', ['tab' => 'kategori'])
            ->with('success', 'Kategori barang berhasil ditambahkan.');
    }

    public function update(StoreKategoriRequest $request, KategoriBarang $kategori): RedirectResponse
    {
        $this->kategoriService->update($kategori, $request->validated());

        return redirect()->route('master.barang.index', ['tab' => 'kategori'])
            ->with('success', 'Kategori barang berhasil diperbarui.');
    }

    public function toggle(KategoriBarang $kategori): RedirectResponse
    {
        $kategori = $this->kategoriService->toggle($kategori);
        $status = $kategori->is_aktif ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('master.barang.index', ['tab' => 'kategori'])
            ->with('success', "Kategori {$kategori->nama} berhasil {$status}.");
    }
}
