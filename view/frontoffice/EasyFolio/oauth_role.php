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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">

  <style>
    :root {
      --bg:#F7F4ED; --paper:#FFFFFF; --ink:#0F0F0F; --ink-2:#2A2A2A;
      --ink-mute:#5C5C5C; --ink-soft:#A3A3A3; --rule:#E8E2D5;
      --sage:#1F5F4D; --sage-d:#134438; --sage-soft:#E8F0EC;
      --honey:#F5C842; --honey-d:#E0B033; --honey-soft:#FBF1D0;
    }
    *, *::before, *::after { box-sizing: border-box; }
    body { font-family:'Manrope', system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--ink); letter-spacing:-.005em; -webkit-font-smoothing:antialiased; margin:0; }
    ::selection { background: var(--sage); color: var(--honey); }

    h1, h2, h3, h4 { font-family:'Manrope', sans-serif; font-weight:700; letter-spacing:-.022em; color: var(--ink); }
    .display-x { font-size: clamp(2rem, 3.6vw, 2.8rem); line-height:1.05; font-weight:800; letter-spacing:-.025em; }
    .lead-x    { font-size:1rem; line-height:1.55; color: var(--ink-mute); font-weight:400; }
    .accent    { font-style: italic; font-weight:700; color: var(--sage); }

    /* Header */
    .sb-header { position:sticky; top:0; z-index:100; background: rgba(247,244,237,.85); backdrop-filter: blur(14px); border-bottom:1px solid var(--rule); }
    .sb-header .container { display:flex; align-items:center; justify-content:space-between; padding:14px 0; }
    .sb-logo { display:inline-flex; align-items:center; text-decoration:none; color: var(--ink); }
    .sb-logo .logo-img { height:38px; width:auto; display:block; }
    .sb-help { color: var(--ink-mute); text-decoration:none; font-weight:500; font-size:.92rem; transition: color .15s; }
    .sb-help:hover { color: var(--sage); }

    /* Page */
    .page-bg { position:relative; overflow:hidden; min-height: calc(100vh - 64px); padding: 64px 0 80px; }
    .blob { position:absolute; border-radius:50%; filter: blur(60px); opacity:.55; pointer-events:none; z-index:0; }
    .blob.sage { background: var(--sage-soft); }
    .blob.honey { background: var(--honey-soft); }
    .blob-1 { width:380px; height:380px; left:-120px; top:-100px; }
    .blob-2 { width:340px; height:340px; right:-100px; bottom:-60px; }
    .page-bg .container { position:relative; z-index:1; }

    /* Provider pill */
    .provider-pill {
      display:inline-flex; align-items:center; gap:10px;
      background: var(--paper); border:1px solid var(--rule);
      padding:7px 16px 7px 7px; border-radius:999px;
      font-size:.85rem; color: var(--ink-2); font-weight:600;
      box-shadow: 0 4px 12px rgba(15,15,15,.04);
    }
    .provider-pill .icon-bg {
      width:30px; height:30px; border-radius:50%; background: var(--bg);
      display:inline-flex; align-items:center; justify-content:center; font-size:.95rem;
    }
    .provider-pill[data-provider="google"]   .icon-bg i { color:#EA4335; }
    .provider-pill[data-provider="github"]   .icon-bg i { color: var(--ink); }
    .provider-pill[data-provider="discord"]  .icon-bg i { color:#5865F2; }
    .provider-pill[data-provider="facebook"] .icon-bg i { color:#1877F2; }
    .provider-pill[data-provider="linkedin"] .icon-bg i { color:#0A66C2; }

    /* Role cards */
    .role-grid { display:grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    @media (max-width: 767.98px) { .role-grid { grid-template-columns: 1fr; gap: 18px; } }

    .role-card {
      background: var(--paper); border:1.5px solid var(--rule); border-radius: 24px;
      padding: 36px 30px 30px;
      display:flex; flex-direction:column; align-items:center; text-align:center;
      transition: all .25s ease; cursor:pointer; text-decoration:none; color:inherit;
      box-shadow: 0 1px 3px rgba(15,15,15,.04);
      width:100%;
      position: relative; overflow: hidden;
      font-family: 'Manrope', sans-serif;
    }
    .role-card::before {
      content:''; position:absolute; top:0; left:0; right:0; height:4px;
      background: transparent; transition: background .25s;
    }
    .role-card:hover {
      transform: translateY(-6px); color: var(--ink);
      box-shadow: 0 30px 60px -25px rgba(31,95,77,.22);
    }
    .role-card.client:hover     { border-color: var(--sage);   }
    .role-card.client:hover::before     { background: var(--sage); }
    .role-card.freelancer:hover { border-color: var(--honey-d); }
    .role-card.freelancer:hover::before { background: var(--honey); }

    .role-card .role-icon {
      width: 88px; height: 88px; border-radius: 24px;
      display:flex; align-items:center; justify-content:center;
      font-size: 2.2rem; margin-bottom: 22px;
      transition: all .25s ease;
    }
    .role-card.client     .role-icon { background: var(--sage-soft);  color: var(--sage); }
    .role-card.freelancer .role-icon { background: var(--honey-soft); color: #92660A; }
    .role-card:hover.client     .role-icon { transform: scale(1.06); box-shadow: 0 12px 26px -10px rgba(31,95,77,.4); }
    .role-card:hover.freelancer .role-icon { transform: scale(1.06); box-shadow: 0 12px 26px -10px rgba(245,200,66,.55); }

    .role-card h3 { font-weight:800; color: var(--ink); margin-bottom: 10px; font-size: 1.4rem; letter-spacing:-.018em; }
    .role-card p  { color: var(--ink-mute); font-size: .94rem; line-height: 1.55; margin: 0 0 24px; flex-grow: 1; }

    .btn-role {
      width: 100%; padding: 14px 20px; border-radius: 14px; font-weight: 700; font-size: .98rem;
      border: none; transition: all .2s ease;
      display:flex; align-items:center; justify-content:center; gap: 8px;
      letter-spacing:-.005em;
    }
    .btn-role.client     { background: var(--sage);   color: var(--paper); }
    .btn-role.freelancer { background: var(--honey);  color: var(--ink);   }
    .role-card.client:hover     .btn-role     { background: var(--sage-d); }
    .role-card.freelancer:hover .btn-role     { background: var(--honey-d); }

    /* Alerts */
    .sb-alert { border-radius:14px; padding:14px 16px; border:1px solid; display:flex; align-items:center; gap:12px; max-width: 600px; margin: 0 auto 24px; }
    .sb-alert.danger  { background: #FEF2F2; border-color: #FECACA; color: #991B1B; }

    /* Cancel link */
    .btn-cancel {
      display:inline-flex; align-items:center; gap:6px;
      color: var(--ink-mute); text-decoration:none; font-weight:600; font-size:.9rem;
      transition: color .15s;
    }
    .btn-cancel:hover { color: var(--sage); }

    /* Footer */
    .sb-footer { background: var(--ink); color: rgba(255,255,255,.65); padding:22px 0; font-size:.88rem; text-align:center; }
    .sb-footer strong { color: var(--paper); }
  </style>
</head>

<body>

  <header class="sb-header">
    <div class="container">
      <a href="index.php" class="sb-logo">
        <img src="assets/img/skillbridge-logo.png" alt="SkillBridge" class="logo-img" loading="eager">
      </a>
      <a href="<?= $BASE ?>/view/frontoffice/EasyFolio/login.php" class="sb-help">
        <i class="bi bi-question-circle"></i> Besoin d'aide ?
      </a>
    </div>
  </header>

  <main>
    <section class="page-bg">
      <div class="blob sage  blob-1"></div>
      <div class="blob honey blob-2"></div>

      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-10 col-xl-9">

            <!-- Header content -->
            <div class="text-center mb-5" data-aos="fade-up">
              <span class="provider-pill" data-provider="<?= htmlspecialchars($provider) ?>">
                <span class="icon-bg"><i class="bi <?= htmlspecialchars($providerIcon) ?>"></i></span>
                Connecté via <?= htmlspecialchars($providerLabel) ?>
              </span>
              <h1 class="display-x mt-4 mb-3">
                Bienvenue, <span class="accent"><?= htmlspecialchars($display) ?></span>.
              </h1>
              <p class="lead-x mb-1">
                Avant de continuer, dites-nous comment vous comptez utiliser SkillBridge.
              </p>
              <p class="mb-0" style="color: var(--ink-soft); font-size: .88rem;">
                Vous pourrez modifier ce choix plus tard depuis votre profil.
              </p>
            </div>

            <?php if (!empty($_SESSION['error'])): ?>
              <div class="sb-alert danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?= htmlspecialchars($_SESSION['error']) ?></span>
              </div>
              <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Role cards -->
            <div class="role-grid">

              <form method="POST" action="<?= $BASE ?>/controller/utilisateurcontroller.php" data-aos="fade-right" data-aos-delay="100">
                <input type="hidden" name="action" value="oauth_complete_role">
                <input type="hidden" name="role"   value="client">
                <button type="submit" class="role-card client">
                  <div class="role-icon"><i class="bi bi-briefcase-fill"></i></div>
                  <h3>Je suis <span class="accent">Client</span></h3>
                  <p>Je cherche des freelancers compétents pour réaliser mes projets : développement, design, rédaction, etc.</p>
                  <span class="btn-role client">
                    Continuer en Client <i class="bi bi-arrow-right"></i>
                  </span>
                </button>
              </form>

              <form method="POST" action="<?= $BASE ?>/controller/utilisateurcontroller.php" data-aos="fade-left" data-aos-delay="200">
                <input type="hidden" name="action" value="oauth_complete_role">
                <input type="hidden" name="role"   value="freelancer">
                <button type="submit" class="role-card freelancer">
                  <div class="role-icon"><i class="bi bi-tools"></i></div>
                  <h3>Je suis <span style="font-style: italic; color: #92660A;">Freelancer</span></h3>
                  <p>Je propose mes compétences et services à des clients qui cherchent un talent pour leurs projets.</p>
                  <span class="btn-role freelancer">
                    Continuer en Freelancer <i class="bi bi-arrow-right"></i>
                  </span>
                </button>
              </form>

            </div>

            <div class="text-center mt-5">
              <a href="<?= $BASE ?>/view/frontoffice/EasyFolio/login.php" class="btn-cancel">
                <i class="bi bi-arrow-left"></i> Annuler et revenir à la connexion
              </a>
            </div>

          </div>
        </div>
      </div>
    </section>
  </main>

  <footer class="sb-footer">
    © <?= date('Y') ?> <strong>SkillBridge</strong> — Tous droits réservés.
  </footer>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script>
    if (typeof AOS !== 'undefined') {
      AOS.init({ duration: 600, easing: 'ease-out-cubic', once: true });
    }
  </script>
</body>
</html>
