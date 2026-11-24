<?php

declare(strict_types=1);

// Set up writable directories in /tmp (Vercel filesystem is read-only except /tmp)
$storagePath = '/tmp/storage';
$directories = [
    $storagePath.'/app/private',
    $storagePath.'/app/public',
    $storagePath.'/framework/cache/data',
    $storagePath.'/framework/sessions',
    $storagePath.'/framework/views',
    $storagePath.'/logs',
    '/tmp/bootstrap/cache',
];

foreach ($directories as $path) {
    if (! is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

// Override cache/storage paths to writable /tmp locations
$envVars = [
    'LARAVEL_STORAGE_PATH' => $storagePath,
    'VIEW_COMPILED_PATH' => $storagePath.'/framework/views',
    'APP_CONFIG_CACHE' => '/tmp/bootstrap/cache/config.php',
    'APP_EVENTS_CACHE' => '/tmp/bootstrap/cache/events.php',
    'APP_PACKAGES_CACHE' => '/tmp/bootstrap/cache/packages.php',
    'APP_ROUTES_CACHE' => '/tmp/bootstrap/cache/routes.php',
    'APP_SERVICES_CACHE' => '/tmp/bootstrap/cache/services.php',
];

foreach ($envVars as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

// Register a global error handler to catch fatal errors before Laravel boots
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    if ($errno & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo "PHP Fatal Error [{$errno}]: {$errstr}\n";
        echo "File: {$errfile}:{$errline}\n";
        exit(1);
    }

    return false;
});

// Register a shutdown function to catch fatal errors that bypass set_error_handler
register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error && ($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
        // Only output if headers not sent yet (i.e. Laravel didn't handle it)
        if (! headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo "PHP Shutdown Fatal Error [{$error['type']}]: {$error['message']}\n";
            echo "File: {$error['file']}:{$error['line']}\n";
        }
    }
});

try {
    require __DIR__.'/../public/index.php';
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');

    echo "=== FATAL SERVER ERROR ===\n";
    echo 'Class: '.get_class($e)."\n";
    echo 'Message: '.$e->getMessage()."\n";
    echo 'File: '.$e->getFile().':'.$e->getLine()."\n";
    echo "\nStack trace:\n".$e->getTraceAsString();

    $prev = $e->getPrevious();
    $depth = 0;
    while ($prev && $depth < 5) {
        $depth++;
        echo "\n\n--- Caused by [depth {$depth}] ---\n";
        echo 'Class: '.get_class($prev)."\n";
        echo 'Message: '.$prev->getMessage()."\n";
        echo 'File: '.$prev->getFile().':'.$prev->getLine()."\n\n";
        echo $prev->getTraceAsString();
        $prev = $prev->getPrevious();
    }

    exit(1);
}
