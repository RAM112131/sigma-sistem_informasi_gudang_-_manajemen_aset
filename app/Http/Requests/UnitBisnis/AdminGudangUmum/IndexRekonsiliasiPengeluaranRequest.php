<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\AdminGudangUmum;

use Illuminate\Foundation\Http\FormRequest;

final class IndexRekonsiliasiPengeluaranRequest extends FormRequest
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
            'pengeluaran_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:menunggu_verifikasi,terverifikasi,ditolak,semua'],
            'q' => ['nullable', 'string', 'max:100'],
            'bagian_peminta' => ['nullable', 'string', 'max:255'],
            'tanggal' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
