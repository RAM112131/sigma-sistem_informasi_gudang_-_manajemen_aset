@php
    $user = Auth::user();
    $isSuperAdmin =
        $user && method_exists($user, 'hasRole') && ($user->hasRole('Super Admin') || $user->hasRole('super_admin'));

    $hasAnyWarehouseRole =
        $user &&
        method_exists($user, 'hasAnyRole') &&
        $user->hasAnyRole([
            'Admin Gudang Umum',
            'admin_gudang_umum',
            'Recording Gudang Umum',
            'recording_gudang_umum',
            'Admin Gudang Store',
            'admin_gudang_store',
        ]);

    $userGudangs = $user && !$isSuperAdmin
        ? ($user->relationLoaded('gudang') ? $user->gudang->sortBy('nama') : $user->gudang()->orderBy('nama')->get())
        : collect();

    if ($user && !$user->relationLoaded('gudang') && $userGudangs->isNotEmpty()) {
        $user->setRelation('gudang', $userGudangs);
    }
    $selectedGudangId = request()->query('gudang_id', session('active_gudang_id', $userGudangs->first()?->id));

    if ($selectedGudangId && $userGudangs->contains('id', (int) $selectedGudangId)) {
        session(['active_gudang_id' => (int) $selectedGudangId]);
    }

    $activeGudangId = $selectedGudangId ?? $userGudangs->first()?->id;
    $activeGudang = $userGudangs->firstWhere('id', (int) $activeGudangId) ?? $userGudangs->first();
@endphp

@if ($isSuperAdmin || (request()->is('admin*') && $isSuperAdmin))
    {{-- Super Admin Scope: Global System Management --}}
    <div class="gudang-btn-static super-admin-badge" title="Super Admin mengelola sistem secara global">
        <svg style="width: 15px; height: 15px;" width="15" height="15" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" class="icon-inline">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
        </svg>
        <span class="gudang-name">Sistem Global</span>
        {{-- <span class="gudang-tag brand">Super Admin</span> --}}
    </div>
@elseif ($userGudangs->count() > 1 && ($hasAnyWarehouseRole || !$isSuperAdmin))
    {{-- Multi-warehouse user: Show dynamic Active Warehouse Selector based on assigned warehouse scope --}}
    <div class="active-gudang-selector" x-data="{ open: false }" @click.away="open = false"
        @keydown.escape.window="open = false">
        <button type="button" class="gudang-btn" @click="open = !open" :aria-expanded="open.toString()"
            aria-label="Selektor Gudang Aktif">
            <svg style="width: 16px; height: 16px;" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" class="icon-inline">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                <polyline points="9 22 9 12 15 12 15 22" />
            </svg>
            <span class="gudang-name">{{ $activeGudang?->nama ?? 'Gudang Sukahati' }}</span>
        </button>

        <div class="gudang-dropdown" x-show="open" x-cloak x-transition>
            <div class="gudang-dropdown-head">Pilih Gudang Aktif</div>

            @foreach ($userGudangs as $gudangItem)
                <a href="{{ request()->fullUrlWithQuery(['gudang_id' => $gudangItem->id]) }}"
                    class="gudang-item {{ $activeGudang?->id === $gudangItem->id ? 'active' : '' }}"
                    @click="open = false">
                    <div class="gudang-item-icon {{ $activeGudang?->id === $gudangItem->id ? '' : 'placeholder' }}">
                        @if ($activeGudang?->id === $gudangItem->id)
                            <svg style="width: 16px; height: 16px;" width="16" height="16" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        @endif
                    </div>
                    <div class="gudang-item-info">
                        <div class="gudang-item-title">{{ $gudangItem->nama }}</div>
                        <div class="gudang-item-sub">
                            {{ $gudangItem->jenis === 'gudang_store' ? 'Gudang Store (Katalog Terpisah)' : 'Gudang Umum Operasional' }}
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@else
    {{-- Single warehouse user or unassigned user: show the effective warehouse context or fallback label --}}
    @php
        $singleGudang = $userGudangs->first();
        $fallbackLabel = $isSuperAdmin ? 'Sistem Global' : $singleGudang?->nama ?? 'Gudang Belum Ditugaskan';
    @endphp
    <div class="gudang-btn-static" title="Konteks Gudang Penugasan Anda">
        <svg style="width: 16px; height: 16px;" width="16" height="16" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" class="icon-inline">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
            <polyline points="9 22 9 12 15 12 15 22" />
        </svg>
        <span class="gudang-name">{{ $fallbackLabel }}</span>
    </div>
@endif
