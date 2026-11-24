<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->session()->get('user_logged_in_id');

        if ($userId === null) {
            return $next($request);
        }

        $currentUser = $request->user();
        $user = ($currentUser !== null && $currentUser->getKey() == $userId)
            ? $currentUser
            : User::withTrashed()->find($userId);

        $errorMessage = match (true) {
            $user === null => 'Sesi dihentikan: Akun Anda telah dihapus secara permanen.',
            $user->trashed() => 'Sesi dihentikan: Akun Anda telah dihapus oleh Administrator.',
            ! $user->is_aktif => 'Sesi dihentikan: Akun Anda telah dinonaktifkan.',
            default => null,
        };

        if ($errorMessage === null) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('error', $errorMessage);
    }
}
