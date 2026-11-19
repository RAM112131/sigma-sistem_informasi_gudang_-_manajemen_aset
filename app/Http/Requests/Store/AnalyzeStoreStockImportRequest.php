<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

final class AnalyzeStoreStockImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('store_stok.create') || $this->user()?->can('stok_awal.import') ?? false;
    }

    public function rules(): array
    {
        return [
            'rows' => ['required', 'array', 'min:1', 'max:50000'],
            'rows.*' => ['required', 'array'],
            'rows.*.row' => ['nullable', 'integer', 'min:1'],
            'rows.*.kode_barang' => ['nullable', 'string', 'max:100'],
            'rows.*.nama_barang' => ['nullable', 'string', 'max:255'],
            'rows.*.kategori' => ['nullable', 'string', 'max:255'],
            'rows.*.satuan' => ['nullable', 'string', 'max:50'],
            'rows.*.qty' => ['nullable', 'string', 'max:50'],
            'rows.*.kondisi' => ['nullable', 'string', 'max:100'],
            'rows.*.catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
