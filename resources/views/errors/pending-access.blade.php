@extends('layouts.guest')

@section('title', 'Akses Belum Dikonfigurasi — ' . config('app.name', 'SIGMA'))

@push('styles')
    <style>
        .pending-card {
            width: 100%;
            max-width: 520px;
            background: var(--surface-card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--surface-border);
            box-shadow:
                0 16px 40px -10px rgba(20, 92, 84, 0.12),
                0 0 0 1px rgba(0, 0, 0, 0.02);
            padding: 40px 36px;
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .pending-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--brand-accent) 0%, var(--status-warning) 100%);
        }

        .pending-icon-box {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(201, 122, 43, 0.12);
            color: var(--brand-accent-d);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .pending-title {
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0 0 8px;
            letter-spacing: -.02em;
        }

        .pending-subtitle {
            font-size: .9rem;
            color: var(--text-muted);
            margin: 0 0 24px;
            line-height: 1.6;
        }

        .user-info-box {
            background: var(--surface-base);
            border: 1px solid var(--surface-border);
            border-radius: var(--radius);
            padding: 16px;
            margin-bottom: 24px;
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 10px;
            font-size: .85rem;
        }

        .user-info-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .user-info-label {
            color: var(--text-muted);
            font-weight: 500;
        }

        .user-info-value {
            font-weight: 700;
            color: var(--text-primary);
        }

        .pending-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn-logout {
            width: 100%;
            padding: 12px 20px;
            font-size: .9rem;
            font-weight: 600;
            border-radius: var(--radius);
            background: var(--surface-base);
            border: 1.5px solid var(--surface-border);
            color: var(--text-primary);
            cursor: pointer;
            transition: all .15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-logout:hover {
            background: #fff;
            border-color: var(--brand-primary);
            color: var(--brand-primary);
        }

        .btn-help {
            width: 100%;
            padding: 12px 20px;
            font-size: .9rem;
            font-weight: 700;
            border-radius: var(--radius);
            background: var(--brand-primary);
            color: #fff;
            border: none;
            cursor: pointer;
            transition: all .15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-help:hover {
            background: var(--brand-primary-d);
        }
    </style>
@endpush

@section('content')
    <div class="pending-card">

        <div class="pending-icon-box">
            <svg style="width: 32px; height: 32px;" width="32" height="32" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" class="icon-inline">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </svg>
        </div>

        <h1 class="pending-title">Akses Modul Belum Dikonfigurasi</h1>
        <p class="pending-subtitle">
            Akun Anda terdaftar resmi di sistem <strong>SIGMA</strong>, namun modul operasional atau rute khusus untuk
            jabatan / tipe gudang Anda sedang dalam tahap konfigurasi oleh tim pengembang.
        </p>

        @php
            $u = Auth::user();
            $roleName = $u?->roles?->pluck('name')->implode(', ') ?: 'Belum Ada Role';
            $gudangName = $u?->gudang?->pluck('nama')->implode(', ') ?: 'Belum Ditugaskan Gudang';
        @endphp

        <div class="user-info-box">
            <div class="user-info-row">
                <span class="user-info-label">Nama Akun:</span>
                <span class="user-info-value">{{ $u?->name ?? '-' }} (<code>{{ $u?->username ?? '-' }}</code>)</span>
            </div>
            <div class="user-info-row">
                <span class="user-info-label">Jabatan (Role):</span>
                <span class="user-info-value" style="color: var(--brand-accent-d);">{{ $roleName }}</span>
            </div>
            <div class="user-info-row">
                <span class="user-info-label">Lokasi Penugasan:</span>
                <span class="user-info-value">{{ $gudangName }}</span>
            </div>
        </div>

        <div class="pending-actions">
            <a href="mailto:admin.gudang@rpasukahati.co.id?subject=Permintaan%20Akses%20Modul%20SIGMA" class="btn-help">
                <svg style="width: 16px; height: 16px;" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" class="icon-inline">
                    <path
                        d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                </svg>
                Hubungi Admin Sistem
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-logout">
                    <svg style="width: 16px; height: 16px;" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" class="icon-inline">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                    Keluar Sistem (Logout)
                </button>
            </form>
        </div>

    </div>
@endsection
