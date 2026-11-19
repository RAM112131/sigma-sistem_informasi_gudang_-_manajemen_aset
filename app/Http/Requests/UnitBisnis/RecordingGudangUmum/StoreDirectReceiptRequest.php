<?php

declare(strict_types=1);

namespace App\Http\Requests\UnitBisnis\RecordingGudangUmum;

use Illuminate\Foundation\Http\FormRequest;

final class StoreDirectReceiptRequest extends FormRequest
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
            'nama_supplier' => ['required', 'string', 'max:255'],
            'has_surat_jalan' => ['required', 'boolean'],
            'alasan_tanpa_sj' => ['nullable', 'string', 'max:1000', 'required_if:has_surat_jalan,false,0'],
            'po_referensi' => ['nullable', 'string', 'max:100'],
            'nomor_surat_jalan' => ['nullable', 'string', 'max:100'],
            'tanggal_terima' => ['nullable', 'date'],
            'waktu_terima' => ['nullable', 'string'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.barang_id' => ['required', 'integer'],
            'items.*.qty_diterima' => ['required', 'numeric', 'min:0.001'],
            'items.*.satuan' => ['nullable', 'string', 'max:50'],
            'items.*.kondisi' => ['required', 'string', 'in:Baik,Rusak,Kurang,Rusak dan Kurang,Lebih,Rusak dan Lebih'],
            'items.*.qty_rusak' => ['nullable', 'numeric', 'min:0'],
            'items.*.qty_kurang' => ['nullable', 'numeric', 'min:0'],
            'items.*.qty_lebih' => ['nullable', 'numeric', 'min:0'],
            'items.*.catatan' => ['nullable', 'string', 'max:500'],
        ];
    }
}
