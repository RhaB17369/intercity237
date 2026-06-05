<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (strlen($full_name) < 3)                        $errors[] = 'Le nom complet doit avoir au moins 3 caractères.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))    $errors[] = 'Adresse email invalide.';
    if (empty($phone))                                 $errors[] = 'Le numéro de téléphone est obligatoire.';
    if (strlen($username) < 3)                         $errors[] = 'Le nom d\'utilisateur doit avoir au moins 3 caractères.';
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username))  $errors[] = 'Nom d\'utilisateur : lettres, chiffres et _ uniquement.';
    if (strlen($password) < 8)                         $errors[] = 'Le mot de passe doit avoir au moins 8 caractères.';
    if ($password !== $confirm)                        $errors[] = 'Les mots de passe ne correspondent pas.';

    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT id FROM users WHERE email=? OR username=?");
        $chk->execute([$email, $username]);
        if ($chk->fetch()) $errors[] = 'Email ou nom d\'utilisateur déjà utilisé.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (full_name,email,phone,username,password,role) VALUES (?,?,?,?,?,'passenger')")
            ->execute([$full_name, $email, $phone, $username, $hash]);
        $success = 'Compte créé avec succès ! <a href="login.php" style="color:var(--g);font-weight:700;">Se connecter</a>';
    }
}

$page_title = 'Inscription';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Inscription — Intercity237</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  :root { --g:#00b96b; --dark:#0a0f1e; --card:#111827; --border:#1f2937; --muted:#6b7280; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { background:var(--dark); color:#e5e7eb; font-family:'Inter',sans-serif; min-height:100vh; display:flex; flex-direction:column; }
  nav { background:rgba(10,15,30,.95); border-bottom:1px solid var(--border); padding:14px 0; }
  .brand { font-family:'Sora',sans-serif; font-size:1.2rem; font-weight:800; color:#fff; text-decoration:none; display:flex; align-items:center; gap:10px; }
  .brand-icon { width:34px; height:34px; background:var(--g); border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:.9rem; color:#fff; }
  .brand span { color:var(--g); }
  .auth-wrap { flex:1; display:flex; align-items:center; justify-content:center; padding:40px 16px; }
  .auth-card { width:100%; max-width:520px; background:var(--card); border:1px solid var(--border); border-radius:24px; overflow:hidden; }
  .auth-header { background:linear-gradient(135deg,#0d1f10,#0a1a14); padding:28px 32px; text-align:center; border-bottom:1px solid var(--border); }
  .auth-icon { width:60px; height:60px; background:rgba(0,185,107,.12); border:2px solid rgba(0,185,107,.25); border-radius:16px; display:flex; align-items:center; justify-content:center; margin:0 auto 14px; font-size:1.6rem; color:var(--g); }
  .auth-body { padding:32px; }
  label { display:block; font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--muted); margin-bottom:6px; }
  .input-wrap { position:relative; }
  .input-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--muted); }
  input[type=text], input[type=email], input[type=tel], input[type=password] {
    width:100%; background:#0a0f1e; border:1px solid var(--border); color:#e5e7eb;
    border-radius:12px; padding:11px 14px 11px 40px; font-size:.95rem; transition:.2s; font-family:'Inter',sans-serif;
  }
  input:focus { outline:none; border-color:var(--g); box-shadow:0 0 0 3px rgba(0,185,107,.15); }
  .pw-toggle { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--muted); cursor:pointer; }
  .alert-err { background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.25); color:#f87171; border-radius:12px; padding:12px 16px; margin-bottom:20px; font-size:.88rem; }
  .alert-ok  { background:rgba(0,185,107,.1); border:1px solid rgba(0,185,107,.25); color:var(--g); border-radius:12px; padding:12px 16px; margin-bottom:20px; font-size:.88rem; }
  .alert-err ul { margin:4px 0 0 16px; }
  .btn-reg { background:var(--g); color:#fff; border:none; border-radius:12px; padding:13px; width:100%; font-weight:800; font-size:1rem; cursor:pointer; transition:.2s; font-family:'Inter',sans-serif; }
  .btn-reg:hover { background:#00a85e; transform:translateY(-1px); box-shadow:0 8px 28px rgba(0,185,107,.4); }
  a { color:var(--g); text-decoration:none; font-weight:600; }
  a:hover { text-decoration:underline; }
  hr { border-color:var(--border); margin:24px 0; }
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
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-icon"><i class="bi bi-person-plus-fill"></i></div>
      <h4 style="font-family:'Sora',sans-serif;font-weight:800;color:#fff;margin-bottom:4px;">Créer un compte</h4>
      <p style="color:var(--muted);font-size:.88rem;margin:0;">Rejoignez Intercity237 et réservez votre prochain voyage</p>
    </div>

    <div class="auth-body">
      <?php if ($success): ?>
      <div class="alert-ok"><i class="bi bi-check-circle-fill me-2"></i><?= $success ?></div>
      <?php endif; ?>

      <?php if ($errors): ?>
      <div class="alert-err">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <ul style="margin:4px 0 0 0;padding-left:20px;">
          <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="row g-3">
          <div class="col-12 mb-1">
            <label>Nom complet</label>
            <div class="input-wrap">
              <i class="bi bi-person input-icon"></i>
              <input type="text" name="full_name" placeholder="Jean Paul Mbida" value="<?= h($_POST['full_name'] ?? '') ?>" required>
            </div>
          </div>
          <div class="col-md-6 mb-1">
            <label>Email</label>
            <div class="input-wrap">
              <i class="bi bi-envelope input-icon"></i>
              <input type="email" name="email" placeholder="you@email.com" value="<?= h($_POST['email'] ?? '') ?>" required>
            </div>
          </div>
          <div class="col-md-6 mb-1">
            <label>Téléphone</label>
            <div class="input-wrap">
              <i class="bi bi-telephone input-icon"></i>
              <input type="tel" name="phone" placeholder="+237 6XX XXX XXX" value="<?= h($_POST['phone'] ?? '') ?>" required>
            </div>
          </div>
          <div class="col-12 mb-1">
            <label>Nom d'utilisateur</label>
            <div class="input-wrap">
              <i class="bi bi-at input-icon"></i>
              <input type="text" name="username" placeholder="jeanpaul237" value="<?= h($_POST['username'] ?? '') ?>" required>
            </div>
          </div>
          <div class="col-md-6 mb-1">
            <label>Mot de passe</label>
            <div class="input-wrap">
              <i class="bi bi-lock input-icon"></i>
              <input type="password" name="password" id="pw1" placeholder="Min 8 caractères" required>
              <button type="button" class="pw-toggle" onclick="togglePw('pw1')"><i class="bi bi-eye"></i></button>
            </div>
          </div>
          <div class="col-md-6 mb-1">
            <label>Confirmer le mot de passe</label>
            <div class="input-wrap">
              <i class="bi bi-lock-fill input-icon"></i>
              <input type="password" name="confirm_password" id="pw2" placeholder="Répéter" required>
              <button type="button" class="pw-toggle" onclick="togglePw('pw2')"><i class="bi bi-eye"></i></button>
            </div>
          </div>
        </div>

        <button type="submit" class="btn-reg mt-4">
          <i class="bi bi-person-check-fill me-2"></i>Créer mon compte
        </button>
      </form>

      <hr>
      <p style="text-align:center;font-size:.88rem;color:var(--muted);margin:0;">
        Déjà inscrit ? <a href="<?= BASE_URL ?>/login.php">Se connecter</a>
      </p>
    </div>
  </div>
</div>

<script>
function togglePw(id) {
  const el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
