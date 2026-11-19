<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\RecordingGudangUmum;

use Illuminate\Foundation\Http\FormRequest;

final class StorePenerimaanRequest extends FormRequest
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
            'jenis_masuk' => ['required', 'string'],
            'barang_nama' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'integer', 'min:1'],
            'satuan' => ['required', 'string', 'max:50'],
            'po_ref' => ['nullable', 'string', 'max:100'],
            'sumber_asal' => ['nullable', 'string', 'max:255'],
            'alasan' => ['nullable', 'string'],
        ];
    }
}
