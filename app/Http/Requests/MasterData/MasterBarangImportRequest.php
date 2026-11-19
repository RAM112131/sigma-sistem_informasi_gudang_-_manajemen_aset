<?php

declare(strict_types=1);

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;

abstract class MasterBarangImportRequest extends FormRequest
{
    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'warehouse_ids.required' => 'Pilih setidaknya satu gudang tujuan.',
            'warehouse_ids.array' => 'Pilihan gudang tujuan tidak terbaca. Silakan pilih ulang gudangnya.',
            'warehouse_ids.min' => 'Pilih setidaknya satu gudang tujuan.',
            'warehouse_ids.max' => 'Maksimal 20 gudang dapat dipilih sekaligus.',
            'warehouse_ids.*.required' => 'Salah satu pilihan gudang tidak terbaca. Silakan pilih ulang gudangnya.',
            'warehouse_ids.*.integer' => 'Pilihan gudang tidak valid. Silakan pilih ulang gudangnya.',
            'warehouse_ids.*.distinct' => 'Gudang tujuan yang sama tidak boleh dipilih lebih dari sekali.',
            'warehouse_ids.*.exists' => 'Salah satu gudang tujuan sudah tidak tersedia. Muat ulang halaman lalu pilih kembali.',
            'file.required' => 'Pilih file Excel yang ingin diperiksa.',
            'file.file' => 'File tidak dapat dibaca. Pilih kembali file Excel dari perangkat Anda.',
            'file.mimes' => 'File harus berformat Excel .xlsx.',
            'file.extensions' => 'Nama file harus menggunakan ekstensi .xlsx.',
            'file.max' => 'Ukuran file maksimal 50 MB.',
            'rows.required' => 'Belum ada data barang yang dapat diperiksa.',
            'rows.array' => 'Data barang tidak terbaca. Silakan tinjau ulang data Anda.',
            'rows.min' => 'Tambahkan setidaknya satu baris barang untuk diperiksa.',
            'rows.*.required' => 'Salah satu baris barang tidak terbaca. Silakan tinjau ulang data Anda.',
            'rows.*.array' => 'Format salah satu baris barang tidak sesuai. Silakan tinjau ulang data Anda.',
            'rows.*.line.required' => 'Nomor baris sumber tidak terbaca. Silakan tinjau ulang data.',
            'rows.*.line.integer' => 'Nomor baris sumber tidak valid. Silakan mulai ulang pemeriksaan data.',
            'rows.*.line.min' => 'Nomor baris sumber tidak valid. Silakan mulai ulang pemeriksaan data.',
            'rows.*.kode_barang.string' => 'Kode barang harus berupa teks.',
            'rows.*.kode_barang.max' => 'Kode barang pada salah satu baris melebihi batas 100 karakter.',
            'rows.*.nama_barang.string' => 'Nama barang harus berupa teks.',
            'rows.*.nama_barang.max' => 'Nama barang pada salah satu baris melebihi batas 500 karakter.',
            'rows.*.kategori.string' => 'Nama kategori harus berupa teks.',
            'rows.*.kategori.max' => 'Kategori pada salah satu baris melebihi batas 200 karakter.',
            'rows.*.satuan.string' => 'Nama satuan harus berupa teks.',
            'rows.*.satuan.max' => 'Satuan pada salah satu baris melebihi batas 200 karakter.',
            'rows.*.deskripsi.string' => 'Deskripsi barang harus berupa teks.',
            'rows.*.deskripsi.max' => 'Deskripsi pada salah satu baris melebihi batas 10.000 karakter.',
        ];
    }
}
