<?php

declare(strict_types=1);

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

try {
    require __DIR__.'/../public/index.php';
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Fatal Server Error: '.$e->getMessage()."\n";
    echo 'Class: '.get_class($e)."\n";
    echo 'File: '.$e->getFile().':'.$e->getLine()."\n";
    echo "\nStack trace:\n".$e->getTraceAsString();

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
}
