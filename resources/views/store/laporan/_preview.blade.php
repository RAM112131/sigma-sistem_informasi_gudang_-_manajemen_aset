<div data-laporan-preview>
    <div class="laporan-review-top">
        <div class="laporan-review-heading">
            <strong>{{ $judul }}</strong>
            <span>{{ $subjudul }} · Gudang {{ $gudang->nama }}</span>
        </div>
        <div class="laporan-export-control">
            <button type="button" class="btn btn-primary"
                data-export-url="{{ route('store.laporan.export.generate', ['jenis' => $jenis, 'periode' => $periode, 'hanya_perlu_restock' => $hanyaPerluRestock ? 1 : null]) }}"
                data-filename="{{ $namaFile }}">Export Excel</button>
            <span class="laporan-export-message" data-export-status role="status" aria-live="polite"></span>
        </div>
    </div>

    <div class="laporan-review-toolbar">
        <div>
            <span class="laporan-caption">PERIODE LAPORAN</span>
            <form method="GET" action="{{ route('store.laporan.export.preview', ['jenis' => $jenis]) }}"
                class="laporan-period-form">
                <label for="review-periode-{{ $jenis }}" class="sr-only">Periode laporan</label>
                <input id="review-periode-{{ $jenis }}" type="month" name="periode" value="{{ $periode }}"
                    max="{{ now()->format('Y-m') }}" required>
                <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                @if ($hanyaPerluRestock)
                    <input type="hidden" name="hanya_perlu_restock" value="1">
                @endif
                <button class="btn btn-outline" type="submit">Tampilkan periode</button>
            </form>
        </div>
        <div class="laporan-review-summary">
            <span class="laporan-caption">SHEET YANG DIREVIEW</span>
            <strong>{{ sprintf('%02d', $tanggal) }}
                {{ \Carbon\Carbon::parse($periode . '-' . $tanggal)->locale((string) config('app.locale'))->translatedFormat('F Y') }}</strong>
        </div>
    </div>

    <nav class="laporan-sheet-tabs" aria-label="Pilih tanggal sheet">
        @for ($hari = 1; $hari <= $jumlahHari; $hari++)
            <a href="{{ route('store.laporan.export.preview', ['jenis' => $jenis, 'periode' => $periode, 'tanggal' => $hari, 'hanya_perlu_restock' => $hanyaPerluRestock ? 1 : null]) }}"
                data-review-tanggal="{{ $hari }}"
                class="laporan-sheet-tab{{ $hari === $tanggal ? ' is-active' : '' }}"
                @if ($hari === $tanggal) aria-current="page" @endif>{{ $hari }}</a>
        @endfor
    </nav>

    <div class="laporan-sheet-frame">
        <div class="laporan-table-scroll">
            <table class="laporan-data-table">
                <thead>
                    <tr>
                        @foreach ($kolom as $kolomJudul)
                            <th scope="col">{{ $kolomJudul }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($baris as $barisLaporan)
                        <tr>
                            @foreach ($kolom as $index => $kolomJudul)
                                <td>{{ $barisLaporan[$index] ?? '' }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ max(count($kolom), 1) }}" class="laporan-empty">
                                Tidak ada data laporan untuk tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="laporan-sheet-status">
            <span>Sheet {{ $tanggal }} · {{ number_format($jumlah_data, 0, ',', '.') }} baris data</span>
            @if ($jumlah_data > $batas_preview)
                <span>Review menampilkan {{ number_format($batas_preview, 0, ',', '.') }} baris pertama. File Excel
                    memuat seluruh data.</span>
            @endif
        </div>
    </div>
</div>
