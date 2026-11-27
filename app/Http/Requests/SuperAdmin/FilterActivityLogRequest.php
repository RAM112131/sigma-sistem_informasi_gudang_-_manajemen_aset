<?php

declare(strict_types=1);

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class FilterActivityLogRequest extends FormRequest
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
        $rules = [
            'search' => ['nullable', 'string', 'max:100'],
            'operator' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'action' => ['nullable', 'string', 'max:20'],
            'subject' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'direction' => ['nullable', Rule::in(['previous', 'next'])],
        ];

        if ($this->routeIs('admin.activity-log.export')) {
            $rules['format'] = ['required', Rule::in(['csv', 'json'])];
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizedFilters(): array
    {
        $filters = $this->validated();
        $filters['search'] = trim((string) ($filters['search'] ?? ''));
        $filters['subject'] = (string) ($filters['subject'] ?? '');

        return $filters;
    }
}
