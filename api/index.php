<?php

/**
 * Vercel Serverless Entrypoint for Laravel 11
 */

// 1. Prepare writable storage directories in /tmp for serverless runtime
$storagePath = '/tmp/storage';
if (!is_dir($storagePath)) {
    @mkdir($storagePath, 0755, true);
    @mkdir($storagePath . '/framework/views', 0755, true);
    @mkdir($storagePath . '/framework/cache', 0755, true);
    @mkdir($storagePath . '/framework/sessions', 0755, true);
    @mkdir($storagePath . '/logs', 0755, true);
}

putenv("LARAVEL_STORAGE_PATH={$storagePath}");
$_ENV['LARAVEL_STORAGE_PATH'] = $storagePath;
$_SERVER['LARAVEL_STORAGE_PATH'] = $storagePath;

// 2. Prepare writable SQLite database in /tmp if using SQLite on Vercel
$bundledDb = __DIR__ . '/../database/database.sqlite';
$tmpDb = '/tmp/database.sqlite';
if (!file_exists($tmpDb) && file_exists($bundledDb)) {
    @copy($bundledDb, $tmpDb);
}

// 3. Set resilient defaults for Vercel if not provided in Vercel dashboard
if (empty($_ENV['DB_CONNECTION']) && empty(getenv('DB_CONNECTION'))) {
    putenv('DB_CONNECTION=sqlite');
    $_ENV['DB_CONNECTION'] = 'sqlite';
    $_SERVER['DB_CONNECTION'] = 'sqlite';
}

if (empty($_ENV['DB_DATABASE']) && empty(getenv('DB_DATABASE'))) {
    putenv("DB_DATABASE={$tmpDb}");
    $_ENV['DB_DATABASE'] = $tmpDb;
    $_SERVER['DB_DATABASE'] = $tmpDb;
}

if (empty($_ENV['SESSION_DRIVER']) && empty(getenv('SESSION_DRIVER'))) {
    putenv('SESSION_DRIVER=cookie');
    $_ENV['SESSION_DRIVER'] = 'cookie';
    $_SERVER['SESSION_DRIVER'] = 'cookie';
}

if (empty($_ENV['APP_KEY']) && empty(getenv('APP_KEY'))) {
    putenv('APP_KEY=base64:cHK9pF3RfTxCotwo05bA3nNYwDnf07mw3lAZLXDEnS0=');
    $_ENV['APP_KEY'] = 'base64:cHK9pF3RfTxCotwo05bA3nNYwDnf07mw3lAZLXDEnS0=';
    $_SERVER['APP_KEY'] = 'base64:cHK9pF3RfTxCotwo05bA3nNYwDnf07mw3lAZLXDEnS0=';
}

if (empty($_ENV['APP_ENV']) && empty(getenv('APP_ENV'))) {
    putenv('APP_ENV=production');
    $_ENV['APP_ENV'] = 'production';
    $_SERVER['APP_ENV'] = 'production';
}

if (empty($_ENV['APP_DEBUG']) && empty(getenv('APP_DEBUG'))) {
    putenv('APP_DEBUG=true');
    $_ENV['APP_DEBUG'] = 'true';
    $_SERVER['APP_DEBUG'] = 'true';
}

// 4. Delegate execution to Laravel's public entrypoint
require __DIR__ . '/../public/index.php';
