<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$token = trim($_GET['token'] ?? '');
$error = '';
$success = '';

if ($token === '') {
    header('Location: ' . BASE_URL . '/forgot_password.php');
    exit;
}

// Validate token
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expiry > NOW() LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $error = 'Invalid or expired reset link. Please <a href="forgot_password.php">request a new one</a>.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    verify_csrf();
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expiry=NULL WHERE id=?")
            ->execute([$hash, $user['id']]);
        $success = 'Password updated successfully. <a href="login.php" class="fw-bold">Login now</a>.';
    }
}

$page_title = 'Reset Password';
include __DIR__ . '/includes/header.php';
?>

<div class="min-vh-75 d-flex align-items-center justify-content-center py-5" style="min-height:70vh">
    <div class="cim-auth-card mx-3">
        <div class="cim-auth-header">
            <i class="bi bi-shield-lock-fill text-warning" style="font-size:2.5rem"></i>
            <h4 class="text-white fw-bold mt-2 mb-0">Set New Password</h4>
            <p class="text-white-50 small mb-0">Choose a strong password</p>
        </div>

        <div class="cim-auth-body">
            <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <?php if ($user && !$success): ?>
            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="Min 8 characters" required autofocus>
                        <button class="btn btn-outline-secondary" type="button"
                                onclick="togglePw('password',this)"><i class="bi bi-eye"></i></button>
                    </div>
                    <span id="pw-strength" class="small mt-1 d-block"></span>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="confirm_password" class="form-control"
                               placeholder="Repeat password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-cim w-100 py-2">
                    <i class="bi bi-check-circle-fill me-2"></i>Update Password
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function togglePw(id, btn) {
    const f = document.getElementById(id);
    f.type = f.type === 'password' ? 'text' : 'password';
    btn.querySelector('i').className = f.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
