<section class="space-y-5">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <article class="rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:p-5">
            <p class="text-xs font-semibold text-amber-900">Dampak Selisih Opname</p>
            <p class="mt-2 text-xl font-bold text-amber-900 tabular-nums">
                {{ $formatMoney($report['totals']['difference_value']) }}</p>
            <p class="mt-1 text-xs text-amber-800">Jumlah selisih {{ $formatQty($report['totals']['difference_qty']) }}
            </p>
        </article>
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <p class="text-xs font-semibold text-slate-500">Barang Rusak dari Penerimaan</p>
            <p class="mt-2 text-xl font-bold text-slate-800 tabular-nums">
                {{ $formatQty($report['totals']['damaged_qty']) }}
                {{ $formatMoney($report['totals']['damaged_value']) }}</p>
            <p class="mt-1 text-xs text-slate-500">Kerusakan internal belum dapat ditampilkan karena belum ada catatan
                sumber di sistem.</p>
        </article>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
            <h2 class="text-base font-bold text-slate-900">A · Selisih Stok Opname Bulanan</h2>
            <p class="mt-1 text-xs text-slate-500">Hanya data opname yang sudah diterapkan, dengan jumlah fisik berbeda
                dari saldo sistem.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="report-table min-w-[980px] w-full text-left text-xs">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-3 py-3">Kode &amp; Nama Barang</th>
                        <th class="px-3 py-3">Satuan</th>
                        <th class="px-3 py-3 text-right">Saldo Sistem</th>
                        <th class="px-3 py-3 text-right">Stok Fisik</th>
                        <th class="px-3 py-3 text-right">Selisih Qty</th>
                        <th class="px-3 py-3 text-right">HPP Satuan (Rp)</th>
                        <th class="px-3 py-3 text-right">Selisih Nilai (Rp)</th>
                        <th class="px-3 py-3">Alasan / Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($report['opname_items'] as $item)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-3 py-3"><span class="font-semibold">{{ $item['kode'] }}</span><span
                                    class="ml-1 text-slate-600">{{ $item['nama'] }}</span></td>
                            <td class="px-3 py-3 text-slate-500">{{ $item['satuan'] }}</td>
                            <td class="px-3 py-3 {{ $numberClass($item['saldo_sistem']) }}">
                                {{ $formatQty($item['saldo_sistem']) }}</td>
                            <td class="px-3 py-3 {{ $numberClass($item['stok_fisik']) }}">
                                {{ $formatQty($item['stok_fisik']) }}</td>
                            <td
                                class="px-3 py-3 tabular-nums text-right font-semibold {{ $item['selisih_qty'] < 0 ? 'text-amber-700' : 'text-[#145C54]' }}">
                                {{ $formatQty($item['selisih_qty']) }}</td>
                            <td class="px-3 py-3 {{ $numberClass($item['hpp']) }}">{{ $formatMoney($item['hpp']) }}
                            </td>
                            <td class="px-3 py-3 {{ $numberClass($item['selisih_nilai']) }}">
                                {{ $formatMoney($item['selisih_nilai']) }}</td>
                            <td class="max-w-64 whitespace-normal px-3 py-3 text-slate-600">
                                {{ $item['catatan'] ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">Tidak ada selisih
                                opname yang sudah diterapkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($report['opname_items'] instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="border-t border-slate-100 px-4 py-3">
                {{ $report['opname_items']->links() }}
            </div>
        @endif
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
            <h2 class="text-base font-bold text-slate-900">B · Barang Rusak &amp; Karantina</h2>
            <p class="mt-1 text-xs text-slate-500">Daftar berasal dari catatan kerusakan pada penerimaan supplier dan
                transfer Store yang sudah diverifikasi.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="report-table min-w-[980px] w-full text-left text-xs">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-3 py-3">Dokumen / Asal</th>
                        <th class="px-3 py-3">Kode &amp; Nama Barang</th>
                        <th class="px-3 py-3 text-right">Qty Rusak</th>
                        <th class="px-3 py-3 text-right">HPP Satuan</th>
                        <th class="px-3 py-3 text-right">Nilai Dampak</th>
                        <th class="px-3 py-3">Penanganan</th>
                        <th class="px-3 py-3">Catatan / Klaim</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($report['damaged_items'] as $item)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-3 py-3"><span
                                    class="font-semibold text-slate-800">{{ $item['document_number'] }}</span><span
                                    class="mt-0.5 block text-slate-500">{{ $item['source_name'] }} ·
                                    {{ $item['source_type'] }}</span></td>
                            <td class="px-3 py-3"><span class="font-semibold">{{ $item['kode'] }}</span><span
                                    class="ml-1 text-slate-600">{{ $item['nama'] }}</span></td>
                            <td class="px-3 py-3 {{ $numberClass($item['damaged_qty']) }}">
                                {{ $formatQty($item['damaged_qty']) }} {{ $item['satuan'] }}</td>
                            <td class="px-3 py-3 {{ $numberClass($item['hpp']) }}">{{ $formatMoney($item['hpp']) }}
                            </td>
                            <td class="px-3 py-3 {{ $numberClass($item['impact_value']) }}">
                                {{ $formatMoney($item['impact_value']) }}</td>
                            <td class="px-3 py-3 text-amber-800">{{ $item['handling_status'] }}</td>
                            <td class="max-w-64 whitespace-normal px-3 py-3 text-slate-600">{{ $item['note'] ?: '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">Tidak ada catatan
                                barang rusak pada penerimaan untuk periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($report['damaged_items'] instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="border-t border-slate-100 px-4 py-3">
                {{ $report['damaged_items']->links() }}
            </div>
        @endif
    </section>
</section>
