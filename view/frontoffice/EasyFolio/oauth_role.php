<?php
session_start();
require_once __DIR__ . '/../../../config.php';

// Si pas d'OAuth en attente → on retourne au login
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
  <title>Choisissez votre rôle - SkillBridge</title>
  <link href="assets/img/favicon.png" rel="icon">
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900&family=Noto+Sans:ital,wght@0,100;0,400;0,700&family=Questrial:wght@400&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
  <style>
    .role-card {
      border: 2px solid #e3e6f0;
      border-radius: 16px;
      padding: 32px 26px;
      transition: all .25s ease;
      cursor: pointer;
      background: #fff;
      height: 100%;
      display: flex;
      flex-direction: column;
      text-align: center;
    }
    .role-card:hover {
      border-color: var(--accent-color, #f97316);
      transform: translateY(-4px);
      box-shadow: 0 12px 30px rgba(0,0,0,.08);
    }
    .role-card .role-icon {
      width: 76px; height: 76px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 2rem; margin: 0 auto 16px;
    }
    .role-card.client  .role-icon { background: #e7f1ff; color: #0d6efd; }
    .role-card.freelancer .role-icon { background: #fff3e0; color: #f97316; }
    .role-card h4 { font-weight: 700; margin-bottom: 8px; }
    .role-card p { color: #6c757d; font-size: .92rem; line-height: 1.55; flex: 1; }
    .role-card .btn { margin-top: 18px; padding: 10px 22px; font-weight: 600; }
    .provider-pill {
      display: inline-flex; align-items: center; gap: 8px;
      background: #f8f9fc; border: 1px solid #e3e6f0;
      padding: 6px 14px; border-radius: 999px;
      font-size: .85rem; color: #5a5c69;
    }
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
    <section class="contact section light-background" style="min-height: 85vh; display:flex; align-items:center;">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-9">

            <div class="text-center mb-4">
              <span class="provider-pill mb-3">
                <i class="bi <?= htmlspecialchars($providerIcon) ?>"></i>
                Connecté via <?= htmlspecialchars($providerLabel) ?>
              </span>
              <h2 class="display-6 mb-2">Bienvenue, <?= htmlspecialchars($display) ?> !</h2>
              <p class="lead mb-1">Avant de continuer, dites-nous comment vous comptez utiliser SkillBridge.</p>
              <p class="text-muted small">Vous pourrez modifier ce choix plus tard depuis votre profil.</p>
            </div>

            <?php if (!empty($_SESSION['error'])): ?>
              <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
              <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="row g-4">

              <!-- CLIENT -->
              <div class="col-md-6">
                <form method="POST" action="<?= $BASE ?>/controller/utilisateurcontroller.php" class="h-100">
                  <input type="hidden" name="action" value="oauth_complete_role">
                  <input type="hidden" name="role"   value="client">
                  <button type="submit" class="role-card client w-100 border-0">
                    <div class="role-icon"><i class="bi bi-briefcase-fill"></i></div>
                    <h4>Je suis Client</h4>
                    <p>Je cherche des freelancers compétents pour réaliser mes projets : développement, design, rédaction, etc.</p>
                    <span class="btn btn-primary">Continuer en Client <i class="bi bi-arrow-right ms-1"></i></span>
                  </button>
                </form>
              </div>

              <!-- FREELANCER -->
              <div class="col-md-6">
                <form method="POST" action="<?= $BASE ?>/controller/utilisateurcontroller.php" class="h-100">
                  <input type="hidden" name="action" value="oauth_complete_role">
                  <input type="hidden" name="role"   value="freelancer">
                  <button type="submit" class="role-card freelancer w-100 border-0">
                    <div class="role-icon"><i class="bi bi-tools"></i></div>
                    <h4>Je suis Freelancer</h4>
                    <p>Je propose mes compétences et services à des clients qui cherchent un talent pour leurs projets.</p>
                    <span class="btn btn-warning text-white">Continuer en Freelancer <i class="bi bi-arrow-right ms-1"></i></span>
                  </button>
                </form>
              </div>

            </div>

            <div class="text-center mt-4">
              <a href="<?= $BASE ?>/view/frontoffice/EasyFolio/login.php" class="text-muted small">
                <i class="bi bi-arrow-left"></i> Annuler et revenir à la connexion
              </a>
            </div>

          </div>
        </div>
      </div>
    </section>
  </main>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/js/main.js"></script>
</body>
</html>
