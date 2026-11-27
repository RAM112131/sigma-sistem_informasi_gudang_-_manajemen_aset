<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis;

use App\Models\Barang;
use App\Services\Shared\ActiveGudangResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUnitStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kode' => strtoupper(trim((string) $this->input('kode'))),
            'nama' => trim((string) $this->input('nama')),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(ActiveGudangResolver $gudangResolver): array
    {
        $activeGudang = $gudangResolver->resolveUnit($this);
        $barang = $this->route('barang');
        $barangId = $barang instanceof Barang ? $barang->id : null;

        return [
            'kode' => [
                'required',
                'string',
                'max:50',
                Rule::unique('barangs', 'kode')
                    ->where(fn ($query) => $query->where('gudang_id', $activeGudang->id))
                    ->ignore($barangId),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'kategori_id' => [
                'nullable',
                'integer',
                Rule::exists('kategori_barangs', 'id')->where(
                    fn ($query) => $query->where('is_aktif', true)
                        ->whereNull('deleted_at')
                        ->where(fn ($warehouseQuery) => $warehouseQuery
                            ->whereNull('gudang_id')
                            ->orWhere('gudang_id', $activeGudang->id))
                ),
            ],
            'satuan_id' => [
                'nullable',
                'integer',
                Rule::exists('satuans', 'id')->where(
                    fn ($query) => $query->where('is_aktif', true)
                        ->whereNull('deleted_at')
                        ->where(fn ($warehouseQuery) => $warehouseQuery
                            ->whereNull('gudang_id')
                            ->orWhere('gudang_id', $activeGudang->id))
                ),
            ],
            'minimum_stok' => ['required', 'integer', 'min:0'],
            'harga_satuan' => ['nullable', 'numeric', 'min:0'],
            'deskripsi' => ['nullable', 'string'],
        ];
    }
}
