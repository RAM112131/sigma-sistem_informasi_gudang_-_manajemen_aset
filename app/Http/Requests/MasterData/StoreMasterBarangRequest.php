<?php

declare(strict_types=1);

namespace App\Http\Requests\MasterData;

use App\Models\MasterBarang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreMasterBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('barang.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'kode' => strtoupper(trim((string) $this->input('kode'))),
            'nama' => trim((string) $this->input('nama')),
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:50', 'unique:master_barangs,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'kategori_id' => ['required', Rule::exists('kategori_barangs', 'id')->where('is_aktif', true)],
            'satuan_id' => ['required', Rule::exists('satuans', 'id')->where('is_aktif', true)],
            'harga_satuan' => ['nullable', 'numeric', 'min:0'],
            'deskripsi' => ['nullable', 'string'],
            'foto_barang' => ['nullable', 'image', 'max:5120'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('nama')) {
                    return;
                }

                $normalizedName = mb_strtolower(trim((string) $this->input('nama')));
                $sameNameExists = MasterBarang::withTrashed()
                    ->whereRaw('LOWER(TRIM(nama)) = ?', [$normalizedName])
                    ->exists();

                if ($sameNameExists) {
                    $validator->errors()->add(
                        'nama',
                        'Nama barang sudah digunakan oleh Master Barang. Periksa data barang sebelum menggunakan kode berbeda.',
                    );
                }
            },
        ];
    }
}
