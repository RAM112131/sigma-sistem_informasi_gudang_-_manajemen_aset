<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class BuatPemindahanLangsungRequest extends FormRequest
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
            'gudang_tujuan_id' => [
                'nullable',
                'integer',
                Rule::exists('gudangs', 'id')->where(fn ($query) => $query
                    ->where('is_aktif', true)
                    ->whereIn('jenis', ['unit_bisnis', 'gudang_umum', 'gudang_sukahati'])),
            ],
            'unit_peminta_teks' => ['nullable', 'string', 'max:255'],
            'pemohon' => ['nullable', 'string', 'max:255'],
            'petugas' => ['nullable', 'string', 'max:255'],
            'items' => ['nullable', 'array', 'min:1', 'max:100'],
            'items.*.barang_id' => ['required_with:items', 'integer'],
            'items.*.qty' => ['required_with:items', 'integer', 'min:1', 'max:2147483647'],
            'barang_id' => ['required_without:items', 'nullable', 'integer'],
            'qty_diminta' => ['required_without:items', 'nullable', 'integer', 'min:1', 'max:2147483647'],
        ];
    }
}
