<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Profil - SkillBridge</title>
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

  <style>
    .profile-avatar {
      width: 130px;
      height: 130px;
      object-fit: cover;
      border-radius: 50%;
      border: 4px solid var(--accent-color, #0ea2bd);
    }
    .profile-avatar-placeholder {
      width: 130px;
      height: 130px;
      border-radius: 50%;
      background: #e0f7fa;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
      color: var(--accent-color, #0ea2bd);
      border: 4px solid var(--accent-color, #0ea2bd);
    }
    .badge-role {
      font-size: 0.85rem;
      padding: 5px 14px;
      border-radius: 20px;
    }
    .info-item .label {
      font-size: 0.8rem;
      color: #888;
      display: block;
      margin-bottom: 2px;
    }
    .info-item .value {
      font-weight: 600;
      font-size: 0.95rem;
    }
    .section-edit-btn {
      font-size: 0.85rem;
    }
  </style>
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
          <li><a href="profil.php" class="active">Mon Profil</a></li>
          <li><a href="../../controllers/UtilisateurController.php?action=logout">Déconnexion</a></li>
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

    <!-- Profil Section -->
    <section class="about section light-background" style="min-height: 85vh;">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <!-- Section Title -->
        <div class="container section-title" data-aos="fade-up">
          <h2>Mon Profil</h2>
          <div class="title-shape">
            <svg viewBox="0 0 200 20" xmlns="http://www.w3.org/2000/svg">
              <path d="M 0,10 C 40,0 60,20 100,10 C 140,0 160,20 200,10" fill="none" stroke="currentColor" stroke-width="2"></path>
            </svg>
          </div>
        </div>

        <?php
        // Affichage messages
        if (isset($success)) {
          echo '<div class="alert alert-success">' . $success . '</div>';
        }
        if (isset($error)) {
          echo '<div class="alert alert-danger">' . $error . '</div>';
        }
        ?>

        <div class="row g-4">

          <!-- Colonne gauche : infos utilisateur -->
          <div class="col-lg-4" data-aos="fade-right" data-aos-delay="200">
            <div class="card text-center p-4 h-100">

              <!-- Avatar -->
              <div class="d-flex justify-content-center mb-3">
                <?php if (!empty($utilisateur['photo'])): ?>
                  <img src="assets/img/profile/<?= htmlspecialchars($utilisateur['photo']) ?>" alt="Avatar" class="profile-avatar">
                <?php else: ?>
                  <div class="profile-avatar-placeholder">
                    <i class="bi bi-person-fill"></i>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Nom -->
              <h3 class="mb-1"><?= htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?></h3>

              <!-- Rôle -->
              <span class="badge bg-primary badge-role mb-3">
                <?= htmlspecialchars(ucfirst($utilisateur['role'])) ?>
              </span>

              <!-- Infos de base -->
              <div class="personal-info text-start mt-3">
                <div class="row g-3">
                  <div class="col-12">
                    <div class="info-item">
                      <span class="label"><i class="bi bi-envelope me-1"></i> Email</span>
                      <span class="value"><?= htmlspecialchars($utilisateur['email']) ?></span>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="info-item">
                      <span class="label"><i class="bi bi-telephone me-1"></i> Téléphone</span>
                      <span class="value"><?= !empty($utilisateur['telephone']) ? htmlspecialchars($utilisateur['telephone']) : 'Non renseigné' ?></span>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="info-item">
                      <span class="label"><i class="bi bi-calendar me-1"></i> Membre depuis</span>
                      <span class="value"><?= htmlspecialchars($utilisateur['date_inscription']) ?></span>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <!-- Colonne droite : édition du profil -->
          <div class="col-lg-8" data-aos="fade-left" data-aos-delay="300">
            <div class="contact-form card">
              <div class="card-body p-4 p-lg-5">

                <div class="d-flex justify-content-between align-items-center mb-4">
                  <h4 class="mb-0">Modifier mes informations</h4>
                </div>

                <form action="../../controllers/UtilisateurController.php" method="POST" enctype="multipart/form-data">
                  <input type="hidden" name="action" value="update_profile">
                  <input type="hidden" name="id" value="<?= htmlspecialchars($utilisateur['id']) ?>">

                  <div class="row gy-4">

                    <!-- Nom -->
                    <div class="col-md-6">
                      <label for="nom" class="form-label">Nom</label>
                      <input type="text" name="nom" id="nom" class="form-control"
                        value="<?= htmlspecialchars($utilisateur['nom']) ?>" required>
                    </div>

                    <!-- Prénom -->
                    <div class="col-md-6">
                      <label for="prenom" class="form-label">Prénom</label>
                      <input type="text" name="prenom" id="prenom" class="form-control"
                        value="<?= htmlspecialchars($utilisateur['prenom']) ?>" required>
                    </div>

                    <!-- Email -->
                    <div class="col-12">
                      <label for="email" class="form-label">Adresse Email</label>
                      <input type="email" name="email" id="email" class="form-control"
                        value="<?= htmlspecialchars($utilisateur['email']) ?>" required>
                    </div>

                    <!-- Téléphone -->
                    <div class="col-md-6">
                      <label for="telephone" class="form-label">Téléphone</label>
                      <input type="tel" name="telephone" id="telephone" class="form-control"
                        value="<?= htmlspecialchars($utilisateur['telephone'] ?? '') ?>"
                        placeholder="+216 XX XXX XXX">
                    </div>

                    <!-- Rôle (lecture seule) -->
                    <div class="col-md-6">
                      <label for="role" class="form-label">Rôle</label>
                      <input type="text" id="role" class="form-control"
                        value="<?= htmlspecialchars(ucfirst($utilisateur['role'])) ?>" disabled>
                    </div>

                    <!-- Bio / Description -->
                    <div class="col-12">
                      <label for="bio" class="form-label">Bio / Description</label>
                      <textarea name="bio" id="bio" class="form-control" rows="4"
                        placeholder="Parlez de vous, vos compétences, votre expérience..."><?= htmlspecialchars($profil['bio'] ?? '') ?></textarea>
                    </div>

                    <!-- Photo de profil -->
                    <div class="col-12">
                      <label for="photo" class="form-label">Photo de profil</label>
                      <input type="file" name="photo" id="photo" class="form-control" accept="image/*">
                    </div>

                    <!-- Nouveau mot de passe -->
                    <div class="col-md-6">
                      <label for="new_password" class="form-label">Nouveau mot de passe</label>
                      <input type="password" name="new_password" id="new_password" class="form-control"
                        placeholder="Laisser vide pour ne pas changer">
                    </div>

                    <!-- Confirmer nouveau mot de passe -->
                    <div class="col-md-6">
                      <label for="confirm_new_password" class="form-label">Confirmer le mot de passe</label>
                      <input type="password" name="confirm_new_password" id="confirm_new_password" class="form-control"
                        placeholder="Répétez le nouveau mot de passe">
                    </div>

                    <!-- Submit -->
                    <div class="col-12 text-center">
                      <button type="submit" class="btn btn-submit w-100">Enregistrer les modifications</button>
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
    // Vérification nouveau mot de passe
    document.querySelector('form').addEventListener('submit', function(e) {
      const pwd = document.getElementById('new_password').value;
      const confirm = document.getElementById('confirm_new_password').value;
      if (pwd !== '' && pwd !== confirm) {
        e.preventDefault();
        alert('Les nouveaux mots de passe ne correspondent pas.');
      }
    });
  </script>

</body>

</html>