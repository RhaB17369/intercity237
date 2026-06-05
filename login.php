<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['full_name']     = $user['full_name'];
            $_SESSION['email']         = $user['email'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['department_id'] = $user['department_id'];

            if (in_array($user['role'], ['admin', 'superadmin'])) {
                header('Location: ' . BASE_URL . '/admin/index.php');
            } else {
                header('Location: ' . BASE_URL . '/index.php');
            }
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$page_title = 'Login';
include __DIR__ . '/includes/header.php';
?>

<div class="cim-auth-wrap">
    <div class="cim-auth-card">

        <div class="cim-auth-header">
            <div class="auth-icon-ring">
                <i class="bi bi-person-fill text-warning" style="font-size:2rem"></i>
            </div>
            <h4 class="text-white fw-bold mb-1" style="font-family:'Sora',sans-serif">Employee Login</h4>
            <p class="text-white-50 small mb-0">Intercity237</p>
        </div>

        <div class="cim-auth-body">
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show auto-dismiss" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= h($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Username or Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="Enter username or email"
                               value="<?= h($_POST['username'] ?? '') ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="pw-input" class="form-control" placeholder="Enter password" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePw('pw-input',this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember">
                        <label class="form-check-label small" for="remember">Remember me</label>
                    </div>
                    <a href="<?= BASE_URL ?>/forgot_password.php" class="text-cim-orange small text-decoration-none">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" class="btn btn-cim w-100 py-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                </button>
            </form>

            <hr class="my-4">
            <p class="text-center text-muted mb-0 small">
                Don't have an account?
                <a href="<?= BASE_URL ?>/register.php" class="text-cim-orange fw-semibold text-decoration-none">Sign Up</a>
            </p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
