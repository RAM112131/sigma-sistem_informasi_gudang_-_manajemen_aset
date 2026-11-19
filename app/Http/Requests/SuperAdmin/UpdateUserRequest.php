<?php

declare(strict_types=1);

namespace App\Http\Requests\SuperAdmin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UpdateUserRequest extends FormRequest
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
        /** @var User $targetUser */
        $targetUser = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($targetUser->id),
            ],
            'password' => ['nullable', 'string', Password::min(8)->letters()->numbers(), 'confirmed'],
            'is_aktif' => ['required', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [
                'required',
                'string',
                'distinct',
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
            ],
            'gudang_ids' => ['nullable', 'array'],
            'gudang_ids.*' => ['integer', 'distinct', Rule::exists('gudangs', 'id')],
        ];
    }
}
