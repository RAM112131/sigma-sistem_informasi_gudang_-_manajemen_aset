  <style>
    :root {
      --paper: #f8f8f4;
      --paper-2: #f3f7f3;
      --white: #ffffff;
      --ink: #092f31;
      --body: #294b5e;
      --muted: #58717a;
      --teal: #075b52;
      --teal-2: #087b6d;
      --teal-3: #159889;
      --mint: #dff1eb;
      --mint-2: #cce8df;
      --orange: #ff941f;
      --line: rgba(7, 91, 82, .11);
      --shadow: 0 12px 30px rgba(18, 69, 61, .09);
      --shadow-soft: 0 5px 18px rgba(18, 69, 61, .065);
      --radius: 20px;
      --content: 880px;
    }

    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; scroll-padding-top: 90px; }
    body {
      margin: 0;
      background: var(--paper);
      color: var(--ink);
      font-family: "Inter", Arial, sans-serif;
      -webkit-font-smoothing: antialiased;
      overflow-x: hidden;
    }
    a { color: inherit; text-decoration: none; }
    button { font: inherit; }
    .container { width: min(var(--content), calc(100% - 80px)); margin-inline: auto; }
    .site-shell { position: relative; overflow: hidden; background: var(--paper); }

    /* ---------- Brand / navigation ---------- */
    .site-header {
      position: absolute;
      z-index: 20;
      top: 22px;
      left: 0;
      width: 100%;
      pointer-events: none;
    }
    .header-inner {
      width: min(896px, calc(100% - 80px));
      margin: auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
    }
    .brand-pill, .nav-pill {
      min-height: 46px;
      display: flex;
      align-items: center;
      border: 1px solid rgba(255,255,255,.75);
      background: rgba(255,255,255,.68);
      box-shadow: 0 7px 25px rgba(33, 82, 75, .045);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
      border-radius: 999px;
      pointer-events: auto;
    }
    .brand-pill { width: fit-content; flex: 0 0 auto; padding: 0 18px; gap: 10px; }
    .brand-mark {
      width: 22px;
      height: 25px;
      flex: 0 0 auto;
      display: block;
    }
    .brand-name {
      font: 800 16px/1 "Inter", Arial, sans-serif;
      letter-spacing: -.65px;
      color: #07544e;
    }
    .brand-divider {
      width: 1px;
      height: 19px;
      background: rgba(7, 75, 69, .35);
      margin: 0 5px 0 4px;
    }
    .brand-description {
      font-size: 9px;
      font-weight: 500;
      color: #193b42;
      white-space: nowrap;
    }
    .nav-pill { padding: 5px 8px 5px 18px; gap: 20px; }
    .nav-link {
      color: #0d3035;
      font-size: 10px;
      font-weight: 600;
      min-height: 34px;
      display: inline-flex;
      align-items: center;
      transition: color .2s ease;
    }
    .nav-link:hover { color: var(--teal-2); }
    .nav-login {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      min-width: 106px;
      height: 34px;
      padding: 0 16px;
      color: #fff;
      background: linear-gradient(135deg, #08786b, #056157);
      border-radius: 999px;
      box-shadow: 0 5px 12px rgba(5, 97, 87, .2);
      font-size: 11px;
      font-weight: 700;
      transition: transform .2s, box-shadow .2s;
    }
    .nav-login:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(5,97,87,.25); }
    .nav-login svg { width: 14px; height: 14px; }

    /* ---------- Hero ---------- */
    .hero {
      min-height: 488px;
      position: relative;
      isolation: isolate;
      padding: 110px 0 46px;
      background:
        radial-gradient(ellipse at 72% 22%, rgba(190, 226, 217, .62), transparent 33%),
        radial-gradient(ellipse at 10% 8%, rgba(215, 235, 228, .68), transparent 31%),
        linear-gradient(115deg, #fafaf6 0%, #f7f8f3 48%, #e7f2ed 100%);
    }
    .hero::before {
      content: "";
      position: absolute;
      z-index: -1;
      width: 570px;
      height: 570px;
      right: -210px;
      top: -280px;
      border-radius: 50%;
      background: linear-gradient(140deg, rgba(25,157,139,.55), rgba(5,86,77,.95));
      box-shadow: -48px 30px 0 rgba(45, 165, 147, .10);
    }
    .hero::after {
      content: "";
      position: absolute;
      z-index: -1;
      left: -130px;
      bottom: -22px;
      width: 510px;
      height: 95px;
      background: linear-gradient(15deg, #087568, #0c9b87);
      border-radius: 0 100% 0 0;
      transform: rotate(4deg);
      opacity: .96;
    }
    .hero-inner {
      width: min(864px, calc(100% - 80px));
      margin: auto;
      min-height: 330px;
      display: grid;
      grid-template-columns: .92fr 1.08fr;
      align-items: center;
      gap: 8px;
      position: relative;
    }
    .hero-copy { position: relative; z-index: 5; padding: 4px 0 10px; }
    .hero-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 7px 13px;
      margin-bottom: 12px;
      border-radius: 99px;
      border: 1px solid rgba(7, 118, 105, .16);
      background: rgba(255, 255, 255, .88);
      color: #075f56;
      box-shadow: 0 6px 16px rgba(7, 91, 82, .09);
      font-size: 10px;
      font-weight: 700;
    }
    .eyebrow-dot { width: 6px; height: 6px; background: #149b88; border-radius: 50%; }
    h1 {
      max-width: 445px;
      margin: 0;
      color: #0a3032;
      font: 800 clamp(36px, 4.15vw, 44px)/1.075 "Inter", Arial, sans-serif;
      letter-spacing: -1.9px;
    }
    h1 .highlight { color: #087366; display: inline; }
    /* The orange underline is anchored to the actual word, not to the heading column. */
    .underline-word {
      position: relative;
      display: inline-block;
      white-space: nowrap;
      padding-bottom: .22em;
    }
    .underline-word svg {
      position: absolute;
      left: -1px;
      bottom: -.015em;
      width: calc(100% + 2px);
      height: .34em;
      min-height: 10px;
      overflow: visible;
      pointer-events: none;
    }
    .hero-description {
      max-width: 370px;
      margin: 12px 0 20px;
      color: #294b5e;
      font-size: 14px;
      line-height: 1.48;
      font-weight: 400;
    }
    .hero-actions { position: relative; z-index: 6; display: flex; flex-wrap: wrap; gap: 16px; align-items: center; }
    .button {
      min-height: 40px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      border-radius: 999px;
      padding: 0 17px;
      font-size: 10px;
      font-weight: 700;
      transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
    }
    .button:hover { transform: translateY(-2px); }
    .button-primary {
      color: white;
      min-width: 160px;
      background: linear-gradient(135deg, #ffa32b, #f1840d);
      box-shadow: 0 8px 18px rgba(232, 131, 15, .22);
    }
    .button-primary:hover { box-shadow: 0 11px 22px rgba(232,131,15,.3); }
    .button-secondary {
      min-width: 126px;
      border: 1px solid rgba(8, 112, 101, .45);
      color: #103e40;
      background: rgba(255,255,255,.9);
    }
    .button svg { width: 15px; height: 15px; }

    /* Local warehouse photography sits softly within the hero's organic artwork. */
    .hero-visual {
      min-width: 0;
      height: 355px;
      position: relative;
      align-self: center;
      margin-right: -48px;
      margin-left: -10px;
    }
    .hero-visual::before {
      content: "";
      position: absolute;
      inset: 6% -5% 1% 0;
      border-radius: 48% 0 0 30%;
      background: linear-gradient(135deg, rgba(214,239,231,.2), rgba(5,104,91,.14));
      filter: blur(10px);
    }
    .warehouse-hero-image {
      position: absolute;
      width: 112%;
      max-height: 100%;
      top: 50%;
      left: -2%;
      display: block;
      object-fit: contain;
      object-position: center;
      transform: translateY(-50%);
    }
    .module-stat {
      position: absolute;
      z-index: 4;
      top: 28px;
      right: clamp(28px, calc(20vw - 192px), 48px);
      width: 168px;
      min-height: 94px;
      padding: 13px 15px;
      display: flex;
      align-items: center;
      gap: 12px;
      border-radius: 19px;
      color: white;
      background: linear-gradient(135deg, rgba(5,93,82,.78), rgba(7,68,63,.83));
      border: 1px solid rgba(255,255,255,.35);
      box-shadow: 0 14px 30px rgba(4,67,60,.2), inset 0 1px 0 rgba(255,255,255,.25);
      backdrop-filter: blur(14px);
    }
    .stat-cube { width: 31px; height: 35px; flex: 0 0 auto; }
    .stat-main { flex: 1; min-width: 0; }
    .stat-number { display: block; font: 800 29px/1 "Inter", Arial, sans-serif; }
    .stat-label { display: block; margin-top: 7px; font-size: 9px; color: #d5e9e3; white-space: nowrap; }
    .stat-signal { align-self: flex-end; display: flex; align-items: end; gap: 3px; height: 28px; margin-bottom: 2px; }
    .stat-signal i { display: block; width: 5px; border-radius: 2px 2px 0 0; background: rgba(164,225,209,.55); }
    .stat-signal i:nth-child(1) { height: 9px; }
    .stat-signal i:nth-child(2) { height: 14px; }
    .stat-signal i:nth-child(3) { height: 20px; }
    .stat-signal i:nth-child(4) { height: 26px; }
    .hero-dots { position: absolute; right: clamp(28px, calc(20vw - 192px), 48px); bottom: 9px; display: flex; gap: 7px; z-index: 5; }
    .hero-dots span { width: 8px; height: 8px; border-radius: 50%; background: rgba(8,94,84,.48); }
    .hero-dots span.is-active { background: var(--orange); }

    /* ---------- Modules ---------- */
    .modules-section {
      position: relative;
      padding: 16px 0 39px;
      background:
        radial-gradient(ellipse at 92% 85%, rgba(211,236,227,.48), transparent 26%),
        linear-gradient(180deg, #f8f8f4 0%, #f8f8f4 100%);
    }
    .modules-section::before {
      content: "";
      position: absolute;
      top: 10px;
      left: 8px;
      width: 44px;
      height: 62px;
      opacity: .55;
      background-image: radial-gradient(#75bdb0 1.3px, transparent 1.3px);
      background-size: 10px 10px;
    }
    .modules-section::after {
      content: "";
      position: absolute;
      right: -135px;
      bottom: -30px;
      width: 270px;
      height: 190px;
      border-radius: 80% 0 0 0;
      background: linear-gradient(140deg, rgba(32,170,150,.08), rgba(8,117,103,.23));
      transform: rotate(-15deg);
      pointer-events: none;
    }
    .section-inner { width: min(884px, calc(100% - 80px)); margin: auto; position: relative; z-index: 1; }
    .section-heading { display: flex; justify-content: space-between; align-items: center; gap: 24px; margin-bottom: 20px; }
    .section-kicker {
      display: inline-flex;
      align-items: center;
      gap: 9px;
      margin-bottom: 8px;
      color: #0a645c;
      font-size: 10px;
      font-weight: 800;
      letter-spacing: .15px;
      text-transform: uppercase;
    }
    .section-kicker::before { content: ""; width: 4px; height: 4px; background: #159889; border-radius: 50%; }
    .section-kicker::after { content: ""; width: 23px; height: 1px; background: #80bdb1; }
    h2 {
      margin: 0 0 7px;
      color: #0a3032;
      font: 800 20px/1.2 "Inter", Arial, sans-serif;
      letter-spacing: -.02em;
    }
    .section-description { margin: 0; color: #35566a; font-size: 14px; line-height: 1.55; max-width: 460px; }
    .all-modules {
      display: inline-flex;
      align-items: center;
      gap: 15px;
      flex: 0 0 auto;
      color: #075e55;
      font-size: 10px;
      font-weight: 700;
      margin-top: 18px;
    }
    .all-modules span {
      width: 24px; height: 24px; display: grid; place-items: center;
      border-radius: 50%; background: #087567; color: white;
      transition: transform .2s;
    }
    .all-modules:hover span { transform: translateX(3px); }
    .all-modules svg { width: 13px; height: 13px; }
    .module-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 10px 12px; }
    .module-card {
      position: relative;
      min-height: 161px;
      padding: 13px 16px 12px;
      border: 1px solid rgba(16, 78, 70, .09);
      border-radius: 17px;
      background: rgba(255,255,255,.88);
      box-shadow: 0 6px 16px rgba(29, 70, 63, .075);
      transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    }
    .module-card:hover {
      transform: translateY(-4px);
      border-color: rgba(8,123,109,.27);
      box-shadow: 0 14px 26px rgba(29,70,63,.12);
    }
    .module-icon {
      width: 49px; height: 44px;
      display: grid; place-items: center;
      margin-bottom: 9px;
      color: #08796c;
      border-radius: 14px;
      background: linear-gradient(145deg, #e9f6f1, #ccebe2);
    }
    .module-icon svg { width: 27px; height: 27px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .card-arrow {
      position: absolute;
      top: 15px; right: 13px;
      width: 20px; height: 20px;
      display: grid; place-items: center;
      border-radius: 50%;
      color: #087469;
      background: #d9eee8;
    }
    .card-arrow svg { width: 12px; height: 12px; }
    .module-card h3 {
      margin: 0 0 7px;
      color: #102e35;
      font: 800 14px/1.25 "Inter", Arial, sans-serif;
      letter-spacing: -.02em;
    }
    .module-card p {
      margin: 0;
      color: #405c70;
      font-size: 13px;
      line-height: 1.42;
      font-weight: 400;
    }

    /* ---------- Workflow panel ---------- */
    .workflow-section {
      padding: 0 0 44px;
      background: linear-gradient(180deg, #f8f8f4, #fbfbf8);
      position: relative;
    }
    .workflow-panel {
      width: min(944px, calc(100% - 80px));
      min-height: 344px;
      margin: auto;
      position: relative;
      overflow: hidden;
      isolation: isolate;
      border: 1px solid rgba(12,119,105,.08);
      border-radius: 42px;
      padding: 27px 34px 25px;
      background:
        linear-gradient(90deg, rgba(239,247,242,.98) 0%, rgba(235,246,240,.91) 35%, rgba(221,239,231,.69) 100%),
        linear-gradient(135deg, #edf5ef, #dceee6);
    }
    .workflow-panel::before {
      content: none;
    }
    .workflow-bg-media {
      position: absolute;
      top: 0;
      right: 0;
      width: 58%;
      height: 220px;
      z-index: 0;
      pointer-events: none;
      overflow: hidden;
      border-top-right-radius: 42px;
    }
    .workflow-bg-media img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center 30%;
      display: block;
      opacity: 0.85;
      filter: saturate(1.08) contrast(1.02);
    }
    .workflow-bg-fade {
      position: absolute;
      inset: 0;
      background:
        linear-gradient(to right, rgba(239,247,242,1) 0%, rgba(239,247,242,0.7) 20%, rgba(239,247,242,0) 55%),
        linear-gradient(to bottom, rgba(239,247,242,0.05) 0%, rgba(230,244,237,0.6) 60%, rgba(221,239,231,0.98) 100%);
    }
    .workflow-panel::after {
      content: "";
      position: absolute;
      z-index: -1;
      right: -100px;
      bottom: -155px;
      width: 360px; height: 270px;
      border-radius: 50%;
      border: 1px solid rgba(8,121,106,.14);
      box-shadow: 0 0 0 25px rgba(8,121,106,.035), 0 0 0 50px rgba(8,121,106,.025);
    }
    .workflow-heading { position: relative; z-index: 1; }
    .workflow-heading h2 { margin-top: 0; font-size: 29px; }
    .workflow-heading .section-description { max-width: 410px; font-size: 11.5px; line-height: 1.5; }
    .workflow-steps {
      position: relative;
      z-index: 2;
      display: grid;
      grid-template-columns: repeat(3, minmax(0,1fr));
      gap: 54px;
      margin-top: 27px;
    }
    .workflow-step {
      min-height: 133px;
      position: relative;
      padding: 23px 15px 15px;
      text-align: center;
      border: 1px solid rgba(255,255,255,.95);
      border-radius: 22px;
      background: rgba(255,255,255,.86);
      box-shadow: 0 10px 22px rgba(30,83,72,.105), inset 0 1px 0 rgba(255,255,255,.9);
      backdrop-filter: blur(10px);
    }
    .step-number {
      position: absolute;
      top: -11px; left: 50%;
      transform: translateX(-50%);
      width: 29px; height: 29px;
      display: grid; place-items: center;
      border-radius: 50%;
      background: linear-gradient(145deg, #0b8878, #056257);
      color: white;
      font: 800 12px "Manrope", sans-serif;
      box-shadow: 0 4px 10px rgba(6,104,93,.2);
    }
    .step-icon { width: 35px; height: 34px; margin: 0 auto 5px; color: #078171; }
    .step-icon svg { width: 100%; height: 100%; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .workflow-step h3 { margin: 0 0 7px; color: #102e35; font: 700 11.5px/1.3 "Manrope", sans-serif; }
    .workflow-step p { margin: 0; color: #405d70; font-size: 10px; line-height: 1.42; }
    .step-connector {
      position: absolute;
      right: -44px;
      top: 50%;
      transform: translateY(-50%);
      width: 31px; height: 31px;
      display: grid; place-items: center;
      border-radius: 50%;
      border: 1px solid #159487;
      background: rgba(239,248,244,.78);
      color: #078171;
    }
    .step-connector svg { width: 16px; height: 16px; }

    /* ---------- Footer ---------- */
    .site-footer {
      min-height: 153px;
      position: relative;
      overflow: hidden;
      isolation: isolate;
      padding: 23px 0 18px;
      color: white;
      background: linear-gradient(115deg, #064a43 0%, #003e39 60%, #003b36 100%);
      border-radius: 24px 24px 0 0;
    }
    .site-footer::before {
      content: "";
      position: absolute;
      z-index: -1;
      width: 470px; height: 210px;
      right: -40px; bottom: -140px;
      border: 1px solid rgba(23,171,151,.55);
      border-radius: 50%;
      transform: rotate(-19deg);
      box-shadow: 0 -17px 0 -16px rgba(23,171,151,.6), 0 -36px 0 -35px rgba(23,171,151,.45);
    }
    .site-footer::after {
      content: "";
      position: absolute;
      z-index: -1;
      right: -65px; top: 40px;
      width: 520px; height: 180px;
      background: linear-gradient(155deg, transparent 35%, rgba(9,105,94,.32) 36%, rgba(9,105,94,.08) 75%, transparent 76%);
      transform: skewY(-12deg);
    }
    .footer-inner { width: min(944px, calc(100% - 80px)); margin: auto; }
    .footer-top { display: flex; align-items: center; justify-content: space-between; gap: 20px; min-height: 39px; }
    .footer-brand { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .footer-brand .brand-name { color: #fff; font-size: 17px; }
    .footer-brand .brand-divider { background: rgba(255,255,255,.65); margin-left: 10px; margin-right: 12px; height: 22px; }
    .footer-brand-text { color: rgba(255,255,255,.92); font-size: 10px; white-space: nowrap; }
    .footer-links { display: flex; align-items: center; gap: 24px; color: #f1f7f4; font-size: 10px; font-weight: 500; }
    .footer-links a:hover { color: #ffb24c; }
    .version {
      padding: 6px 13px;
      border: 1px solid rgba(255,255,255,.25);
      border-radius: 99px;
      color: white;
      font-size: 10px;
    }
    .footer-divider { height: 1px; margin: 17px 0 16px; background: rgba(255,255,255,.16); }
    .copyright { color: rgba(255,255,255,.9); font-size: 10px; }

    /* ---------- Responsive ---------- */
    @media (min-width: 1200px) {
      :root { --content: 1000px; }
      .hero-inner, .header-inner { width: min(1000px, calc(100% - 100px)); }
      .section-inner { width: min(1000px, calc(100% - 100px)); }
      .workflow-panel, .footer-inner { width: min(1060px, calc(100% - 100px)); }
      .site-header { top: 40px; }
      .brand-pill, .nav-pill { min-height: 60px; }
      .brand-pill { flex-basis: auto; }
      .nav-pill { flex: 0 0 auto; width: auto; justify-content: flex-end; }
      .brand-pill { padding-inline: 24px; gap: 12px; }
      .brand-mark { width: 27px; height: 31px; }
      .brand-name { font-size: 20px; }
      .brand-description { font-size: 11px; }
      .brand-divider { height: 22px; }
      .nav-pill { justify-content: flex-end; padding: 6px 10px 6px 10px; gap: 12px; }
      .nav-login { margin-left: 4px; }
      .nav-link { font-size: 13px; }
      .nav-login { min-width: 132px; height: 46px; font-size: 13px; }
      .nav-login svg { width: 16px; height: 16px; }
      .hero { min-height: 640px; padding: 140px 0 70px; }
      .hero-inner { min-height: 430px; grid-template-columns: .92fr 1.08fr; gap: 0; }
      .hero-copy { padding: 18px 0 10px 26px; }
      .hero-eyebrow { min-height: 32px; margin-bottom: 17px; padding: 8px 14px; font-size: 12px; }
      h1 { max-width: 540px; font-size: clamp(42px, 3.8vw, 52px); line-height: 1.05; letter-spacing: -2.3px; }
      .hero-title-line.highlight { display: block; }
      .hero-description { max-width: 500px; margin: 17px 0 26px; font-size: 15px; line-height: 1.55; }
      .hero-actions { gap: 18px; }
      .button { min-height: 54px; padding-inline: 24px; gap: 11px; font-size: 13px; }
      .button-primary { min-width: 212px; }
      .button-secondary { min-width: 168px; }
      .button svg { width: 18px; height: 18px; }
      .hero-visual { height: 430px; margin-left: -14px; }
      .module-stat { top: 32px; right: 48px; width: 220px; min-height: 122px; padding: 16px 18px; gap: 13px; border-radius: 22px; }
      .stat-cube { width: 38px; height: 42px; }
      .stat-number { font-size: 36px; }
      .stat-label { margin-top: 8px; font-size: 11px; }
      .stat-signal { height: 32px; }
      .stat-signal i { width: 6px; }
      .module-card { min-height: 174px; }
    }
    @media (max-width: 820px) {
      .container, .header-inner, .hero-inner, .section-inner, .workflow-panel, .footer-inner { width: calc(100% - 48px); }
      .hero { min-height: 0; padding-top: 105px; }
      .hero-inner { grid-template-columns: 1fr 1fr; }
      h1 { font-size: clamp(32px, 5vw, 39px); }
      .hero-visual { height: 300px; margin-right: -38px; }
      .module-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
      .module-card { min-height: 150px; }
      .workflow-panel { padding-inline: 25px; }
      .workflow-steps { gap: 35px; }
      .step-connector { right: -33px; }
    }
    @media (max-width: 600px) {
      .site-header { top: 12px; }
      .header-inner { width: calc(100% - 28px); gap: 8px; }
      .brand-pill { min-height: 42px; padding: 0 12px; gap: 7px; }
      .brand-mark { width: 19px; height: 22px; }
      .brand-name { font-size: 14px; }
      .brand-description, .brand-divider { display: none; }
      .nav-pill { min-height: 42px; padding: 4px; gap: 0; }
      .nav-link { display: none; }
      .nav-login { min-width: 88px; height: 33px; font-size: 10px; }
      .hero { padding: 91px 0 35px; }
      .hero-inner { width: calc(100% - 40px); display: flex; flex-direction: column; align-items: stretch; gap: 0; }
      .hero-copy { padding: 0; }
      .hero-eyebrow { margin-bottom: 12px; font-size: 9px; }
      h1 { max-width: 440px; font-size: clamp(35px, 9vw, 43px); letter-spacing: -1.8px; line-height: 1.07; }
        .hero-description { max-width: 440px; font-size: 12px; margin: 10px 0 17px; }
      .hero-actions { gap: 10px; }
      .button { min-height: 39px; font-size: 10px; }
      .button-primary { min-width: 145px; }
      .button-secondary { min-width: 116px; }
      .hero-visual { width: 100%; height: 245px; margin: 4px -10px -4px; }
      .warehouse-hero-image { width: 112%; left: -6%; }
      .module-stat { width: 126px; min-height: 73px; top: 18px; right: 0; padding: 9px 10px; border-radius: 14px; gap: 7px; }
      .stat-cube { width: 23px; height: 26px; }
      .stat-number { font-size: 23px; }
      .stat-label { font-size: 7px; margin-top: 4px; }
      .stat-signal { height: 21px; }
      .stat-signal i { width: 4px; }
      .hero-dots { right: 8px; bottom: 0; }
      .modules-section { padding: 23px 0 32px; }
      .section-inner { width: calc(100% - 38px); }
      .section-heading { align-items: flex-start; margin-bottom: 18px; }
      h2 { font-size: 26px; }
      .section-description { font-size: 11px; }
      .all-modules { display: none; }
      .module-grid { grid-template-columns: repeat(2, minmax(0,1fr)); gap: 10px; }
      .module-card { min-height: 165px; padding: 12px 12px 11px; border-radius: 15px; }
      .module-icon { width: 43px; height: 40px; margin-bottom: 9px; }
      .module-icon svg { width: 24px; height: 24px; }
      .card-arrow { width: 18px; height: 18px; top: 12px; right: 10px; }
      .module-card h3 { font-size: 11px; }
      .module-card p { font-size: 10px; line-height: 1.4; }
      .workflow-section { padding-bottom: 28px; }
      .workflow-panel { width: calc(100% - 28px); padding: 24px 17px 22px; border-radius: 27px; }
      .workflow-panel::before { width: 100%; opacity: .35; }
      .workflow-heading h2 { font-size: 25px; }
      .workflow-heading .section-description { font-size: 10.5px; }
      .workflow-steps { grid-template-columns: 1fr; gap: 20px; margin-top: 27px; }
      .workflow-step { min-height: 130px; padding: 21px 17px 15px; }
      .workflow-step h3 { font-size: 12px; }
      .workflow-step p { max-width: 330px; margin-inline: auto; font-size: 10.5px; }
      .step-connector { right: auto; left: 50%; top: auto; bottom: -17px; transform: translateX(-50%) rotate(90deg); width: 26px; height: 26px; }
      .site-footer { min-height: 0; padding: 23px 0 17px; border-radius: 18px 18px 0 0; }
      .footer-inner { width: calc(100% - 38px); }
      .footer-top { align-items: flex-start; flex-direction: column; gap: 17px; }
      .footer-brand .brand-name { font-size: 16px; }
      .footer-brand-text { font-size: 9px; }
      .footer-links { flex-wrap: wrap; gap: 16px; font-size: 10px; }
      .footer-divider { margin: 15px 0 13px; }
      .copyright { font-size: 9px; }
    }
    @media (max-width: 360px) {
      .brand-pill { padding-inline: 9px; }
      .brand-name { font-size: 13px; }
      h1 { font-size: 34px; }
      .module-card { padding-inline: 10px; }
      .module-card h3 { font-size: 10.5px; }
      .module-card p { font-size: 9.5px; }
    }
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; }
    }
  </style>

<style>
  /* FINAL MOBILE LAYOUT CORRECTION — preserve the visual identity, fix composition only. */
  .hero-title-line { display: block; width: max-content; max-width: 100%; }
  @media (max-width: 700px) {
    /* Remove the oversized decorative orb from the text area on phones. */
    .hero { position: relative; overflow: hidden; padding-top: 88px !important; }
    .hero::before, .hero::after {
      content: none !important;
      display: none !important;
      visibility: hidden !important;
      background: none !important;
    }
    .hero-inner { width: min(100% - 36px, 520px) !important; gap: 0 !important; }
    .hero-copy { position: relative; z-index: 2; width: 100%; }
    h1 {
      max-width: 100%;
      font-size: clamp(30px, 8.1vw, 34px) !important;
      line-height: 1.13 !important;
      letter-spacing: -1.15px !important;
      text-wrap: balance;
    }
    /* Keep the headline hierarchy intentional: three balanced lines. */
    .hero-title-line { display: block; width: max-content; max-width: 100%; }
    .hero-title-first, .hero-title-last { white-space: nowrap; }
    h1 .highlight { display: block !important; }
    .underline-word { display: inline-block; white-space: nowrap; padding-bottom: .22em; }
    .underline-word svg { width: calc(100% + 2px); height: .27em; bottom: -.01em; }
    .hero-description { max-width: 100%; margin: 12px 0 17px; font-size: 14px; line-height: 1.58; }
    /* Two balanced CTAs reduce the vertical stack and keep both actions visible sooner. */
    .hero-actions { display: grid; grid-template-columns: minmax(0, 1.08fr) minmax(0, .92fr); gap: 10px; width: 100%; }
    .hero-actions .button { width: 100%; min-width: 0; min-height: 48px; padding: 0 10px; gap: 7px; font-size: 12px; white-space: nowrap; }
    .hero-actions .button svg { width: 16px; height: 16px; flex: 0 0 auto; }
    .hero-visual { margin-top: 20px; width: 100%; max-width: 520px; height: clamp(215px, 62vw, 270px); }
  }
  @media (max-width: 380px) {
    .hero-inner { width: calc(100% - 32px) !important; }
    h1 { font-size: 30px !important; letter-spacing: -1.1px !important; }
    .hero-actions { grid-template-columns: 1fr 1fr; gap: 8px; }
    .hero-actions .button { font-size: 11px; padding-inline: 7px; }
  }
  @media (max-width: 340px) {
    .hero-inner { width: calc(100% - 28px) !important; }
    h1 { font-size: 28px !important; }
    .hero-actions { grid-template-columns: 1fr; }
  }
</style>

<style>
  .skip-link { position: absolute; z-index: 100; top: 8px; left: 8px; padding: 10px 16px; border-radius: 8px; background: #fff; color: #092f31; font-weight: 700; transform: translateY(-160%); }
  .skip-link:focus { transform: translateY(0); outline: 3px solid #ff941f; outline-offset: 3px; }
</style>
