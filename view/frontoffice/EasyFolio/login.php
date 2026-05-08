<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Connexion - SkillBridge</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900&family=Noto+Sans:ital,wght@0,100;0,400;0,700&family=Questrial:wght@400&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">
</head>

<body class="index-page">

  <!-- Header -->
  <header id="header" class="header d-flex align-items-center sticky-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

      <a href="index.html" class="logo d-flex align-items-center me-auto me-xl-0">
        <h1 class="sitename">SkillBridge</h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.html">Accueil</a></li>
          <li><a href="login.php" class="active">Connexion</a></li>
          <li><a href="register.php">Inscription</a></li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>

      <div class="header-social-links">
        <a href="#" class="twitter"><i class="bi bi-twitter-x"></i></a>
        <a href="#" class="facebook"><i class="bi bi-facebook"></i></a>
        <a href="#" class="instagram"><i class="bi bi-instagram"></i></a>
        <a href="#" class="linkedin"><i class="bi bi-linkedin"></i></a>
      </div>

    </div>
  </header>

  <main class="main">

    <!-- Login Section -->
    <section class="contact section light-background" style="min-height: 85vh; display:flex; align-items:center;">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row justify-content-center">
          <div class="col-lg-5">

            <!-- Section Title -->
            <div class="text-center mb-5" data-aos="fade-up">
              <div class="section-category mb-3">Bienvenue</div>
              <h2 class="display-5 mb-3">Se connecter</h2>
              <p class="lead">Connectez-vous à votre compte SkillBridge.</p>
            </div>

            <div class="contact-form card" data-aos="fade-up" data-aos-delay="200">
              <div class="card-body p-4 p-lg-5">

                <?php
                session_start();
                require_once __DIR__ . '/../../../config.php';
                $BASE    = base_url(); // racine portable
                $error   = $_SESSION['error'] ?? null;
                $success = $_SESSION['success'] ?? null;
                unset($_SESSION['error'], $_SESSION['success']);
                ?>

                <?php if ($success): ?>
                  <div class="alert alert-success d-flex align-items-start gap-3" role="alert">
                    <i class="bi bi-envelope-check fs-4 text-success mt-1"></i>
                    <div>
                      <h6 class="fw-bold mb-1">Compte créé avec succès !</h6>
                      <p class="mb-0"><?= htmlspecialchars($success) ?></p>
                    </div>
                  </div>
                <?php endif; ?>

                <?php if ($error === 'desactivated'): ?>
                  <div class="alert alert-warning d-flex align-items-start gap-3" role="alert">
                    <i class="bi bi-shield-exclamation fs-3 text-warning mt-1"></i>
                    <div>
                      <h6 class="fw-bold mb-1">Compte désactivé</h6>
                      <p class="mb-1">Votre compte a été désactivé par l'administrateur.</p>
                      <p class="mb-0">Pour obtenir de l'aide, contactez-nous à :
                        <a href="mailto:admin@skillbridge.com" class="fw-bold text-dark">
                          admin@skillbridge.com
                        </a>
                      </p>
                    </div>
                  </div>
                <?php elseif ($error === 'unverified'): ?>
                  <div class="alert alert-warning d-flex align-items-start gap-3" role="alert">
                    <i class="bi bi-envelope-exclamation fs-3 text-warning mt-1"></i>
                    <div>
                      <h6 class="fw-bold mb-1">Email non vérifié</h6>
                      <p class="mb-0">Vérifiez votre boîte email et cliquez sur le lien de confirmation avant de vous connecter.</p>
                    </div>
                  </div>
                <?php elseif ($error): ?>
                  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Conteneur erreurs JS -->
                <div id="js-errors" class="alert alert-danger d-none"></div>

                <form id="loginForm" action="../../../controller/utilisateurcontroller.php" method="POST" novalidate>
                  <input type="hidden" name="action" value="login">

                  <div class="row gy-4">

                    <!-- Email -->
                    <div class="col-12">
                      <label for="email" class="form-label">Adresse Email <span class="text-danger">*</span></label>
                      <input type="text" name="email" id="email" class="form-control" placeholder="example@email.com">
                      <div id="email-error" class="text-danger mt-1" style="font-size:0.85rem; display:none;"></div>
                    </div>

                    <!-- Mot de passe -->
                    <div class="col-12">
                      <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Votre mot de passe">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                          <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                      </div>
                      <div id="password-error" class="text-danger mt-1" style="font-size:0.85rem; display:none;"></div>
                    </div>

                    <!-- Se souvenir de moi -->
                    <div class="col-12 d-flex justify-content-between align-items-center">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Se souvenir de moi</label>
                      </div>
                      <a href="forgot-password.php" class="text-decoration-none">Mot de passe oublié ?</a>
                    </div>

                    <!-- Submit -->
                    <div class="col-12 text-center">
                      <button type="submit" class="btn btn-submit w-100">Se connecter</button>
                    </div>

                    <!-- Séparateur -->
                    <div class="col-12 text-center my-2">
                      <span class="text-muted" style="font-size:0.9rem;">— ou continuer avec —</span>
                    </div>

                    <!-- Boutons OAuth -->
                    <div class="col-12 d-flex flex-column gap-2">

                      <a href="<?= $BASE ?>/controller/oauthcontroller.php?provider=google"
                         class="btn btn-outline-danger w-100">
                        <i class="bi bi-google me-2"></i> Continuer avec Google
                      </a>

                      <a href="<?= $BASE ?>/controller/oauthcontroller.php?provider=github"
                         class="btn btn-outline-dark w-100">
                        <i class="bi bi-github me-2"></i> Continuer avec GitHub
                      </a>

                      <a href="<?= $BASE ?>/controller/oauthcontroller.php?provider=discord"
                         class="btn w-100" style="border:1px solid #5865F2; color:#5865F2;">
                        <i class="bi bi-discord me-2"></i> Continuer avec Discord
                      </a>

                    </div>

                    <!-- Bouton reconnaissance faciale -->
                    <div class="col-12 text-center mt-2">
                      <button type="button" class="btn w-100" id="btnFaceLogin"
                              style="border:1px solid #6f42c1; color:#6f42c1;">
                        <i class="bi bi-camera-video me-2"></i> Se connecter avec mon visage
                      </button>
                    </div>

                    <!-- Lien inscription -->
                    <div class="col-12 text-center">
                      <p class="mb-0">Vous n'avez pas de compte ? <a href="register.php">S'inscrire</a></p>
                    </div>

                  </div>
                </form>

              </div>
            </div>

          </div>
        </div>

      </div>

    </section>

    <!-- Modal Webcam -->
    <div class="modal fade" id="faceModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="bi bi-camera-video me-2"></i>Reconnaissance Faciale
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body text-center">

            <div id="faceStatus" class="alert alert-info mb-3">
              Entrez votre email et activez la caméra.
            </div>

            <div class="mb-3">
              <input type="text" id="faceEmail" class="form-control text-center"
                     placeholder="Votre adresse email">
            </div>

            <button class="btn btn-outline-secondary mb-3" id="btnStartCamera">
              <i class="bi bi-camera me-1"></i> Activer la caméra
            </button>

            <div class="position-relative d-inline-block">
              <video id="faceVideo" width="320" height="240"
                     style="border-radius:12px; display:none; border:3px solid #6f42c1;"></video>
              <canvas id="faceCanvas" style="position:absolute; top:0; left:0; display:none;"></canvas>
            </div>

            <div class="mt-3">
              <button class="btn btn-success w-100" id="btnVerifyFace" style="display:none;">
                <i class="bi bi-check-circle me-1"></i> Vérifier mon visage
              </button>
            </div>

          </div>
        </div>
      </div>
    </div>

  </main>

  <!-- Footer -->
  <footer id="footer" class="footer">
    <div class="container">
      <div class="copyright text-center">
        <p>© <span>Copyright</span> <strong class="px-1 sitename">SkillBridge</strong> <span>All Rights Reserved</span></p>
      </div>
      <div class="social-links d-flex justify-content-center">
        <a href=""><i class="bi bi-twitter-x"></i></a>
        <a href=""><i class="bi bi-facebook"></i></a>
        <a href=""><i class="bi bi-instagram"></i></a>
        <a href=""><i class="bi bi-linkedin"></i></a>
      </div>
    </div>
  </footer>

  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/js/main.js"></script>

  <script>
  document.getElementById('loginForm').addEventListener('submit', function (e) {

    let valid = true;

    const emailField    = document.getElementById('email');
    const passwordField = document.getElementById('password');
    const emailError    = document.getElementById('email-error');
    const passwordError = document.getElementById('password-error');

    function resetField(field, errorDiv) {
      field.classList.remove('is-invalid', 'is-valid');
      errorDiv.textContent = '';
      errorDiv.style.display = 'none';
    }

    resetField(emailField, emailError);
    resetField(passwordField, passwordError);

    function showError(field, errorDiv, message) {
      field.classList.add('is-invalid');
      errorDiv.textContent = message;
      errorDiv.style.display = 'block';
      field.focus();
      valid = false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const email      = emailField.value.trim();
    const password   = passwordField.value;

    if (email === '') {
      showError(emailField, emailError, "L'adresse email est obligatoire.");
    } else if (!emailRegex.test(email)) {
      showError(emailField, emailError, "Format invalide (ex: nom@email.com).");
    } else {
      emailField.classList.add('is-valid');
    }

    if (password === '') {
      showError(passwordField, passwordError, "Le mot de passe est obligatoire.");
    } else if (password.length < 6) {
      showError(passwordField, passwordError, "Minimum 6 caractères.");
    } else {
      passwordField.classList.add('is-valid');
    }

    if (!valid) {
      e.preventDefault();
    } else {
      document.querySelector('button[type="submit"]').disabled = true;
    }
  });

  ['email', 'password'].forEach(function (id) {
    const field    = document.getElementById(id);
    const errorDiv = document.getElementById(id + '-error');
    field.addEventListener('input', function () {
      field.classList.remove('is-invalid');
      errorDiv.textContent = '';
      errorDiv.style.display = 'none';
    });
  });

  document.getElementById('togglePassword').addEventListener('click', function () {
    const pwd  = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    pwd.type = (pwd.type === 'password') ? 'text' : 'password';
    icon.classList.toggle('bi-eye');
    icon.classList.toggle('bi-eye-slash');
  });
  </script>

  <script src="assets/js/face-api.min.js"></script>

  <script>
  const WEIGHTS_URL = 'assets/weights';
  let stream        = null;
  let modelsLoaded  = false;

  document.getElementById('btnFaceLogin').addEventListener('click', function () {
    const emailField = document.getElementById('email');
    if (emailField && emailField.value.trim() !== '') {
      document.getElementById('faceEmail').value = emailField.value.trim();
    }
    const modal = new bootstrap.Modal(document.getElementById('faceModal'));
    modal.show();
  });

  document.getElementById('faceModal').addEventListener('hidden.bs.modal', function () {
    stopCamera();
  });

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
    if (!email) {
      setStatus('Veuillez entrer votre email.', 'danger');
      return;
    }
    setStatus('Chargement des modèles IA...', 'info');
    try {
      await loadModels();
      stream = await navigator.mediaDevices.getUserMedia({ video: true });
      const video = document.getElementById('faceVideo');
      video.srcObject = stream;
      video.play();
      video.style.display = 'block';
      document.getElementById('faceCanvas').style.display = 'block';
      document.getElementById('btnVerifyFace').style.display = 'block';
      setStatus('Caméra activée. Placez votre visage bien en face puis cliquez "Vérifier".', 'success');
    } catch (e) {
      setStatus("Impossible d'accéder à la caméra.", 'danger');
    }
  });

  document.getElementById('btnVerifyFace').addEventListener('click', async function () {
    const email = document.getElementById('faceEmail').value.trim();
    if (!email) {
      setStatus('Email requis.', 'danger');
      return;
    }

    setStatus('Récupération de votre photo...', 'info');

    const formData = new FormData();
    formData.append('email', email);

    const res  = await fetch('<?= $BASE ?>/controller/get_photo.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (!data.success) {
      if (data.message === 'desactivated') {
        setStatus("Votre compte a été désactivé par l'administrateur.", 'danger');
      } else {
        setStatus(data.message, 'danger');
      }
      return;
    }

    setStatus('Analyse du visage en cours...', 'info');

    const profileImg       = await faceapi.fetchImage(data.photo);
    const profileDetection = await faceapi
      .detectSingleFace(profileImg)
      .withFaceLandmarks()
      .withFaceDescriptor();

    if (!profileDetection) {
      setStatus('Aucun visage détecté dans votre photo de profil. Uploadez une photo claire.', 'danger');
      return;
    }

    const video  = document.getElementById('faceVideo');
    const canvas = document.createElement('canvas');
    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);

    const liveDetection = await faceapi
      .detectSingleFace(canvas)
      .withFaceLandmarks()
      .withFaceDescriptor();

    if (!liveDetection) {
      setStatus('Aucun visage détecté. Placez-vous bien face à la caméra.', 'danger');
      return;
    }

    const distance = faceapi.euclideanDistance(
      profileDetection.descriptor,
      liveDetection.descriptor
    );

    console.log('Distance:', distance);

    if (distance < 0.5) {
      setStatus('✅ Visage reconnu ! Connexion en cours...', 'success');
      stopCamera();

      const loginData = new FormData();
      loginData.append('user_id',   data.user_id);
      loginData.append('user_nom',  data.user_nom);
      loginData.append('user_role', data.user_role);

      const loginRes  = await fetch('<?= $BASE ?>/controller/face_login.php', { method: 'POST', body: loginData });
      const loginJson = await loginRes.json();

      if (loginJson.success) {
        window.location.href = loginJson.redirect;
      }
    } else {
      setStatus('❌ Visage non reconnu. Réessayez.', 'danger');
    }
  });

  function stopCamera() {
    if (stream) {
      stream.getTracks().forEach(t => t.stop());
      stream = null;
    }
    document.getElementById('faceVideo').style.display  = 'none';
    document.getElementById('btnVerifyFace').style.display = 'none';
  }

  function setStatus(msg, type) {
    const el      = document.getElementById('faceStatus');
    el.className  = 'alert alert-' + type + ' mb-3';
    el.innerHTML  = msg;
  }
  </script>

</body>
</html>