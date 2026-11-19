@extends('layouts.guest')

@section('title', 'Masuk — ' . config('app.name', 'SIGMA'))

@push('styles')
    <style>
        .auth-card {
            width: 100%;
            max-width: 440px;
            background: var(--surface-card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--surface-border);
            box-shadow:
                0 12px 32px -8px rgba(20, 92, 84, 0.12),
                0 0 0 1px rgba(0, 0, 0, 0.02);
            padding: 40px 36px 32px;
            position: relative;
            overflow: hidden;
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--brand-primary) 0%, var(--brand-accent) 100%);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .auth-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            text-decoration: none;
        }

        .auth-brand-name {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--brand-primary);
            letter-spacing: -.02em;
        }

        .auth-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(20, 92, 84, .08);
            color: var(--brand-primary);
            font-size: .75rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            border: 1px solid rgba(20, 92, 84, .15);
            letter-spacing: .04em;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .auth-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0 0 6px;
            letter-spacing: -.02em;
        }

        .auth-subtitle {
            font-size: .875rem;
            color: var(--text-muted);
            margin: 0;
            line-height: 1.5;
        }

        .auth-context {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin: 0 0 14px;
            padding: 5px 10px;
            border: 1px solid rgba(20, 92, 84, .16);
            border-radius: 999px;
            color: var(--brand-primary);
            background: rgba(20, 92, 84, .06);
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        /* Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius);
            font-size: .85rem;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.45;
        }

        .alert strong {
            font-weight: 700;
        }

        .alert-success {
            background: rgba(63, 122, 82, 0.1);
            border: 1px solid rgba(63, 122, 82, 0.25);
            color: var(--status-success);
        }

        .alert-danger {
            background: rgba(193, 68, 60, 0.08);
            border: 1px solid rgba(193, 68, 60, 0.25);
            color: var(--status-critical);
        }

        .alert-icon {
            flex-shrink: 0;
            margin-top: 1px;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: .85rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-left {
            position: absolute;
            left: 14px;
            color: var(--text-subtle);
            pointer-events: none;
            display: flex;
            align-items: center;
        }

        .form-control {
            width: 100%;
            height: 44px;
            padding: 10px 14px 10px 42px;
            font-family: var(--font-body);
            font-size: .9rem;
            color: var(--text-primary);
            background: #FFFFFF;
            border: 1.5px solid var(--surface-border);
            border-radius: var(--radius);
            outline: none;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out, background-color .15s ease-in-out;
        }

        .form-control.has-toggle {
            padding-right: 42px;
        }

        .form-control:focus {
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 3px rgba(20, 92, 84, 0.12);
        }

        .form-control.is-invalid {
            border-color: var(--status-critical);
            background-color: rgba(193, 68, 60, 0.02);
        }

        .form-control.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(193, 68, 60, 0.15);
        }

        .input-toggle-btn {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: var(--text-subtle);
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            border-radius: 4px;
            transition: color .15s, background-color .15s;
        }

        .input-toggle-btn:hover {
            color: var(--brand-primary);
            background: rgba(20, 92, 84, .07);
        }

        .input-toggle-btn:focus-visible,
        .back-link:focus-visible,
        .form-check-input:focus-visible {
            outline: 2px solid var(--brand-accent);
            outline-offset: 3px;
        }

        .invalid-feedback {
            font-size: .78rem;
            color: var(--status-critical);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .form-control::placeholder {
            color: var(--text-subtle);
            opacity: 1;
        }

        /* Checkbox */
        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
            user-select: none;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            border: 1.5px solid var(--surface-border);
            accent-color: var(--brand-primary);
            cursor: pointer;
            margin: 0;
        }

        .form-check-label {
            font-size: .85rem;
            color: var(--text-muted);
            cursor: pointer;
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            height: 46px;
            background: var(--brand-primary);
            color: #FFFFFF;
            border: 1px solid var(--brand-primary);
            border-radius: var(--radius);
            font-family: var(--font-body);
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(20, 92, 84, 0.2);
            transition: all .2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-submit:hover:not(:disabled) {
            background: var(--brand-primary-d);
            border-color: var(--brand-primary-d);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(20, 92, 84, 0.3);
        }

        .btn-submit:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Auth Footer Links */
        .auth-footer {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid var(--surface-border);
            text-align: center;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: .85rem;
            font-weight: 600;
            color: var(--brand-primary);
            transition: color .15s;
            margin-bottom: 12px;
        }

        .back-link:hover {
            color: var(--brand-accent);
        }

        .help-note {
            font-size: .78rem;
            color: var(--text-subtle);
            margin: 0;
            line-height: 1.5;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        [x-cloak] {
            display: none !important;
        }

        @media (max-width: 480px) {
            .auth-card {
                padding: 32px 22px 26px;
                border-radius: var(--radius);
            }

            .auth-title {
                font-size: 1.35rem;
            }

            .auth-subtitle {
                font-size: .82rem;
            }

            .layout-container {
                padding: 20px 14px;
                align-items: flex-start;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="auth-card">

        {{-- Header --}}
        <div class="auth-header">
            <a href="{{ route('welcome') }}" class="auth-brand" aria-label="{{ $appName }} — Kembali ke beranda">
                <svg style="width: 28px; height: 28px;" width="28" height="28" viewBox="0 0 32 32" aria-hidden="true"
                    class="icon-inline">
                    <polygon points="16,2 29,9 29,23 16,30 3,23 3,9" fill="#145C54" />
                    <polygon points="16,2 29,9 16,16 3,9" fill="#C97A2B" />
                </svg>
                <span class="auth-brand-name">{{ $appName }}</span>
            </a>


            <h1 class="auth-title">Masuk ke Sistem</h1>
            <p class="auth-subtitle">Sistem Informasi Gudang &amp; Manajemen Aset</p>
        </div>

        {{-- Session Flash Status Success Alert --}}
        @if (session('status'))
            <div class="alert alert-success" role="status" aria-live="polite">
                <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" class="alert-icon icon-inline">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                    <polyline points="22 4 12 14.01 9 11.01" />
                </svg>
                <div>{{ session('status') }}</div>
            </div>
        @endif

        {{-- Global Errors Alert --}}
        @if ($errors->any())
            <div class="alert alert-danger" role="alert" aria-live="assertive">
                <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" class="alert-icon icon-inline">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                </svg>
                <div>
                    @if ($errors->count() === 1)
                        <strong>{{ $errors->first() }}</strong>
                    @else
                        <strong>Gagal Masuk:</strong> Mohon periksa kembali kredensial dan input Anda.
                    @endif
                </div>
            </div>
        @endif

        {{-- Login Form --}}
        <form method="POST" action="{{ route('login') }}" x-data="{ submitting: false }" @submit="submitting = true"
            x-bind:aria-busy="submitting ? 'true' : 'false'">
            @csrf

            {{-- Username Field --}}
            <div class="form-group">
                <label for="username" class="form-label">Username / ID Pengguna</label>
                <div class="input-wrapper">
                    <span class="input-icon-left" aria-hidden="true">
                        <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </span>
                    <input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus
                        autocomplete="username" aria-required="true"
                        aria-invalid="{{ $errors->has('username') ? 'true' : 'false' }}"
                        aria-describedby="username-help username-error"
                        placeholder="Masukkan username anda (misal: superadmin)"
                        class="form-control @error('username') is-invalid @enderror">
                </div>
                <span id="username-help" class="sr-only">Masukkan username akun internal Anda.</span>
                @error('username')
                    <div id="username-error" class="invalid-feedback" role="alert">
                        <svg style="width: 14px; height: 14px;" width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" class="icon-inline">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Password Field with Alpine.js Show/Hide Toggle --}}
            <div class="form-group" x-data="{ showPassword: false }">
                <label for="password" class="form-label">Kata Sandi</label>
                <div class="input-wrapper">
                    <span class="input-icon-left" aria-hidden="true">
                        <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>
                    </span>
                    <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required
                        autocomplete="current-password" aria-required="true"
                        aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                        aria-describedby="password-help password-error" placeholder="••••••••"
                        class="form-control has-toggle @error('password') is-invalid @enderror">
                    <button type="button" class="input-toggle-btn" @click="showPassword = !showPassword"
                        :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        :aria-pressed="showPassword ? 'true' : 'false'">
                        {{-- Eye Icon (Show) --}}
                        <svg x-show="!showPassword" style="width: 18px; height: 18px;" width="18" height="18"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            class="icon-inline">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        {{-- Eye Off Icon (Hide) --}}
                        <svg x-show="showPassword" x-cloak style="width: 18px; height: 18px;" width="18"
                            height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            class="icon-inline">
                            <path
                                d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                            <line x1="1" y1="1" x2="23" y2="23" />
                        </svg>
                    </button>
                </div>
                <span id="password-help" class="sr-only">Masukkan kata sandi akun internal Anda.</span>
                @error('password')
                    <div id="password-error" class="invalid-feedback" role="alert">
                        <svg style="width: 14px; height: 14px;" width="14" height="14" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            {{-- Remember Me --}}
            <div class="form-check">
                <input id="remember" type="checkbox" name="remember" class="form-check-input"
                    {{ old('remember') ? 'checked' : '' }}>
                <label for="remember" class="form-check-label">
                    Ingat saya di perangkat ini
                </label>
            </div>

            {{-- Submit Button --}}
            <button type="submit" class="btn-submit" :disabled="submitting"
                :aria-disabled="submitting ? 'true' : 'false'">
                <template x-if="submitting">
                    <span style="display: inline-flex; align-items: center; gap: 8px;" role="status" aria-live="polite">
                        <span class="spinner" aria-hidden="true"></span>
                        Memverifikasi akun...
                    </span>
                </template>
                <template x-if="!submitting">
                    <span style="display: inline-flex; align-items: center; gap: 8px;">
                        <svg style="width: 18px; height: 18px;" width="18" height="18" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2.2" class="icon-inline">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                            <polyline points="10 17 15 12 10 7" />
                            <line x1="15" y1="12" x2="3" y2="12" />
                        </svg>
                        Masuk ke Sistem
                    </span>
                </template>
            </button>
        </form>

        {{-- Footer --}}
        <div class="auth-footer">
            <a href="{{ route('welcome') }}" class="back-link">
                <svg style="width: 16px; height: 16px;" width="16" height="16" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" class="icon-inline">
                    <line x1="19" y1="12" x2="5" y2="12" />
                    <polyline points="12 19 5 12 12 5" />
                </svg>
                Kembali ke Beranda
            </a>
            <p class="help-note">
                Belum memiliki akun? Hubungi Super Admin di unit Anda
            </p>
        </div>

    </div>
@endsection
