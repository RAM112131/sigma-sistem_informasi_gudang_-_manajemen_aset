@extends('layouts.app')

@section('title', 'Status Kiriman Store — ' . config('app.name', 'SIGMA'))

@section('content')
    <div class="gu-dashboard space-y-4 pb-24">
        <header class="gu-page-heading">
            <h1>Status Kiriman Store</h1>
            <p>Pantau kiriman barang dari Gudang Store menuju {{ $gudang->nama }}.</p>
        </header>

        @if ($errors->any())
            <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                <h2 class="font-bold">Penerimaan belum dapat disimpan</h2>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="font-bold text-slate-900">Daftar Kiriman</h2>
                <p class="mt-1 text-sm text-slate-600">Gudang tujuan: {{ $gudang->nama }}</p>
            </div>
            <form method="GET" action="{{ route($listingRoute) }}" class="flex flex-wrap items-end gap-3">
                <label for="status" class="grid gap-1 text-sm font-medium text-slate-700">
                    Status kiriman
                    <select id="status" name="status" class="rounded-lg border border-slate-300 px-3 py-2">
                        <option value="dikirim" @selected($status === 'dikirim')>Dalam Perjalanan</option>
                        <option value="diterima" @selected($status === 'diterima')>Telah Diterima</option>
                    </select>
                </label>
                <button type="submit" class="btn btn-primary">Tampilkan</button>
            </form>
        </section>

        @unless ($canConfirmReceipt)
            <p class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                Kiriman ini hanya untuk melihat. Konfirmasi penerimaan dilakukan oleh petugas Recording Gudang Umum.
            </p>
        @endunless

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th scope="col" class="px-4 py-3">Nomor SJ</th>
                            <th scope="col" class="px-4 py-3">Permintaan</th>
                            <th scope="col" class="px-4 py-3">Barang</th>
                            <th scope="col" class="px-4 py-3">Jumlah Dikirim</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            @if ($canConfirmReceipt)
                                <th scope="col" class="px-4 py-3">Penerimaan</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($transfers as $transfer)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">
                                    {{ $transfer->nomor_sj }}
                                    <span class="mt-1 block text-xs font-normal text-slate-500">
                                        {{ $transfer->gudangAsal?->nama ?? 'Gudang Store' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ $transfer->permintaanBarang?->nomor_pr ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="block font-medium text-slate-900">{{ $transfer->barang?->nama ?? '-' }}</span>
                                    <span class="text-xs text-slate-500">
                                        {{ $transfer->barang?->kode ?? '-' }}
                                        @if ($transfer->barang?->satuan)
                                            · {{ $transfer->barang->satuan->nama }}
                                        @endif
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    {{ number_format((float) $transfer->qty_dikirim, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                        {{ $transfer->status === 'dikirim' ? 'Dalam Perjalanan' : 'Telah Diterima' }}
                                    </span>
                                </td>
                                @if ($canConfirmReceipt)
                                    <td class="min-w-72 px-4 py-3">
                                        <details>
                                            <summary class="cursor-pointer font-semibold text-[#145C54]">
                                                Periksa &amp; Terima
                                            </summary>
                                            <form method="POST"
                                                action="{{ route($confirmationRoute, $transfer) }}"
                                                class="mt-3 grid gap-3">
                                                @csrf
                                                <label class="grid gap-1 text-xs font-medium text-slate-700">
                                                    Jumlah diterima
                                                    <input type="number" name="qty_diterima" min="0" step="1"
                                                        value="{{ $transfer->qty_dikirim }}" required
                                                        class="rounded-lg border border-slate-300 px-3 py-2">
                                                </label>
                                                <label class="grid gap-1 text-xs font-medium text-slate-700">
                                                    Jumlah rusak
                                                    <input type="number" name="qty_rusak" min="0" step="1"
                                                        value="0" required
                                                        class="rounded-lg border border-slate-300 px-3 py-2">
                                                </label>
                                                <label class="grid gap-1 text-xs font-medium text-slate-700">
                                                    Catatan selisih
                                                    <textarea name="catatan_discrepancy" rows="2" maxlength="2000"
                                                        class="rounded-lg border border-slate-300 px-3 py-2"></textarea>
                                                </label>
                                                <button type="submit" class="btn btn-primary">Simpan Penerimaan</button>
                                            </form>
                                        </details>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canConfirmReceipt ? 6 : 5 }}" class="px-4 py-10 text-center text-slate-500">
                                    Tidak ada kiriman dengan status ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($transfers->hasPages())
                <div class="border-t border-slate-200 px-4 py-3">
                    {{ $transfers->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
