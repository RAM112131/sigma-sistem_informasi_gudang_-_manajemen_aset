<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\AdminGudangUmum;

use Illuminate\Foundation\Http\FormRequest;

final class IndexRekonsiliasiPenerimaanRequest extends FormRequest
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
            'penerimaan_id' => ['nullable', 'integer'],
            'sumber_penerimaan' => ['nullable', 'in:supplier,permintaan'],
            'status' => ['nullable', 'in:menunggu_verifikasi,sudah_diverifikasi,tindak_lanjut,semua'],
            'q' => ['nullable', 'string', 'max:100'],
            'diterima_oleh' => ['nullable', 'integer', 'exists:users,id'],
            'tanggal' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
