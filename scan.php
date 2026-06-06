<?php
require_once __DIR__ . '/config/db.php';

$token  = trim($_GET['token'] ?? '');
$result = null;
$error  = '';

if ($token) {
    $stmt = $pdo->prepare("
        SELECT t.id, t.scanned_at,
               b.reference, b.passenger_name, b.passenger_phone,
               b.status AS booking_status, b.amount,
               s.departure_at, s.arrival_at,
               c1.name AS origin, c2.name AS destination,
               o.name AS operator
        FROM tickets t
        JOIN bookings b  ON t.booking_id = b.id
        JOIN schedules s ON b.schedule_id = s.id
        JOIN routes r    ON s.route_id = r.id
        JOIN cities c1   ON r.origin_id = c1.id
        JOIN cities c2   ON r.destination_id = c2.id
        JOIN buses bus   ON s.bus_id = bus.id
        JOIN operators o ON bus.operator_id = o.id
        WHERE t.qr_token = :token
    ");
    $stmt->execute([':token' => $token]);
    $result = $stmt->fetch();

    if (!$result) {
        $error = 'Ticket invalide ou introuvable.';
    } elseif ($result['booking_status'] !== 'confirmed') {
        $error = 'Réservation non confirmée.';
    } elseif ($result['scanned_at'] !== null) {
        $error = 'Ce ticket a déjà été scanné le ' . date('d/m/Y à H:i', strtotime($result['scanned_at']));
    } else {
        // Mark as scanned
        $pdo->prepare("UPDATE tickets SET scanned_at=NOW() WHERE qr_token=?")
            ->execute([$token]);
        $result['scanned_at'] = date('Y-m-d H:i:s');
    }
}

$valid = $result && !$error;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Scan Ticket — Intercity237</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Sora:wght@800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  :root { --g:#00b96b; --dark:#0a0f1e; --card:#111827; --border:#1f2937; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { background:var(--dark); color:#e5e7eb; font-family:'Inter',sans-serif; min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:24px; }
  .scan-card { background:var(--card); border-radius:20px; padding:32px; width:100%; max-width:420px; text-align:center; border:2px solid; }
  .scan-card.valid   { border-color:var(--g); }
  .scan-card.invalid { border-color:#ef4444; }
  .scan-card.empty   { border-color:var(--border); }
  .icon-circle { width:72px; height:72px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:2rem; margin:0 auto 20px; }
  .icon-valid   { background:rgba(0,185,107,.15); color:var(--g); }
  .icon-invalid { background:rgba(239,68,68,.15); color:#ef4444; }
  .icon-empty   { background:rgba(255,255,255,.06); color:#6b7280; }
  h2 { font-family:'Sora',sans-serif; font-size:1.4rem; font-weight:800; margin-bottom:8px; }
  .info-row { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border); font-size:.9rem; }
  .info-row:last-child { border-bottom:none; }
  .info-label { color:#6b7280; }
  .info-val { font-weight:600; text-align:right; }
  .brand { font-family:'Sora',sans-serif; font-size:1rem; font-weight:800; margin-bottom:32px; }
  .brand span { color:var(--g); }
  .badge-ok { background:rgba(0,185,107,.1); color:var(--g); border:1px solid rgba(0,185,107,.2); padding:4px 12px; border-radius:20px; font-size:.8rem; font-weight:700; }
  form { margin-top:32px; }
  form input { background:#0a0f1e; border:1px solid var(--border); color:#e5e7eb; border-radius:10px; padding:12px 16px; width:100%; font-size:.95rem; margin-bottom:12px; }
  form input:focus { outline:none; border-color:var(--g); }
  form button { background:var(--g); color:#fff; border:none; border-radius:10px; padding:12px; width:100%; font-weight:700; cursor:pointer; }
  form button:hover { background:#00a85e; }
</style>
</head>
<body>

<div class="brand">Intercity<span>237</span> — Agent Scan</div>

<?php if (!$token): ?>
  <div class="scan-card empty">
    <div class="icon-circle icon-empty"><i class="bi bi-qr-code-scan"></i></div>
    <h2>Scanner un ticket</h2>
    <p style="color:#6b7280;margin-bottom:24px;font-size:.9rem;">Entrez manuellement le token ou scannez le QR code.</p>
    <form method="GET">
      <input type="text" name="token" placeholder="Token du ticket..." autofocus required>
      <button type="submit"><i class="bi bi-search me-2"></i>Vérifier</button>
    </form>
  </div>

<?php elseif ($valid): ?>
  <div class="scan-card valid">
    <div class="icon-circle icon-valid"><i class="bi bi-check-lg"></i></div>
    <h2 style="color:var(--g);">Ticket Valide</h2>
    <p style="color:#6b7280;font-size:.85rem;margin-bottom:20px;"><span class="badge-ok"><i class="bi bi-shield-check me-1"></i>Embarquement autorisé</span></p>
    <div>
      <div class="info-row"><span class="info-label">Passager</span><span class="info-val"><?= htmlspecialchars($result['passenger_name']) ?></span></div>
      <div class="info-row"><span class="info-label">Référence</span><span class="info-val" style="font-family:monospace;color:var(--g);"><?= htmlspecialchars($result['reference']) ?></span></div>
      <div class="info-row"><span class="info-label">Trajet</span><span class="info-val"><?= htmlspecialchars($result['origin']) ?> → <?= htmlspecialchars($result['destination']) ?></span></div>
      <div class="info-row"><span class="info-label">Départ</span><span class="info-val"><?= date('d/m/Y H:i', strtotime($result['departure_at'])) ?></span></div>
      <div class="info-row"><span class="info-label">Opérateur</span><span class="info-val"><?= htmlspecialchars($result['operator']) ?></span></div>
      <div class="info-row"><span class="info-label">Montant</span><span class="info-val" style="color:var(--g);"><?= number_format($result['amount'], 0, ',', ' ') ?> FCFA</span></div>
    </div>
  </div>

<?php else: ?>
  <div class="scan-card invalid">
    <div class="icon-circle icon-invalid"><i class="bi bi-x-lg"></i></div>
    <h2 style="color:#ef4444;">Ticket Invalide</h2>
    <p style="color:#f87171;margin-bottom:20px;font-size:.9rem;"><?= htmlspecialchars($error) ?></p>
    <a href="/scan.php" style="color:#6b7280;font-size:.85rem;text-decoration:none;"><i class="bi bi-arrow-left me-1"></i>Scanner un autre ticket</a>
  </div>

<?php endif; ?>

</body>
</html>
