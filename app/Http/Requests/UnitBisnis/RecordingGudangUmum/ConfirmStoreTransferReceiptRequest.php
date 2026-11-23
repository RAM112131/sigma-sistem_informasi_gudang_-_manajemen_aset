<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\RecordingGudangUmum;

use Illuminate\Foundation\Http\FormRequest;

final class ConfirmStoreTransferReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'transfer_id' => ['nullable', 'integer'],
            'request_id' => ['nullable', 'string', 'max:255'],
            'has_surat_jalan' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.transfer_id' => ['required', 'integer'],
            'items.*.qty_diterima' => ['required', 'numeric', 'min:0'],
            'items.*.qty_rusak' => ['nullable', 'numeric', 'min:0'],
            'items.*.catatan_discrepancy' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
