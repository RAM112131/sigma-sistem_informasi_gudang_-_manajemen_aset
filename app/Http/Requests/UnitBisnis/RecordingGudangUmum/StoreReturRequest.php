<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\RecordingGudangUmum;

use Illuminate\Foundation\Http\FormRequest;

final class StoreReturRequest extends FormRequest
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
            'barang_id' => ['nullable', 'exists:barangs,id'],
            'barang_nama' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'integer', 'min:1'],
            'satuan' => ['required', 'string', 'max:50'],
            'kondisi' => ['required', 'string', 'in:baik,rusak'],
            'alasan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
