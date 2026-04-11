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

                <?php if (isset($error)): ?>
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

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

  <script>
document.getElementById('loginForm').addEventListener('submit', function (e) {

  let valid = true;

  const emailField = document.getElementById('email');
  const passwordField = document.getElementById('password');

  const emailError = document.getElementById('email-error');
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

  const email = emailField.value.trim();
  const password = passwordField.value;

  // Email
  if (email === '') {
    showError(emailField, emailError, "L'adresse email est obligatoire.");
  } else if (!emailRegex.test(email)) {
    showError(emailField, emailError, "Format invalide (ex: nom@email.com).");
  } else {
    emailField.classList.add('is-valid');
  }

  // Password
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

// Effacer erreurs en tapant
['email', 'password'].forEach(function (id) {
  const field = document.getElementById(id);
  const errorDiv = document.getElementById(id + '-error');

  field.addEventListener('input', function () {
    field.classList.remove('is-invalid');
    errorDiv.textContent = '';
    errorDiv.style.display = 'none';
  });
});

// Toggle password
document.getElementById('togglePassword').addEventListener('click', function () {
  const pwd = document.getElementById('password');
  const icon = document.getElementById('eyeIcon');

  pwd.type = (pwd.type === 'password') ? 'text' : 'password';

  icon.classList.toggle('bi-eye');
  icon.classList.toggle('bi-eye-slash');
});
</script>