<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (!in_array($_SESSION['role'] ?? '', ['admin', 'superadmin'])) {
        header('Location: ' . BASE_URL . '/index.php?error=access_denied');
        exit;
    }
}

function require_superadmin(): void {
    require_login();
    if (($_SESSION['role'] ?? '') !== 'superadmin') {
        header('Location: ' . BASE_URL . '/index.php?error=access_denied');
        exit;
    }
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function is_admin(): bool {
    return in_array($_SESSION['role'] ?? '', ['admin', 'superadmin']);
}

function is_superadmin(): bool {
    return ($_SESSION['role'] ?? '') === 'superadmin';
}

function is_passenger(): bool {
    return ($_SESSION['role'] ?? '') === 'passenger';
}

function is_agent(): bool {
    return ($_SESSION['role'] ?? '') === 'agent';
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die('CSRF validation failed. <a href="javascript:history.back()">Go back</a>');
        }
    }
}
