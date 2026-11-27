<header class="app-header">
    <div class="header-left">
        {{-- Hamburger trigger for mobile sidebar --}}
        <button type="button" class="mobile-toggle" @click="sidebarOpen = !sidebarOpen"
            aria-label="Toggle navigasi sidebar">
            <svg style="width: 20px; height: 20px;" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" class="icon-inline">
                <line x1="3" y1="12" x2="21" y2="12" />
                <line x1="3" y1="6" x2="21" y2="6" />
                <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
        </button>

    </div>

    <div class="header-right">
        {{-- Active Gudang Context Selector --}}
        @include('components.active-gudang-selector')

        {{-- Notifications Bell Pill --}}
        <a href="#" class="header-action-btn" title="Notifikasi Reminder">
            <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" class="icon-inline">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
            </svg>
            <span class="notification-dot"></span>
        </a>

        {{-- User Profile Dropdown --}}
        <div class="user-menu-wrapper" x-data="{ userOpen: false }">
            <button type="button" class="user-menu-btn" @click="userOpen = !userOpen" @click.away="userOpen = false">
                <div class="user-avatar">
                    {{ strtoupper(substr(Auth::user()?->name ?? 'SA', 0, 2)) }}
                </div>
                <div class="user-info">
                    <span class="user-name">{{ Auth::user()?->name ?? 'Super Admin' }}</span>
                    <span class="user-role">{{ Auth::user()?->roles?->first()?->name ?? 'Super Admin' }}</span>
                </div>
                <svg style="width: 14px; height: 14px;" width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" class="icon-inline">
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>

            <div class="user-dropdown" x-show="userOpen" x-cloak x-transition>
                <div class="user-dropdown-header">
                    <div class="user-dropdown-name">{{ Auth::user()?->name ?? 'Super Admin' }}</div>
                </div>

                {{-- <a href="{{ route('welcome') }}" class="user-dropdown-item">
          <svg style="width: 16px; height: 16px;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
          </svg>
          Halaman Utama Publik
        </a> --}}

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="user-dropdown-item danger">
                        <svg style="width: 16px; height: 16px;" width="16" height="16" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <polyline points="16 17 21 12 16 7" />
                            <line x1="21" y1="12" x2="9" y2="12" />
                        </svg>
                        Keluar Sistem
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
