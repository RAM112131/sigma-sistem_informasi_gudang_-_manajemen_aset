<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\AdminGudangUmum;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Note: This request is NOT injected into the controller directly because
 * DashboardController needs soft-fail validation (fall back to defaults on
 * invalid input rather than redirect with errors). The rules are referenced
 * here for documentation and can be used via manual Validator::make().
 */
final class FilterAdminDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'period' => ['nullable', 'in:week,custom'],
            'start_date' => ['exclude_unless:period,custom', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'end_date' => ['exclude_unless:period,custom', 'required', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'period.in' => 'Pilihan periode grafik tidak valid.',
            'start_date.required' => 'Tanggal awal wajib dipilih untuk rentang kustom.',
            'start_date.date_format' => 'Format tanggal awal tidak valid.',
            'start_date.before_or_equal' => 'Tanggal awal tidak boleh melewati hari ini.',
            'end_date.required' => 'Tanggal akhir wajib dipilih untuk rentang kustom.',
            'end_date.date_format' => 'Format tanggal akhir tidak valid.',
            'end_date.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
            'end_date.before_or_equal' => 'Tanggal akhir tidak boleh melewati hari ini.',
        ];
    }
}
