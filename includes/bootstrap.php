<?php

date_default_timezone_set('Asia/Manila');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// The homepage is useful without a database, but feature pages need MySQL.
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$usesDatabase = preg_match('~/(registration|reservation|menu|review-payment|admin)/~', $scriptPath) === 1;
$setup = database_setup_status();
$missingConfig = $setup['missing_config'];
$missingDriver = $setup['missing_driver'];

if ($usesDatabase && ($missingConfig || $missingDriver)) {
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Local database setup required</title>
        <style>
            body { max-width: 720px; margin: 3rem auto; padding: 0 1rem; color: #252525; font: 16px/1.5 system-ui, sans-serif; }
            main { padding: 1.5rem; border: 1px solid #ded8d2; border-radius: 10px; }
            code { padding: .15rem .35rem; background: #f5f2ef; }
        </style>
    </head>
    <body><main>
        <h1>Local database setup required</h1>
        <p>This feature needs a local MySQL connection. No booking or account data was changed.</p>
        <ol>
            <?php if ($missingConfig): ?>
                <li>From the project folder, copy <code>config.example.php</code> to <code>config.local.php</code>.</li>
                <li>Edit <code>config.local.php</code> with your local MySQL username and password. Do not share or commit this file.</li>
            <?php endif; ?>
            <?php if ($missingDriver): ?>
                <li>Enable the <code>pdo_mysql</code> PHP extension and restart Apache. Check the <code>php.ini</code> used by Apache, which may differ from the CLI PHP configuration.</li>
            <?php endif; ?>
            <li>Start MySQL in XAMPP. The app creates its database, tables, and sample menu automatically on first use.</li>
        </ol>
        <p>After setup, reload this page. The home page remains available while database setup is incomplete.</p>
    </main></body>
    </html>
    <?php
    exit;
}
