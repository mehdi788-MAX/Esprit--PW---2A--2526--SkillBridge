<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Inscription - SkillBridge</title>
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

    <!-- Register Section -->
    <section class="contact section light-background" style="min-height: 85vh; display:flex; align-items:center;">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row justify-content-center">
          <div class="col-lg-8">

            <!-- Section Title -->
            <div class="text-center mb-5" data-aos="fade-up">
              <div class="section-category mb-3">Rejoignez-nous</div>
              <h2 class="display-5 mb-3">Créer un compte</h2>
              <p class="lead">Inscrivez-vous sur SkillBridge et commencez à collaborer avec des freelancers du monde entier.</p>
            </div>

            <div class="contact-form card" data-aos="fade-up" data-aos-delay="200">
              <div class="card-body p-4 p-lg-5">

                <?php
                // Affichage des erreurs
                if (isset($error)) {
                  echo '<div class="alert alert-danger">' . $error . '</div>';
                }
                if (isset($success)) {
                  echo '<div class="alert alert-success">' . $success . '</div>';
                }
                ?>

                <form action="../../controllers/UtilisateurController.php" method="POST">
                  <input type="hidden" name="action" value="register">

                  <div class="row gy-4">

                    <!-- Nom -->
                    <div class="col-md-6">
                      <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                      <input type="text" name="nom" id="nom" class="form-control" placeholder="Votre nom" required>
                    </div>

                    <!-- Prénom -->
                    <div class="col-md-6">
                      <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                      <input type="text" name="prenom" id="prenom" class="form-control" placeholder="Votre prénom" required>
                    </div>

                    <!-- Email -->
                    <div class="col-12">
                      <label for="email" class="form-label">Adresse Email <span class="text-danger">*</span></label>
                      <input type="email" name="email" id="email" class="form-control" placeholder="example@email.com" required>
                    </div>

                    <!-- Mot de passe -->
                    <div class="col-md-6">
                      <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                      <input type="password" name="password" id="password" class="form-control" placeholder="Minimum 8 caractères" required minlength="8">
                    </div>

                    <!-- Confirmer mot de passe -->
                    <div class="col-md-6">
                      <label for="confirm_password" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                      <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Répétez le mot de passe" required>
                    </div>

                    <!-- Rôle -->
                    <div class="col-12">
                      <label for="role" class="form-label">Je suis <span class="text-danger">*</span></label>
                      <select name="role" id="role" class="form-control" required>
                        <option value="" disabled selected>Choisissez votre rôle</option>
                        <option value="freelancer">Freelancer</option>
                        <option value="client">Client</option>
                      </select>
                    </div>

                    <!-- Téléphone -->
                    <div class="col-12">
                      <label for="telephone" class="form-label">Téléphone</label>
                      <input type="tel" name="telephone" id="telephone" class="form-control" placeholder="+216 XX XXX XXX">
                    </div>

                    <!-- Submit -->
                    <div class="col-12 text-center">
                      <button type="submit" class="btn btn-submit w-100">S'inscrire</button>
                    </div>

                    <!-- Lien connexion -->
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
    // Vérification mot de passe côté client
    document.querySelector('form').addEventListener('submit', function(e) {
      const pwd = document.getElementById('password').value;
      const confirm = document.getElementById('confirm_password').value;
      if (pwd !== confirm) {
        e.preventDefault();
        alert('Les mots de passe ne correspondent pas.');
      }
    });
  </script>

</body>

</html>
