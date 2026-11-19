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

<style>
    .app-header {
        height: var(--header-height);
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid var(--surface-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 32px;
        position: sticky;
        top: 0;
        z-index: 30;
    }

    @media (max-width: 640px) {
        .app-header {
            padding: 0 16px;
        }
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .mobile-toggle {
        display: none;
        background: none;
        border: none;
        cursor: pointer;
        color: var(--text-primary);
        padding: 6px;
        border-radius: var(--radius);
    }

    @media (max-width: 1024px) {
        .mobile-toggle {
            display: block;
        }
    }

    .header-brand-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: .9rem;
        font-weight: 700;
    }

    .system-title {
        color: var(--brand-primary);
        letter-spacing: -.02em;
    }

    .system-slash {
        color: var(--surface-border);
    }

    .system-sub {
        color: var(--text-muted);
        font-weight: 500;
        font-size: .82rem;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .header-action-btn {
        position: relative;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--surface-base);
        border: 1px solid var(--surface-border);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
        transition: all .15s;
    }

    .header-action-btn:hover {
        color: var(--brand-primary);
        border-color: var(--brand-primary);
    }

    .notification-dot {
        position: absolute;
        top: 6px;
        right: 6px;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--brand-accent);
        box-shadow: 0 0 0 2px #fff;
    }

    /* User Menu */
    .user-menu-wrapper {
        position: relative;
    }

    .user-menu-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        border-radius: var(--radius);
        transition: background .15s;
    }

    .user-menu-btn:hover {
        background: var(--surface-base);
    }

    .user-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: var(--brand-primary);
        color: #fff;
        font-weight: 700;
        font-size: .8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        letter-spacing: .05em;
    }

    .user-info {
        text-align: left;
        display: flex;
        flex-direction: column;
    }

    @media (max-width: 640px) {
        .user-info {
            display: none;
        }
    }

    .user-name {
        font-size: .85rem;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1.2;
    }

    .user-role {
        font-size: .72rem;
        color: var(--text-muted);
    }

    .user-dropdown {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        width: 220px;
        background: #fff;
        border: 1px solid var(--surface-border);
        border-radius: var(--radius);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        padding: 6px;
        z-index: 50;
    }

    .user-dropdown-header {
        padding: 10px;
        border-bottom: 1px solid var(--surface-border);
        margin-bottom: 4px;
    }

    .user-dropdown-name {
        font-weight: 700;
        font-size: .875rem;
    }

    .user-dropdown-email {
        font-size: .75rem;
        color: var(--text-muted);
    }

    .user-dropdown-item {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        padding: 8px 10px;
        font-size: .85rem;
        color: var(--text-primary);
        border-radius: 6px;
        background: none;
        border: none;
        cursor: pointer;
        transition: background .15s;
        text-decoration: none;
    }

    .user-dropdown-item:hover {
        background: var(--surface-base);
    }

    .user-dropdown-item.danger {
        color: var(--status-critical);
    }

    .user-dropdown-item.danger:hover {
        background: rgba(193, 68, 60, 0.08);
    }
</style>
