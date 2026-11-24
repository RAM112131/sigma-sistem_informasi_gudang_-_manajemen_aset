<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogReadActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Handle tasks after the HTTP response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        if (auth()->check()) {
            try {
                AuditLog::create([
                    'causer_id' => auth()->id(),
                    'causer_type' => User::class,
                    'subject_id' => auth()->id(),
                    'subject_type' => User::class,
                    'action' => 'read',
                    'description' => 'Mengakses '.$request->path(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Throwable) {
                // Silently ignore audit log failures during terminate
            }
        }
    }
}
