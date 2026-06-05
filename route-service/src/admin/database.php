<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$bookings = $pdo->query("
    SELECT b.reference, b.passenger_name, b.passenger_phone, b.amount,
           b.payment_method, b.status, b.created_at,
           c1.name AS origin, c2.name AS destination
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    JOIN routes r    ON s.route_id = r.id
    JOIN cities c1   ON r.origin_id = c1.id
    JOIN cities c2   ON r.destination_id = c2.id
    ORDER BY b.created_at DESC LIMIT 100
")->fetchAll();

$users = $pdo->query("
    SELECT id, full_name, username, email, phone, role, created_at
    FROM users ORDER BY created_at DESC LIMIT 50
")->fetchAll();

$page_title = 'Vue base de données';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4 px-4">
  <h3 style="font-family:'Sora',sans-serif;font-weight:800;margin-bottom:24px;">Vue base de données</h3>

  <!-- Réservations -->
  <div class="i237-table-wrap mb-4">
    <div class="p-4" style="border-bottom:1px solid var(--border);">
      <h5 style="font-family:'Sora',sans-serif;font-weight:800;margin:0;">
        <i class="bi bi-ticket-perforated-fill me-2" style="color:var(--g);"></i>
        Réservations (<?= count($bookings) ?>)
      </h5>
    </div>
    <div class="table-responsive">
      <table class="table mb-0">
        <thead><tr>
          <th>Référence</th><th>Passager</th><th>Téléphone</th>
          <th>Trajet</th><th>Montant</th><th>Paiement</th><th>Statut</th><th>Date</th>
        </tr></thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
          <tr>
            <td><code style="color:var(--g);"><?= h($b['reference']) ?></code></td>
            <td style="font-weight:600;"><?= h($b['passenger_name']) ?></td>
            <td style="color:var(--muted);font-size:.85rem;"><?= h($b['passenger_phone']) ?></td>
            <td><?= h($b['origin']) ?> → <?= h($b['destination']) ?></td>
            <td style="color:var(--g);font-weight:700;"><?= number_format((float)$b['amount'],0,',',' ') ?> FCFA</td>
            <td style="font-size:.82rem;"><?= $b['payment_method']==='mobile_money'?'MTN MoMo':'Orange Money' ?></td>
            <td><span class="badge-i237"><?= h($b['status']) ?></span></td>
            <td style="color:var(--muted);font-size:.82rem;"><?= date('d/m/Y H:i', strtotime($b['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Utilisateurs -->
  <div class="i237-table-wrap">
    <div class="p-4" style="border-bottom:1px solid var(--border);">
      <h5 style="font-family:'Sora',sans-serif;font-weight:800;margin:0;">
        <i class="bi bi-people-fill me-2" style="color:var(--g);"></i>
        Utilisateurs (<?= count($users) ?>)
      </h5>
    </div>
    <div class="table-responsive">
      <table class="table mb-0">
        <thead><tr><th>Nom</th><th>Username</th><th>Email</th><th>Téléphone</th><th>Rôle</th><th>Inscrit le</th></tr></thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td style="font-weight:600;"><?= h($u['full_name']) ?></td>
            <td><code style="color:var(--g);"><?= h($u['username']) ?></code></td>
            <td style="font-size:.88rem;"><?= h($u['email']) ?></td>
            <td style="font-size:.85rem;color:var(--muted);"><?= h($u['phone'] ?? '—') ?></td>
            <td>
              <?php $rc = ['superadmin'=>'badge-role-admin','admin'=>'badge-role-agent','passenger'=>'badge-role-passenger','agent'=>'badge-role-agent'][$u['role']] ?? 'badge-i237'; ?>
              <span class="badge-i237 <?= $rc ?>"><?= ucfirst($u['role']) ?></span>
            </td>
            <td style="color:var(--muted);font-size:.82rem;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
