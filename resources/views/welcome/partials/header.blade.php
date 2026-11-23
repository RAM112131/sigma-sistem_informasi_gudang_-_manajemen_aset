  <header class="site-header">
    <div class="header-inner">
      <a class="brand-pill" href="#beranda" aria-label="{{ $appName }} — Beranda">
        <svg class="brand-mark" viewBox="0 0 32 36" aria-hidden="true">
          <path d="M16 1 31 9.5 16 18 1 9.5Z" fill="#F5A12A"/>
          <path d="M1 9.5 16 18v17L1 26.5Z" fill="#0A8877"/>
          <path d="M31 9.5 16 18v17l15-8.5Z" fill="#07564E"/>
        </svg>
        <span class="brand-name">{{ $appName }}</span>
        <span class="brand-divider"></span>
        <span class="brand-description">Sistem Informasi Gudang &amp; Manajemen Aset</span>
      </a>
      <nav class="nav-pill" id="primary-navigation" aria-label="Navigasi utama">
        <a class="nav-link" href="#modul">Modul Fitur</a>
        <a class="nav-link" href="#alur">Alur Kerja</a>
        <a class="nav-login" href="{{ route('login') }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M13 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
          Masuk
        </a>
      </nav>
      <button class="menu-toggle" type="button" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="primary-navigation">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>
