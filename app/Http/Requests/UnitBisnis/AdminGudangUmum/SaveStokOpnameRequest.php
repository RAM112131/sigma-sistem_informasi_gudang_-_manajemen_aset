<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\AdminGudangUmum;

use App\Models\User;
use App\Services\UnitBisnis\StokOpnameService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveStokOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->hasAnyRole([
            'Admin Gudang Umum',
            'admin_gudang_umum',
            'Super Admin',
            'super_admin',
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(StokOpnameService $service): array
    {
        return [
            'gudang_id' => ['nullable', 'integer'],
            'periode' => ['required', 'date_format:Y-m', Rule::in($service->availablePeriods())],
            'snapshot_barang_id' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in(StokOpnameService::PAGE_SIZES)],
            'page' => ['nullable', 'integer', 'min:1'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.barang_id' => ['required', 'integer', 'distinct'],
            'items.*.qty_fisik' => ['nullable', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999.999'],
            'items.*.catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'periode.in' => 'Periode opname tidak tersedia.',
            'items.*.qty_fisik.decimal' => 'Stok fisik maksimal memiliki tiga angka desimal.',
        ];
    }
}
