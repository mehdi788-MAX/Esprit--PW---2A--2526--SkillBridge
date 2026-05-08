<?php
session_start();
require_once __DIR__ . '/../../../config.php';
$BASE = base_url();

$error   = $_SESSION['error']   ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Inscription — SkillBridge</title>
  <meta name="description" content="Rejoignez SkillBridge — la marketplace freelance qui matche les bons talents.">

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
      min-height: calc(100vh - 64px); padding: 60px 0;
    }
    .section-tag {
      display:inline-block; padding:4px 14px; border-radius:999px;
      background: rgba(37,99,235,.08); color: var(--sb-blue);
      font-weight:600; font-size:.82rem;
    }
    h1.auth-title { font-weight:800; line-height:1.05; letter-spacing:-.02em; color:var(--sb-dark); }
    h1.auth-title .accent {
      background: linear-gradient(90deg, var(--sb-orange), var(--sb-blue));
      -webkit-background-clip:text; background-clip:text; color:transparent;
    }

    .auth-card {
      background:#fff; border:1px solid #e2e8f0; border-radius:24px;
      box-shadow: 0 30px 60px -25px rgba(15,23,42,.18);
      padding: 38px 36px;
    }
    .auth-card .form-label { font-weight:600; font-size:.88rem; color:#475569; }
    .auth-card .form-control, .auth-card .form-select {
      border-radius:12px; padding:13px 14px; border:1px solid #e2e8f0;
      transition: all .18s; font-size:.95rem;
    }
    .auth-card .form-control:focus, .auth-card .form-select:focus {
      border-color: var(--sb-blue); box-shadow: 0 0 0 4px rgba(37,99,235,.12);
    }

    .btn-gradient {
      background: linear-gradient(135deg, var(--sb-orange), var(--sb-blue));
      color:#fff !important; font-weight:600; padding:13px 24px; border-radius:14px;
      border:none; transition: all .2s ease;
    }
    .btn-gradient:hover { transform: translateY(-2px); box-shadow: 0 14px 30px -10px rgba(37,99,235,.45); color:#fff; }

    /* Role chooser cards */
    .role-toggle { display:flex; gap:10px; }
    .role-btn {
      flex:1; cursor:pointer; padding:16px; border:2px solid #e2e8f0; border-radius:14px;
      background:#fff; text-align:center; transition: all .18s ease;
      display:flex; flex-direction:column; align-items:center; gap:6px;
    }
    .role-btn:hover { transform: translateY(-2px); box-shadow:0 8px 20px -6px rgba(15,23,42,.1); }
    .role-btn .role-icon {
      width:42px; height:42px; border-radius:12px;
      display:flex; align-items:center; justify-content:center; font-size:1.25rem;
      background:#f1f5f9; color:#94a3b8; transition: all .2s;
    }
    .role-btn .role-label { font-weight:700; font-size:.95rem; color: var(--sb-dark); }
    .role-btn .role-desc  { font-size:.72rem; color:#64748b; }
    .role-btn input[type="radio"] { display:none; }
    .role-btn input[type="radio"]:checked + .role-content .role-icon-client     { background:rgba(37,99,235,.12); color: var(--sb-blue); }
    .role-btn input[type="radio"]:checked + .role-content .role-icon-freelancer { background:rgba(249,115,22,.12); color: var(--sb-orange); }
    .role-btn:has(input[type="radio"]:checked).client     { border-color: var(--sb-blue);   background: rgba(37,99,235,.04); }
    .role-btn:has(input[type="radio"]:checked).freelancer { border-color: var(--sb-orange); background: rgba(249,115,22,.04); }

    /* Right value-prop */
    .feature-tile {
      background:#fff; border:1px solid #e2e8f0; border-radius:14px;
      padding:14px 16px; display:flex; gap:12px; align-items:center;
      transition:all .2s; box-shadow: 0 1px 3px rgba(0,0,0,.04);
    }
    .feature-tile:hover { transform: translateY(-2px); box-shadow:0 12px 24px -10px rgba(15,23,42,.1); }
    .feature-tile .tile-icon {
      width:44px; height:44px; border-radius:12px; flex-shrink:0;
      display:flex; align-items:center; justify-content:center; font-size:1.2rem;
    }
    .feature-tile .tile-title { font-weight:700; color: var(--sb-dark); font-size:.95rem; }
    .feature-tile .tile-sub   { color:#64748b; font-size:.8rem; }
  </style>
</head>

<body class="index-page">

  <header id="header" class="header d-flex align-items-center sticky-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
      <a href="index.php" class="logo d-flex align-items-center me-auto me-xl-0">
        <h1 class="sitename">SkillBridge</h1>
      </a>
      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.php">Accueil</a></li>
          <li><a href="login.php">Connexion</a></li>
          <li><a href="register.php" class="active">Inscription</a></li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>
    </div>
  </header>

  <main class="main">
    <section class="auth-bg">
      <div class="container">
        <div class="row align-items-center g-5 justify-content-center">

          <!-- LEFT — Form -->
          <div class="col-lg-7" data-aos="fade-right">
            <div class="auth-card">
              <span class="section-tag" style="background: rgba(249,115,22,.1); color: var(--sb-orange);">
                <i class="bi bi-rocket-takeoff me-1"></i> Rejoignez-nous
              </span>
              <h1 class="auth-title display-5 mt-3 mb-2">Créez votre <span class="accent">compte</span>.</h1>
              <p class="text-muted mb-4">Inscrivez-vous gratuitement et démarrez sur SkillBridge en moins d'une minute.</p>

              <?php if ($error): ?>
                <div class="alert alert-danger" style="border-radius:12px;"><?= htmlspecialchars($error) ?></div>
              <?php endif; ?>
              <?php if ($success): ?>
                <div class="alert alert-success" style="border-radius:12px;"><?= htmlspecialchars($success) ?></div>
              <?php endif; ?>

              <div id="js-errors" class="alert alert-danger d-none" style="border-radius:12px;"></div>

              <form id="registerForm" action="../../../controller/utilisateurcontroller.php" method="POST" novalidate>
                <input type="hidden" name="action" value="register">

                <!-- Role chooser -->
                <div class="mb-4">
                  <label class="form-label">Je suis</label>
                  <div class="role-toggle">
                    <label class="role-btn client">
                      <input type="radio" name="role" value="client">
                      <span class="role-content">
                        <span class="role-icon role-icon-client"><i class="bi bi-briefcase-fill"></i></span>
                        <span class="role-label">Client</span>
                        <span class="role-desc">Je cherche un freelancer</span>
                      </span>
                    </label>
                    <label class="role-btn freelancer">
                      <input type="radio" name="role" value="freelancer">
                      <span class="role-content">
                        <span class="role-icon role-icon-freelancer"><i class="bi bi-tools"></i></span>
                        <span class="role-label">Freelancer</span>
                        <span class="role-desc">Je propose mes services</span>
                      </span>
                    </label>
                  </div>
                  <div id="role-error" class="text-danger mt-1 small" style="display:none;"></div>
                </div>

                <div class="row g-3">
                  <div class="col-md-6">
                    <label for="prenom" class="form-label">Prénom</label>
                    <input type="text" name="prenom" id="prenom" class="form-control" placeholder="Votre prénom">
                    <div id="prenom-error" class="text-danger mt-1 small" style="display:none;"></div>
                  </div>
                  <div class="col-md-6">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" name="nom" id="nom" class="form-control" placeholder="Votre nom">
                    <div id="nom-error" class="text-danger mt-1 small" style="display:none;"></div>
                  </div>

                  <div class="col-12">
                    <label for="email" class="form-label">Adresse email</label>
                    <input type="text" name="email" id="email" class="form-control" placeholder="vous@exemple.com">
                    <div id="email-error" class="text-danger mt-1 small" style="display:none;"></div>
                  </div>

                  <div class="col-md-6">
                    <label for="password" class="form-label">Mot de passe</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Minimum 8 caractères">
                    <div id="password-error" class="text-danger mt-1 small" style="display:none;"></div>
                  </div>
                  <div class="col-md-6">
                    <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Répétez le mot de passe">
                    <div id="confirm-error" class="text-danger mt-1 small" style="display:none;"></div>
                  </div>

                  <div class="col-12">
                    <label for="telephone" class="form-label">Téléphone <span class="text-muted small">(optionnel)</span></label>
                    <input type="text" name="telephone" id="telephone" class="form-control" placeholder="+216 XX XXX XXX">
                  </div>
                </div>

                <button type="submit" class="btn btn-gradient w-100 mt-4">
                  <i class="bi bi-rocket-takeoff me-1"></i> Créer mon compte
                </button>

                <p class="text-center mb-0 mt-4 small text-muted">
                  Vous avez déjà un compte ? <a href="login.php" class="fw-semibold text-decoration-none" style="color:var(--sb-blue);">Se connecter</a>
                </p>

              </form>
            </div>
          </div>

          <!-- RIGHT — Why join -->
          <div class="col-lg-5 d-none d-lg-block" data-aos="fade-left">
            <span class="section-tag">Pourquoi nous rejoindre</span>
            <h2 class="display-5 mt-3 mb-3" style="font-weight:800; letter-spacing:-.01em; color: var(--sb-dark);">
              La marketplace freelance <span style="background: linear-gradient(90deg, var(--sb-orange), var(--sb-blue)); -webkit-background-clip:text; background-clip:text; color:transparent;">tout-en-un</span>.
            </h2>
            <p class="text-muted lead mb-4">
              SkillBridge réunit toutes les fonctionnalités modernes nécessaires pour bien collaborer : messagerie temps réel, OAuth, profils vérifiés.
            </p>

            <div class="d-flex flex-column gap-3">
              <div class="feature-tile">
                <div class="tile-icon" style="background:rgba(37,99,235,.1); color:var(--sb-blue);"><i class="bi bi-cash-coin"></i></div>
                <div>
                  <div class="tile-title">100% gratuit</div>
                  <div class="tile-sub">Inscription et utilisation sans frais cachés.</div>
                </div>
              </div>
              <div class="feature-tile">
                <div class="tile-icon" style="background:rgba(249,115,22,.1); color:var(--sb-orange);"><i class="bi bi-chat-dots-fill"></i></div>
                <div>
                  <div class="tile-title">Chat temps réel</div>
                  <div class="tile-sub">Discutez avec vos collaborateurs sans quitter la plateforme.</div>
                </div>
              </div>
              <div class="feature-tile">
                <div class="tile-icon" style="background:rgba(124,58,237,.1); color:#7c3aed;"><i class="bi bi-shield-check"></i></div>
                <div>
                  <div class="tile-title">Vérification d'email</div>
                  <div class="tile-sub">Tous les comptes sont vérifiés pour collaborer en confiance.</div>
                </div>
              </div>
              <div class="feature-tile">
                <div class="tile-icon" style="background:rgba(16,185,129,.1); color:#10b981;"><i class="bi bi-globe2"></i></div>
                <div>
                  <div class="tile-title">Communauté locale</div>
                  <div class="tile-sub">Talents et clients tunisiens, paiements en TND.</div>
                </div>
              </div>
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

  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/js/main.js"></script>

  <script>
  document.getElementById('registerForm').addEventListener('submit', function (e) {
    let valid = true;
    ['nom','prenom','email','password','confirm_password'].forEach(id => {
      const f = document.getElementById(id);
      if (f) f.classList.remove('is-invalid','is-valid');
    });
    ['nom-error','prenom-error','email-error','password-error','confirm-error','role-error'].forEach(id => {
      const e = document.getElementById(id);
      if (e) { e.textContent=''; e.style.display='none'; }
    });

    function show(fid, eid, m) {
      const f = document.getElementById(fid), er = document.getElementById(eid);
      if (f) f.classList.add('is-invalid');
      if (er) { er.textContent = m; er.style.display='block'; }
      valid = false;
    }

    const nom    = document.getElementById('nom').value.trim();
    const prenom = document.getElementById('prenom').value.trim();
    const email  = document.getElementById('email').value.trim();
    const pwd    = document.getElementById('password').value;
    const cpwd   = document.getElementById('confirm_password').value;
    const role   = document.querySelector('input[name="role"]:checked');
    const re     = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!role)  show(null, 'role-error', 'Veuillez choisir un rôle (Client ou Freelancer).');
    if (!nom)    show('nom',    'nom-error',    'Le nom est obligatoire.');
    if (!prenom) show('prenom', 'prenom-error', 'Le prénom est obligatoire.');
    if (!email)            show('email', 'email-error', "L'email est obligatoire.");
    else if (!re.test(email)) show('email', 'email-error', 'Format invalide (ex : nom@email.com).');
    if (!pwd)              show('password', 'password-error', 'Le mot de passe est obligatoire.');
    else if (pwd.length<8) show('password', 'password-error', 'Minimum 8 caractères.');
    if (!cpwd)             show('confirm_password', 'confirm-error', 'Veuillez confirmer le mot de passe.');
    else if (pwd !== cpwd) show('confirm_password', 'confirm-error', 'Les mots de passe ne correspondent pas.');

    if (!valid) e.preventDefault();
  });
  </script>
</body>
</html>
