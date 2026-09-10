<?php

/**
 * Vercel Serverless Entrypoint for Laravel 11
 */

// 1. Prepare writable storage directories in /tmp for serverless runtime
$storagePath = '/tmp/storage';
if (!is_dir($storagePath)) {
    @mkdir($storagePath, 0755, true);
}

@mkdir($storagePath . '/framework/views', 0755, true);
@mkdir($storagePath . '/framework/cache/data', 0755, true);
@mkdir($storagePath . '/framework/sessions', 0755, true);
@mkdir($storagePath . '/logs', 0755, true);

// Set storage paths to writable /tmp
putenv("LARAVEL_STORAGE_PATH={$storagePath}");
$_ENV['LARAVEL_STORAGE_PATH'] = $storagePath;
$_SERVER['LARAVEL_STORAGE_PATH'] = $storagePath;

putenv("VIEW_COMPILED_PATH={$storagePath}/framework/views");
$_ENV['VIEW_COMPILED_PATH'] = "{$storagePath}/framework/views";
$_SERVER['VIEW_COMPILED_PATH'] = "{$storagePath}/framework/views";

// Move bootstrap cache files from read-only root into writable /tmp
putenv("APP_SERVICES_CACHE={$storagePath}/services.php");
$_ENV['APP_SERVICES_CACHE'] = "{$storagePath}/services.php";
$_SERVER['APP_SERVICES_CACHE'] = "{$storagePath}/services.php";

putenv("APP_PACKAGES_CACHE={$storagePath}/packages.php");
$_ENV['APP_PACKAGES_CACHE'] = "{$storagePath}/packages.php";
$_SERVER['APP_PACKAGES_CACHE'] = "{$storagePath}/packages.php";

putenv("APP_CONFIG_CACHE={$storagePath}/config.php");
$_ENV['APP_CONFIG_CACHE'] = "{$storagePath}/config.php";
$_SERVER['APP_CONFIG_CACHE'] = "{$storagePath}/config.php";

putenv("APP_ROUTES_CACHE={$storagePath}/routes.php");
$_ENV['APP_ROUTES_CACHE'] = "{$storagePath}/routes.php";
$_SERVER['APP_ROUTES_CACHE'] = "{$storagePath}/routes.php";

putenv("APP_EVENTS_CACHE={$storagePath}/events.php");
$_ENV['APP_EVENTS_CACHE'] = "{$storagePath}/events.php";
$_SERVER['APP_EVENTS_CACHE'] = "{$storagePath}/events.php";

// 2. Prepare writable SQLite database in /tmp if using SQLite on Vercel
$bundledDb = __DIR__ . '/../database/database.sqlite';
$tmpDb = '/tmp/database.sqlite';
if (!file_exists($tmpDb) && file_exists($bundledDb)) {
    @copy($bundledDb, $tmpDb);
}

// 3. Set resilient defaults for any empty or missing environment variables
$defaults = [
    'APP_MAINTENANCE_DRIVER' => 'file',
    'APP_MAINTENANCE_STORE'  => 'database',
    'DB_CONNECTION'          => 'sqlite',
    'DB_DATABASE'            => $tmpDb,
    'SESSION_DRIVER'         => 'cookie',
    'CACHE_STORE'            => 'array',
    'QUEUE_CONNECTION'       => 'sync',
    'LOG_CHANNEL'            => 'stderr',
    'MAIL_MAILER'            => 'log',
    'BROADCAST_CONNECTION'   => 'log',
    'APP_ENV'                => 'production',
    'APP_DEBUG'              => 'true',
    'APP_KEY'                => 'base64:cHK9pF3RfTxCotwo05bA3nNYwDnf07mw3lAZLXDEnS0=',
];

foreach ($defaults as $k => $v) {
    $current = getenv($k);
    if ($current === false || trim((string)$current) === '') {
        putenv("{$k}={$v}");
        $_ENV[$k] = $v;
        $_SERVER[$k] = $v;
    }
}

// 4. Delegate execution to Laravel's public entrypoint
require __DIR__ . '/../public/index.php';
