<?php
// Bootstrap pour les tests PHPUnit — évite l'initialisation PDO
define('BASE_URL',  '');
define('SITE_NAME', 'Intercity237 Test');
define('APP_URL',   'http://localhost');
define('MAIL_FROM', 'test@intercity237.cm');

// Démarrer la session pour les tests qui en ont besoin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Charger uniquement auth.php (pas db.php pour éviter la connexion MySQL)
require_once __DIR__ . '/../src/includes/auth.php';
