<?php
// config/database.php

// Optional local configuration override for shared hosting environments (e.g. cPanel, Plesk)
$configFile = __DIR__ . '/config.php';
$localConfig = file_exists($configFile) ? require $configFile : [];

define('DB_HOST', $localConfig['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', $localConfig['DB_PORT'] ?? getenv('DB_PORT') ?: '3306');
define('DB_NAME', $localConfig['DB_NAME'] ?? getenv('DB_NAME') ?: 'solaris');
define('DB_USER', $localConfig['DB_USER'] ?? getenv('DB_USER') ?: 'solaris_user');
define('DB_PASS', $localConfig['DB_PASS'] ?? getenv('DB_PASS') ?: 'solaris_pass');

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
        }
        return self::$instance;
    }
}
