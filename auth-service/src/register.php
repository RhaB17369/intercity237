<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$departments = get_departments($pdo);
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $full_name    = trim($_POST['full_name'] ?? '');
    $home_address = trim($_POST['home_address'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $password     = $_POST['password'] ?? '';
    $confirm_pw   = $_POST['confirm_password'] ?? '';
    $dept_id      = (int)($_POST['department_id'] ?? 0);

    // Validation
    if (strlen($full_name) < 3)        $errors[] = 'Full name must be at least 3 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
    if (empty($phone) && empty($home_address)) $errors[] = 'Provide at least a phone number or home address.';
    if (strlen($username) < 3)         $errors[] = 'Username must be at least 3 characters.';
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) $errors[] = 'Username: letters, numbers and underscores only.';
    if (strlen($password) < 8)         $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm_pw)     $errors[] = 'Passwords do not match.';
    if ($dept_id <= 0)                 $errors[] = 'Please select a department.';

    // Check uniqueness
    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email=? OR username=?");
        $chk->execute([$email, $username]);
        if ($chk->fetch()) $errors[] = 'Email or username already taken.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name,home_address,email,phone,username,password,role,department_id)
                                VALUES (?,?,?,?,?,?,'employee',?)");
        $stmt->execute([$full_name, $home_address, $email, $phone, $username, $hash, $dept_id]);
        $success = 'Registration successful! You can now <a href="login.php" class="fw-bold">login</a>.';
    }
}

$page_title = 'Register';
include __DIR__ . '/includes/header.php';
?>

<div class="cim-auth-wrap" style="align-items:flex-start;padding-top:48px;padding-bottom:48px">
    <div style="width:100%;max-width:620px;margin:0 auto;padding:0 16px">
        <div class="cim-auth-card" style="max-width:100%">
            <div class="cim-auth-header">
                <div class="auth-icon-ring">
                    <i class="bi bi-person-plus-fill text-warning" style="font-size:1.8rem"></i>
                </div>
                <h4 class="text-white fw-bold mb-1" style="font-family:'Sora',sans-serif">Employee Registration</h4>
                <p class="text-white-50 small mb-0">Create your Intercity237 account</p>
            </div>

                    <div class="cim-auth-body">
                        <?php if ($success): ?>
                        <div class="alert alert-success auto-dismiss"><?= $success ?></div>
                        <?php endif; ?>

                        <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $e): ?>
                                <li><?= h($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <form method="POST" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                            <div class="row g-3">
                                <!-- Full Name -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                        <input type="text" name="full_name" class="form-control"
                                               placeholder="e.g. Jean-Pierre Mbarga"
                                               value="<?= h($_POST['full_name'] ?? '') ?>" required>
                                    </div>
                                </div>

                                <!-- Department -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-diagram-3-fill"></i></span>
                                        <select name="department_id" class="form-select" required>
                                            <option value="">— Select your department —</option>
                                            <?php foreach ($departments as $d): ?>
                                            <option value="<?= $d['id'] ?>"
                                                <?= (($_POST['department_id'] ?? '') == $d['id']) ? 'selected' : '' ?>>
                                                <?= h($d['name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Email & Phone -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                                        <input type="email" name="email" class="form-control"
                                               placeholder="your@email.com"
                                               value="<?= h($_POST['email'] ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Phone Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-telephone-fill"></i></span>
                                        <input type="tel" name="phone" class="form-control"
                                               placeholder="+237 6XX XXX XXX"
                                               value="<?= h($_POST['phone'] ?? '') ?>">
                                    </div>
                                </div>

                                <!-- Home Address -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Home Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-geo-alt-fill"></i></span>
                                        <input type="text" name="home_address" class="form-control"
                                               placeholder="Quarter, City, Region"
                                               value="<?= h($_POST['home_address'] ?? '') ?>">
                                    </div>
                                </div>

                                <!-- Username -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-at"></i></span>
                                        <input type="text" name="username" class="form-control"
                                               placeholder="e.g. jmbarga"
                                               value="<?= h($_POST['username'] ?? '') ?>" required>
                                    </div>
                                </div>

                                <!-- Password -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                        <input type="password" id="password" name="password" class="form-control"
                                               placeholder="Min 8 characters" required>
                                        <button class="btn btn-outline-secondary" type="button"
                                                onclick="togglePw('password',this)"><i class="bi bi-eye"></i></button>
                                    </div>
                                    <span id="pw-strength" class="small mt-1 d-block"></span>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                        <input type="password" name="confirm_password" class="form-control"
                                               placeholder="Repeat password" required>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-cim w-100 mt-4 py-2">
                                <i class="bi bi-person-check-fill me-2"></i>Create Account
                            </button>
                        </form>

                        <hr class="my-4">
                        <p class="text-center text-muted mb-0 small">
                            Already registered?
                            <a href="<?= BASE_URL ?>/login.php" class="text-cim-orange fw-semibold text-decoration-none">Login here</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
