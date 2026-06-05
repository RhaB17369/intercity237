<?php
// Must be called after config/db.php and includes/auth.php are already required
$departments = get_departments($pdo);
$current_user_name = h($_SESSION['full_name'] ?? '');
$current_role      = $_SESSION['role'] ?? '';
$user_dept_id      = $_SESSION['department_id'] ?? null;
$user_initials     = strtoupper(substr($current_user_name, 0, 1) . (strpos($current_user_name, ' ') !== false ? substr($current_user_name, strpos($current_user_name, ' ') + 1, 1) : ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? h($page_title) . ' — ' : '' ?><?= SITE_NAME ?></title>
    <meta name="description" content="Intercity237 — Centralized human resource management for Intercity237.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>

<!-- Scroll progress bar -->
<div id="scroll-progress"></div>

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-expand-lg navbar-dark cim-navbar" id="mainNav">
    <div class="container-fluid px-4 h-100 d-flex align-items-center">

        <!-- Brand / Logo -->
        <a class="navbar-brand d-flex align-items-center gap-2 me-4 text-decoration-none" href="<?= BASE_URL ?>/index.php">
            <div class="cim-logo-icon">
                <i class="bi bi-buildings-fill fs-5"></i>
            </div>
            <div>
                <div class="fw-bold lh-1" style="font-family:'Sora',sans-serif;font-size:1rem;letter-spacing:-0.01em">Intercity237</div>
                <div class="cim-tagline">Bus Booking</div>
            </div>
        </a>

        <!-- Mobile toggler -->
        <button class="navbar-toggler border-0 ms-auto me-2" type="button"
                data-bs-toggle="collapse" data-bs-target="#navMain"
                aria-controls="navMain" aria-expanded="false">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMain">
            <!-- Left links -->
            <ul class="navbar-nav me-auto ms-2 mb-2 mb-lg-0 gap-1">

                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['PHP_SELF'], 'admin') === false) ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/index.php">
                        <i class="bi bi-house-fill me-1 opacity-75"></i>Home
                    </a>
                </li>

                <?php if (is_logged_in()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= (basename($_SERVER['PHP_SELF']) === 'department.php') ? 'active' : '' ?>"
                       href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-grid-3x3-gap-fill me-1 opacity-75"></i>Departments
                    </a>
                    <ul class="dropdown-menu cim-dropdown shadow">
                        <?php foreach ($departments as $dept):
                            $can_access = is_admin() || ($user_dept_id == $dept['id']); ?>
                            <?php if ($can_access): ?>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/department.php?id=<?= $dept['id'] ?>">
                                    <i class="bi bi-diagram-3 text-cim-orange"></i><?= h($dept['name']) ?>
                                </a>
                            </li>
                            <?php else: ?>
                            <li>
                                <span class="dropdown-item disabled">
                                    <i class="bi bi-lock opacity-50"></i><?= h($dept['name']) ?>
                                </span>
                            </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (is_admin()): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= (strpos($_SERVER['PHP_SELF'], 'admin') !== false) ? 'active' : '' ?>"
                       href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-shield-fill me-1 opacity-75"></i>Admin
                    </a>
                    <ul class="dropdown-menu cim-dropdown shadow">
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/admin/index.php">
                                <i class="bi bi-speedometer2 text-cim-orange"></i>Dashboard
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/admin/users.php">
                                <i class="bi bi-people-fill text-cim-orange"></i>Manage Employees
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/admin/database.php">
                                <i class="bi bi-database-fill text-cim-orange"></i>Database View
                            </a>
                        </li>
                        <?php if (is_superadmin()): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/admin/admins.php">
                                <i class="bi bi-person-badge-fill" style="color:#f59e0b"></i>
                                <span style="color:#f59e0b">Manage Admins</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Right nav -->
            <ul class="navbar-nav align-items-center gap-2">
                <?php if (is_logged_in()): ?>
                    <li class="nav-item">
                        <div class="nav-user-chip">
                            <div class="nav-avatar"><?= h($user_initials) ?></div>
                            <div>
                                <div class="text-white fw-semibold" style="font-size:0.82rem;line-height:1.1"><?= $current_user_name ?></div>
                                <div style="font-size:0.65rem;color:rgba(255,255,255,0.38);text-transform:uppercase;letter-spacing:0.08em"><?= ucfirst($current_role) ?></div>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm fw-semibold" href="<?= BASE_URL ?>/logout.php"
                           style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);color:rgba(255,255,255,0.8);border-radius:8px;padding:6px 14px;">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-sm fw-semibold" href="<?= BASE_URL ?>/login.php"
                           style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.14);color:rgba(255,255,255,0.8);border-radius:8px;padding:6px 16px;">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-cim btn-sm fw-semibold" href="<?= BASE_URL ?>/register.php">
                            <i class="bi bi-person-plus-fill me-1"></i>Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php if (isset($_GET['error']) && $_GET['error'] === 'access_denied'): ?>
<div class="alert alert-danger alert-dismissible fade show mx-3 mt-3 mb-0" role="alert">
    <i class="bi bi-shield-exclamation me-2"></i>Access denied — insufficient privileges.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
