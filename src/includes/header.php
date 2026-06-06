<?php
$current_user_name = h($_SESSION['full_name'] ?? '');
$current_role      = $_SESSION['role'] ?? '';
$user_initials     = strtoupper(substr($current_user_name, 0, 1) . (strpos($current_user_name, ' ') !== false ? substr($current_user_name, strpos($current_user_name, ' ') + 1, 1) : ''));
$current_page      = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= isset($page_title) ? h($page_title) . ' — ' : '' ?>Intercity237</title>
<meta name="description" content="Intercity237 — Réservez votre ticket de bus interurbain au Cameroun.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Sora:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>

<div id="scroll-progress"></div>

<nav class="i237-navbar" id="mainNav">
  <div class="container-fluid px-4 h-100 d-flex align-items-center">

    <a href="<?= BASE_URL ?>/index.php" class="i237-brand me-4">
      <div class="i237-brand-icon"><i class="bi bi-bus-front-fill"></i></div>
      <div>
        <div>Intercity<span>237</span></div>
        <div class="i237-tagline">Voyagez au Cameroun</div>
      </div>
    </a>

    <button class="navbar-toggler border-0 ms-auto me-2" type="button"
            data-bs-toggle="collapse" data-bs-target="#navMain" style="color:#fff;">
      <i class="bi bi-list fs-4"></i>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto gap-1">
        <li class="nav-item">
          <a class="nav-link <?= $current_page==='index.php' ? 'active':'' ?>" href="<?= BASE_URL ?>/index.php">
            <i class="bi bi-house-fill me-1"></i>Accueil
          </a>
        </li>
        <?php if (is_logged_in()): ?>
        <li class="nav-item">
          <a class="nav-link" href="http://localhost/">
            <i class="bi bi-search me-1"></i>Rechercher un trajet
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="http://localhost/bookings.php">
            <i class="bi bi-ticket-perforated me-1"></i>Mes réservations
          </a>
        </li>
        <?php endif; ?>
        <?php if (is_admin()): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= str_contains($_SERVER['PHP_SELF'],'admin')?'active':'' ?>"
             href="#" data-bs-toggle="dropdown">
            <i class="bi bi-shield-fill me-1"></i>Admin
          </a>
          <ul class="dropdown-menu i237-dropdown">
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/index.php"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/users.php"><i class="bi bi-people-fill"></i>Passagers</a></li>
            <?php if (is_superadmin()): ?>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/admins.php"><i class="bi bi-person-badge-fill"></i>Administrateurs</a></li>
            <?php endif; ?>
          </ul>
        </li>
        <?php endif; ?>
      </ul>

      <ul class="navbar-nav align-items-center gap-2">
        <?php if (is_logged_in()): ?>
          <li class="nav-item">
            <div class="nav-user-chip">
              <div class="nav-avatar"><?= h($user_initials) ?></div>
              <div>
                <div style="font-size:.82rem;font-weight:600;color:#fff;line-height:1.1"><?= $current_user_name ?></div>
                <div style="font-size:.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:.07em"><?= ucfirst($current_role) ?></div>
              </div>
            </div>
          </li>
          <li class="nav-item">
            <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm"
               style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#f87171;border-radius:8px;padding:6px 14px;font-weight:600;">
              <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a href="<?= BASE_URL ?>/login.php" class="btn btn-sm"
               style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.8);border-radius:8px;padding:6px 16px;font-weight:600;">
              <i class="bi bi-box-arrow-in-right me-1"></i>Connexion
            </a>
          </li>
          <li class="nav-item">
            <a href="<?= BASE_URL ?>/register.php" class="btn btn-sm btn-i237" style="padding:6px 16px;">
              <i class="bi bi-person-plus-fill me-1"></i>S'inscrire
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<?php if (isset($_GET['error']) && $_GET['error'] === 'access_denied'): ?>
<div class="alert alert-danger alert-dismissible fade show mx-3 mt-3 mb-0">
  <i class="bi bi-shield-exclamation me-2"></i>Accès refusé — privilèges insuffisants.
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
