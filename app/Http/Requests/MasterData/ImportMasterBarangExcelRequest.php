<?php

declare(strict_types=1);

namespace App\Http\Requests\MasterData;

final class ImportMasterBarangExcelRequest extends MasterBarangImportRequest
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
            'file' => ['required', 'file', 'mimes:xlsx', 'extensions:xlsx', 'max:51200'],
        ];
    }
}
