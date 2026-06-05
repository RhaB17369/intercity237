<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = 'Espace passager';

// Stats rapides si admin
$stats = [];
if (is_admin()) {
    $stats['passengers']  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='passenger'")->fetchColumn();
    $stats['bookings']    = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetchColumn();
    $stats['today']       = $pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at)=CURDATE() AND status='confirmed'")->fetchColumn();
}

include __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section style="background:linear-gradient(135deg,#0a0f1e,#061a0e);padding:80px 0 64px;position:relative;overflow:hidden;">
  <div style="position:absolute;inset:0;background:radial-gradient(ellipse 70% 60% at 50% 0%,rgba(0,185,107,.1) 0%,transparent 70%);"></div>
  <div class="container position-relative">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="badge-i237 mb-3" style="display:inline-flex;"><i class="bi bi-bus-front-fill me-2"></i>Plateforme de réservation</div>
        <h1 style="font-size:clamp(2rem,5vw,3rem);color:#fff;margin-bottom:16px;">
          Voyagez partout<br>au <span style="color:var(--g);">Cameroun</span>
        </h1>
        <p style="color:var(--muted);font-size:1.05rem;margin-bottom:28px;line-height:1.7;">
          Réservez votre ticket de bus en ligne. Paiement Mobile Money, ticket QR instantané, sans file d'attente.
        </p>
        <div class="d-flex gap-3 flex-wrap">
          <a href="http://localhost/" class="btn-i237 btn" style="padding:13px 28px;border-radius:12px;font-size:1rem;">
            <i class="bi bi-search me-2"></i>Rechercher un trajet
          </a>
          <?php if (!is_logged_in()): ?>
          <a href="<?= BASE_URL ?>/register.php" class="btn-i237-outline btn" style="padding:13px 28px;border-radius:12px;font-size:1rem;">
            <i class="bi bi-person-plus me-2"></i>Créer un compte
          </a>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="row g-3">
          <?php
          $features = [
            ['icon'=>'ticket-perforated-fill', 'title'=>'Réservation instantanée', 'desc'=>'Choisissez votre trajet et payez en moins de 2 minutes'],
            ['icon'=>'phone-fill',             'title'=>'MTN & Orange Money',       'desc'=>'Paiement Mobile Money sécurisé, aucune carte requise'],
            ['icon'=>'qr-code',                'title'=>'Ticket QR Code',           'desc'=>'Présentez votre QR à l\'agent lors de l\'embarquement'],
            ['icon'=>'map-fill',               'title'=>'10 villes desservies',     'desc'=>'Yaoundé, Douala, Bafoussam, Bamenda et plus'],
          ];
          foreach ($features as $f): ?>
          <div class="col-6">
            <div class="i237-card p-3">
              <div style="width:40px;height:40px;background:rgba(0,185,107,.12);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--g);margin-bottom:10px;">
                <i class="bi bi-<?= $f['icon'] ?>"></i>
              </div>
              <div style="font-weight:700;font-size:.9rem;color:#fff;margin-bottom:4px;"><?= $f['title'] ?></div>
              <div style="font-size:.78rem;color:var(--muted);"><?= $f['desc'] ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Admin stats (si admin connecté) -->
<?php if (is_admin() && !empty($stats)): ?>
<section class="container py-4">
  <div class="row g-3">
    <div class="col-12 mb-2">
      <div class="d-flex align-items-center justify-content-between">
        <h5 style="font-family:'Sora',sans-serif;font-weight:800;margin:0;">Vue d'ensemble</h5>
        <a href="<?= BASE_URL ?>/admin/index.php" class="btn-i237-outline btn btn-sm" style="border-radius:8px;">
          <i class="bi bi-speedometer2 me-1"></i>Dashboard complet
        </a>
      </div>
    </div>
    <?php
    $sc = [
      ['label'=>'Passagers inscrits',   'val'=>$stats['passengers'],  'icon'=>'people-fill',          'c'=>'var(--g)'],
      ['label'=>'Total réservations',   'val'=>$stats['bookings'],    'icon'=>'ticket-perforated-fill','c'=>'#818cf8'],
      ['label'=>'Réservations du jour', 'val'=>$stats['today'],       'icon'=>'calendar-check-fill',  'c'=>'#fbbf24'],
    ];
    foreach ($sc as $c): ?>
    <div class="col-sm-4">
      <div class="stat-card">
        <div class="d-flex align-items-center gap-3">
          <div class="stat-icon" style="background:rgba(255,255,255,.05);color:<?= $c['c'] ?>;">
            <i class="bi bi-<?= $c['icon'] ?>"></i>
          </div>
          <div>
            <div style="font-size:1.8rem;font-weight:800;color:#fff;line-height:1;"><?= $c['val'] ?></div>
            <div style="font-size:.78rem;color:var(--muted);"><?= $c['label'] ?></div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- Opérateurs partenaires -->
<section class="container py-5">
  <h2 style="text-align:center;font-family:'Sora',sans-serif;font-size:1.4rem;margin-bottom:8px;">Opérateurs partenaires</h2>
  <p style="text-align:center;color:var(--muted);margin-bottom:32px;font-size:.9rem;">Les meilleures compagnies de bus du Cameroun</p>
  <div class="row g-3 justify-content-center">
    <?php
    $operators = $pdo->query("SELECT name, description FROM operators ORDER BY name")->fetchAll();
    foreach ($operators as $op): ?>
    <div class="col-sm-6 col-md-3">
      <div class="i237-card i237-card-hover p-4 text-center">
        <div style="width:52px;height:52px;background:rgba(0,185,107,.1);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:1.4rem;color:var(--g);">
          <i class="bi bi-bus-front"></i>
        </div>
        <div style="font-weight:700;color:#fff;margin-bottom:4px;"><?= h($op['name']) ?></div>
        <div style="font-size:.78rem;color:var(--muted);"><?= h($op['description'] ?? '') ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
