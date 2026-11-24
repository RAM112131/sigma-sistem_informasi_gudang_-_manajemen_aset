<?php

use App\Http\Middleware\CheckAccountStatus;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            CheckAccountStatus::class,
        ]);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            return new Response(
                "=== LARAVEL APPLICATION ERROR ===\n\n".
                'Exception: '.get_class($e)."\n".
                'Message: '.$e->getMessage()."\n".
                'File: '.$e->getFile().' (Line '.$e->getLine().")\n\n".
                "Stack Trace:\n".$e->getTraceAsString(),
                500,
                ['Content-Type' => 'text/plain; charset=UTF-8']
            );
        });
    })->create();

if ($storagePath = env('LARAVEL_STORAGE_PATH', $_ENV['LARAVEL_STORAGE_PATH'] ?? ($_SERVER['LARAVEL_STORAGE_PATH'] ?? null))) {
    $app->useStoragePath($storagePath);
}

return $app;
