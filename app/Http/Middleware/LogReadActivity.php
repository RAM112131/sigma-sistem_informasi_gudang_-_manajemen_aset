<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class LogReadActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (auth()->check()) {
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
        }

        return $response;
    }
}
