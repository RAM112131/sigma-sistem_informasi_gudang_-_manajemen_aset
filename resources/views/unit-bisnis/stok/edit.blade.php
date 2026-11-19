@extends('layouts.app')

@section('title', 'Edit Barang — Stok Barang Fisik')

@push('styles')
    <style>
        .physical-stock-edit-page {
            display: flex;
            max-width: 900px;
            flex-direction: column;
            gap: 16px;
            color: var(--text-primary);
        }

        .physical-stock-edit-header h1 {
            margin: 0;
            color: var(--text-primary);
            font-size: 24px;
            font-weight: 850;
            line-height: 1.2;
        }

        .physical-stock-edit-header p {
            margin: 5px 0 0;
            color: var(--text-muted);
            font-size: .875rem;
            line-height: 1.5;
        }

        .physical-stock-edit-card {
            padding: 20px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
        }

        .physical-stock-edit-notice {
            margin-bottom: 18px;
            padding: 12px 14px;
            border: 1px solid rgba(184, 121, 26, .25);
            border-radius: var(--radius);
            background: rgba(184, 121, 26, .08);
            color: var(--text-primary);
            font-size: .82rem;
            line-height: 1.5;
        }

        .physical-stock-edit-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .physical-stock-edit-field {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 6px;
        }

        .physical-stock-edit-field.is-full {
            grid-column: 1 / -1;
        }

        .physical-stock-edit-field label {
            color: var(--text-primary);
            font-size: .8rem;
            font-weight: 700;
        }

        .physical-stock-edit-field input,
        .physical-stock-edit-field select,
        .physical-stock-edit-field textarea {
            width: 100%;
            min-height: 44px;
            padding: 9px 11px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
            color: var(--text-primary);
            font: inherit;
            font-size: .84rem;
        }

        .physical-stock-edit-field textarea {
            min-height: 100px;
            resize: vertical;
        }

        .physical-stock-edit-field input:focus-visible,
        .physical-stock-edit-field select:focus-visible,
        .physical-stock-edit-field textarea:focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
        }

        .physical-stock-edit-field .form-error {
            margin: 0;
            color: var(--status-critical);
            font-size: .75rem;
        }

        .physical-stock-edit-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 20px;
        }

        .physical-stock-edit-button {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-base);
            color: var(--text-primary);
            font-size: .82rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .physical-stock-edit-button.is-primary {
            border-color: var(--brand-primary);
            background: var(--brand-primary);
            color: #fff;
        }

        .physical-stock-edit-button:focus-visible {
            outline: 2px solid var(--brand-primary);
            outline-offset: 2px;
        }

        @media (max-width: 640px) {
            .physical-stock-edit-header h1 {
                font-size: 1.4rem;
            }

            .physical-stock-edit-card {
                padding: 14px;
            }

            .physical-stock-edit-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .physical-stock-edit-field.is-full {
                grid-column: auto;
            }

            .physical-stock-edit-actions {
                flex-direction: column-reverse;
            }

            .physical-stock-edit-button {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')
    <main class="physical-stock-edit-page">
        <header class="physical-stock-edit-header">
            <div class="physical-stock-breadcrumb">
                <a
                    href="{{ route('unit-bisnis.stok.index', ['tab' => 'stok_saat_ini', 'gudang_id' => $activeGudang->id]) }}">Stok
                    Barang Fisik</a>
                <span aria-hidden="true">/</span>
                <strong>Edit Barang</strong>
            </div>
            <h1>Edit Data Barang</h1>
            <p>{{ $barang->kode }} · {{ $barang->nama }} — {{ $activeGudang->nama }}</p>
        </header>

        <section class="physical-stock-edit-card">
            <div class="physical-stock-edit-notice" role="note">
                Perubahan di halaman ini hanya memperbarui informasi barang. Saldo fisik tidak diedit langsung; gunakan
                fitur stok opname untuk menyesuaikan saldo.
            </div>

            <form
                action="{{ route('unit-bisnis.stok.update', ['barang' => $barang->id, 'gudang_id' => $activeGudang->id]) }}"
                method="POST">
                @csrf
                @method('PUT')

                <div class="physical-stock-edit-grid">
                    <div class="physical-stock-edit-field">
                        <label for="stock-item-code">Kode Barang</label>
                        <input id="stock-item-code" name="kode" value="{{ old('kode', $barang->kode) }}" maxlength="50"
                            required>
                        @error('kode')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="physical-stock-edit-field">
                        <label for="stock-item-name">Nama Barang</label>
                        <input id="stock-item-name" name="nama" value="{{ old('nama', $barang->nama) }}" maxlength="255"
                            required>
                        @error('nama')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="physical-stock-edit-field">
                        <label for="stock-item-category">Kategori</label>
                        <select id="stock-item-category" name="kategori_id">
                            <option value="">Tanpa Kategori</option>
                            @foreach ($kategoriList as $kategori)
                                <option value="{{ $kategori->id }}" @selected((string) old('kategori_id', $barang->kategori_id) === (string) $kategori->id)>{{ $kategori->nama }}
                                </option>
                            @endforeach
                        </select>
                        @error('kategori_id')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="physical-stock-edit-field">
                        <label for="stock-item-unit">Satuan</label>
                        <select id="stock-item-unit" name="satuan_id">
                            <option value="">Tanpa Satuan</option>
                            @foreach ($satuanList as $satuan)
                                <option value="{{ $satuan->id }}" @selected((string) old('satuan_id', $barang->satuan_id) === (string) $satuan->id)>{{ $satuan->nama }}
                                </option>
                            @endforeach
                        </select>
                        @error('satuan_id')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="physical-stock-edit-field">
                        <label for="stock-item-minimum">Stok Minimum</label>
                        <input id="stock-item-minimum" type="number" name="minimum_stok" min="0" step="1"
                            value="{{ old('minimum_stok', $barang->minimum_stok) }}" required>
                        @error('minimum_stok')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="physical-stock-edit-field">
                        <label for="stock-item-price">HPP Berlaku (Rp)</label>
                        <input id="stock-item-price" type="number" name="harga_satuan" min="0" step="0.01"
                            value="{{ old('harga_satuan', $barang->harga_satuan) }}">
                        @error('harga_satuan')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="physical-stock-edit-field is-full">
                        <label for="stock-item-description">Deskripsi</label>
                        <textarea id="stock-item-description" name="deskripsi">{{ old('deskripsi', $barang->deskripsi) }}</textarea>
                        @error('deskripsi')
                            <p class="form-error" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="physical-stock-edit-actions">
                    <a class="physical-stock-edit-button"
                        href="{{ route('unit-bisnis.stok.index', ['tab' => 'stok_saat_ini', 'gudang_id' => $activeGudang->id]) }}">Batal</a>
                    <button class="physical-stock-edit-button is-primary" type="submit">Simpan Perubahan</button>
                </div>
            </form>
        </section>
    </main>
@endsection
