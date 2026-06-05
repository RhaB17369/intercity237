<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . (is_admin() ? '/admin/index.php' : '/index.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['role']      = $user['role'];

            header('Location: ' . BASE_URL . (in_array($user['role'], ['admin','superadmin']) ? '/admin/index.php' : '/index.php'));
            exit;
        } else {
            $error = 'Identifiant ou mot de passe incorrect.';
        }
    }
}

$page_title = 'Connexion';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Connexion — Intercity237</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  :root { --g:#00b96b; --dark:#0a0f1e; --card:#111827; --border:#1f2937; --muted:#6b7280; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { background:var(--dark); color:#e5e7eb; font-family:'Inter',sans-serif; min-height:100vh; display:flex; flex-direction:column; }

  /* Nav */
  nav { background:rgba(10,15,30,.95); border-bottom:1px solid var(--border); padding:14px 0; }
  .brand { font-family:'Sora',sans-serif; font-size:1.2rem; font-weight:800; color:#fff; text-decoration:none; display:flex; align-items:center; gap:10px; }
  .brand-icon { width:34px; height:34px; background:var(--g); border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:.9rem; color:#fff; }
  .brand span { color:var(--g); }

  /* Layout */
  .auth-wrap { flex:1; display:flex; }
  .auth-left {
    flex:1; background:linear-gradient(135deg,#0a0f1e,#061a0e);
    display:none; align-items:center; justify-content:center; padding:48px;
    position:relative; overflow:hidden;
  }
  @media(min-width:992px){ .auth-left { display:flex; } }
  .auth-left::before { content:''; position:absolute; inset:0; background:radial-gradient(ellipse 80% 60% at 50% 30%, rgba(0,185,107,.12) 0%, transparent 70%); }
  .auth-right { width:100%; max-width:460px; display:flex; align-items:center; justify-content:center; padding:32px 24px; margin:auto; }

  /* Card */
  .auth-card { width:100%; background:var(--card); border:1px solid var(--border); border-radius:24px; overflow:hidden; box-shadow:0 32px 80px rgba(0,0,0,.5); }
  .auth-header { background:linear-gradient(135deg,#0d1f10,#0a1a14); padding:32px; text-align:center; border-bottom:1px solid var(--border); }
  .auth-icon { width:64px; height:64px; background:rgba(0,185,107,.12); border:2px solid rgba(0,185,107,.25); border-radius:18px; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; font-size:1.8rem; color:var(--g); }
  .auth-body { padding:32px; }

  /* Inputs */
  label { display:block; font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--muted); margin-bottom:6px; }
  .input-wrap { position:relative; }
  .input-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:.95rem; }
  input[type=text], input[type=email], input[type=password] {
    width:100%; background:#0a0f1e; border:1px solid var(--border); color:#e5e7eb;
    border-radius:12px; padding:12px 42px; font-size:.95rem; transition:.2s; font-family:'Inter',sans-serif;
  }
  input:focus { outline:none; border-color:var(--g); box-shadow:0 0 0 3px rgba(0,185,107,.15); }
  .pw-toggle { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--muted); cursor:pointer; font-size:.9rem; }
  .pw-toggle:hover { color:#e5e7eb; }

  /* Alert */
  .alert-err { background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.25); color:#f87171; border-radius:12px; padding:12px 16px; margin-bottom:20px; display:flex; align-items:center; gap:8px; font-size:.9rem; }

  /* Button */
  .btn-login { background:var(--g); color:#fff; border:none; border-radius:12px; padding:13px; width:100%; font-weight:800; font-size:1rem; cursor:pointer; transition:.2s; font-family:'Inter',sans-serif; }
  .btn-login:hover { background:#00a85e; transform:translateY(-1px); box-shadow:0 8px 28px rgba(0,185,107,.4); }

  /* Misc */
  .divider { border-color:var(--border); margin:24px 0; }
  a { color:var(--g); text-decoration:none; font-weight:600; }
  a:hover { text-decoration:underline; }
  small { color:var(--muted); font-size:.82rem; }

  /* Left panel content */
  .promo-title { font-family:'Sora',sans-serif; font-size:2.2rem; font-weight:800; color:#fff; line-height:1.2; margin-bottom:20px; }
  .promo-title span { color:var(--g); }
  .promo-feature { display:flex; align-items:center; gap:12px; margin-bottom:14px; }
  .promo-feature-icon { width:40px; height:40px; background:rgba(0,185,107,.12); border-radius:10px; display:flex; align-items:center; justify-content:center; color:var(--g); flex-shrink:0; }
  .promo-feature p { margin:0; font-size:.9rem; }
  .promo-feature strong { color:#fff; display:block; margin-bottom:2px; }
  .promo-feature span { color:var(--muted); font-size:.82rem; }
  .cities-line { display:flex; gap:8px; flex-wrap:wrap; margin-top:28px; }
  .city-chip { background:rgba(255,255,255,.05); border:1px solid var(--border); border-radius:20px; padding:5px 12px; font-size:.78rem; color:var(--muted); }
</style>
</head>
<body>

<nav>
  <div class="container">
    <a href="http://localhost/" class="brand">
      <div class="brand-icon"><i class="bi bi-bus-front-fill"></i></div>
      Intercity<span>237</span>
    </a>
  </div>
</nav>

<div class="auth-wrap">

  <!-- Left — promo panel -->
  <div class="auth-left">
    <div style="position:relative;z-index:1;max-width:400px;">
      <h1 class="promo-title">Voyagez partout<br>au <span>Cameroun</span></h1>

      <div class="promo-feature">
        <div class="promo-feature-icon"><i class="bi bi-ticket-perforated-fill"></i></div>
        <p><strong>Réservation en ligne</strong><span>Choisissez votre trajet, votre heure, votre opérateur</span></p>
      </div>
      <div class="promo-feature">
        <div class="promo-feature-icon"><i class="bi bi-phone-fill"></i></div>
        <p><strong>Paiement Mobile Money</strong><span>MTN MoMo & Orange Money acceptés</span></p>
      </div>
      <div class="promo-feature">
        <div class="promo-feature-icon"><i class="bi bi-qr-code"></i></div>
        <p><strong>Ticket QR instantané</strong><span>Présentez votre QR code à l'embarquement</span></p>
      </div>

      <div class="cities-line">
        <?php foreach (['Yaoundé','Douala','Bafoussam','Garoua','Bamenda','Ngaoundéré'] as $c): ?>
        <div class="city-chip"><i class="bi bi-geo-alt-fill me-1" style="color:var(--g);font-size:.65rem;"></i><?= $c ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Right — form -->
  <div class="auth-right">
    <div class="auth-card">

      <div class="auth-header">
        <div class="auth-icon"><i class="bi bi-person-fill"></i></div>
        <h4 style="font-family:'Sora',sans-serif;font-weight:800;color:#fff;margin-bottom:4px;">Connexion</h4>
        <p style="color:var(--muted);font-size:.88rem;margin:0;">Accédez à votre espace Intercity237</p>
      </div>

      <div class="auth-body">
        <?php if ($error): ?>
        <div class="alert-err"><i class="bi bi-exclamation-triangle-fill"></i><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

          <div class="mb-4">
            <label>Identifiant ou Email</label>
            <div class="input-wrap">
              <i class="bi bi-person input-icon"></i>
              <input type="text" name="username" placeholder="Nom d'utilisateur ou email"
                     value="<?= h($_POST['username'] ?? '') ?>" required autofocus>
            </div>
          </div>

          <div class="mb-4">
            <label>Mot de passe</label>
            <div class="input-wrap">
              <i class="bi bi-lock input-icon"></i>
              <input type="password" name="password" id="pwInput" placeholder="Votre mot de passe" required>
              <button class="pw-toggle" type="button" onclick="togglePw()">
                <i class="bi bi-eye" id="pwIcon"></i>
              </button>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-4" style="font-size:.85rem;">
            <label style="text-transform:none;letter-spacing:0;font-weight:400;margin:0;display:flex;align-items:center;gap:6px;cursor:pointer;">
              <input type="checkbox" style="width:15px;height:15px;accent-color:var(--g);"> Se souvenir de moi
            </label>
            <a href="<?= BASE_URL ?>/forgot_password.php">Mot de passe oublié ?</a>
          </div>

          <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
          </button>
        </form>

        <hr class="divider">
        <p style="text-align:center;font-size:.88rem;color:var(--muted);margin:0;">
          Pas encore de compte ? <a href="<?= BASE_URL ?>/register.php">Créer un compte</a>
        </p>
      </div>
    </div>
  </div>

</div>

<script>
function togglePw() {
  const input = document.getElementById('pwInput');
  const icon  = document.getElementById('pwIcon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'bi bi-eye';
  }
}
</script>
</body>
</html>
