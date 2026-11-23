  <footer class="site-footer" id="masuk">
    <div class="footer-inner">
      <div class="footer-top">
        <div class="footer-brand">
          <svg class="brand-mark" viewBox="0 0 32 36" aria-hidden="true">
            <path d="M16 1 31 9.5 16 18 1 9.5Z" fill="#F5A12A"/>
            <path d="M1 9.5 16 18v17L1 26.5Z" fill="#0A9B87"/>
            <path d="M31 9.5 16 18v17l15-8.5Z" fill="#08776a"/>
          </svg>
          <span class="brand-name">{{ $appName }}</span>
          <span class="brand-divider"></span>
          <span class="footer-brand-text">Sistem Informasi Gudang &amp; Manajemen Aset</span>
        </div>
        <nav class="footer-links" aria-label="Navigasi footer">
          <a href="#modul">Modul Fitur</a>
          <a href="#alur">Alur Kerja</a>
          <a href="{{ route('login') }}">Masuk</a>
          <span class="version">v{{ $version }}</span>
        </nav>
      </div>
      <div class="footer-divider"></div>
      <div class="copyright">© {{ date('Y') }} {{ $appName }}. All rights reserved.</div>
    </div>
  </footer>
