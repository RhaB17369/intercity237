<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$departments = get_departments($pdo);
$msg = '';
$msg_type = 'success';

// ---- DELETE ----
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    // Cannot delete superadmin or yourself
    $target = $pdo->prepare("SELECT role FROM users WHERE id=?");
    $target->execute([$del_id]);
    $t = $target->fetch();
    if ($t && $t['role'] !== 'superadmin' && $del_id !== (int)$_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$del_id]);
        $msg = 'Employee deleted.';
        $msg_type = 'warning';
    } else {
        $msg = 'Cannot delete this user.';
        $msg_type = 'danger';
    }
}

// ---- CREATE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    verify_csrf();
    $full_name    = trim($_POST['full_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $home_address = trim($_POST['home_address'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $password     = $_POST['password'] ?? '';
    $dept_id      = (int)($_POST['department_id'] ?? 0) ?: null;

    if ($full_name && $email && $username && $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $pdo->prepare("INSERT INTO users (full_name,email,phone,home_address,username,password,role,department_id)
                            VALUES (?,?,?,?,?,?,'employee',?)")
                ->execute([$full_name, $email, $phone, $home_address, $username, $hash, $dept_id]);
            $msg = "Employee '$full_name' created. Username: $username";
        } catch (PDOException $e) {
            $msg = 'Error: email or username already exists.';
            $msg_type = 'danger';
        }
    } else {
        $msg = 'All required fields must be filled.';
        $msg_type = 'danger';
    }
}

// Load employees
$employees = $pdo->query(
    "SELECT u.*, d.name as dept_name FROM users u
     LEFT JOIN departments d ON u.department_id = d.id
     WHERE u.role = 'employee'
     ORDER BY u.created_at DESC"
)->fetchAll();

$page_title = 'Manage Employees';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4 px-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-bold mb-0">Manage Employees</h3>
            <p class="text-muted small mb-0"><?= count($employees) ?> employee<?= count($employees) != 1 ? 's' : '' ?> registered</p>
        </div>
        <button class="btn btn-cim" data-bs-toggle="modal" data-bs-target="#createUserModal">
            <i class="bi bi-person-plus-fill me-2"></i>Add Employee
        </button>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show auto-dismiss">
        <?= h($msg) ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="cim-table-wrapper">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="empTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Registered</th>
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-5">
                        <i class="bi bi-people d-block fs-1 mb-2"></i>No employees yet.
                    </td></tr>
                    <?php else: ?>
                    <?php foreach ($employees as $i => $emp): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= h($emp['full_name']) ?></td>
                        <td><code><?= h($emp['username']) ?></code></td>
                        <td><?= h($emp['email']) ?></td>
                        <td><?= h($emp['phone'] ?? '') ?></td>
                        <td><?= h($emp['dept_name'] ?? '—') ?></td>
                        <td class="text-muted small"><?= date('d/m/Y', strtotime($emp['created_at'])) ?></td>
                        <td class="no-export">
                            <button class="btn btn-sm btn-outline-danger"
                                    onclick="confirmDelete('users.php?delete=<?= $emp['id'] ?>','<?= h(addslashes($emp['full_name'])) ?>')"
                                    title="Delete">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-2 text-end">
        <button class="btn btn-sm btn-outline-danger"
                onclick="exportTableToPDF('empTable','Employees_List','Employee List','All Departments')">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>Export PDF
        </button>
    </div>
</div>

<!-- Create Employee Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header cim-modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Add Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">— None —</option>
                                <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= h($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Home Address</label>
                            <input type="text" name="home_address" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Username *</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Password *</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cim"><i class="bi bi-save-fill me-2"></i>Create Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
