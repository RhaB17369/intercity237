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

// QR code data (encode en base64 pour l'URL du QR)
$qr_content = urlencode(APP_URL . '/scan.php?token=' . $token);
$qr_url     = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={$qr_content}&bgcolor=111827&color=00b96b&margin=12";
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
  :root { --g:#00b96b; --dark:#0a0f1e; --card:#111827; --border:#1f2937; --muted:#6b7280; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { background:var(--dark); color:#e5e7eb; font-family:'Inter',sans-serif; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }

  .ticket-wrap { width:100%; max-width:520px; }

  /* Success banner */
  .success-banner { background:rgba(0,185,107,.1); border:1px solid rgba(0,185,107,.25); border-radius:16px; padding:20px 24px; margin-bottom:24px; display:flex; align-items:center; gap:16px; }
  .success-icon { width:48px; height:48px; background:var(--g); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; color:#fff; flex-shrink:0; }

  /* Ticket card */
  .ticket-card { background:var(--card); border-radius:24px; overflow:hidden; box-shadow:0 32px 80px rgba(0,0,0,.6); }
  .ticket-header { background:linear-gradient(135deg,#0d1f10,#0a1a0e); padding:28px 32px; border-bottom:1px solid var(--border); }
  .ticket-body { padding:28px 32px; }
  .ticket-footer { background:#0d1117; padding:24px 32px; border-top:2px dashed var(--border); display:flex; align-items:center; gap:24px; }

  /* Route display */
  .route-display { display:flex; align-items:center; gap:12px; margin-bottom:8px; }
  .city-name { font-family:'Sora',sans-serif; font-size:1.6rem; font-weight:800; color:#fff; }
  .route-arrow { flex:1; display:flex; align-items:center; gap:8px; }
  .route-line { flex:1; height:2px; background:linear-gradient(90deg,var(--g),rgba(0,185,107,.1)); }
  .route-dot { width:8px; height:8px; border-radius:50%; background:var(--g); }

  /* Info grid */
  .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:24px; }
  .info-item label { display:block; font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--muted); margin-bottom:4px; }
  .info-item span { font-size:.95rem; font-weight:600; color:#e5e7eb; }

  /* Reference */
  .ref-badge { font-family:'JetBrains Mono',monospace; font-size:1.2rem; font-weight:600; color:var(--g); background:rgba(0,185,107,.08); border:1px solid rgba(0,185,107,.2); border-radius:10px; padding:10px 16px; display:inline-block; }

  /* Status badge */
  .status-confirmed { background:rgba(0,185,107,.12); color:var(--g); border:1px solid rgba(0,185,107,.25); padding:5px 14px; border-radius:20px; font-size:.78rem; font-weight:700; }

  /* QR */
  .qr-box { background:#111827; border:2px solid var(--border); border-radius:16px; padding:16px; display:flex; align-items:center; justify-content:center; }
  .qr-box img { border-radius:8px; }

  /* Actions */
  .btn-print { background:var(--g); color:#fff; border:none; border-radius:10px; padding:11px 24px; font-weight:700; cursor:pointer; transition:.2s; }
  .btn-print:hover { background:#00a85e; }
  .btn-home { background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.12); color:#e5e7eb; border-radius:10px; padding:11px 24px; font-weight:600; text-decoration:none; transition:.2s; }
  .btn-home:hover { background:rgba(255,255,255,.12); color:#fff; }

  @media print {
    body { background:#fff; }
    .ticket-card { box-shadow:none; }
    .no-print { display:none !important; }
  }
</style>
</head>
<body>
<div class="ticket-wrap">

  <!-- Success banner -->
  <div class="success-banner no-print">
    <div class="success-icon"><i class="bi bi-check-lg"></i></div>
    <div>
      <div style="font-weight:700;font-size:1rem;color:#fff;">Réservation confirmée !</div>
      <div style="font-size:.85rem;color:var(--muted);">Votre ticket a été envoyé par SMS au <?= htmlspecialchars($ticket['passenger_phone']) ?></div>
    </div>
  </div>

  <!-- Ticket -->
  <div class="ticket-card">

    <div class="ticket-header">
      <div class="d-flex justify-content-between align-items-start mb-4">
        <div style="font-family:'Sora',sans-serif;font-size:1.1rem;font-weight:800;color:#fff;">Intercity<span style="color:var(--g)">237</span></div>
        <span class="status-confirmed"><i class="bi bi-check-circle me-1"></i>Confirmé</span>
      </div>
      <div class="route-display">
        <div>
          <div class="city-name"><?= htmlspecialchars($ticket['origin_name']) ?></div>
          <div style="color:var(--muted);font-size:.8rem;"><?= date('H:i', strtotime($ticket['departure_at'])) ?></div>
        </div>
        <div class="route-arrow">
          <div class="route-dot"></div>
          <div class="route-line"></div>
          <i class="bi bi-airplane-fill" style="color:var(--g);font-size:.9rem;"></i>
          <div class="route-line"></div>
          <div class="route-dot"></div>
        </div>
        <div class="text-end">
          <div class="city-name"><?= htmlspecialchars($ticket['destination_name']) ?></div>
          <div style="color:var(--muted);font-size:.8rem;"><?= date('H:i', strtotime($ticket['arrival_at'])) ?></div>
        </div>
      </div>
    </div>

    <div class="ticket-body">
      <div class="info-grid">
        <div class="info-item">
          <label>Passager</label>
          <span><?= htmlspecialchars($ticket['passenger_name']) ?></span>
        </div>
        <div class="info-item">
          <label>Date</label>
          <span><?= date('d/m/Y', strtotime($ticket['departure_at'])) ?></span>
        </div>
        <div class="info-item">
          <label>Opérateur</label>
          <span><?= htmlspecialchars($ticket['operator_name']) ?></span>
        </div>
        <div class="info-item">
          <label>Montant payé</label>
          <span style="color:var(--g);"><?= number_format($ticket['amount'], 0, ',', ' ') ?> FCFA</span>
        </div>
        <div class="info-item" style="grid-column:1/-1">
          <label>Référence</label>
          <div class="ref-badge"><?= htmlspecialchars($ticket['reference']) ?></div>
        </div>
      </div>
    </div>

    <div class="ticket-footer">
      <div class="qr-box">
        <img src="<?= $qr_url ?>" width="140" height="140" alt="QR Code ticket" loading="lazy">
      </div>
      <div style="flex:1;">
        <div style="font-size:.78rem;color:var(--muted);margin-bottom:8px;">Présentez ce QR code à l'agent lors de l'embarquement</div>
        <div style="font-family:'JetBrains Mono',monospace;font-size:.75rem;color:var(--g);word-break:break-all;opacity:.6;"><?= substr($ticket['qr_token'], 0, 24) ?>...</div>
        <div style="font-size:.72rem;color:var(--muted);margin-top:8px;"><i class="bi bi-shield-check me-1"></i>Ticket sécurisé et infalsifiable</div>
      </div>
    </div>
  </div>

  <!-- Actions -->
  <div class="d-flex gap-3 mt-4 no-print">
    <button class="btn-print flex-grow-1" onclick="window.print()"><i class="bi bi-printer me-2"></i>Imprimer / Télécharger</button>
    <a href="/" class="btn-home"><i class="bi bi-house me-1"></i>Accueil</a>
  </div>

</div>
</body>
</html>
