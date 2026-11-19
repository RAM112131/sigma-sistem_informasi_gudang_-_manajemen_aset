<?php

declare(strict_types=1);

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RegisterMasterBarangGudangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('barang.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kode_lokal' => $this->filled('kode_lokal') ? strtoupper(trim((string) $this->input('kode_lokal'))) : null,
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'gudang_id' => ['required', 'integer', 'exists:gudangs,id'],
            'kode_lokal' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('barangs', 'kode')->where(
                    fn ($query) => $query->where('gudang_id', $this->integer('gudang_id')),
                )->withoutTrashed(),
            ],
            'minimum_stok' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
