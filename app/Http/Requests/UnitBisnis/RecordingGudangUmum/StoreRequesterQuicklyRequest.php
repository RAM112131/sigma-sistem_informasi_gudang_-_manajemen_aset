<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\RecordingGudangUmum;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class StoreRequesterQuicklyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && $user->hasAnyRole(['Recording Gudang Umum', 'recording_gudang_umum', 'Super Admin', 'super_admin']);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'bagian_peminta' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('bagian_peminta')) {
            $this->merge(['bagian_peminta' => trim((string) $this->input('bagian_peminta'))]);
        }
    }
}
