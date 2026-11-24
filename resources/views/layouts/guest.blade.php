<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="turbo-prefetch" content="true">
    <meta name="turbo-cache-control" content="cache">
    <title>@yield('title', 'Masuk — ' . config('app.name', 'SIGMA'))</title>

    {{-- Favicon --}}
    <link rel="icon"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Cpolygon points='16,2 29,9 29,23 16,30 3,23 3,9' fill='%23145C54'/%3E%3Cpolygon points='16,2 29,9 16,16 3,9' fill='%23C97A2B'/%3E%3C/svg%3E">

    {{-- Local compiled assets & Alpine.js runtime --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /*
   * ─────────────────────────────────────────────────
   *  Design System Tokens — PRD Section 17
   * ─────────────────────────────────────────────────
   */
        :root {
            --brand-primary: #145C54;
            --brand-primary-d: #0f4740;
            --brand-accent: #C97A2B;
            --brand-accent-d: #a86020;

            --surface-base: #F7F6F3;
            --surface-card: #FFFFFF;
            --surface-border: #E2DFD9;

            --text-primary: #1F2523;
            --text-muted: #5B6B79;
            --text-subtle: #8A9AA6;

            --status-critical: #C1443C;
            --status-warning: #B8791A;
            --status-success: #3F7A52;
            --status-neutral: #5B6B79;

            --radius: 8px;
            --radius-lg: 14px;
            --font-body: 'Inter', system-ui, -apple-system, sans-serif;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: var(--font-body);
            font-size: 15px;
            line-height: 1.6;
            color: var(--text-primary);
            background-color: var(--surface-base);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Grid background berlapisan halus */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            z-index: -1;
            background-image:
                linear-gradient(var(--surface-border) 1px, transparent 1px),
                linear-gradient(90deg, var(--surface-border) 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: 0.35;
            pointer-events: none;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .tabular {
            font-variant-numeric: tabular-nums;
        }

        /* Strict SVG dimensions to prevent icon overflow across browsers */
        svg.icon-inline {
            display: inline-block;
            vertical-align: middle;
            flex-shrink: 0 !important;
            max-width: 100%;
        }

        svg[width="14"] {
            width: 14px !important;
            height: 14px !important;
            min-width: 14px !important;
            min-height: 14px !important;
        }

        svg[width="16"] {
            width: 16px !important;
            height: 16px !important;
            min-width: 16px !important;
            min-height: 16px !important;
        }

        svg[width="18"] {
            width: 18px !important;
            height: 18px !important;
            min-width: 18px !important;
            min-height: 18px !important;
        }

        svg[width="20"] {
            width: 20px !important;
            height: 20px !important;
            min-width: 20px !important;
            min-height: 20px !important;
        }

        svg[width="28"] {
            width: 28px !important;
            height: 28px !important;
            min-width: 28px !important;
            min-height: 28px !important;
        }

        .layout-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
        }
    </style>
    @stack('styles')
</head>

<body>

    <main class="layout-container" id="main-content">
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>
