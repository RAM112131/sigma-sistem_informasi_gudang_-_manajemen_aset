<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\RecordingGudangUmum;

use Illuminate\Foundation\Http\FormRequest;

final class SearchUnitStockImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $u = $this->user();
        if (! $u) {
            return false;
        }

        return $u->hasAnyRole([
            'Super Admin',
            'super_admin',
            'Admin Gudang Umum',
            'admin_gudang_umum',
            'Recording Gudang Umum',
            'recording_gudang_umum',
        ])
            || $u->can('stokunitbisnis.create')
            || $u->can('stok_awal.import');
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:1', 'max:150'],
            'field' => ['nullable', 'string', 'in:kode_barang,nama_barang'],
            'gudang_id' => ['nullable', 'integer'],
        ];
    }
}

