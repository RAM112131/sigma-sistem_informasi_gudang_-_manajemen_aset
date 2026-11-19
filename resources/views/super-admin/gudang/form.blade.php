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

    <style>
        .super-dashboard {
            --dash-text-helper: 12px;
            --dash-text-body: 14px;
            --dash-text-section: 20px;
            --dash-text-metric: 24px;
            min-width: 0;
        }

        /* Header Halaman */
        .super-users-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 20px;
        }

        .super-breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: var(--dash-text-helper);
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .super-breadcrumb a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.15s;
        }

        .super-breadcrumb a:hover {
            color: var(--brand-primary);
        }

        .super-breadcrumb span:last-child {
            color: var(--text-primary);
            font-weight: 700;
        }

        .super-page-title {
            font-size: var(--dash-text-section);
            font-weight: 800;
            color: var(--text-primary);
            margin: 0;
            line-height: 1.2;
        }

        .super-page-subtitle {
            font-size: var(--dash-text-helper);
            color: var(--text-muted);
            margin: 3px 0 0;
            line-height: 1.35;
        }

        .super-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .super-guard-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            font-size: var(--dash-text-helper);
            color: var(--text-muted);
        }

        .super-guard-badge svg {
            width: 14px;
            height: 14px;
            stroke: var(--brand-primary);
            stroke-width: 2;
            fill: none;
        }

        .super-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--surface-border);
            background: var(--surface-card);
            color: var(--text-muted);
            font-size: var(--dash-text-body);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .super-back-btn:hover {
            color: var(--brand-primary);
            border-color: var(--brand-primary);
            background: rgba(20, 92, 84, 0.04);
        }

        .super-back-btn svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            stroke-width: 2.2;
            fill: none;
        }

        /* Form Layout */
        .super-user-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .super-form-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.85fr) minmax(300px, 1fr);
            align-items: start;
            gap: 20px;
        }

        .super-form-main {
            display: flex;
            flex-direction: column;
            gap: 20px;
            min-width: 0;
        }

        .super-form-aside {
            display: flex;
            flex-direction: column;
            gap: 20px;
            min-width: 0;
            position: sticky;
            top: 86px;
        }

        /* Panel Card */
        .super-panel {
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-md);
            padding: 20px;
            box-shadow: none;
        }

        .super-panel-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--surface-border);
        }

        .super-panel-header-split {
            justify-content: space-between;
        }

        .super-panel-heading-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .super-panel-icon-wrap {
            width: 38px;
            height: 38px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .super-panel-icon-wrap.icon-teal {
            background: rgba(20, 92, 84, 0.08);
            color: var(--brand-primary);
        }

        .super-panel-icon-wrap.icon-amber {
            background: #FEF3E8;
            color: var(--brand-accent);
        }

        .super-panel-icon-wrap svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .super-panel-title {
            font-size: var(--dash-text-body);
            font-weight: 800;
            color: var(--text-primary);
            margin: 0;
            line-height: 1.25;
        }

        .super-panel-subtitle {
            font-size: var(--dash-text-helper);
            color: var(--text-muted);
            margin: 2px 0 0;
        }

        /* Form Inputs */
        .super-fields-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .super-field-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .super-field-label {
            font-size: var(--dash-text-body);
            font-weight: 700;
            color: var(--text-primary);
        }

        .super-required {
            color: var(--status-critical);
            font-weight: 700;
        }

        .super-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-sm);
            background: var(--surface-card);
            font-size: var(--dash-text-body);
            color: var(--text-primary);
            box-sizing: border-box;
            transition: all 0.15s ease;
        }

        .super-input:focus {
            outline: none;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(20, 92, 84, 0.15);
        }

        .super-input.is-invalid {
            border-color: var(--status-critical);
            background: var(--status-critical-bg);
        }

        .super-field-hint {
            font-size: var(--dash-text-helper);
            color: var(--text-subtle);
        }

        .super-field-error {
            font-size: var(--dash-text-helper);
            color: var(--status-critical);
            font-weight: 600;
        }

        /* Code Input with Prefix & Tag */
        .super-code-input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .super-code-prefix {
            position: absolute;
            left: 12px;
            font-family: monospace;
            font-weight: 700;
            color: var(--text-subtle);
            font-size: 14px;
            pointer-events: none;
        }

        .super-code-input {
            padding-left: 28px;
            padding-right: 76px;
            font-family: monospace;
            font-weight: 700;
            letter-spacing: 0.04em;
        }

        .super-code-tag {
            position: absolute;
            right: 8px;
            padding: 2px 6px;
            border-radius: 4px;
            background: var(--surface-base);
            color: var(--text-muted);
            font-size: 0.625rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            border: 1px solid var(--surface-border);
            pointer-events: none;
        }

        /* Toggle Switch */
        .super-status-toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 16px;
            background: var(--surface-base);
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-sm);
        }

        .super-toggle-title {
            font-size: var(--dash-text-body);
            font-weight: 700;
            color: var(--text-primary);
        }

        .super-toggle-subtitle {
            margin: 2px 0 0;
            font-size: var(--dash-text-helper);
            color: var(--text-muted);
        }

        .super-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
        }

        .super-toggle-track {
            width: 44px;
            height: 24px;
            background: var(--surface-border);
            border-radius: 999px;
            padding: 2px;
            display: flex;
            align-items: center;
            transition: background-color 0.2s ease;
        }

        .super-toggle-btn.is-active .super-toggle-track {
            background: var(--brand-primary);
        }

        .super-toggle-thumb {
            width: 20px;
            height: 20px;
            background: #ffffff;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s ease;
        }

        .super-toggle-btn.is-active .super-toggle-thumb {
            transform: translateX(20px);
        }

        .super-toggle-label {
            font-size: var(--dash-text-body);
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Choices Grid */
        .super-choices-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }

        .super-choice-card {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .super-choice-card:hover {
            border-color: var(--brand-primary);
            background: rgba(20, 92, 84, 0.02);
        }

        .super-choice-card.is-selected {
            border-color: var(--brand-primary);
            background: rgba(20, 92, 84, 0.04);
            box-shadow: 0 0 0 1px var(--brand-primary);
        }

        .super-choice-checkbox {
            margin-top: 3px;
            width: 16px;
            height: 16px;
            accent-color: var(--brand-primary);
            cursor: pointer;
            flex-shrink: 0;
        }

        .super-choice-body {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
            flex: 1;
        }

        .super-choice-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }

        .super-warehouse-name {
            font-size: var(--dash-text-body);
            font-weight: 800;
            color: var(--text-primary);
        }

        .super-choice-desc {
            margin: 0;
            font-size: var(--dash-text-helper);
            color: var(--text-muted);
            line-height: 1.4;
        }

        .super-wh-feature-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 4px;
        }

        /* Tags & Badges */
        .super-tag {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        .tag-navy {
            background: #E2DFD9;
            color: var(--text-primary);
        }

        .tag-teal {
            background: rgba(20, 92, 84, 0.1);
            color: var(--brand-primary);
        }

        .tag-orange {
            background: #FEF3E8;
            color: #C97A2B;
        }

        .super-role-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .role-super {
            background: rgba(20, 92, 84, 0.1);
            color: var(--brand-primary);
        }

        .role-store {
            background: #FEF3E8;
            color: #C97A2B;
        }

        .super-perm-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 6px;
            border-radius: 4px;
            background: var(--surface-base);
            color: var(--text-muted);
            font-size: 0.6875rem;
            font-weight: 600;
        }

        .super-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.6875rem;
            font-weight: 600;
        }

        .status-active {
            background: var(--status-success-bg);
            color: var(--status-success);
        }

        .status-active::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--status-success);
        }

        .status-inactive {
            background: #F0EDE8;
            color: var(--text-subtle);
        }

        .status-inactive::before {
            content: "";
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--text-subtle);
        }

        /* Info Banners */
        .super-panel-info-banner {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            font-size: var(--dash-text-helper);
            margin-top: 14px;
        }

        .super-panel-info-banner.banner-warning {
            background: var(--status-warning-bg);
            border: 1px solid rgba(184, 121, 26, 0.2);
            color: var(--status-warning);
        }

        .super-panel-info-banner svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        /* Live Preview Sidebar */
        .super-summary-panel {
            background: var(--surface-card);
            border-top: 3px solid var(--brand-primary);
        }

        .super-summary-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding-bottom: 12px;
            margin-bottom: 14px;
            border-bottom: 1px solid var(--surface-border);
        }

        .super-summary-title-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: var(--dash-text-body);
            font-weight: 700;
            color: var(--text-primary);
        }

        .super-summary-title-wrap svg {
            width: 16px;
            height: 16px;
            stroke: var(--brand-primary);
            stroke-width: 2;
            fill: none;
        }

        .super-preview-stack {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 12px;
            background: var(--surface-base);
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-sm);
            margin-bottom: 12px;
        }

        .super-preview-row {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .super-preview-label {
            font-size: 0.6875rem;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.04em;
        }

        .super-preview-code-val {
            font-family: monospace;
            font-size: 15px;
            color: var(--text-primary);
            font-weight: 800;
        }

        .super-preview-name-val {
            font-size: 14px;
            font-weight: 800;
            color: var(--text-primary);
        }

        .super-preview-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding-top: 8px;
            border-top: 1px solid var(--surface-border);
        }

        .super-preview-staff-val {
            font-size: var(--dash-text-body);
            font-weight: 700;
            color: var(--text-primary);
            display: block;
        }

        .super-preview-hint {
            font-size: var(--dash-text-helper);
            color: var(--text-subtle);
            margin: 0;
            font-style: italic;
        }

        /* Impact List */
        .super-impact-list {
            margin: 0 0 16px 0;
            padding-left: 20px;
            font-size: var(--dash-text-helper);
            color: var(--text-muted);
            display: flex;
            flex-direction: column;
            gap: 8px;
            line-height: 1.45;
        }

        .super-impact-list strong {
            color: var(--text-primary);
        }

        .super-guide-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 10px;
            border-top: 1px solid var(--surface-border);
            font-size: 0.6875rem;
            color: var(--text-subtle);
            font-weight: 700;
        }

        .super-guide-footer code {
            padding: 1px 6px;
            border-radius: 4px;
            background: var(--surface-base);
            color: var(--text-primary);
            border: 1px solid var(--surface-border);
        }

        /* Footer Actions */
        .super-form-actions-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 20px;
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-md);
        }

        .super-actions-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .super-actions-hint {
            font-size: var(--dash-text-helper);
            color: var(--text-muted);
        }

        .super-actions-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .super-btn-cancel {
            padding: 8px 16px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-sm);
            background: var(--surface-card);
            color: var(--text-muted);
            font-size: var(--dash-text-body);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .super-btn-cancel:hover {
            background: var(--surface-base);
            color: var(--text-primary);
        }

        .super-btn-save {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 20px;
            background: var(--brand-primary);
            color: #ffffff;
            border: none;
            border-radius: var(--radius-sm);
            font-size: var(--dash-text-body);
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            transition: all 0.15s ease;
        }

        .super-btn-save:hover {
            background: var(--brand-primary-d);
        }

        .super-btn-save svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            stroke-width: 2.2;
            fill: none;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1080px) {
            .super-form-layout {
                grid-template-columns: 1fr;
            }

            .super-form-aside {
                position: static;
            }

            .super-form-actions-card {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .super-actions-right {
                justify-content: flex-end;
            }
        }

        @media (max-width: 680px) {
            .super-users-header {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .super-fields-grid {
                grid-template-columns: 1fr;
            }

            .super-choices-grid {
                grid-template-columns: 1fr;
            }

            .super-actions-right {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .super-btn-save {
                justify-content: center;
            }

            .super-preview-meta-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection
