<?php
require_once __DIR__ . '/config/db.php';

$token = trim($_GET['token'] ?? '');
if (!$token) { header('Location: /'); exit; }

$stmt = $pdo->prepare("
    SELECT t.*, b.reference, b.passenger_name, b.passenger_phone,
           b.amount, b.payment_method, b.paid_at, b.status AS booking_status,
           s.departure_at, s.arrival_at,
           c1.name AS origin_name, c2.name AS destination_name,
           o.name AS operator_name, bus.model AS bus_model
    FROM tickets t
    JOIN bookings b   ON t.booking_id = b.id
    JOIN schedules s  ON b.schedule_id = s.id
    JOIN routes r     ON s.route_id = r.id
    JOIN cities c1    ON r.origin_id = c1.id
    JOIN cities c2    ON r.destination_id = c2.id
    JOIN buses bus    ON s.bus_id = bus.id
    JOIN operators o  ON bus.operator_id = o.id
    WHERE t.qr_token = :token
");
$stmt->execute([':token' => $token]);
$ticket = $stmt->fetch();
if (!$ticket) { http_response_code(404); die('Ticket introuvable.'); }

$qr_content = urlencode(APP_URL . '/scan.php?token=' . $token);
$qr_url     = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={$qr_content}&bgcolor=111827&color=00b96b&margin=10";

$dep  = new DateTime($ticket['departure_at']);
$arr  = new DateTime($ticket['arrival_at']);
$diff = $dep->diff($arr);
$dur  = $diff->h.'h'.($diff->i>0?$diff->i.'min':'');
$payLabel = ['mobile_money'=>'Mobile Money','cash'=>'Espèces','card'=>'Carte'][$ticket['payment_method']] ?? $ticket['payment_method'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ticket <?= htmlspecialchars($ticket['reference']) ?> — Intercity237</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@700;800&family=JetBrains+Mono:wght@600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root { --g:#00b96b; --dark:#0a0f1e; --card:#111827; --card2:#161f2e; --border:#1e293b; --muted:#64748b; --text:#e2e8f0; }
*,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }
body { background:var(--dark); color:var(--text); font-family:'Inter',sans-serif; min-height:100vh; display:flex; align-items:flex-start; justify-content:center; padding:32px 16px; }

.page-wrap { width:100%; max-width:600px; }

/* ── Success Banner ── */
.success-banner {
  background:rgba(0,185,107,.08); border:1px solid rgba(0,185,107,.2);
  border-radius:16px; padding:18px 24px; margin-bottom:24px;
  display:flex; align-items:center; gap:14px;
}
.success-icon { width:44px;height:44px;background:var(--g);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff;flex-shrink:0; }

/* ── Ticket ── */
.ticket {
  background:var(--card); border-radius:24px; overflow:visible;
  box-shadow:0 40px 100px rgba(0,0,0,.7);
  position:relative;
}

/* Header gradient */
.ticket-header {
  background:url('https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80') center/cover no-repeat;
  padding:28px 32px;
  border-radius:24px 24px 0 0;
  position:relative;
  overflow:hidden;
}
.ticket-header::after {
  content:''; position:absolute; inset:0;
  background:linear-gradient(135deg,rgba(6,24,16,.92) 0%,rgba(10,22,40,.88) 100%);
}
.ticket-header::before {
  content:'';position:absolute;
  width:300px;height:300px;border-radius:50%;
  background:radial-gradient(circle,rgba(0,185,107,.15) 0%,transparent 70%);
  top:-100px;right:-60px;
  z-index:1;
}
.ticket-brand { position:relative;z-index:2;display:flex;align-items:center;gap:9px;margin-bottom:24px; }
.brand-dot { width:34px;height:34px;background:var(--g);border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.9rem;color:#fff; }
.brand-name { font-family:'Sora',sans-serif;font-size:1.1rem;font-weight:800;color:#fff; }
.brand-name em { color:var(--g);font-style:normal; }
.confirmed-badge { background:rgba(0,185,107,.15);border:1px solid rgba(0,185,107,.3);color:var(--g);border-radius:20px;padding:4px 14px;font-size:.73rem;font-weight:700;margin-left:auto; }

.route-display { position:relative;z-index:2; }
.city-big { font-family:'Sora',sans-serif;font-size:1.8rem;font-weight:800;color:#fff; }
.city-time { color:var(--g);font-size:1.1rem;font-weight:700;margin-top:4px; }
.city-date { color:var(--muted);font-size:.78rem;margin-top:2px; }
.route-mid { flex:1; display:flex;flex-direction:column;align-items:center;gap:4px;padding:0 12px; }
.route-progress { width:100%;height:2px;background:linear-gradient(90deg,var(--g),rgba(0,185,107,.2));border-radius:2px;position:relative; }
.route-progress::before,.route-progress::after { content:'';position:absolute;top:50%;transform:translateY(-50%);width:8px;height:8px;border-radius:50%;background:var(--g); }
.route-progress::before { left:0; }
.route-progress::after { right:0; }
.route-dur { color:var(--muted);font-size:.72rem;margin-top:4px; }

/* Tear line */
.tear-line {
  position:relative;margin:0;height:0;
  border-top:2px dashed var(--border);
}
.tear-circle { position:absolute;top:50%;width:24px;height:24px;border-radius:50%;background:var(--dark);transform:translateY(-50%); }
.tear-circle.left { left:-12px; }
.tear-circle.right { right:-12px; }

/* Body */
.ticket-body { padding:28px 32px; }
.info-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.info-item label { display:block;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.09em;color:var(--muted);margin-bottom:4px; }
.info-item span { font-size:.93rem;font-weight:600;color:var(--text); }

/* QR Footer */
.ticket-footer {
  background:var(--card2); padding:24px 32px;
  border-radius:0 0 24px 24px;
  display:flex; align-items:center; gap:24px;
}
.qr-box { background:var(--card);border:2px solid var(--border);border-radius:16px;padding:12px;flex-shrink:0; }
.qr-box img { display:block;border-radius:8px; }
.ref-badge { font-family:'JetBrains Mono',monospace;font-size:1.1rem;font-weight:700;color:var(--g);background:rgba(0,185,107,.08);border:1px solid rgba(0,185,107,.15);border-radius:10px;padding:9px 16px;display:inline-block;margin-bottom:8px; }
.scan-hint { color:var(--muted);font-size:.75rem;margin-top:4px; }

/* Actions */
.actions { display:flex;gap:12px;margin-top:20px;flex-wrap:wrap; }
.btn-g { background:var(--g);color:#fff;border:none;border-radius:12px;padding:11px 24px;font-weight:700;font-size:.9rem;font-family:'Inter',sans-serif;cursor:pointer;transition:.2s;text-decoration:none;display:inline-flex;align-items:center;gap:7px; }
.btn-g:hover { background:#00d47e;color:#fff;transform:translateY(-1px);box-shadow:0 8px 24px rgba(0,185,107,.4); }
.btn-outline { background:rgba(255,255,255,.05);border:1px solid var(--border);color:var(--text);border-radius:12px;padding:11px 24px;font-weight:600;font-size:.9rem;font-family:'Inter',sans-serif;cursor:pointer;transition:.2s;text-decoration:none;display:inline-flex;align-items:center;gap:7px; }
.btn-outline:hover { background:rgba(255,255,255,.1);color:#fff; }

@media print {
  body { background:#fff;padding:0; }
  .no-print { display:none !important; }
  .success-banner { display:none; }
  .actions { display:none; }
  .ticket { box-shadow:none; }
}
::-webkit-scrollbar { width:5px; } ::-webkit-scrollbar-track { background:var(--dark); } ::-webkit-scrollbar-thumb { background:var(--border);border-radius:3px; }
</style>
</head>
<body>
<div class="page-wrap">

  <!-- Success banner -->
  <div class="success-banner no-print">
    <div class="success-icon"><i class="bi bi-check-lg"></i></div>
    <div>
      <div style="font-weight:700;color:#fff;margin-bottom:3px;">Réservation confirmée !</div>
      <div style="color:var(--muted);font-size:.83rem;">
        Ticket envoyé par SMS au <?= htmlspecialchars($ticket['passenger_phone']) ?>
      </div>
    </div>
    <div style="margin-left:auto;text-align:right;flex-shrink:0;">
      <div style="color:var(--g);font-weight:800;font-size:1rem;"><?= number_format($ticket['amount'],0,',',' ') ?> FCFA</div>
      <div style="color:var(--muted);font-size:.73rem;"><?= $payLabel ?></div>
    </div>
  </div>

  <!-- Ticket -->
  <div class="ticket">

    <!-- Header -->
    <div class="ticket-header">
      <div class="ticket-brand">
        <div class="brand-dot"><i class="bi bi-bus-front-fill"></i></div>
        <span class="brand-name">Intercity<em>237</em></span>
        <span class="confirmed-badge"><i class="bi bi-check-circle me-1"></i>Confirmé</span>
      </div>

      <div class="route-display d-flex align-items-center gap-3">
        <div>
          <div class="city-big"><?= htmlspecialchars($ticket['origin_name']) ?></div>
          <div class="city-time"><?= $dep->format('H:i') ?></div>
          <div class="city-date"><?= $dep->format('d M Y') ?></div>
        </div>
        <div class="route-mid">
          <div class="route-progress"></div>
          <div class="route-dur"><i class="bi bi-clock me-1"></i><?= $dur ?></div>
        </div>
        <div class="text-end">
          <div class="city-big"><?= htmlspecialchars($ticket['destination_name']) ?></div>
          <div class="city-time"><?= $arr->format('H:i') ?></div>
          <div class="city-date"><?= $arr->format('d M Y') ?></div>
        </div>
      </div>
    </div>

    <!-- Tear line -->
    <div class="tear-line">
      <div class="tear-circle left"></div>
      <div class="tear-circle right"></div>
    </div>

    <!-- Body -->
    <div class="ticket-body">
      <div class="info-grid">
        <div class="info-item">
          <label>Passager</label>
          <span><?= htmlspecialchars($ticket['passenger_name']) ?></span>
        </div>
        <div class="info-item">
          <label>Téléphone</label>
          <span><?= htmlspecialchars($ticket['passenger_phone']) ?></span>
        </div>
        <div class="info-item">
          <label>Opérateur</label>
          <span><?= htmlspecialchars($ticket['operator_name']) ?></span>
        </div>
        <div class="info-item">
          <label>Bus</label>
          <span><?= htmlspecialchars($ticket['bus_model'] ?? 'Standard') ?></span>
        </div>
        <div class="info-item">
          <label>Montant payé</label>
          <span style="color:var(--g);"><?= number_format($ticket['amount'],0,',',' ') ?> FCFA</span>
        </div>
        <div class="info-item">
          <label>Paiement</label>
          <span><?= $payLabel ?></span>
        </div>
      </div>
    </div>

    <!-- QR Footer -->
    <div class="ticket-footer">
      <div class="qr-box">
        <img src="<?= $qr_url ?>" width="140" height="140" alt="QR Code">
      </div>
      <div>
        <div class="ref-badge"><?= htmlspecialchars($ticket['reference']) ?></div>
        <p class="scan-hint">
          <i class="bi bi-qr-code me-1"></i>
          Présentez ce QR code à l'embarquement.<br>
          Valide uniquement pour ce voyage.
        </p>
      </div>
    </div>

  </div>

  <!-- Actions -->
  <div class="actions no-print mt-4">
    <button onclick="window.print()" class="btn-g">
      <i class="bi bi-printer"></i>Imprimer
    </button>
    <a href="/bookings.php" class="btn-outline">
      <i class="bi bi-ticket-perforated"></i>Mes réservations
    </a>
    <a href="/" class="btn-outline">
      <i class="bi bi-search"></i>Nouveau trajet
    </a>
  </div>

</div>
</body>
</html>
