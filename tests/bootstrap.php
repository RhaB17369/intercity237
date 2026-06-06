<?php
define('BASE_URL',  '');
define('SITE_NAME', 'Intercity237 Test');
define('APP_URL',   'http://localhost');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/includes/helpers.php';
