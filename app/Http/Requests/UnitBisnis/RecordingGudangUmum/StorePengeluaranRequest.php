<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\RecordingGudangUmum;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePengeluaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && $user->hasAnyRole([
                'Recording Gudang Umum', 'recording_gudang_umum',
                'Admin Gudang Umum', 'admin_gudang_umum',
                'Super Admin', 'super_admin',
            ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'waktu_keluar' => ['required', 'date_format:H:i'],
            'entry_mode' => ['required', Rule::in(['live', 'late-paper'])],
            'bagian_peminta' => ['required', 'string', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.barang_id' => ['required', 'integer', Rule::exists('barangs', 'id')],
            'items.*.qty' => ['required', 'numeric', 'gt:0', 'max:999999999.999'],
            'items.*.catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
