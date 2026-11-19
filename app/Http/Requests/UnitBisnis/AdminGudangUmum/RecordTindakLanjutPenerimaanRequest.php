<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\AdminGudangUmum;

use Illuminate\Foundation\Http\FormRequest;

final class RecordTindakLanjutPenerimaanRequest extends FormRequest
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
            'catatan_tindak_lanjut' => ['required', 'string', 'max:1000'],
        ];
    }
}
