<?php

declare(strict_types=1);

namespace App\Http\Requests\AdminGu;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReportFilterRequest extends FormRequest
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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'period' => $this->input('period', now()->format('Y-m')),
            'tab' => $this->input('tab', 'mutasi'),
            'matrix_mode' => $this->input('matrix_mode', 'qty'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period' => ['required', 'date_format:Y-m'],
            'tab' => ['required', Rule::in(['mutasi', 'matriks', 'pemasukan', 'selisih'])],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:255'],
            'source_type' => ['nullable', Rule::in(['store_transfer', 'direct_purchase'])],
            'matrix_mode' => ['required', Rule::in(['qty', 'rupiah'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
