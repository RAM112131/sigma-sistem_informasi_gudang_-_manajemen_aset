<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-1 border-b border-slate-200 px-4 py-4 sm:px-5">
        <h2 class="text-base font-bold text-slate-900">Mutasi Stok &amp; Nilai Persediaan</h2>
        <p class="text-xs text-slate-500">Saldo awal dihitung dari histori stok hingga bulan sebelumnya. HPP memakai
            harga satuan yang tersedia pada barang.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="report-table min-w-[1180px] w-full text-left text-xs">
            <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-3 py-3">Kode / Nama Barang</th>
                    <th class="px-3 py-3 text-right">Saldo Awal</th>
                    <th class="px-3 py-3 text-right">Masuk Store</th>
                    <th class="px-3 py-3 text-right">Masuk Beli Umum</th>
                    <th class="px-3 py-3 text-right">Keluar Bersih</th>
                    <th class="px-3 py-3 text-right">Selisih Opname</th>
                    <th class="px-3 py-3 text-right">Saldo Akhir (Qty)</th>
                    <th class="px-3 py-3 text-right">HPP Acuan (Rp)</th>
                    <th class="px-3 py-3 text-right">Total Nilai Stok (Rp)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($report['items'] as $item)
                    <tr class="transition hover:bg-slate-50/80">
                        <td class="px-3 py-3">
                            <div class="font-semibold text-slate-800">{{ $item['kode'] }} · {{ $item['nama'] }}</div>
                            <div class="mt-0.5 text-[11px] text-slate-500">{{ $item['satuan'] }} <span
                                    class="mx-1">·</span> {{ $item['kategori'] }}</div>
                        </td>
                        @foreach (['saldo_awal', 'masuk_store', 'masuk_beli_umum', 'keluar_bersih', 'selisih_opname', 'saldo_akhir'] as $field)
                            <td class="px-3 py-3 {{ $numberClass($item[$field]) }}">{{ $formatQty($item[$field]) }}</td>
                        @endforeach
                        <td class="px-3 py-3 {{ $numberClass($item['hpp']) }}">{{ $formatMoney($item['hpp']) }}</td>
                        <td class="px-3 py-3 {{ $numberClass($item['nilai_stok']) }}">
                            {{ $formatMoney($item['nilai_stok']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-sm text-slate-500">Tidak ada barang yang
                            sesuai dengan filter.</td>
                    </tr>
                @endforelse
            </tbody>
            @if (count($report['items']) > 0)
                <tfoot class="border-t-2 border-slate-200 bg-slate-50 text-xs font-bold text-slate-800">
                    <tr>
                        <td class="px-3 py-3">GRAND TOTAL</td>
                        @foreach (['saldo_awal', 'masuk_store', 'masuk_beli_umum', 'keluar_bersih', 'selisih_opname', 'saldo_akhir'] as $field)
                            <td class="px-3 py-3 tabular-nums text-right">{{ $formatQty($report['totals'][$field]) }}
                            </td>
                        @endforeach
                        <td class="px-3 py-3 text-right text-slate-400">—</td>
                        <td class="px-3 py-3 tabular-nums text-right">
                            {{ $formatMoney($report['totals']['nilai_stok']) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    @if ($report['items'] instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="border-t border-slate-200 px-4 py-3">{{ $report['items']->links() }}</div>
    @endif
</section>
