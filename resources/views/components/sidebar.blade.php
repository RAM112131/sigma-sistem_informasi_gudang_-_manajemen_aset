<aside class="app-sidebar" :class="{ 'show': sidebarOpen }" x-cloak>
    {{-- Sidebar Header --}}
    <div class="sidebar-header">
        <a href="{{ route('welcome') }}" class="sidebar-brand">
            <svg style="width: 26px; height: 26px;" width="26" height="26" viewBox="0 0 32 32" aria-hidden="true"
                class="icon-inline">
                <polygon points="16,2 29,9 29,23 16,30 3,23 3,9" fill="#145C54" />
                <polygon points="16,2 29,9 16,16 3,9" fill="#C97A2B" />
            </svg>
            <div class="sidebar-brand-text">
                <span class="brand-title">SIGMA</span>
                <span class="brand-subtitle">Sistem Informasi Gudang &amp; Manajemen Aset</span>
            </div>
        </a>
        <button type="button" class="sidebar-close-btn" @click="sidebarOpen = false">&times;</button>
    </div>

    @php
        $u = Auth::user();
        $isSuperAdmin =
            $u && method_exists($u, 'hasRole') && ($u->hasRole('Super Admin') || $u->hasRole('super_admin'));
        $userGudangs = $u && !$isSuperAdmin ? $u->gudang()->orderBy('nama')->get() : collect();
        $selectedGudangId = request()->query('gudang_id', session('active_gudang_id'));

        if ($selectedGudangId && $userGudangs->contains('id', (int) $selectedGudangId)) {
            session(['active_gudang_id' => (int) $selectedGudangId]);
        }

        $activeGudang =
            $userGudangs->firstWhere('id', (int) ($selectedGudangId ?? $userGudangs->first()?->id)) ??
            $userGudangs->first();
        $isStoreGudang = $activeGudang && in_array($activeGudang->jenis, ['holding', 'global_master', 'gudang_store']);

        $isAdminStore =
            $u &&
            method_exists($u, 'hasRole') &&
            ($u->hasRole('Admin Gudang Store') || $u->hasRole('admin_gudang_store'));
        $isAdminGU =
            $u &&
            method_exists($u, 'hasRole') &&
            ($u->hasRole('Admin Gudang Umum') || $u->hasRole('admin_gudang_umum'));
        $isRecordingGU =
            $u &&
            method_exists($u, 'hasRole') &&
            ($u->hasRole('Recording Gudang Umum') || $u->hasRole('recording_gudang_umum'));

        $dashboardUrl = route('pending-access');
        $dashboardLabel = 'Akses Belum Dikonfigurasi';
        if ($isSuperAdmin) {
            $dashboardUrl = route('admin.dashboard');
            $dashboardLabel = 'Dashboard';
        } elseif ($isAdminStore) {
            $dashboardUrl = route('store.dashboard');
            $dashboardLabel = 'Dashboard Store';
        } elseif ($isRecordingGU) {
            $dashboardUrl = route('unit-bisnis.recording-gu.dashboard');
            $dashboardLabel = 'Dashboard Tugas Harian';
        } elseif ($isAdminGU) {
            $dashboardUrl = route('unit-bisnis.admin-gu.dashboard');
            $dashboardLabel = 'Dashboard Admin GU';
        }

        // -----------------------------------------------------------------------
        // Permission helpers: mendukung KEDUA skema naming
        //   DB baru: resource.action (penerimaan.create, barang.read …)
        //   Lama   : resource.verb  (penerimaan.input,  barang.view …)
        // -----------------------------------------------------------------------
        $canUserRead = $isSuperAdmin || $u?->can('user.read') || $u?->can('user.manage');
        $canRoleRead = $isSuperAdmin || $u?->can('role.read') || $u?->can('role.manage');
        $canGudangRead = $isSuperAdmin || $u?->can('gudang.read') || $u?->can('gudang.manage');
        $canAuditRead = $isSuperAdmin || $u?->can('audit.read') || $u?->can('audit.view');
        $canBarangRead =
            $isSuperAdmin || $u?->can('barang.read') || $u?->can('barang.view') || $u?->can('barang.manage');
        $canKategoriRead = $isSuperAdmin || $u?->can('kategori.read') || $u?->can('kategori.manage');
        $canSatuanRead = $isSuperAdmin || $u?->can('satuan.read') || $u?->can('satuan.manage');
        $canAreaRead = $isSuperAdmin || $u?->can('area.read') || $u?->can('area.manage');
        $canStoreStok =
            $isSuperAdmin || $u?->can('store_stok.read') || $u?->can('store.read') || $u?->can('store.view');
        $canStorePenerima =
            $isSuperAdmin ||
            $u?->can('store_penerimaan.read') ||
            $u?->can('store_penerimaan.create') ||
            $u?->can('store.create') ||
            $u?->can('store.penerimaan');
        $canStoreDistrib =
            $isSuperAdmin ||
            $u?->can('store_pengeluaran.read') ||
            $u?->can('store_pengeluaran.create') ||
            $u?->can('store.distribusi');
        $canStorePermintaan =
            $isSuperAdmin ||
            $u?->can('store_pengeluaran.read') ||
            $u?->can('store_pengeluaran.create') ||
            $u?->can('store.distribusi');
        $canStoreLaporan =
            $isSuperAdmin ||
            $u?->can('store_laporan.read') ||
            (($isStoreGudang || $isAdminStore) && ($u?->can('laporan.read') || $u?->can('laporan.view')));
        $canLaporanRead = $isSuperAdmin || $u?->can('laporan.read') || $u?->can('laporan.view');
        $canPemindahan =
            $isSuperAdmin ||
            $u?->can('pemindahan.read') ||
            $u?->can('pemindahan.create') ||
            $u?->can('pemindahan.update') ||
            $u?->can('pemindahan.terima');
        $canPermintaan = $isSuperAdmin || $u?->can('permintaan_barang.read') || $u?->can('permintaan_barang.create');
        $canOpnameInput =
            $isSuperAdmin ||
            $u?->can('stok_opname.read') ||
            $u?->can('stok_opname.create') ||
            $u?->can('stok_opname.input');
        $canOpnameFinal = $isSuperAdmin || $u?->can('stok_opname.finalisasi') || $u?->can('stok_opname.update');
        $canPenerimaan =
            $isSuperAdmin ||
            $u?->can('penerimaan.read') ||
            $u?->can('penerimaan.create') ||
            $u?->can('penerimaan.input');
        $canPengeluaran =
            $isSuperAdmin ||
            $u?->can('pengeluaran.read') ||
            $u?->can('pengeluaran.create') ||
            $u?->can('pengeluaran.input');
        $canRetur = $isSuperAdmin || $u?->can('retur.read') || $u?->can('retur.create') || $u?->can('retur.input');
        $canLabel = $isSuperAdmin || $u?->can('label.read') || $u?->can('label.create') || $u?->can('label.cetak');
        $canStokUnit =
            $isSuperAdmin ||
            $u?->can('stokunitbisnis.read') ||
            $u?->can('stokunitbisnis.view') ||
            $u?->can('barang.read') ||
            $u?->can('barang.view');
    @endphp

    {{-- Sidebar Nav Scroll Area --}}
    <nav class="sidebar-nav">

        {{-- Group 1: Dashboard Utama --}}
        <div class="nav-group">
            <div class="nav-group-title">Utama</div>
            <a href="{{ $dashboardUrl }}"
                class="nav-item {{ (request()->routeIs('unit-bisnis.admin-gu.dashboard') || request()->routeIs('unit-bisnis.recording-gu.dashboard') || request()->routeIs('store.dashboard') || request()->routeIs('admin.dashboard')) && !request()->query('panel') ? 'active' : '' }}">
                <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" class="icon-inline">
                    <rect x="3" y="3" width="7" height="7" />
                    <rect x="14" y="3" width="7" height="7" />
                    <rect x="14" y="14" width="7" height="7" />
                    <rect x="3" y="14" width="7" height="7" />
                </svg>
                <span>{{ $dashboardLabel }}</span>
            </a>
        </div>

        @if ($isSuperAdmin || $canUserRead || $canRoleRead || $canGudangRead || $canAuditRead)
            {{-- Group 2: Super Admin / Master Pengguna & Sistem --}}
            <div class="nav-group">
                <div class="nav-group-title">Sistem &amp; Pengguna</div>

                @if ($canUserRead)
                    <a href="{{ route('admin.users.index') }}"
                        class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                        <span>Kelola User</span>
                    </a>
                @endif

                @if ($canRoleRead)
                    <a href="{{ route('admin.roles.index') }}"
                        class="nav-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                        <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        </svg>
                        <span>Role &amp; Permission Matrix</span>
                    </a>
                @endif

                @if ($canGudangRead)
                    <a href="{{ route('admin.gudang.index') }}"
                        class="nav-item {{ request()->routeIs('admin.gudang.*') ? 'active' : '' }}">
                        <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                            <polyline points="9 22 9 12 15 12 15 22" />
                        </svg>
                        <span>Master Lokasi Gudang</span>
                    </a>
                @endif

                @if ($canAuditRead)
                    <a href="{{ route('admin.activity-log.index') }}"
                        class="nav-item {{ request()->routeIs('admin.activity-log.*') ? 'active' : '' }}">
                        <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                            <polyline points="12 8 12 12 14 14" />
                            <circle cx="12" cy="12" r="10" />
                        </svg>
                        <span>Log Audit Aktivitas</span>
                    </a>
                @endif
            </div>
        @endif

        {{-- Group 3: Master Data & Katalog --}}
        @if ($canBarangRead || $canKategoriRead || $canSatuanRead || $canAreaRead)
            <div class="nav-group">
                <div class="nav-group-title">Master Data &amp; Katalog</div>

                <a href="{{ route('master.barang.index') }}"
                    class="nav-item {{ request()->routeIs('master.barang.*') ? 'active' : '' }}">
                    <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                        <ellipse cx="12" cy="5" rx="9" ry="3" />
                        <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" />
                        <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
                    </svg>
                    <span>Master Barang</span>
                </a>

            </div>
        @endif

        {{-- Group 4: Gudang Store (Armada / Holding) --}}
        @if ($canStoreStok || $canStorePenerima || $canStoreDistrib || $canStoreLaporan || $canStorePermintaan)
            <div class="nav-group">
                <div class="nav-group-title">Gudang Store (Armada)</div>

                @if ($canStoreStok)
                    <a href="{{ route('store.stok.index') }}"
                        class="nav-item {{ request()->routeIs('store.stok.*') ? 'active' : '' }}">
                        <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                            <ellipse cx="12" cy="5" rx="9" ry="3" />
                            <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" />
                            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
                        </svg>
                        <span>Stok Gudang Store</span>
                    </a>
                @endif

                @if ($canStorePenerima)
                    <a href="{{ route('store.penerimaan.index') }}"
                        class="nav-item {{ request()->routeIs('store.penerimaan.*') ? 'active' : '' }}">
                        <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg>
                        <span>Penerimaan Barang</span>
                    </a>
                @endif

                @if ($canStorePermintaan)
                    <a href="{{ route('store.permintaan.index') }}"
                        class="nav-item {{ request()->routeIs('store.permintaan.*') ? 'active' : '' }}">
                        <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <path d="M14 2v6h6M8 13h8M8 17h8" />
                        </svg>
                        <span>Permintaan Barang</span>
                    </a>
                @endif

                @if ($canStoreDistrib)
                    <a href="{{ route('store.pengeluaran.index', ['tab' => 'pemindahan']) }}"
                        class="nav-item {{ request()->routeIs('store.pengeluaran.*') ? 'active' : '' }}">
                        <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                        </svg>
                        <span>Pengeluaran Barang</span>
                    </a>
                @endif

                @if ($canStoreLaporan)
                    <a href="{{ route('store.laporan.index') }}"
                        class="nav-item {{ request()->routeIs('store.laporan.*') ? 'active' : '' }}">
                        <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                            <polyline points="12 8 12 12 14 14" />
                            <circle cx="12" cy="12" r="10" />
                        </svg>
                        <span>Laporan Gudang Store</span>
                    </a>
                @endif
            </div>
        @endif

        @if ($isAdminGU && ($isRecordingGU || $isSuperAdmin))
            <div class="nav-group">
                <div class="nav-group-title">Permintaan &amp; Penerimaan</div>

                <a href="{{ route('unit-bisnis.admin-gu.permintaan.index') }}"
                    class="nav-item {{ request()->routeIs('unit-bisnis.admin-gu.permintaan.*') ? 'active' : '' }}">
                    <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" class="icon-inline"
                        aria-hidden="true">
                        <path d="M12 3v12m0 0 4-4m-4 4-4-4" />
                        <path d="M5 13v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6" />
                    </svg>
                    <span>Permintaan Barang ke Store</span>
                </a>

            </div>
        @endif

        @if ($isAdminGU && !$isRecordingGU && !$isSuperAdmin)
            <div class="nav-group">
                <div class="nav-group-title">Persediaan</div>

                <a href="{{ route('unit-bisnis.stok.index') }}"
                    class="nav-item {{ request()->routeIs('unit-bisnis.*stok.*') || request()->routeIs('unit-bisnis.stok.*') ? 'active' : '' }}">
                    <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" class="icon-inline"
                        aria-hidden="true">
                        <path d="m12 3 9 5-9 5-9-5 9-5Z" />
                        <path d="m3 12 9 5 9-5M3 16l9 5 9-5" />
                    </svg>
                    <span>Stok Barang</span>
                </a>

                <a href="{{ route('unit-bisnis.admin-gu.stok-opname.index') }}"
                    class="nav-item {{ request()->routeIs('unit-bisnis.admin-gu.stok-opname.*') ? 'active' : '' }}">
                    <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" class="icon-inline"
                        aria-hidden="true">
                        <path d="M8 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-3" />
                        <path d="M8 2h6v4H8zM7 11h6M7 15h4" />
                        <circle cx="18" cy="14" r="3" />
                        <path d="m20.2 16.2 2 2" />
                    </svg>
                    <span>Stok Opname Bulanan</span>
                </a>
            </div>

            <div class="nav-group">
                <div class="nav-group-title">Permintaan &amp; Verifikasi</div>

                <a href="{{ route('unit-bisnis.admin-gu.permintaan.index') }}"
                    class="nav-item {{ request()->routeIs('unit-bisnis.admin-gu.permintaan.*') ? 'active' : '' }}">
                    <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" class="icon-inline"
                        aria-hidden="true">
                        <path d="M4 7h12a3 3 0 0 1 3 3v1" />
                        <path d="m16 8 3 3 3-3" />
                        <path d="M20 17H8a3 3 0 0 1-3-3v-1" />
                        <path d="m8 16-3-3-3 3" />
                    </svg>
                    <span>Permintaan Barang ke Store</span>
                </a>

                <a href="{{ route('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.index') }}"
                    class="nav-item {{ request()->routeIs('unit-bisnis.admin-gu.rekonsiliasi-penerimaan.*') ? 'active' : '' }}">
                    <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" class="icon-inline"
                        aria-hidden="true">
                        <path d="M4 4h16v16H4z" />
                        <path d="M8 12h3l2 2 4-5" />
                    </svg>
                    <span>Verifikasi Penerimaan</span>
                </a>

                <a href="{{ route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.index') }}"
                    class="nav-item {{ request()->routeIs('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.*') ? 'active' : '' }}">
                    <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" class="icon-inline"
                        aria-hidden="true">
                        <path d="M4 7h16M4 17h16" />
                        <path d="m8 4-3 3 3 3m8 4 3 3-3 3" />
                    </svg>
                    <span>Verifikasi Pengeluaran &amp; Penerimaan</span>
                </a>
            </div>

            <div class="nav-group">
                <div class="nav-group-title">Laporan</div>

                <a href="{{ route('unit-bisnis.admin-gu.laporan.index') }}"
                    class="nav-item {{ request()->routeIs('unit-bisnis.admin-gu.laporan.*') ? 'active' : '' }}">
                    <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" class="icon-inline"
                        aria-hidden="true">
                        <path d="M4 19V5M4 19h16" />
                        <path d="M8 16v-4m4 4V8m4 8v-7" />
                    </svg>
                    <span>Rekapitulasi Laporan Matriks</span>
                </a>
            </div>
        @endif

        {{-- Group 5: Operasional Unit Bisnis (Admin GU & Recording GU) --}}
        @if (
            ($isAdminGU && ($isRecordingGU || $isSuperAdmin)) ||
                $isSuperAdmin ||
                (!$isStoreGudang &&
                    !$isAdminGU &&
                    ($canPermintaan ||
                        $canPemindahan ||
                        $canOpnameInput ||
                        $canOpnameFinal ||
                        $canPenerimaan ||
                        $canPengeluaran ||
                        $canLabel ||
                        $canRetur)))
            <div class="nav-group">
                <div class="nav-group-title">
                    @if ($isAdminGU)
                        Operasional Admin GU
                    @elseif($isRecordingGU)
                        Operasional Recording GU
                    @else
                        Operasional Unit Bisnis
                    @endif
                </div>

                {{-- 1. Stok Barang (Tersedia untuk Admin GU, Recording GU, dan Super Admin) --}}
                <a href="{{ route('unit-bisnis.stok.index') }}"
                    class="nav-item {{ request()->routeIs('unit-bisnis.*stok.*') || request()->routeIs('unit-bisnis.stok.*') ? 'active' : '' }}">
                    <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                        <ellipse cx="12" cy="5" rx="9" ry="3" />
                        <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" />
                        <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
                    </svg>
                    <span>Stok Barang</span>
                </a>

                {{-- Permintaan tersedia di grup manajerial Admin GU atau grup operasional lainnya. --}}
                @if (($canPermintaan || $isAdminGU || $isSuperAdmin) && !$isAdminGU)
                    <a href="{{ route('unit-bisnis.admin-gu.permintaan.index') }}"
                        class="nav-item {{ request()->routeIs('unit-bisnis.admin-gu.permintaan.*') ? 'active' : '' }}">
                        <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <path d="M14 2v6h6M8 13h8M8 17h8" />
                        </svg>
                        <span>Permintaan Barang ke Store</span>
                    </a>
                @endif

                {{-- Penerimaan fisik hanya tersedia untuk Recording GU (dan Super Admin). --}}
                @if ($isRecordingGU || $isSuperAdmin)
                    <a href="{{ route('unit-bisnis.recording-gu.penerimaan.index') }}"
                        class="nav-item {{ request()->routeIs('unit-bisnis.recording-gu.penerimaan.*') || request()->routeIs('unit-bisnis.penerimaan.*') ? 'active' : '' }}">
                        <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                            <rect x="1" y="3" width="15" height="13" rx="1" />
                            <path d="M16 8h4l3 4v5h-7V8z" />
                            <circle cx="5.5" cy="18.5" r="2.5" />
                            <circle cx="18.5" cy="18.5" r="2.5" />
                        </svg>
                        <span>Penerimaan Fisik Barang</span>
                    </a>
                @endif

                {{-- Physical transaction entry is handled by Recording GU. --}}
                @if ($isRecordingGU || $isSuperAdmin)
                    <a href="{{ route('unit-bisnis.recording-gu.pengeluaran.index') }}"
                        class="nav-item {{ request()->routeIs('unit-bisnis.recording-gu.pengeluaran.*') || request()->routeIs('unit-bisnis.pengeluaran.*') ? 'active' : '' }}">
                        <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                            <rect x="1" y="3" width="15" height="13" rx="1" />
                            <path d="M16 8h4l3 4v5h-7V8z" />
                            <circle cx="5.5" cy="18.5" r="2.5" />
                            <circle cx="18.5" cy="18.5" r="2.5" />
                            <path d="M7 9l3-3-3-3" />
                        </svg>
                        <span>Pengeluaran &amp; Pengembalian (BPB)</span>
                    </a>
                @endif

                {{-- Menu Manajerial, Biaya & Kontrol (Khusus Admin Gudang Umum & Super Admin) --}}
                @if ($isAdminGU || $isSuperAdmin)
                    <a href="{{ route('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.index') }}"
                        class="nav-item {{ request()->routeIs('unit-bisnis.admin-gu.rekonsiliasi-pengeluaran.*') ? 'active' : '' }}">
                        <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline"
                            aria-hidden="true">
                            <path d="M4 7h16M4 17h16" />
                            <path d="m8 4-3 3 3 3m8 4 3 3-3 3" />
                        </svg>
                        <span>Verifikasi Pengeluaran & Penerimaan</span>
                    </a>
                    <a href="{{ route('unit-bisnis.admin-gu.stok-opname.index') }}"
                        class="nav-item {{ request()->routeIs('unit-bisnis.admin-gu.stok-opname.*') ? 'active' : '' }}">
                        <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline"
                            aria-hidden="true">
                            <path d="M8 4H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-3" />
                            <path d="M8 2h6v4H8zM7 11h6M7 15h4" />
                            <circle cx="18" cy="14" r="3" />
                            <path d="m20.2 16.2 2 2" />
                        </svg>
                        <span>Stok Opname Bulanan</span>
                    </a>
                    <a href="{{ route('unit-bisnis.admin-gu.laporan.index') }}"
                        class="nav-item {{ request()->routeIs('unit-bisnis.admin-gu.laporan.*') ? 'active' : '' }}">
                        <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline"
                            aria-hidden="true">
                            <path d="M4 19V5M4 19h16" />
                            <path d="M8 16v-4m4 4V8m4 8v-7" />
                        </svg>
                        <span>Rekapitulasi Laporan Matriks</span>
                    </a>
                @endif
            </div>
        @endif

    </nav>

    {{-- Sidebar Footer --}}
    <div class="sidebar-footer">
        <div class="user-profile-widget">
            <div class="user-avatar">
                {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
            </div>
            <div class="user-info">
                <span class="user-name">{{ Auth::user()->name ?? 'User' }}</span>
                <span class="user-role">
                    {{ Auth::user()->roles->first()->name ?? 'Pegawai' }}
                </span>
            </div>
        </div>
    </div>
</aside>
