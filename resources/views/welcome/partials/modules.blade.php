    <section class="modules-section" id="modul">
      <div class="section-inner">
        <div class="section-heading">
          <div>
            <div class="section-kicker">Fitur Sistem</div>
            <h2>Modul yang Tersedia</h2>
            <p class="section-description">Seluruh modul di bawah ini dirancang untuk operasional gudang dan pencatatan tanpa ribet, tanpa batas.</p>
          </div>
        </div>

        <div class="module-grid">
          @foreach ($modules as $module)
            <a class="module-card" href="{{ route($module['route']) }}">
              <span class="card-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M5 12h13m-5-5 5 5-5 5"/></svg></span>
              <div class="module-icon">
                <svg viewBox="0 0 24 24">
                  @switch ($module['ikon'])
                    @case('database')
                      <ellipse cx="12" cy="5" rx="8.5" ry="3"/><path d="M3.5 5v14c0 1.7 17 1.7 17 0V5M3.5 10c0 1.7 17 1.7 17 0M3.5 15c0 1.7 17 1.7 17 0"/>
                      @break
                    @case('clipboard-check')
                    @case('tag')
                      <path d="M12 3 21 11l-9 10-9-10 9-8Z"/><path d="M8 8h.01M14 7l3 3"/>
                      @break
                    @case('truck-incoming')
                      <path d="M2 6h13v12H2zM15 10h4l3 4v4h-7z"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="19" r="2"/>
                      @break
                    @case('truck-outgoing')
                      <path d="m12 2 9 5v10l-9 5-9-5V7l9-5Z"/><path d="m3 7 9 5 9-5M12 12v10"/>
                      @break
                    @case('return')
                      <path d="M4 12a8 8 0 1 0 2.3-5.6L4 9"/><path d="M4 4v5h5"/>
                      @break
                    @case('shopping-cart')
                      <path d="M2 3h2l2.5 13h13L22 7H5"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/>
                      @break
                    @case('chart-bar')
                      <path d="M3 20h18M6 17V9M11 17V4M16 17v-6M21 17V7"/>
                      @break
                    @default
                      <path d="m12 2 9 5v10l-9 5-9-5V7l9-5Z"/><path d="m3 7 9 5 9-5M12 12v10"/>
                  @endswitch
                </svg>
              </div>
              <h3>{{ $module['nama'] }}</h3>
              <p>{{ $module['deskripsi'] }}</p>
            </a>
          @endforeach
        </div>
      </div>
    </section>

