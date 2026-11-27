@extends('layouts.guest')

@section('title', 'Masuk — ' . config('app.name', 'SIGMA'))

@push('styles')
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
