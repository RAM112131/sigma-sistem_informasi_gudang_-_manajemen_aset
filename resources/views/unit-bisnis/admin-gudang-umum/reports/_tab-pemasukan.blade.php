<section class="space-y-4">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <p class="text-xs font-semibold text-slate-500">Transfer dari Gudang Store</p>
            <p class="mt-2 text-xl font-bold text-[#145C54] tabular-nums">
                {{ $formatMoney($report['kpi']['store_value']) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ number_format($report['kpi']['store_documents']) }} dokumen</p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <p class="text-xs font-semibold text-slate-500">Pembelian Umum / Supplier</p>
            <p class="mt-2 text-xl font-bold text-slate-800 tabular-nums">
                {{ $formatMoney($report['kpi']['purchase_value']) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ number_format($report['kpi']['purchase_documents']) }} dokumen</p>
        </article>
        <article class="rounded-2xl border border-[#145C54]/20 bg-[#145C54]/5 p-4 shadow-sm sm:p-5">
            <p class="text-xs font-semibold text-slate-600">Total Pemasukan Bulan Ini</p>
            <p class="mt-2 text-xl font-bold text-[#0F4740] tabular-nums">
                {{ $formatMoney($report['kpi']['total_value']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Nilai dokumen yang sesuai dengan filter SKU.</p>
        </article>
    </div>

    <form method="GET" action="{{ route('unit-bisnis.admin-gu.laporan.index') }}"
        class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-end sm:justify-between">
        @foreach (['period', 'tab', 'category_id', 'search', 'matrix_mode'] as $key)
            @if ($filters[$key] !== null && $filters[$key] !== '')
                <input type="hidden" name="{{ $key }}" value="{{ $filters[$key] }}">
            @endif
        @endforeach
        <label class="block w-full sm:max-w-xs">
            <span class="mb-1.5 block text-xs font-semibold text-slate-700">Sumber pemasukan</span>
            <select name="source_type" onchange="this.form.requestSubmit()"
                class="min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none focus:border-[#145C54] focus:ring-2 focus:ring-[#145C54]/15">
                <option value="">Semua sumber</option>
                <option value="store_transfer" @selected($filters['source_type'] === 'store_transfer')>Transfer Gudang Store</option>
                <option value="direct_purchase" @selected($filters['source_type'] === 'direct_purchase')>Pembelian Umum (Supplier)</option>
            </select>
        </label>
        <p class="text-xs text-slate-500">Kartu ringkasan menampilkan kedua sumber; pilihan ini menyaring daftar
            dokumen.</p>
    </form>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
            <h2 class="text-base font-bold text-slate-900">Dokumen Penerimaan Terverifikasi</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="report-table min-w-[1050px] w-full text-left text-xs">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-3 py-3">No. Dokumen</th>
                        <th class="px-3 py-3">Tanggal Verifikasi</th>
                        <th class="px-3 py-3">Sumber / Supplier</th>
                        <th class="px-3 py-3">No. Nota / Surat Jalan</th>
                        <th class="px-3 py-3 text-right">Jumlah SKU</th>
                        <th class="px-3 py-3">Kondisi Fisik</th>
                        <th class="px-3 py-3 text-right">Nilai Masuk (Rp)</th>
                        <th class="px-3 py-3">Catatan Tindak Lanjut / Klaim</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($report['documents'] as $document)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-3 py-3 font-semibold text-slate-800">{{ $document['document_number'] }}</td>
                            <td class="px-3 py-3 text-slate-600">
                                {{ \Illuminate\Support\Carbon::parse($document['verified_at'])->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-3 py-3">{{ $document['source_name'] }}<span
                                    class="mt-0.5 block text-[10px] text-slate-400">{{ $document['source_type'] === 'store_transfer' ? 'Transfer Store' : 'Pembelian Umum' }}</span>
                            </td>
                            <td class="px-3 py-3 text-slate-600">{{ $document['reference_number'] }}</td>
                            <td class="px-3 py-3 {{ $numberClass($document['sku_count']) }}">
                                {{ number_format($document['sku_count']) }}</td>
                            <td class="px-3 py-3">
                                <span
                                    class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $document['physical_condition'] === 'Sesuai' ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800' }}">{{ $document['physical_condition'] }}</span>
                            </td>
                            <td class="px-3 py-3 {{ $numberClass($document['total_value']) }}">
                                {{ $formatMoney($document['total_value']) }}</td>
                            <td class="max-w-72 whitespace-normal px-3 py-3 text-slate-600">
                                {{ $document['follow_up'] ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-500">Belum ada dokumen
                                terverifikasi untuk periode dan filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($report['documents'] instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">{{ $report['documents']->links() }}</div>
    @endif
</section>
