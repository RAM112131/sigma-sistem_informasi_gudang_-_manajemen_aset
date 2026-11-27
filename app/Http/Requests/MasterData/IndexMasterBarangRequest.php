<?php

declare(strict_types=1);

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;

final class IndexMasterBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('gudang_id') && ! is_array($this->input('gudang_id'))) {
            $this->merge(['gudang_id' => [$this->input('gudang_id')]]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'kategori_id' => ['nullable', 'integer'],
            'satuan_id' => ['nullable', 'integer'],
            'gudang_id' => ['nullable', 'array'],
            'gudang_id.*' => ['integer', 'distinct', 'exists:gudangs,id'],
            'status' => ['nullable', 'in:0,1'],
            'registrasi' => ['nullable', 'in:semua,terdaftar,belum'],
            'per_page' => ['nullable', 'in:8,15,20,25,50'],
            'area_gudang_id' => ['nullable', 'integer', 'exists:gudangs,id'],
            'area_periode' => ['nullable', 'date_format:Y-m'],
            'kategori_drawer' => ['nullable', 'integer', 'exists:kategori_barangs,id'],
            'drawer_search' => ['nullable', 'string', 'max:255'],
            'tab' => ['nullable', 'in:barang,kategori,satuan,area'],
            'barang_view' => ['nullable', 'in:catalog,add,import'],
        ];
    }
}
