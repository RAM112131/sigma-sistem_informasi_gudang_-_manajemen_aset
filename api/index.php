<?php

declare(strict_types=1);

$storagePath = getenv('LARAVEL_STORAGE_PATH') ?: sys_get_temp_dir().'/sigma-storage';
$directories = [
    'app/private',
    'app/public',
    'framework/cache/data',
    'framework/sessions',
    'framework/views',
    'logs',
];

foreach ($directories as $directory) {
    $path = $storagePath.'/'.$directory;

    if (! is_dir($path) && ! mkdir($path, 0755, true) && ! is_dir($path)) {
        throw new RuntimeException(sprintf('Unable to create Laravel storage directory: %s', $path));
    }
}

$_ENV['LARAVEL_STORAGE_PATH'] = $storagePath;
$_SERVER['LARAVEL_STORAGE_PATH'] = $storagePath;

require __DIR__.'/../public/index.php';
