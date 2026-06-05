<?php
// ============================================================
// Intercity237 — One-time Setup Script
// Run ONCE: http://localhost:8080/setup.php
// DELETE this file after running!
// ============================================================

$host = 'localhost';
$user = 'intercity237';
$pass = 'Intercity2372026';

$errors   = [];
$messages = [];

try {
    // Connect without selecting a DB first
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `intercity237` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `intercity237`");
    $messages[] = '✅ Database <strong>intercity237</strong> created.';

    // Departments
    $pdo->exec("CREATE TABLE IF NOT EXISTS `departments` (
        `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name`        VARCHAR(100) NOT NULL,
        `description` TEXT,
        `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $messages[] = '✅ Table <strong>departments</strong> ready.';

    // Users
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `full_name`     VARCHAR(200) NOT NULL,
        `home_address`  TEXT,
        `email`         VARCHAR(150) NOT NULL,
        `phone`         VARCHAR(30),
        `username`      VARCHAR(100) NOT NULL,
        `password`      VARCHAR(255) NOT NULL,
        `role`          ENUM('superadmin','admin','employee') NOT NULL DEFAULT 'employee',
        `department_id` INT UNSIGNED,
        `reset_token`   VARCHAR(255),
        `reset_expiry`  DATETIME,
        `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uq_email` (`email`),
        UNIQUE KEY `uq_username` (`username`),
        FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    $messages[] = '✅ Table <strong>users</strong> ready.';

    // Department records (10 fields per exam requirement)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `department_records` (
        `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `department_id` INT UNSIGNED NOT NULL,
        `employee_id`   VARCHAR(50)  DEFAULT NULL,  -- Field 1
        `full_name`     VARCHAR(200) DEFAULT NULL,  -- Field 2
        `position`      VARCHAR(150) DEFAULT NULL,  -- Field 3
        `salary`        VARCHAR(50)  DEFAULT NULL,  -- Field 4
        `start_date`    DATE         DEFAULT NULL,  -- Field 5
        `status`        ENUM('Active','On Leave','Suspended','Resigned') DEFAULT 'Active', -- Field 6
        `phone`         VARCHAR(30)  DEFAULT NULL,  -- Field 7
        `email`         VARCHAR(150) DEFAULT NULL,  -- Field 8
        `address`       TEXT         DEFAULT NULL,  -- Field 9
        `remarks`       TEXT         DEFAULT NULL,  -- Field 10
        `created_by`    INT UNSIGNED DEFAULT NULL,
        `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    $messages[] = '✅ Table <strong>department_records</strong> ready.';

    // Insert 10 departments
    $count = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
    if ($count == 0) {
        $depts = [
            ['Production',              'Cement production and manufacturing operations'],
            ['Quality Control',         'Product quality assurance and testing'],
            ['Logistics & Transport',   'Distribution, transport, and supply chain management'],
            ['Finance & Accounting',    'Financial management, payroll, and accounting'],
            ['Human Resources',         'Personnel management, recruitment, and training'],
            ['Information Technology',  'IT infrastructure, systems, and support'],
            ['Sales & Marketing',       'Commercial activities and market development'],
            ['Procurement',             'Purchasing, supplier management, and contracts'],
            ['Maintenance',             'Equipment maintenance and engineering services'],
            ['Administration',          'General administration and executive management'],
        ];
        $stmt = $pdo->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
        foreach ($depts as [$name, $desc]) $stmt->execute([$name, $desc]);
        $messages[] = '✅ 10 departments inserted.';
    } else {
        $messages[] = 'ℹ️ Departments already exist — skipped.';
    }

    // Insert superadmin
    $exists = $pdo->prepare("SELECT id FROM users WHERE username = 'superadmin'");
    $exists->execute();
    if (!$exists->fetch()) {
        $hash = password_hash('Admin@1234', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (full_name, email, username, password, role)
                        VALUES (?, ?, ?, ?, ?)")
            ->execute(['Super Administrator', 'superadmin@intercity237.cm', 'superadmin', $hash, 'superadmin']);
        $messages[] = '✅ Superadmin created. Username: <strong>superadmin</strong> / Password: <strong>Admin@1234</strong>';
    } else {
        $messages[] = 'ℹ️ Superadmin already exists — skipped.';
    }

    $done = true;
} catch (PDOException $e) {
    $errors[] = 'Database error: ' . htmlspecialchars($e->getMessage());
    $done = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Intercity237 Setup</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex justify-content-center align-items-center min-vh-100">
<div class="card shadow" style="max-width:560px;width:100%">
    <div class="card-header text-white fw-bold" style="background:#2d2d2d;border-bottom:3px solid #e85d04">
        🏗️ Intercity237 — Database Setup
    </div>
    <div class="card-body">
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-danger"><i class="bi bi-x-circle me-1"></i><?= $e ?></div>
        <?php endforeach; ?>
        <?php foreach ($messages as $m): ?>
            <p class="mb-1"><?= $m ?></p>
        <?php endforeach; ?>

        <?php if ($done): ?>
        <hr>
        <div class="alert alert-success fw-semibold">
            🎉 Setup complete! Database is ready.
        </div>
        <p class="text-danger small"><strong>⚠️ Security:</strong> Delete or rename this file before going to production.</p>
        <a href="index.php" class="btn btn-warning fw-bold">Go to Homepage →</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
