<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SIGMA — Sistem Informasi Gudang & Manajemen Aset">
    <title>{{ $appName }} — Sistem Informasi Gudang & Manajemen Aset</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg-page:        #f7faf8;
            --bg-card:        #ffffff;
            --bg-icon:        #e3f2ec;
            --bg-footer:      #01322d;
            --teal-heading:   #165d55;
            --teal-primary:   #0f5547;
            --teal-dark:      #0c2a26;
            --text-dark:      #0d2222;
            --text-body:      #50665d;
            --text-muted:     #6b8070;
            --orange-btn:     #e58d24;
            --orange-swoosh:  #e89b25;
            --border-card:    #eef2ef;
            --radius-pill:    9999px;
            --radius-card:    20px;
            --font:           'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --max-w:          1160px;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font);
            background: var(--bg-page);
            color: var(--text-dark);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
            position: relative;
        }

        /* ─── AMBIENT BACKGROUND GRAPHICS ─────────────────────────── */
        /* Smooth subtle top-right green ribbon extension */
        .bg-top-right-wave {
            position: absolute;
            top: 70px;
            right: 0;
            width: 320px;
            height: 300px;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
            opacity: 0.9;
        }
        .bg-top-right-wave svg {
            width: 100%;
            height: 100%;
        }

        /* ─── CONTAINER ─────────────────────────────────────────── */
        .container {
            width: 100%;
            max-width: var(--max-w);
            margin: 0 auto;
            padding: 0 32px;
            position: relative;
            z-index: 2;
        }

        /* ─── NAVBAR ─────────────────────────────────────────────── */
        .navbar-wrap {
            padding: 24px 0 16px;
            position: relative;
            z-index: 10;
        }

        .navbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar-inner > nav {
            background: #ffffff;
            border-radius: var(--radius-pill);
            padding: 4px 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }

        /* Brand floating capsule */
        .brand-pill {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: #ffffff;
            border-radius: var(--radius-pill);
            padding: 7px 22px 7px 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .brand-pill:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 24px rgba(0,0,0,0.09);
        }

        .brand-logo-cube {
            width: 28px;
            height: 28px;
            flex-shrink: 0;
            display: block;
        }

        .brand-divider {
            width: 1px;
            height: 20px;
            background: #d4deda;
        }

        .brand-name {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--teal-dark);
            letter-spacing: -0.01em;
        }

        .brand-tagline {
            font-size: 0.75rem;
            font-weight: 500;
            color: #556e66;
            white-space: nowrap;
        }

        /* Right nav */
        .nav-right {
            display: flex;
            align-items: center;
            gap: 32px;
            list-style: none;
        }

        .nav-right a {
            font-size: 0.875rem;
            font-weight: 600;
            color: #1a332d;
            text-decoration: none;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            transition: color 0.2s;
        }
        .nav-right a:hover {
            color: var(--teal-primary);
        }

        .nav-btn-masuk {
            display: inline-flex !important;
            align-items: center;
            gap: 8px;
            background: var(--teal-primary) !important;
            color: #ffffff !important;
            border-radius: var(--radius-pill);
            padding: 8px 22px;
            font-size: 0.875rem !important;
            font-weight: 700 !important;
            text-decoration: none;
            box-shadow: 0 2px 10px rgba(15,85,71,0.25);
            transition: background 0.2s, transform 0.15s !important;
        }
        .nav-btn-masuk:hover {
            background: #0a4337 !important;
            transform: translateY(-1px);
        }
        .nav-btn-masuk svg {
            width: 16px;
            height: 16px;
        }

        /* ─── HERO SECTION ───────────────────────────────────────── */
        .hero {
            position: relative;
            padding: 36px 0 24px;
            min-height: 450px;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1fr 1.32fr;
            align-items: center;
            gap: 32px;
            position: relative;
        }

        /* Hero Left */
        .hero-copy {
            padding-bottom: 16px;
            position: relative;
            z-index: 2;
        }

        .hero-h1 {
            font-size: clamp(2.3rem, 3.6vw, 3rem);
            font-weight: 800;
            line-height: 1.15;
            color: var(--text-dark);
            letter-spacing: -0.025em;
            margin-bottom: 22px;
        }

        .hero-teal-line {
            color: var(--teal-heading);
            display: inline-block;
        }

        .hero-gudang-wrap {
            display: inline-flex;
            align-items: flex-start;
            gap: 16px;
            white-space: nowrap;
        }

        .hero-word-gudang {
            display: inline-block;
        }

        .hero-swoosh {
            width: 220px;
            height: 18px;
            display: inline-block;
            margin-top: 10px;
        }

        .hero-lead {
            font-size: 0.92rem;
            line-height: 1.68;
            color: var(--text-body);
            max-width: 440px;
            margin-bottom: 34px;
        }

        .hero-cta-row {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn-orange {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #e58d24 0%, #db7f16 100%);
            color: #ffffff;
            border-radius: var(--radius-pill);
            padding: 13px 28px;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 8px 24px rgba(229, 141, 36, 0.35);
            transition: transform 0.15s, box-shadow 0.2s, background 0.2s;
        }
        .btn-orange:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(229, 141, 36, 0.45);
        }
        .btn-orange svg {
            width: 17px;
            height: 17px;
        }

        .btn-white-outline {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            color: var(--text-dark);
            border: 1.5px solid #d4dfdb;
            border-radius: var(--radius-pill);
            padding: 12px 26px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            transition: border-color 0.2s, color 0.2s, transform 0.15s;
        }
        .btn-white-outline:hover {
            border-color: var(--teal-primary);
            color: var(--teal-primary);
            transform: translateY(-2px);
        }
        .btn-white-outline svg {
            width: 14px;
            height: 14px;
            fill: var(--teal-primary);
        }

        /* Hero Right Visual */
        .hero-visual {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .hero-warehouse-img {
            display: block;
            width: 100%;
            max-width: 680px;
            height: auto;
            object-fit: contain;
            pointer-events: none;
            user-select: none;
        }

        /* Pagination dots bottom left */
        .hero-dots-indicator {
            position: absolute;
            left: 32px;
            bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
            z-index: 2;
        }
        .hero-dots-indicator span {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #b5c7bf;
            display: inline-block;
        }
        .hero-dots-indicator span.active {
            width: 18px;
            border-radius: 9999px;
            background: var(--teal-primary);
        }

        /* Dot matrix decoration */
        .dot-matrix {
            position: absolute;
            z-index: 1;
            opacity: 0.4;
            pointer-events: none;
        }

        /* ─── SECTION BADGES (PILL + ACCENT LINE) ─────────────────── */
        .badge-row {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .badge-pill {
            background: #eaf3ef;
            color: var(--teal-primary);
            font-size: 0.6875rem;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            border-radius: var(--radius-pill);
            padding: 5px 16px;
            display: inline-block;
        }

        .badge-line {
            width: 32px;
            height: 1.5px;
            background: #b5cdc2;
            display: inline-block;
        }

        .badge-line-arrow {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            color: #115d53;
        }
        .badge-line-arrow svg {
            width: 38px;
            height: 8px;
        }

        /* ─── MODULES SECTION ────────────────────────────────────── */
        .modules-section {
            padding: 48px 0 60px;
            position: relative;
        }

        .dot-matrix-modules {
            position: absolute;
            left: calc(50% - 620px);
            top: 40px;
            z-index: 1;
            opacity: 0.45;
            pointer-events: none;
        }

        .modules-hdr {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 36px;
            gap: 20px;
        }

        .modules-hdr h2 {
            font-size: 2.1rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.02em;
            line-height: 1.15;
            margin-bottom: 8px;
        }

        .modules-hdr p {
            font-size: 0.875rem;
            color: var(--text-body);
            line-height: 1.6;
            max-width: 440px;
        }

        .see-all-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--teal-primary);
            text-decoration: none;
            white-space: nowrap;
            flex-shrink: 0;
            transition: gap 0.2s;
        }
        .see-all-link:hover { gap: 14px; }

        .see-all-circle {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--teal-primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
        }
        .see-all-circle svg { width: 13px; height: 13px; }

        /* 4x2 Grid */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        .module-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: var(--radius-card);
            padding: 22px 20px 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.025);
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        .module-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(15,85,71,0.09);
            border-color: rgba(15,85,71,0.2);
        }

        .module-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .icon-box {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: var(--bg-icon);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #115d53;
            flex-shrink: 0;
            transition: background 0.2s, color 0.2s;
        }
        .icon-box svg { width: 22px; height: 22px; }
        .module-card:hover .icon-box {
            background: var(--teal-primary);
            color: #ffffff;
        }

        .arrow-circle {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #e8f3ee;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #115d53;
            flex-shrink: 0;
            transition: background 0.2s;
        }
        .arrow-circle svg { width: 11px; height: 11px; }
        .module-card:hover .arrow-circle { background: #d7ebe0; }

        .module-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #0d2222;
            line-height: 1.3;
            margin-top: 4px;
        }

        .module-desc {
            font-size: 0.8125rem;
            font-weight: 400;
            color: var(--text-body);
            line-height: 1.55;
            flex: 1;
        }

        /* ─── ALUR KERJA SECTION (GIANT ROUNDED CONTAINER) ────────── */
        .workflow-wrap {
            padding: 24px 0 48px;
        }

        .workflow-card-container {
            background: #edf5f0;
            border-radius: 36px;
            padding: 44px 44px 50px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.03);
            border: 1px solid rgba(21,93,83,0.06);
        }

        /* Racks background graphic in container */
        .workflow-racks-bg {
            position: absolute;
            top: 0;
            right: 0;
            width: 58%;
            height: 56%;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }

        .workflow-racks-bg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: top right;
            display: block;
        }

        .workflow-hdr {
            position: relative;
            z-index: 1;
            max-width: 460px;
            margin-bottom: 44px;
        }

        .workflow-hdr h2 {
            font-size: 2.1rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.02em;
            line-height: 1.15;
            margin-bottom: 8px;
        }
        .workflow-hdr h2 .teal {
            color: var(--teal-heading);
        }

        .workflow-hdr p {
            font-size: 0.875rem;
            color: var(--text-body);
            line-height: 1.6;
        }

        /* Step cards row */
        .workflow-steps-row {
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            gap: 0;
            position: relative;
            z-index: 1;
        }

        .step-card {
            flex: 1;
            background: #ffffff;
            border-radius: var(--radius-card);
            padding: 26px 22px 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.02);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .step-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 28px rgba(15,85,71,0.09);
        }

        .step-badge-num {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--teal-primary);
            color: #ffffff;
            font-size: 0.8125rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
        }

        .step-icon-svg {
            width: 32px;
            height: 32px;
            color: var(--teal-primary);
            margin-bottom: 14px;
        }

        .step-title {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1.3;
            margin-bottom: 8px;
        }

        .step-desc {
            font-size: 0.8125rem;
            color: var(--text-body);
            line-height: 1.55;
        }

        /* Step connector arrow */
        .step-connector {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 16px;
            color: var(--teal-primary);
            flex-shrink: 0;
        }
        .step-connector svg {
            width: 20px;
            height: 20px;
        }

        /* ─── FOOTER ─────────────────────────────────────────────── */
        footer {
            background: linear-gradient(180deg, #073d36 0%, #032722 100%);
            border-radius: 32px 32px 0 0;
            padding: 30px 0 24px;
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }

        /* Subtle thin decorative lines bottom-right */
        .footer-lines-decor {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 260px;
            height: 90px;
            pointer-events: none;
            opacity: 0.18;
        }

        .footer-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding-bottom: 14px;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .footer-brand-name {
            font-size: 1rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.01em;
        }

        .footer-divider {
            width: 1px;
            height: 18px;
            background: rgba(255,255,255,0.22);
        }

        .footer-tagline {
            font-size: 0.78rem;
            color: rgba(255,255,255,0.72);
            font-weight: 500;
        }

        .footer-nav {
            display: flex;
            align-items: center;
            gap: 26px;
        }

        .footer-nav a {
            font-size: 0.8125rem;
            font-weight: 500;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            transition: color 0.2s;
        }
        .footer-nav a:hover {
            color: #ffffff;
        }

        .footer-pill-version {
            border: 1px solid rgba(255,255,255,0.22);
            border-radius: var(--radius-pill);
            padding: 3px 14px;
            font-size: 0.75rem;
            color: rgba(255,255,255,0.65);
            font-weight: 600;
        }

        .footer-bottom-row {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.45);
            padding-top: 6px;
        }

        a:focus-visible {
            outline: 3px solid #e58d24;
            outline-offset: 4px;
        }

        [id] {
            scroll-margin-top: 24px;
        }

        .skip-link {
            position: absolute;
            top: 8px;
            left: 8px;
            z-index: 20;
            padding: 12px 16px;
            border-radius: 8px;
            background: #ffffff;
            color: var(--teal-dark);
            font-weight: 700;
            transform: translateY(-160%);
            transition: transform 0.2s ease;
        }

        .skip-link:focus {
            transform: translateY(0);
        }

        @media (max-width: 1024px) {
            :root {
                --max-w: 960px;
            }

            .container {
                padding-right: 28px;
                padding-left: 28px;
            }

            .hero {
                min-height: 0;
                padding: 24px 0 12px;
            }

            .hero-grid {
                grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
                gap: 18px;
            }

            .hero-h1 {
                font-size: clamp(1.9rem, 3.8vw, 2.7rem);
                line-height: 1.08;
            }

            .hero-swoosh {
                width: clamp(100px, 17vw, 160px);
            }

            .brand-tagline {
                font-size: 0.5rem;
            }

            .nav-right {
                gap: 22px;
            }

            .modules-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 12px;
            }
        }

        @media (min-width: 701px) and (max-width: 900px) {
            .container {
                padding-right: 48px;
                padding-left: 48px;
            }

            .navbar-wrap {
                padding: 16px 0 0;
            }

            .brand-pill {
                gap: 6px;
                padding: 7px 14px 7px 10px;
            }

            .brand-tagline {
                font-size: 0.43rem;
            }

            .nav-right {
                gap: 22px;
            }

            .nav-right a {
                min-height: 36px;
                font-size: 0.7rem;
            }

            .nav-btn-masuk {
                padding: 6px 20px;
                font-size: 0.72rem !important;
            }

            .hero {
                padding: 39px 0 12px;
            }

            .hero-grid {
                align-items: start;
            }

            .hero-copy {
                padding-bottom: 0;
            }

            .hero-h1 {
                margin-bottom: 12px;
            }

            .hero-lead {
                max-width: 300px;
                margin-bottom: 12px;
                font-size: 0.72rem;
                line-height: 1.35;
            }

            .hero-cta-row {
                flex-wrap: nowrap;
                gap: 10px;
            }

            .hero-cta-row .btn-orange,
            .hero-cta-row .btn-white-outline {
                min-height: 40px;
                gap: 7px;
                padding: 10px 14px;
                font-size: 0.72rem;
            }

            .hero-cta-row svg {
                width: 14px;
                height: 14px;
            }

            .hero-visual {
                align-self: start;
                margin: -36px -57px 0 -18px;
            }

            .hero-warehouse-img {
                height: 270px;
                object-fit: cover;
            }

            .modules-section {
                padding: 20px 0 28px;
            }

            .modules-grid {
                gap: 8px 12px;
            }

            .modules-hdr {
                align-items: flex-end;
                margin-bottom: 7px;
            }

            .badge-row {
                margin-bottom: 6px;
            }

            .badge-pill {
                padding: 3px 12px;
                font-size: 0.6rem;
            }

            .modules-hdr h2,
            .workflow-hdr h2 {
                font-size: 1.5rem;
                margin-bottom: 4px;
            }

            .modules-hdr p {
                font-size: 0.72rem;
                line-height: 1.35;
            }

            .module-card {
                min-height: 0;
                gap: 4px;
                padding: 10px 9px 8px;
            }

            .icon-box {
                width: 32px;
                height: 32px;
                border-radius: 11px;
            }

            .icon-box svg {
                width: 18px;
                height: 18px;
            }

            .module-title {
                font-size: 0.68rem;
                line-height: 1.2;
            }

            .module-desc {
                font-size: 0.625rem;
                line-height: 1.25;
            }

            .workflow-wrap {
                padding: 12px 0 34px;
            }

            .workflow-wrap .container {
                max-width: none;
                padding-right: 24px;
                padding-left: 24px;
            }

            .workflow-card-container {
                padding: 18px 24px 20px;
                border-radius: 28px;
            }

            .workflow-hdr {
                margin-bottom: 13px;
            }

            .workflow-hdr p,
            .step-desc {
                font-size: 0.68rem;
                line-height: 1.3;
            }

            .step-card {
                position: relative;
                height: 128px;
                min-height: 128px;
                padding: 18px 10px 8px;
            }

            .step-badge-num {
                position: absolute;
                top: -10px;
                left: calc(50% - 10px);
                width: 20px;
                height: 20px;
                margin: 0;
                font-size: 0.7rem;
            }

            .step-icon-svg {
                width: 24px;
                height: 24px;
                flex: 0 0 24px;
                margin-bottom: 6px;
            }

            .step-title {
                margin-bottom: 4px;
                font-size: 0.65rem;
                line-height: 1.15;
            }

            .step-desc {
                font-size: 0.58rem;
                line-height: 1.25;
            }

            .step-connector {
                padding: 0 8px;
            }

            .footer-top-row {
                padding-bottom: 10px;
            }

            .footer-nav {
                gap: 18px;
            }

            footer {
                padding: 18px 0 14px;
            }
        }

        @media (max-width: 700px) {
            .navbar-wrap {
                padding-top: 18px;
            }

            .navbar-inner {
                align-items: flex-start;
                gap: 16px;
            }

            .navbar-inner > nav {
                background: transparent;
                border-radius: 0;
                padding: 0;
                box-shadow: none;
            }

            .brand-pill {
                padding: 7px 14px 7px 10px;
            }

            .brand-tagline,
            .brand-divider {
                display: none;
            }

            .nav-right {
                gap: 10px;
            }

            .nav-right a {
                padding: 0 6px;
                font-size: 0.8rem;
            }

            .nav-btn-masuk {
                padding: 0 16px !important;
            }

            .hero {
                min-height: 0;
                padding-top: 36px;
                padding-bottom: 12px;
            }

            .hero-grid {
                grid-template-columns: minmax(0, 1fr);
                gap: 10px;
            }

            .hero-copy {
                max-width: 590px;
                padding-bottom: 0;
            }

            .hero-h1 {
                max-width: 600px;
                font-size: clamp(2.45rem, 7vw, 3.5rem);
            }

            .hero-lead {
                max-width: 560px;
                margin-bottom: 24px;
            }

            .hero-visual {
                justify-content: center;
                margin: -4px -16px 0;
            }

            .hero-warehouse-img {
                max-width: 620px;
            }

            .hero-dots-indicator {
                display: none;
            }

            .modules-section {
                padding-top: 38px;
                padding-bottom: 42px;
            }

            .modules-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 14px;
            }

            .modules-hdr {
                align-items: flex-start;
                margin-bottom: 24px;
            }

            .modules-hdr h2,
            .workflow-hdr h2 {
                font-size: 1.85rem;
            }

            .workflow-wrap {
                padding: 12px 0 36px;
            }

            .workflow-card-container {
                padding: 34px 28px;
                border-radius: 28px;
            }

            .workflow-hdr {
                margin-bottom: 28px;
            }

            .workflow-steps-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 12px;
            }

            .step-card {
                padding: 22px;
            }

            .step-connector {
                min-height: 16px;
                padding: 0;
                transform: rotate(90deg);
            }

            .footer-top-row {
                align-items: flex-start;
                flex-direction: column;
                gap: 18px;
            }

            .footer-nav {
                flex-wrap: wrap;
                gap: 8px 20px;
            }
        }

        @media (max-width: 520px) {
            .container {
                padding-right: 20px;
                padding-left: 20px;
            }

            .navbar-wrap {
                padding-top: 14px;
            }

            .navbar-inner {
                align-items: center;
                flex-wrap: wrap;
                row-gap: 10px;
            }

            .brand-name {
                font-size: 0.9rem;
            }

            .brand-logo-cube {
                width: 25px;
                height: 25px;
            }

            .navbar-inner > nav {
                width: 100%;
            }

            .nav-right {
                justify-content: space-between;
                gap: 4px;
            }

            .nav-right a {
                font-size: 0.78rem;
            }

            .hero {
                padding-top: 32px;
            }

            .hero-h1 {
                font-size: clamp(2.15rem, 10vw, 2.8rem);
                line-height: 1.12;
            }

            .hero-gudang-wrap {
                gap: 8px;
                white-space: normal;
            }

            .hero-swoosh {
                width: clamp(74px, 22vw, 112px);
                height: 14px;
                margin-top: 7px;
            }

            .hero-lead {
                font-size: 0.9rem;
            }

            .hero-cta-row {
                align-items: stretch;
                gap: 10px;
            }

            .btn-orange,
            .btn-white-outline {
                min-height: 48px;
                justify-content: center;
                padding: 12px 16px;
                font-size: 0.82rem;
            }

            .modules-hdr {
                flex-direction: column;
                gap: 16px;
            }

            .modules-hdr h2,
            .workflow-hdr h2 {
                font-size: 1.7rem;
            }

            .modules-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .module-card {
                min-height: 170px;
                padding: 18px;
            }

            .workflow-card-container {
                padding: 28px 18px;
                border-radius: 24px;
            }

            .workflow-racks-bg {
                width: 100%;
                height: 34%;
                opacity: 0.45;
            }

            .workflow-hdr p,
            .step-desc {
                font-size: 0.85rem;
            }

            footer {
                border-radius: 24px 24px 0 0;
                padding: 26px 0 22px;
            }

            .footer-brand {
                flex-wrap: wrap;
                gap: 8px;
            }

            .footer-brand .brand-logo-cube {
                width: 25px;
                height: 25px;
            }

            .footer-tagline {
                flex-basis: 100%;
                padding-left: 33px;
            }

            .footer-nav {
                gap: 8px 16px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
<a href="#main-content" class="skip-link">Lewati ke konten utama</a>

{{-- ═══════════════════════════════════════════════════════
     AMBIENT SVG BACKGROUNDS
═══════════════════════════════════════════════════════ --}}
<div class="bg-top-right-wave" aria-hidden="true">
    <svg viewBox="0 0 320 300" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M 0 0 C 80 40, 180 120, 320 280 L 320 0 Z" fill="url(#gradTop)"/>
        <defs>
            <linearGradient id="gradTop" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#0e5a46"/>
                <stop offset="100%" stop-color="#042419"/>
            </linearGradient>
        </defs>
    </svg>
</div>

{{-- ═══════════════════════════════════════════════════════
     NAVBAR
═══════════════════════════════════════════════════════ --}}
<div class="navbar-wrap">
    <div class="container navbar-inner">

        {{-- Floating Brand Capsule --}}
        <a href="{{ route('welcome') }}" class="brand-pill" aria-label="{{ $appName }} — Beranda">
            {{-- 3D Isometric Cube Logo --}}
            <svg class="brand-logo-cube" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M16 4.5 L26.5 10.2 L16 16 L5.5 10.2 Z" fill="#F0A528"/>
                <path d="M5.5 10.2 L16 16 L16 27.5 L5.5 21.7 Z" fill="#0D5548"/>
                <path d="M16 16 L26.5 10.2 L26.5 21.7 L16 27.5 Z" fill="#187B6A"/>
            </svg>
            <span class="brand-name">{{ $appName }}</span>
            <span class="brand-divider" aria-hidden="true"></span>
            <span class="brand-tagline">Sistem Informasi Gudang &amp; Manajemen Aset</span>
        </a>

        {{-- Nav Links --}}
        <nav aria-label="Navigasi Utama">
            <ul class="nav-right">
                <li><a href="#modul-fitur">Modul Fitur</a></li>
                <li><a href="#alur-kerja">Alur Kerja</a></li>
                <li>
                    <a href="{{ route('login') }}" class="nav-btn-masuk">
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M10 3h3a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-3M7 11l3-3-3-3M10 8H2"/>
                        </svg>
                        Masuk
                    </a>
                </li>
            </ul>
        </nav>

    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     HERO SECTION
═══════════════════════════════════════════════════════ --}}
<main id="main-content">
<section class="hero" aria-label="Pengenalan SIGMA">
    <div class="container hero-grid">

        {{-- Left: Text & CTA --}}
        <div class="hero-copy">
            <h1 class="hero-h1">
                Satu sistem untuk<br>
                <span class="hero-teal-line">seluruh pencatatan</span><br>
                <span class="hero-gudang-wrap">
                    <span class="hero-word-gudang">gudang</span>
                    {{-- Exact curved swoosh right beside "gudang" under "pencatatan" --}}
                    <svg class="hero-swoosh" viewBox="0 0 240 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M 4 8 C 65 15, 175 16, 234 10" stroke="#E89B25" stroke-width="4.5" stroke-linecap="round"/>
                    </svg>
                </span>
            </h1>

            <p class="hero-lead">
                SIGMA memudahkan alur kerja harian tim gudang mulai dari proses penerimaan &amp; pengeluaran barang, cetak label produk, hingga reminder stok minimum &amp; kedaluwarsa tanpa kertas, tanpa entri ganda.
            </p>

            <div class="hero-cta-row">
                <a href="{{ route('login') }}" class="btn-orange">
                    {{-- Rocket / Send icon --}}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m22 2-7 20-4-9-9-4Z"/>
                        <path d="M22 2 11 13"/>
                    </svg>
                    Masuk ke Sistem
                </a>

                <a href="#modul-fitur" class="btn-white-outline">
                    {{-- Solid Play Triangle --}}
                    <svg viewBox="0 0 16 16" aria-hidden="true">
                        <polygon points="4,2 14,8 4,14"/>
                    </svg>
                    Lihat Modul
                </a>
            </div>
        </div>

        {{-- Right: Warehouse 3D building + forklift + stats badge image --}}
        <div class="hero-visual">
            <img
                class="hero-warehouse-img"
                src="{{ asset('images/hero-warehouse-pixelperfect.png') }}"
                alt="Gudang modern SIGMA dengan forklift dan palet barang"
                width="640"
                loading="eager"
                fetchpriority="high"
            >
        </div>

    </div>

    {{-- Pagination dots on bottom left --}}
    <div class="hero-dots-indicator" aria-hidden="true">
        <span class="active"></span>
        <span></span>
        <span></span>
        <span></span>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     MODULES SECTION
═══════════════════════════════════════════════════════ --}}
<section class="modules-section" id="modul-fitur" aria-label="Modul yang Tersedia">
    {{-- Dot matrix decoration on far left of modules --}}
    <div class="dot-matrix dot-matrix-modules" aria-hidden="true">
        <svg width="68" height="76" viewBox="0 0 68 76">
            @for ($r = 0; $r < 6; $r++)
                @for ($c = 0; $c < 4; $c++)
                    <circle cx="{{ $c * 18 + 6 }}" cy="{{ $r * 13 + 6 }}" r="2" fill="#b0c5bb"/>
                @endfor
            @endfor
        </svg>
    </div>

    <div class="container">

        <div class="modules-hdr">
            <div>
                {{-- Pill badge + accent line --}}
                <div class="badge-row">
                    <span class="badge-pill">FITUR SISTEM</span>
                    <span class="badge-line" aria-hidden="true"></span>
                </div>
                <h2>Modul yang Tersedia</h2>
                <p>Seluruh modul di bawah ini dirancang khusus untuk operasional gudang dan manajemen aset — tanpa ribet, tanpa batas.</p>
            </div>

            <a href="{{ route('login') }}" class="see-all-link">
                Lihat Semua Modul
                <span class="see-all-circle" aria-hidden="true">
                    <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2.5 7h9M8 3.5l3.5 3.5L8 10.5"/>
                    </svg>
                </span>
            </a>
        </div>

        <div class="modules-grid" role="list">
            @foreach ($modules as $modul)
            <a href="{{ route('login') }}" class="module-card" role="listitem">
                <div class="module-card-top">
                    <div class="icon-box" aria-hidden="true">
                        @switch($modul['ikon'])
                            @case('database')
                                {{-- 3 Stacked Cylinders --}}
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <ellipse cx="12" cy="5" rx="9" ry="3"/>
                                    <path d="M3 5v5c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/>
                                    <path d="M3 10v5c0 1.66 4.03 3 9 3s9-1.34 9-3v-5"/>
                                    <path d="M3 15v4c0 1.66 4.03 3 9 3s9-1.34 9-3v-4"/>
                                </svg>
                                @break

                            @case('clipboard-check')
                                {{-- Angled Tag with Hole --}}
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="14" height="14" rx="2" transform="rotate(45 12 12)"/>
                                    <circle cx="12" cy="7.5" r="1.5" fill="currentColor"/>
                                </svg>
                                @break

                            @case('truck-incoming')
                                {{-- Delivery Truck --}}
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 3h14v13H1z"/>
                                    <path d="M15 8h4l3 3v5h-7V8z"/>
                                    <circle cx="5.5" cy="18.5" r="2.5"/>
                                    <circle cx="18.5" cy="18.5" r="2.5"/>
                                </svg>
                                @break

                            @case('truck-outgoing')
                                {{-- Isometric Cube Outline --}}
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 3l8 4.5v9l-8 4.5-8-4.5v-9l8-4.5z"/>
                                    <path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/>
                                </svg>
                                @break

                            @case('tag')
                                {{-- Price Tag / Diamond Barcode --}}
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="14" height="14" rx="2" transform="rotate(45 12 12)"/>
                                    <circle cx="12" cy="7.5" r="1.5" fill="currentColor"/>
                                </svg>
                                @break

                            @case('return')
                                {{-- U-Turn / Undo Arrow --}}
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 14L4 9l5-5"/>
                                    <path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/>
                                </svg>
                                @break

                            @case('shopping-cart')
                                {{-- Shopping Cart --}}
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="9" cy="21" r="1"/>
                                    <circle cx="20" cy="21" r="1"/>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                                </svg>
                                @break

                            @case('chart-bar')
                                {{-- 3 Vertical Bars --}}
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="20" x2="18" y2="10"/>
                                    <line x1="12" y1="20" x2="12" y2="4"/>
                                    <line x1="6"  y1="20" x2="6"  y2="14"/>
                                </svg>
                                @break

                            @default
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                                </svg>
                        @endswitch
                    </div>

                    {{-- Small Circle Arrow (top-right) --}}
                    <div class="arrow-circle" aria-hidden="true">
                        <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9L9 3M9 3H4M9 3V8"/>
                        </svg>
                    </div>
                </div>

                <h3 class="module-title">{{ $modul['nama'] }}</h3>
                <p class="module-desc">{{ $modul['deskripsi'] }}</p>
            </a>
            @endforeach
        </div>

    </div>
</section>

{{-- ═══════════════════════════════════════════════════════
     ALUR KERJA SECTION (GIANT ROUNDED CONTAINER)
═══════════════════════════════════════════════════════ --}}
<section class="workflow-wrap" id="alur-kerja" aria-label="Alur Kerja SIGMA">
    <div class="container">

        <div class="workflow-card-container">

            {{-- Background Racks Image inside top-right of container --}}
            <div class="workflow-racks-bg" aria-hidden="true">
                <img src="{{ asset('images/alur-racks-clean.png') }}" alt="" loading="lazy">
            </div>

            {{-- Header --}}
            <div class="workflow-hdr">
                {{-- Pill badge + line arrow --}}
                <div class="badge-row">
                    <span class="badge-pill">ALUR KERJA</span>
                    <span class="badge-line-arrow" aria-hidden="true">
                        <svg viewBox="0 0 40 8" fill="none" stroke="currentColor" stroke-width="1.5">
                            <line x1="0" y1="4" x2="34" y2="4"/>
                            <polyline points="30,1 34,4 30,7"/>
                        </svg>
                    </span>
                </div>

                <h2>Alur Kerja <span class="teal">SIGMA</span></h2>
                <p>Proses terpadu yang digunakan tim gudang setiap hari — dari input fisik hingga verifikasi akhir oleh Admin GU.</p>
            </div>

            {{-- 3 Step Cards --}}
            <div class="workflow-steps-row" role="list">

                {{-- Step 1 --}}
                <article class="step-card" role="listitem">
                    <div class="step-badge-num">1</div>
                    {{-- Document with Pen Icon --}}
                    <svg class="step-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                    <h3 class="step-title">Pencatatan Transaksi Langsung</h3>
                    <p class="step-desc">Recording GU mencatat penerimaan, pengeluaran, stok opname, label, dan retur langsung di sistem.</p>
                </article>

                {{-- Connector Arrow --}}
                <div class="step-connector" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </div>

                {{-- Step 2 --}}
                <article class="step-card" role="listitem">
                    <div class="step-badge-num">2</div>
                    {{-- Recycle Sync Arrows Icon --}}
                    <svg class="step-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="1 4 1 10 7 10"/>
                        <polyline points="23 20 23 14 17 14"/>
                        <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15"/>
                    </svg>
                    <h3 class="step-title">Konsolidasi &amp; Sync Real-Time</h3>
                    <p class="step-desc">Stok, selisih opname, dan referensi PO diperbarui otomatis di dashboard terpusat.</p>
                </article>

                {{-- Connector Arrow --}}
                <div class="step-connector" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </div>

                {{-- Step 3 --}}
                <article class="step-card" role="listitem">
                    <div class="step-badge-num">3</div>
                    {{-- Shield / Circle with Checkmark Icon --}}
                    <svg class="step-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <h3 class="step-title">Verifikasi &amp; Kunci Laporan</h3>
                    <p class="step-desc">Admin Gudang memverifikasi dan mengunci laporan serta menerima pengingat stok minimum dan kedaluwarsa.</p>
                </article>

            </div>

        </div>

    </div>
</section>
</main>

{{-- ═══════════════════════════════════════════════════════
     FOOTER
═══════════════════════════════════════════════════════ --}}
<footer>
    <div class="container">

        <div class="footer-top-row">
            {{-- Brand info --}}
            <div class="footer-brand">
                {{-- 3D Isometric Cube Logo --}}
                <svg class="brand-logo-cube" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M16 4.5 L26.5 10.2 L16 16 L5.5 10.2 Z" fill="#F0A528"/>
                    <path d="M5.5 10.2 L16 16 L16 27.5 L5.5 21.7 Z" fill="#0D5548"/>
                    <path d="M16 16 L26.5 10.2 L26.5 21.7 L16 27.5 Z" fill="#187B6A"/>
                </svg>
                <span class="footer-brand-name">{{ $appName }}</span>
                <span class="footer-divider" aria-hidden="true"></span>
                <span class="footer-tagline">Sistem Informasi Gudang &amp; Manajemen Aset</span>
            </div>

            {{-- Nav links & version pill --}}
            <nav class="footer-nav" aria-label="Footer Navigation">
                <a href="#modul-fitur">Modul Fitur</a>
                <a href="#alur-kerja">Alur Kerja</a>
                <a href="{{ route('login') }}">Masuk</a>
                <span class="footer-pill-version">v{{ $version }}</span>
            </nav>
        </div>

        <div class="footer-bottom-row">
            &copy; {{ date('Y') }} SIGMA. All rights reserved.
        </div>

    </div>
</footer>

</body>
</html>
