<section class="report-matrix-section space-y-4" x-data="{ mode: @js($filters['matrix_mode']) }">
    <div
        class="report-surface report-matrix-heading flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <div>
            <h2 class="text-base font-bold text-slate-900">Matriks Pemakaian per Divisi</h2>
            <p class="mt-1 text-xs text-slate-500">Pemakaian bersih dihitung dari jumlah keluar setelah dikurangi barang
                yang kembali.</p>
        </div>
        <div class="report-matrix-mode inline-flex min-h-11 w-fit items-center gap-1 rounded-lg bg-slate-100 p-1"
            role="group" aria-label="Mode angka matriks">
            <button type="button" @click="mode = 'qty'" :aria-pressed="mode === 'qty'"
                class="min-h-9 rounded-md px-4 text-sm font-semibold transition"
                :class="mode === 'qty' ? 'bg-white text-[#145C54] shadow-sm' : 'text-slate-600 hover:text-slate-900'">Qty</button>
            <button type="button" @click="mode = 'rupiah'" :aria-pressed="mode === 'rupiah'"
                class="min-h-9 rounded-md px-4 text-sm font-semibold transition"
                :class="mode === 'rupiah' ? 'bg-white text-[#145C54] shadow-sm' : 'text-slate-600 hover:text-slate-900'">Rp</button>
        </div>
    </div>

    <div
        class="report-surface report-matrix-panel overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="report-matrix-scroll overflow-auto">
            <table class="report-table report-matrix-table min-w-max text-left text-xs">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="sticky left-0 z-20 min-w-64 border-r border-slate-200 bg-slate-50 px-3 py-3">Kode
                            &amp; Nama Barang</th>
                        <th class="px-3 py-3">Satuan</th>
                        @foreach ($report['divisions'] as $division)
                            <th class="min-w-28 px-3 py-3 text-right">{{ $division['nama'] }}</th>
                        @endforeach
                        <th
                            class="sticky right-32 z-20 min-w-28 border-l border-slate-200 bg-slate-50 px-3 py-3 text-right">
                            Total Pemakaian</th>
                        <th
                            class="sticky right-0 z-20 min-w-32 border-l border-slate-200 bg-slate-50 px-3 py-3 text-right">
                            Total Biaya (Rp)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($report['items'] as $item)
                        <tr class="hover:bg-slate-50/80">
                            <td class="sticky left-0 z-10 border-r border-slate-100 bg-white px-3 py-3">
                                <span class="font-semibold text-slate-800">{{ $item['kode'] }}</span>
                                <span class="ml-1 text-slate-600">{{ $item['nama'] }}</span>
                            </td>
                            <td class="px-3 py-3 text-slate-500">{{ $item['satuan'] }}</td>
                            @foreach ($report['divisions'] as $division)
                                @php($cell = $item['divisions'][$division['id']])
                                <td class="px-3 py-3 {{ $numberClass($cell['qty']) }}">
                                    <span x-show="mode === 'qty'">{{ $formatQty($cell['qty']) }}</span>
                                    <span x-show="mode === 'rupiah'" x-cloak>{{ $formatMoney($cell['value']) }}</span>
                                </td>
                            @endforeach
                            <td class="sticky right-32 z-10 bg-white px-3 py-3 {{ $numberClass($item['total_qty']) }}">
                                <span x-show="mode === 'qty'">{{ $formatQty($item['total_qty']) }}</span>
                                <span x-show="mode === 'rupiah'" x-cloak>{{ $formatMoney($item['total_value']) }}</span>
                            </td>
                            <td
                                class="sticky right-0 z-10 border-l border-slate-100 bg-white px-3 py-3 {{ $numberClass($item['total_value']) }}">
                                {{ $formatMoney($item['total_value']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($report['divisions']) + 4 }}"
                                class="report-matrix-empty px-4 py-12 text-center text-sm text-slate-500">Belum ada SKU
                                aktif atau pemakaian untuk filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if (count($report['items']) > 0)
                    <tfoot class="border-t-2 border-slate-200 bg-slate-50 font-bold text-slate-800">
                        <tr>
                            <td class="sticky left-0 z-20 border-r border-slate-200 bg-slate-50 px-3 py-3">GRAND TOTAL
                            </td>
                            <td></td>
                            @foreach ($report['divisions'] as $division)
                                @php($columnTotal = $report['totals']['divisions'][$division['id']])
                                <td class="px-3 py-3 tabular-nums text-right">
                                    <span x-show="mode === 'qty'">{{ $formatQty($columnTotal['qty']) }}</span>
                                    <span x-show="mode === 'rupiah'"
                                        x-cloak>{{ $formatMoney($columnTotal['value']) }}</span>
                                </td>
                            @endforeach
                            <td class="sticky right-32 z-20 bg-slate-50 px-3 py-3 tabular-nums text-right">
                                <span x-show="mode === 'qty'">{{ $formatQty($report['totals']['qty']) }}</span>
                                <span x-show="mode === 'rupiah'"
                                    x-cloak>{{ $formatMoney($report['totals']['value']) }}</span>
                            </td>
                            <td
                                class="sticky right-0 z-20 border-l border-slate-200 bg-slate-50 px-3 py-3 tabular-nums text-right">
                                {{ $formatMoney($report['totals']['value']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
    @if ($report['items'] instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="report-surface report-matrix-pagination rounded-xl border border-slate-200 bg-white px-4 py-3">
            {{ $report['items']->links() }}</div>
    @endif
</section>
