<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
$page_title = 'Home';

$total_employees = $pdo->query("SELECT COUNT(*) FROM users WHERE role='employee'")->fetchColumn();
$total_admins    = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN('admin','superadmin')")->fetchColumn();
$total_depts     = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
$total_records   = $pdo->query("SELECT COUNT(*) FROM department_records")->fetchColumn();

/* Hero page: no top padding — navbar overlays the hero */
$extra_body_class = 'hero-page';

include __DIR__ . '/includes/header.php';
// Inject hero-page class via JS (see main.js)
?>

<!-- ================================================================
     HERO — Full Viewport Cinematic
     ================================================================ -->
<section class="cim-hero" id="hero">
    <!-- Parallax image layer -->
    <div class="hero-bg-img" id="heroBg"></div>
    <!-- Dark gradient overlay -->
    <div class="hero-overlay"></div>
    <!-- Grid texture -->
    <div class="hero-grid"></div>

    <div class="container">
        <div class="row align-items-center" style="min-height:100vh; padding:130px 0 90px;">

            <!-- ---- LEFT: Content ---- -->
            <div class="col-lg-6 col-xl-5">

                <div class="cim-hero-badge reveal">
                    <span class="badge-dot"></span>
                    Since 1963 &mdash; Building Cameroon
                </div>

                <h1 class="reveal reveal-d1">
                    Portail RH<br>
                    <span class="gradient-text">Intercity237</span>
                </h1>

                <p class="hero-desc reveal reveal-d2">
                    Cimenteries du Cameroun &mdash; plateforme centralisée de gestion
                    des ressources humaines pour tous les départements et sites.
                </p>

                <?php if (!is_logged_in()): ?>
                <div class="d-flex gap-3 flex-wrap mt-4 reveal reveal-d3">
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-cim btn-lg px-4">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Employee Login
                    </a>
                    <a href="<?= BASE_URL ?>/register.php"
                       class="btn btn-lg px-4 fw-semibold"
                       style="background:rgba(255,255,255,0.08);border:1.5px solid rgba(255,255,255,0.2);color:white;border-radius:10px;">
                        <i class="bi bi-person-plus me-2"></i>Register
                    </a>
                </div>
                <?php else: ?>
                <div class="d-flex gap-3 flex-wrap mt-4 reveal reveal-d3">
                    <?php if (is_admin()): ?>
                    <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-cim btn-lg px-4">
                        <i class="bi bi-speedometer2 me-2"></i>Admin Dashboard
                    </a>
                    <?php else: ?>
                    <a href="<?= BASE_URL ?>/department.php?id=<?= (int)($_SESSION['department_id'] ?? 1) ?>"
                       class="btn btn-cim btn-lg px-4">
                        <i class="bi bi-diagram-3 me-2"></i>My Department
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Trust badges -->
                <div class="d-flex align-items-center gap-4 mt-5 reveal reveal-d4">
                    <div style="color:rgba(255,255,255,0.35);font-size:0.72rem;letter-spacing:0.1em;text-transform:uppercase">Trusted by</div>
                    <div style="width:1px;height:20px;background:rgba(255,255,255,0.12)"></div>
                    <div style="color:rgba(255,255,255,0.5);font-size:0.8rem;font-weight:500">HeidelbergCement Group</div>
                    <div style="width:1px;height:20px;background:rgba(255,255,255,0.12)"></div>
                    <div style="color:rgba(255,255,255,0.5);font-size:0.8rem;font-weight:500">ISO&nbsp;9001</div>
                </div>
            </div>

            <!-- ---- RIGHT: Floating stat card ---- -->
            <div class="col-lg-5 offset-lg-1 reveal reveal-right reveal-d2">
                <div class="hero-stat-card">

                    <div class="hero-stat-row">
                        <div class="hero-stat-icon"
                             style="background:rgba(232,93,4,0.12);border:1px solid rgba(232,93,4,0.2);color:var(--cim-orange)">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div>
                            <div class="hero-stat-num" data-count="<?= (int)$total_employees ?>"><?= (int)$total_employees ?></div>
                            <div class="hero-stat-lbl">Registered Employees</div>
                        </div>
                        <div class="ms-auto">
                            <span style="background:rgba(5,150,105,0.15);color:#34d399;border-radius:50px;padding:3px 10px;font-size:0.72rem;font-weight:600">Active</span>
                        </div>
                    </div>

                    <div class="hero-stat-row">
                        <div class="hero-stat-icon"
                             style="background:rgba(37,99,235,0.12);border:1px solid rgba(37,99,235,0.2);color:#60a5fa">
                            <i class="bi bi-diagram-3-fill"></i>
                        </div>
                        <div>
                            <div class="hero-stat-num" data-count="<?= (int)$total_depts ?>"><?= (int)$total_depts ?></div>
                            <div class="hero-stat-lbl">Departments</div>
                        </div>
                        <div class="ms-auto">
                            <span style="background:rgba(37,99,235,0.12);color:#60a5fa;border-radius:50px;padding:3px 10px;font-size:0.72rem;font-weight:600">Managed</span>
                        </div>
                    </div>

                    <div class="hero-stat-row">
                        <div class="hero-stat-icon"
                             style="background:rgba(139,92,246,0.12);border:1px solid rgba(139,92,246,0.2);color:#a78bfa">
                            <i class="bi bi-file-earmark-text-fill"></i>
                        </div>
                        <div>
                            <div class="hero-stat-num" data-count="<?= (int)$total_records ?>"><?= (int)$total_records ?></div>
                            <div class="hero-stat-lbl">HR Records</div>
                        </div>
                        <div class="ms-auto">
                            <span style="background:rgba(139,92,246,0.12);color:#a78bfa;border-radius:50px;padding:3px 10px;font-size:0.72rem;font-weight:600">Centralised</span>
                        </div>
                    </div>

                    <div class="hero-stat-row">
                        <div class="hero-stat-icon"
                             style="background:rgba(245,158,11,0.12);border:1px solid rgba(245,158,11,0.2);color:#fbbf24">
                            <i class="bi bi-shield-check-fill"></i>
                        </div>
                        <div>
                            <div class="hero-stat-num" data-count="<?= (int)$total_admins ?>"><?= (int)$total_admins ?></div>
                            <div class="hero-stat-lbl">System Admins</div>
                        </div>
                        <div class="ms-auto">
                            <span style="background:rgba(245,158,11,0.12);color:#fbbf24;border-radius:50px;padding:3px 10px;font-size:0.72rem;font-weight:600">Secured</span>
                        </div>
                    </div>

                    <div class="mt-4 pt-2" style="border-top:1px solid rgba(255,255,255,0.06)">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width:8px;height:8px;border-radius:50%;background:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,0.2);animation:pulse-dot 2s ease infinite"></div>
                            <span style="color:rgba(255,255,255,0.45);font-size:0.78rem">System operational &mdash; All departments online</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scroll indicator -->
    <div class="scroll-indicator">
        <div class="scroll-wheel"></div>
        <span>Scroll</span>
    </div>
</section>


<!-- ================================================================
     STATS SECTION — Dark Animated Counters
     ================================================================ -->
<section class="stats-section">
    <div class="container">
        <div class="row g-4">
            <?php
            $stats = [
                ['val' => 60,  'suffix' => '+', 'label' => 'Years of Excellence',  'icon' => 'bi-award-fill'],
                ['val' => (int)$total_employees, 'suffix' => '', 'label' => 'HR Records Managed', 'icon' => 'bi-people-fill'],
                ['val' => 3,   'suffix' => '',  'label' => 'Production Sites',     'icon' => 'bi-geo-alt-fill'],
                ['val' => (int)$total_depts, 'suffix' => '', 'label' => 'Active Departments',   'icon' => 'bi-diagram-3-fill'],
            ];
            foreach ($stats as $i => $s): ?>
            <div class="col-sm-6 col-lg-3">
                <div class="stat-counter-wrap reveal reveal-d<?= $i + 1 ?>">
                    <div class="stat-counter-icon">
                        <i class="bi <?= $s['icon'] ?>"></i>
                    </div>
                    <div class="stat-counter-number">
                        <span class="js-counter" data-target="<?= $s['val'] ?>">0</span>
                        <span class="stat-suffix"><?= $s['suffix'] ?></span>
                    </div>
                    <div class="stat-counter-label"><?= $s['label'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ================================================================
     ABOUT SECTION — Split layout with professional image
     ================================================================ -->
<section class="about-section">
    <div class="container">
        <div class="row align-items-center g-5">

            <!-- Image side -->
            <div class="col-lg-6 reveal reveal-left">
                <div class="about-img-wrap">
                    <img
                        src="https://images.unsplash.com/photo-1565193566173-7a0ee3dbe261?auto=format&fit=crop&w=900&q=80"
                        alt="Intercity237 Industrial Plant"
                        loading="lazy"
                        onerror="this.style.display='none';this.parentElement.style.background='linear-gradient(135deg,#1a1a24,#252535)'"
                    >
                    <!-- Floating info badge -->
                    <div class="about-img-badge">
                        <i class="bi bi-buildings-fill fs-3" style="color:var(--cim-orange)"></i>
                        <div>
                            <div class="big-num">1963</div>
                            <div class="sub-txt">Founded<br>in Cameroon</div>
                        </div>
                    </div>
                    <div class="about-img-corner"></div>
                </div>
            </div>

            <!-- Content side -->
            <div class="col-lg-6 reveal reveal-right">
                <span class="section-eyebrow">About Intercity237</span>
                <h2 class="fw-bold mb-0" style="font-size:clamp(1.8rem,3.5vw,2.8rem);letter-spacing:-0.02em;line-height:1.1">
                    Cameroon's Leading<br>Cement Producer
                </h2>
                <div class="divider-line mt-3 mb-4"></div>

                <p class="text-muted" style="font-size:1rem;line-height:1.8">
                    Cimenteries du Cameroun (Intercity237) has been the backbone of Cameroon's
                    construction industry since 1963. As a subsidiary of the <strong>HeidelbergCement
                    Group</strong>, we operate three state-of-the-art production facilities across the country.
                </p>

                <div class="mt-4">
                    <div class="about-feat-item">
                        <div class="about-feat-icon"><i class="bi bi-shield-check-fill"></i></div>
                        <div>
                            <div class="fw-semibold" style="font-size:0.9rem">ISO 9001 Certified Quality</div>
                            <div class="text-muted small mt-1">Internationally recognised quality management standards across all operations.</div>
                        </div>
                    </div>
                    <div class="about-feat-item">
                        <div class="about-feat-icon"><i class="bi bi-globe2"></i></div>
                        <div>
                            <div class="fw-semibold" style="font-size:0.9rem">HeidelbergCement Group</div>
                            <div class="text-muted small mt-1">Part of one of the world's largest building materials companies, operating in 50+ countries.</div>
                        </div>
                    </div>
                    <div class="about-feat-item">
                        <div class="about-feat-icon"><i class="bi bi-people-fill"></i></div>
                        <div>
                            <div class="fw-semibold" style="font-size:0.9rem">Committed to our People</div>
                            <div class="text-muted small mt-1">This HR portal centralises employee management, payroll records, and department reporting.</div>
                        </div>
                    </div>
                    <div class="about-feat-item">
                        <div class="about-feat-icon"><i class="bi bi-leaf-fill"></i></div>
                        <div>
                            <div class="fw-semibold" style="font-size:0.9rem">Sustainable Development</div>
                            <div class="text-muted small mt-1">Investing in sustainable production practices and local community development since our founding.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ================================================================
     FEATURES SECTION — Dark Portal Capabilities
     ================================================================ -->
<section class="features-section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-eyebrow">Portal Features</span>
            <h2 class="section-title-dark mt-1">Everything Your HR Team Needs</h2>
            <p style="color:rgba(255,255,255,0.38);font-size:0.95rem;max-width:460px;margin:12px auto 0;line-height:1.7">
                A complete platform designed for efficiency, security, and full departmental visibility.
            </p>
        </div>

        <div class="row g-4">
            <?php
            $features = [
                ['icon'=>'bi-person-lines-fill', 'title'=>'Employee Records',        'desc'=>'Store and manage complete HR dossiers for every employee — position, salary, status, contact details and more.'],
                ['icon'=>'bi-diagram-3-fill',    'title'=>'Department Management',   'desc'=>'Organise your workforce across 10 departments with role-based access control and dedicated views.'],
                ['icon'=>'bi-shield-fill',        'title'=>'Role-Based Security',     'desc'=>'Superadmin, admin, and employee roles with granular permissions — employees can only access their own data.'],
                ['icon'=>'bi-file-earmark-pdf-fill','title'=>'PDF Report Export',    'desc'=>'Generate professional HR reports in one click. Export any department\'s records as a formatted PDF.'],
                ['icon'=>'bi-bar-chart-fill',     'title'=>'Real-Time Statistics',   'desc'=>'Instant dashboards showing employee counts, department records, and system-wide metrics at a glance.'],
                ['icon'=>'bi-lock-fill',           'title'=>'Secure Authentication', 'desc'=>'CSRF protection, password hashing (bcrypt), session management, and access control on every route.'],
            ];
            foreach ($features as $i => $f): ?>
            <div class="col-sm-6 col-lg-4">
                <div class="feature-card reveal reveal-d<?= ($i % 3) + 1 ?>">
                    <div class="feature-icon"><i class="bi <?= $f['icon'] ?>"></i></div>
                    <div class="feature-title"><?= $f['title'] ?></div>
                    <p class="feature-desc mb-0"><?= $f['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ================================================================
     DEPARTMENTS GRID
     ================================================================ -->
<section class="page-section" style="background:var(--cim-bg)">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-eyebrow">Organisation</span>
            <h2 class="fw-bold" style="letter-spacing:-0.02em;font-size:clamp(1.8rem,3.5vw,2.6rem)">
                Our <?= (int)$total_depts ?> Departments
            </h2>
            <p class="text-muted mt-2" style="max-width:440px;margin:0 auto;font-size:0.95rem">
                Select your department to view and manage HR records
            </p>
            <div class="divider-line mx-auto mt-3"></div>
        </div>

        <div class="row g-3">
            <?php
            $dept_icons = [
                'bi-gear-fill','bi-check2-circle','bi-truck','bi-cash-stack',
                'bi-people-fill','bi-cpu-fill','bi-bar-chart-fill',
                'bi-box-seam','bi-wrench-adjustable','bi-building'
            ];
            $depts = get_departments($pdo);
            foreach ($depts as $i => $dept):
                $can_access = is_admin() || ($_SESSION['department_id'] ?? null) == $dept['id'];
                $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM department_records WHERE department_id=?");
                $stmt2->execute([$dept['id']]);
                $recs = $stmt2->fetchColumn();
            ?>
            <div class="col-sm-6 col-md-4 col-xl-3 reveal reveal-d<?= ($i % 4) + 1 ?>">
                <?php if ($can_access && is_logged_in()): ?>
                <a href="<?= BASE_URL ?>/department.php?id=<?= $dept['id'] ?>" class="dept-card h-100">
                    <i class="bi <?= $dept_icons[$i % count($dept_icons)] ?> dept-icon"></i>
                    <div class="fw-semibold" style="font-size:0.88rem"><?= h($dept['name']) ?></div>
                    <div class="text-muted small mt-1"><?= $recs ?> record<?= $recs != 1 ? 's' : '' ?></div>
                </a>
                <?php else: ?>
                <div class="dept-card h-100 opacity-40" style="cursor:default;pointer-events:none">
                    <i class="bi <?= $dept_icons[$i % count($dept_icons)] ?> dept-icon" style="color:#aaa"></i>
                    <div class="fw-semibold" style="font-size:0.88rem"><?= h($dept['name']) ?></div>
                    <div class="mt-2">
                        <?php if (!is_logged_in()): ?>
                        <span class="badge bg-secondary small">Login required</span>
                        <?php else: ?>
                        <span class="badge bg-secondary small"><i class="bi bi-lock me-1"></i>Restricted</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ================================================================
     LOCATIONS SECTION
     ================================================================ -->
<section class="locations-section">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-eyebrow">Our Sites</span>
            <h2 class="fw-bold" style="letter-spacing:-0.02em;font-size:clamp(1.8rem,3.5vw,2.6rem)">
                3 Production Sites Across Cameroon
            </h2>
            <div class="divider-line mx-auto mt-3"></div>
        </div>

        <div class="row g-4">
            <!-- Douala -->
            <div class="col-md-4 reveal reveal-d1">
                <div class="location-card">
                    <div class="location-img-wrap">
                        <img
                            src="https://images.unsplash.com/photo-1581093458791-9f3c3900df4b?auto=format&fit=crop&w=600&q=80"
                            alt="Douala Industrial Plant"
                            loading="lazy"
                            onerror="this.style.background='linear-gradient(135deg,#1a1a24,#e85d04 200%)';this.style.height='100%'"
                        >
                        <div class="location-img-overlay"></div>
                        <div class="location-img-tag">Headquarters</div>
                    </div>
                    <div class="location-body">
                        <div class="location-name"><i class="bi bi-geo-alt-fill text-cim-orange me-1"></i>Douala</div>
                        <p class="location-meta mt-2 mb-3">
                            Zone Industrielle de Bonabéri — Main production and administrative headquarters.
                            Largest cement plant with coastal shipping access.
                        </p>
                        <div class="d-flex gap-3">
                            <div style="font-size:0.78rem;color:var(--cim-text-muted)"><i class="bi bi-building me-1"></i>Main Plant</div>
                            <div style="font-size:0.78rem;color:var(--cim-text-muted)"><i class="bi bi-telephone me-1"></i>+237 233 400 000</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Figuil -->
            <div class="col-md-4 reveal reveal-d2">
                <div class="location-card">
                    <div class="location-img-wrap">
                        <img
                            src="https://images.unsplash.com/photo-1504309092620-4d0ec726efa4?auto=format&fit=crop&w=600&q=80"
                            alt="Figuil Production Site"
                            loading="lazy"
                            onerror="this.style.background='linear-gradient(135deg,#252535,#e85d04 200%)';this.style.height='100%'"
                        >
                        <div class="location-img-overlay"></div>
                        <div class="location-img-tag">Production</div>
                    </div>
                    <div class="location-body">
                        <div class="location-name"><i class="bi bi-geo-alt-fill text-cim-orange me-1"></i>Figuil</div>
                        <p class="location-meta mt-2 mb-3">
                            Northern Cameroon — Limestone extraction and primary cement production.
                            Key facility serving the northern regions and neighbouring countries.
                        </p>
                        <div class="d-flex gap-3">
                            <div style="font-size:0.78rem;color:var(--cim-text-muted)"><i class="bi bi-building me-1"></i>Clinker Plant</div>
                            <div style="font-size:0.78rem;color:var(--cim-text-muted)"><i class="bi bi-geo me-1"></i>Nord Region</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Yaoundé -->
            <div class="col-md-4 reveal reveal-d3">
                <div class="location-card">
                    <div class="location-img-wrap">
                        <img
                            src="https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=600&q=80"
                            alt="Yaoundé Office"
                            loading="lazy"
                            onerror="this.style.background='linear-gradient(135deg,#1a1a24,#e85d04 200%)';this.style.height='100%'"
                        >
                        <div class="location-img-overlay"></div>
                        <div class="location-img-tag">Regional Office</div>
                    </div>
                    <div class="location-body">
                        <div class="location-name"><i class="bi bi-geo-alt-fill text-cim-orange me-1"></i>Yaoundé</div>
                        <p class="location-meta mt-2 mb-3">
                            National Capital — Regional sales and distribution office.
                            Manages government relations, tenders, and central Cameroon operations.
                        </p>
                        <div class="d-flex gap-3">
                            <div style="font-size:0.78rem;color:var(--cim-text-muted)"><i class="bi bi-building me-1"></i>Sales Office</div>
                            <div style="font-size:0.78rem;color:var(--cim-text-muted)"><i class="bi bi-geo me-1"></i>Centre Region</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ================================================================
     CTA SECTION
     ================================================================ -->
<?php if (!is_logged_in()): ?>
<section class="cta-section">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-7 reveal">
                <span class="section-eyebrow">Get Started</span>
                <h2 class="section-title-dark mt-2" style="font-size:clamp(2rem,4vw,3rem)">
                    Join the Intercity237<br>HR Platform Today
                </h2>
                <p style="color:rgba(255,255,255,0.45);font-size:1rem;max-width:480px;margin:16px auto 0;line-height:1.8">
                    Request access to your department's HR portal. Manage records,
                    export reports, and stay up-to-date with your team.
                </p>
                <div class="d-flex gap-3 justify-content-center mt-5 flex-wrap">
                    <a href="<?= BASE_URL ?>/register.php" class="btn btn-cim btn-lg px-5">
                        <i class="bi bi-person-plus-fill me-2"></i>Create Account
                    </a>
                    <a href="<?= BASE_URL ?>/login.php"
                       class="btn btn-lg px-5 fw-semibold"
                       style="background:rgba(255,255,255,0.07);border:1.5px solid rgba(255,255,255,0.15);color:rgba(255,255,255,0.85);border-radius:10px;">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login Instead
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>


<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
/* Hero page class injection */
document.body.classList.add('hero-page');
</script>
