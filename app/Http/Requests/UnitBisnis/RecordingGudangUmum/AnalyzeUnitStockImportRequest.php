<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\RecordingGudangUmum;

use Illuminate\Foundation\Http\FormRequest;

final class AnalyzeUnitStockImportRequest extends FormRequest
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
            'rows' => ['required', 'array', 'min:1', 'max:50000'],
            'rows.*' => ['required', 'array'],
            'rows.*.row' => ['nullable', 'integer', 'min:1'],
            'rows.*.kode_barang' => ['nullable', 'string', 'max:100'],
            'rows.*.nama_barang' => ['nullable', 'string', 'max:255'],
            'rows.*.kategori' => ['nullable', 'string', 'max:255'],
            'rows.*.satuan' => ['nullable', 'string', 'max:50'],
            'rows.*.qty' => ['nullable', 'string', 'max:50'],
            'rows.*.kondisi' => ['nullable', 'string', 'max:100'],
            'rows.*.catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
