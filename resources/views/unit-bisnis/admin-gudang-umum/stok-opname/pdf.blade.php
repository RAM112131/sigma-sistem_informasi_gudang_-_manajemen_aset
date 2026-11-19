<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Berita Acara Stok Opname {{ $opname->periode->format('m/Y') }}</title>
    <style>
        body {
            color: #1f2523;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
        }

        h1 {
            margin: 0 0 4px;
            font-size: 18px;
        }

        p {
            margin: 3px 0;
            color: #52615d;
        }

        .header {
            border-bottom: 2px solid #145c54;
            margin-bottom: 16px;
            padding-bottom: 10px;
        }

        .meta {
            margin: 12px 0 16px;
            width: 100%;
        }

        .meta td {
            padding: 3px 0;
        }

        table.items {
            border-collapse: collapse;
            width: 100%;
        }

        .items th,
        .items td {
            border: 1px solid #d7ddd9;
            padding: 6px;
        }

        .items th {
            background: #e8f1ef;
            color: #34403c;
            font-size: 8px;
            text-align: left;
            text-transform: uppercase;
        }

        .number {
            text-align: right;
            white-space: nowrap;
        }

        .signatures {
            margin-top: 40px;
            width: 100%;
        }

        .signatures td {
            padding-top: 48px;
            text-align: center;
            width: 50%;
        }
    </style>
</head>

<body>
    @php
        $hasItemSnapshots = $opname->details->contains(static fn($detail): bool => $detail->qty_sistem_at !== null);
    @endphp

    <header class="header">
        <h1>Berita Acara Stok Opname Bulanan</h1>
        <p>Dokumen penyesuaian stok fisik Gudang Umum</p>
    </header>

    <table class="meta">
        <tr>
            <td><strong>Gudang</strong></td>
            <td>{{ $opname->gudang->nama }} ({{ $opname->gudang->kode }})</td>
            <td><strong>Periode</strong></td>
            <td>{{ $opname->periode->translatedFormat('F Y') }}</td>
        </tr>
        <tr>
            <td><strong>Tanggal efektif</strong></td>
            <td>{{ $hasItemSnapshots ? 'Sesuai waktu hitung tiap barang' : $opname->tanggal_efektif->translatedFormat('d F Y') }}
            </td>
            <td><strong>Diterapkan</strong></td>
            <td>{{ $opname->diterapkan_at?->translatedFormat('d F Y, H:i') ?? '—' }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Kode SKU</th>
                <th>Nama barang</th>
                <th>Satuan</th>
                <th class="number">Saldo sistem</th>
                <th class="number">Stok fisik</th>
                <th>Waktu hitung</th>
                <th class="number">Selisih qty</th>
                <th class="number">Nilai selisih</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @php $nilaiBersih = 0.0; @endphp
            @foreach ($opname->details as $detail)
                @php
                    $selisih = (float) $detail->qty_fisik - (float) $detail->qty_sistem;
                    $nilai = $selisih * (float) $detail->harga_satuan;
                    $nilaiBersih += $nilai;
                @endphp
                <tr>
                    <td>{{ $detail->kode_barang }}</td>
                    <td>{{ $detail->nama_barang }}</td>
                    <td>{{ $detail->satuan ?? '—' }}</td>
                    <td class="number">{{ number_format((float) $detail->qty_sistem, 3, ',', '.') }}</td>
                    <td class="number">{{ number_format((float) $detail->qty_fisik, 3, ',', '.') }}</td>
                    <td>{{ $detail->qty_sistem_at?->locale('id')->translatedFormat('d M Y, H:i') ?? $opname->tanggal_efektif->translatedFormat('d M Y') }}
                    </td>
                    <td class="number">{{ $selisih > 0 ? '+' : '' }}{{ number_format($selisih, 3, ',', '.') }}</td>
                    <td class="number">Rp {{ number_format($nilai, 0, ',', '.') }}</td>
                    <td>{{ $detail->catatan ?: '—' }}</td>
                </tr>
            @endforeach
            <tr>
                <th colspan="7" class="number">Total selisih nilai</th>
                <th class="number">Rp {{ number_format($nilaiBersih, 0, ',', '.') }}</th>
                <td></td>
            </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>Petugas Gudang</td>
            <td>Admin Gudang Umum</td>
        </tr>
        <tr>
            <td>( ____________________ )</td>
            <td>{{ $opname->diterapkanOleh?->name ?? '—' }}<br>( ____________________ )</td>
        </tr>
    </table>
</body>

</html>
