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
            height: 38px;
            padding: 0 12px;
            border-radius: var(--radius);
            background: var(--surface-base);
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

        .super-guard-badge strong {
            color: var(--text-primary);
        }

        .super-back-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            height: 38px;
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            color: var(--text-primary);
            padding: 0 14px;
            border-radius: var(--radius);
            font-size: var(--dash-text-helper);
            font-weight: 700;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .super-back-btn:hover {
            background: var(--surface-base);
            border-color: var(--brand-primary);
            color: var(--brand-primary);
        }

        .super-back-btn svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
            stroke-width: 2.2;
            fill: none;
        }

        /* Form Layout */
        .super-role-matrix-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-width: 0;
        }

        .super-form-layout {
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-width: 0;
            width: 100%;
        }

        .super-form-main {
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-width: 0;
            width: 100%;
        }

        /* Panel Cards (Dashboard Standard) */
        .super-panel {
            min-width: 0;
            overflow: hidden;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-lg);
            background: var(--surface-card);
        }

        .super-panel-header {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 60px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--surface-border);
        }

        .super-panel-header-split {
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .super-panel-heading-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .super-panel-icon-wrap {
            width: 32px;
            height: 32px;
            border-radius: var(--radius);
            border: 1px solid var(--surface-border);
            background: var(--surface-base);
            color: var(--brand-primary);
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }

        .super-panel-icon-wrap.icon-amber {
            background: rgba(201, 122, 43, .1);
            color: var(--brand-accent-d);
            border-color: rgba(201, 122, 43, .2);
        }

        .super-panel-icon-wrap.icon-teal {
            background: rgba(20, 92, 84, .1);
            color: var(--brand-primary);
            border-color: rgba(20, 92, 84, .2);
        }

        .super-panel-icon-wrap svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .super-panel-title {
            font-size: var(--dash-text-section);
            font-weight: 800;
            color: var(--text-primary);
            margin: 0;
            line-height: 1.2;
        }

        .super-panel-subtitle {
            font-size: var(--dash-text-helper);
            color: var(--text-muted);
            margin: 3px 0 0;
            line-height: 1.35;
        }

        /* Input Fields */
        .super-fields-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px;
            padding: 16px;
        }

        .super-field-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }

        .super-field-label {
            font-size: var(--dash-text-helper);
            font-weight: 700;
            color: var(--text-primary);
        }

        .super-required {
            color: var(--status-critical);
            font-weight: 700;
        }

        .super-optional {
            font-size: 11px;
            color: var(--text-subtle);
            font-weight: normal;
        }

        .super-input {
            width: 100%;
            min-width: 0;
            height: 38px;
            padding: 0 12px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
            font: inherit;
            font-size: var(--dash-text-helper);
            color: var(--text-primary);
            box-sizing: border-box;
            transition: all 0.15s ease;
        }

        .super-input:focus {
            outline: 2px solid var(--brand-primary);
            outline-offset: 1px;
            border-color: var(--brand-primary);
        }

        .super-field-error {
            font-size: 11px;
            color: var(--status-critical);
            font-weight: 600;
        }

        /* Template Controls */
        .super-template-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            background: var(--surface-base);
            padding: 5px 10px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
        }

        .super-template-radio {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: var(--dash-text-helper);
            color: var(--text-muted);
            font-weight: 600;
            cursor: pointer;
        }

        .super-template-radio input {
            accent-color: var(--brand-primary);
        }

        .super-template-select {
            height: 30px;
            padding: 0 8px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
            font: inherit;
            font-size: var(--dash-text-helper);
            color: var(--text-primary);
        }

        .super-template-select:focus {
            outline: 2px solid var(--brand-primary);
        }

        /* Matriks Split Layout */
        .super-matrix-panel {
            padding: 0;
            overflow: hidden;
        }

        .super-matrix-split-layout {
            display: grid;
            grid-template-columns: minmax(200px, 230px) minmax(0, 1fr);
            min-height: 480px;
            min-width: 0;
        }

        /* Sidebar Kelompok Permission */
        .super-matrix-groups-aside {
            background: var(--surface-base);
            border-right: 1px solid var(--surface-border);
            padding: 12px 10px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 0;
        }

        .super-groups-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 6px 8px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.05em;
            color: var(--text-muted);
        }

        .super-groups-badge {
            color: var(--text-subtle);
            font-family: monospace;
            font-size: 11px;
        }

        .super-groups-nav {
            display: flex;
            flex-direction: column;
            gap: 3px;
            flex: 1;
            min-width: 0;
        }

        .super-group-nav-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 8px 10px;
            background: transparent;
            border: 1px solid transparent;
            border-radius: var(--radius);
            color: var(--text-primary);
            font: inherit;
            font-size: var(--dash-text-helper);
            font-weight: 600;
            text-align: left;
            cursor: pointer;
            transition: all 0.15s ease;
            min-width: 0;
        }

        .super-group-nav-btn:hover {
            background: #ffffff;
            border-color: var(--surface-border);
        }

        .super-group-nav-btn.is-active {
            background: var(--brand-primary);
            color: #ffffff;
            border-color: var(--brand-primary);
        }

        .super-group-title-wrap {
            display: flex;
            align-items: center;
            gap: 7px;
            min-width: 0;
            line-height: 1.25;
            overflow: hidden;
        }

        .super-group-title-wrap span {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .super-group-title-wrap svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
            flex-shrink: 0;
        }

        .super-group-counter {
            padding: 2px 5px;
            border-radius: 4px;
            background: rgba(91, 107, 121, .12);
            color: var(--text-muted);
            font-size: 10px;
            font-family: monospace;
            font-weight: 700;
            flex-shrink: 0;
        }

        .super-group-counter.is-active {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }

        .super-groups-total-box {
            margin-top: auto;
            padding: 10px 4px 2px;
            border-top: 1px solid var(--surface-border);
        }

        .super-groups-total-labels {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 5px;
        }

        .super-groups-total-labels strong {
            color: var(--brand-primary);
        }

        .super-progress-bar {
            height: 5px;
            background: var(--surface-border);
            border-radius: 999px;
            overflow: hidden;
        }

        .super-progress-fill {
            height: 100%;
            background: var(--brand-primary);
            border-radius: inherit;
            transition: width 0.2s ease;
        }

        /* Konten Matriks */
        .super-matrix-content-area {
            padding: 14px 16px;
            overflow-x: auto;
            min-width: 0;
        }

        .super-matrix-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .super-matrix-search-box {
            display: flex;
            align-items: center;
            gap: 7px;
            height: 36px;
            padding: 0 10px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
            width: min(100%, 280px);
        }

        .super-matrix-search-box svg {
            width: 14px;
            height: 14px;
            stroke: var(--text-muted);
            stroke-width: 2;
            fill: none;
            flex-shrink: 0;
        }

        .super-matrix-search-box input {
            width: 100%;
            min-width: 0;
            border: 0;
            outline: 0;
            background: transparent;
            font: inherit;
            font-size: var(--dash-text-helper);
            color: var(--text-primary);
        }

        .super-matrix-batch-actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .super-batch-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            height: 36px;
            padding: 0 10px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: var(--surface-card);
            color: var(--text-muted);
            font: inherit;
            font-size: var(--dash-text-helper);
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .super-batch-btn svg {
            width: 13px;
            height: 13px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .super-batch-btn:hover:not(:disabled) {
            background: var(--surface-base);
            color: var(--text-primary);
            border-color: var(--brand-primary);
        }

        .btn-reset-all:hover:not(:disabled) {
            color: var(--status-critical);
            border-color: rgba(193, 68, 60, .3);
            background: rgba(193, 68, 60, .05);
        }

        .super-matrix-hint-note {
            font-size: 11px;
            color: var(--text-muted);
            margin: 0 0 10px;
            line-height: 1.35;
        }

        .super-matrix-hint-note code {
            color: var(--brand-primary);
            background: rgba(20, 92, 84, .08);
            padding: 1px 4px;
            border-radius: 3px;
            font-family: monospace;
            font-weight: 700;
        }

        /* Table Matriks */
        .super-matrix-table-wrap {
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
            background: #ffffff;
        }

        .super-matrix-table {
            width: 100%;
            min-width: 460px;
            border-collapse: collapse;
            font-size: var(--dash-text-body);
        }

        .super-matrix-table thead th {
            padding: 9px 12px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.05em;
            background: var(--surface-base);
            color: var(--text-muted);
            border-bottom: 1px solid var(--surface-border);
            white-space: nowrap;
        }

        .super-matrix-table thead th:not(:first-child) {
            width: 72px;
        }

        .super-matrix-table tbody tr {
            border-bottom: 1px solid #edf2f0;
        }

        .super-matrix-table tbody tr:hover {
            background: rgba(20, 92, 84, .02);
        }

        .super-matrix-table tbody th {
            padding: 8px 12px;
            text-align: left;
            vertical-align: middle;
            font-weight: normal;
        }

        .super-matrix-table td {
            padding: 8px 12px;
            vertical-align: middle;
            text-align: center;
        }

        .super-perm-resource-cell {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 160px;
        }

        .super-perm-resource-icon {
            width: 26px;
            height: 26px;
            border-radius: var(--radius);
            background: var(--surface-base);
            border: 1px solid var(--surface-border);
            color: var(--brand-primary);
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }

        .super-perm-resource-icon svg {
            width: 13px;
            height: 13px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .super-resource-label {
            display: block;
            font-size: var(--dash-text-helper);
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.25;
        }

        .super-resource-keys {
            display: block;
            font-size: 11px;
            color: var(--text-subtle);
            font-family: monospace;
            line-height: 1.25;
        }

        .super-matrix-checkbox {
            width: 18px;
            height: 18px;
            accent-color: var(--brand-primary);
            cursor: pointer;
            vertical-align: middle;
        }

        /* Extra Permissions Grid */
        .super-extra-perms-box {
            padding: 12px;
            background: var(--surface-base);
            border-top: 1px solid var(--surface-border);
        }

        .super-extra-title {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: var(--text-muted);
            text-transform: uppercase;
            margin: 0 0 8px;
        }

        .super-extra-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 8px;
        }

        .super-extra-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 7px 10px;
            background: #ffffff;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .super-extra-card:hover {
            border-color: var(--brand-primary);
        }

        .super-extra-copy strong {
            display: block;
            font-size: var(--dash-text-helper);
            color: var(--text-primary);
            line-height: 1.2;
        }

        .super-extra-copy code {
            font-size: 10px;
            color: var(--text-subtle);
            font-family: monospace;
        }

        /* Live Preview Sidebar */
        .super-summary-panel {
            background: #ffffff;
            border-top: 3px solid var(--brand-primary);
        }

        .super-summary-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--surface-border);
        }

        .super-summary-title-wrap {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: var(--dash-text-body);
            font-weight: 800;
            color: var(--text-primary);
        }

        .super-summary-title-wrap svg {
            width: 16px;
            height: 16px;
            stroke: var(--brand-primary);
            stroke-width: 2;
            fill: none;
        }

        .super-summary-identity {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--surface-border);
        }

        .super-summary-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--brand-primary);
            color: #ffffff;
            font-size: 14px;
            font-weight: 800;
            display: grid;
            place-items: center;
            flex-shrink: 0;
        }

        .super-summary-identity-copy {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
            flex: 1;
        }

        .super-summary-name {
            font-size: var(--dash-text-body);
            font-weight: 700;
            color: var(--text-primary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .super-role-desc-preview {
            font-size: 11px;
            color: var(--text-muted);
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .super-summary-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--surface-border);
            text-align: center;
        }

        .super-stat-cell {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .super-stat-k {
            font-size: 10px;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .super-stat-v {
            font-size: 11px;
            font-weight: 800;
            color: var(--text-primary);
        }

        .super-summary-group {
            padding: 12px 16px;
        }

        .super-summary-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .03em;
            margin-bottom: 8px;
        }

        .super-preview-chip-list {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            max-height: 180px;
            overflow-y: auto;
        }

        .super-perm-badge {
            padding: 2px 6px;
            border-radius: 4px;
            background: rgba(20, 92, 84, .1);
            color: var(--brand-primary);
            font-size: 11px;
            font-weight: 700;
        }

        /* Info Banners */
        .super-panel-info-banner {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            padding: 10px 14px;
            border-radius: var(--radius);
            background: var(--surface-base);
            border: 1px solid var(--surface-border);
            color: var(--text-muted);
            font-size: var(--dash-text-helper);
            line-height: 1.35;
        }

        .super-panel-info-banner.banner-warning {
            background: rgba(184, 121, 26, .08);
            border-color: rgba(184, 121, 26, .25);
            color: var(--status-warning);
        }

        .super-panel-info-banner svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
            margin-top: 1px;
        }

        /* Footer Actions */
        .super-form-actions-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 16px;
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            border-radius: var(--radius-lg);
        }

        .super-actions-left {
            display: flex;
            align-items: center;
            gap: 10px;
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

        .super-count-indicator {
            font-size: 11px;
            font-weight: 800;
            color: var(--brand-primary);
            background: rgba(20, 92, 84, .1);
            padding: 4px 8px;
            border-radius: 999px;
        }

        .super-btn-cancel {
            display: inline-flex;
            align-items: center;
            height: 38px;
            padding: 0 16px;
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            background: #ffffff;
            color: var(--text-muted);
            font: inherit;
            font-size: var(--dash-text-helper);
            font-weight: 700;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .super-btn-cancel:hover {
            background: var(--surface-base);
            color: var(--text-primary);
            border-color: var(--surface-border);
        }

        .super-btn-save {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            height: 38px;
            padding: 0 18px;
            background: var(--brand-primary);
            color: #ffffff;
            border: none;
            border-radius: var(--radius);
            font: inherit;
            font-size: var(--dash-text-helper);
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .super-btn-save:hover {
            background: var(--brand-primary-d);
        }

        .super-btn-save svg {
            width: 14px;
            height: 14px;
            stroke: currentColor;
            stroke-width: 2.2;
            fill: none;
        }

        /* Tags */
        .super-tag {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 800;
        }

        .tag-teal {
            background: rgba(20, 92, 84, .1);
            color: var(--brand-primary);
        }

        .super-table-empty {
            text-align: center;
            padding: 28px 16px;
            color: var(--text-muted);
            font-size: var(--dash-text-helper);
        }

        /* Responsiveness */
        @media (max-width: 1080px) {
            .super-form-layout {
                grid-template-columns: minmax(0, 1fr);
            }

            .super-form-aside {
                position: static;
            }
        }

        @media (max-width: 840px) {
            .super-matrix-split-layout {
                grid-template-columns: minmax(0, 1fr);
            }

            .super-matrix-groups-aside {
                border-right: none;
                border-bottom: 1px solid var(--surface-border);
                flex-direction: row;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                padding: 8px 10px;
                gap: 6px;
            }

            .super-groups-heading,
            .super-groups-total-box {
                display: none;
            }

            .super-groups-nav {
                flex-direction: row;
                overflow-x: auto;
                gap: 6px;
            }

            .super-group-nav-btn {
                flex-shrink: 0;
                white-space: nowrap;
            }

            .super-form-actions-card {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .super-actions-right {
                justify-content: space-between;
            }
        }

        @media (max-width: 600px) {
            .super-users-header {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .super-header-actions {
                justify-content: flex-start;
            }

            .super-matrix-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .super-matrix-search-box {
                width: 100%;
            }

            .super-matrix-batch-actions {
                justify-content: flex-end;
            }
        }
    </style>
@endsection
