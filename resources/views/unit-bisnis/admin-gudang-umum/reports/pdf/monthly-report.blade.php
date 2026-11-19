<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Rekapitulasi Laporan Gudang Umum</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 24px 28px 30px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            color: #1f2937;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
        }

        h1 {
            color: #145C54;
            font-size: 17px;
            margin: 0 0 3px;
        }

        h2 {
            color: #0F4740;
            font-size: 12px;
            margin: 18px 0 7px;
        }

        p {
            margin: 3px 0;
        }

        .header {
            border-bottom: 2px solid #145C54;
            margin-bottom: 13px;
            padding-bottom: 9px;
        }

        .muted {
            color: #64748b;
        }

        .status {
            color: #9a5c1f;
            font-weight: bold;
        }

        table {
            border-collapse: collapse;
            margin-top: 8px;
            width: 100%;
        }

        thead {
            display: table-header-group;
        }

        th {
            background: #145C54;
            color: #fff;
            font-size: 8px;
            text-align: left;
        }

        th,
        td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            vertical-align: top;
        }

        td.num,
        th.num {
            text-align: right;
            white-space: nowrap;
        }

        tbody tr {
            page-break-inside: avoid;
        }

        .summary {
            background: #f1f5f9;
            font-weight: bold;
        }

        .signatures {
            margin-top: 42px;
            page-break-inside: avoid;
            width: 100%;
        }

        .signatures td {
            border: 0;
            padding: 4px;
            text-align: center;
            width: 50%;
        }

        .sign-space {
            height: 54px;
        }

        .note {
            color: #64748b;
            font-size: 8px;
            margin-top: 6px;
        }
    </style>
</head>

<body>
    @php
        $formatQty = static fn(mixed $value): string => rtrim(
            rtrim(number_format((float) $value, 3, ',', '.'), '0'),
            ',',
        );
        $formatMoney = static fn(mixed $value): string => ((float) $value < 0 ? '-Rp ' : 'Rp ') .
            number_format(abs((float) $value), 0, ',', '.');
    @endphp

    <header class="header">
        <h1>CV Sukahati Pratama — Gudang Umum</h1>
        <p><strong>Rekapitulasi Laporan Matriks</strong> · {{ $periodLabel }}</p>
        <p class="muted">Gudang: {{ $activeGudang->nama }} · Status: <span class="status">{{ $reportStatus }}</span></p>
    </header>

    @if ($filters['tab'] === 'mutasi')
        <h2>Mutasi Stok &amp; Nilai Persediaan</h2>
        <table>
            <thead>
                <tr>
                    <th>Kode SKU</th>
                    <th>Nama Barang</th>
                    <th>Satuan</th>
                    <th>Kategori</th>
                    <th class="num">Saldo Awal</th>
                    <th class="num">Masuk Store</th>
                    <th class="num">Masuk Beli Umum</th>
                    <th class="num">Keluar Bersih</th>
                    <th class="num">Selisih Opname</th>
                    <th class="num">Saldo Akhir</th>
                    <th class="num">HPP Acuan</th>
                    <th class="num">Nilai Stok</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['items'] as $item)
                    <tr>
                        <td>{{ $item['kode'] }}</td>
                        <td>{{ $item['nama'] }}</td>
                        <td>{{ $item['satuan'] }}</td>
                        <td>{{ $item['kategori'] }}</td>
                        <td class="num">{{ $formatQty($item['saldo_awal']) }}</td>
                        <td class="num">{{ $formatQty($item['masuk_store']) }}</td>
                        <td class="num">{{ $formatQty($item['masuk_beli_umum']) }}</td>
                        <td class="num">{{ $formatQty($item['keluar_bersih']) }}</td>
                        <td class="num">{{ $formatQty($item['selisih_opname']) }}</td>
                        <td class="num">{{ $formatQty($item['saldo_akhir']) }}</td>
                        <td class="num">{{ $formatMoney($item['hpp']) }}</td>
                        <td class="num">{{ $formatMoney($item['nilai_stok']) }}</td>
                    </tr>
                @endforeach
                <tr class="summary">
                    <td colspan="4">GRAND TOTAL</td>
                    <td class="num">{{ $formatQty($report['totals']['saldo_awal']) }}</td>
                    <td class="num">{{ $formatQty($report['totals']['masuk_store']) }}</td>
                    <td class="num">{{ $formatQty($report['totals']['masuk_beli_umum']) }}</td>
                    <td class="num">{{ $formatQty($report['totals']['keluar_bersih']) }}</td>
                    <td class="num">{{ $formatQty($report['totals']['selisih_opname']) }}</td>
                    <td class="num">{{ $formatQty($report['totals']['saldo_akhir']) }}</td>
                    <td class="num">—</td>
                    <td class="num">{{ $formatMoney($report['totals']['nilai_stok']) }}</td>
                </tr>
            </tbody>
        </table>
        <p class="note">HPP menggunakan harga satuan yang tersedia pada data barang karena histori HPP akhir periode
            belum disimpan terpisah.</p>
    @elseif($filters['tab'] === 'matriks')
        <h2>Matriks Pemakaian per Divisi · Mode {{ $filters['matrix_mode'] === 'qty' ? 'Qty' : 'Rp' }}</h2>
        <table>
            <thead>
                <tr>
                    <th>Kode SKU</th>
                    <th>Nama Barang</th>
                    <th>Satuan</th>
                    @foreach ($report['divisions'] as $division)
                        <th class="num">{{ $division['nama'] }}</th>
                    @endforeach
                    <th class="num">
                        Total Pemakaian</th>
                    <th class="num">Total Biaya</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['items'] as $item)
                    <tr>
                        <td>{{ $item['kode'] }}</td>
                        <td>{{ $item['nama'] }}</td>
                        <td>{{ $item['satuan'] }}</td>
                        @foreach ($report['divisions'] as $division)
                            <td class="num">
                                {{ $filters['matrix_mode'] === 'qty' ? $formatQty($item['divisions'][$division['id']]['qty']) : $formatMoney($item['divisions'][$division['id']]['value']) }}
                            </td>
                        @endforeach
                        <td class="num">
                            {{ $formatQty($item['total_qty']) }}</td>
                        <td class="num">{{ $formatMoney($item['total_value']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($filters['tab'] === 'pemasukan')
        <h2>Rekap Pemasukan</h2>
        <p>Transfer Store: {{ $formatMoney($report['kpi']['store_value']) }} ({{ $report['kpi']['store_documents'] }}
            dokumen) · Pembelian Umum: {{ $formatMoney($report['kpi']['purchase_value']) }}
            ({{ $report['kpi']['purchase_documents'] }} dokumen) · Total:
            {{ $formatMoney($report['kpi']['total_value']) }}</p>
        <table>
            <thead>
                <tr>
                    <th>No. Dokumen</th>
                    <th>Tanggal Verifikasi</th>
                    <th>Sumber / Supplier</th>
                    <th>No. Nota / Surat Jalan</th>
                    <th class="num">Jumlah SKU</th>
                    <th>Kondisi Fisik</th>
                    <th class="num">Nilai Masuk</th>
                    <th>Catatan Tindak Lanjut / Klaim</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['documents'] as $document)
                    <tr>
                        <td>{{ $document['document_number'] }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($document['verified_at'])->format('d/m/Y H:i') }}</td>
                        <td>{{ $document['source_name'] }}</td>
                        <td>{{ $document['reference_number'] }}</td>
                        <td class="num">{{ $document['sku_count'] }}</td>
                        <td>{{ $document['physical_condition'] }}</td>
                        <td class="num">{{ $formatMoney($document['total_value']) }}</td>
                        <td>{{ $document['follow_up'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <h2>A · Selisih Stok Opname Bulanan</h2>
        <table>
            <thead>
                <tr>
                    <th>Kode SKU</th>
                    <th>Nama Barang</th>
                    <th>Satuan</th>
                    <th class="num">Saldo Sistem</th>
                    <th class="num">Stok Fisik</th>
                    <th class="num">Selisih Qty</th>
                    <th class="num">HPP</th>
                    <th class="num">Selisih Nilai</th>
                    <th>Alasan / Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['opname_items'] as $item)
                    <tr>
                        <td>{{ $item['kode'] }}</td>
                        <td>{{ $item['nama'] }}</td>
                        <td>{{ $item['satuan'] }}</td>
                        <td class="num">{{ $formatQty($item['saldo_sistem']) }}</td>
                        <td class="num">{{ $formatQty($item['stok_fisik']) }}</td>
                        <td class="num">{{ $formatQty($item['selisih_qty']) }}</td>
                        <td class="num">{{ $formatMoney($item['hpp']) }}</td>
                        <td class="num">{{ $formatMoney($item['selisih_nilai']) }}</td>
                        <td>{{ $item['catatan'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <h2>B · Barang Rusak dari Penerimaan / Transfer Store</h2>
        <table>
            <thead>
                <tr>
                    <th>Dokumen</th>
                    <th>Asal</th>
                    <th>Kode SKU</th>
                    <th>Nama Barang</th>
                    <th class="num">Qty Rusak</th>
                    <th class="num">HPP</th>
                    <th class="num">Nilai Dampak</th>
                    <th>Penanganan</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['damaged_items'] as $item)
                    <tr>
                        <td>{{ $item['document_number'] }}</td>
                        <td>{{ $item['source_name'] }}</td>
                        <td>{{ $item['kode'] }}</td>
                        <td>{{ $item['nama'] }}</td>
                        <td class="num">{{ $formatQty($item['damaged_qty']) }}</td>
                        <td class="num">{{ $formatMoney($item['hpp']) }}</td>
                        <td class="num">{{ $formatMoney($item['impact_value']) }}</td>
                        <td>{{ $item['handling_status'] }}</td>
                        <td>{{ $item['note'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="note">Catatan kerusakan internal belum tersedia di sistem. Laporan ini hanya memuat kerusakan yang
            tercatat pada penerimaan.</p>
    @endif

    <table class="signatures">
        <tr>
            <td>Dibuat Oleh: Admin GU</td>
            <td>Diperiksa / Disetujui Oleh: Pimpinan / Finance</td>
        </tr>
        <tr>
            <td class="sign-space"></td>
            <td class="sign-space"></td>
        </tr>
        <tr>
            <td>( __________________________ )</td>
            <td>( __________________________ )</td>
        </tr>
    </table>
</body>

</html>
