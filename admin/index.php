<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$stats = [
    'employees' => $pdo->query("SELECT COUNT(*) FROM users WHERE role='employee'")->fetchColumn(),
    'admins'    => $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn(),
    'records'   => $pdo->query("SELECT COUNT(*) FROM department_records")->fetchColumn(),
    'depts'     => $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn(),
];

$recent_users = $pdo->query(
    "SELECT u.full_name, u.role, u.created_at, d.name as dept_name
     FROM users u LEFT JOIN departments d ON u.department_id = d.id
     ORDER BY u.created_at DESC LIMIT 8"
)->fetchAll();

$page_title = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold mb-0">Admin Dashboard</h3>
            <p class="text-muted mb-0 small">Welcome back, <?= h($_SESSION['full_name']) ?></p>
        </div>
        <span class="badge bg-<?= is_superadmin() ? 'danger' : 'warning text-dark' ?> fs-6 px-3 py-2">
            <i class="bi bi-shield-fill me-1"></i><?= ucfirst($_SESSION['role']) ?>
        </span>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <?php
        $cards = [
            ['label'=>'Employees','val'=>$stats['employees'],'icon'=>'people-fill','color'=>'warning'],
            ['label'=>'Admins','val'=>$stats['admins'],'icon'=>'shield-fill','color'=>'danger'],
            ['label'=>'HR Records','val'=>$stats['records'],'icon'=>'file-text-fill','color'=>'success'],
            ['label'=>'Departments','val'=>$stats['depts'],'icon'=>'diagram-3-fill','color'=>'primary'],
        ];
        foreach ($cards as $c): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="cim-stat-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="cim-stat-icon bg-<?= $c['color'] ?> bg-opacity-10 text-<?= $c['color'] ?>">
                        <i class="bi bi-<?= $c['icon'] ?>"></i>
                    </div>
                    <div>
                        <div class="fs-2 fw-bold lh-1"><?= number_format((int)$c['val']) ?></div>
                        <div class="text-muted small"><?= $c['label'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick Actions -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <h5 class="fw-bold">Quick Actions</h5>
        </div>
        <div class="col-sm-6 col-md-3">
            <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-outline-primary w-100 py-3">
                <i class="bi bi-people-fill d-block fs-3 mb-1"></i>Manage Employees
            </a>
        </div>
        <?php if (is_superadmin()): ?>
        <div class="col-sm-6 col-md-3">
            <a href="<?= BASE_URL ?>/admin/admins.php" class="btn btn-outline-danger w-100 py-3">
                <i class="bi bi-person-badge-fill d-block fs-3 mb-1"></i>Manage Admins
            </a>
        </div>
        <?php endif; ?>
        <div class="col-sm-6 col-md-3">
            <a href="<?= BASE_URL ?>/admin/database.php" class="btn btn-outline-secondary w-100 py-3">
                <i class="bi bi-database-fill d-block fs-3 mb-1"></i>Database View
            </a>
        </div>
        <div class="col-sm-6 col-md-3">
            <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-dark w-100 py-3">
                <i class="bi bi-house-fill d-block fs-3 mb-1"></i>Homepage
            </a>
        </div>
    </div>

    <!-- Recent Users -->
    <div class="cim-table-wrapper">
        <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>Recent Registrations</h6>
            <a href="users.php" class="btn btn-sm btn-outline-secondary">View All</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $u): ?>
                    <tr>
                        <td class="fw-semibold"><?= h($u['full_name']) ?></td>
                        <td>
                            <span class="badge bg-<?= $u['role']==='superadmin' ? 'danger' : ($u['role']==='admin' ? 'warning text-dark' : 'secondary') ?>">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td><?= h($u['dept_name'] ?? '—') ?></td>
                        <td class="text-muted small"><?= date('d M Y H:i', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
