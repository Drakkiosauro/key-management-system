<?php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__, 2) . '/logs/php_errors.log');

ini_set('session.cookie_httponly', getenv('SESSION_COOKIE_HTTPONLY') ?: 1);
ini_set('session.cookie_secure', getenv('SESSION_COOKIE_SECURE') ?: 0);
ini_set('session.cookie_samesite', getenv('SESSION_COOKIE_SAMESITE') ?: 'Strict');
ini_set('session.use_strict_mode', 1);

$host = getenv('DB_HOST') ?: 'localhost';
$db = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

if (!$db || !$user) {
    http_response_code(500);
    die('Database configuration is missing');
}

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    die('Database connection failed');
}

define('ADMIN_USER', getenv('ADMIN_USER'));
define('ADMIN_PASS', getenv('ADMIN_PASS'));
define('SECRET_TOKEN', getenv('SECRET_TOKEN'));
define('DISCORD_WEBHOOK', getenv('DISCORD_WEBHOOK') ?: '');
define('SCRIPT_PATH', dirname(__DIR__, 2) . '/' . (getenv('SCRIPT_PATH') ?: 'scripts/'));

if (!session_id()) {
    session_start();
}
