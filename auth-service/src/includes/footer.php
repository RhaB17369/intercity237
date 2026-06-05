
<footer class="cim-footer">
    <div class="cim-footer-deco"></div>

    <!-- Top section -->
    <div class="cim-footer-top">
        <div class="container">
            <div class="row g-5">

                <!-- Brand column -->
                <div class="col-lg-4">
                    <div class="cim-footer-brand">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="logo-icon">
                                <i class="bi bi-buildings-fill text-white"></i>
                            </div>
                            <div>
                                <div class="brand-name">Intercity237</div>
                                <div class="brand-sub">Cimenteries du Cameroun</div>
                            </div>
                        </div>
                        <div class="cim-footer-divider"></div>
                        <p>
                            Leading cement producer in Cameroon since 1963.
                            Our HR portal centralises employee management
                            across all departments and production sites.
                        </p>
                        <div class="d-flex gap-2 mt-4 flex-wrap">
                            <div class="cim-footer-badge">
                                <i class="bi bi-shield-check-fill"></i>
                                ISO 9001 Certified
                            </div>
                            <div class="cim-footer-badge">
                                <i class="bi bi-globe2"></i>
                                HeidelbergCement
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick links -->
                <div class="col-sm-6 col-lg-2 cim-footer-col">
                    <h6>Navigation</h6>
                    <ul class="cim-footer-links">
                        <li><a href="<?= BASE_URL ?>/index.php"><i class="bi bi-house-fill"></i>Home</a></li>
                        <?php if (is_logged_in()): ?>
                        <li><a href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right"></i>Logout</a></li>
                        <?php else: ?>
                        <li><a href="<?= BASE_URL ?>/login.php"><i class="bi bi-box-arrow-in-right"></i>Employee Login</a></li>
                        <li><a href="<?= BASE_URL ?>/register.php"><i class="bi bi-person-plus"></i>Register</a></li>
                        <li><a href="<?= BASE_URL ?>/forgot_password.php"><i class="bi bi-key"></i>Forgot Password</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Departments -->
                <div class="col-sm-6 col-lg-3 cim-footer-col">
                    <h6>Departments</h6>
                    <ul class="cim-footer-links">
                        <?php
                        $footer_depts = get_departments($pdo);
                        foreach (array_slice($footer_depts, 0, 6) as $fd):
                        ?>
                        <li>
                            <a href="<?= is_logged_in() ? BASE_URL.'/department.php?id='.$fd['id'] : BASE_URL.'/login.php' ?>">
                                <i class="bi bi-diagram-3"></i><?= h($fd['name']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Contact -->
                <div class="col-lg-3 cim-footer-col">
                    <h6>Contact &amp; Sites</h6>

                    <div class="cim-contact-item">
                        <div class="cim-contact-icon"><i class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            Zone Industrielle de Bonabéri<br>
                            BP&nbsp;1170, Douala, Cameroun
                        </div>
                    </div>

                    <div class="cim-contact-item">
                        <div class="cim-contact-icon"><i class="bi bi-telephone-fill"></i></div>
                        <div>+237 233 400 000</div>
                    </div>

                    <div class="cim-contact-item">
                        <div class="cim-contact-icon"><i class="bi bi-envelope-fill"></i></div>
                        <div>hr@intercity237.cm</div>
                    </div>

                    <div class="cim-contact-item">
                        <div class="cim-contact-icon"><i class="bi bi-globe2"></i></div>
                        <div>www.intercity237.cm</div>
                    </div>

                    <!-- Sites indicator -->
                    <div class="mt-3 d-flex gap-3 flex-wrap">
                        <?php foreach (['Douala','Figuil','Yaoundé'] as $site): ?>
                        <div class="d-flex align-items-center gap-1" style="font-size:0.75rem;color:rgba(255,255,255,0.4)">
                            <div style="width:6px;height:6px;border-radius:50%;background:var(--cim-orange)"></div>
                            <?= $site ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Bottom bar -->
    <div class="cim-footer-bottom">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <p>&copy; <?= date('Y') ?> Intercity237 &mdash; All rights reserved.</p>
                <p class="text-center">
                    BSC-SE3230 &mdash; Web Application Development &mdash; Engr. Tanwi Nkiamboh
                </p>
                <div class="d-flex align-items-center gap-2" style="font-size:0.72rem;color:rgba(255,255,255,0.2)">
                    <div style="width:6px;height:6px;border-radius:50%;background:#10b981"></div>
                    System Online
                </div>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
<script src="<?= BASE_URL ?>/js/main.js"></script>
</body>
</html>
