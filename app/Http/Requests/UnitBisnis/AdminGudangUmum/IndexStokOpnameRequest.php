<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\AdminGudangUmum;

use App\Models\User;
use App\Services\UnitBisnis\StokOpnameService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexStokOpnameRequest extends FormRequest
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
            'periode' => ['nullable', 'date_format:Y-m', Rule::in($service->availablePeriods())],
            'status' => ['nullable', Rule::in(['semua', 'kurang', 'sesuai', 'lebih', 'belum'])],
            'q' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', Rule::in(StokOpnameService::PAGE_SIZES)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
