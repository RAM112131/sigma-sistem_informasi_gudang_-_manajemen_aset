<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use App\Services\Shared\ActiveGudangResolver;
use App\Services\Store\PenerimaanStoreService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StorePenerimaanStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $penerimaanService = app(PenerimaanStoreService::class);
        $tanpaSj = $this->boolean('tanpa_surat_jalan');

        $sourceGroups = $penerimaanService->normalizeSourceGroups(
            $this->all(),
            $tanpaSj,
            $this->input('alasan_tanpa_sj'),
            $this->input('items', []),
        );

        $this->merge(['source_groups' => $sourceGroups]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(
        ActiveGudangResolver $gudangResolver,
        PenerimaanStoreService $penerimaanService,
    ): array {
        $gudangStore = $gudangResolver->resolveStore($this, [
            'store_penerimaan.create',
            'store.penerimaan',
            'store.create',
        ]);

        $receiptDate = now();
        $today = $receiptDate->toDateString();
        $sourceGroups = is_array($this->input('source_groups')) ? $this->input('source_groups') : [];

        $rules = [
            'tanggal_diterima' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($today): void {
                    if ($value !== $today) {
                        $fail('Tanggal penerimaan harus sama dengan tanggal hari ini.');
                    }
                },
            ],
            'source_groups' => ['required', 'array', 'min:1'],
            'source_groups.*.items' => ['required', 'array', 'min:1'],
            'source_groups.*.sumber_supplier' => ['required', 'string', 'max:255'],
            'source_groups.*.nomor_rcv_prefix' => [
                'required',
                'string',
                'regex:/^RCV-STR-'.$receiptDate->format('dmY').'\d{3}-$/',
            ],
            'source_groups.*.nomor_penerimaan_preview' => [
                'required',
                'string',
                'regex:/^RCV-STR-'.$receiptDate->format('dmY').'\d{3}-\d{4}$/',
            ],
            'source_groups.*.pemesan' => ['required', 'string', 'max:255'],
            'source_groups.*.pengambil_barang' => ['required', 'string', 'max:255'],
            'source_groups.*.metode_pembayaran' => ['required', 'string', 'in:Tunai,Transfer,Kredit,Debit'],
            'source_groups.*.catatan_rekap' => ['nullable', 'string'],
            'source_groups.*.tanpa_surat_jalan' => ['required', Rule::in(['ada', 'belum_ada'])],
            'source_groups.*.alasan_tanpa_sj' => ['nullable', 'string', 'max:1000'],
            'tanpa_surat_jalan' => ['nullable', 'boolean'],
            'alasan_tanpa_sj' => ['nullable', 'string', 'max:1000'],
            'foto_dokumen' => ['nullable', 'image', 'max:5120'],
            'catatan' => ['nullable', 'string'],

            'source_groups.*.items.*.barang_id' => [
                'required',
                Rule::exists('barangs', 'id')->where(fn ($query) => $query->where('gudang_id', $gudangStore->id)->where('is_aktif', true)),
            ],
            'source_groups.*.items.*.qty_diterima' => ['required', 'integer', 'min:1'],
            'source_groups.*.items.*.qty_rusak' => ['nullable', 'integer', 'min:0'],
            'source_groups.*.items.*.qty_kurang' => ['nullable', 'integer', 'min:0'],
            'source_groups.*.items.*.qty_lebih' => ['nullable', 'integer', 'min:0'],
            'source_groups.*.items.*.satuan_id' => ['required', 'exists:satuans,id'],
            'source_groups.*.items.*.harga_satuan' => ['nullable', 'numeric', 'min:0'],
            'source_groups.*.items.*.total_harga' => ['nullable', 'numeric', 'min:0'],
            'source_groups.*.items.*.kondisi' => ['nullable', 'string', 'in:Baik,Rusak,Kurang,Rusak dan Kurang,Lebih,Rusak dan Lebih'],
            'source_groups.*.items.*.pemakai' => ['nullable', 'string', 'max:255'],
            'source_groups.*.items.*.catatan' => ['nullable', 'string'],
        ];

        return array_merge($rules, $penerimaanService->conditionalValidationRules($sourceGroups));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'source_groups.required' => 'Minimal harus ada 1 kelompok sumber penerimaan.',
            'source_groups.min' => 'Minimal harus ada 1 kelompok sumber penerimaan.',
            'source_groups.*.pemesan.required' => 'Nama pemesan / order wajib diisi pada setiap kelompok sumber.',
            'source_groups.*.pengambil_barang.required' => 'Nama pengambil barang / ekspedisi wajib diisi pada setiap kelompok sumber.',
            'source_groups.*.sumber_supplier.required' => 'Nama supplier wajib diisi pada setiap kelompok sumber.',
            'source_groups.*.metode_pembayaran.required' => 'Metode pembayaran wajib dipilih pada setiap kelompok sumber.',
            'source_groups.*.alasan_tanpa_sj.required' => 'Catatan tindak lanjut wajib diisi jika surat jalan belum ada.',
            'tanggal_diterima.date' => 'Format tanggal penerimaan tidak valid.',
            'tanggal_diterima.required' => 'Tanggal penerimaan wajib diisi.',
            'source_groups.*.items.required' => 'Minimal satu barang harus diisi untuk setiap supplier.',
            'source_groups.*.items.min' => 'Minimal satu barang harus diisi untuk setiap supplier.',
            'source_groups.*.items.*.barang_id.required' => 'Pilih barang yang valid untuk setiap baris penerimaan.',
            'source_groups.*.items.*.qty_diterima.required' => 'Qty penerimaan wajib diisi minimal 1.',
            'source_groups.*.items.*.qty_diterima.min' => 'Qty penerimaan wajib diisi minimal 1.',
            'source_groups.*.items.*.satuan_id.required' => 'Satuan barang wajib dipilih.',
            'source_groups.*.items.*.qty_rusak.required' => 'Qty rusak wajib diisi jika kondisi barang Rusak.',
            'source_groups.*.items.*.qty_rusak.min' => 'Qty rusak minimal 1.',
            'source_groups.*.items.*.qty_kurang.required' => 'Qty kurang wajib diisi jika kondisi barang Kurang.',
            'source_groups.*.items.*.qty_kurang.min' => 'Qty kurang minimal 1.',
            'source_groups.*.items.*.qty_lebih.required' => 'Qty lebih wajib diisi jika kondisi barang Lebih.',
            'source_groups.*.items.*.qty_lebih.min' => 'Qty lebih minimal 1.',
            'source_groups.*.items.*.qty_lebih.max' => 'Qty lebih tidak boleh melebihi qty yang diterima.',
            'source_groups.*.items.*.qty_rusak.max' => 'Qty rusak tidak boleh melebihi qty fisik yang diterima.',
        ];
    }
}
