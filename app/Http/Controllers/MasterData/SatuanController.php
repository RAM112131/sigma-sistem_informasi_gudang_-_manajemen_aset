<?php

declare(strict_types=1);

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreSatuanRequest;
use App\Models\Satuan;
use App\Services\MasterData\SatuanService;
use Illuminate\Http\RedirectResponse;

final class SatuanController extends Controller
{
    public function __construct(
        private readonly SatuanService $satuanService,
    ) {}

    /**
     * Redirect the legacy listing route to the combined master data page.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('master.barang.index', ['tab' => 'satuan']);
    }

    public function store(StoreSatuanRequest $request): RedirectResponse
    {
        $this->satuanService->create($request->validated());

        return redirect()->route('master.barang.index', ['tab' => 'satuan'])
            ->with('success', 'Satuan barang berhasil ditambahkan.');
    }

    public function update(StoreSatuanRequest $request, Satuan $satuan): RedirectResponse
    {
        $this->satuanService->update($satuan, $request->validated());

        return redirect()->route('master.barang.index', ['tab' => 'satuan'])
            ->with('success', 'Satuan barang berhasil diperbarui.');
    }

    public function toggle(Satuan $satuan): RedirectResponse
    {
        $satuan = $this->satuanService->toggle($satuan);
        $status = $satuan->is_aktif ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('master.barang.index', ['tab' => 'satuan'])
            ->with('success', "Satuan '{$satuan->kode}' berhasil {$status}.");
    }

    public function destroy(Satuan $satuan): RedirectResponse
    {
        $this->satuanService->delete($satuan);

        return redirect()->route('master.barang.index', ['tab' => 'satuan'])
            ->with('success', 'Satuan barang berhasil dihapus.');
    }
}
