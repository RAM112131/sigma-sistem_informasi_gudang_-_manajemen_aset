<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\AdminGudangUmum;

use Illuminate\Foundation\Http\FormRequest;

final class SaveRekonsiliasiPengeluaranRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.detail_id' => ['required', 'integer', 'distinct'],
            'items.*.qty_keluar' => ['required', 'numeric', 'min:0', 'decimal:0,3'],
            'items.*.qty_kembali' => ['required', 'numeric', 'min:0', 'decimal:0,3'],
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
            'items.*.qty_keluar.decimal' => 'Jumlah keluar maksimal menggunakan tiga angka desimal.',
            'items.*.qty_kembali.decimal' => 'Jumlah pengembalian maksimal menggunakan tiga angka desimal.',
        ];
    }
}
