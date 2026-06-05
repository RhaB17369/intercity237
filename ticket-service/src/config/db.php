<?php
define('BASE_URL',  getenv('BASE_URL')  ?: '');
define('SITE_NAME', getenv('SITE_NAME') ?: 'Intercity237');
define('APP_URL',   getenv('APP_URL')   ?: 'http://localhost');
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@intercity237.cm');

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'intercity237');
define('DB_USER', getenv('DB_USER') ?: 'intercity237');
define('DB_PASS', getenv('DB_PASS') ?: 'Intercity237!');

try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(503);
    die(json_encode(['error' => 'Database unavailable']));
}
