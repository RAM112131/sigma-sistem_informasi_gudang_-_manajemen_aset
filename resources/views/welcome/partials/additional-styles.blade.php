<style>
  /* ---------- Responsive refinement ---------- */
  .menu-toggle {
    display: none;
    width: 43px; height: 43px; flex: 0 0 auto;
    align-items: center; justify-content: center; flex-direction: column; gap: 5px;
    border: 1px solid rgba(255,255,255,.85); border-radius: 50%;
    background: rgba(255,255,255,.82); color: var(--teal);
    box-shadow: 0 7px 22px rgba(33,82,75,.08);
    cursor: pointer; pointer-events: auto;
  }
  .menu-toggle span { display: block; width: 17px; height: 2px; border-radius: 2px; background: currentColor; transition: transform .2s ease, opacity .2s ease; }
  .menu-toggle.is-active span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
  .menu-toggle.is-active span:nth-child(2) { opacity: 0; }
  .menu-toggle.is-active span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }
  .menu-toggle:focus-visible, .nav-login:focus-visible, .button:focus-visible, a:focus-visible { outline: 3px solid rgba(255,148,31,.65); outline-offset: 3px; }

  @media (max-width: 1100px) {
    .hero-inner { width: min(920px, calc(100% - 64px)); }
    .hero-visual { margin-right: -28px; }
    .section-inner { width: min(900px, calc(100% - 64px)); }
    .workflow-panel, .footer-inner { width: min(940px, calc(100% - 64px)); }
  }
  @media (min-width: 701px) and (max-width: 900px) {
    .brand-description { display: block; font-size: 8px; }
  }
  @media (min-width: 901px) and (max-width: 1199px) {
    .nav-pill { justify-content: flex-end; gap: 10px; padding-left: 8px; }
    .nav-login { margin-left: 2px; }
  }
  @media (max-width: 900px) {
    .header-inner { width: calc(100% - 48px); gap: 12px; }
    .brand-description { font-size: 8px; }
    .nav-pill { gap: 16px; }
    .hero-inner { width: calc(100% - 56px); grid-template-columns: .95fr 1.05fr; gap: 0; }
    .hero-visual { height: 315px; margin-left: -20px; margin-right: -35px; }
    .module-stat { transform: scale(.9); transform-origin: top right; }
    .workflow-steps { gap: 30px; }
    .step-connector { right: -28px; }
  }
  @media (min-width: 761px) and (max-width: 900px) {
    .module-stat { right: 35px; }
    .hero-dots { right: 42px; }
  }
  @media (max-width: 760px) {
    .hero { min-height: 0; padding: 105px 0 36px; }
    .hero-inner { display: flex; flex-direction: column; align-items: stretch; gap: 4px; }
    .hero-copy { max-width: 620px; }
    h1 { max-width: 580px; font-size: clamp(38px, 7vw, 54px); }
    .hero-description { max-width: 570px; font-size: 13px; }
    .hero-visual { width: min(100%, 620px); height: clamp(250px, 48vw, 350px); margin: 0 auto; }
    .warehouse-hero-image { width: 108%; left: -4%; }
    .module-stat { transform: none; }
    .module-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
    .workflow-panel { padding: 28px 24px 25px; }
    .workflow-steps { grid-template-columns: 1fr; gap: 20px; max-width: 560px; margin-inline: auto; }
    .workflow-step { min-height: 126px; padding: 22px 22px 17px; }
    .workflow-step p { max-width: 430px; margin-inline: auto; }
    .step-connector { right: auto; left: 50%; top: auto; bottom: -17px; transform: translateX(-50%) rotate(90deg); width: 27px; height: 27px; }
    .workflow-step:last-child .step-connector { display: none; }
    .workflow-panel::before { width: 100%; opacity: .4; }
    .workflow-bg-media { width: 100%; height: 180px; opacity: .45; border-top-left-radius: 42px; }
  }
  @media (max-width: 700px) {
    .site-header { top: 12px; }
    .header-inner { width: calc(100% - 28px); position: relative; }
    .brand-pill { min-height: 43px; padding: 0 12px; gap: 8px; }
    .brand-description, .brand-divider { display: none; }
    .menu-toggle { display: flex; }
    .nav-pill {
      display: none; position: absolute; top: 53px; right: 0; z-index: 30;
      width: min(230px, calc(100vw - 28px)); padding: 8px;
      align-items: stretch; flex-direction: column; gap: 0;
      border-radius: 17px; background: rgba(255,255,255,.96);
      border: 1px solid rgba(7,91,82,.12); box-shadow: 0 16px 38px rgba(9,54,48,.16);
    }
    .nav-pill.is-open { display: flex; }
    .nav-link { display: block; padding: 12px 11px; border-radius: 9px; font-size: 12px; }
    .nav-link:hover { background: #edf6f2; }
    .nav-login { width: 100%; min-height: 40px; margin-top: 5px; font-size: 11px; }
    .hero { padding-top: 91px; }
    .section-heading { align-items: flex-start; }
    .all-modules { margin-top: 21px; }
    .footer-top { align-items: flex-start; }
  }
  @media (max-width: 480px) {
    .hero-inner { width: calc(100% - 38px); }
    .hero-eyebrow { font-size: 9px; }
    h1 { font-size: clamp(34px, 9.5vw, 43px); letter-spacing: -1.7px; }
    .hero-description { font-size: 11.5px; line-height: 1.55; }
    .hero-actions { gap: 9px; }
    .button { min-height: 40px; padding-inline: 13px; font-size: 9.5px; }
    .button-primary { min-width: 0; }
    .button-secondary { min-width: 0; }
    .hero-visual { height: 230px; width: calc(100% + 10px); margin-inline: -5px; }
    .warehouse-hero-image { width: 118%; left: -9%; }
    .module-stat { top: 12px; right: 2px; width: 116px; min-height: 67px; padding: 8px; gap: 6px; }
    .stat-cube { width: 21px; height: 24px; }
    .stat-number { font-size: 22px; }
    .stat-label { font-size: 7px; }
    .section-inner { width: calc(100% - 36px); }
    .section-heading { margin-bottom: 16px; }
    h2 { font-size: 25px; }
    .section-description { font-size: 10.5px; }
    .desktop-break { display: none; }
    .all-modules { display: none; }
    .module-grid { grid-template-columns: 1fr; gap: 9px; }
    .module-card {
      display: grid; grid-template-columns: 43px minmax(0,1fr); column-gap: 12px;
      align-items: start; min-height: 0; padding: 13px 14px; border-radius: 14px;
    }
    .module-icon { grid-column: 1; grid-row: 1 / span 2; width: 42px; height: 42px; margin: 0; }
    .module-card h3 { grid-column: 2; margin: 2px 22px 5px 0; font-size: 11.5px; }
    .module-card p { grid-column: 2; font-size: 10.5px; line-height: 1.45; }
    .card-arrow { top: 12px; right: 11px; width: 18px; height: 18px; }
    .workflow-panel { width: calc(100% - 24px); padding: 23px 16px 20px; border-radius: 24px; }
    .workflow-heading h2 { font-size: 25px; }
    .workflow-heading .section-description { font-size: 10.5px; }
    .workflow-step { min-height: 0; padding: 22px 15px 16px; }
    .workflow-step h3 { font-size: 11px; }
    .workflow-step p { font-size: 10px; }
    .footer-inner { width: calc(100% - 36px); }
    .footer-brand-text { white-space: normal; font-size: 9px; }
    .footer-links { gap: 13px; }
  }
  @media (max-width: 360px) {
    .brand-pill { padding-inline: 10px; }
    .brand-name { font-size: 13px; }
    .hero-inner { width: calc(100% - 32px); }
    h1 { font-size: 34px; }
    .hero-actions { gap: 7px; }
    .button { padding-inline: 10px; font-size: 9px; }
    .hero-visual { height: 205px; }
    .module-card { column-gap: 9px; padding-inline: 11px; }
    .module-card p { font-size: 10px; }
    .footer-brand .brand-divider { margin-inline: 3px; }
  }
  @media (max-height: 520px) and (orientation: landscape) {
    .hero { padding-top: 95px; }
    .hero-inner { grid-template-columns: 1fr 1fr; display: grid; }
    .hero-visual { height: 235px; }
    .hero-description { margin-block: 8px 12px; }
  }

  .reveal-ready { opacity: .01; transform: translateY(12px); transition: opacity .55s ease, transform .55s ease; }
  .reveal-ready.is-visible { opacity: 1; transform: translateY(0); }
  @media (prefers-reduced-motion: reduce) { .reveal-ready { opacity: 1; transform: none; transition: none; } }
</style>

<style>
  /* Mobile workflow: center each downward connector both horizontally and in the vertical gap. */
  @media (max-width: 760px) {
    .workflow-steps {
      row-gap: 56px;
    }

    .workflow-step:not(:last-child) .step-connector {
      position: absolute;
      left: 50%;
      right: auto;
      top: auto;
      bottom: -41.5px; /* centers a 28px connector inside the 56px gap */
      z-index: 4;
      width: 28px;
      height: 28px;
      margin: 0;
      transform: translateX(-50%);
      display: grid;
      place-items: center;
    }

    /* Rotate only the arrow glyph, not the circular connector. */
    .workflow-step:not(:last-child) .step-connector svg {
      width: 16px;
      height: 16px;
      transform: rotate(90deg);
    }

    .workflow-step:last-child .step-connector {
      display: none;
    }
  }
</style>

<style>
  /* ================================================================
     MOBILE UX PASS
     Larger readable type, comfortable controls, balanced card spacing.
     These rules intentionally override the earlier layout refinements.
     ================================================================ */
  @media (min-width: 701px) and (max-width: 900px) {
    .hero-description { font-size: 14px; line-height: 1.62; }
    .hero-eyebrow { font-size: 11px; }
    .button { min-height: 44px; font-size: 12px; }
    .section-description { font-size: 13px; }
    .module-card { padding: 17px 15px; min-height: 170px; }
    .module-icon { width: 46px; height: 46px; }
    .module-card h3 { font-size: 13px; }
    .module-card p { font-size: 12px; line-height: 1.5; }
    .workflow-step { padding: 25px 18px 18px; }
    .workflow-step h3 { font-size: 13px; }
    .workflow-step p { font-size: 12px; line-height: 1.5; }
  }

  @media (max-width: 760px) {
    html { scroll-padding-top: 78px; }
    .menu-toggle { width: 46px; height: 46px; }
    .menu-toggle span { width: 19px; }
    .nav-link { min-height: 44px; display: flex; align-items: center; font-size: 13px; }
    .nav-login { min-height: 44px; font-size: 12px; }

    .hero-copy { max-width: 640px; }
    .hero-eyebrow { min-height: 30px; padding: 7px 12px; font-size: 10px; }
    h1 { font-size: clamp(36px, 6.4vw, 48px); line-height: 1.08; letter-spacing: -1.5px; }
    .hero-description { max-width: 600px; font-size: 14px; line-height: 1.62; }
    .button { min-height: 46px; padding-inline: 17px; font-size: 12px; }
    .button svg { width: 17px; height: 17px; }
    .hero-visual { height: clamp(245px, 43vw, 330px); }

    .section-heading { margin-bottom: 20px; }
    h2 { font-size: clamp(26px, 4.2vw, 30px); line-height: 1.18; }
    .section-description { font-size: 13px; line-height: 1.6; }
    .module-card { min-height: 165px; padding: 16px; }
    .module-icon { width: 47px; height: 47px; border-radius: 14px; }
    .module-icon svg { width: 27px; height: 27px; }
    .module-card h3 { font-size: 13.5px; line-height: 1.35; }
    .module-card p { font-size: 12.5px; line-height: 1.55; }
    .card-arrow { width: 23px; height: 23px; }
    .card-arrow svg { width: 14px; height: 14px; }

    .workflow-panel { padding: 28px 22px 25px; }
    .workflow-heading h2 { font-size: 28px; }
    .workflow-heading .section-description { font-size: 13px; line-height: 1.58; }
    .workflow-steps { row-gap: 64px; }
    .workflow-step { min-height: 0; padding: 26px 20px 20px; border-radius: 20px; }
    .step-number { width: 34px; height: 34px; top: -13px; font-size: 13px; }
    .step-icon { width: 42px; height: 42px; margin-bottom: 8px; }
    .workflow-step h3 { font-size: 14px; line-height: 1.4; margin-bottom: 8px; }
    .workflow-step p { max-width: 460px; font-size: 12.8px; line-height: 1.58; }
    .workflow-step:not(:last-child) .step-connector {
      bottom: -48px;
      width: 32px;
      height: 32px;
    }
    .workflow-step:not(:last-child) .step-connector svg { width: 18px; height: 18px; }

    .footer-brand-text { font-size: 11px; }
    .footer-links { gap: 18px; font-size: 12px; }
    .version { font-size: 11px; }
    .copyright { font-size: 11px; }
  }

  @media (max-width: 480px) {
    .hero { padding-top: 91px; padding-bottom: 26px; }
    .hero-inner { width: calc(100% - 36px); }
    .hero-eyebrow { font-size: 10px; margin-bottom: 13px; }
    h1 { font-size: clamp(34px, 8.8vw, 40px); line-height: 1.1; letter-spacing: -1.35px; }
    .hero-description { margin: 13px 0 19px; font-size: 14px; line-height: 1.62; }
    .hero-actions { gap: 10px; }
    .button { min-height: 46px; padding-inline: 14px; font-size: 11.5px; }
    .button-primary { min-width: 145px; }
    .button-secondary { min-width: 118px; }
    .hero-visual { height: clamp(210px, 59vw, 255px); width: calc(100% + 12px); margin: 5px -6px -2px; }
    .module-stat { top: 10px; right: 0; width: 124px; min-height: 72px; padding: 9px 10px; }
    .stat-cube { width: 23px; height: 26px; }
    .stat-number { font-size: 24px; }
    .stat-label { font-size: 8px; }
    .stat-signal { height: 22px; }

    .modules-section { padding-top: 25px; padding-bottom: 31px; }
    .section-inner { width: calc(100% - 36px); }
    .section-heading { margin-bottom: 17px; }
    h2 { font-size: 27px; }
    .section-description { font-size: 13px; line-height: 1.58; }
    .module-grid { grid-template-columns: 1fr; gap: 10px; }
    .module-card {
      display: grid;
      grid-template-columns: 48px minmax(0, 1fr);
      column-gap: 13px;
      align-items: center;
      min-height: 0;
      padding: 15px 14px;
      border-radius: 15px;
    }
    .module-icon {
      grid-column: 1;
      grid-row: 1 / span 2;
      align-self: start;
      width: 46px;
      height: 46px;
      margin: 1px 0 0;
    }
    .module-icon svg { width: 26px; height: 26px; }
    .module-card h3 { grid-column: 2; margin: 1px 25px 5px 0; font-size: 13.5px; line-height: 1.38; }
    .module-card p { grid-column: 2; font-size: 12.5px; line-height: 1.5; }
    .card-arrow { top: 13px; right: 12px; width: 22px; height: 22px; }

    .workflow-section { padding-bottom: 28px; }
    .workflow-panel { width: calc(100% - 24px); padding: 25px 16px 23px; border-radius: 25px; }
    .workflow-heading h2 { font-size: 27px; }
    .workflow-heading .section-description { font-size: 12.8px; line-height: 1.58; }
    .workflow-steps { margin-top: 31px; row-gap: 62px; }
    .workflow-step { padding: 26px 16px 19px; }
    .step-number { width: 34px; height: 34px; font-size: 13px; }
    .step-icon { width: 40px; height: 40px; }
    .workflow-step h3 { font-size: 13.5px; }
    .workflow-step p { font-size: 12.3px; line-height: 1.55; }
    .workflow-step:not(:last-child) .step-connector { bottom: -47px; width: 32px; height: 32px; }

    .site-footer { padding: 23px 0 17px; }
    .footer-inner { width: calc(100% - 36px); }
    .footer-top { gap: 18px; }
    .footer-brand .brand-name { font-size: 16px; }
    .footer-brand-text { font-size: 10.5px; line-height: 1.45; }
    .footer-links { gap: 15px; font-size: 11.5px; }
    .version { font-size: 10.5px; }
    .copyright { font-size: 10.5px; }
  }

  @media (max-width: 360px) {
    .hero-inner { width: calc(100% - 32px); }
    h1 { font-size: 33px; letter-spacing: -1.2px; }
    .hero-description { font-size: 13px; }
    .button { min-height: 44px; padding-inline: 11px; font-size: 11px; }
    .button-primary { min-width: 137px; }
    .button-secondary { min-width: 112px; }
    .hero-visual { height: 205px; }
    .section-inner { width: calc(100% - 32px); }
    h2 { font-size: 25px; }
    .section-description { font-size: 12.5px; }
    .module-card { grid-template-columns: 44px minmax(0, 1fr); column-gap: 11px; padding: 14px 12px; }
    .module-icon { width: 43px; height: 43px; }
    .module-card h3 { font-size: 13px; }
    .module-card p { font-size: 12px; }
    .workflow-panel { width: calc(100% - 20px); padding-inline: 13px; }
    .workflow-step { padding-inline: 13px; }
    .workflow-step h3 { font-size: 13px; }
    .workflow-step p { font-size: 12px; }
    .footer-brand-text { font-size: 10px; }
    .footer-links { gap: 12px; font-size: 11px; }
  }

  @media (max-width: 340px) {
    .brand-pill { padding-inline: 8px; }
    .brand-name { font-size: 12px; }
    .menu-toggle { width: 44px; height: 44px; }
    .hero-actions { align-items: stretch; flex-direction: column; max-width: 220px; }
    .button { width: 100%; }
    .module-card p { overflow-wrap: anywhere; }
  }

  @media (prefers-reduced-motion: reduce) {
    .module-card, .button, .nav-login, .all-modules span { transition: none !important; }
  }


  /* ================================================================
     FINAL MOBILE-FIRST REFINEMENT
     Prevent decorative overlap, improve reading rhythm and touch UX.
     Desktop layout remains unchanged.
     ================================================================ */
  @media (max-width: 700px) {
    html { scroll-padding-top: 76px; }
    body, .site-shell { width: 100%; max-width: 100%; overflow-x: clip; }

    /* Header: compact brand + predictable menu button */
    .site-header { top: 12px; }
    .header-inner { width: calc(100% - 32px); gap: 12px; }
    .brand-pill { min-height: 44px; padding: 0 12px; gap: 8px; }
    .brand-mark { width: 20px; height: 23px; }
    .brand-name { font-size: 14px; }
    .menu-toggle { display: flex; width: 44px; height: 44px; }
    .menu-toggle span { width: 18px; }
    .nav-pill {
      top: 54px; right: 0; width: min(260px, calc(100vw - 32px));
      padding: 8px; border-radius: 17px;
    }
    .nav-link { min-height: 46px; padding: 12px 13px; font-size: 13px; }
    .nav-login { min-height: 46px; font-size: 13px; }

    /* Hero: remove oversized desktop decorative circles that crossed the text. */
    .hero {
      min-height: 0;
      padding: 104px 0 30px;
      background: linear-gradient(155deg, #f7faf6 0%, #eef7f2 58%, #e3f1eb 100%);
      overflow: hidden;
    }
    .hero::before, .hero::after { content: none !important; display: none !important; }
    .hero-inner {
      width: calc(100% - 40px);
      min-height: 0;
      display: flex;
      flex-direction: column;
      align-items: stretch;
      gap: 0;
    }
    .hero-copy { width: 100%; max-width: 560px; padding: 0; }
    .hero-eyebrow {
      min-height: 30px; margin: 0 0 14px; padding: 7px 12px;
      font-size: 10px; line-height: 1.2;
    }
    h1 {
      width: 100%; max-width: 100%; margin: 0;
      font-size: clamp(32px, 8.6vw, 37px);
      line-height: 1.12; letter-spacing: -1.25px;
      overflow-wrap: normal; word-break: normal;
    }
    h1 .highlight { display: inline; }
    .underline-word { white-space: nowrap; padding-bottom: .24em; }
    .underline-word svg { height: .28em; bottom: -.015em; }
    .hero-description {
      max-width: 100%; margin: 13px 0 20px;
      font-size: 14px; line-height: 1.65; color: #315466;
    }
    .hero-actions {
      width: 100%; max-width: 100%; display: flex;
      flex-direction: column; align-items: stretch; gap: 10px;
    }
    .button {
      width: 100%; min-width: 0; min-height: 48px;
      padding: 0 18px; justify-content: center;
      font-size: 13px; line-height: 1.2;
    }
    .button svg { width: 18px; height: 18px; }
    .hero-visual {
      width: 100%; max-width: 560px; height: clamp(220px, 62vw, 270px);
      margin: 18px auto 0; align-self: center;
    }
    .warehouse-hero-image { width: 112%; height: 100%; left: -6%; }
    .hero-visual::before { inset: 8% 0 2%; }
    .module-stat { top: 10px; right: 0; width: 120px; min-height: 68px; padding: 8px 10px; border-radius: 14px; gap: 7px; }
    .stat-cube { width: 21px; height: 24px; }
    .stat-number { font-size: 23px; }
    .stat-label { margin-top: 4px; font-size: 8px; }
    .stat-signal { height: 20px; }
    .hero-dots { right: 7px; bottom: 1px; }

    /* Module section: single-column cards with comfortable scan pattern. */
    .modules-section { padding: 30px 0 36px; }
    .modules-section::before { left: 5px; top: 14px; opacity: .28; }
    .section-inner { width: calc(100% - 40px); }
    .section-heading { display: block; margin-bottom: 20px; }
    .section-kicker { font-size: 10px; margin-bottom: 9px; }
    h2 { font-size: clamp(25px, 7vw, 29px); line-height: 1.18; letter-spacing: -.8px; }
    .section-description { max-width: 100%; font-size: 13.5px; line-height: 1.6; }
    .all-modules { display: none; }
    .module-grid { grid-template-columns: 1fr; gap: 11px; }
    .module-card {
      display: grid; grid-template-columns: 48px minmax(0, 1fr);
      grid-template-rows: auto auto; align-items: start;
      min-height: 0; padding: 16px 15px; column-gap: 13px;
      border-radius: 16px;
    }
    .module-icon {
      grid-column: 1; grid-row: 1 / span 2;
      width: 46px; height: 46px; margin: 1px 0 0;
      border-radius: 13px;
    }
    .module-icon svg { width: 25px; height: 25px; }
    .module-card h3 {
      grid-column: 2; margin: 1px 25px 5px 0;
      font-size: 14px; line-height: 1.4; letter-spacing: -.2px;
    }
    .module-card p { grid-column: 2; font-size: 13px; line-height: 1.55; }
    .card-arrow { top: 14px; right: 12px; width: 22px; height: 22px; }
    .card-arrow svg { width: 13px; height: 13px; }

    /* Workflow: readable cards and arrows centered in the actual gap. */
    .workflow-section { padding: 0 0 30px; }
    .workflow-panel {
      width: calc(100% - 24px); min-height: 0;
      padding: 26px 16px 26px; border-radius: 25px;
    }
    .workflow-panel::before { width: 100%; height: 180px; opacity: .22; }
    .workflow-bg-media { width: 100%; height: 170px; opacity: .35; border-top-left-radius: 25px; border-top-right-radius: 25px; }
    .workflow-heading h2 { font-size: clamp(25px, 7vw, 29px); }
    .workflow-heading .section-description { font-size: 13px; line-height: 1.6; }
    .workflow-steps {
      display: grid; grid-template-columns: 1fr; gap: 60px;
      width: 100%; max-width: 520px; margin: 34px auto 0;
    }
    .workflow-step {
      min-height: 0; padding: 27px 17px 20px;
      border-radius: 19px; text-align: center;
    }
    .step-number { top: -15px; width: 36px; height: 36px; font-size: 14px; }
    .step-icon { width: 42px; height: 42px; margin: 0 auto 9px; }
    .workflow-step h3 { margin: 0 0 8px; font-size: 14px; line-height: 1.4; }
    .workflow-step p { max-width: 440px; margin-inline: auto; font-size: 13px; line-height: 1.58; }
    .workflow-step:not(:last-child) .step-connector {
      display: grid; left: 50%; right: auto; top: auto; bottom: -47px;
      width: 34px; height: 34px; transform: translateX(-50%);
      border-radius: 50%;
    }
    .workflow-step:not(:last-child) .step-connector svg { width: 18px; height: 18px; }
    .workflow-step:last-child .step-connector { display: none; }

    /* Footer stacks cleanly and keeps links easy to tap. */
    .site-footer { min-height: 0; padding: 25px 0 20px; border-radius: 20px 20px 0 0; }
    .footer-inner { width: calc(100% - 40px); }
    .footer-top { align-items: flex-start; flex-direction: column; gap: 17px; }
    .footer-brand { flex-wrap: wrap; gap: 9px; }
    .footer-brand .brand-name { font-size: 17px; }
    .footer-brand .brand-divider { height: 20px; margin: 0 5px; }
    .footer-brand-text { white-space: normal; font-size: 11px; line-height: 1.45; }
    .footer-links { display: flex; flex-wrap: wrap; gap: 10px 18px; font-size: 12px; }
    .footer-links a { min-height: 36px; display: inline-flex; align-items: center; }
    .version { font-size: 10px; }
    .footer-divider { margin: 15px 0 13px; }
    .copyright { font-size: 11px; line-height: 1.5; }
  }

  @media (max-width: 380px) {
    .hero-inner, .section-inner, .footer-inner { width: calc(100% - 32px); }
    h1 { font-size: clamp(30px, 8.4vw, 32px); letter-spacing: -1.05px; }
    .hero-description { font-size: 13.5px; }
    .hero-visual { height: 215px; }
    .module-card { grid-template-columns: 44px minmax(0, 1fr); column-gap: 11px; padding: 14px 12px; }
    .module-icon { width: 43px; height: 43px; }
    .module-card h3 { font-size: 13.5px; }
    .module-card p { font-size: 12.5px; }
    .workflow-panel { width: calc(100% - 20px); padding-inline: 13px; }
    .workflow-step { padding-inline: 13px; }
  }

  @media (max-width: 340px) {
    .header-inner { width: calc(100% - 24px); }
    .hero-inner, .section-inner, .footer-inner { width: calc(100% - 28px); }
    h1 { font-size: 29px; letter-spacing: -.9px; }
    .hero-eyebrow { font-size: 9px; }
    .button { min-height: 46px; font-size: 12px; }
    .module-card h3 { font-size: 13px; }
    .module-card p, .workflow-step p { font-size: 12px; }
  }



  /* ================================================================
     MOBILE VISUAL POLISH — preserve SIGMA's original visual identity
     ================================================================ */
  @media (max-width: 700px) {
    .hero {
      background:
        radial-gradient(ellipse at 88% 8%, rgba(38,166,148,.22) 0%, transparent 38%),
        radial-gradient(ellipse at 4% 72%, rgba(205,235,224,.78) 0%, transparent 34%),
        linear-gradient(150deg, #fbfcf8 0%, #f2f8f3 53%, #e4f2ec 100%);
      padding-top: 100px;
      padding-bottom: 34px;
    }
    .hero::before {
      content: "" !important; display: block !important;
      position: absolute; z-index: -1;
      width: min(112vw, 440px); height: min(112vw, 440px);
      right: -58vw; top: -245px; border-radius: 50%;
      background: linear-gradient(145deg, rgba(38,165,146,.88), rgba(5,91,82,.96));
      box-shadow: -24px 24px 0 rgba(45,165,147,.10);
    }
    .hero::after {
      content: "" !important; display: block !important;
      position: absolute; z-index: -1; left: -115px; bottom: -16px;
      width: 290px; height: 54px; border-radius: 0 100% 0 0;
      background: linear-gradient(15deg, #087568, #19a18e);
      transform: rotate(5deg); opacity: .9;
    }
    .hero-inner { position: relative; z-index: 1; }
    .hero-copy { position: relative; z-index: 2; }
    .hero-eyebrow { box-shadow: 0 5px 16px rgba(10,104,91,.07); border: 1px solid rgba(255,255,255,.72); }
    .hero-description { max-width: 38rem; }
    .hero-actions {
      display: grid; grid-template-columns: minmax(0,1.12fr) minmax(0,.88fr);
      gap: 10px; align-items: stretch;
    }
    .hero-actions .button { min-width: 0; width: 100%; padding-inline: 10px; white-space: nowrap; }
    .button-primary { box-shadow: 0 9px 22px rgba(255,148,31,.23); }
    .button-secondary { background: rgba(255,255,255,.72); backdrop-filter: blur(10px); }
    .hero-visual {
      position: relative; z-index: 1; margin-top: 23px; padding: 7px 0 2px;
      border-radius: 26px;
      background: linear-gradient(135deg, rgba(255,255,255,.62), rgba(213,239,229,.34));
      border: 1px solid rgba(255,255,255,.76);
      box-shadow: 0 14px 30px rgba(15,88,76,.10); overflow: visible;
    }
    .hero-visual::before {
      inset: 7% 1% 4%; border-radius: 24px;
      background: linear-gradient(135deg, rgba(8,117,103,.13), rgba(5,91,82,.25)); filter: blur(12px);
    }
    .warehouse-hero-image { width: 108%; left: -4%; }
    .module-stat { right: 8px; top: 7px; }
    .hero-dots { right: 12px; bottom: 8px; }
    .modules-section {
      background:
        radial-gradient(ellipse at 100% 8%, rgba(204,235,224,.46), transparent 35%),
        radial-gradient(ellipse at 0% 78%, rgba(226,242,235,.62), transparent 32%),
        linear-gradient(180deg, #fbfcf9 0%, #f6f8f4 100%);
    }
    .modules-section::before { opacity: .48; }
    .module-card {
      background: linear-gradient(145deg, rgba(255,255,255,.98), rgba(247,252,249,.94));
      border: 1px solid rgba(14,111,98,.12);
      box-shadow: 0 7px 20px rgba(20,75,66,.075), inset 0 1px 0 rgba(255,255,255,.9);
    }
    .module-icon {
      background: linear-gradient(145deg, #e8f6f1, #ccebe1);
      box-shadow: inset 0 1px 0 rgba(255,255,255,.9);
    }
    .card-arrow { box-shadow: 0 2px 8px rgba(8,117,103,.08); }
    .workflow-panel {
      position: relative;
      background: radial-gradient(ellipse at 90% 5%, rgba(255,255,255,.7), transparent 42%),
                  linear-gradient(145deg, rgba(237,247,241,.96), rgba(218,239,231,.92));
      border: 1px solid rgba(8,117,103,.10);
      box-shadow: 0 12px 30px rgba(14,93,81,.07);
    }
    .workflow-panel::before { opacity: .28; }
    .workflow-step {
      background: linear-gradient(145deg, rgba(255,255,255,.98), rgba(248,252,249,.95));
      border: 1px solid rgba(10,104,91,.10);
      box-shadow: 0 8px 20px rgba(17,79,69,.09), inset 0 1px 0 rgba(255,255,255,.95);
    }
    .step-number { box-shadow: 0 5px 12px rgba(5,91,82,.20); }
    .site-footer {
      background: radial-gradient(ellipse at 88% 0%, rgba(24,132,117,.26), transparent 38%),
                  linear-gradient(120deg, #06463f, #003c38 65%, #052f31);
    }
  }
  @media (max-width: 380px) {
    .hero { padding-top: 96px; }
    .hero-actions { grid-template-columns: 1fr; gap: 9px; }
    .hero-actions .button { min-height: 47px; }
    .hero::before { right: -66vw; top: -220px; }
    .hero-visual { margin-top: 19px; }
  }
  @media (min-width: 381px) and (max-width: 700px) {
    .hero-actions .button { font-size: 12px; }
  }


  /* SIGMA HERO COLOR FIELD — bespoke layered gradients, shared by desktop and mobile. */
  .hero {
    background:
      radial-gradient(ellipse 42% 72% at 5% 22%, rgba(190, 226, 214, .62) 0%, rgba(190, 226, 214, .18) 48%, transparent 78%),
      radial-gradient(ellipse 27% 48% at 0% 82%, rgba(31, 148, 130, .13) 0%, transparent 76%),
      radial-gradient(ellipse 30% 34% at 33% 2%, rgba(235, 205, 158, .19) 0%, transparent 82%),
      radial-gradient(ellipse 38% 62% at 80% 18%, rgba(190, 226, 217, .48), transparent 78%),
      linear-gradient(112deg, #f7faf5 0%, #f8f8f3 48%, #e7f2ed 100%);
  }
  .hero-inner { isolation: isolate; }
  .hero-inner::before {
    content: "";
    position: absolute;
    z-index: 0;
    pointer-events: none;
    left: -15%;
    top: -13%;
    width: 61%;
    height: 126%;
    border-radius: 8% 46% 52% 17% / 9% 29% 58% 22%;
    background:
      radial-gradient(ellipse at 14% 25%, rgba(11, 130, 115, .22) 0%, rgba(11, 130, 115, .08) 30%, transparent 58%),
      radial-gradient(ellipse at 25% 72%, rgba(102, 190, 168, .28) 0%, transparent 57%),
      radial-gradient(ellipse at 62% 15%, rgba(245, 174, 83, .17) 0%, transparent 42%),
      linear-gradient(132deg, rgba(211, 235, 225, .76) 0%, rgba(228, 241, 232, .57) 48%, rgba(248, 249, 243, 0) 100%);
    clip-path: polygon(0 0, 82% 0, 100% 22%, 91% 46%, 100% 69%, 82% 100%, 0 91%);
    box-shadow: inset -1px 0 rgba(255,255,255,.52);
    opacity: .94;
  }
  .hero-inner::after {
    content: "";
    position: absolute;
    z-index: 0;
    pointer-events: none;
    left: -8%;
    top: 11%;
    width: 34%;
    height: 72%;
    border-left: 1px solid rgba(8, 111, 99, .13);
    border-top: 1px solid rgba(255, 255, 255, .72);
    border-radius: 48% 0 0 52%;
    transform: rotate(-13deg);
    opacity: .7;
  }
  .hero-copy, .hero-visual { position: relative; }
  .hero-copy { z-index: 5; }
  .hero-visual { z-index: 1; }

  @media (min-width: 761px) {
    .hero-inner::before { left: -18%; width: 65%; }
    .hero-inner::after { left: -10%; width: 37%; }
  }
  @media (max-width: 700px) {
    .hero {
      background:
        radial-gradient(ellipse 78% 42% at 0% 12%, rgba(163, 216, 201, .64) 0%, rgba(163, 216, 201, .22) 52%, transparent 82%),
        radial-gradient(ellipse 58% 36% at 4% 68%, rgba(22, 139, 122, .16) 0%, transparent 78%),
        radial-gradient(ellipse 42% 24% at 52% 0%, rgba(241, 184, 111, .16) 0%, transparent 82%),
        linear-gradient(155deg, #f7faf5 0%, #f5f8f2 56%, #e7f2ed 100%);
    }
    .hero-inner::before {
      left: -24%;
      top: -72px;
      width: 132%;
      height: 370px;
      border-radius: 0 0 54% 8% / 0 0 22% 8%;
      background:
        radial-gradient(ellipse at 7% 18%, rgba(7, 119, 105, .22) 0%, rgba(7, 119, 105, .07) 33%, transparent 64%),
        radial-gradient(ellipse at 18% 72%, rgba(111, 193, 171, .30) 0%, transparent 56%),
        radial-gradient(ellipse at 68% 5%, rgba(244, 177, 94, .15) 0%, transparent 38%),
        linear-gradient(132deg, rgba(205, 232, 221, .76) 0%, rgba(229, 242, 233, .54) 52%, rgba(248, 249, 243, .08) 100%);
      clip-path: polygon(0 0, 100% 0, 100% 68%, 84% 83%, 57% 100%, 25% 88%, 0 70%);
      opacity: .9;
    }
    .hero-inner::after {
      left: -15%; top: 5%; width: 55%; height: 280px;
      border-left-color: rgba(8, 111, 99, .12);
      transform: rotate(-17deg);
    }
    .hero-copy { z-index: 2; }
    .hero-visual { z-index: 1; }
  }
  @media (max-width: 380px) {
    .hero-inner::before { left: -30%; width: 145%; height: 345px; }
  }


  /* SIGMA signature: five ordered organic ribbons anchored to the left edge. */
  .hero { isolation: isolate; }
  /* Remove the previous broad center-spreading ornaments; the left SVG now owns this motif. */
  .hero-inner::before,
  .hero-inner::after,
  .hero::after {
    content: none !important;
    display: none !important;
  }
  .hero-left-ribbons {
    position: absolute;
    z-index: 0;
    left: 0;
    top: 0;
    width: min(36%, 430px);
    height: 100%;
    display: block;
    pointer-events: none;
    overflow: visible;
  }
  .hero-bottom-wave {
    position: absolute;
    z-index: 2;
    left: 0;
    bottom: -1px;
    display: block;
    width: 100%;
    height: clamp(80px, 8vw, 110px);
    pointer-events: none;
  }
  .hero-inner { position: relative; z-index: auto; isolation: auto; }
  .hero-copy { position: relative; z-index: 5; }
  .hero-visual { position: relative; z-index: auto; }

  @media (min-width: 1200px) {
    .hero-bottom-wave { height: clamp(110px, 8.5vw, 135px); }
  }

  @media (max-width: 700px) {
    .hero-bottom-wave { height: clamp(65px, 14vw, 95px); }
    .hero-left-ribbons {
      width: min(40%, 170px);
      height: 100%;
      opacity: .98;
    }
    /* Keep the colored bands close to the left edge; the headline stays clear. */
    .hero-left-ribbons path:nth-child(1) { opacity: .88; }
    .hero-left-ribbons path:nth-child(2) { opacity: .9; }
    .hero-left-ribbons path:nth-child(3) { opacity: .88; }
    .hero-left-ribbons path:nth-child(4) { opacity: .96; }
    .hero-left-ribbons path:nth-child(5) { opacity: 1; }
  }
  @media (max-width: 380px) {
    .hero-left-ribbons { width: min(40%, 150px); }
  }
</style>


<style>
  .hero::before { content: none !important; display: none !important; }

  @media (max-width: 760px) {
    .hero-image-stage { top: -10px; }
  }
</style>
<style>
  .header-inner,
  .hero-inner,
  .section-inner,
  .workflow-panel,
  .footer-inner {
    width: min(1120px, calc(100% - 64px));
  }

  @media (max-width: 1100px) {
    .header-inner,
    .hero-inner,
    .section-inner,
    .workflow-panel,
    .footer-inner {
      width: calc(100% - 64px);
    }
  }

  @media (max-width: 760px) {
    .header-inner,
    .hero-inner,
    .section-inner,
    .workflow-panel,
    .footer-inner {
      width: calc(100% - 48px);
    }
  }

  @media (max-width: 480px) {
    .header-inner,
    .hero-inner,
    .section-inner,
    .workflow-panel,
    .footer-inner {
      width: calc(100% - 32px);
    }
  }

  .hero-image-stage {
    position: absolute;
    z-index: 1;
    inset: 58px -56px -28px -88px;
    overflow: hidden;
    background: transparent;
    -webkit-mask-image: linear-gradient(90deg, transparent 0%, rgba(0,0,0,.02) 12%, rgba(0,0,0,.18) 30%, rgba(0,0,0,.55) 50%, #000 72%);
    mask-image: linear-gradient(90deg, transparent 0%, rgba(0,0,0,.02) 12%, rgba(0,0,0,.18) 30%, rgba(0,0,0,.55) 50%, #000 72%);
  }
  @media (min-width: 1201px) {
    .hero-image-stage { top: -34%; }
  }
  @media (min-width: 901px) and (max-width: 1200px) {
    .hero-image-stage { top: -33%; }
  }
  @media (min-width: 761px) and (max-width: 900px) {
    .hero-image-stage { top: -40%; }
  }
  @media (min-width: 761px) {
    .hero-visual::before { content: none; display: none; }
  }
  .hero-image-stage::after {
    content: "";
    position: absolute;
    z-index: 3;
    inset: 0;
    pointer-events: none;
    background: linear-gradient(90deg, rgba(248,248,244,.98) 0%, rgba(248,248,244,.88) 9%, rgba(248,248,244,.5) 24%, transparent 48%);
  }
  .warehouse-hero-image {
    z-index: 1;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    max-height: none;
    object-fit: cover;
    object-position: center;
    opacity: 0;
    transform: translateX(-22px);
    transition: opacity .72s ease, transform .72s cubic-bezier(.22,.61,.36,1);
    will-change: opacity, transform;
  }
  .warehouse-hero-image.is-active {
    z-index: 2;
    opacity: 1;
    transform: translateX(0);
  }
  .warehouse-hero-image.is-exiting {
    z-index: 1;
    opacity: 0;
    transform: translateX(18px);
  }
  .hero-dots { right: 16px; bottom: clamp(56px, 6vw, 90px); z-index: 5; }
  .hero-dots span { transition: background-color .25s ease, transform .25s ease; }
  .hero-dots span.is-active { transform: scale(1.25); }

  @media (max-width: 700px) {
    .hero-image-stage { inset: -12px -18px -26px -18px; }
    .hero-image-stage::after {
      background: linear-gradient(90deg, rgba(248,248,244,.78) 0%, rgba(248,248,244,.42) 14%, transparent 38%);
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .warehouse-hero-image,
    .hero-dots span { transition: none; }
  }
  @media (max-width: 760px) {
    .hero-image-stage {
      top: -10px;
      clip-path: none;
      -webkit-mask-image: none;
      mask-image: none;
    }
  }
  @media (max-width: 700px) {
    .hero-image-stage { top: -12px; }
  }
  .site-header .header-inner {
    width: min(1120px, calc(100% - 64px));
  }
  @media (min-width: 1200px) {
    .site-header .header-inner { padding-left: 0; }
    .hero-copy { padding-left: 0; transform: translateY(-20px); }
  }
  @media (max-width: 1100px) {
    .site-header .header-inner { width: calc(100% - 64px); }
  }
  @media (max-width: 760px) {
    .site-header .header-inner { width: calc(100% - 48px); }
  }
  @media (max-width: 700px) {
    .site-header .header-inner { width: min(520px, calc(100% - 36px)); }
  }
  @media (max-width: 480px) {
    .site-header .header-inner { width: calc(100% - 36px); }
  }
  @media (max-width: 380px) {
    .site-header .header-inner { width: calc(100% - 32px); }
  }
  @media (max-width: 340px) {
    .site-header .header-inner { width: calc(100% - 28px); }
  }

  :root {
    --font-body: "Inter", system-ui, -apple-system, sans-serif;
    --type-helper: 12px;
    --type-body: 14px;
    --type-section: 20px;
    --type-metric: 24px;
  }

  body {
    font-family: var(--font-body);
    font-size: var(--type-body);
    line-height: 1.5;
  }

  .brand-name,
  h1,
  h2,
  h3,
  .module-card h3,
  .workflow-step h3,
  .stat-number,
  .step-number,
  .nav-link,
  .nav-login,
  .hero-eyebrow,
  .button,
  .all-modules,
  .brand-description,
  .section-description,
  .module-card p,
  .workflow-step p,
  .footer-brand-text,
  .footer-links,
  .version,
  .copyright {
    font-family: var(--font-body);
  }

  .hero-eyebrow,
  .section-kicker {
    font-size: var(--type-helper);
    line-height: 1.2;
  }

  h1 {
    font-size: clamp(40px, 3.8vw, 48px);
    line-height: 1.08;
    letter-spacing: -.045em;
  }

  .nav-link,
  .nav-login,
  .button,
  .all-modules,
  .brand-description,
  .stat-label,
  .step-number,
  .footer-brand-text,
  .footer-links,
  .version,
  .copyright {
    font-size: var(--type-helper);
  }

  h2,
  .workflow-heading h2 {
    font-size: var(--type-section);
    line-height: 1.2;
    letter-spacing: -.02em;
  }

  .hero-description,
  .section-description,
  .workflow-heading .section-description,
  .module-card p,
  .workflow-step p {
    font-size: var(--type-body);
    line-height: 1.55;
  }

  .module-card h3,
  .workflow-step h3 {
    font-size: var(--type-body);
    font-weight: 800;
    line-height: 1.25;
  }

  .stat-number {
    font-size: var(--type-metric);
  }

  @media (max-width: 900px) {
    h1 {
      font-size: clamp(36px, 6vw, 40px);
    }

    .hero-description {
      font-size: 14px;
    }
  }

  @media (max-width: 600px) {
    h1 {
      font-size: clamp(32px, 8.5vw, 36px);
      letter-spacing: -.04em;
    }

    h2,
    .workflow-heading h2 {
      font-size: 20px;
    }

    .stat-number {
      font-size: var(--type-metric);
    }
  }
</style>
