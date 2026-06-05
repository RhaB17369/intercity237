<?php
require_once __DIR__ . '/config/db.php';
session_start();

$schedule_id = (int)($_GET['schedule'] ?? 0);
if (!$schedule_id) { header('Location: /'); exit; }

$stmt = $pdo->prepare("
    SELECT s.*, s.seats_total - s.seats_booked AS seats_available,
           r.base_price, r.distance_km, r.duration_min,
           c1.name AS origin_name, c2.name AS destination_name,
           o.name AS operator_name, b.model AS bus_model
    FROM schedules s
    JOIN routes r    ON s.route_id = r.id
    JOIN buses b     ON s.bus_id   = b.id
    JOIN operators o ON b.operator_id = o.id
    JOIN cities c1   ON r.origin_id      = c1.id
    JOIN cities c2   ON r.destination_id = c2.id
    WHERE s.id = :id AND s.status IN ('scheduled','boarding')
");
$stmt->execute([':id' => $schedule_id]);
$schedule = $stmt->fetch();
if (!$schedule) { header('Location: /'); exit; }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['passenger_name'] ?? '');
    $phone   = trim($_POST['passenger_phone'] ?? '');
    $payment = $_POST['payment_method'] ?? 'mobile_money';
    $mobile  = trim($_POST['mobile_number'] ?? '');

    if (!$name || !$phone) {
        $error = 'Nom et téléphone sont obligatoires.';
    } elseif ($schedule['seats_available'] <= 0) {
        $error = 'Plus de places disponibles.';
    } else {
        // Générer référence unique
        $ref = 'I237-' . strtoupper(substr(md5(uniqid()), 0, 8));

        // Simuler paiement (fake)
        $paid = simulate_payment($payment, $mobile, $schedule['base_price']);

        if ($paid) {
            $pdo->beginTransaction();
            try {
                // Créer la réservation
                $stmt2 = $pdo->prepare("
                    INSERT INTO bookings
                        (reference, user_id, schedule_id, passenger_name, passenger_phone,
                         amount, status, payment_method, paid_at)
                    VALUES
                        (:ref, :uid, :sid, :name, :phone, :amount, 'confirmed', :method, NOW())
                ");
                $stmt2->execute([
                    ':ref'    => $ref,
                    ':uid'    => $_SESSION['user_id'] ?? 1,
                    ':sid'    => $schedule_id,
                    ':name'   => $name,
                    ':phone'  => $phone,
                    ':amount' => $schedule['base_price'],
                    ':method' => $payment,
                ]);
                $booking_id = (int)$pdo->lastInsertId();

                // Générer le ticket QR
                $qr_token = bin2hex(random_bytes(32));
                $qr_data  = json_encode([
                    'ref'    => $ref,
                    'name'   => $name,
                    'from'   => $schedule['origin_name'],
                    'to'     => $schedule['destination_name'],
                    'dep'    => $schedule['departure_at'],
                    'token'  => $qr_token,
                ]);
                $pdo->prepare("INSERT INTO tickets (booking_id, qr_token, qr_data) VALUES (?,?,?)")
                    ->execute([$booking_id, $qr_token, $qr_data]);

                // Décrémenter les places
                $pdo->prepare("UPDATE schedules SET seats_booked = seats_booked + 1 WHERE id = ?")
                    ->execute([$schedule_id]);

                $pdo->commit();
                header("Location: /ticket.php?token={$qr_token}");
                exit;

            } catch (Throwable $e) {
                $pdo->rollBack();
                $error = 'Erreur lors de la réservation. Réessayez.';
            }
        } else {
            $error = 'Paiement refusé. Vérifiez votre numéro Mobile Money.';
        }
    }
}

function simulate_payment(string $method, string $mobile, float $amount): bool {
    // Simulation : tout passe sauf les numéros commençant par 000
    if (str_starts_with($mobile, '000')) return false;
    usleep(800000); // simuler délai bancaire
    return true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Réserver — Intercity237</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  :root { --g:#00b96b; --dark:#0a0f1e; --card:#111827; --border:#1f2937; --muted:#6b7280; }
  body { background:var(--dark); color:#e5e7eb; font-family:'Inter',sans-serif; min-height:100vh; }
  .i237-nav { background:rgba(10,15,30,.95); border-bottom:1px solid var(--border); padding:14px 0; }
  .i237-brand { font-family:'Sora',sans-serif; font-size:1.3rem; font-weight:800; color:#fff; text-decoration:none; }
  .i237-brand span { color:var(--g); }
  .checkout-card { background:var(--card); border:1px solid var(--border); border-radius:20px; padding:32px; }
  .trip-summary { background:rgba(0,185,107,.06); border:1px solid rgba(0,185,107,.2); border-radius:16px; padding:24px; }
  .city-big { font-size:1.4rem; font-weight:800; color:#fff; }
  .arrow-line { flex:1; height:2px; background:linear-gradient(90deg,var(--g),rgba(0,185,107,.2)); border-radius:2px; margin:0 12px; }
  .form-label { color:var(--muted); font-size:.8rem; font-weight:600; text-transform:uppercase; letter-spacing:.06em; }
  .form-control, .form-select {
    background:#0a0f1e !important; border:1px solid var(--border) !important;
    color:#e5e7eb !important; border-radius:12px; padding:12px 16px;
  }
  .form-control:focus, .form-select:focus { border-color:var(--g) !important; box-shadow:0 0 0 3px rgba(0,185,107,.15) !important; }
  .payment-method { background:#0a0f1e; border:2px solid var(--border); border-radius:12px; padding:16px; cursor:pointer; transition:.2s; }
  .payment-method.active { border-color:var(--g); background:rgba(0,185,107,.06); }
  .payment-method input { display:none; }
  .btn-pay { background:var(--g); color:#fff; border:none; border-radius:12px; padding:15px; font-weight:800; font-size:1.05rem; width:100%; transition:.2s; }
  .btn-pay:hover { background:#00a85e; transform:translateY(-1px); box-shadow:0 8px 28px rgba(0,185,107,.4); }
  .price-final { font-size:2rem; font-weight:900; color:var(--g); }
  .loading-overlay { display:none; position:fixed; inset:0; background:rgba(10,15,30,.9); z-index:9999; flex-direction:column; align-items:center; justify-content:center; }
  .spinner-ring { width:56px; height:56px; border:4px solid var(--border); border-top-color:var(--g); border-radius:50%; animation:spin .8s linear infinite; }
  @keyframes spin { to { transform:rotate(360deg); } }
</style>
</head>
<body>
<nav class="i237-nav">
  <div class="container"><a href="/" class="i237-brand">Intercity<span>237</span></a></div>
</nav>

<!-- Loading overlay (paiement en cours) -->
<div class="loading-overlay" id="loadingOverlay">
  <div class="spinner-ring mb-4"></div>
  <div style="color:#e5e7eb;font-weight:600;font-size:1.1rem;">Traitement du paiement...</div>
  <div style="color:var(--muted);font-size:.9rem;margin-top:8px;">Veuillez ne pas fermer cette page</div>
</div>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">

      <!-- Résumé du trajet -->
      <div class="trip-summary mb-4">
        <div class="d-flex align-items-center mb-3">
          <span style="background:rgba(0,185,107,.15);color:var(--g);font-size:.75rem;font-weight:700;padding:4px 10px;border-radius:20px;">
            <i class="bi bi-building me-1"></i><?= htmlspecialchars($schedule['operator_name']) ?>
          </span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <div>
            <div class="city-big"><?= htmlspecialchars($schedule['origin_name']) ?></div>
            <div style="color:var(--muted);font-size:.85rem;"><?= date('H:i', strtotime($schedule['departure_at'])) ?></div>
          </div>
          <div class="arrow-line"></div>
          <div class="text-end">
            <div class="city-big"><?= htmlspecialchars($schedule['destination_name']) ?></div>
            <div style="color:var(--muted);font-size:.85rem;"><?= date('H:i', strtotime($schedule['arrival_at'])) ?></div>
          </div>
        </div>
        <div class="mt-3 d-flex gap-3" style="font-size:.82rem;color:var(--muted);">
          <span><i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($schedule['departure_at'])) ?></span>
          <span><i class="bi bi-people me-1"></i><?= $schedule['seats_available'] ?> places restantes</span>
          <span><i class="bi bi-bus-front me-1"></i><?= htmlspecialchars($schedule['bus_model'] ?? 'Bus Standard') ?></span>
        </div>
      </div>

      <?php if ($error): ?>
        <div class="alert" style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#f87171;border-radius:12px;padding:16px;">
          <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" id="checkoutForm" onsubmit="showLoading()">
        <div class="checkout-card">
          <h4 style="font-family:'Sora',sans-serif;font-weight:800;margin-bottom:24px;">Informations voyageur</h4>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">Nom complet</label>
              <input type="text" name="passenger_name" class="form-control" placeholder="Jean Paul Mbida" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Téléphone</label>
              <input type="tel" name="passenger_phone" class="form-control" placeholder="+237 6XX XXX XXX" required>
            </div>
          </div>

          <h4 style="font-family:'Sora',sans-serif;font-weight:800;margin-bottom:20px;">Paiement</h4>

          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="payment-method active" id="mtn-label">
                <input type="radio" name="payment_method" value="mobile_money" checked onchange="switchPayment('mtn')">
                <div class="d-flex align-items-center gap-2">
                  <div style="width:36px;height:36px;background:#ffcc00;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.7rem;color:#000;">MTN</div>
                  <div>
                    <div style="font-weight:600;font-size:.9rem;">MTN Mobile Money</div>
                    <div style="font-size:.75rem;color:var(--muted);">Paiement instantané</div>
                  </div>
                </div>
              </label>
            </div>
            <div class="col-6">
              <label class="payment-method" id="orange-label">
                <input type="radio" name="payment_method" value="mobile_money" onchange="switchPayment('orange')">
                <div class="d-flex align-items-center gap-2">
                  <div style="width:36px;height:36px;background:#ff6600;border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.7rem;color:#fff;">OM</div>
                  <div>
                    <div style="font-weight:600;font-size:.9rem;">Orange Money</div>
                    <div style="font-size:.75rem;color:var(--muted);">Paiement instantané</div>
                  </div>
                </div>
              </label>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label">Numéro Mobile Money</label>
            <input type="tel" name="mobile_number" class="form-control" placeholder="6XX XXX XXX" required>
            <div style="font-size:.75rem;color:var(--muted);margin-top:6px;"><i class="bi bi-info-circle me-1"></i>Un code de confirmation vous sera envoyé par SMS</div>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-4" style="border-top:1px solid var(--border);padding-top:20px;">
            <div>
              <div style="color:var(--muted);font-size:.85rem;">Total à payer</div>
              <div class="price-final"><?= number_format($schedule['base_price'], 0, ',', ' ') ?> <span style="font-size:1rem;color:var(--muted);">FCFA</span></div>
            </div>
            <button type="submit" class="btn-pay" style="width:auto;padding:14px 40px;">
              <i class="bi bi-shield-check me-2"></i>Confirmer & Payer
            </button>
          </div>

          <div style="font-size:.75rem;color:var(--muted);text-align:center;">
            <i class="bi bi-lock-fill me-1"></i>Paiement sécurisé — Votre ticket sera envoyé par SMS après confirmation
          </div>
        </div>
      </form>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showLoading() {
  document.getElementById('loadingOverlay').style.display = 'flex';
}
function switchPayment(type) {
  document.getElementById('mtn-label').classList.toggle('active', type === 'mtn');
  document.getElementById('orange-label').classList.toggle('active', type === 'orange');
}
</script>
</body>
</html>
