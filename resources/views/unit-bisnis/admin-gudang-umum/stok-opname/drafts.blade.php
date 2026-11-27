@extends('layouts.app')

@section('title', 'Draf Stok Opname — ' . config('app.name', 'SIGMA'))

@section('content')
    @push('styles')
    @vite('resources/css/pages/unit-bisnis/admin-gudang-umum/stok-opname/drafts.css')
@endpush

    <div class="stocktake-drafts-page space-y-5 pb-10">
        <header class="stocktake-drafts-header">
            <div>
                <span class="stocktake-drafts-eyebrow">Stok Opname Bulanan</span>
                <h1>Draf Stok Opname</h1>
                <p>Draf tersimpan belum mengubah saldo stok. Lanjutkan hitungan dari periode dan gudang yang sama.</p>
            </div>
        </header>

        @include('unit-bisnis.admin-gudang-umum.stok-opname._tabs', ['activeTab' => 'drafts'])

        <section class="stocktake-drafts-panel" aria-labelledby="stocktake-drafts-title">
            <header class="stocktake-drafts-panel-header">
                <div>
                    <h2 id="stocktake-drafts-title">Draf tersimpan</h2>
                    <p>Pilih draf untuk melanjutkan atau melengkapi hitungan fisik.</p>
                </div>
                <span class="stocktake-drafts-count">{{ number_format($drafts->total()) }} draf</span>
            </header>

            @if ($drafts->isEmpty())
                <div class="stocktake-drafts-empty">
                    <h3>Belum ada draf opname</h3>
                    <p>Draf akan muncul di sini setelah hitungan fisik disimpan.</p>
                    <a href="{{ route('unit-bisnis.admin-gu.stok-opname.index') }}" class="stocktake-drafts-empty-action">
                        Mulai Stok Opname
                    </a>
                </div>
            @else
                <div class="stocktake-drafts-table-wrap">
                    <table class="stocktake-drafts-table">
                        <caption class="sr-only">Daftar draf stok opname yang dapat dilanjutkan</caption>
                        <thead>
                            <tr>
                                <th scope="col">Periode</th>
                                <th scope="col">Gudang</th>
                                <th scope="col">Progres hitungan</th>
                                <th scope="col"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($drafts as $draft)
                                @php
                                    $totalDetails = (int) $draft->details_count;
                                    $countedDetails = (int) $draft->counted_details_count;
                                    $progress =
                                        $totalDetails === 0 ? 0 : round(($countedDetails / $totalDetails) * 100, 1);
                                    $periodLabel = $draft->periode->locale('id')->translatedFormat('F Y');
                                @endphp
                                <tr>
                                    <td class="stocktake-draft-period">{{ $periodLabel }}</td>
                                    <td>{{ $draft->gudang->nama }}</td>
                                    <td>
                                        <div class="stocktake-draft-progress">
                                            <div class="stocktake-draft-progress-label">
                                                <span>{{ number_format($countedDetails) }} dari
                                                    {{ number_format($totalDetails) }} barang</span>
                                                <span>{{ number_format($progress, 1, ',', '.') }}%</span>
                                            </div>
                                            <div class="stocktake-draft-progress-track" role="progressbar"
                                                aria-label="Progres opname {{ $draft->gudang->nama }} periode {{ $periodLabel }}"
                                                aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}">
                                                <div class="stocktake-draft-progress-value"
                                                    style="width: {{ $progress }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('unit-bisnis.admin-gu.stok-opname.index', ['periode' => $draft->periode->format('Y-m'), 'gudang_id' => $draft->gudang_id]) }}"
                                            class="stocktake-drafts-continue">
                                            Lanjutkan
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($drafts->hasPages())
                    <div class="stocktake-drafts-pagination">
                        {{ $drafts->links() }}
                    </div>
                @endif
            @endif
        </section>
    </div>
@endsection
