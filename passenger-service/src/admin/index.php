<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

// Stats Intercity237
$stats = [
    'bookings_today' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at)=CURDATE() AND status='confirmed'")->fetchColumn(),
    'bookings_total' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetchColumn(),
    'revenue_today'  => $pdo->query("SELECT COALESCE(SUM(amount),0) FROM bookings WHERE DATE(created_at)=CURDATE() AND status='confirmed'")->fetchColumn(),
    'passengers'     => $pdo->query("SELECT COUNT(*) FROM users WHERE role='passenger'")->fetchColumn(),
    'routes'         => $pdo->query("SELECT COUNT(*) FROM routes")->fetchColumn(),
    'schedules_today'=> $pdo->query("SELECT COUNT(*) FROM schedules WHERE DATE(departure_at)=CURDATE() AND status='scheduled'")->fetchColumn(),
];

$recent_bookings = $pdo->query("
    SELECT b.reference, b.passenger_name, b.amount, b.payment_method, b.created_at,
           c1.name AS origin, c2.name AS destination, o.name AS operator
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    JOIN routes r    ON s.route_id = r.id
    JOIN cities c1   ON r.origin_id = c1.id
    JOIN cities c2   ON r.destination_id = c2.id
    JOIN buses bus   ON s.bus_id = bus.id
    JOIN operators o ON bus.operator_id = o.id
    WHERE b.status = 'confirmed'
    ORDER BY b.created_at DESC LIMIT 10
")->fetchAll();

$page_title = 'Dashboard Admin';
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4 px-4">

  <!-- Header -->
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h3 style="font-family:'Sora',sans-serif;font-weight:800;margin:0;">Dashboard Admin</h3>
      <p style="color:var(--muted);font-size:.88rem;margin:4px 0 0;">Bienvenue, <?= h($_SESSION['full_name']) ?> —
        <?= date('d/m/Y') ?></p>
    </div>
    <span class="badge-i237 <?= is_superadmin() ? '' : 'badge-role-agent' ?>" style="font-size:.82rem;padding:7px 16px;">
      <i class="bi bi-shield-fill me-1"></i><?= ucfirst($_SESSION['role']) ?>
    </span>
  </div>

  <!-- Stat cards -->
  <div class="row g-3 mb-4">
    <?php
    $cards = [
      ['label'=>'Réservations aujourd\'hui', 'val'=>$stats['bookings_today'], 'icon'=>'ticket-perforated-fill', 'bg'=>'rgba(0,185,107,.1)',  'color'=>'var(--g)'],
      ['label'=>'Chiffre d\'affaires du jour', 'val'=>number_format((float)$stats['revenue_today'],0,',',' ').' FCFA', 'icon'=>'cash-coin', 'bg'=>'rgba(99,102,241,.1)', 'color'=>'#818cf8'],
      ['label'=>'Total réservations',          'val'=>$stats['bookings_total'], 'icon'=>'collection-fill', 'bg'=>'rgba(245,158,11,.1)', 'color'=>'#fbbf24'],
      ['label'=>'Passagers inscrits',           'val'=>$stats['passengers'],    'icon'=>'people-fill',     'bg'=>'rgba(239,68,68,.1)',  'color'=>'#f87171'],
      ['label'=>'Routes actives',               'val'=>$stats['routes'],        'icon'=>'map-fill',        'bg'=>'rgba(59,130,246,.1)',  'color'=>'#60a5fa'],
      ['label'=>'Départs aujourd\'hui',         'val'=>$stats['schedules_today'],'icon'=>'bus-front-fill', 'bg'=>'rgba(0,185,107,.1)',  'color'=>'var(--g)'],
    ];
    foreach ($cards as $c): ?>
    <div class="col-sm-6 col-xl-4 col-xxl-2">
      <div class="stat-card">
        <div class="d-flex align-items-center gap-3">
          <div class="stat-icon" style="background:<?= $c['bg'] ?>;color:<?= $c['color'] ?>;">
            <i class="bi bi-<?= $c['icon'] ?>"></i>
          </div>
          <div>
            <div style="font-size:1.5rem;font-weight:800;line-height:1;color:#fff;"><?= $c['val'] ?></div>
            <div style="font-size:.75rem;color:var(--muted);margin-top:3px;"><?= $c['label'] ?></div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Quick actions -->
  <div class="row g-3 mb-4">
    <?php
    $actions = [
      ['href'=>BASE_URL.'/admin/users.php',   'icon'=>'people-fill',          'label'=>'Passagers',     'color'=>'var(--g)'],
      ['href'=>BASE_URL.'/admin/admins.php',  'icon'=>'person-badge-fill',    'label'=>'Admins',        'color'=>'#818cf8'],
      ['href'=>'http://localhost/api/routes', 'icon'=>'map-fill',             'label'=>'Routes (API)',  'color'=>'#60a5fa'],
      ['href'=>'http://localhost/scan.php',   'icon'=>'qr-code-scan',         'label'=>'Scanner ticket','color'=>'#fbbf24'],
    ];
    foreach ($actions as $a): ?>
    <div class="col-6 col-md-3">
      <a href="<?= $a['href'] ?>" class="i237-card d-flex flex-column align-items-center justify-content-center py-4 text-decoration-none">
        <i class="bi bi-<?= $a['icon'] ?>" style="font-size:1.8rem;color:<?= $a['color'] ?>;margin-bottom:8px;"></i>
        <span style="font-size:.85rem;font-weight:600;color:#fff;"><?= $a['label'] ?></span>
      </a>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Recent bookings -->
  <div class="i237-table-wrap">
    <div class="p-4 d-flex align-items-center justify-content-between" style="border-bottom:1px solid var(--border);">
      <h5 style="font-family:'Sora',sans-serif;font-weight:800;margin:0;">
        <i class="bi bi-clock-history me-2" style="color:var(--g);"></i>Dernières réservations
      </h5>
      <span class="badge-i237"><?= count($recent_bookings) ?> affichées</span>
    </div>
    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr>
            <th>Référence</th>
            <th>Passager</th>
            <th>Trajet</th>
            <th>Opérateur</th>
            <th>Montant</th>
            <th>Paiement</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recent_bookings)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:40px;">
            <i class="bi bi-inbox" style="font-size:2.5rem;display:block;margin-bottom:8px;"></i>Aucune réservation pour le moment
          </td></tr>
          <?php else: ?>
          <?php foreach ($recent_bookings as $b): ?>
          <tr>
            <td><code><?= h($b['reference']) ?></code></td>
            <td style="font-weight:600;"><?= h($b['passenger_name']) ?></td>
            <td><?= h($b['origin']) ?> <i class="bi bi-arrow-right" style="color:var(--muted);font-size:.8rem;"></i> <?= h($b['destination']) ?></td>
            <td style="color:var(--muted);font-size:.85rem;"><?= h($b['operator']) ?></td>
            <td style="color:var(--g);font-weight:700;"><?= number_format((float)$b['amount'],0,',',' ') ?> FCFA</td>
            <td>
              <span style="background:<?= $b['payment_method']==='mobile_money'?'rgba(255,204,0,.12)':'rgba(255,102,0,.12)' ?>;color:<?= $b['payment_method']==='mobile_money'?'#fbbf24':'#fb923c' ?>;border-radius:6px;padding:3px 8px;font-size:.75rem;font-weight:700;">
                <?= $b['payment_method'] === 'mobile_money' ? 'MTN MoMo' : 'Orange Money' ?>
              </span>
            </td>
            <td style="color:var(--muted);font-size:.82rem;"><?= date('d/m/Y H:i', strtotime($b['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
