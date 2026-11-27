@extends('layouts.app')

@section('title', 'Edit Barang — Stok Barang Fisik')

@push('styles')
    @vite('resources/css/pages/unit-bisnis/stok/edit.css')
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
