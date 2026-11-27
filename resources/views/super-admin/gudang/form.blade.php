@extends('layouts.app')

@section('title', ($gudang ?? null ? 'Edit' : 'Tambah') . ' Lokasi Gudang — ' . config('app.name', 'SIGMA'))

@section('content')
    @php
        $currentGudang = $gudang ?? null;
        $warehouseType = old('jenis', $currentGudang?->jenis ?? 'gudang_store');
        $isActive = (bool) old('is_aktif', $currentGudang?->is_aktif ?? true);
    @endphp

    <div class="super-dashboard" x-data="{
        warehouseCode: @js(old('kode', $gudang?->kode ?? '')),
        warehouseName: @js(old('nama', $gudang?->nama ?? '')),
        warehouseType: @js($warehouseType),
        isActive: @js($isActive)
    }">
        {{-- Header Halaman Form --}}
        <header class="super-users-header">
            <div>
                <nav class="super-breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('admin.dashboard') }}">Super Admin</a>
                    <span aria-hidden="true">›</span>
                    <a href="{{ route('admin.gudang.index') }}">Master Lokasi Gudang</a>
                    <span aria-hidden="true">›</span>
                    <span>{{ $gudang ? 'Edit Lokasi Gudang' : 'Registrasi Fasilitas Gudang' }}</span>
                </nav>
                <h1 class="super-page-title">{{ $gudang ? 'Edit Lokasi Gudang' : 'Registrasi Fasilitas Gudang' }}</h1>
                <p class="super-page-subtitle">Lengkapi identitas, kode unik, dan alur konfigurasi operasional fasilitas
                    gudang.</p>
            </div>
            <div class="super-header-actions">
                <span class="super-guard-badge">
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                        <polyline points="9 22 9 12 15 12 15 22" />
                    </svg>
                    <span>Entitas: <strong>Gudang</strong></span>
                </span>
                <a class="super-back-btn" href="{{ route('admin.gudang.index') }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                    <span>Kembali ke Daftar</span>
                </a>
            </div>
        </header>

        <form id="warehouseForm"
            action="{{ $gudang ? route('admin.gudang.update', $gudang) : route('admin.gudang.store') }}" method="POST"
            class="super-user-form">
            @csrf
            @if ($gudang)
                @method('PUT')
            @endif

            <div class="super-form-layout">
                {{-- Kolom Utama (Input Form) --}}
                <div class="super-form-main">

                    {{-- Section 1: Identitas Fasilitas --}}
                    <section class="super-panel" aria-labelledby="warehouse-identity-title">
                        <header class="super-panel-header">
                            <div class="super-panel-icon-wrap icon-teal">
                                <svg aria-hidden="true" viewBox="0 0 24 24">
                                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                    <polyline points="9 22 9 12 15 12 15 22" />
                                </svg>
                            </div>
                            <div>
                                <h2 id="warehouse-identity-title" class="super-panel-title">Identitas Fasilitas</h2>
                                <p class="super-panel-subtitle">Informasi dasar yang digunakan pada dokumen dan identifikasi
                                    gudang.</p>
                            </div>
                        </header>

                        <div class="super-fields-grid">
                            {{-- Kode Fasilitas --}}
                            <div class="super-field-group">
                                <label for="warehouse-code" class="super-field-label">
                                    Kode Fasilitas <span class="super-required">*</span>
                                </label>
                                <div class="super-code-input-wrap">
                                    <span class="super-code-prefix">#</span>
                                    <input id="warehouse-code" name="kode" type="text" maxlength="50"
                                        class="super-input super-code-input @error('kode') is-invalid @enderror"
                                        placeholder="Contoh: WH-BKS-03" value="{{ old('kode', $gudang?->kode ?? '') }}"
                                        x-model="warehouseCode"
                                        x-on:input="warehouseCode = $event.target.value.toUpperCase(); $event.target.value = warehouseCode"
                                        autocomplete="off" required aria-describedby="warehouse-code-help">
                                    <span class="super-code-tag">KODE UNIK</span>
                                </div>
                                <small id="warehouse-code-help" class="super-field-hint">Kode unik fasilitas; digunakan pada
                                    identifikasi dan dokumen operasional.</small>
                                @error('kode')
                                    <span class="super-field-error">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Nama Fasilitas Gudang --}}
                            <div class="super-field-group">
                                <label for="warehouse-name" class="super-field-label">
                                    Nama Fasilitas Gudang <span class="super-required">*</span>
                                </label>
                                <input id="warehouse-name" name="nama" type="text" maxlength="255"
                                    class="super-input @error('nama') is-invalid @enderror"
                                    placeholder="Contoh: Hub Logistik Cibinong"
                                    value="{{ old('nama', $gudang?->nama ?? '') }}" x-model="warehouseName"
                                    autocomplete="organization" required>
                                <small class="super-field-hint">Nama resmi fasilitas yang digunakan pada surat jalan dan
                                    dokumen gudang.</small>
                                @error('nama')
                                    <span class="super-field-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Status Aktivasi Fasilitas --}}
                        <div class="super-status-toggle-row">
                            <div>
                                <div class="super-toggle-title">Status Aktivasi Fasilitas</div>
                                <p class="super-toggle-subtitle">Gudang nonaktif tidak dipilih untuk operasional baru.</p>
                            </div>
                            <div>
                                <input type="hidden" name="is_aktif" value="0">
                                <input type="checkbox" name="is_aktif" value="1" @checked($isActive)
                                    x-model="isActive" style="display: none;" aria-label="Aktifkan gudang">
                                <button type="button" class="super-toggle-btn" :class="{ 'is-active': isActive }"
                                    @click="isActive = !isActive" aria-label="Toggle status aktif gudang">
                                    <span class="super-toggle-track">
                                        <span class="super-toggle-thumb"></span>
                                    </span>
                                    <span class="super-toggle-label"
                                        x-text="isActive ? 'Status Gudang Aktif' : 'Status Gudang Nonaktif'"></span>
                                </button>
                            </div>
                        </div>
                        @error('is_aktif')
                            <span class="super-field-error">{{ $message }}</span>
                        @enderror
                    </section>

                    {{-- Section 2: Jenis Operasional Gudang --}}
                    <section class="super-panel" aria-labelledby="warehouse-type-title">
                        <header class="super-panel-header super-panel-header-split">
                            <div class="super-panel-heading-left">
                                <div class="super-panel-icon-wrap icon-amber">
                                    <svg aria-hidden="true" viewBox="0 0 24 24">
                                        <rect x="3" y="3" width="7" height="7" />
                                        <rect x="14" y="3" width="7" height="7" />
                                        <rect x="14" y="14" width="7" height="7" />
                                        <rect x="3" y="14" width="7" height="7" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 id="warehouse-type-title" class="super-panel-title">Jenis Operasional Gudang <span
                                            class="super-required">*</span></h2>
                                    <p class="super-panel-subtitle">Pilih tipe yang menentukan alur operasional fasilitas
                                        ini.</p>
                                </div>
                            </div>
                            <span class="super-tag tag-navy">KONFIGURASI OPERASIONAL</span>
                        </header>

                        <div class="super-choices-grid">
                            {{-- Option: Gudang Umum --}}
                            <label class="super-choice-card" :class="{ 'is-selected': warehouseType === 'gudang_umum' }">
                                <input type="radio" name="jenis" value="gudang_umum" x-model="warehouseType"
                                    class="super-choice-checkbox">
                                <div class="super-choice-body">
                                    <div class="super-choice-header">
                                        <span class="super-tag tag-teal">OPERASIONAL UNIT BISNIS</span>
                                    </div>
                                    <strong class="super-warehouse-name">Gudang Umum</strong>
                                    <p class="super-choice-desc">Operasional unit bisnis harian, penerimaan dan pengeluaran
                                        barang, serta stok opname.</p>
                                    <div class="super-wh-feature-tags">
                                        <span class="super-perm-badge">Stok opname</span>
                                        <span class="super-perm-badge">Penerimaan barang</span>
                                        <span class="super-perm-badge">Pengeluaran &amp; retur</span>
                                    </div>
                                </div>
                            </label>

                            {{-- Option: Gudang Store --}}
                            <label class="super-choice-card" :class="{ 'is-selected': warehouseType === 'gudang_store' }">
                                <input type="radio" name="jenis" value="gudang_store" x-model="warehouseType"
                                    class="super-choice-checkbox">
                                <div class="super-choice-body">
                                    <div class="super-choice-header">
                                        <span class="super-tag tag-orange">CENTRAL CATALOG &amp; HOLDING</span>
                                    </div>
                                    <strong class="super-warehouse-name">Gudang Store</strong>
                                    <p class="super-choice-desc">Katalog master barang, stok pusat, penerimaan supplier,
                                        dan distribusi ke unit cabang.</p>
                                    <div class="super-wh-feature-tags">
                                        <span class="super-perm-badge">Master katalog</span>
                                        <span class="super-perm-badge">Distribusi unit</span>
                                        <span class="super-perm-badge">Transfer gudang</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                        @error('jenis')
                            <span class="super-field-error">{{ $message }}</span>
                        @enderror

                        {{-- Callout Banner --}}
                        <div class="super-panel-info-banner banner-warning">
                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <line x1="12" y1="16" x2="12.01" y2="16" />
                            </svg>
                            <div>
                                <strong>Pastikan tipe operasional sudah sesuai.</strong>
                                <span>Perubahan tipe dapat berdampak pada akses staf dan alur transaksi yang tersedia untuk
                                    gudang.</span>
                            </div>
                        </div>
                    </section>

                </div>

                {{-- Kolom Sidebar (Preview & Panduan) --}}
                <aside class="super-form-aside">
                    {{-- Preview Fasilitas --}}
                    <section class="super-panel super-summary-panel">
                        <div class="super-summary-header">
                            <div class="super-summary-title-wrap">
                                <svg aria-hidden="true" viewBox="0 0 24 24">
                                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <span>PREVIEW FASILITAS</span>
                            </div>
                            <span class="super-status" :class="isActive ? 'status-active' : 'status-inactive'">
                                <span x-text="isActive ? 'SIAP AKTIF' : 'NONAKTIF'"></span>
                            </span>
                        </div>

                        <div class="super-preview-stack">
                            <div class="super-preview-row">
                                <span class="super-preview-label">KODE FASILITAS</span>
                                <strong class="super-preview-code-val" x-text="warehouseCode || 'WH-___-00'"></strong>
                            </div>

                            <div class="super-preview-row">
                                <span class="super-preview-label">NAMA FASILITAS</span>
                                <strong class="super-preview-name-val"
                                    x-text="warehouseName || 'Nama Gudang Belum Diisi'"></strong>
                            </div>

                            <div class="super-preview-meta-grid">
                                <div>
                                    <span class="super-preview-label">ARSITEKTUR</span>
                                    <span class="super-role-badge"
                                        :class="warehouseType === 'gudang_store' ? 'role-store' : 'role-super'"
                                        x-text="warehouseType === 'gudang_store' ? 'GUDANG STORE' : 'GUDANG UMUM'"></span>
                                </div>
                                <div>
                                    <span class="super-preview-label">STAF TERHUBUNG</span>
                                    <strong
                                        class="super-preview-staff-val">{{ number_format($gudang?->users_count ?? 0) }}
                                        Personel</strong>
                                </div>
                            </div>
                        </div>

                        <p class="super-preview-hint">Penugasan staf dapat dikelola setelah fasilitas tersimpan.</p>
                    </section>

                    {{-- Panduan Kebijakan & Dampak --}}
                    <section class="super-panel">
                        <div class="super-summary-header"
                            style="border-bottom: none; margin-bottom: 8px; padding-bottom: 0;">
                            <div class="super-summary-title-wrap">
                                <svg aria-hidden="true" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M12 16v-4" />
                                    <path d="M12 8h.01" />
                                </svg>
                                <span>Dampak Jenis Operasional</span>
                            </div>
                        </div>

                        <ol class="super-impact-list">
                            <li><strong>Menu staf:</strong> tipe gudang menentukan fitur operasional yang relevan bagi staf.
                            </li>
                            <li><strong>Hak akses:</strong> penugasan gudang pengguna diatur terpisah melalui menu Pengguna.
                            </li>
                            <li><strong>Dokumen:</strong> kode fasilitas digunakan untuk membedakan lokasi pada administrasi
                                operasional.</li>
                        </ol>

                        <div class="super-guide-footer">
                            <span>PROTOKOL KEAMANAN SIGMA</span>
                            <code>SEC-LEVEL-0</code>
                        </div>
                    </section>
                </aside>
            </div>

            {{-- Footer Aksi Form --}}
            <footer class="super-form-actions-card">
                <div class="super-actions-left">
                    <a class="super-btn-cancel" href="{{ route('admin.gudang.index') }}">Batal</a>
                    <span class="super-actions-hint">
                        Periksa kembali konfigurasi gudang. Data fasilitas akan digunakan oleh modul operasional SIGMA.
                    </span>
                </div>
                <div class="super-actions-right">
                    <button class="super-btn-save" type="submit">
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                            <polyline points="17 21 17 13 7 13 7 21" />
                            <polyline points="7 3 7 8 15 8" />
                        </svg>
                        <span>{{ $gudang ? 'Simpan Perubahan' : 'Simpan Fasilitas Gudang' }}</span>
                    </button>
                </div>
            </footer>
        </form>
    </div>

    @push('styles')
    @vite('resources/css/pages/super-admin/gudang/form.css')
@endpush
@endsection
