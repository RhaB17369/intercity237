<?php
require_once __DIR__ . '/config/db.php';
session_start();

$origin_id      = (int)($_GET['origin'] ?? 0);
$destination_id = (int)($_GET['destination'] ?? 0);
$travel_date    = $_GET['date'] ?? date('Y-m-d');

$cities = $pdo->query("SELECT id, name FROM cities ORDER BY name")->fetchAll();

$schedules = [];
if ($origin_id && $destination_id) {
    $stmt = $pdo->prepare("
        SELECT s.id, s.departure_at, s.arrival_at,
               s.seats_total, s.seats_booked,
               (s.seats_total - s.seats_booked) AS seats_available,
               r.base_price,
               o.name AS operator_name,
               b.model AS bus_model, b.capacity,
               c1.name AS origin_name,
               c2.name AS destination_name
        FROM schedules s
        JOIN routes r   ON s.route_id   = r.id
        JOIN buses b    ON s.bus_id     = b.id
        JOIN operators o ON b.operator_id = o.id
        JOIN cities c1  ON r.origin_id      = c1.id
        JOIN cities c2  ON r.destination_id = c2.id
        WHERE r.origin_id      = :origin
          AND r.destination_id = :dest
          AND DATE(s.departure_at) = :date
          AND s.status IN ('scheduled','boarding')
          AND (s.seats_total - s.seats_booked) > 0
        ORDER BY s.departure_at
    ");
    $stmt->execute([':origin' => $origin_id, ':dest' => $destination_id, ':date' => $travel_date]);
    $schedules = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Rechercher un trajet — Intercity237</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Sora:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  :root {
    --i237-green: #00b96b;
    --i237-dark:  #0a0f1e;
    --i237-card:  #111827;
    --i237-border:#1f2937;
    --i237-text:  #e5e7eb;
    --i237-muted: #6b7280;
  }
  * { box-sizing: border-box; }
  body { background: var(--i237-dark); color: var(--i237-text); font-family: 'Inter', sans-serif; min-height: 100vh; }

  /* ── Navbar ── */
  .i237-nav { background: rgba(10,15,30,.95); border-bottom: 1px solid var(--i237-border); backdrop-filter: blur(12px); padding: 14px 0; position: sticky; top: 0; z-index: 100; }
  .i237-brand { font-family: 'Sora', sans-serif; font-size: 1.35rem; font-weight: 800; color: #fff; text-decoration: none; letter-spacing: -.02em; }
  .i237-brand span { color: var(--i237-green); }

  /* ── Hero search ── */
  .search-hero { background: linear-gradient(135deg, #0a0f1e 0%, #0d1a2e 50%, #0a1628 100%); padding: 72px 0 60px; position: relative; overflow: hidden; }
  .search-hero::before { content: ''; position: absolute; inset: 0; background: radial-gradient(ellipse 70% 60% at 50% 0%, rgba(0,185,107,.12) 0%, transparent 70%); }
  .search-hero h1 { font-family: 'Sora', sans-serif; font-size: clamp(2rem,5vw,3.2rem); font-weight: 800; color: #fff; letter-spacing: -.03em; }
  .search-hero h1 span { color: var(--i237-green); }
  .search-hero p { color: var(--i237-muted); font-size: 1.05rem; }

  /* ── Search card ── */
  .search-card { background: var(--i237-card); border: 1px solid var(--i237-border); border-radius: 20px; padding: 32px; margin-top: -20px; position: relative; z-index: 2; box-shadow: 0 24px 64px rgba(0,0,0,.5); }
  .search-card select, .search-card input[type="date"] {
    background: #0a0f1e; border: 1px solid var(--i237-border); color: var(--i237-text);
    border-radius: 12px; padding: 12px 16px; width: 100%; font-size: .95rem;
    transition: border-color .2s;
  }
  .search-card select:focus, .search-card input:focus { border-color: var(--i237-green); outline: none; box-shadow: 0 0 0 3px rgba(0,185,107,.15); }
  .search-card label { color: var(--i237-muted); font-size: .8rem; font-weight: 600; text-transform: uppercase; letter-spacing: .07em; margin-bottom: 8px; display: block; }
  .btn-search { background: var(--i237-green); color: #fff; border: none; border-radius: 12px; padding: 13px 32px; font-weight: 700; font-size: 1rem; transition: all .2s; width: 100%; }
  .btn-search:hover { background: #00a85e; transform: translateY(-1px); box-shadow: 0 8px 24px rgba(0,185,107,.35); }

  /* ── Route card ── */
  .route-card { background: var(--i237-card); border: 1px solid var(--i237-border); border-radius: 16px; padding: 24px; margin-bottom: 16px; transition: border-color .2s, transform .2s; }
  .route-card:hover { border-color: var(--i237-green); transform: translateY(-2px); }
  .operator-badge { background: rgba(0,185,107,.12); color: var(--i237-green); font-size: .75rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; display: inline-flex; align-items: center; gap: 5px; }
  .time-big { font-size: 1.8rem; font-weight: 800; color: #fff; letter-spacing: -.03em; }
  .time-sep { color: var(--i237-muted); font-size: .8rem; text-align: center; }
  .duration-line { width: 100%; height: 2px; background: linear-gradient(90deg, var(--i237-green), rgba(0,185,107,.2)); border-radius: 2px; margin: 8px 0; position: relative; }
  .duration-line::after { content: attr(data-dur); position: absolute; top: -9px; left: 50%; transform: translateX(-50%); background: var(--i237-card); color: var(--i237-muted); font-size: .72rem; padding: 0 8px; white-space: nowrap; }
  .price-tag { font-size: 1.6rem; font-weight: 800; color: var(--i237-green); }
  .seats-badge { background: rgba(255,255,255,.06); border-radius: 8px; padding: 6px 12px; font-size: .8rem; color: var(--i237-muted); }
  .seats-badge.low { background: rgba(239,68,68,.1); color: #f87171; }
  .btn-book { background: var(--i237-green); color: #fff; border: none; border-radius: 10px; padding: 11px 28px; font-weight: 700; transition: all .2s; white-space: nowrap; }
  .btn-book:hover { background: #00a85e; box-shadow: 0 6px 20px rgba(0,185,107,.3); }
  .empty-state { text-align: center; padding: 72px 24px; }
  .empty-state i { font-size: 3.5rem; color: var(--i237-border); }
  .empty-state h3 { color: var(--i237-text); margin-top: 16px; }
  .empty-state p { color: var(--i237-muted); }

  /* scrollbar */
  ::-webkit-scrollbar { width: 6px; } ::-webkit-scrollbar-track { background: var(--i237-dark); } ::-webkit-scrollbar-thumb { background: var(--i237-border); border-radius: 3px; }
</style>
</head>
<body>

<nav class="i237-nav">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="/" class="i237-brand">Intercity<span>237</span></a>
    <div class="d-flex gap-2">
      <?php if (!empty($_SESSION['user_id'])): ?>
        <a href="/bookings.php" class="btn btn-sm" style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#fff;border-radius:8px;">
          <i class="bi bi-ticket-perforated me-1"></i>Mes réservations
        </a>
        <a href="/logout.php" class="btn btn-sm" style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#fff;border-radius:8px;">
          <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
        </a>
      <?php else: ?>
        <a href="/login.php" class="btn btn-sm" style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#fff;border-radius:8px;">
          <i class="bi bi-person me-1"></i>Connexion
        </a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="search-hero">
  <div class="container position-relative">
    <div class="row justify-content-center text-center mb-5">
      <div class="col-lg-7">
        <h1>Voyagez partout au<br><span>Cameroun</span></h1>
        <p class="mt-3">Réservez votre ticket de bus en ligne. Rapide, sûr, sans file d'attente.</p>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-lg-9">
        <div class="search-card">
          <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
              <label><i class="bi bi-geo-alt me-1"></i>Départ</label>
              <select name="origin" required>
                <option value="">Choisir une ville...</option>
                <?php foreach ($cities as $c): ?>
                  <option value="<?= $c['id'] ?>" <?= $origin_id == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label><i class="bi bi-geo-alt-fill me-1"></i>Arrivée</label>
              <select name="destination" required>
                <option value="">Choisir une ville...</option>
                <?php foreach ($cities as $c): ?>
                  <option value="<?= $c['id'] ?>" <?= $destination_id == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <label><i class="bi bi-calendar3 me-1"></i>Date</label>
              <input type="date" name="date" value="<?= htmlspecialchars($travel_date) ?>" min="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn-search"><i class="bi bi-search me-2"></i>Chercher</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="container py-5">
  <?php if ($origin_id && $destination_id): ?>
    <div class="d-flex align-items-center gap-3 mb-4">
      <h2 class="mb-0" style="font-family:'Sora',sans-serif;font-weight:800;font-size:1.3rem;">
        <?php
          $oName = '';
          $dName = '';
          foreach ($cities as $c) {
              if ($c['id'] == $origin_id)      $oName = $c['name'];
              if ($c['id'] == $destination_id) $dName = $c['name'];
          }
          echo htmlspecialchars($oName) . ' <i class="bi bi-arrow-right text-success"></i> ' . htmlspecialchars($dName);
        ?>
      </h2>
      <span class="operator-badge"><?= count($schedules) ?> voyage<?= count($schedules) > 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($schedules)): ?>
      <div class="empty-state">
        <i class="bi bi-bus-front"></i>
        <h3>Aucun voyage disponible</h3>
        <p>Essayez une autre date ou un autre trajet.</p>
      </div>
    <?php else: ?>
      <?php foreach ($schedules as $s): ?>
        <div class="route-card">
          <div class="row align-items-center g-3">
            <div class="col-auto">
              <div class="operator-badge"><i class="bi bi-building"></i><?= htmlspecialchars($s['operator_name']) ?></div>
              <div class="mt-1" style="font-size:.75rem;color:var(--i237-muted);"><?= htmlspecialchars($s['bus_model'] ?? 'Bus Standard') ?></div>
            </div>
            <div class="col">
              <div class="d-flex align-items-center gap-3">
                <div class="text-center">
                  <div class="time-big"><?= date('H:i', strtotime($s['departure_at'])) ?></div>
                  <div style="font-size:.78rem;color:var(--i237-muted);"><?= htmlspecialchars($s['origin_name']) ?></div>
                </div>
                <div class="flex-grow-1">
                  <div class="duration-line" data-dur="<?php
                    $dep = new DateTime($s['departure_at']);
                    $arr = new DateTime($s['arrival_at']);
                    $diff = $dep->diff($arr);
                    echo $diff->h . 'h' . ($diff->i > 0 ? $diff->i . 'min' : '');
                  ?>"></div>
                </div>
                <div class="text-center">
                  <div class="time-big"><?= date('H:i', strtotime($s['arrival_at'])) ?></div>
                  <div style="font-size:.78rem;color:var(--i237-muted);"><?= htmlspecialchars($s['destination_name']) ?></div>
                </div>
              </div>
            </div>
            <div class="col-auto text-center">
              <div class="price-tag"><?= number_format($s['base_price'], 0, ',', ' ') ?> <span style="font-size:.9rem;color:var(--i237-muted);">FCFA</span></div>
              <div class="seats-badge mt-1 <?= $s['seats_available'] <= 10 ? 'low' : '' ?>">
                <i class="bi bi-people me-1"></i><?= $s['seats_available'] ?> places
              </div>
            </div>
            <div class="col-auto">
              <a href="/checkout.php?schedule=<?= $s['id'] ?>" class="btn-book">
                <i class="bi bi-ticket-perforated me-1"></i>Réserver
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  <?php else: ?>
    <div class="empty-state">
      <i class="bi bi-map" style="font-size:3.5rem;color:var(--i237-border)"></i>
      <h3 style="margin-top:16px;">Recherchez votre trajet</h3>
      <p style="color:var(--i237-muted);">Sélectionnez votre ville de départ, d'arrivée et la date.</p>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
