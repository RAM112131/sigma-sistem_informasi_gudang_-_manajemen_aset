@extends('layouts.app')

@section('title', ($user ? 'Edit Pengguna' : 'Tambah Pengguna') . ' — ' . config('app.name', 'SIGMA'))

@section('content')
    <div class="super-dashboard" x-data="adminUserEditor({{ \Illuminate\Support\Js::from($formState) }})">
        {{-- Header Halaman Form --}}
        <header class="super-users-header">
            <div>
                <nav class="super-breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('admin.dashboard') }}">Super Admin</a>
                    <span aria-hidden="true">›</span>
                    <a href="{{ route('admin.users.index') }}">Manajemen Pengguna</a>
                    <span aria-hidden="true">›</span>
                    <span>{{ $user ? 'Edit Pengguna' : 'Tambah Pengguna' }}</span>
                </nav>
                <h1 class="super-page-title">{{ $user ? 'Edit Pengguna' : 'Tambah Pengguna Baru' }}</h1>
                <p class="super-page-subtitle">Atur identitas login, role otoritas Spatie, status akun, dan penugasan akses
                    gudang secara terpusat.</p>
            </div>
            <div class="super-header-actions">
                <a class="super-back-btn" href="{{ route('admin.users.index') }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                    <span>Kembali ke Daftar</span>
                </a>
            </div>
        </header>

        <form class="super-user-form" action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}"
            method="POST">
            @csrf
            @if ($user)
                @method('PUT')
            @endif

            <div class="super-form-layout">
                {{-- Kolom Kiri: Form Input Utama --}}
                <div class="super-form-main">
                    {{-- Section 1: Informasi Akun --}}
                    <section class="super-panel" aria-labelledby="user-account-title">
                        <header class="super-panel-header">
                            <div class="super-panel-icon-wrap">
                                <svg aria-hidden="true" viewBox="0 0 24 24">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </div>
                            <div>
                                <h2 id="user-account-title" class="super-panel-title">1. Informasi Akun</h2>
                                <p class="super-panel-subtitle">Lengkapi identitas login dan status aktif pengguna pada
                                    sistem.</p>
                            </div>
                        </header>

                        <div class="super-fields-grid">
                            {{-- Nama Lengkap --}}
                            <div class="super-field-group">
                                <label for="user-name" class="super-field-label">
                                    Nama Lengkap <span class="super-required">*</span>
                                </label>
                                <input id="user-name" class="super-input @error('name') is-invalid @enderror" type="text"
                                    name="name" value="{{ old('name', $user?->name) }}" x-model="form.name"
                                    autocomplete="name" maxlength="255" placeholder="Masukkan nama lengkap..." required>
                                @error('name')
                                    <span class="super-field-error">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Username --}}
                            <div class="super-field-group">
                                <label for="user-username" class="super-field-label">
                                    Username <span class="super-required">*</span>
                                </label>
                                <input id="user-username" class="super-input @error('username') is-invalid @enderror"
                                    type="text" name="username" value="{{ old('username', $user?->username) }}"
                                    x-model="form.username" autocomplete="username" maxlength="50"
                                    placeholder="misal: operator.store" required>
                                <span class="super-field-hint">Maksimal 50 karakter alfanumerik.</span>
                                @error('username')
                                    <span class="super-field-error">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Kata Sandi --}}
                            <div class="super-field-group">
                                <label for="user-password" class="super-field-label">
                                    Kata Sandi
                                    @if ($user)
                                        <span class="super-optional">(opsional jika tidak diubah)</span>
                                    @else
                                        <span class="super-required">*</span>
                                    @endif
                                </label>
                                <div class="super-password-input-wrap">
                                    <input id="user-password" class="super-input @error('password') is-invalid @enderror"
                                        :type="showPassword ? 'text' : 'password'" name="password"
                                        autocomplete="new-password" {{ $user ? '' : 'required' }} minlength="8"
                                        placeholder="{{ $user ? 'Kosongkan jika tidak ingin mengubah' : 'Minimal 8 karakter...' }}"
                                        x-model="password">
                                    <button type="button" class="super-password-toggle"
                                        aria-label="Tampilkan atau sembunyikan kata sandi"
                                        @click="showPassword = !showPassword">
                                        <svg x-show="!showPassword" aria-hidden="true" viewBox="0 0 24 24">
                                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                        <svg x-show="showPassword" x-cloak aria-hidden="true" viewBox="0 0 24 24">
                                            <path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8" />
                                            <path
                                                d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a15.4 15.4 0 0 1-3.1 3.8M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7a10 10 0 0 0 3.2-.5" />
                                        </svg>
                                    </button>
                                </div>
                                <span class="super-field-hint">Minimal 8 karakter kombinasi aman.</span>
                                @error('password')
                                    <span class="super-field-error">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Konfirmasi Sandi --}}
                            <div class="super-field-group">
                                <label for="user-password-confirmation" class="super-field-label">
                                    Konfirmasi Kata Sandi {{ $user ? '' : '*' }}
                                </label>
                                <div class="super-password-input-wrap">
                                    <input id="user-password-confirmation" class="super-input"
                                        :type="showPasswordConfirmation ? 'text' : 'password'" name="password_confirmation"
                                        autocomplete="new-password" {{ $user ? '' : 'required' }} minlength="8"
                                        placeholder="Ulangi kata sandi..." x-model="passwordConfirmation">
                                    <button type="button" class="super-password-toggle"
                                        aria-label="Tampilkan atau sembunyikan konfirmasi kata sandi"
                                        @click="showPasswordConfirmation = !showPasswordConfirmation">
                                        <svg x-show="!showPasswordConfirmation" aria-hidden="true" viewBox="0 0 24 24">
                                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                        <svg x-show="showPasswordConfirmation" x-cloak aria-hidden="true"
                                            viewBox="0 0 24 24">
                                            <path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8" />
                                            <path
                                                d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a15.4 15.4 0 0 1-3.1 3.8M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7a10 10 0 0 0 3.2-.5" />
                                        </svg>
                                    </button>
                                </div>
                                <span class="super-field-match"
                                    x-show="password && passwordConfirmation && password === passwordConfirmation" x-cloak>
                                    <svg aria-hidden="true" viewBox="0 0 24 24">
                                        <path d="m5 12 4 4L19 6" />
                                    </svg> Sandi cocok
                                </span>
                                <span class="super-field-error"
                                    x-show="password && passwordConfirmation && password !== passwordConfirmation" x-cloak>
                                    Sandi belum cocok.
                                </span>
                            </div>
                        </div>

                        {{-- Status Akun Aktif Toggle --}}
                        <div class="super-status-toggle-row">
                            <div>
                                <strong class="super-toggle-title">Status Akun Pengguna</strong>
                                <p class="super-toggle-subtitle">Akun yang berstatus aktif dapat login dan bertransaksi
                                    sesuai izin dan gudang yang diberikan.</p>
                                @error('is_aktif')
                                    <span class="super-field-error">{{ $message }}</span>
                                @enderror
                            </div>
                            <input type="hidden" name="is_aktif" value="{{ $isActive ? '1' : '0' }}"
                                :value="form.isActive ? '1' : '0'">
                            <button type="button" class="super-toggle-btn" :class="form.isActive ? 'is-active' : ''"
                                role="switch" :aria-checked="form.isActive.toString()"
                                @click="form.isActive = !form.isActive">
                                <span class="super-toggle-track">
                                    <span class="super-toggle-thumb"></span>
                                </span>
                                <span class="super-toggle-label"
                                    x-text="form.isActive ? 'Aktif' : 'Nonaktif'">{{ $isActive ? 'Aktif' : 'Nonaktif' }}</span>
                            </button>
                        </div>
                    </section>

                    {{-- Section 2: Role & Hak Akses (Multi-Role Spatie) --}}
                    <section class="super-panel" aria-labelledby="user-roles-title">
                        <header class="super-panel-header super-panel-header-split">
                            <div class="super-panel-heading-left">
                                <div class="super-panel-icon-wrap icon-amber">
                                    <svg aria-hidden="true" viewBox="0 0 24 24">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                        <path d="m9 12 2 2 4-4" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 id="user-roles-title" class="super-panel-title">2. Role &amp; Hak Akses Otoritas
                                    </h2>
                                    <p class="super-panel-subtitle">Pilih satu atau beberapa role Spatie untuk menentukan
                                        batasan izin pengguna di sistem.</p>
                                </div>
                            </div>
                            <span class="super-tag tag-navy">MULTI-ROLE</span>
                        </header>

                        <div class="super-choices-grid">
                            @foreach ($roles as $role)
                                @php
                                    $roleClass = match ($role->name) {
                                        'Super Admin', 'super_admin' => 'role-super',
                                        'Admin Gudang Store', 'admin_gudang_store' => 'role-store',
                                        'Admin Gudang Umum', 'admin_gudang_umum' => 'role-admin',
                                        'Recording Gudang Umum', 'recording_gudang_umum' => 'role-recording',
                                        default => 'role-custom',
                                    };
                                @endphp
                                <label class="super-choice-card"
                                    :class="form.roles.includes(@js($role->name)) ? 'is-selected' : ''">
                                    <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                                        x-model="form.roles" @checked(in_array($role->name, $selectedRoles, true)) class="super-choice-checkbox">
                                    <div class="super-choice-body">
                                        <div class="super-choice-header">
                                            <span class="super-role-badge {{ $roleClass }}">{{ $role->name }}</span>
                                            <span class="super-role-guard">{{ $role->guard_name }}</span>
                                        </div>
                                        <p class="super-choice-desc">
                                            {{ $role->description ?: 'Hak akses mengikuti izin yang terhubung pada role ini.' }}
                                        </p>
                                        <div class="super-choice-meta">
                                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                            </svg>
                                            <span>{{ $role->permissions->count() }} izin terhubung</span>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('roles')
                            <span class="super-field-error"
                                style="margin-top: 10px; display:block;">{{ $message }}</span>
                        @enderror
                        @error('roles.*')
                            <span class="super-field-error"
                                style="margin-top: 10px; display:block;">{{ $message }}</span>
                        @enderror
                    </section>

                    {{-- Section 3: Penugasan Gudang --}}
                    <section class="super-panel" aria-labelledby="user-warehouses-title">
                        <header class="super-panel-header">
                            <div class="super-panel-icon-wrap icon-teal">
                                <svg aria-hidden="true" viewBox="0 0 24 24">
                                    <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                    <path d="M9 22V12h6v10" />
                                </svg>
                            </div>
                            <div>
                                <h2 id="user-warehouses-title" class="super-panel-title">3. Penugasan Akses Gudang</h2>
                                <p class="super-panel-subtitle">Tentukan gudang mana saja yang dapat dikelola atau diakses
                                    oleh pengguna ini.</p>
                            </div>
                        </header>

                        <div class="super-choices-grid">
                            @forelse ($warehouses as $warehouse)
                                <label class="super-choice-card"
                                    :class="form.gudangIds.includes(@js((string) $warehouse->id)) ? 'is-selected' : ''">
                                    <input type="checkbox" name="gudang_ids[]" value="{{ $warehouse->id }}"
                                        x-model="form.gudangIds" @checked(in_array((string) $warehouse->id, $selectedGudangIds, true))
                                        class="super-choice-checkbox">
                                    <div class="super-choice-body">
                                        <div class="super-choice-header">
                                            <strong class="super-warehouse-name">{{ $warehouse->nama }}</strong>
                                            <span class="super-warehouse-code">{{ $warehouse->kode }}</span>
                                        </div>
                                        <div class="super-choice-meta">
                                            <span class="super-tag tag-gray">{{ $warehouse->jenis_label }}</span>
                                            <span
                                                class="super-status {{ $warehouse->is_aktif ? 'status-active' : 'status-inactive' }}">
                                                {{ $warehouse->is_aktif ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                        </div>
                                    </div>
                                </label>
                            @empty
                                <p class="super-table-empty">Belum ada master data gudang.</p>
                            @endforelse
                        </div>
                        @error('gudang_ids')
                            <span class="super-field-error"
                                style="margin-top: 10px; display:block;">{{ $message }}</span>
                        @enderror
                        @error('gudang_ids.*')
                            <span class="super-field-error"
                                style="margin-top: 10px; display:block;">{{ $message }}</span>
                        @enderror

                        <div class="super-panel-info-banner">
                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 16v-4m0-4h.01" />
                            </svg>
                            <span>Pengguna hanya dapat melihat dan memproses mutasi barang sesuai gudang yang telah
                                ditugaskan kepadanya.</span>
                        </div>
                    </section>
                </div>

                {{-- Kolom Kanan: Live Preview Ringkasan Akses Pengguna (Sticky Aside) --}}
                <aside class="super-form-aside" aria-label="Ringkasan akses pengguna">
                    <section class="super-panel super-summary-panel">
                        <header class="super-summary-header">
                            <div class="super-summary-title-wrap">
                                <svg aria-hidden="true" viewBox="0 0 24 24">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                </svg>
                                <strong>Ringkasan Akses Pengguna</strong>
                            </div>
                            <span class="super-tag tag-teal">LIVE PREVIEW</span>
                        </header>

                        {{-- Kartu Identitas --}}
                        <div class="super-summary-identity">
                            <span class="super-summary-avatar" x-text="initials">
                                {{ mb_strtoupper(mb_substr($formState['name'] ?: 'U', 0, 2)) }}
                            </span>
                            <div class="super-summary-identity-copy">
                                <strong class="super-summary-name" x-text="form.name.trim() || 'Nama Pengguna'">
                                    {{ $formState['name'] ?: 'Nama Pengguna' }}
                                </strong>
                                <span class="super-username-code" x-text="form.username.trim() || 'username'">
                                    {{ $formState['username'] ?: 'username' }}
                                </span>
                            </div>
                            <span class="super-status" :class="form.isActive ? 'status-active' : 'status-inactive'"
                                x-text="form.isActive ? 'Aktif' : 'Nonaktif'">
                                {{ $isActive ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>

                        {{-- Section Role Dipilih --}}
                        <div class="super-summary-group">
                            <div class="super-summary-label">
                                <span>Peran Role Terpilih</span>
                                <strong x-text="selectedRoleOptions.length"></strong>
                            </div>
                            <div class="super-summary-role-list" x-show="selectedRoleOptions.length">
                                <template x-for="role in selectedRoleOptions" :key="role.name">
                                    <span class="super-role-badge role-super" x-text="role.name"></span>
                                </template>
                            </div>
                            <p class="super-summary-empty" x-show="!selectedRoleOptions.length">Belum ada role yang
                                dipilih.</p>
                        </div>

                        {{-- Section Gudang Ditugaskan --}}
                        <div class="super-summary-group">
                            <div class="super-summary-label">
                                <span>Gudang Ditugaskan</span>
                                <strong x-text="selectedWarehouseOptions.length"></strong>
                            </div>
                            <div class="super-summary-warehouse-list" x-show="selectedWarehouseOptions.length">
                                <template x-for="warehouse in selectedWarehouseOptions" :key="warehouse.id">
                                    <div class="super-summary-wh-item">
                                        <span class="super-wh-title" x-text="warehouse.name"></span>
                                        <span class="super-warehouse-code" x-text="warehouse.code"></span>
                                    </div>
                                </template>
                            </div>
                            <p class="super-summary-empty" x-show="!selectedWarehouseOptions.length">Belum ada gudang
                                ditugaskan.</p>
                        </div>

                        {{-- Section Izin Efektif --}}
                        <div class="super-summary-group">
                            <div class="super-summary-label">
                                <span>Izin Akses Efektif</span>
                                <strong x-text="effectivePermissions.length"></strong>
                            </div>
                            <div class="super-summary-perm-list" x-show="effectivePermissions.length">
                                <template x-for="permission in visiblePermissions" :key="permission">
                                    <span class="super-perm-badge">
                                        <svg aria-hidden="true" viewBox="0 0 24 24">
                                            <path d="m5 12 4 4L19 6" />
                                        </svg>
                                        <span x-text="permission"></span>
                                    </span>
                                </template>
                                <small class="super-perm-more"
                                    x-show="effectivePermissions.length > visiblePermissions.length"
                                    x-text="'+' + (effectivePermissions.length - visiblePermissions.length) + ' izin lainnya'"></small>
                            </div>
                            <p class="super-summary-empty" x-show="!effectivePermissions.length">Izin efektif akan
                                terhitung otomatis sesuai role terpilih.</p>
                        </div>

                        {{-- Petunjuk Keamanan --}}
                        <div class="super-panel-info-banner banner-warning">
                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                <path d="M12 8v4m0 4h.01" />
                            </svg>
                            <span>Pastikan pemberian hak akses role dan gudang sesuai dengan fungsi operasional
                                pengguna.</span>
                        </div>
                    </section>
                </aside>
            </div>

            {{-- Footer Aksi Form --}}
            <footer class="super-form-actions-card">
                <div class="super-actions-left">
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 16v-4m0-4h.01" />
                    </svg>
                    <span>Bidang bertanda <b class="super-required">*</b> wajib diisi sebelum menyimpan data.</span>
                </div>
                <div class="super-actions-right">
                    <a class="super-btn-cancel" href="{{ route('admin.users.index') }}">Batal</a>
                    <button class="super-btn-save" type="submit">
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                            <polyline points="17 21 17 13 7 13 7 21" />
                            <polyline points="7 3 7 8 15 8" />
                        </svg>
                        <span>{{ $user ? 'Simpan Perubahan' : 'Simpan Pengguna' }}</span>
                    </button>
                </div>
            </footer>
        </form>
    </div>

    @push('scripts')
    @vite('resources/js/pages/super-admin/users/form.js')
@endpush

    @push('styles')
    @vite('resources/css/pages/super-admin/users/form.css')
@endpush
@endsection
