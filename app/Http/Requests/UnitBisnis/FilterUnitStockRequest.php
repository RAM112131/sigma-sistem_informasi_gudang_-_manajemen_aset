<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis;

use Illuminate\Foundation\Http\FormRequest;

final class FilterUnitStockRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'kategori_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:aman,warning,kritis,critical'],
        ];
    }
}
