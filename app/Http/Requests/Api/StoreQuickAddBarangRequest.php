<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class StoreQuickAddBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->can('barang.create');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['nullable', 'string', 'max:50'],
            'kategori_id' => ['required', 'integer'],
            'satuan_id' => ['required', 'integer'],
            'minimum_stok' => ['nullable', 'integer', 'min:0'],
            'deskripsi' => ['nullable', 'string'],
        ];
    }
}
