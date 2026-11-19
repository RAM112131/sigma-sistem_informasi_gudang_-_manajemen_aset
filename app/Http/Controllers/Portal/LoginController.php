<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class LoginController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();

            return redirect()->intended(
                $user instanceof User ? $this->dashboardRoute($user) : route('pending-access'),
            );
        }

        return view('auth.login', [
            'appName' => config('app.name', 'SIGMA'),
            'version' => config('app.version', app()->version()),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $request->session()->forget(['active_gudang_id', 'url.intended']);

        $user = Auth::user();
        if ($user) {
            session(['user_logged_in_id' => $user->id]);

            AuditLog::create([
                'causer_type' => User::class,
                'causer_id' => $user->id,
                'action' => 'login',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'description' => 'Login ke sistem',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);
        }
        $userName = $user?->name ?? 'Pengguna';

        $dashboardRoute = $user instanceof User
            ? $this->dashboardRoute($user)
            : route('pending-access');

        return redirect($dashboardRoute)
            ->with('status', 'Selamat datang kembali, '.$userName.'!');
    }

    private function dashboardRoute(User $user): string
    {
        if ($user->hasAnyRole(['Super Admin', 'super_admin'])) {
            return route('admin.dashboard');
        }

        if ($user->hasAnyRole(['Admin Gudang Store', 'admin_gudang_store'])) {
            return route('store.dashboard');
        }

        if ($user->hasAnyRole(['Recording Gudang Umum', 'recording_gudang_umum'])) {
            return route('unit-bisnis.recording-gu.dashboard');
        }

        if ($user->hasAnyRole(['Admin Gudang Umum', 'admin_gudang_umum'])) {
            return route('unit-bisnis.admin-gu.dashboard');
        }

        return route('pending-access');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user) {
            AuditLog::create([
                'causer_type' => User::class,
                'causer_id' => $user->id,
                'action' => 'logout',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'description' => 'Logout dari sistem',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);
        }

        return redirect()->route('welcome')
            ->with('status', 'Anda telah keluar dari sistem.');
    }
}
