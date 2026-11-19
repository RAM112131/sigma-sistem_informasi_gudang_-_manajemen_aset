<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Store\PenerimaanStoreService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class ReceiptBooleanNormalizationTest extends TestCase
{
    public function test_receipt_number_date_prefix_uses_store_date(): void
    {
        $service = app(PenerimaanStoreService::class);
        $makePrefix = new ReflectionMethod($service, 'receiptNumberDatePrefix');

        $prefix = $makePrefix->invoke($service, Carbon::create(2026, 9, 30));

        $this->assertSame('RCV-STR-30092026', $prefix);
    }

    public function test_daily_sequence_continues_across_different_suppliers(): void
    {
        $service = app(PenerimaanStoreService::class);
        $getOptions = new ReflectionMethod($service, 'receiptNumberOptions');

        $options = $getOptions->invoke($service, [
            (object) ['nomor_penerimaan' => 'RCV-STR-30092026123-0001', 'sumber_supplier' => 'Supplier A'],
            (object) ['nomor_penerimaan' => 'RCV-STR-30092026123-0002', 'sumber_supplier' => 'Supplier A'],
            (object) ['nomor_penerimaan' => 'RCV-STR-30092026456-0003', 'sumber_supplier' => 'Supplier B'],
        ], Carbon::create(2026, 9, 30));

        $this->assertSame('RCV-STR-30092026123-', $options['supplierPrefixes']['Supplier A']);
        $this->assertSame('RCV-STR-30092026456-', $options['supplierPrefixes']['Supplier B']);
        $this->assertSame(4, $options['nextSequence']);
        $this->assertSame(['123', '456'], $options['usedTokens']);
    }

    public function test_receipt_prefix_validation_rejects_a_token_used_by_another_supplier(): void
    {
        $service = app(PenerimaanStoreService::class);
        $validatePrefixes = new ReflectionMethod($service, 'validateReceiptPrefixesForGroups');

        $this->expectException(ValidationException::class);

        $validatePrefixes->invoke($service, [
            [
                'sumber_supplier' => 'Supplier B',
                'nomor_rcv_prefix' => 'RCV-STR-30092026123-',
            ],
        ], [
            (object) ['nomor_penerimaan' => 'RCV-STR-30092026123-0001', 'sumber_supplier' => 'Supplier A'],
        ], Carbon::create(2026, 9, 30));
    }

    public function test_receipt_prefix_validation_accepts_distinct_tokens_for_distinct_suppliers(): void
    {
        $service = app(PenerimaanStoreService::class);
        $validatePrefixes = new ReflectionMethod($service, 'validateReceiptPrefixesForGroups');

        $prefixes = $validatePrefixes->invoke($service, [
            [
                'sumber_supplier' => 'Supplier A',
                'nomor_rcv_prefix' => 'RCV-STR-30092026123-',
            ],
            [
                'sumber_supplier' => 'Supplier B',
                'nomor_rcv_prefix' => 'RCV-STR-30092026456-',
            ],
        ], [
            (object) ['nomor_penerimaan' => 'RCV-STR-30092026123-0001', 'sumber_supplier' => 'Supplier A'],
        ], Carbon::create(2026, 9, 30));

        $this->assertSame('RCV-STR-30092026123-', $prefixes['Supplier A']);
        $this->assertSame('RCV-STR-30092026456-', $prefixes['Supplier B']);
    }

    public function test_surat_jalan_enum_values_are_validated_without_boolean_conversion(): void
    {
        foreach (['ada', 'belum_ada'] as $state) {
            $request = Request::create('/store/penerimaan', 'POST', [
                'source_groups' => [
                    ['tanpa_surat_jalan' => $state, 'items' => []],
                ],
            ]);

            $service = app(PenerimaanStoreService::class);
            $normalize = new ReflectionMethod($service, 'normalizeSourceGroups');
            $sourceGroups = $normalize->invoke($service, $request);
            $request->merge(['source_groups' => $sourceGroups]);

            $validator = Validator::make($request->all(), [
                'source_groups.0.tanpa_surat_jalan' => ['required', 'in:ada,belum_ada'],
            ]);

            $this->assertTrue($validator->passes());
            $this->assertSame($state, $request->input('source_groups.0.tanpa_surat_jalan'));
        }
    }

    public function test_legacy_boolean_values_are_normalized_to_surat_jalan_enum(): void
    {
        foreach ([[false, 'ada'], [true, 'belum_ada']] as [$boolean, $expected]) {
            $request = Request::create('/store/penerimaan', 'POST', [
                'source_groups' => [
                    ['tanpa_surat_jalan' => $boolean, 'items' => []],
                ],
            ]);

            $service = app(PenerimaanStoreService::class);
            $normalize = new ReflectionMethod($service, 'normalizeSourceGroups');
            $sourceGroups = $normalize->invoke($service, $request);

            $this->assertSame($expected, $sourceGroups[0]['tanpa_surat_jalan']);
        }
    }

    public function test_invalid_surat_jalan_enum_is_not_silently_converted(): void
    {
        $request = Request::create('/store/penerimaan', 'POST', [
            'source_groups' => [
                ['tanpa_surat_jalan' => 'unknown', 'items' => []],
            ],
        ]);

        $service = app(PenerimaanStoreService::class);
        $normalize = new ReflectionMethod($service, 'normalizeSourceGroups');
        $sourceGroups = $normalize->invoke($service, $request);
        $request->merge(['source_groups' => $sourceGroups]);

        $validator = Validator::make($request->all(), [
            'source_groups.0.tanpa_surat_jalan' => ['required', 'in:ada,belum_ada'],
        ]);

        $this->assertFalse($validator->passes());
    }

    public function test_duplicate_barang_ids_are_detected_within_a_supplier_group(): void
    {
        $service = app(PenerimaanStoreService::class);
        $findDuplicates = new ReflectionMethod($service, 'duplicateBarangIds');

        $duplicates = $findDuplicates->invoke($service, [
            ['barang_id' => '12'],
            ['barang_id' => 12],
            ['barang_id' => '34'],
            ['barang_id' => ''],
        ]);

        $this->assertSame(['12'], $duplicates);
    }

    public function test_same_barang_is_allowed_in_separate_supplier_groups(): void
    {
        $service = app(PenerimaanStoreService::class);
        $findDuplicates = new ReflectionMethod($service, 'duplicateBarangIds');
        $groupA = $findDuplicates->invoke($service, [['barang_id' => '12']]);
        $groupB = $findDuplicates->invoke($service, [['barang_id' => '12']]);

        $this->assertSame([], $groupA);
        $this->assertSame([], $groupB);
    }
}
