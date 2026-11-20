<?php

/**
 * Check local prerequisites before trying to open a database connection.
 *
 * @return array{config_path: string, missing_config: bool, missing_driver: bool}
 */
function database_setup_status(): array
{
    $projectRoot = dirname(__DIR__);
    $localConfig = $projectRoot . DIRECTORY_SEPARATOR . 'config.local.php';
    $exampleConfig = $projectRoot . DIRECTORY_SEPARATOR . 'config.example.php';
    $configPath = is_file($localConfig) ? $localConfig : $exampleConfig;

    return [
        'config_path' => $configPath,
        'missing_config' => !is_file($configPath),
        'missing_driver' => !in_array('mysql', PDO::getAvailableDrivers(), true),
    ];
}

function run_sql_file(PDO $connection, string $filePath): void
{
    $sql = file_get_contents($filePath);
    if ($sql === false) {
        throw new RuntimeException('Could not read database setup file: ' . $filePath);
    }

    // These simple setup files contain one SQL statement per semicolon, not stored procedures.
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (explode(';', $sql) as $statement) {
        if (trim($statement) !== '') {
            $connection->exec($statement);
        }
    }
}

function initialize_database(PDO $connection): void
{
    $tableCount = (int)$connection->query(
        "SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name IN ('users', 'menu_packages', 'extras', 'dining_tables',
                              'reservations', 'reservation_extras', 'occupied_slots')"
    )->fetchColumn();
    if ($tableCount < 7) {
        run_sql_file($connection, dirname(__DIR__) . '/database/setup.sql');
    }

    $packageCount = (int)$connection->query('SELECT COUNT(*) FROM menu_packages')->fetchColumn();
    if ($packageCount === 0) {
        run_sql_file($connection, dirname(__DIR__) . '/database/seed.sql');
    }
}

function db(): PDO
{
    // Reuse one connection for all database work during this request.
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $setup = database_setup_status();
    if ($setup['missing_config']) {
        throw new RuntimeException('Missing config.local.php. Copy config.example.php and enter your local MySQL settings.');
    }

    if ($setup['missing_driver']) {
        throw new RuntimeException('PDO_MYSQL is not enabled. Enable the pdo_mysql extension in the php.ini used by Apache, then restart Apache.');
    }

    $config = require $setup['config_path'];
    if (!preg_match('/^[A-Za-z0-9_]+$/', $config['database'])) {
        throw new RuntimeException('The MySQL database name in the local config is invalid.');
    }

    try {
        $connection = new PDO($config['dsn'], $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        // XAMPP users do not need to create the database manually; setup runs on first use.
        $connection->exec(
            'CREATE DATABASE IF NOT EXISTS `' . $config['database'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        $connection->exec('USE `' . $config['database'] . '`');
        initialize_database($connection);
    } catch (PDOException $error) {
        throw new RuntimeException('Could not connect to or initialize MySQL. Start MySQL in XAMPP and check the local account permissions and config.local.php.', 0, $error);
    }
    return $connection;
}
