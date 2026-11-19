<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\AdminGudangUmum;

use Illuminate\Foundation\Http\FormRequest;

final class KonfirmasiPemindahanRequest extends FormRequest
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
            'qty_diterima' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'qty_rusak' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'catatan_discrepancy' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
