<?php

declare(strict_types=1);

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\SaveDivisiPemintaRequest;
use App\Models\DivisiPeminta;
use App\Models\User;
use App\Services\MasterData\DivisiPemintaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DivisiPemintaController extends Controller
{
    public function __construct(private readonly DivisiPemintaService $divisiPemintaService) {}

    public function store(SaveDivisiPemintaRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $area = $this->divisiPemintaService->create(
            $user,
            $request->validated('area_gudang_id') ?? $request->session()->get('active_gudang_id'),
            $request->validated('nama'),
        );

        return redirect()->route('master.barang.index', [
            'tab' => 'area',
            'area_gudang_id' => $area->gudang_id,
            'area_periode' => $request->validated('area_periode'),
        ])->with('success', "Area/peminta {$area->nama} berhasil ditambahkan.");
    }

    public function update(
        SaveDivisiPemintaRequest $request,
        DivisiPeminta $area,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $area = $this->divisiPemintaService->update(
            $user,
            $area,
            $request->validated('area_gudang_id') ?? $request->session()->get('active_gudang_id'),
            $request->validated('nama'),
        );

        return redirect()->route('master.barang.index', [
            'tab' => 'area',
            'area_gudang_id' => $area->gudang_id,
            'area_periode' => $request->validated('area_periode'),
        ])->with('success', 'Nama area/peminta berhasil diperbarui.');
    }

    public function toggle(Request $request, DivisiPeminta $area): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $filters = $request->validate([
            'area_periode' => ['nullable', 'date_format:Y-m'],
        ]);

        $area = $this->divisiPemintaService->toggle(
            $user,
            $area,
            $request->input('area_gudang_id') ?? $request->session()->get('active_gudang_id'),
        );
        $status = $area->is_aktif ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('master.barang.index', [
            'tab' => 'area',
            'area_gudang_id' => $area->gudang_id,
            'area_periode' => $filters['area_periode'] ?? null,
        ])->with('success', "Area/peminta {$area->nama} berhasil {$status}.");
    }

    public function destroy(Request $request, DivisiPeminta $area): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $name = $area->nama;
        $warehouseId = $area->gudang_id;

        $this->divisiPemintaService->delete(
            $user,
            $area,
            $request->input('area_gudang_id') ?? $request->session()->get('active_gudang_id'),
        );

        return redirect()->route('master.barang.index', [
            'tab' => 'area',
            'area_gudang_id' => $warehouseId,
            'area_periode' => $request->input('area_periode'),
        ])->with('success', "Area/peminta {$name} berhasil dihapus.");
    }
}
