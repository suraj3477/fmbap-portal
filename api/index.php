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
if (file_exists($bundledDb)) {
    if (!file_exists($tmpDb) || filesize($tmpDb) === 0) {
        @copy($bundledDb, $tmpDb);
    }
}

// 3. Set resilient defaults for any empty or missing environment variables
$defaults = [
    'APP_NAME'               => 'FMBAP Portal',
    'APP_MAINTENANCE_DRIVER' => 'file',
    'APP_MAINTENANCE_STORE'  => 'database',
    'DB_CONNECTION'          => 'sqlite',
    'DB_DATABASE'            => $tmpDb,
    'SESSION_DRIVER'         => 'cookie',
    'SESSION_LIFETIME'       => '120',
    'SESSION_SECURE_COOKIE'  => 'true',
    'SESSION_SAME_SITE'      => 'lax',
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

// 3b. Sanitize impossible configurations on Vercel serverless
$dbConn = getenv('DB_CONNECTION');
$dbHost = getenv('DB_HOST');
// If database is configured for MySQL localhost/127.0.0.1 (impossible on Vercel), fall back to bundled SQLite
if ($dbConn === 'mysql' && (empty($dbHost) || $dbHost === '127.0.0.1' || $dbHost === 'localhost')) {
    putenv('DB_CONNECTION=sqlite');
    $_ENV['DB_CONNECTION'] = 'sqlite';
    $_SERVER['DB_CONNECTION'] = 'sqlite';

    putenv("DB_DATABASE={$tmpDb}");
    $_ENV['DB_DATABASE'] = $tmpDb;
    $_SERVER['DB_DATABASE'] = $tmpDb;
}

// On serverless, ensure cookie driver is used unless external Redis/Memcached is explicitly configured
$sessionDriver = getenv('SESSION_DRIVER');
if ($sessionDriver === 'database' || $sessionDriver === 'file' || empty($sessionDriver)) {
    putenv('SESSION_DRIVER=cookie');
    $_ENV['SESSION_DRIVER'] = 'cookie';
    $_SERVER['SESSION_DRIVER'] = 'cookie';
}

// Clean up SESSION_DOMAIN if it was set to string "null"
if (getenv('SESSION_DOMAIN') === 'null') {
    putenv('SESSION_DOMAIN=');
    $_ENV['SESSION_DOMAIN'] = '';
    $_SERVER['SESSION_DOMAIN'] = '';
}

// 4. Force HTTPS server environment for Vercel SSL termination
if ((isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')) {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = 443;
}

// 5. Delegate execution to Laravel's public entrypoint
require __DIR__ . '/../public/index.php';
