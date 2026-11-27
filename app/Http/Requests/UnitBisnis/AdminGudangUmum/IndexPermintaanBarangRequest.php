<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\AdminGudangUmum;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexPermintaanBarangRequest extends FormRequest
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
            'status' => ['nullable', Rule::in(['baru', 'diproses', 'selesai', 'ditolak'])],
            'search' => ['nullable', 'string', 'max:100'],
            'gudang_id' => ['nullable', 'integer'],
        ];
    }
}
