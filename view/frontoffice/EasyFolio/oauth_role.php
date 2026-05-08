<?php
session_start();
require_once __DIR__ . '/../../../config.php';

if (!isset($_SESSION['pending_oauth'])) {
    header('Location: ' . base_url() . '/view/frontoffice/EasyFolio/login.php');
    exit;
}
$pending  = $_SESSION['pending_oauth'];
$provider = $pending['provider'] ?? 'oauth';
$email    = $pending['email']    ?? '';
$prenom   = $pending['prenom']   ?? '';
$nom      = $pending['nom']      ?? '';
$display  = trim($prenom . ' ' . $nom) ?: $email;
$BASE     = base_url();

$providerLabel = [
    'google'   => 'Google',
    'github'   => 'GitHub',
    'discord'  => 'Discord',
    'facebook' => 'Facebook',
    'linkedin' => 'LinkedIn',
][$provider] ?? ucfirst($provider);

$providerIcon = [
    'google'   => 'bi-google',
    'github'   => 'bi-github',
    'discord'  => 'bi-discord',
    'facebook' => 'bi-facebook',
    'linkedin' => 'bi-linkedin',
][$provider] ?? 'bi-shield-check';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Choisissez votre rôle — SkillBridge</title>

  <link href="assets/img/favicon.png" rel="icon">
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    :root { --sb-blue:#2563eb; --sb-orange:#f97316; --sb-dark:#0f172a; --sb-soft:#f8fafc; }
    body { font-family:'Inter', system-ui, -apple-system, sans-serif; }

    .auth-bg {
      background:
        radial-gradient(1100px 600px at 110% -10%, rgba(249,115,22,.18), transparent 60%),
        radial-gradient(900px 500px at -10% 110%, rgba(37,99,235,.15), transparent 60%),
        #fff;
      min-height: calc(100vh - 64px); padding: 80px 0;
    }
    .section-tag {
      display:inline-flex; align-items:center; gap:6px;
      padding:5px 14px; border-radius:999px;
      background: rgba(37,99,235,.08); color: var(--sb-blue);
      font-weight:600; font-size:.82rem;
    }
    h1.auth-title { font-weight:800; line-height:1.05; letter-spacing:-.02em; color:var(--sb-dark); }
    h1.auth-title .accent {
      background: linear-gradient(90deg, var(--sb-orange), var(--sb-blue));
      -webkit-background-clip:text; background-clip:text; color:transparent;
    }

    /* Provider pill */
    .provider-pill {
      display:inline-flex; align-items:center; gap:8px;
      background:#fff; border:1px solid #e2e8f0;
      padding:7px 16px; border-radius:999px;
      font-size:.85rem; color:#475569; font-weight:600;
      box-shadow: 0 4px 12px rgba(15,23,42,.05);
    }
    .provider-pill .icon-bg {
      width:22px; height:22px; border-radius:50%; background:var(--sb-soft);
      display:inline-flex; align-items:center; justify-content:center; font-size:.85rem;
    }
    .provider-pill[data-provider="google"]   .icon-bg i { color:#ea4335; }
    .provider-pill[data-provider="github"]   .icon-bg i { color:#0f172a; }
    .provider-pill[data-provider="discord"]  .icon-bg i { color:#5865F2; }
    .provider-pill[data-provider="facebook"] .icon-bg i { color:#1877F2; }
    .provider-pill[data-provider="linkedin"] .icon-bg i { color:#0A66C2; }

    /* Role cards */
    .role-card {
      background:#fff; border:2px solid #e2e8f0; border-radius:24px;
      padding:38px 32px; height:100%;
      display:flex; flex-direction:column; align-items:center; text-align:center;
      transition: all .25s ease; cursor:pointer; text-decoration:none; color:inherit;
      box-shadow: 0 1px 3px rgba(15,23,42,.04);
    }
    .role-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 30px 60px -25px rgba(15,23,42,.18);
      color:inherit;
    }
    .role-card.client:hover     { border-color: var(--sb-blue); }
    .role-card.freelancer:hover { border-color: var(--sb-orange); }

    .role-card .role-icon {
      width:84px; height:84px; border-radius:22px;
      display:flex; align-items:center; justify-content:center;
      font-size:2.2rem; margin-bottom:18px;
      transition: all .25s ease;
    }
    .role-card.client     .role-icon { background: rgba(37,99,235,.1);  color: var(--sb-blue); }
    .role-card.freelancer .role-icon { background: rgba(249,115,22,.1); color: var(--sb-orange); }
    .role-card:hover.client     .role-icon { transform: scale(1.06); box-shadow: 0 12px 28px -10px rgba(37,99,235,.4); }
    .role-card:hover.freelancer .role-icon { transform: scale(1.06); box-shadow: 0 12px 28px -10px rgba(249,115,22,.4); }

    .role-card h3 { font-weight:800; color: var(--sb-dark); margin-bottom:10px; letter-spacing:-.01em; }
    .role-card p  { color:#64748b; font-size:.95rem; line-height:1.55; flex:1; }

    .btn-role {
      width:100%; padding:14px 24px; border-radius:14px; font-weight:700;
      border:none; transition: all .2s ease; color:#fff;
      display:flex; align-items:center; justify-content:center; gap:8px;
    }
    .btn-role.client     { background: linear-gradient(135deg, var(--sb-blue),   #1d4ed8); }
    .btn-role.freelancer { background: linear-gradient(135deg, var(--sb-orange), #ea580c); }
    .btn-role:hover { transform: translateY(-2px); color:#fff; }
    .btn-role.client:hover     { box-shadow: 0 14px 30px -10px rgba(37,99,235,.55); }
    .btn-role.freelancer:hover { box-shadow: 0 14px 30px -10px rgba(249,115,22,.55); }
  </style>
</head>

<body class="index-page">

  <header id="header" class="header d-flex align-items-center sticky-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
      <a href="index.php" class="logo d-flex align-items-center me-auto me-xl-0">
        <h1 class="sitename">SkillBridge</h1>
      </a>
    </div>
  </header>

  <main class="main">
    <section class="auth-bg">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-10">

            <!-- Header content -->
            <div class="text-center mb-5" data-aos="fade-up">
              <span class="provider-pill" data-provider="<?= htmlspecialchars($provider) ?>">
                <span class="icon-bg"><i class="bi <?= htmlspecialchars($providerIcon) ?>"></i></span>
                Connecté via <?= htmlspecialchars($providerLabel) ?>
              </span>
              <h1 class="auth-title display-4 mt-4 mb-3">
                Bienvenue, <span class="accent"><?= htmlspecialchars($display) ?></span> !
              </h1>
              <p class="lead text-muted mb-1">
                Avant de continuer, dites-nous comment vous comptez utiliser SkillBridge.
              </p>
              <p class="small text-muted mb-0">
                Vous pourrez modifier ce choix plus tard depuis votre profil.
              </p>
            </div>

            <?php if (!empty($_SESSION['error'])): ?>
              <div class="alert alert-danger mb-4 mx-auto" style="max-width:600px; border-radius:12px;">
                <?= htmlspecialchars($_SESSION['error']) ?>
              </div>
              <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Role cards -->
            <div class="row g-4 justify-content-center">

              <div class="col-md-6 col-lg-5" data-aos="fade-right" data-aos-delay="100">
                <form method="POST" action="<?= $BASE ?>/controller/utilisateurcontroller.php" class="h-100">
                  <input type="hidden" name="action" value="oauth_complete_role">
                  <input type="hidden" name="role"   value="client">
                  <button type="submit" class="role-card client">
                    <div class="role-icon"><i class="bi bi-briefcase-fill"></i></div>
                    <h3>Je suis Client</h3>
                    <p>Je cherche des freelancers compétents pour réaliser mes projets : développement, design, rédaction, etc.</p>
                    <span class="btn-role client mt-3">
                      Continuer en Client <i class="bi bi-arrow-right"></i>
                    </span>
                  </button>
                </form>
              </div>

              <div class="col-md-6 col-lg-5" data-aos="fade-left" data-aos-delay="200">
                <form method="POST" action="<?= $BASE ?>/controller/utilisateurcontroller.php" class="h-100">
                  <input type="hidden" name="action" value="oauth_complete_role">
                  <input type="hidden" name="role"   value="freelancer">
                  <button type="submit" class="role-card freelancer">
                    <div class="role-icon"><i class="bi bi-tools"></i></div>
                    <h3>Je suis Freelancer</h3>
                    <p>Je propose mes compétences et services à des clients qui cherchent un talent pour leurs projets.</p>
                    <span class="btn-role freelancer mt-3">
                      Continuer en Freelancer <i class="bi bi-arrow-right"></i>
                    </span>
                  </button>
                </form>
              </div>

            </div>

            <div class="text-center mt-5">
              <a href="<?= $BASE ?>/view/frontoffice/EasyFolio/login.php" class="text-decoration-none small fw-semibold" style="color:#64748b;">
                <i class="bi bi-arrow-left me-1"></i> Annuler et revenir à la connexion
              </a>
            </div>

          </div>
        </div>
      </div>
    </section>
  </main>

  <footer id="footer" class="footer" style="background:var(--sb-dark); color:#cbd5e1; padding:24px 0;">
    <div class="container text-center small">
      © <?= date('Y') ?> <strong style="color:#fff;">SkillBridge</strong> — Tous droits réservés.
    </div>
  </footer>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/js/main.js"></script>
  <script>
    // Filet de sécurité : si main.js n'a pas initialisé AOS pour une raison
    // quelconque, on l'initialise nous-mêmes pour que les éléments
    // [data-aos] ne restent pas invisibles (opacity:0).
    if (typeof AOS !== 'undefined') {
      AOS.init({ duration: 600, once: true });
    }
  </script>
</body>
</html>
