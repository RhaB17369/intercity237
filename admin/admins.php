<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_superadmin(); // ONLY superadmin can access this page

$departments = get_departments($pdo);
$msg = '';
$msg_type = 'success';

// ---- DELETE ----
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    if ($del_id !== (int)$_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id=? AND role='admin'")->execute([$del_id]);
        $msg = 'Admin removed.';
        $msg_type = 'warning';
    } else {
        $msg = 'You cannot delete your own account.';
        $msg_type = 'danger';
    }
}

// ---- CREATE ADMIN ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    verify_csrf();
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';

    if ($full_name && $email && $username && $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $pdo->prepare("INSERT INTO users (full_name,email,username,password,role) VALUES (?,?,?,?,'admin')")
                ->execute([$full_name, $email, $username, $hash]);
            $msg = "Admin '$username' created successfully.";
        } catch (PDOException $e) {
            $msg = 'Error: email or username already exists.';
            $msg_type = 'danger';
        }
    } else {
        $msg = 'All fields are required.';
        $msg_type = 'danger';
    }
}

$admins = $pdo->query(
    "SELECT * FROM users WHERE role IN('admin','superadmin') ORDER BY role DESC, created_at ASC"
)->fetchAll();

$page_title = 'Manage Admins';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold mb-0">Manage Admins</h3>
            <p class="text-muted small mb-0">
                <i class="bi bi-shield-fill text-danger me-1"></i>
                Super Admin access only — <?= count($admins) ?> admin<?= count($admins) != 1 ? 's' : '' ?> total
            </p>
        </div>
        <button class="btn btn-cim" data-bs-toggle="modal" data-bs-target="#createAdminModal">
            <i class="bi bi-person-badge-fill me-2"></i>Add Admin
        </button>
    </div>

    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <span>Only the <strong>Super Administrator</strong> can create or delete admin accounts. Admins can manage employees.</span>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show auto-dismiss">
        <?= h($msg) ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="cim-table-wrapper">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $i => $a): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= h($a['full_name']) ?></td>
                        <td><code><?= h($a['username']) ?></code></td>
                        <td><?= h($a['email']) ?></td>
                        <td>
                            <span class="badge bg-<?= $a['role'] === 'superadmin' ? 'danger' : 'warning text-dark' ?> px-3">
                                <i class="bi bi-shield-fill me-1"></i><?= ucfirst($a['role']) ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= date('d/m/Y', strtotime($a['created_at'])) ?></td>
                        <td>
                            <?php if ($a['role'] !== 'superadmin' && $a['id'] != $_SESSION['user_id']): ?>
                            <button class="btn btn-sm btn-outline-danger"
                                    onclick="confirmDelete('admins.php?delete=<?= $a['id'] ?>','<?= h(addslashes($a['full_name'])) ?>')"
                                    title="Remove admin">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Admin Modal -->
<div class="modal fade" id="createAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header cim-modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-badge-fill me-2"></i>Create Admin Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Username *</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Password *</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-semibold">
                        <i class="bi bi-shield-plus me-2"></i>Create Admin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
