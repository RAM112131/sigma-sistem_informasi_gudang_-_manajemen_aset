<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\AdminGudangUmum;

use Illuminate\Foundation\Http\FormRequest;

final class StorePermintaanBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('permintaan_barang.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nomor_pr' => ['nullable', 'string', 'max:50'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.barang_id' => ['required', 'integer', 'distinct', 'exists:barangs,id'],
            'items.*.qty_diminta' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'items.*.catatan' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
