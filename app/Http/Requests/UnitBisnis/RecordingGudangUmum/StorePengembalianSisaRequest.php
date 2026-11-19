<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\RecordingGudangUmum;

use Illuminate\Foundation\Http\FormRequest;

final class StorePengembalianSisaRequest extends FormRequest
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
            'bagian_peminta' => ['required', 'string', 'max:255'],
            'tanggal_kembali' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.qty_kembali' => ['required', 'numeric', 'gt:0'],
            'items.*.catatan' => ['nullable', 'string', 'max:500'],
        ];
    }
}
