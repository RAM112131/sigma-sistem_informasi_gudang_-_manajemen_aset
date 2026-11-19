@pushOnce('styles')
    <style>
        .area-master-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.8fr) minmax(280px, .9fr);
            align-items: start;
            gap: 12px;
        }

        .area-master-card {
            min-width: 0;
            padding: 16px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
            box-shadow: 0 1px 3px rgba(0, 0, 0, .04);
        }

        .area-master-card h2 {
            margin: 0 0 6px;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .area-master-card p {
            margin: 0;
            color: var(--text-muted);
            font-size: .82rem;
            line-height: 1.5;
        }

        .area-master-side {
            display: grid;
            gap: 12px;
        }

        .area-master-period-group {
            display: grid;
            gap: 8px;
        }

        .area-master-period-picker {
            display: grid;
            max-width: 180px;
            gap: 4px;
        }

        .area-master-card--warning {
            border-top: 3px solid var(--status-warning, #c97a2b);
        }

        .area-master-search {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }

        .area-master-search .form-control {
            min-width: 0;
        }

        .area-master-table-wrap {
            overflow-x: auto;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
        }

        .area-master-table {
            width: 100%;
            min-width: 680px;
            border-collapse: collapse;
            font-size: .8rem;
        }

        .area-master-table th,
        .area-master-table td {
            padding: 10px 11px;
            border-bottom: 1px solid var(--surface-border);
            text-align: left;
            vertical-align: middle;
        }

        .area-master-table th {
            background: var(--surface-base);
            color: var(--text-muted);
            font-size: .69rem;
            font-weight: 750;
            letter-spacing: .035em;
            text-transform: uppercase;
        }

        .area-master-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .area-master-table td:nth-child(2),
        .area-master-table td:nth-child(3) {
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .area-master-name {
            color: var(--text-primary);
            font-weight: 700;
        }

        .area-master-inactive {
            display: inline-flex;
            margin-left: 6px;
            padding: 2px 6px;
            border-radius: 999px;
            background: var(--surface-base);
            color: var(--text-muted);
            font-size: .68rem;
        }

        .area-master-action-buttons {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .area-master-action-buttons .btn {
            padding: 4px 10px;
            min-height: 32px;
            font-size: .78rem;
        }

        .area-master-orphan-form button {
            min-height: 38px;
        }

        .area-master-quick-form {
            display: grid;
            gap: 10px;
            margin-top: 14px;
        }

        .area-master-quick-form .btn {
            width: 100%;
            min-height: 44px;
        }

        .area-master-orphans {
            display: grid;
            gap: 8px;
            margin-top: 12px;
        }

        .area-master-orphan-form {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding-top: 8px;
            border-top: 1px solid var(--surface-border);
        }

        .area-master-orphan-form span {
            min-width: 0;
            color: var(--text-primary);
            font-size: .8rem;
            overflow-wrap: anywhere;
        }

        .area-master-empty {
            padding: 24px 12px;
            color: var(--text-muted);
            text-align: center;
        }

        .area-master-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-top: 12px;
            color: var(--text-muted);
            font-size: .78rem;
        }

        .area-master-warehouse-picker {
            display: grid;
            max-width: 480px;
            gap: 10px;
            margin-top: 14px;
        }

        @media (max-width: 900px) {
            .area-master-layout {
                grid-template-columns: minmax(0, 1fr);
            }

            .area-master-side {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 620px) {
            .area-master-side {
                grid-template-columns: minmax(0, 1fr);
            }

            .area-master-card {
                padding: 12px;
            }

            .area-master-footer {
                align-items: flex-start;
                flex-direction: column;
            }

            .area-master-edit-form {
                min-width: 0;
                flex-wrap: wrap;
            }
        }
    </style>
@endPushOnce

<section class="area-master-layout" aria-label="Master data user peminta dan area">
    <div class="area-master-card">
        <h2>Daftar user peminta / area</h2>
        <p>
            @if ($activeAreaWarehouse)
                {{ $activeAreaWarehouse->nama }} · Jumlah permintaan dan nilai pengeluaran bulan {{ $areaMonthLabel }}.
            @else
                Pilih gudang unit bisnis untuk melihat daftar area dan aktivitasnya.
            @endif
        </p>

        @if (!$activeAreaWarehouse)
            @if ($areaWarehouses->isNotEmpty())
                <form method="GET" action="{{ route('master.barang.index') }}" class="area-master-warehouse-picker">
                    <input type="hidden" name="tab" value="area">
                    <label for="area-gudang-picker" class="form-label">Gudang unit bisnis</label>
                    <select id="area-gudang-picker" name="area_gudang_id" class="form-control" required>
                        <option value="">Pilih gudang</option>
                        @foreach ($areaWarehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->nama }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">Tampilkan area</button>
                </form>
            @else
                <div class="area-master-empty">Tidak ada gudang unit bisnis aktif yang dapat diakses akun ini.</div>
            @endif
        @else
            <form method="GET" action="{{ route('master.barang.index') }}" class="area-master-search">
                <input type="hidden" name="tab" value="area">
                <input type="hidden" name="area_gudang_id" value="{{ $activeAreaWarehouse->id }}">
                <input type="hidden" name="area_periode" value="{{ $selectedAreaPeriod }}">
                <label for="area-search" class="sr-only">Cari nama area atau user</label>
                <input id="area-search" type="search" name="search" class="form-control"
                    value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama area atau user">
                <button type="submit" class="btn btn-outline">Cari</button>
            </form>

            <div class="area-master-table-wrap">
                <table class="area-master-table">
                    <thead>
                        <tr>
                            <th>Area / user</th>
                            <th>Permintaan {{ $areaMonthLabel }}</th>
                            <th>Pengeluaran</th>
                            @if ($canManageArea)
                                <th>Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($areas as $area)
                            <tr>
                                <td>
                                    <span class="area-master-name">{{ $area->nama }}</span>
                                    @unless ($area->is_aktif)
                                        <span class="area-master-inactive">Nonaktif</span>
                                    @endunless
                                </td>
                                <td>{{ number_format((int) $area->request_count) }}</td>
                                <td>Rp {{ number_format((float) $area->expenditure_total, 0, ',', '.') }}</td>
                                @if ($canManageArea)
                                    <td>
                                        <div class="area-master-action-buttons">
                                            <button type="button" class="btn btn-outline"
                                                @click="modal = 'area-edit-{{ $area->id }}'">
                                                Edit
                                            </button>
                                            <form action="{{ route('master.area.destroy', $area->id) }}" method="POST"
                                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus area {{ addslashes($area->nama) }}?');"
                                                style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="area_gudang_id"
                                                    value="{{ $activeAreaWarehouse->id }}">
                                                <input type="hidden" name="area_periode"
                                                    value="{{ $selectedAreaPeriod }}">
                                                <button type="submit" class="btn btn-outline"
                                                    style="color: var(--status-critical, #e53e3e);">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                            @if ($canManageArea)
                                <template x-teleport="body">
                                    <div class="modal-backdrop master-barang-modal-scope"
                                        x-show="modal === 'area-edit-{{ $area->id }}'" x-cloak x-transition
                                        @click.self="modal = null">
                                        <section class="modal-card area-modal-card" role="dialog" aria-modal="true"
                                            aria-labelledby="edit-area-title-{{ $area->id }}"
                                            style="max-width: 480px; width: 100%;">
                                            <header class="modal-header">
                                                <h2 id="edit-area-title-{{ $area->id }}" class="modal-title">Edit
                                                    User Peminta / Area</h2>
                                                <button type="button" class="modal-close"
                                                    aria-label="Tutup dialog edit area"
                                                    @click="modal = null">&times;</button>
                                            </header>
                                            <form action="{{ route('master.area.update', $area->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="area_gudang_id"
                                                    value="{{ $activeAreaWarehouse->id }}">
                                                <input type="hidden" name="area_periode"
                                                    value="{{ $selectedAreaPeriod }}">
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label for="edit-area-nama-{{ $area->id }}"
                                                            class="form-label">Nama Area / User Peminta <span
                                                                aria-hidden="true">*</span></label>
                                                        <input id="edit-area-nama-{{ $area->id }}" name="nama"
                                                            class="form-control" value="{{ $area->nama }}"
                                                            maxlength="255" required>
                                                    </div>
                                                </div>
                                                <footer class="modal-footer">
                                                    <button type="button" class="btn btn-outline"
                                                        @click="modal = null">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Simpan
                                                        Perubahan</button>
                                                </footer>
                                            </form>
                                        </section>
                                    </div>
                                </template>
                            @endif
                        @empty
                            <tr>
                                <td colspan="{{ $canManageArea ? 4 : 3 }}" class="area-master-empty">
                                    @if (($filters['search'] ?? '') !== '')
                                        Tidak ada area yang cocok dengan pencarian.
                                    @else
                                        Belum ada user peminta atau area yang terdaftar.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <footer class="area-master-footer">
                <span>
                    @if ($areas->total() > 0)
                        Menampilkan {{ number_format($areas->firstItem()) }}–{{ number_format($areas->lastItem()) }}
                        dari {{ number_format($areas->total()) }} area terdaftar
                    @else
                        Menampilkan 0 area
                    @endif
                </span>
                {{ $areas->links() }}
            </footer>
        @endif
    </div>

    @if ($activeAreaWarehouse)
        <aside class="area-master-side" aria-label="Aksi data area">
            <div class="area-master-period-group">
                <form method="GET" action="{{ route('master.barang.index') }}" class="area-master-period-picker">
                    <input type="hidden" name="tab" value="area">
                    <input type="hidden" name="area_gudang_id" value="{{ $activeAreaWarehouse->id }}">
                    @if (($filters['search'] ?? '') !== '')
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                    @endif
                    <label for="area-period" class="form-label">Periode</label>
                    <select id="area-period" name="area_periode" class="form-control"
                        @change="$el.form.requestSubmit()">
                        @foreach ($areaPeriods as $period)
                            <option value="{{ $period['value'] }}" @selected($selectedAreaPeriod === $period['value'])>
                                {{ $period['label'] }}</option>
                        @endforeach
                    </select>
                </form>

                <section class="area-master-card area-master-card--warning">
                    <h2>Nama peminta belum terdaftar</h2>
                    <p>{{ number_format($orphanedAreaCount) }} nama muncul di transaksi bulan {{ $areaMonthLabel }}
                        tanpa pasangan area master.</p>
                    @if ($orphanedAreaNames->isNotEmpty())
                        <div class="area-master-orphans">
                            @foreach ($orphanedAreaNames as $orphan)
                                <form action="{{ route('master.area.store') }}" method="POST"
                                    class="area-master-orphan-form">
                                    @csrf
                                    <input type="hidden" name="area_gudang_id"
                                        value="{{ $activeAreaWarehouse->id }}">
                                    <input type="hidden" name="area_periode" value="{{ $selectedAreaPeriod }}">
                                    <input type="hidden" name="nama" value="{{ $orphan->nama }}">
                                    <span>{{ $orphan->nama }} · {{ number_format((int) $orphan->request_count) }}
                                        transaksi</span>
                                    @if ($canCreateArea)
                                        <button type="submit" class="btn btn-outline"
                                            aria-label="Daftarkan {{ $orphan->nama }}">Daftarkan</button>
                                    @endif
                                </form>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            @if ($canCreateArea)
                <section class="area-master-card">
                    <h2>Tambah cepat</h2>
                    <p>Nama yang ditambahkan tersedia sebagai pilihan di form Recording.</p>
                    <form action="{{ route('master.area.store') }}" method="POST" class="area-master-quick-form">
                        @csrf
                        <input type="hidden" name="area_gudang_id" value="{{ $activeAreaWarehouse->id }}">
                        <input type="hidden" name="area_periode" value="{{ $selectedAreaPeriod }}">
                        <div class="form-group">
                            <label for="new-area-name" class="form-label">Nama area / user</label>
                            <input id="new-area-name" name="nama" class="form-control"
                                value="{{ old('nama') }}" maxlength="255" required>
                            @error('nama')
                                <span class="form-error" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </form>
                </section>
            @endif
        </aside>
    @endif
</section>
