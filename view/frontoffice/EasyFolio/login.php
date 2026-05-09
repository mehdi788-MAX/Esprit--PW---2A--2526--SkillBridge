<?php
session_start();
require_once __DIR__ . '/../../../config.php';
$BASE = base_url();

// OAuth providers (masque les boutons sans client_id dans .env)
$oauthCfg = @require __DIR__ . '/../../../config/oauth.php';
$oauthHas = function ($p) use ($oauthCfg) {
    return is_array($oauthCfg) && !empty($oauthCfg[$p]['client_id']);
};
$hasAnyOAuth = $oauthHas('google') || $oauthHas('github') || $oauthHas('discord');

$error   = $_SESSION['error']   ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Connexion — SkillBridge</title>
  <meta name="description" content="Connectez-vous à votre compte SkillBridge.">

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

    .auth-grid { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 60px; align-items: center; }
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
    .auth-card .form-control {
      border-radius: 12px; padding: 13px 14px; font-size: .96rem;
      border: 1px solid var(--rule); background: var(--paper);
      transition: border-color .18s, box-shadow .18s;
      width: 100%;
    }
    .auth-card .form-control:focus {
      outline: none;
      border-color: var(--sage);
      box-shadow: 0 0 0 4px rgba(31,95,77,.12);
    }
    .auth-card .form-control.is-invalid { border-color: #DC2626; }
    .auth-card .pwd-wrap { position: relative; }
    .auth-card .pwd-wrap .form-control { padding-right: 48px; }
    .auth-card .pwd-toggle {
      position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
      width: 36px; height: 36px; border-radius: 9px; border: none;
      background: transparent; color: var(--ink-mute);
      display: inline-flex; align-items: center; justify-content: center;
      cursor: pointer; transition: all .15s ease; padding: 0;
    }
    .auth-card .pwd-toggle:hover { background: var(--bg); color: var(--ink); }
    .auth-card .form-check-input:checked { background-color: var(--sage); border-color: var(--sage); }

    /* ----------------- Buttons ----------------- */
    .btn-sage {
      display: flex; align-items: center; justify-content: center; gap: 10px;
      width: 100%; padding: 14px 22px; border-radius: 12px; border: none;
      background: var(--sage); color: var(--paper) !important;
      font-weight: 700; font-size: 1rem;
      transition: all .2s ease; text-decoration: none; cursor: pointer;
    }
    .btn-sage:hover { background: var(--sage-d); transform: translateY(-2px); box-shadow: 0 14px 28px -12px rgba(31,95,77,.4); }
    .btn-sage:disabled { opacity: .6; cursor: not-allowed; transform: none; }

    .oauth-btn {
      width: 100%; padding: 11px 16px; border-radius: 12px; border: 1px solid var(--rule);
      background: var(--paper); color: var(--ink); font-weight: 600; font-size: .92rem;
      display: flex; align-items: center; justify-content: center; gap: 10px;
      transition: all .18s ease; text-decoration: none; cursor: pointer;
    }
    .oauth-btn:hover { border-color: var(--ink); transform: translateY(-2px); color: var(--ink); }
    .oauth-btn.google  i { color: #EA4335; }
    .oauth-btn.github  i { color: var(--ink); }
    .oauth-btn.discord i { color: #5865F2; }
    .oauth-btn.face {
      background: var(--sage-soft); color: var(--sage);
      border: 1px dashed var(--sage);
    }
    .oauth-btn.face:hover { background: var(--sage); color: var(--paper); border-color: var(--sage); }
    .oauth-btn.face i { color: inherit; }

    .or-divider { display:flex; align-items:center; gap:12px; margin: 18px 0 14px; color: var(--ink-soft); font-size:.82rem; font-weight: 500; }
    .or-divider::before, .or-divider::after { content:''; flex:1; height:1px; background: var(--rule); }

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
      display: flex; align-items: flex-start; gap: 12px;
    }
    .alert-success { background: var(--sage-soft); border-color: rgba(31,95,77,.2); color: var(--sage-d); }
    .alert-warning { background: var(--honey-soft); border-color: rgba(224,176,51,.3); color: #7a4f08; }
    .alert-danger  { background: #FEF2F2; border-color: #FECACA; color: #991B1B; }

    /* ----------------- Modal (face login) ----------------- */
    .modal-content { border: 1px solid var(--rule) !important; border-radius: 22px !important; background: var(--paper); }
    .modal-header  { border-bottom: 1px solid var(--rule); padding: 20px 24px; }
    .modal-body    { padding: 22px 24px 28px; }
    .modal-title   { font-weight: 800; color: var(--ink); }

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
        <a href="login.php" class="active">Connexion</a>
        <a href="register.php">Inscription</a>
      </nav>
      <a href="register.php" class="sb-cta">
        <span>Créer un compte</span><i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </header>

  <main>
    <section class="auth-bg">
      <div class="blob sage  blob-1"></div>
      <div class="blob honey blob-2"></div>

      <div class="container">
        <div class="auth-grid">

          <!-- LEFT — Form -->
          <div data-aos="fade-right">
            <div class="auth-card">
              <span class="eyebrow"><span class="dot"></span> Bienvenue</span>
              <h1 class="display-x mt-3 mb-2">Se <span class="accent">reconnecter</span>.</h1>
              <p class="lead-x mb-4" style="font-size:.95rem;">Entrez vos identifiants pour accéder à votre espace.</p>

              <?php if ($success): ?>
                <div class="alert alert-success">
                  <i class="bi bi-envelope-check fs-4 mt-1"></i>
                  <div>
                    <div style="font-weight:700; margin-bottom:2px;">Compte créé avec succès !</div>
                    <div style="font-size:.88rem;"><?= htmlspecialchars($success) ?></div>
                  </div>
                </div>
              <?php endif; ?>

              <?php if ($error === 'desactivated'): ?>
                <div class="alert alert-warning">
                  <i class="bi bi-shield-exclamation fs-3 mt-1"></i>
                  <div>
                    <div style="font-weight:700; margin-bottom:2px;">Compte désactivé</div>
                    <div style="font-size:.88rem;">Votre compte a été désactivé par l'administrateur. Contactez <a href="mailto:admin@skillbridge.com" style="color:inherit; font-weight:700;">admin@skillbridge.com</a>.</div>
                  </div>
                </div>
              <?php elseif ($error === 'unverified'): ?>
                <div class="alert alert-warning">
                  <i class="bi bi-envelope-exclamation fs-3 mt-1"></i>
                  <div>
                    <div style="font-weight:700; margin-bottom:2px;">Email non vérifié</div>
                    <div style="font-size:.88rem;">Vérifiez votre boîte email et cliquez sur le lien de confirmation.</div>
                  </div>
                </div>
              <?php elseif ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
              <?php endif; ?>

              <div id="js-errors" class="alert alert-danger d-none"></div>

              <form id="loginForm" action="../../../controller/utilisateurcontroller.php" method="POST" novalidate>
                <input type="hidden" name="action" value="login">

                <div class="mb-3">
                  <label for="email" class="form-label">Adresse email</label>
                  <input type="text" name="email" id="email" class="form-control" placeholder="vous@exemple.com">
                  <div id="email-error" class="text-danger mt-1 small" style="display:none;"></div>
                </div>

                <div class="mb-3">
                  <label for="password" class="form-label">Mot de passe</label>
                  <div class="pwd-wrap">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Votre mot de passe">
                    <button class="pwd-toggle" type="button" id="togglePassword" aria-label="Afficher le mot de passe">
                      <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                  </div>
                  <div id="password-error" class="text-danger mt-1 small" style="display:none;"></div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label small" for="remember" style="color: var(--ink-mute);">Se souvenir de moi</label>
                  </div>
                  <a href="forgot-password.php" class="text-decoration-none small fw-semibold" style="color: var(--sage);">Mot de passe oublié&nbsp;?</a>
                </div>

                <button type="submit" class="btn-sage">
                  <i class="bi bi-box-arrow-in-right"></i> Se connecter
                </button>

                <?php if ($hasAnyOAuth): ?>
                  <div class="or-divider">ou continuer avec</div>
                  <div class="d-flex flex-column gap-2">
                    <?php if ($oauthHas('google')): ?>
                      <a href="<?= $BASE ?>/controller/oauthcontroller.php?provider=google" class="oauth-btn google">
                        <i class="bi bi-google"></i> Continuer avec Google
                      </a>
                    <?php endif; ?>
                    <?php if ($oauthHas('github')): ?>
                      <a href="<?= $BASE ?>/controller/oauthcontroller.php?provider=github" class="oauth-btn github">
                        <i class="bi bi-github"></i> Continuer avec GitHub
                      </a>
                    <?php endif; ?>
                    <?php if ($oauthHas('discord')): ?>
                      <a href="<?= $BASE ?>/controller/oauthcontroller.php?provider=discord" class="oauth-btn discord">
                        <i class="bi bi-discord"></i> Continuer avec Discord
                      </a>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>

                <button type="button" class="oauth-btn face mt-2" id="btnFaceLogin">
                  <i class="bi bi-camera-video"></i> Se connecter avec mon visage
                </button>

                <p class="text-center mb-0 mt-4 small" style="color: var(--ink-mute);">
                  Pas encore de compte ? <a href="register.php" class="fw-semibold text-decoration-none" style="color: var(--sage);">Créer un compte</a>
                </p>
              </form>
            </div>
          </div>

          <!-- RIGHT — Value prop -->
          <div class="d-none d-lg-block" data-aos="fade-left">
            <span class="eyebrow honey"><span class="dot"></span> Marketplace freelance</span>
            <h2 class="display-l mt-3 mb-3">Continuez votre <span class="accent">parcours</span>.</h2>
            <p class="lead-x mb-4">
              Reprenez vos conversations, mettez à jour votre profil et collaborez avec les meilleurs talents.
            </p>

            <div class="d-flex flex-column gap-3">
              <div class="feature-tile">
                <div class="tile-icon t-sage"><i class="bi bi-chat-dots-fill"></i></div>
                <div>
                  <div class="tile-title">Messagerie temps réel</div>
                  <div class="tile-sub">Messages, fichiers, photos, réactions emoji.</div>
                </div>
              </div>
              <div class="feature-tile">
                <div class="tile-icon t-honey"><i class="bi bi-bell-fill"></i></div>
                <div>
                  <div class="tile-title">Notifications instantanées</div>
                  <div class="tile-sub">Cloche + toasts dès qu'un message vous concerne.</div>
                </div>
              </div>
              <div class="feature-tile">
                <div class="tile-icon t-sage"><i class="bi bi-shield-lock-fill"></i></div>
                <div>
                  <div class="tile-title">Authentification multi-modes</div>
                  <div class="tile-sub">OAuth Google / GitHub / Discord, ou reconnaissance faciale.</div>
                </div>
              </div>
              <div class="feature-tile">
                <div class="tile-icon t-honey"><i class="bi bi-person-badge-fill"></i></div>
                <div>
                  <div class="tile-title">Profils vérifiés</div>
                  <div class="tile-sub">Email vérifié, photos, bio, compétences, localisation.</div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- Modal Webcam (face login) -->
    <div class="modal fade" id="faceModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="bi bi-camera-video me-2" style="color: var(--sage);"></i>Reconnaissance faciale</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body text-center">
            <div id="faceStatus" class="alert alert-success" style="border-radius:12px; text-align:left;">Entrez votre email et activez la caméra.</div>
            <input type="text" id="faceEmail" class="form-control text-center mb-3" placeholder="Votre adresse email" style="border-radius:12px; padding:12px;">
            <button class="btn-sage" id="btnStartCamera" style="width:auto; padding: 11px 22px; margin-bottom: 16px;">
              <i class="bi bi-camera"></i> Activer la caméra
            </button>
            <div class="position-relative d-inline-block">
              <video id="faceVideo" width="320" height="240" style="border-radius:14px; display:none; border:3px solid var(--sage);"></video>
              <canvas id="faceCanvas" style="position:absolute; top:0; left:0; display:none;"></canvas>
            </div>
            <button class="btn-sage" id="btnVerifyFace" style="display:none; margin-top: 14px; background: var(--sage);">
              <i class="bi bi-check-circle"></i> Vérifier mon visage
            </button>
          </div>
        </div>
      </div>
    </div>
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

  <!-- Form validation -->
  <script>
  document.getElementById('loginForm').addEventListener('submit', function (e) {
    let valid = true;
    const emailField = document.getElementById('email');
    const passwordField = document.getElementById('password');
    const emailError = document.getElementById('email-error');
    const passwordError = document.getElementById('password-error');

    function reset(f, e) { f.classList.remove('is-invalid','is-valid'); e.textContent=''; e.style.display='none'; }
    reset(emailField, emailError); reset(passwordField, passwordError);

    function show(f, e, m) { f.classList.add('is-invalid'); e.textContent=m; e.style.display='block'; valid=false; }

    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const email = emailField.value.trim(); const pwd = passwordField.value;
    if (!email)            show(emailField, emailError, "L'adresse email est obligatoire.");
    else if (!re.test(email)) show(emailField, emailError, "Format invalide (ex : nom@email.com).");
    else emailField.classList.add('is-valid');

    if (!pwd)               show(passwordField, passwordError, "Le mot de passe est obligatoire.");
    else if (pwd.length < 6) show(passwordField, passwordError, "Minimum 6 caractères.");
    else passwordField.classList.add('is-valid');

    if (!valid) e.preventDefault();
    else document.querySelector('button[type="submit"]').disabled = true;
  });

  ['email','password'].forEach(id => {
    const f = document.getElementById(id);
    const e = document.getElementById(id + '-error');
    f.addEventListener('input', () => { f.classList.remove('is-invalid'); e.textContent=''; e.style.display='none'; });
  });

  document.getElementById('togglePassword').addEventListener('click', function () {
    const p = document.getElementById('password');
    const i = document.getElementById('eyeIcon');
    p.type = (p.type === 'password') ? 'text' : 'password';
    i.classList.toggle('bi-eye'); i.classList.toggle('bi-eye-slash');
  });
  </script>

  <!-- Face login -->
  <script src="assets/js/face-api.min.js"></script>
  <script>
  const WEIGHTS_URL = 'assets/weights';
  let stream = null, modelsLoaded = false;

  document.getElementById('btnFaceLogin').addEventListener('click', function () {
    const ef = document.getElementById('email');
    if (ef && ef.value.trim() !== '') document.getElementById('faceEmail').value = ef.value.trim();
    new bootstrap.Modal(document.getElementById('faceModal')).show();
  });
  document.getElementById('faceModal').addEventListener('hidden.bs.modal', stopCamera);

  async function loadModels() {
    if (modelsLoaded) return;
    setStatus('Chargement des modèles IA...', 'warning');
    await Promise.all([
      faceapi.nets.ssdMobilenetv1.loadFromUri(WEIGHTS_URL),
      faceapi.nets.faceLandmark68Net.loadFromUri(WEIGHTS_URL),
      faceapi.nets.faceRecognitionNet.loadFromUri(WEIGHTS_URL),
    ]);
    modelsLoaded = true;
  }

  document.getElementById('btnStartCamera').addEventListener('click', async function () {
    const email = document.getElementById('faceEmail').value.trim();
    if (!email) { setStatus('Veuillez entrer votre email.', 'danger'); return; }
    setStatus('Chargement des modèles IA...', 'warning');
    try {
      await loadModels();
      stream = await navigator.mediaDevices.getUserMedia({ video: true });
      const v = document.getElementById('faceVideo');
      v.srcObject = stream; v.play(); v.style.display = 'block';
      document.getElementById('faceCanvas').style.display = 'block';
      document.getElementById('btnVerifyFace').style.display = 'inline-flex';
      setStatus('Caméra activée. Placez votre visage face à la caméra puis cliquez "Vérifier".', 'success');
    } catch (e) { setStatus("Impossible d'accéder à la caméra.", 'danger'); }
  });

  document.getElementById('btnVerifyFace').addEventListener('click', async function () {
    const email = document.getElementById('faceEmail').value.trim();
    if (!email) { setStatus('Email requis.', 'danger'); return; }
    setStatus('Récupération de votre photo...', 'warning');
    const fd = new FormData(); fd.append('email', email);
    const res = await fetch('<?= $BASE ?>/controller/get_photo.php', { method:'POST', body:fd });
    const data = await res.json();
    if (!data.success) { setStatus(data.message === 'desactivated' ? "Votre compte a été désactivé." : data.message, 'danger'); return; }

    setStatus('Analyse du visage en cours...', 'warning');
    const profileImg = await faceapi.fetchImage(data.photo);
    const profileDet = await faceapi.detectSingleFace(profileImg).withFaceLandmarks().withFaceDescriptor();
    if (!profileDet) { setStatus('Aucun visage détecté dans votre photo de profil.', 'danger'); return; }

    const v = document.getElementById('faceVideo');
    const c = document.createElement('canvas');
    c.width = v.videoWidth; c.height = v.videoHeight;
    c.getContext('2d').drawImage(v, 0, 0);
    const liveDet = await faceapi.detectSingleFace(c).withFaceLandmarks().withFaceDescriptor();
    if (!liveDet) { setStatus('Aucun visage détecté. Placez-vous bien face à la caméra.', 'danger'); return; }

    const dist = faceapi.euclideanDistance(profileDet.descriptor, liveDet.descriptor);
    if (dist < 0.5) {
      setStatus('✅ Visage reconnu ! Connexion en cours...', 'success');
      stopCamera();
      const ld = new FormData();
      ld.append('user_id', data.user_id); ld.append('user_nom', data.user_nom); ld.append('user_role', data.user_role);
      const r = await fetch('<?= $BASE ?>/controller/face_login.php', { method:'POST', body:ld });
      const j = await r.json();
      if (j.success) window.location.href = j.redirect;
    } else {
      setStatus('❌ Visage non reconnu. Réessayez.', 'danger');
    }
  });

  function stopCamera() {
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
    document.getElementById('faceVideo').style.display = 'none';
    document.getElementById('btnVerifyFace').style.display = 'none';
  }
  function setStatus(msg, type) {
    const el = document.getElementById('faceStatus');
    el.className = 'alert alert-' + type;
    el.innerHTML = msg;
  }
  </script>
</body>
</html>
