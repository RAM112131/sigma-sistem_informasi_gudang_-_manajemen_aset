<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

final class LaporanExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('store_laporan.export') || $this->user()?->can('laporan.export') ?? false;
    }

    public function rules(): array
    {
        return [
            'periode' => [
                'required',
                'date_format:Y-m',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (
                        is_string($value)
                        && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $value) === 1
                        && $value > now()->format('Y-m')
                    ) {
                        $fail('Periode laporan tidak boleh melewati bulan berjalan.');
                    }
                },
            ],
            'hanya_perlu_restock' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'periode.required' => 'Periode laporan wajib dipilih.',
            'periode.date_format' => 'Periode laporan harus berformat bulan dan tahun.',
            'hanya_perlu_restock.boolean' => 'Filter stok restock tidak valid.',
        ];
    }
}
