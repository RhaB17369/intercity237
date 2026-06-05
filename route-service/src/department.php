<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$dept_id = (int)($_GET['id'] ?? 0);
if ($dept_id <= 0) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Load department
$stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
$stmt->execute([$dept_id]);
$dept = $stmt->fetch();
if (!$dept) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Access control: admin sees all, employee only their dept
if (!is_admin() && $_SESSION['department_id'] != $dept_id) {
    header('Location: ' . BASE_URL . '/index.php?error=access_denied');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$msg = '';
$msg_type = 'success';

// ---- DELETE ----
if (isset($_GET['delete']) && is_admin()) {
    $del_id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM department_records WHERE id=? AND department_id=?")->execute([$del_id, $dept_id]);
    $msg = 'Record deleted.';
    $msg_type = 'warning';
}

// ---- CREATE / UPDATE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action   = $_POST['action'] ?? '';
    $rec_id   = (int)($_POST['rec_id'] ?? 0);

    $employee_id = trim($_POST['employee_id'] ?? '');
    $full_name   = trim($_POST['full_name'] ?? '');
    $position    = trim($_POST['position'] ?? '');
    $salary      = trim($_POST['salary'] ?? '');
    $start_date  = $_POST['start_date'] ?? null;
    $status      = $_POST['status'] ?? 'Active';
    $phone       = trim($_POST['phone'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $address     = trim($_POST['address'] ?? '');
    $remarks     = trim($_POST['remarks'] ?? '');

    $allowed_statuses = ['Active','On Leave','Suspended','Resigned'];
    if (!in_array($status, $allowed_statuses)) $status = 'Active';
    if (empty($start_date)) $start_date = null;

    if ($action === 'create' && is_admin()) {
        $pdo->prepare("INSERT INTO department_records
            (department_id,employee_id,full_name,position,salary,start_date,status,phone,email,address,remarks,created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$dept_id,$employee_id,$full_name,$position,$salary,$start_date,$status,$phone,$email,$address,$remarks,$user_id]);
        $msg = 'Record created successfully.';

    } elseif ($action === 'update' && $rec_id > 0) {
        // Admin can update any; employee only their own row
        $where = is_admin()
            ? "id=? AND department_id=?"
            : "id=? AND department_id=? AND email=?";

        $params_check = is_admin()
            ? [$rec_id, $dept_id]
            : [$rec_id, $dept_id, $_SESSION['email'] ?? ''];

        $chk = $pdo->prepare("SELECT id FROM department_records WHERE $where");
        $chk->execute($params_check);

        if ($chk->fetch()) {
            $pdo->prepare("UPDATE department_records SET
                employee_id=?,full_name=?,position=?,salary=?,start_date=?,
                status=?,phone=?,email=?,address=?,remarks=?
                WHERE id=?")
                ->execute([$employee_id,$full_name,$position,$salary,$start_date,$status,$phone,$email,$address,$remarks,$rec_id]);
            $msg = 'Record updated successfully.';
        } else {
            $msg = 'Unauthorized or record not found.';
            $msg_type = 'danger';
        }
    }

    header('Location: ' . BASE_URL . '/department.php?id=' . $dept_id . '&msg=' . urlencode($msg));
    exit;
}

if (isset($_GET['msg'])) { $msg = h($_GET['msg']); }

// Load records
$records = $pdo->prepare("SELECT * FROM department_records WHERE department_id=? ORDER BY id DESC");
$records->execute([$dept_id]);
$records = $records->fetchAll();

// For edit modal — load specific record
$edit_rec = null;
if (isset($_GET['edit']) && ($rec_id = (int)$_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM department_records WHERE id=? AND department_id=?");
    $stmt->execute([$rec_id, $dept_id]);
    $edit_rec = $stmt->fetch();
    // Employee can only edit their own row
    if (!is_admin() && $edit_rec && $edit_rec['email'] !== ($_SESSION['email'] ?? '')) {
        $edit_rec = null;
    }
}

$page_title = h($dept['name']) . ' Department';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4 mt-2">

    <!-- Dept Header -->
    <div class="cim-dept-header mb-0 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <div class="cim-hero-badge mb-2">Department</div>
            <h2 class="fw-bold mb-1"><?= h($dept['name']) ?></h2>
            <p class="text-white-50 mb-0 small"><?= h($dept['description'] ?? '') ?></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if (is_admin()): ?>
            <button class="btn btn-warning fw-semibold" data-bs-toggle="modal" data-bs-target="#recordModal">
                <i class="bi bi-plus-circle-fill me-2"></i>Add Record
            </button>
            <?php endif; ?>
            <button class="btn btn-light fw-semibold"
                    onclick="exportTableToPDF('deptTable','<?= h($dept['name']) ?>_HR','HR Records','<?= h($dept['name']) ?>')">
                <i class="bi bi-file-earmark-pdf-fill me-2 text-danger"></i>Export PDF
            </button>
        </div>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show auto-dismiss mt-3" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= $msg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="cim-table-wrapper mt-0" style="border-radius:0 0 12px 12px">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="deptTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Emp. ID</th>       <!-- Field 1 -->
                        <th>Full Name</th>      <!-- Field 2 -->
                        <th>Position</th>       <!-- Field 3 -->
                        <th>Salary (XAF)</th>   <!-- Field 4 -->
                        <th>Start Date</th>     <!-- Field 5 -->
                        <th>Status</th>         <!-- Field 6 -->
                        <th>Phone</th>          <!-- Field 7 -->
                        <th>Email</th>          <!-- Field 8 -->
                        <th>Address</th>        <!-- Field 9 -->
                        <th>Remarks</th>        <!-- Field 10 -->
                        <th class="no-export">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="12" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No records yet.
                            <?= is_admin() ? 'Click <strong>Add Record</strong> to start.' : '' ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td><?= h($r['employee_id'] ?? '') ?></td>
                        <td class="fw-semibold"><?= h($r['full_name'] ?? '') ?></td>
                        <td><?= h($r['position'] ?? '') ?></td>
                        <td><?= $r['salary'] ? number_format((float)$r['salary']) : '' ?></td>
                        <td><?= $r['start_date'] ? date('d/m/Y', strtotime($r['start_date'])) : '' ?></td>
                        <td>
                            <span class="status-badge status-<?= h($r['status'] ?? 'Active') ?>">
                                <?= h($r['status'] ?? 'Active') ?>
                            </span>
                        </td>
                        <td><?= h($r['phone'] ?? '') ?></td>
                        <td><?= h($r['email'] ?? '') ?></td>
                        <td class="text-truncate" style="max-width:120px"><?= h($r['address'] ?? '') ?></td>
                        <td class="text-truncate" style="max-width:120px"><?= h($r['remarks'] ?? '') ?></td>
                        <td class="no-export">
                            <?php
                            $can_edit = is_admin() || ($r['email'] === ($_SESSION['email'] ?? ''));
                            ?>
                            <?php if ($can_edit): ?>
                            <a href="?id=<?= $dept_id ?>&edit=<?= $r['id'] ?>"
                               class="btn btn-sm btn-outline-primary me-1"
                               title="Edit" data-bs-toggle="modal" data-bs-target="#recordModal"
                               onclick="loadEdit(<?= htmlspecialchars(json_encode($r)) ?>)">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (is_admin()): ?>
                            <button class="btn btn-sm btn-outline-danger"
                                    onclick="confirmDelete('?id=<?= $dept_id ?>&delete=<?= $r['id'] ?>','<?= h(addslashes($r['full_name'] ?? 'this record')) ?>')"
                                    title="Delete">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===== CREATE / EDIT MODAL ===== -->
<div class="modal fade" id="recordModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header cim-modal-header">
                <h5 class="modal-title fw-bold" id="modalTitle">
                    <i class="bi bi-person-plus-fill me-2"></i>Add HR Record
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="rec_id" id="formRecId" value="">

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Employee ID <span class="text-warning">(Field 1)</span></label>
                            <input type="text" name="employee_id" id="f_employee_id" class="form-control" placeholder="CIM-001">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Full Name <span class="text-warning">(Field 2)</span></label>
                            <input type="text" name="full_name" id="f_full_name" class="form-control" placeholder="e.g. Jean-Pierre Mbarga">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Position/Title <span class="text-warning">(Field 3)</span></label>
                            <input type="text" name="position" id="f_position" class="form-control" placeholder="e.g. Senior Engineer">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Salary (XAF) <span class="text-warning">(Field 4)</span></label>
                            <input type="number" name="salary" id="f_salary" class="form-control" placeholder="e.g. 450000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Start Date <span class="text-warning">(Field 5)</span></label>
                            <input type="date" name="start_date" id="f_start_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Status <span class="text-warning">(Field 6)</span></label>
                            <select name="status" id="f_status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="On Leave">On Leave</option>
                                <option value="Suspended">Suspended</option>
                                <option value="Resigned">Resigned</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Phone <span class="text-warning">(Field 7)</span></label>
                            <input type="text" name="phone" id="f_phone" class="form-control" placeholder="+237 6XX XXX XXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Email <span class="text-warning">(Field 8)</span></label>
                            <input type="email" name="email" id="f_email" class="form-control" placeholder="employee@intercity237.cm">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Home Address <span class="text-warning">(Field 9)</span></label>
                            <input type="text" name="address" id="f_address" class="form-control" placeholder="Quarter, City, Region">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Remarks <span class="text-warning">(Field 10)</span></label>
                            <textarea name="remarks" id="f_remarks" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-cim">
                        <i class="bi bi-save-fill me-2"></i>Save Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function loadEdit(rec) {
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil-fill me-2"></i>Edit HR Record';
    document.getElementById('formAction').value = 'update';
    document.getElementById('formRecId').value = rec.id;
    document.getElementById('f_employee_id').value = rec.employee_id || '';
    document.getElementById('f_full_name').value   = rec.full_name  || '';
    document.getElementById('f_position').value    = rec.position   || '';
    document.getElementById('f_salary').value      = rec.salary     || '';
    document.getElementById('f_start_date').value  = rec.start_date || '';
    document.getElementById('f_status').value      = rec.status     || 'Active';
    document.getElementById('f_phone').value       = rec.phone      || '';
    document.getElementById('f_email').value       = rec.email      || '';
    document.getElementById('f_address').value     = rec.address    || '';
    document.getElementById('f_remarks').value     = rec.remarks    || '';
}

// Reset modal on close
document.getElementById('recordModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-person-plus-fill me-2"></i>Add HR Record';
    document.getElementById('formAction').value = 'create';
    document.getElementById('formRecId').value = '';
    this.querySelector('form').reset();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
