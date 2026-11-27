@extends('layouts.app')

@php
    $currentRole = $role ?? null;
    $isEditing = $currentRole !== null;
    $isProtectedRole = $isProtectedRole ?? false;
    $initialPermissions = array_values(
        (array) old('permissions', $currentRole?->permissions?->pluck('name')->all() ?? []),
    );
@endphp

@section('title', ($isEditing ? 'Edit Role: ' . $currentRole->name : 'Tambah Role Baru') . ' — ' . config('app.name',
    'SIGMA'))

@section('content')
    <div class="super-dashboard" x-data="{
        roleName: @js(old('name', $currentRole?->name ?? '')),
        roleDescription: @js(old('description', $currentRole?->description ?? '')),
        isProtectedRole: @js($isProtectedRole),
        permissionGroups: @js($permissionGroups),
        roleTemplates: @js($roleTemplates),
        selectedPermissions: @js($initialPermissions),
        actionColumns: [
            { key: 'view', label: 'LIHAT' },
            { key: 'create', label: 'BUAT' },
            { key: 'update', label: 'UBAH' },
            { key: 'delete', label: 'HAPUS' }
        ],
        groupIcons: {
            'Master Pengguna & Sistem': 'users',
            'Master Data & Katalog': 'catalog',
            'Gudang Store (Armada Holding)': 'catalog',
            'Operasional Unit Bisnis': 'opname',
            'Master Data & Katalog Global': 'catalog',
            'Operasional Gudang Store (Holding)': 'catalog',
            'Operasional Gudang Unit Bisnis': 'opname',
            'Laporan & Analytics': 'audit',
            'Manajemen Sistem (Super Admin)': 'users'
        },
        resourceIcons: {
            barang: 'catalog',
            store: 'catalog',
            store_stok: 'catalog',
            store_penerimaan: 'truck',
            store_pengeluaran: 'truck',
            store_laporan: 'audit',
            kategori: 'category',
            satuan: 'unit',
            stok_awal: 'catalog',
            stok_opname: 'opname',
            pemindahan: 'move',
            penerimaan: 'truck',
            pengeluaran: 'truck',
            label: 'label',
            retur: 'return',
            berita_acara: 'document',
            laporan: 'audit',
            user: 'users',
            users: 'users',
            role: 'role',
            roles: 'role',
            gudang: 'warehouse',
            audit: 'audit'
        },
        resourceLabels: {
            user: 'Kelola Akun Pengguna',
            users: 'Kelola Akun Pengguna',
            role: 'Role & Permission Matrix',
            roles: 'Role & Permission Matrix',
            gudang: 'Master Lokasi Gudang',
            audit: 'Log Audit Aktivitas',
            barang: 'Master Barang',
            kategori: 'Kategori Barang',
            satuan: 'Satuan Barang',
            stok_awal: 'Import Data Awal',
            store_stok: 'Stok Gudang Store',
            store_penerimaan: 'Penerimaan Barang Store',
            store_pengeluaran: 'Pengeluaran Barang Store',
            store_laporan: 'Laporan Gudang Store',
            pemindahan: 'Penerimaan Kiriman Store (Transfer)',
            stok_opname: 'Stok Opname Unit',
            penerimaan: 'Penerimaan Barang Unit',
            pengeluaran: 'Pengeluaran & BPB Unit',
            retur: 'Retur Barang Unit',
            label: 'Cetak Label & Barcode',
            berita_acara: 'Berita Acara Kehilangan',
            laporan: 'Rekapitulasi Laporan'
        },
        templateMode: 'empty',
        selectedTemplate: '',
        activeGroup: @js(array_key_first($permissionGroups) ?? ''),
        permissionSearch: '',
        groupIcon(group) {
            return this.groupIcons[group] || 'dashboard';
        },
        resourceIcon(resource) {
            return this.resourceIcons[resource] || 'dashboard';
        },
        get groupNames() {
            return Object.keys(this.permissionGroups);
        },
        get allPermissions() {
            return Object.values(this.permissionGroups).flat();
        },
        get activePermissionRows() {
            const rows = new Map();
            const search = this.permissionSearch.trim().toLowerCase();
    
            (this.permissionGroups[this.activeGroup] ?? []).forEach(permission => {
                const [resource] = permission.key.split('.');
    
                if (search && !(permission.label + ' ' + permission.key + ' ' + resource).toLowerCase().includes(search)) {
                    return;
                }
    
                if (!rows.has(resource)) {
                    rows.set(resource, {
                        resource,
                        label: this.resourceLabels[resource] || resource.replaceAll('_', ' ').replace(/\b\w/g, character => character.toUpperCase()),
                        icon: this.resourceIcon(resource),
                        permissions: [],
                        actions: { view: [], create: [], update: [], delete: [] },
                        extraPermissions: []
                    });
                }
    
                const row = rows.get(resource);
                row.permissions.push(permission);
    
                if (permission.actions.length === 0) {
                    row.extraPermissions.push(permission);
                }
    
                permission.actions.forEach(action => row.actions[action].push(permission.key));
            });
    
            return [...rows.values()];
        },
        get selectedPermissionDetails() {
            return Object.entries(this.permissionGroups).flatMap(([group, permissions]) =>
                permissions
                .filter(permission => this.selectedPermissions.includes(permission.key))
                .map(permission => ({ ...permission, group }))
            );
        },
        countSelected(group) {
            return this.permissionGroups[group].filter(permission => this.selectedPermissions.includes(permission.key)).length;
        },
        isActionSelected(row, action) {
            return row.actions[action].every(permission => this.selectedPermissions.includes(permission));
        },
        toggleAction(row, action, shouldSelect) {
            const permissions = row.actions[action];
    
            this.selectedPermissions = shouldSelect ? [...new Set([...this.selectedPermissions, ...permissions])] :
                this.selectedPermissions.filter(permission => !permissions.includes(permission));
        },
        togglePermission(permission, shouldSelect) {
            this.selectedPermissions = shouldSelect ? [...new Set([...this.selectedPermissions, permission.key])] :
                this.selectedPermissions.filter(selected => selected !== permission.key);
        },
        toggleGroup(group, shouldSelect) {
            const groupPermissions = this.permissionGroups[group].map(permission => permission.key);
    
            this.selectedPermissions = shouldSelect ? [...new Set([...this.selectedPermissions, ...groupPermissions])] :
                this.selectedPermissions.filter(permission => !groupPermissions.includes(permission));
        },
        loadTemplate() {
            const template = this.roleTemplates.find(role => role.id === this.selectedTemplate);
    
            this.selectedPermissions = template ? [...template.permissions] : [];
        }
    }">
        {{-- SVG Library untuk Ikon Matriks --}}
        <svg class="super-matrix-icon-library" aria-hidden="true" focusable="false"
            style="position: absolute; width: 0; height: 0; overflow: hidden;">
            <symbol id="role-icon-dashboard" viewBox="0 0 24 24">
                <rect x="3" y="3" width="7" height="7" />
                <rect x="14" y="3" width="7" height="7" />
                <rect x="14" y="14" width="7" height="7" />
                <rect x="3" y="14" width="7" height="7" />
            </symbol>
            <symbol id="role-icon-catalog" viewBox="0 0 24 24">
                <ellipse cx="12" cy="5" rx="9" ry="3" />
                <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" />
                <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
            </symbol>
            <symbol id="role-icon-category" viewBox="0 0 24 24">
                <polygon points="12 2 2 7 12 12 22 7 12 2" />
                <polyline points="2 17 12 22 22 17" />
                <polyline points="2 12 12 17 22 12" />
            </symbol>
            <symbol id="role-icon-unit" viewBox="0 0 24 24">
                <path d="M21.21 15.89A10 10 0 1 1 8 2.83" />
                <path d="M22 12A10 10 0 0 0 12 2v10z" />
            </symbol>
            <symbol id="role-icon-users" viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </symbol>
            <symbol id="role-icon-role" viewBox="0 0 24 24">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
            </symbol>
            <symbol id="role-icon-warehouse" viewBox="0 0 24 24">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                <polyline points="9 22 9 12 15 12 15 22" />
            </symbol>
            <symbol id="role-icon-audit" viewBox="0 0 24 24">
                <polyline points="12 8 12 12 14 14" />
                <circle cx="12" cy="12" r="10" />
            </symbol>
            <symbol id="role-icon-opname" viewBox="0 0 24 24">
                <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" />
                <rect x="9" y="3" width="6" height="4" rx="1" />
                <path d="m9 12 2 2 4-4" />
            </symbol>
            <symbol id="role-icon-move" viewBox="0 0 24 24">
                <path d="M5 12h14" />
                <path d="m12 5 7 7-7 7" />
            </symbol>
            <symbol id="role-icon-truck" viewBox="0 0 24 24">
                <rect x="1" y="3" width="15" height="13" rx="1" />
                <path d="M16 8h4l3 4v5h-7V8z" />
                <circle cx="5.5" cy="18.5" r="2.5" />
                <circle cx="18.5" cy="18.5" r="2.5" />
            </symbol>
            <symbol id="role-icon-label" viewBox="0 0 24 24">
                <path
                    d="M20.59 13.41L11 3.83A2 2 0 0 0 9.59 3H4a1 1 0 0 0-1 1v5.59a2 2 0 0 0 .59 1.42l9.58 9.58a2 2 0 0 0 2.83 0l4.59-4.59a2 2 0 0 0 0-2.82z" />
                <circle cx="7.5" cy="7.5" r="1.4" />
            </symbol>
            <symbol id="role-icon-return" viewBox="0 0 24 24">
                <polyline points="1 4 1 10 7 10" />
                <path d="M3.51 15a9 9 0 1 0 .49-4.95" />
            </symbol>
            <symbol id="role-icon-document" viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14 2 14 8 20 8" />
            </symbol>
        </svg>

        {{-- Header Halaman Form --}}
        <header class="super-users-header">
            <div>
                <nav class="super-breadcrumb" aria-label="Breadcrumb">
                    <a href="{{ route('admin.dashboard') }}">Super Admin</a>
                    <span aria-hidden="true">›</span>
                    <a href="{{ route('admin.roles.index') }}">Role &amp; Permission</a>
                    <span aria-hidden="true">›</span>
                    <span>{{ $isEditing ? 'Edit Role' : 'Tambah Role Baru' }}</span>
                </nav>
                <h1 class="super-page-title">{{ $isEditing ? 'Edit Role: ' . $currentRole->name : 'Tambah Role Baru' }}</h1>
                <p class="super-page-subtitle">Konfigurasikan identitas profil peran, guard web, serta alokasi matriks hak
                    akses CRUD granular per-modul.</p>
            </div>
            <div class="super-header-actions">
                <span class="super-guard-badge">
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="16" rx="2" />
                        <path d="M7 8h10M7 12h10M7 16h5" />
                    </svg>
                    <span>Guard: <strong>web</strong></span>
                </span>
                <a class="super-back-btn" href="{{ route('admin.roles.index') }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                    <span>Kembali ke Daftar</span>
                </a>
            </div>
        </header>

        <form action="{{ $isEditing ? route('admin.roles.update', $currentRole) : route('admin.roles.store') }}"
            method="POST" id="create-role-form" class="super-role-matrix-form">
            @csrf
            @if ($isEditing)
                @method('PUT')
            @endif
            <template x-for="permission in allPermissions" :key="permission.key">
                <input type="hidden" name="permissions[]" :value="permission.key"
                    :disabled="!selectedPermissions.includes(permission.key)">
            </template>

            <div class="super-form-layout">
                {{-- Kolom Kiri: Form Identitas & Matriks Hak Akses --}}
                <div class="super-form-main">
                    {{-- Section 1: Informasi Role --}}
                    <section class="super-panel" aria-labelledby="role-identity-title">
                        <header class="super-panel-header">
                            <div class="super-panel-icon-wrap icon-amber">
                                <svg aria-hidden="true" viewBox="0 0 24 24">
                                    <rect x="3" y="4" width="18" height="16" rx="2" />
                                    <circle cx="9" cy="10" r="2" />
                                    <path d="M5.5 17a3.5 3.5 0 0 1 7 0M15 9h3m-3 4h3" />
                                </svg>
                            </div>
                            <div>
                                <h2 id="role-identity-title" class="super-panel-title">
                                    {{ $isEditing ? 'Informasi Role' : 'Informasi Role Baru' }}</h2>
                                <p class="super-panel-subtitle">Konfigurasikan identitas profil peran dan cakupan wewenang
                                    operasionalnya.</p>
                            </div>
                        </header>

                        <div class="super-fields-grid">
                            {{-- Nama Role --}}
                            <div class="super-field-group">
                                <label for="role-name-input" class="super-field-label">
                                    Nama Role <span class="super-required">*</span>
                                </label>
                                <input id="role-name-input" class="super-input @error('name') is-invalid @enderror"
                                    type="text" name="name" value="{{ old('name', $currentRole?->name ?? '') }}"
                                    x-model="roleName" maxlength="255"
                                    placeholder="{{ $isEditing ? '' : 'misal: Supervisor Lapangan' }}" autocomplete="off"
                                    required @disabled($isProtectedRole)>
                                @error('name')
                                    <span class="super-field-error">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Deskripsi Wewenang --}}
                            <div class="super-field-group">
                                <label for="role-desc-input" class="super-field-label">
                                    Deskripsi Wewenang <span class="super-optional">(Opsional)</span>
                                </label>
                                <input id="role-desc-input" class="super-input @error('description') is-invalid @enderror"
                                    type="text" name="description"
                                    value="{{ old('description', $currentRole?->description ?? '') }}"
                                    x-model="roleDescription" maxlength="1000"
                                    placeholder="Rincian fungsionalitas bagi tim operasional..."
                                    @disabled($isProtectedRole)>
                                @error('description')
                                    <span class="super-field-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </section>

                    {{-- Banner Role Dilindungi (Jika Super Admin) --}}
                    @if ($isProtectedRole)
                        <div class="super-panel-info-banner banner-warning">
                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                <path d="M9 12h6" />
                            </svg>
                            <span><strong>Role Super Admin dilindungi.</strong> Nama, deskripsi, dan hak akses penuh role
                                ini tidak dapat diubah melalui halaman ini.</span>
                        </div>
                    @endif

                    {{-- Section 2: Matriks Hak Akses & Otorisasi --}}
                    <section class="super-panel super-matrix-panel" aria-labelledby="role-permission-title">
                        <header class="super-panel-header super-panel-header-split">
                            <div class="super-panel-heading-left">
                                <div class="super-panel-icon-wrap icon-teal">
                                    <svg aria-hidden="true" viewBox="0 0 24 24">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                        <path d="m9 12 2 2 4-4" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 id="role-permission-title" class="super-panel-title">Matriks Hak Akses &amp;
                                        Otorisasi</h2>
                                    <p class="super-panel-subtitle">Atur hak akses per modul melalui kolom lihat, buat,
                                        ubah, dan hapus.</p>
                                </div>
                            </div>

                            {{-- Template Selector Saat Create --}}
                            @unless ($isEditing)
                                <div class="super-template-controls">
                                    <label class="super-template-radio">
                                        <input type="radio" name="role_source" value="empty" x-model="templateMode"
                                            @change="selectedTemplate = ''; selectedPermissions = []">
                                        <span>Mulai dari kosong</span>
                                    </label>
                                    <label class="super-template-radio">
                                        <input type="radio" name="role_source" value="template" x-model="templateMode">
                                        <span>Salin dari role yang ada</span>
                                    </label>
                                    <select aria-label="Pilih role template" class="super-template-select"
                                        x-model="selectedTemplate" @change="loadTemplate()"
                                        :disabled="templateMode !== 'template' || roleTemplates.length === 0">
                                        <option value="">-- Pilih Role Template --</option>
                                        <template x-for="template in roleTemplates" :key="template.id">
                                            <option :value="template.id" x-text="template.name"></option>
                                        </template>
                                    </select>
                                </div>
                            @endunless
                        </header>

                        {{-- Container Layout Matriks: Sidebar Kelompok + Konten Matriks --}}
                        <div class="super-matrix-split-layout">
                            {{-- Sidebar Kelompok Modul --}}
                            <aside class="super-matrix-groups-aside" aria-label="Daftar kelompok permission">
                                <div class="super-groups-heading">
                                    <span>KELOMPOK PERMISSION</span>
                                    <span class="super-groups-badge" x-text="groupNames.length + ' grup'"></span>
                                </div>

                                <div class="super-groups-nav">
                                    <template x-for="(group, index) in groupNames" :key="group">
                                        <button class="super-group-nav-btn"
                                            :class="activeGroup === group ? 'is-active' : ''" type="button"
                                            @click="activeGroup = group; permissionSearch = ''">
                                            <span class="super-group-title-wrap">
                                                <svg aria-hidden="true" viewBox="0 0 24 24">
                                                    <use :href="'#role-icon-' + groupIcon(group)"></use>
                                                </svg>
                                                <span x-text="group"></span>
                                            </span>
                                            <span class="super-group-counter"
                                                :class="activeGroup === group ? 'is-active' : ''"
                                                x-text="countSelected(group) + '/' + permissionGroups[group].length"></span>
                                        </button>
                                    </template>
                                </div>

                                <div class="super-groups-total-box">
                                    <div class="super-groups-total-labels">
                                        <span>Total Hak Terpilih</span>
                                        <strong><span x-text="selectedPermissions.length"></span> / <span
                                                x-text="allPermissions.length"></span></strong>
                                    </div>
                                    <div class="super-progress-bar">
                                        <div class="super-progress-fill"
                                            :style="'width:' + (allPermissions.length ? selectedPermissions.length /
                                                allPermissions.length * 100 : 0) + '%'">
                                        </div>
                                    </div>
                                </div>
                            </aside>

                            {{-- Konten Utama Matriks Tabel --}}
                            <div class="super-matrix-content-area">
                                <template x-for="group in groupNames" :key="group">
                                    <section x-cloak x-show="activeGroup === group" class="super-matrix-group-panel">
                                        {{-- Toolbar Pencarian & Aksi Batch Grup --}}
                                        <div class="super-matrix-toolbar">
                                            <label class="super-matrix-search-box">
                                                <svg aria-hidden="true" viewBox="0 0 24 24">
                                                    <circle cx="11" cy="11" r="7" />
                                                    <path d="m20 20-4-4" />
                                                </svg>
                                                <input type="search" placeholder="Cari permission di kelompok ini..."
                                                    x-model="permissionSearch"
                                                    :aria-label="'Cari permission di ' + group">
                                            </label>

                                            <div class="super-matrix-batch-actions">
                                                <button type="button" class="super-batch-btn btn-check-all"
                                                    @click="toggleGroup(group, true)" @disabled($isProtectedRole)>
                                                    <svg aria-hidden="true" viewBox="0 0 24 24">
                                                        <path d="M8 5h13M8 12h13M8 19h13M3 5h.01M3 12h.01M3 19h.01" />
                                                    </svg>
                                                    <span>Pilih Semua</span>
                                                </button>
                                                <button type="button" class="super-batch-btn btn-reset-all"
                                                    @click="toggleGroup(group, false)" @disabled($isProtectedRole)>
                                                    <svg aria-hidden="true" viewBox="0 0 24 24">
                                                        <path d="M5 5l14 14M19 5 5 19" />
                                                    </svg>
                                                    <span>Reset Grup</span>
                                                </button>
                                            </div>
                                        </div>

                                        <p class="super-matrix-hint-note">
                                            Aksi yang belum memiliki permission dinonaktifkan. Permission
                                            <code>manage</code> yang mencakup ubah dan hapus akan tampil pada kedua kolom.
                                        </p>

                                        {{-- Tabel Matriks Akses --}}
                                        <div class="super-matrix-table-wrap">
                                            <table class="super-matrix-table role-permission-table">
                                                <thead>
                                                    <tr>
                                                        <th scope="col" style="text-align: left;">Modul</th>
                                                        <template x-for="action in actionColumns" :key="action.key">
                                                            <th scope="col" style="text-align: center;"
                                                                x-text="action.label"></th>
                                                        </template>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <template x-for="row in activePermissionRows" :key="row.resource">
                                                        <tr>
                                                            <th scope="row">
                                                                <div class="super-perm-resource-cell">
                                                                    <span class="super-perm-resource-icon">
                                                                        <svg aria-hidden="true" viewBox="0 0 24 24">
                                                                            <use :href="'#role-icon-' + row.icon"></use>
                                                                        </svg>
                                                                    </span>
                                                                    <div>
                                                                        <strong class="super-resource-label"
                                                                            x-text="row.label"></strong>
                                                                        <code class="super-resource-keys"
                                                                            x-text="row.permissions.map(p => p.key).join(', ')"></code>
                                                                    </div>
                                                                </div>
                                                            </th>
                                                            <template x-for="action in actionColumns"
                                                                :key="action.key">
                                                                <td style="text-align: center;">
                                                                    <input type="checkbox" class="super-matrix-checkbox"
                                                                        :checked="row.actions[action.key].length > 0 &&
                                                                            isActionSelected(row, action.key)"
                                                                        :disabled="isProtectedRole || row.actions[action.key]
                                                                            .length === 0"
                                                                        :title="row.actions[action.key].length ? row.actions[
                                                                                action.key].join(', ') :
                                                                            'Permission aksi ini belum tersedia.'"
                                                                        :aria-label="action.label + ' — ' + row.label"
                                                                        @change="toggleAction(row, action.key, $event.target.checked)">
                                                                </td>
                                                            </template>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>

                                            <div class="super-table-empty" x-show="permissionGroups[group].length === 0">
                                                Belum ada permission di kelompok ini.
                                            </div>
                                            <div class="super-table-empty"
                                                x-show="permissionGroups[group].length > 0 && activePermissionRows.length === 0">
                                                Permission tidak ditemukan dengan kata kunci tersebut.
                                            </div>

                                            {{-- Extra Permissions (Non-CRUD) --}}
                                            <div class="super-extra-perms-box role-extra-permissions"
                                                x-show="activePermissionRows.some(row => row.extraPermissions.length > 0)">
                                                <h3 class="super-extra-title">Permission operasional lainnya</h3>
                                                <div class="super-extra-grid">
                                                    <template x-for="row in activePermissionRows"
                                                        :key="row.resource + '-extra'">
                                                        <template x-for="permission in row.extraPermissions"
                                                            :key="permission.key">
                                                            <label class="super-extra-card">
                                                                <div class="super-extra-copy">
                                                                    <strong x-text="permission.label"></strong>
                                                                    <code x-text="permission.key"></code>
                                                                </div>
                                                                <input type="checkbox" class="super-matrix-checkbox"
                                                                    :checked="selectedPermissions.includes(permission.key)"
                                                                    :disabled="isProtectedRole"
                                                                    :aria-label="'Izinkan ' + permission.label"
                                                                    @change="togglePermission(permission, $event.target.checked)">
                                                            </label>
                                                        </template>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </template>

                                @error('permissions')
                                    <span class="super-field-error"
                                        style="margin-top: 10px; display:block;">{{ $message }}</span>
                                @enderror
                                @error('permissions.*')
                                    <span class="super-field-error"
                                        style="margin-top: 10px; display:block;">{{ $message }}</span>
                                @enderror

                                @if (count($permissionGroups) === 0)
                                    <div class="super-panel-info-banner banner-warning">
                                        Belum ada permission guard <code>web</code> yang tersedia. Role tetap dapat dibuat,
                                        tetapi belum memiliki hak akses.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            {{-- Footer Aksi Form Matriks --}}
            <footer class="super-form-actions-card">
                <div class="super-actions-left">
                    <a class="super-btn-cancel" href="{{ route('admin.roles.index') }}">Batal</a>
                    <span class="super-actions-hint">
                        {{ $isProtectedRole ? 'Role bawaan ini hanya dapat dilihat (read-only).' : ($isEditing ? 'Perubahan hak akses langsung berlaku setelah disimpan.' : 'Pastikan nama dan permission telah sesuai.') }}
                    </span>
                </div>
                <div class="super-actions-right">
                    <span class="super-count-indicator" x-text="selectedPermissions.length + ' permission dipilih'">0
                        permission dipilih</span>
                    @unless ($isProtectedRole)
                        <button class="super-btn-save" type="submit">
                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                                <polyline points="17 21 17 13 7 13 7 21" />
                                <polyline points="7 3 7 8 15 8" />
                            </svg>
                            <span>{{ $isEditing ? 'Simpan Role & Hak Akses' : 'Simpan Role' }}</span>
                        </button>
                    @endunless
                </div>
            </footer>
        </form>
    </div>

    @push('styles')
    @vite('resources/css/pages/super-admin/roles/create.css')
@endpush
@endsection
