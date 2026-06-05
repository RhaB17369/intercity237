<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

// Fetch all users grouped by role
$superadmins = $pdo->query(
    "SELECT u.id, u.full_name, u.email, u.username, u.phone, u.created_at, d.name as dept_name
     FROM users u LEFT JOIN departments d ON u.department_id = d.id
     WHERE u.role = 'superadmin' ORDER BY u.created_at"
)->fetchAll();

$admins = $pdo->query(
    "SELECT u.id, u.full_name, u.email, u.username, u.phone, u.created_at, d.name as dept_name
     FROM users u LEFT JOIN departments d ON u.department_id = d.id
     WHERE u.role = 'admin' ORDER BY u.created_at"
)->fetchAll();

$employees = $pdo->query(
    "SELECT u.id, u.full_name, u.email, u.username, u.phone, u.home_address, u.created_at, d.name as dept_name
     FROM users u LEFT JOIN departments d ON u.department_id = d.id
     WHERE u.role = 'employee' ORDER BY d.name, u.full_name"
)->fetchAll();

$page_title = 'Database View';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold mb-0">Database View</h3>
            <p class="text-muted small mb-0">Live view of all users in the system</p>
        </div>
        <button class="btn btn-outline-danger"
                onclick="exportTableToPDF('fullTable','Intercity237_Database','All Users Database','')">
            <i class="bi bi-file-earmark-pdf-fill me-2"></i>Export Full DB to PDF
        </button>
    </div>

    <!-- Summary badges -->
    <div class="d-flex gap-3 flex-wrap mb-4">
        <span class="badge bg-danger fs-6 px-3 py-2">
            <i class="bi bi-shield-fill me-1"></i><?= count($superadmins) ?> Super Admin<?= count($superadmins)!=1?'s':'' ?>
        </span>
        <span class="badge bg-warning text-dark fs-6 px-3 py-2">
            <i class="bi bi-shield me-1"></i><?= count($admins) ?> Admin<?= count($admins)!=1?'s':'' ?>
        </span>
        <span class="badge bg-secondary fs-6 px-3 py-2">
            <i class="bi bi-people-fill me-1"></i><?= count($employees) ?> Employee<?= count($employees)!=1?'s':'' ?>
        </span>
    </div>

    <!-- ===== SUPER ADMINS ===== -->
    <div class="mb-4">
        <h5 class="fw-bold d-flex align-items-center gap-2">
            <span class="badge bg-danger">Super Admin</span>
        </h5>
        <div class="cim-table-wrapper">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th><th>Full Name</th><th>Username</th>
                            <th>Email</th><th>Phone</th><th>Department</th><th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($superadmins)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-3">No super admins.</td></tr>
                        <?php else: ?>
                        <?php foreach ($superadmins as $u): ?>
                        <tr>
                            <td class="text-muted small"><?= $u['id'] ?></td>
                            <td class="fw-semibold"><?= h($u['full_name']) ?></td>
                            <td><code><?= h($u['username']) ?></code></td>
                            <td><?= h($u['email']) ?></td>
                            <td><?= h($u['phone'] ?? '—') ?></td>
                            <td><?= h($u['dept_name'] ?? '—') ?></td>
                            <td class="text-muted small"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== ADMINS ===== -->
    <div class="mb-4">
        <h5 class="fw-bold d-flex align-items-center gap-2">
            <span class="badge bg-warning text-dark">Admins</span>
        </h5>
        <div class="cim-table-wrapper">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th><th>Full Name</th><th>Username</th>
                            <th>Email</th><th>Phone</th><th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($admins)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No admins yet.</td></tr>
                        <?php else: ?>
                        <?php foreach ($admins as $u): ?>
                        <tr>
                            <td class="text-muted small"><?= $u['id'] ?></td>
                            <td class="fw-semibold"><?= h($u['full_name']) ?></td>
                            <td><code><?= h($u['username']) ?></code></td>
                            <td><?= h($u['email']) ?></td>
                            <td><?= h($u['phone'] ?? '—') ?></td>
                            <td class="text-muted small"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== EMPLOYEES ===== -->
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="fw-bold d-flex align-items-center gap-2 mb-0">
                <span class="badge bg-secondary">Employees</span>
            </h5>
            <button class="btn btn-sm btn-outline-danger"
                    onclick="exportTableToPDF('empDbTable','Employees_Database','Employees List','')">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i>Export
            </button>
        </div>
        <div class="cim-table-wrapper">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="empDbTable">
                    <thead>
                        <tr>
                            <th>ID</th><th>Full Name</th><th>Username</th><th>Email</th>
                            <th>Phone</th><th>Department</th><th>Home Address</th><th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-people d-block fs-1 mb-2"></i>No employees yet.
                        </td></tr>
                        <?php else: ?>
                        <?php foreach ($employees as $u): ?>
                        <tr>
                            <td class="text-muted small"><?= $u['id'] ?></td>
                            <td class="fw-semibold"><?= h($u['full_name']) ?></td>
                            <td><code><?= h($u['username']) ?></code></td>
                            <td><?= h($u['email']) ?></td>
                            <td><?= h($u['phone'] ?? '—') ?></td>
                            <td><?= h($u['dept_name'] ?? '—') ?></td>
                            <td class="text-truncate" style="max-width:150px"><?= h($u['home_address'] ?? '—') ?></td>
                            <td class="text-muted small"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Combined table for full PDF export (hidden) -->
    <table id="fullTable" style="display:none">
        <thead>
            <tr><th>ID</th><th>Full Name</th><th>Username</th><th>Email</th><th>Role</th><th>Department</th><th>Created</th></tr>
        </thead>
        <tbody>
            <?php foreach (array_merge($superadmins, $admins, $employees) as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= h($u['full_name']) ?></td>
                <td><?= h($u['username']) ?></td>
                <td><?= h($u['email']) ?></td>
                <td><?= isset($u['home_address']) ? 'Employee' : (in_array($u, $admins) ? 'Admin' : 'SuperAdmin') ?></td>
                <td><?= h($u['dept_name'] ?? '—') ?></td>
                <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
