<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use App\Services\Laporan\LaporanQueryService;
use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class LaporanPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('store_laporan.export') || $this->user()?->can('laporan.export') ?? false;
    }

    public function rules(): array
    {
        return [
            'periode' => [
                'required',
                'date_format:Y-m',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (
                        is_string($value)
                        && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $value) === 1
                        && $value > now()->format('Y-m')
                    ) {
                        $fail('Periode laporan tidak boleh melewati bulan berjalan.');
                    }
                },
            ],
            'tanggal' => ['required', 'integer', 'min:1', 'max:31'],
            'hanya_perlu_restock' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['periode', 'tanggal'])) {
                    return;
                }

                $periode = $this->string('periode')->toString();
                $jenis = (string) $this->route('jenis');
                $jumlahHari = $jenis === 'stok'
                    ? app(LaporanQueryService::class)->jumlahHariTersediaDalamPeriode($periode)
                    : Carbon::parse($periode.'-01')->daysInMonth;

                if ($this->integer('tanggal') > $jumlahHari) {
                    $validator->errors()->add('tanggal', 'Tanggal preview tidak tersedia untuk periode tersebut.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'periode.required' => 'Periode laporan wajib dipilih.',
            'periode.date_format' => 'Periode laporan harus berformat bulan dan tahun.',
            'tanggal.required' => 'Tanggal preview wajib dipilih.',
            'tanggal.integer' => 'Tanggal preview tidak valid.',
            'tanggal.min' => 'Tanggal preview tidak valid.',
            'tanggal.max' => 'Tanggal preview tidak valid.',
            'hanya_perlu_restock.boolean' => 'Filter stok restock tidak valid.',
        ];
    }
}
