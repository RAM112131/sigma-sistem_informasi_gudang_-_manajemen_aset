<?php

declare(strict_types=1);

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreGudangRequest extends FormRequest
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
            'kode' => ['required', 'string', 'max:50', Rule::unique('gudangs', 'kode')],
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'string', Rule::in([
                'holding',
                'global_master',
                'gudang_store',
                'unit_bisnis',
                'gudang_sukahati',
                'gudang_umum',
            ])],
            'is_aktif' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];
        $kode = $this->input('kode');

        if (is_string($kode)) {
            $data['kode'] = mb_strtoupper(trim($kode));
        }

        if ($this->has('is_aktif')) {
            $data['is_aktif'] = $this->boolean('is_aktif');
        }

        $this->merge($data);
    }
}
