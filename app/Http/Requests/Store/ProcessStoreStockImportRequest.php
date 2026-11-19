<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

final class ProcessStoreStockImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('store_stok.create') || $this->user()?->can('stok_awal.import') ?? false;
    }

    public function rules(): array
    {
        return [
            'tanggal_acuan' => ['required', 'date'],
            'catatan_umum' => ['nullable', 'string', 'max:500'],
            'rows' => ['required', 'array', 'min:1', 'max:50000'],
            'rows.*.row' => ['nullable', 'integer', 'min:1'],
            'rows.*.kode_barang' => ['required', 'string'],
            'rows.*.nama_barang' => ['required', 'string'],
            'rows.*.kategori' => ['required', 'string'],
            'rows.*.satuan' => ['required', 'string'],
            'rows.*.qty' => ['required', 'integer', 'min:0'],
            'rows.*.catatan' => ['nullable', 'string'],
        ];
    }
}
