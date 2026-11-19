<?php

declare(strict_types=1);

namespace App\Http\Requests\MasterData;

final class AnalyzeMasterBarangImportRequest extends MasterBarangImportRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('barang.import') ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'warehouse_ids' => ['required', 'array', 'min:1', 'max:20'],
            'warehouse_ids.*' => ['required', 'integer', 'distinct', 'exists:gudangs,id'],
            'rows' => ['required', 'array', 'min:1', 'max:100'],
            'rows.*' => ['required', 'array'],
            'rows.*.line' => ['required', 'integer', 'min:1'],
            'rows.*.kode_barang' => ['nullable', 'string', 'max:100'],
            'rows.*.nama_barang' => ['nullable', 'string', 'max:500'],
            'rows.*.kategori' => ['nullable', 'string', 'max:200'],
            'rows.*.satuan' => ['nullable', 'string', 'max:200'],
            'rows.*.deskripsi' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
