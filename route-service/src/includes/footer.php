<footer class="i237-footer">
  <div class="i237-footer-main">
    <div class="container">
      <div class="row g-4">

        <div class="col-lg-4">
          <div class="footer-brand">Intercity<span>237</span></div>
          <p class="footer-desc">
            La plateforme de réservation de tickets de bus interurbains au Cameroun.
            Réservez en ligne, payez par Mobile Money, voyagez sans file d'attente.
          </p>
          <div class="d-flex gap-2 mt-3 flex-wrap">
            <span class="badge-i237"><i class="bi bi-shield-check me-1"></i>Paiement sécurisé</span>
            <span class="badge-i237"><i class="bi bi-qr-code me-1"></i>Ticket QR Code</span>
          </div>
        </div>

        <div class="col-sm-6 col-lg-2">
          <div class="footer-heading">Navigation</div>
          <ul class="footer-links">
            <li><a href="<?= BASE_URL ?>/index.php"><i class="bi bi-house-fill"></i>Accueil</a></li>
            <li><a href="http://localhost/"><i class="bi bi-search"></i>Rechercher un trajet</a></li>
            <?php if (is_logged_in()): ?>
            <li><a href="http://localhost/bookings.php"><i class="bi bi-ticket-perforated"></i>Mes réservations</a></li>
            <li><a href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right"></i>Déconnexion</a></li>
            <?php else: ?>
            <li><a href="<?= BASE_URL ?>/login.php"><i class="bi bi-box-arrow-in-right"></i>Connexion</a></li>
            <li><a href="<?= BASE_URL ?>/register.php"><i class="bi bi-person-plus"></i>Inscription</a></li>
            <?php endif; ?>
          </ul>
        </div>

        <div class="col-sm-6 col-lg-3">
          <div class="footer-heading">Trajets populaires</div>
          <ul class="footer-links">
            <li><a href="http://localhost/"><i class="bi bi-arrow-right-circle"></i>Yaoundé → Douala</a></li>
            <li><a href="http://localhost/"><i class="bi bi-arrow-right-circle"></i>Douala → Bafoussam</a></li>
            <li><a href="http://localhost/"><i class="bi bi-arrow-right-circle"></i>Yaoundé → Bafoussam</a></li>
            <li><a href="http://localhost/"><i class="bi bi-arrow-right-circle"></i>Douala → Bamenda</a></li>
            <li><a href="http://localhost/"><i class="bi bi-arrow-right-circle"></i>Yaoundé → Ngaoundéré</a></li>
          </ul>
        </div>

        <div class="col-lg-3">
          <div class="footer-heading">Contact</div>
          <ul class="footer-links">
            <li><a href="#"><i class="bi bi-geo-alt-fill"></i>Yaoundé, Cameroun</a></li>
            <li><a href="tel:+237600000000"><i class="bi bi-telephone-fill"></i>+237 6XX XXX XXX</a></li>
            <li><a href="mailto:support@intercity237.cm"><i class="bi bi-envelope-fill"></i>support@intercity237.cm</a></li>
            <li><a href="#"><i class="bi bi-globe2"></i>www.intercity237.cm</a></li>
          </ul>
          <div class="mt-3">
            <div style="font-size:.75rem;color:var(--muted);margin-bottom:8px;">Paiement accepté</div>
            <div class="d-flex gap-2">
              <div style="background:#ffcc00;color:#000;border-radius:6px;padding:3px 10px;font-size:.72rem;font-weight:900;">MTN MoMo</div>
              <div style="background:#ff6600;color:#fff;border-radius:6px;padding:3px 10px;font-size:.72rem;font-weight:900;">Orange Money</div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
      <p>&copy; <?= date('Y') ?> Intercity237 — Tous droits réservés.</p>
      <p>SEN3244 Software Architecture — Engr. TEKOH PALMA — ICT University</p>
      <div class="d-flex align-items-center gap-2" style="font-size:.72rem;color:var(--muted);">
        <div style="width:6px;height:6px;border-radius:50%;background:var(--g);"></div>
        Système en ligne
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/js/main.js"></script>
</body>
</html>
