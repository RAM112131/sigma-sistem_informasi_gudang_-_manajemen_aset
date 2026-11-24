<?php

use App\Http\Middleware\CheckAccountStatus;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

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
        $exceptions->report(function (Throwable $e): void {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo "=== REAL UNDERLYING ERROR ===\n";
            echo 'Class: '.get_class($e)."\n";
            echo 'Message: '.$e->getMessage()."\n";
            echo 'File: '.$e->getFile().':'.$e->getLine()."\n\n";
            echo "Stack trace:\n".$e->getTraceAsString();

            $prev = $e->getPrevious();
            while ($prev) {
                echo "\n\nCaused by:\n";
                echo 'Class: '.get_class($prev)."\n";
                echo 'Message: '.$prev->getMessage()."\n";
                echo 'File: '.$prev->getFile().':'.$prev->getLine()."\n\n";
                echo $prev->getTraceAsString();
                $prev = $prev->getPrevious();
            }

            exit(1);
        });

        $exceptions->shouldRenderJsonWhen(
            fn () => true,
        );
    })->create();

if ($storagePath = env('LARAVEL_STORAGE_PATH', $_ENV['LARAVEL_STORAGE_PATH'] ?? ($_SERVER['LARAVEL_STORAGE_PATH'] ?? null))) {
    $app->useStoragePath($storagePath);
}

return $app;
