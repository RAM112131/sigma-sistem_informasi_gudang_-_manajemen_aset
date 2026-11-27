<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

final class KonfirmasiKirimStoreRequest extends FormRequest
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
            'qty_dikirim' => ['required', 'integer', 'min:1'],
            'supir_pengantar' => ['nullable', 'string', 'max:255'],
            'foto_bukti' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
