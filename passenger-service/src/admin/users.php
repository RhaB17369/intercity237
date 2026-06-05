<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$msg      = '';
$msg_type = 'success';

// DELETE
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $target = $pdo->prepare("SELECT role FROM users WHERE id=?");
    $target->execute([$del_id]);
    $t = $target->fetch();
    if ($t && $t['role'] === 'passenger' && $del_id !== (int)$_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$del_id]);
        $msg = 'Passager supprimé.';
        $msg_type = 'warning';
    } else {
        $msg = 'Impossible de supprimer cet utilisateur.';
        $msg_type = 'danger';
    }
}

// Passagers avec nb de réservations
$passengers = $pdo->query("
    SELECT u.id, u.full_name, u.username, u.email, u.phone, u.created_at,
           COUNT(b.id) AS total_bookings
    FROM users u
    LEFT JOIN bookings b ON b.user_id = u.id AND b.status='confirmed'
    WHERE u.role = 'passenger'
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();

$page_title = 'Gestion des passagers';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4 px-4">

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h3 style="font-family:'Sora',sans-serif;font-weight:800;margin:0;">Passagers inscrits</h3>
      <p style="color:var(--muted);font-size:.85rem;margin-top:4px;"><?= count($passengers) ?> passager<?= count($passengers) > 1 ? 's' : '' ?> au total</p>
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show" style="border-radius:12px;">
    <?= h($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <div class="i237-table-wrap">
    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr>
            <th>#</th>
            <th>Nom complet</th>
            <th>Identifiant</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Réservations</th>
            <th>Inscrit le</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($passengers)): ?>
          <tr><td colspan="8" style="text-align:center;padding:48px;color:var(--muted);">
            <i class="bi bi-people" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>
            Aucun passager inscrit.
          </td></tr>
          <?php else: ?>
          <?php foreach ($passengers as $i => $p): ?>
          <tr>
            <td style="color:var(--muted);font-size:.82rem;"><?= $i + 1 ?></td>
            <td style="font-weight:600;"><?= h($p['full_name']) ?></td>
            <td><code style="color:var(--g);"><?= h($p['username']) ?></code></td>
            <td style="font-size:.88rem;"><?= h($p['email']) ?></td>
            <td style="font-size:.88rem;color:var(--muted);"><?= h($p['phone'] ?? '—') ?></td>
            <td>
              <span class="badge-i237"><?= $p['total_bookings'] ?> voyage<?= $p['total_bookings'] > 1 ? 's' : '' ?></span>
            </td>
            <td style="color:var(--muted);font-size:.82rem;"><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
            <td>
              <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm"
                 style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#f87171;border-radius:8px;padding:4px 10px;"
                 onclick="return confirm('Supprimer <?= h(addslashes($p['full_name'])) ?> ?')">
                <i class="bi bi-trash-fill"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
