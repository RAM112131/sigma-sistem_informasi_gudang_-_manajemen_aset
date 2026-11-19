<?php

declare(strict_types=1);

namespace App\Http\Requests\MasterData;

use App\Models\Satuan;
use App\Services\MasterData\SatuanService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSatuanRequest extends FormRequest
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
        /** @var Satuan|null $satuan */
        $satuan = $this->route('satuan');
        $codeRules = ['required', 'string', 'max:20'];

        if ($satuan?->masterBarangs()->exists()) {
            $codeRules[] = Rule::in([$satuan->kode, strtolower($satuan->kode)]);
        } else {
            $uniqueCodeRule = Rule::unique('satuans', 'kode');

            if ($satuan !== null) {
                $uniqueCodeRule->ignore($satuan->id);
            }

            $codeRules[] = $uniqueCodeRule;
        }

        return [
            'kode' => $codeRules,
            'nama' => ['required', 'string', 'max:100'],
            'satuan_input' => ['nullable', 'string', 'max:100'],
            'is_aktif' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode.unique' => 'Satuan sudah terdaftar.',
            'kode.in' => 'Satuan tidak dapat diubah karena sudah dipakai barang.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $enteredUnit = $this->input('satuan_input', $this->input('kode'));
        if (! is_string($enteredUnit)) {
            return;
        }

        $normalized = SatuanService::normalizeInput($enteredUnit);
        $satuan = $this->route('satuan');
        $isNewSatuan = ! ($satuan instanceof Satuan);
        $existingCode = $satuan instanceof Satuan ? strtolower($satuan->kode) : null;
        $unitChanged = $existingCode !== null && strtolower(trim($enteredUnit)) !== $existingCode;
        $enteredName = $this->input('nama');

        $this->merge([
            'kode' => $isNewSatuan || $unitChanged
                ? $normalized['kode']
                : strtolower(trim($enteredUnit)),
            'nama' => ($isNewSatuan || $unitChanged) && SatuanService::isKnownAlias($enteredUnit)
                ? $normalized['nama']
                : (is_string($enteredName) && trim($enteredName) !== ''
                    ? trim($enteredName)
                    : $normalized['nama']),
        ]);
    }
}
