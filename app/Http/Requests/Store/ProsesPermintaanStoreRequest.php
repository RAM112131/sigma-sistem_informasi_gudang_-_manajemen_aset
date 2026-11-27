<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

final class ProsesPermintaanStoreRequest extends FormRequest
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
            'nomor_keluar' => ['nullable', 'string', 'max:60'],
            'qty_dialokasikan' => ['required', 'array', 'min:1', 'max:100'],
            'qty_dialokasikan.*' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ];
    }
}
