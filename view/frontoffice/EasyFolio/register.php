<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Inscription - SkillBridge</title>

  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Noto+Sans:wght@400;600;700&family=Questrial:wght@400&display=swap" rel="stylesheet">

  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
</head>

<body class="index-page">

  <header id="header" class="header d-flex align-items-center sticky-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
      <a href="index.html" class="logo d-flex align-items-center me-auto me-xl-0">
        <h1 class="sitename">SkillBridge</h1>
      </a>
      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.html">Accueil</a></li>
          <li><a href="login.php">Connexion</a></li>
          <li><a href="register.php" class="active">Inscription</a></li>
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
    <section class="contact section light-background" style="min-height: 85vh; display:flex; align-items:center;">
      <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row justify-content-center">
          <div class="col-lg-8">

            <div class="text-center mb-5" data-aos="fade-up">
              <div class="section-category mb-3">Rejoignez-nous</div>
              <h2 class="display-5 mb-3">Créer un compte</h2>
              <p class="lead">Inscrivez-vous sur SkillBridge et commencez à collaborer avec des freelancers du monde entier.</p>
            </div>

            <div class="contact-form card" data-aos="fade-up" data-aos-delay="200">
              <div class="card-body p-4 p-lg-5">

                <?php
                session_start();
                if (isset($_SESSION['error'])) {
                  echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
                  unset($_SESSION['error']);
                }
                if (isset($_SESSION['success'])) {
                  echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                  unset($_SESSION['success']);
                }
                ?>

                <div id="js-errors" class="alert alert-danger" style="display:none;"></div>

                <form id="registerForm" action="../../../controller/utilisateurcontroller.php" method="POST" novalidate>
                  <input type="hidden" name="action" value="register">

                  <div class="row gy-4">

                    <div class="col-md-6">
                      <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                      <input type="text" name="nom" id="nom" class="form-control" placeholder="Votre nom">
                      <div id="nom-error" class="text-danger mt-1" style="font-size:0.85rem; display:none;"></div>
                    </div>

                    <div class="col-md-6">
                      <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                      <input type="text" name="prenom" id="prenom" class="form-control" placeholder="Votre prénom">
                      <div id="prenom-error" class="text-danger mt-1" style="font-size:0.85rem; display:none;"></div>
                    </div>

                    <div class="col-12">
                      <label for="email" class="form-label">Adresse Email <span class="text-danger">*</span></label>
                      <input type="text" name="email" id="email" class="form-control" placeholder="example@email.com">
                      <div id="email-error" class="text-danger mt-1" style="font-size:0.85rem; display:none;"></div>
                    </div>

                    <div class="col-md-6">
                      <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                      <input type="password" name="password" id="password" class="form-control" placeholder="Minimum 8 caractères">
                      <div id="password-error" class="text-danger mt-1" style="font-size:0.85rem; display:none;"></div>
                    </div>

                    <div class="col-md-6">
                      <label for="confirm_password" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                      <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Répétez le mot de passe">
                      <div id="confirm-error" class="text-danger mt-1" style="font-size:0.85rem; display:none;"></div>
                    </div>

                    <div class="col-12">
                      <label for="role" class="form-label">Je suis <span class="text-danger">*</span></label>
                      <select name="role" id="role" class="form-control">
                        <option value="">Choisissez votre rôle</option>
                        <option value="freelancer">Freelancer</option>
                        <option value="client">Client</option>
                      </select>
                      <div id="role-error" class="text-danger mt-1" style="font-size:0.85rem; display:none;"></div>
                    </div>

                    <div class="col-12">
                      <label for="telephone" class="form-label">Téléphone</label>
                      <input type="text" name="telephone" id="telephone" class="form-control" placeholder="+216 XX XXX XXX">
                    </div>

                    <div class="col-12 text-center">
                      <button type="submit" class="btn btn-submit w-100">S'inscrire</button>
                    </div>

                    <div class="col-12 text-center">
                      <p class="mb-0">Vous avez déjà un compte ? <a href="login.php">Se connecter</a></p>
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

  <footer id="footer" class="footer">
    <div class="container">
      <div class="copyright text-center">
        <p>© <span>Copyright</span> <strong class="px-1 sitename">SkillBridge</strong> <span>All Rights Reserved</span></p>
      </div>
    </div>
  </footer>

  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/js/main.js"></script>

  <script>
    document.getElementById('registerForm').addEventListener('submit', function(e) {

      let valid = true;

      // Reset
      ['nom', 'prenom', 'email', 'password', 'confirm_password', 'role'].forEach(function(id) {
        const field = document.getElementById(id);
        if (field) field.classList.remove('is-invalid', 'is-valid');
      });
      ['nom-error','prenom-error','email-error','password-error','confirm-error','role-error'].forEach(function(id) {
        const el = document.getElementById(id);
        if (el) { el.textContent = ''; el.style.display = 'none'; }
      });

      function showError(fieldId, errorId, msg) {
        const field = document.getElementById(fieldId);
        const err   = document.getElementById(errorId);
        if (field) field.classList.add('is-invalid');
        if (err)   { err.textContent = msg; err.style.display = 'block'; }
        valid = false;
      }

      const nom             = document.getElementById('nom').value.trim();
      const prenom          = document.getElementById('prenom').value.trim();
      const email           = document.getElementById('email').value.trim();
      const password        = document.getElementById('password').value;
      const confirmPassword = document.getElementById('confirm_password').value;
      const role            = document.getElementById('role').value;
      const emailRegex      = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (nom === '')
        showError('nom', 'nom-error', 'Le nom est obligatoire.');

      if (prenom === '')
        showError('prenom', 'prenom-error', 'Le prénom est obligatoire.');

      if (email === '')
        showError('email', 'email-error', "L'email est obligatoire.");
      else if (!emailRegex.test(email))
        showError('email', 'email-error', 'Format invalide (ex: nom@email.com).');

      if (password === '')
        showError('password', 'password-error', 'Le mot de passe est obligatoire.');
      else if (password.length < 8)
        showError('password', 'password-error', 'Minimum 8 caractères.');

      if (confirmPassword === '')
        showError('confirm_password', 'confirm-error', 'Veuillez confirmer le mot de passe.');
      else if (password !== confirmPassword)
        showError('confirm_password', 'confirm-error', 'Les mots de passe ne correspondent pas.');

      if (role === '')
        showError('role', 'role-error', 'Veuillez choisir un rôle.');

      if (!valid) e.preventDefault();
    });
  </script>

</body>
</html>

