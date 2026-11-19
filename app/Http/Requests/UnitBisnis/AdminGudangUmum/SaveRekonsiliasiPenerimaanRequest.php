<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\AdminGudangUmum;

use Illuminate\Foundation\Http\FormRequest;

final class SaveRekonsiliasiPenerimaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole([
            'Admin Gudang Umum',
            'admin_gudang_umum',
            'Super Admin',
            'super_admin',
        ]) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'gudang_id' => ['nullable', 'integer'],
            'sumber_penerimaan' => ['required', 'in:supplier,permintaan'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.detail_id' => ['required', 'integer', 'distinct'],
            'items.*.qty_diperiksa' => ['required', 'numeric', 'min:0', 'decimal:0,3'],
            'items.*.qty_rusak_diperiksa' => ['required', 'numeric', 'min:0', 'decimal:0,3'],
            'items.*.harga_beli' => ['nullable', 'required_if:sumber_penerimaan,supplier', 'numeric', 'min:0', 'decimal:0,2'],
            'catatan_verifikasi' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Rincian barang wajib dikirim.',
            'items.*.detail_id.distinct' => 'Rincian barang tidak boleh dikirim lebih dari sekali.',
            'items.*.qty_diperiksa.decimal' => 'Jumlah fisik maksimal menggunakan tiga angka desimal.',
            'items.*.qty_rusak_diperiksa.decimal' => 'Jumlah rusak maksimal menggunakan tiga angka desimal.',
            'items.*.harga_beli.decimal' => 'Harga beli maksimal menggunakan dua angka desimal.',
        ];
    }
}
