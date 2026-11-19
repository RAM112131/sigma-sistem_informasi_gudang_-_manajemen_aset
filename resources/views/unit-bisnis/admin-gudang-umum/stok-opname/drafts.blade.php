@extends('layouts.app')

@section('title', 'Draf Stok Opname — ' . config('app.name', 'SIGMA'))

@section('content')
    <style>
        .stocktake-drafts-page {
            --draft-helper: 12px;
            min-width: 0;
        }

        .stocktake-drafts-header,
        .stocktake-drafts-panel {
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
        }

        .stocktake-drafts-header {
            padding: 14px 16px;
        }

        .stocktake-drafts-header h1 {
            margin: 0;
            color: var(--text-primary);
            font-size: 22px;
            font-weight: 800;
            line-height: 1.2;
        }

        .stocktake-drafts-header p {
            margin: 4px 0 0;
            color: var(--text-muted);
            font-size: var(--draft-helper);
            line-height: 1.4;
        }

        .stocktake-drafts-eyebrow,
        .stocktake-drafts-count {
            color: var(--brand-primary);
            font-size: var(--draft-helper);
            font-weight: 800;
        }

        .stocktake-drafts-eyebrow {
            display: inline-block;
            margin-bottom: 5px;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .stocktake-drafts-count {
            flex: 0 0 auto;
            padding: 4px 7px;
            border-radius: var(--radius);
            background: rgba(20, 92, 84, .1);
        }

        .stocktake-drafts-continue,
        .stocktake-drafts-empty-action {
            display: inline-flex;
            min-height: 38px;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            font-size: var(--draft-helper);
            font-weight: 800;
            transition: background-color .15s ease, border-color .15s ease, color .15s ease;
        }

        .stocktake-drafts-empty-action:hover {
            border-color: var(--brand-primary);
            background: var(--surface-base);
        }

        .stocktake-drafts-panel {
            overflow: hidden;
        }

        .stocktake-drafts-panel-header {
            display: flex;
            min-height: 68px;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--surface-border);
        }

        .stocktake-drafts-panel-header h2 {
            margin: 0;
            color: var(--text-primary);
            font-size: 20px;
            font-weight: 800;
            line-height: 1.2;
        }

        .stocktake-drafts-panel-header p {
            margin: 3px 0 0;
            color: var(--text-muted);
            font-size: var(--draft-helper);
            line-height: 1.4;
        }

        .stocktake-drafts-table-wrap {
            overflow-x: auto;
        }

        .stocktake-drafts-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .stocktake-drafts-table th {
            padding: 10px 12px;
            border-bottom: 1px solid var(--surface-border);
            background: var(--surface-base);
            color: var(--text-muted);
            font-size: var(--draft-helper);
            font-weight: 800;
            letter-spacing: .035em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .stocktake-drafts-table td {
            padding: 12px;
            border-bottom: 1px solid #edf2f0;
            color: var(--text-primary);
            font-size: 14px;
            vertical-align: middle;
        }

        .stocktake-drafts-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .stocktake-draft-period {
            font-weight: 800;
            white-space: nowrap;
        }

        .stocktake-draft-progress {
            min-width: 180px;
        }

        .stocktake-draft-progress-label {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 5px;
            color: var(--text-muted);
            font-size: var(--draft-helper);
            font-variant-numeric: tabular-nums;
        }

        .stocktake-draft-progress-track {
            height: 6px;
            overflow: hidden;
            border-radius: 999px;
            background: var(--surface-base);
        }

        .stocktake-draft-progress-value {
            height: 100%;
            border-radius: inherit;
            background: var(--brand-primary);
        }

        .stocktake-drafts-continue,
        .stocktake-drafts-empty-action {
            padding: 0 12px;
            border-color: var(--brand-primary);
            background: var(--brand-primary);
            color: #fff;
            white-space: nowrap;
        }

        .stocktake-drafts-continue:hover,
        .stocktake-drafts-empty-action:hover {
            background: var(--brand-primary-d);
            color: #fff;
        }

        .stocktake-drafts-empty {
            padding: 40px 16px;
            color: var(--text-muted);
            text-align: center;
        }

        .stocktake-drafts-empty h3 {
            margin: 0;
            color: var(--text-primary);
            font-size: 16px;
            font-weight: 800;
        }

        .stocktake-drafts-empty p {
            margin: 5px 0 16px;
            font-size: var(--draft-helper);
        }

        .stocktake-drafts-pagination {
            padding: 8px 12px;
            border-top: 1px solid var(--surface-border);
        }

        .stocktake-drafts-header :focus-visible,
        .stocktake-drafts-table :focus-visible,
        .stocktake-drafts-empty :focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
        }

        @media (max-width: 600px) {
            .stocktake-drafts-header {
                padding: 12px;
            }

            .stocktake-drafts-header h1 {
                font-size: 20px;
            }

            .stocktake-drafts-continue,
            .stocktake-drafts-empty-action {
                min-height: 44px;
            }

            .stocktake-drafts-panel-header {
                align-items: flex-start;
                padding: 12px;
            }

            .stocktake-drafts-panel-header h2 {
                font-size: 18px;
            }
        }
    </style>

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
