<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class FilterPengeluaranStoreRequest extends FormRequest
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
            'tab' => ['nullable', 'in:pemindahan,permintaan,langsung,histori'],
            'permintaan_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['diminta', 'dikirim', 'diterima', 'baru', 'diproses', 'selesai', 'ditolak'])],
            'search' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'tgl_awal' => ['nullable', 'date'],
            'tgl_akhir' => ['nullable', 'date'],
        ];
    }
}
