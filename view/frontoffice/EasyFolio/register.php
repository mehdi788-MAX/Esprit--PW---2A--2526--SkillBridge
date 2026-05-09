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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">

  <style>
    :root {
      --bg:          #F7F4ED;
      --paper:       #FFFFFF;
      --ink:         #0F0F0F;
      --ink-2:       #2A2A2A;
      --ink-mute:    #5C5C5C;
      --ink-soft:    #A3A3A3;
      --rule:        #E8E2D5;
      --sage:        #1F5F4D;
      --sage-d:      #134438;
      --sage-soft:   #E8F0EC;
      --honey:       #F5C842;
      --honey-d:     #E0B033;
      --honey-soft:  #FBF1D0;
      --sb-orange: var(--honey);
      --sb-blue:   var(--sage);
      --sb-dark:   var(--ink);
    }
    *, *::before, *::after { box-sizing: border-box; }
    body {
      font-family: 'Manrope', system-ui, -apple-system, sans-serif;
      background: var(--bg); color: var(--ink); letter-spacing: -.005em;
      -webkit-font-smoothing: antialiased; margin: 0;
    }
    ::selection { background: var(--sage); color: var(--honey); }

    h1, h2, h3, h4, h5 { font-family: 'Manrope', sans-serif; font-weight: 700; letter-spacing: -.022em; color: var(--ink); }
    .display-x { font-size: clamp(2rem, 3.6vw, 2.8rem); line-height: 1.05; font-weight: 800; letter-spacing: -.025em; }
    .display-l { font-size: clamp(1.6rem, 2.4vw, 2.1rem); line-height: 1.1;  letter-spacing: -.02em; font-weight: 800; }
    .lead-x    { font-size: 1rem; line-height: 1.55; color: var(--ink-mute); font-weight: 400; }
    .accent    { font-style: italic; font-weight: 700; color: var(--sage); }

    .eyebrow {
      display:inline-flex; align-items:center; gap:8px;
      font-size: .8rem; font-weight: 600;
      color: var(--sage); padding: 6px 12px;
      background: var(--sage-soft); border-radius: 999px;
    }
    .eyebrow .dot { width:6px; height:6px; border-radius:50%; background: var(--sage); }
    .eyebrow.honey { color: #92660A; background: var(--honey-soft); }
    .eyebrow.honey .dot { background: var(--honey-d); }

    /* ----------------- Header ----------------- */
    .sb-header {
      position: sticky; top: 0; z-index: 100;
      background: rgba(247,244,237,.85); backdrop-filter: blur(14px);
      border-bottom: 1px solid var(--rule);
    }
    .sb-header .container { display:flex; align-items:center; justify-content:space-between; padding: 14px 0; }
    .sb-logo { display:inline-flex; align-items:center; text-decoration:none; color: var(--ink); }
    .sb-logo .logo-img { height: 38px; width: auto; display: block; }
    .sb-nav { display:flex; align-items:center; gap: 28px; }
    .sb-nav a { color: var(--ink-mute); text-decoration:none; font-weight:500; font-size:.92rem; transition: color .15s; }
    .sb-nav a:hover, .sb-nav a.active { color: var(--ink); }
    .sb-nav a.active { color: var(--sage); }
    .sb-cta {
      display:inline-flex; align-items:center; gap:8px;
      background: var(--ink); color: var(--bg); padding: 10px 20px; border-radius: 999px;
      text-decoration:none; font-weight:600; font-size:.92rem; transition: all .2s ease;
    }
    .sb-cta:hover { background: var(--sage); color: var(--paper); transform: translateY(-1px); }
    @media (max-width: 991.98px) { .sb-nav { display: none; } }

    /* ----------------- Auth canvas ----------------- */
    .auth-bg {
      position: relative; overflow: hidden; min-height: calc(100vh - 64px);
      padding: 48px 0 60px;
    }
    .blob { position: absolute; border-radius: 50%; filter: blur(60px); opacity: .55; pointer-events: none; z-index: 0; }
    .blob.sage  { background: var(--sage-soft); }
    .blob.honey { background: var(--honey-soft); }
    .blob-1 { width: 380px; height: 380px; left: -120px; top: -120px; }
    .blob-2 { width: 360px; height: 360px; right: -80px; bottom: -60px; }
    .auth-bg .container { position: relative; z-index: 1; }

    .auth-grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr); gap: 48px; align-items: start; }
    @media (max-width: 991.98px) { .auth-grid { grid-template-columns: 1fr; gap: 40px; } }

    /* ----------------- Auth card ----------------- */
    .auth-card {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 24px;
      padding: 36px 34px;
      box-shadow: 0 30px 60px -25px rgba(31,95,77,.18);
    }
    .auth-card .form-label {
      font-weight: 600; font-size: .88rem; color: var(--ink-2); margin-bottom: 6px; display: block;
    }
    .auth-card .form-control, .auth-card .form-select {
      border-radius: 12px; padding: 13px 14px; font-size: .96rem;
      border: 1px solid var(--rule); background: var(--paper);
      transition: border-color .18s, box-shadow .18s;
      width: 100%;
    }
    .auth-card .form-control:focus, .auth-card .form-select:focus {
      outline: none;
      border-color: var(--sage);
      box-shadow: 0 0 0 4px rgba(31,95,77,.12);
    }
    .auth-card .form-control.is-invalid { border-color: #DC2626; }

    /* ----------------- Buttons ----------------- */
    .btn-sage {
      display: flex; align-items: center; justify-content: center; gap: 10px;
      width: 100%; padding: 14px 22px; border-radius: 12px; border: none;
      background: var(--sage); color: var(--paper) !important;
      font-weight: 700; font-size: 1rem;
      transition: all .2s ease; text-decoration: none; cursor: pointer;
    }
    .btn-sage:hover { background: var(--sage-d); transform: translateY(-2px); box-shadow: 0 14px 28px -12px rgba(31,95,77,.4); }

    /* ----------------- Role chooser ----------------- */
    .role-toggle { display: flex; gap: 12px; }
    .role-btn {
      flex: 1; cursor: pointer; padding: 18px 16px;
      border: 2px solid var(--rule); border-radius: 16px; background: var(--paper);
      transition: all .2s ease;
      display: flex; flex-direction: column; align-items: center; gap: 8px;
      text-align: center;
    }
    .role-btn:hover { transform: translateY(-2px); border-color: var(--sage); box-shadow: 0 14px 26px -14px rgba(31,95,77,.18); }
    .role-btn .role-icon {
      width: 46px; height: 46px; border-radius: 13px;
      display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
      background: var(--bg); color: var(--ink-soft); transition: all .2s ease;
    }
    .role-btn .role-label { font-weight: 700; font-size: 1rem; color: var(--ink); }
    .role-btn .role-desc  { font-size: .76rem; color: var(--ink-mute); line-height: 1.3; }
    .role-btn input[type="radio"] { display: none; }
    .role-btn input[type="radio"]:checked + .role-content .role-icon-client     { background: var(--sage-soft);  color: var(--sage); }
    .role-btn input[type="radio"]:checked + .role-content .role-icon-freelancer { background: var(--honey-soft); color: #92660A; }
    .role-btn:has(input[type="radio"]:checked).client     { border-color: var(--sage);  background: var(--sage-soft); }
    .role-btn:has(input[type="radio"]:checked).freelancer { border-color: var(--honey-d); background: var(--honey-soft); }

    /* ----------------- Right column feature tiles ----------------- */
    .feature-tile {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 16px;
      padding: 16px 18px; display: flex; gap: 14px; align-items: center;
      transition: all .2s ease;
    }
    .feature-tile:hover { transform: translateY(-2px); border-color: var(--sage); }
    .feature-tile .tile-icon {
      width: 46px; height: 46px; border-radius: 12px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
    }
    .feature-tile .tile-icon.t-sage  { background: var(--sage-soft); color: var(--sage); }
    .feature-tile .tile-icon.t-honey { background: var(--honey-soft); color: #92660A; }
    .feature-tile .tile-title { font-weight: 700; color: var(--ink); font-size: .98rem; line-height: 1.2; }
    .feature-tile .tile-sub   { color: var(--ink-mute); font-size: .82rem; margin-top: 2px; }

    /* ----------------- Alerts ----------------- */
    .alert {
      border-radius: 14px; padding: 14px 16px; border: 1px solid; margin-bottom: 16px;
    }
    .alert-success { background: var(--sage-soft);  border-color: rgba(31,95,77,.2);  color: var(--sage-d); }
    .alert-warning { background: var(--honey-soft); border-color: rgba(224,176,51,.3); color: #7a4f08; }
    .alert-danger  { background: #FEF2F2; border-color: #FECACA; color: #991B1B; }

    /* ----------------- Footer ----------------- */
    .sb-footer { background: var(--ink); color: rgba(255,255,255,.65); padding: 22px 0; font-size: .88rem; text-align: center; }
    .sb-footer strong { color: var(--paper); }

    /* legacy hooks */
    .navmenu a.active { color: var(--sage) !important; }
  </style>
</head>

<body>

  <header class="sb-header">
    <div class="container">
      <a href="index.php" class="sb-logo">
        <img src="assets/img/skillbridge-logo.png" alt="SkillBridge" class="logo-img" loading="eager">
      </a>
      <nav class="sb-nav">
        <a href="index.php">Accueil</a>
        <a href="login.php">Connexion</a>
        <a href="register.php" class="active">Inscription</a>
      </nav>
      <a href="login.php" class="sb-cta">
        <span>Connexion</span><i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </header>

  <main>
    <section class="auth-bg">
      <div class="blob honey blob-1"></div>
      <div class="blob sage  blob-2"></div>

      <div class="container">
        <div class="auth-grid">

          <!-- LEFT — Form -->
          <div data-aos="fade-right">
            <div class="auth-card">
              <span class="eyebrow honey"><span class="dot"></span> Rejoignez-nous</span>
              <h1 class="display-x mt-3 mb-2">Créez votre <span class="accent">compte</span>.</h1>
              <p class="lead-x mb-4" style="font-size:.95rem;">Inscrivez-vous gratuitement et démarrez sur SkillBridge en moins d'une minute.</p>

              <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
              <?php endif; ?>
              <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
              <?php endif; ?>

              <div id="js-errors" class="alert alert-danger d-none"></div>

              <form id="registerForm" action="../../../controller/utilisateurcontroller.php" method="POST" novalidate>
                <input type="hidden" name="action" value="register">

                <!-- Role chooser -->
                <div class="mb-4">
                  <label class="form-label">Je suis</label>
                  <div class="role-toggle">
                    <label class="role-btn client">
                      <input type="radio" name="role" value="client">
                      <span class="role-content d-flex flex-column align-items-center gap-1">
                        <span class="role-icon role-icon-client"><i class="bi bi-briefcase-fill"></i></span>
                        <span class="role-label">Client</span>
                        <span class="role-desc">Je cherche un freelancer</span>
                      </span>
                    </label>
                    <label class="role-btn freelancer">
                      <input type="radio" name="role" value="freelancer">
                      <span class="role-content d-flex flex-column align-items-center gap-1">
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
                    <label for="confirm_password" class="form-label">Confirmer</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Répétez le mot de passe">
                    <div id="confirm-error" class="text-danger mt-1 small" style="display:none;"></div>
                  </div>

                  <div class="col-12">
                    <label for="telephone" class="form-label">Téléphone <span style="color: var(--ink-soft); font-weight: 500;">(optionnel)</span></label>
                    <input type="text" name="telephone" id="telephone" class="form-control" placeholder="+216 XX XXX XXX">
                  </div>
                </div>

                <button type="submit" class="btn-sage mt-4">
                  <i class="bi bi-rocket-takeoff"></i> Créer mon compte
                </button>

                <p class="text-center mb-0 mt-4 small" style="color: var(--ink-mute);">
                  Vous avez déjà un compte ? <a href="login.php" class="fw-semibold text-decoration-none" style="color: var(--sage);">Se connecter</a>
                </p>

              </form>
            </div>
          </div>

          <!-- RIGHT — Why join -->
          <div class="d-none d-lg-block" data-aos="fade-left">
            <span class="eyebrow"><span class="dot"></span> Pourquoi nous rejoindre</span>
            <h2 class="display-l mt-3 mb-3">La marketplace freelance <span class="accent">tout-en-un</span>.</h2>
            <p class="lead-x mb-4">
              SkillBridge réunit toutes les fonctionnalités modernes nécessaires pour bien collaborer : messagerie temps réel, OAuth, profils vérifiés.
            </p>

            <div class="d-flex flex-column gap-3">
              <div class="feature-tile">
                <div class="tile-icon t-sage"><i class="bi bi-cash-coin"></i></div>
                <div>
                  <div class="tile-title">100% gratuit</div>
                  <div class="tile-sub">Inscription et utilisation sans frais cachés.</div>
                </div>
              </div>
              <div class="feature-tile">
                <div class="tile-icon t-honey"><i class="bi bi-chat-dots-fill"></i></div>
                <div>
                  <div class="tile-title">Chat temps réel</div>
                  <div class="tile-sub">Discutez avec vos collaborateurs sans quitter la plateforme.</div>
                </div>
              </div>
              <div class="feature-tile">
                <div class="tile-icon t-sage"><i class="bi bi-shield-check"></i></div>
                <div>
                  <div class="tile-title">Vérification d'email</div>
                  <div class="tile-sub">Tous les comptes sont vérifiés pour collaborer en confiance.</div>
                </div>
              </div>
              <div class="feature-tile">
                <div class="tile-icon t-honey"><i class="bi bi-globe2"></i></div>
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

  <footer class="sb-footer">
    © <?= date('Y') ?> <strong>SkillBridge</strong> — Tous droits réservés.
  </footer>

  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script>
    if (typeof AOS !== 'undefined') AOS.init({ duration: 600, easing: 'ease-out-cubic', once: true });
  </script>

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
