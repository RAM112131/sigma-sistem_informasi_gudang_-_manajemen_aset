<?php

declare(strict_types=1);

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMasterBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('barang.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kode' => strtoupper(trim((string) $this->input('kode'))),
            'nama' => trim((string) $this->input('nama')),
        ]);

        if ($this->has('has_gudang_sync')) {
            $this->merge([
                'gudang_ids' => $this->input('gudang_ids', []),
            ]);
        }
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'kode' => [
                'required',
                'string',
                'max:50',
                Rule::unique('master_barangs', 'kode')->ignore($this->route('masterBarang')),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'kategori_id' => ['required', Rule::exists('kategori_barangs', 'id')->where('is_aktif', true)],
            'satuan_id' => ['required', Rule::exists('satuans', 'id')->where('is_aktif', true)],
            'harga_satuan' => ['nullable', 'numeric', 'min:0'],
            'deskripsi' => ['nullable', 'string'],
            'is_aktif' => ['required', 'boolean'],
            'gudang_ids' => ['nullable', 'array'],
            'gudang_ids.*' => ['integer', 'distinct', 'exists:gudangs,id'],
            'has_gudang_sync' => ['nullable', 'string', 'in:1,0'],
        ];
    }
}
