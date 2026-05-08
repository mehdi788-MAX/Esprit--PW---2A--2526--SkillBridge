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
      min-height: calc(100vh - 64px);
      padding: 60px 0;
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
    .auth-card .form-control {
      border-radius:12px; padding:13px 14px; border:1px solid #e2e8f0;
      transition: all .18s; font-size:.95rem;
    }
    .auth-card .form-control:focus {
      border-color: var(--sb-blue); box-shadow: 0 0 0 4px rgba(37,99,235,.12);
    }
    .auth-card .input-group .form-control { border-right:none; }
    .auth-card .input-group .btn { border-radius:0 12px 12px 0; border:1px solid #e2e8f0; border-left:none; background:#fff; color:#64748b; }
    .auth-card .form-check-input:checked { background-color: var(--sb-blue); border-color: var(--sb-blue); }

    .btn-gradient {
      background: linear-gradient(135deg, var(--sb-orange), var(--sb-blue));
      color:#fff !important; font-weight:600; padding:13px 24px; border-radius:14px;
      border:none; transition: all .2s ease;
    }
    .btn-gradient:hover { transform: translateY(-2px); box-shadow: 0 14px 30px -10px rgba(37,99,235,.45); color:#fff; }

    .oauth-btn {
      width:100%; padding:11px 16px; border-radius:12px; border:1px solid #e2e8f0;
      background:#fff; color:#0f172a; font-weight:600; font-size:.92rem;
      display:flex; align-items:center; justify-content:center; gap:8px;
      transition: all .18s ease; text-decoration:none;
    }
    .oauth-btn:hover { transform: translateY(-2px); box-shadow:0 8px 20px -8px rgba(15,23,42,.16); color:#0f172a; border-color:#cbd5e1; }
    .oauth-btn.google  i { color:#ea4335; }
    .oauth-btn.github  i { color:#0f172a; }
    .oauth-btn.discord i { color:#5865F2; }
    .oauth-btn.face    { border:1px dashed #7c3aed; color:#7c3aed; background: rgba(124,58,237,.04); }
    .oauth-btn.face i  { color:#7c3aed; }

    .or-divider { display:flex; align-items:center; gap:12px; margin: 18px 0 14px; color:#94a3b8; font-size:.82rem; font-weight:500; }
    .or-divider::before, .or-divider::after { content:''; flex:1; height:1px; background:#e2e8f0; }

    /* Right value-prop column */
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
          <li><a href="login.php" class="active">Connexion</a></li>
          <li><a href="register.php">Inscription</a></li>
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
          <div class="col-lg-6 col-xl-5" data-aos="fade-right">
            <div class="auth-card">
              <span class="section-tag">Bienvenue</span>
              <h1 class="auth-title display-5 mt-3 mb-2">Se <span class="accent">reconnecter</span>.</h1>
              <p class="text-muted mb-4">Entrez vos identifiants pour accéder à votre espace SkillBridge.</p>

              <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-start gap-3" role="alert" style="border-radius:12px; border:1px solid #d1fae5;">
                  <i class="bi bi-envelope-check fs-4 text-success mt-1"></i>
                  <div>
                    <h6 class="fw-bold mb-1">Compte créé avec succès !</h6>
                    <p class="mb-0 small"><?= htmlspecialchars($success) ?></p>
                  </div>
                </div>
              <?php endif; ?>

              <?php if ($error === 'desactivated'): ?>
                <div class="alert alert-warning d-flex align-items-start gap-3" role="alert" style="border-radius:12px;">
                  <i class="bi bi-shield-exclamation fs-3 text-warning mt-1"></i>
                  <div>
                    <h6 class="fw-bold mb-1">Compte désactivé</h6>
                    <p class="mb-0 small">Votre compte a été désactivé par l'administrateur. Contactez <a href="mailto:admin@skillbridge.com" class="fw-semibold">admin@skillbridge.com</a>.</p>
                  </div>
                </div>
              <?php elseif ($error === 'unverified'): ?>
                <div class="alert alert-warning d-flex align-items-start gap-3" role="alert" style="border-radius:12px;">
                  <i class="bi bi-envelope-exclamation fs-3 text-warning mt-1"></i>
                  <div>
                    <h6 class="fw-bold mb-1">Email non vérifié</h6>
                    <p class="mb-0 small">Vérifiez votre boîte email et cliquez sur le lien de confirmation.</p>
                  </div>
                </div>
              <?php elseif ($error): ?>
                <div class="alert alert-danger" style="border-radius:12px;"><?= htmlspecialchars($error) ?></div>
              <?php endif; ?>

              <div id="js-errors" class="alert alert-danger d-none" style="border-radius:12px;"></div>

              <form id="loginForm" action="../../../controller/utilisateurcontroller.php" method="POST" novalidate>
                <input type="hidden" name="action" value="login">

                <div class="mb-3">
                  <label for="email" class="form-label">Adresse email</label>
                  <input type="text" name="email" id="email" class="form-control" placeholder="vous@exemple.com">
                  <div id="email-error" class="text-danger mt-1 small" style="display:none;"></div>
                </div>

                <div class="mb-3">
                  <label for="password" class="form-label">Mot de passe</label>
                  <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Votre mot de passe">
                    <button class="btn" type="button" id="togglePassword"><i class="bi bi-eye" id="eyeIcon"></i></button>
                  </div>
                  <div id="password-error" class="text-danger mt-1 small" style="display:none;"></div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label small" for="remember">Se souvenir de moi</label>
                  </div>
                  <a href="forgot-password.php" class="text-decoration-none small fw-semibold" style="color:var(--sb-blue);">Mot de passe oublié&nbsp;?</a>
                </div>

                <button type="submit" class="btn btn-gradient w-100">
                  <i class="bi bi-box-arrow-in-right me-1"></i> Se connecter
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

                <p class="text-center mb-0 mt-4 small text-muted">
                  Pas encore de compte ? <a href="register.php" class="fw-semibold text-decoration-none" style="color:var(--sb-orange);">Créer un compte</a>
                </p>

              </form>
            </div>
          </div>

          <!-- RIGHT — Value prop -->
          <div class="col-lg-6 col-xl-5 d-none d-lg-block" data-aos="fade-left">
            <span class="section-tag" style="background: rgba(249,115,22,.1); color: var(--sb-orange);">
              <i class="bi bi-stars me-1"></i> Marketplace freelance
            </span>
            <h2 class="display-5 mt-3 mb-3" style="font-weight:800; letter-spacing:-.01em; color: var(--sb-dark);">
              Continuez votre <span style="background: linear-gradient(90deg, var(--sb-orange), var(--sb-blue)); -webkit-background-clip:text; background-clip:text; color:transparent;">parcours</span>.
            </h2>
            <p class="text-muted lead mb-4">
              Reprenez vos conversations, mettez à jour votre profil et collaborez avec les meilleurs talents.
            </p>

            <div class="d-flex flex-column gap-3">
              <div class="feature-tile">
                <div class="tile-icon" style="background:rgba(37,99,235,.1); color:var(--sb-blue);"><i class="bi bi-chat-dots-fill"></i></div>
                <div>
                  <div class="tile-title">Messagerie temps réel</div>
                  <div class="tile-sub">Messages, fichiers, photos, réactions emoji.</div>
                </div>
              </div>
              <div class="feature-tile">
                <div class="tile-icon" style="background:rgba(249,115,22,.1); color:var(--sb-orange);"><i class="bi bi-bell-fill"></i></div>
                <div>
                  <div class="tile-title">Notifications instantanées</div>
                  <div class="tile-sub">Cloche + toasts dès qu'un message vous concerne.</div>
                </div>
              </div>
              <div class="feature-tile">
                <div class="tile-icon" style="background:rgba(124,58,237,.1); color:#7c3aed;"><i class="bi bi-shield-lock-fill"></i></div>
                <div>
                  <div class="tile-title">Authentification multi-modes</div>
                  <div class="tile-sub">OAuth Google / GitHub / Discord, ou reconnaissance faciale.</div>
                </div>
              </div>
              <div class="feature-tile">
                <div class="tile-icon" style="background:rgba(16,185,129,.1); color:#10b981;"><i class="bi bi-person-badge-fill"></i></div>
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
        <div class="modal-content" style="border-radius:18px; border:none;">
          <div class="modal-header" style="border-bottom:1px solid #f1f5f9;">
            <h5 class="modal-title fw-bold"><i class="bi bi-camera-video me-2" style="color:#7c3aed;"></i>Reconnaissance faciale</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body text-center">
            <div id="faceStatus" class="alert alert-info mb-3" style="border-radius:12px;">Entrez votre email et activez la caméra.</div>
            <input type="text" id="faceEmail" class="form-control text-center mb-3" placeholder="Votre adresse email" style="border-radius:12px; padding:12px;">
            <button class="btn btn-outline-secondary mb-3" id="btnStartCamera" style="border-radius:12px;">
              <i class="bi bi-camera me-1"></i> Activer la caméra
            </button>
            <div class="position-relative d-inline-block">
              <video id="faceVideo" width="320" height="240" style="border-radius:14px; display:none; border:3px solid #7c3aed;"></video>
              <canvas id="faceCanvas" style="position:absolute; top:0; left:0; display:none;"></canvas>
            </div>
            <button class="btn w-100 mt-3" id="btnVerifyFace" style="display:none; background:#10b981; color:#fff; font-weight:600; border-radius:12px; padding:12px;">
              <i class="bi bi-check-circle me-1"></i> Vérifier mon visage
            </button>
          </div>
        </div>
      </div>
    </div>

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
    setStatus('Chargement des modèles IA...', 'info');
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
    setStatus('Chargement des modèles IA...', 'info');
    try {
      await loadModels();
      stream = await navigator.mediaDevices.getUserMedia({ video: true });
      const v = document.getElementById('faceVideo');
      v.srcObject = stream; v.play(); v.style.display = 'block';
      document.getElementById('faceCanvas').style.display = 'block';
      document.getElementById('btnVerifyFace').style.display = 'block';
      setStatus('Caméra activée. Placez votre visage face à la caméra puis cliquez "Vérifier".', 'success');
    } catch (e) { setStatus("Impossible d'accéder à la caméra.", 'danger'); }
  });

  document.getElementById('btnVerifyFace').addEventListener('click', async function () {
    const email = document.getElementById('faceEmail').value.trim();
    if (!email) { setStatus('Email requis.', 'danger'); return; }
    setStatus('Récupération de votre photo...', 'info');
    const fd = new FormData(); fd.append('email', email);
    const res = await fetch('<?= $BASE ?>/controller/get_photo.php', { method:'POST', body:fd });
    const data = await res.json();
    if (!data.success) { setStatus(data.message === 'desactivated' ? "Votre compte a été désactivé." : data.message, 'danger'); return; }

    setStatus('Analyse du visage en cours...', 'info');
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
    el.className = 'alert alert-' + type + ' mb-3';
    el.style.borderRadius = '12px';
    el.innerHTML = msg;
  }
  </script>
</body>
</html>
