    <section class="hero" id="beranda">
      <svg class="hero-left-ribbons" viewBox="0 0 360 600" preserveAspectRatio="none" aria-hidden="true">
        <defs>
          <linearGradient id="ribbon-gradient-1" gradientUnits="objectBoundingBox" x1="0%" y1="50%" x2="100%" y2="50%">
            <stop offset="0%" stop-color="#EAF4EF"/>
            <stop offset="25%" stop-color="#EAF4EF"/>
            <stop offset="100%" stop-color="#EAF4EF" stop-opacity="0"/>
          </linearGradient>
          <linearGradient id="ribbon-gradient-2" gradientUnits="objectBoundingBox" x1="0%" y1="50%" x2="100%" y2="50%">
            <stop offset="0%" stop-color="#D8ECE4"/>
            <stop offset="25%" stop-color="#D8ECE4"/>
            <stop offset="100%" stop-color="#D8ECE4" stop-opacity="0"/>
          </linearGradient>
          <linearGradient id="ribbon-gradient-3" gradientUnits="objectBoundingBox" x1="0%" y1="50%" x2="100%" y2="50%">
            <stop offset="0%" stop-color="#B9DED2"/>
            <stop offset="25%" stop-color="#B9DED2"/>
            <stop offset="100%" stop-color="#B9DED2" stop-opacity="0"/>
          </linearGradient>
          <linearGradient id="ribbon-gradient-4" gradientUnits="objectBoundingBox" x1="0%" y1="50%" x2="100%" y2="50%">
            <stop offset="0%" stop-color="#63B9A8"/>
            <stop offset="25%" stop-color="#63B9A8"/>
            <stop offset="100%" stop-color="#63B9A8" stop-opacity="0"/>
          </linearGradient>
          <linearGradient id="ribbon-gradient-5" gradientUnits="objectBoundingBox" x1="0%" y1="50%" x2="100%" y2="50%">
            <stop offset="0%" stop-color="#08796D"/>
            <stop offset="25%" stop-color="#08796D"/>
            <stop offset="100%" stop-color="#08796D" stop-opacity="0"/>
          </linearGradient>
        </defs>
        <path d="M0 0H270C236 54 194 82 139 112C80 144 31 176 0 218Z" fill="url(#ribbon-gradient-1)"/>
        <path d="M0 78C66 112 112 151 145 207C174 257 169 316 129 367C94 412 45 439 0 463Z" fill="url(#ribbon-gradient-2)"/>
        <path d="M0 255C52 280 79 322 99 369C108 401 116 438 123 466C126 484 129 500 130 516L0 578Z" fill="url(#ribbon-gradient-3)"/>
        <path d="M0 355C48 379 70 419 105 465C142 514 199 545 274 562C302 569 331 574 360 578L0 578Z" fill="url(#ribbon-gradient-4)"/>
        <path d="M0 455C63 485 123 511 190 530C248 546 302 555 360 565V600H0Z" fill="url(#ribbon-gradient-5)"/>
      </svg>
      <div class="hero-inner">
        <div class="hero-copy">
          <div class="hero-eyebrow"><span class="eyebrow-dot"></span> Solusi Manajemen Gudang Modern</div>
          <h1><span class="hero-title-line hero-title-first">Satu sistem untuk</span> <span class="highlight hero-title-line">seluruh <span class="underline-word">pencatatan<svg viewBox="0 0 220 18" preserveAspectRatio="none" aria-hidden="true"><path d="M3 13C58 2 145 2 217 11" fill="none" stroke="#FF941F" stroke-width="5" stroke-linecap="round"/></svg></span></span> <span class="hero-title-line hero-title-last">gudang</span></h1>
          <p class="hero-description">SIGMA memudahkan alur kerja harian tim gudang mulai dari proses penerimaan &amp; pengeluaran barang, cetak label produk, hingga reminder stok minimum &amp; kedaluwarsa tanpa kertas, tanpa entri ganda.</p>
          <div class="hero-actions">
            <a class="button button-primary" id="hero-cta" href="{{ route('login') }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 11 18-8-8 18-2-8-8-2Z"/><path d="m11 13 5-5"/></svg>
              Masuk ke Sistem <span aria-hidden="true">›</span>
            </a>
            <a class="button button-secondary" href="#modul">
              <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m10 7 7 5-7 5z" fill="#fff"/></svg>
              Lihat Modul
            </a>
          </div>
        </div>

        <div class="hero-visual">
          <div class="hero-image-stage" role="group" aria-roledescription="carousel" aria-label="Foto suasana operasional gudang SIGMA" aria-live="off">
            <img class="warehouse-hero-image is-active" src="{{ asset('images/image2.avif') }}" alt="Area gudang dengan rak, palet, dan forklift" width="1024" height="572" loading="eager" fetchpriority="high" decoding="async">
            <img class="warehouse-hero-image" data-src="{{ asset('images/image1.avif') }}" alt="Tim memproses produk di area produksi yang tertata" width="1024" height="559" decoding="async" aria-hidden="true">
            <img class="warehouse-hero-image" data-src="{{ asset('images/image3.avif') }}" alt="Petugas mencatat persediaan gudang menggunakan tablet" width="1024" height="559" decoding="async" aria-hidden="true">
            <div class="hero-dots" aria-hidden="true"><span class="is-active"></span><span></span><span></span></div>
          </div>

          <div class="module-stat">
            <svg class="stat-cube" viewBox="0 0 36 40" fill="none" aria-hidden="true">
              <path d="M18 2 32 10v18L18 36 4 28V10L18 2Z" stroke="white" stroke-width="3.5"/>
              <path d="M4 10 18 18 32 10M18 18v18M18 2v16" stroke="white" stroke-width="3.5"/>
            </svg>
            <div class="stat-main"><strong class="stat-number">{{ $activeModuleCount }}</strong><span class="stat-label">Modul Aktif</span></div>
            <span class="stat-signal" aria-hidden="true"><i></i><i></i><i></i><i></i></span>
          </div>
        </div>
      </div>
      <svg class="hero-bottom-wave" viewBox="0 0 1440 180" preserveAspectRatio="none" aria-hidden="true">
        <defs>
          <linearGradient id="modules-bg-match" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" stop-color="#fbfcf9"/>
            <stop offset="100%" stop-color="#f6f8f4"/>
          </linearGradient>
        </defs>

        <!-- Pita 1: Hijau Mint (#B9DED2) - Bergelombang penuh, tebal di tepi dan dipertipis drastis (super tipis ~3.5px) di tengah -->
        <path d="M 0,42 C 140,60 260,96 380,96 C 520,96 640,66 760,66 C 900,66 1020,98 1160,98 C 1260,98 1350,48 1440,32 L 1440,56 C 1350,70 1260,114 1160,114 C 1020,114 900,69.5 760,69.5 C 640,69.5 520,114 380,114 C 260,114 140,90 0,74 Z" fill="#B9DED2"/>

        <!-- Pita 2: Hijau Tua (#08796D) - Bergelombang tepat di bawah pita mint, dipertipis drastis (~3.5px) di tengah, lalu melebar dinamis di kanan -->
        <path d="M 0,74 C 140,90 260,114 380,114 C 520,114 640,69.5 760,69.5 C 900,69.5 1020,114 1160,114 C 1260,114 1350,70 1440,56 L 1440,98 C 1350,108 1260,136 1160,136 C 1020,136 900,73 760,73 C 640,73 520,134 380,134 C 260,134 140,118 0,108 Z" fill="#08796D"/>

        <!-- Transisi Bawah Selaras (#fbfcf9 -> #f6f8f4) - Warna latar belakang section modul tanpa garis putih terpisah -->
        <path d="M 0,108 C 140,118 260,134 380,134 C 520,134 640,73 760,73 C 900,73 1020,136 1160,136 C 1260,136 1350,108 1440,98 L 1440,180 L 0,180 Z" fill="url(#modules-bg-match)"/>
      </svg>
    </section>

