<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\RecordingGudangUmum;

use Illuminate\Foundation\Http\FormRequest;

final class ProcessUnitStockImportRequest extends FormRequest
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

    public function rules(): array
    {
        return [
            'tanggal_acuan' => ['required', 'date'],
            'catatan_umum' => ['nullable', 'string', 'max:500'],
            'rows' => ['required', 'array', 'min:1', 'max:50000'],
            'rows.*.row' => ['nullable', 'integer', 'min:1'],
            'rows.*.kode_barang' => ['required', 'string'],
            'rows.*.nama_barang' => ['required', 'string'],
            'rows.*.kategori' => ['required', 'string'],
            'rows.*.satuan' => ['required', 'string'],
            'rows.*.qty' => ['required', 'integer', 'min:0'],
            'rows.*.catatan' => ['nullable', 'string'],
        ];
    }
}
