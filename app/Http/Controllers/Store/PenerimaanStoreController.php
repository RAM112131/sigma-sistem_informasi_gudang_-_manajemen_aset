<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use App\Models\User;
use App\Services\Store\GudangStoreService;
use App\Services\Store\PenerimaanStoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class PenerimaanStoreController extends Controller
{
    public function __construct(
        private readonly PenerimaanStoreService $penerimaanService,
        private readonly GudangStoreService $gudangStoreService,
    ) {}

    /**
     * Display listing & input form for Penerimaan Barang Supplier (Gudang Store).
     */
    public function index(Request $request): View
    {
        $activeTab = $request->query('tab');
        $activeTab = is_string($activeTab) ? $activeTab : 'penerimaan_baru';
        $supplier = $request->query('supplier');
        $supplier = is_string($supplier) ? trim($supplier) : '';

        $dateFilters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $search = trim($dateFilters['search'] ?? ($dateFilters['q'] ?? ''));
        $gudangStore = $this->resolveStoreGudang($request, [
            'store_penerimaan.read',
            'store_penerimaan.create',
            'store.view',
            'store.read',
            'store.penerimaan',
        ]);

        $listingData = $this->penerimaanService->getListingData(
            $gudangStore,
            [
                'search' => $search,
                'supplier' => $supplier,
                'date_from' => $dateFilters['date_from'] ?? null,
                'date_to' => $dateFilters['date_to'] ?? null,
            ]
        );

        return view('store.penerimaan.index', array_merge($listingData, [
            'gudangStore' => $gudangStore,
            'activeTab' => $activeTab,
            'search' => $search,
        ]));
    }

    /**
     * Store a daily goods receipt document that may contain multiple source groups.
     */
    public function store(Request $request): RedirectResponse
    {
        $gudangStore = $this->resolveStoreGudang($request, [
            'store_penerimaan.create',
            'store.penerimaan',
            'store.create',
        ]);
        $tanpaSj = $request->boolean('tanpa_surat_jalan');

        $sourceGroups = $this->penerimaanService->normalizeSourceGroups(
            $request->all(),
            $tanpaSj,
            $request->input('alasan_tanpa_sj'),
            $request->input('items', [])
        );
        $request->merge(['source_groups' => $sourceGroups]);

        $receiptDate = now();
        $today = $receiptDate->toDateString();

        $validationRules = [
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

        $validationRules = array_merge(
            $validationRules,
            $this->penerimaanService->conditionalValidationRules($sourceGroups),
        );

        $validated = $request->validate($validationRules, [
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
        ]);

        $sanitizedSourceGroups = $this->penerimaanService->sanitizeSourceGroups($tanpaSj, $validated);

        $fotoPath = null;
        if ($request->hasFile('foto_dokumen')) {
            $fotoPath = $request->file('foto_dokumen')->store('penerimaan_store', 'public');
        }

        $receiptResult = $this->penerimaanService->createReceipts(
            $validated,
            $sanitizedSourceGroups,
            $gudangStore,
            $fotoPath,
            (int) Auth::id()
        );

        $nomorPenerimaan = $receiptResult['numbers'];
        $itemCount = $receiptResult['item_count'];

        return redirect()->route('store.penerimaan.index', ['tab' => 'riwayat_penerimaan'])
            ->with('success', 'Penerimaan Barang berhasil disimpan dengan nomor RCV: '.implode(', ', $nomorPenerimaan)." ({$itemCount} baris barang); stok ter-update!");
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function resolveStoreGudang(Request $request, array $permissions): Gudang
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $gudang = $this->gudangStoreService->resolveAssignedWarehouse(
            $user,
            $request->query('gudang_id', session('active_gudang_id')),
            ['holding', 'global_master', 'gudang_store'],
            $permissions,
        );
        session(['active_gudang_id' => $gudang->id]);

        return $gudang;
    }
}
