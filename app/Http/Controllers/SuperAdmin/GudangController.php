<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\IndexGudangRequest;
use App\Http\Requests\SuperAdmin\StoreGudangRequest;
use App\Http\Requests\SuperAdmin\UpdateGudangRequest;
use App\Models\Gudang;
use App\Services\SuperAdmin\GudangService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class GudangController extends Controller
{
    public function __construct(
        private readonly GudangService $gudangService,
    ) {}

    /**
     * Display a listing of master warehouse locations.
     */
    public function index(IndexGudangRequest $request): View
    {
        $filters = $request->validated();
        $filters['jenis'] = $filters['jenis'] ?? '';
        $filters['status'] = $filters['status'] ?? '';
        $filters['search'] = trim($filters['search'] ?? '');
        $gudangs = $this->gudangService->paginate($filters);

        return view('super-admin.gudang.index', [
            'gudangs' => $gudangs,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the warehouse creation form.
     */
    public function create(): View
    {
        return view('super-admin.gudang.form', [
            'gudang' => null,
        ]);
    }

    /**
     * Store a newly created warehouse location in database.
     */
    public function store(StoreGudangRequest $request): RedirectResponse
    {
        $gudang = $this->gudangService->create($request->validated());

        return redirect()->route('admin.gudang.index')->with('success', "Lokasi gudang {$gudang->nama} ({$gudang->kode}) berhasil ditambahkan.");
    }

    /**
     * Show the warehouse editing form.
     */
    public function edit(Gudang $gudang): View
    {
        return view('super-admin.gudang.form', [
            'gudang' => $gudang,
        ]);
    }

    /**
     * Update the specified warehouse location in database.
     */
    public function update(UpdateGudangRequest $request, Gudang $gudang): RedirectResponse
    {
        $gudang = $this->gudangService->update($gudang, $request->validated());

        return redirect()->route('admin.gudang.index')->with('success', "Data gudang {$gudang->nama} ({$gudang->kode}) berhasil diperbarui.");
    }

    /**
     * Remove the specified warehouse location from database.
     */
    public function destroy(Gudang $gudang): RedirectResponse
    {
        $result = $this->gudangService->delete($gudang);
        if (! $result['deleted']) {
            return redirect()->route('admin.gudang.index')->with('error', $result['reason']);
        }

        return redirect()->route('admin.gudang.index')->with('success', "Gudang {$result['name']} berhasil dihapus.");
    }
}
