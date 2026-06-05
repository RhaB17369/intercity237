<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$message = '';
$type    = 'info';
$dev_link = ''; // show reset link in dev (no mail server)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $type    = 'danger';
    } else {
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token   = bin2hex(random_bytes(32));
            $expiry  = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            $pdo->prepare("UPDATE users SET reset_token=?, reset_expiry=? WHERE id=?")
                ->execute([$token, $expiry, $user['id']]);

            $reset_url = APP_URL . '/reset_password.php?token=' . $token;

            // Try to send email (may not work without mail server)
            $subject = 'Intercity237 — Password Reset';
            $body    = "Hello {$user['full_name']},\n\n"
                     . "Click the link below to reset your password (valid 1 hour):\n\n"
                     . $reset_url . "\n\n"
                     . "If you did not request this, ignore this email.\n\n"
                     . "— Intercity237 HR Team";
            $headers = "From: " . MAIL_FROM . "\r\nX-Mailer: PHP/" . phpversion();
            @mail($email, $subject, $body, $headers);

            // Show link in dev mode
            $dev_link = $reset_url;
        }

        // Always show same message (security: don't reveal if email exists)
        $message = 'If this email is registered, a password reset link has been sent.';
        $type    = 'success';
    }
}

$page_title = 'Forgot Password';
include __DIR__ . '/includes/header.php';
?>

<div class="cim-auth-wrap">
    <div class="cim-auth-card">
        <div class="cim-auth-header">
            <div class="auth-icon-ring">
                <i class="bi bi-key-fill text-warning" style="font-size:1.8rem"></i>
            </div>
            <h4 class="text-white fw-bold mb-1" style="font-family:'Sora',sans-serif">Forgot Password?</h4>
            <p class="text-white-50 small mb-0">Enter your email to receive a reset link</p>
        </div>

        <div class="cim-auth-body">
            <?php if ($message): ?>
            <div class="alert alert-<?= $type ?> auto-dismiss">
                <i class="bi bi-<?= $type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
                <?= h($message) ?>
            </div>
            <?php endif; ?>

            <?php if ($dev_link): ?>
            <div class="alert alert-warning small">
                <strong>🔧 Dev mode:</strong> No mail server configured.<br>
                <a href="<?= h($dev_link) ?>" class="fw-bold">Click here to reset password</a>
            </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div class="mb-4">
                    <label class="form-label fw-semibold">Registered Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                        <input type="email" name="email" class="form-control"
                               placeholder="your@email.com"
                               value="<?= h($_POST['email'] ?? '') ?>" required autofocus>
                    </div>
                </div>

                <button type="submit" class="btn btn-cim w-100 py-2">
                    <i class="bi bi-send-fill me-2"></i>Send Reset Link
                </button>
            </form>

            <hr class="my-4">
            <p class="text-center text-muted mb-0 small">
                Remember your password?
                <a href="<?= BASE_URL ?>/login.php" class="text-cim-orange fw-semibold text-decoration-none">Login</a>
            </p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
