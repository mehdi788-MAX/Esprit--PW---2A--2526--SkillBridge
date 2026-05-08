<?php
require_once 'auth_check.php';

require_once '../../../config.php';
require_once '../../../model/utilisateur.php';
require_once '../../../model/profil.php';

// Charger l'utilisateur connecté
$utilisateurModel = new Utilisateur($pdo);
$utilisateurModel->id = $_SESSION['user_id'];
$utilisateurModel->readOne();

$utilisateur = [
    'id'               => $utilisateurModel->id,
    'nom'              => $utilisateurModel->nom,
    'prenom'           => $utilisateurModel->prenom,
    'email'            => $utilisateurModel->email,
    'role'             => $utilisateurModel->role,
    'telephone'        => $utilisateurModel->telephone,
    'photo'            => $utilisateurModel->photo,
    'date_inscription' => $utilisateurModel->date_inscription,
];

// Charger le profil
$profilModel = new Profil($pdo);
$profilModel->utilisateur_id = $_SESSION['user_id'];
$profilModel->readByUserId();

$profil = [
    'bio'          => $profilModel->bio ?? '',
    'competences'  => $profilModel->competences ?? '',
    'localisation' => $profilModel->localisation ?? '',
    'site_web'     => $profilModel->site_web ?? '',
];

// Profile completion
$completion_items = [
    'Photo'        => !empty($utilisateur['photo']),
    'Téléphone'    => !empty($utilisateur['telephone']),
    'Bio'          => !empty($profil['bio']),
    'Compétences'  => !empty($profil['competences']),
    'Localisation' => !empty($profil['localisation']),
    'Site Web'     => !empty($profil['site_web']),
];

$completed = count(array_filter($completion_items));
$total     = count($completion_items);
$percent   = round(($completed / $total) * 100);

$bar_color = $percent < 40 ? 'danger' : ($percent < 80 ? 'warning' : 'success');
$bar_label = $percent < 40 ? 'Incomplet' : ($percent < 80 ? 'En cours' : ($percent === 100 ? 'Complet !' : 'Presque complet'));
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Profil - SkillBridge</title>

  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Noto+Sans:wght@400;600;700&family=Questrial:wght@400&display=swap" rel="stylesheet">

  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    .profile-avatar {
      width: 130px; height: 130px;
      object-fit: cover; border-radius: 50%;
      border: 4px solid var(--accent-color, #0ea2bd);
    }
    .profile-avatar-placeholder {
      width: 130px; height: 130px; border-radius: 50%;
      background: #e0f7fa;
      display: flex; align-items: center; justify-content: center;
      font-size: 3rem; color: var(--accent-color, #0ea2bd);
      border: 4px solid var(--accent-color, #0ea2bd);
    }
    .badge-role {
      font-size: 0.85rem; padding: 5px 14px; border-radius: 20px;
    }
    .info-item .label {
      font-size: 0.8rem; color: #888; display: block; margin-bottom: 2px;
    }
    .info-item .value { font-weight: 600; font-size: 0.95rem; }
    .progress { background-color: #e9ecef; }
  </style>
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
          <li><a href="profil.php" class="active">Mon Profil</a></li>
          <li><a href="../chat/conversations.php">Mes Conversations</a></li>
          <li><a href="../../../controller/utilisateurcontroller.php?action=logout">Déconnexion</a></li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>
    </div>
  </header>

  <main class="main">
    <section class="about section light-background" style="min-height: 85vh;">
      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="container section-title" data-aos="fade-up">
          <h2>Mon Profil</h2>
          <div class="title-shape">
            <svg viewBox="0 0 200 20" xmlns="http://www.w3.org/2000/svg">
              <path d="M 0,10 C 40,0 60,20 100,10 C 140,0 160,20 200,10" fill="none" stroke="currentColor" stroke-width="2"></path>
            </svg>
          </div>
        </div>

        

        <div class="row g-4">

          <!-- Colonne gauche -->
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

              <h3 class="mb-1"><?= htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?></h3>
              <span class="badge bg-primary badge-role mb-3"><?= htmlspecialchars(ucfirst($utilisateur['role'])) ?></span>

              <!-- Progress Bar -->
              <div class="mt-2 text-start">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <small class="fw-bold">Complétion du profil</small>
                  <small class="text-<?= $bar_color ?>">
                    <strong><?= $percent ?>%</strong> — <?= $bar_label ?>
                  </small>
                </div>
                <div class="progress" style="height: 10px; border-radius: 10px;">
                  <div class="progress-bar bg-<?= $bar_color ?>"
                       role="progressbar"
                       style="width: <?= $percent ?>%; border-radius: 10px; transition: width 1s ease;"
                       aria-valuenow="<?= $percent ?>"
                       aria-valuemin="0"
                       aria-valuemax="100">
                  </div>
                </div>

                <!-- Checklist -->
                <ul class="list-unstyled mt-3" style="font-size: 0.85rem;">
                  <?php foreach ($completion_items as $label => $done): ?>
                    <li class="mb-1">
                      <?php if ($done): ?>
                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                      <?php else: ?>
                        <i class="bi bi-circle text-muted me-2"></i>
                      <?php endif; ?>
                      <span class="<?= $done ? 'text-success' : 'text-muted' ?>">
                        <?= $label ?>
                      </span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <!-- Info -->
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
                      <span class="value"><?= date('d/m/Y', strtotime($utilisateur['date_inscription'])) ?></span>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <!-- Colonne droite -->
          <div class="col-lg-8" data-aos="fade-left" data-aos-delay="300">
            <div class="contact-form card">
              <div class="card-body p-4 p-lg-5">

                <h4 class="mb-4">Modifier mes informations</h4>

                <form id="profilForm" action="../../../controller/utilisateurcontroller.php" method="POST" enctype="multipart/form-data" novalidate>
                  <input type="hidden" name="action" value="update_profile">
                  <input type="hidden" name="id" value="<?= htmlspecialchars($utilisateur['id']) ?>">
                  <input type="hidden" name="old_photo" value="<?= htmlspecialchars($utilisateur['photo'] ?? '') ?>">

                  <div class="row gy-4">

                    <div class="col-md-6">
                      <label for="nom" class="form-label">Nom</label>
                      <input type="text" name="nom" id="nom" class="form-control" value="<?= htmlspecialchars($utilisateur['nom']) ?>">
                      <div id="nom-error" class="text-danger mt-1" style="font-size:0.85rem; display:none;"></div>
                    </div>

                    <div class="col-md-6">
                      <label for="prenom" class="form-label">Prénom</label>
                      <input type="text" name="prenom" id="prenom" class="form-control" value="<?= htmlspecialchars($utilisateur['prenom']) ?>">
                      <div id="prenom-error" class="text-danger mt-1" style="font-size:0.85rem; display:none;"></div>
                    </div>

                    <div class="col-12">
                      <label for="email" class="form-label">Adresse Email</label>
                      <input type="text" name="email" id="email" class="form-control" value="<?= htmlspecialchars($utilisateur['email']) ?>">
                      <div id="email-error" class="text-danger mt-1" style="font-size:0.85rem; display:none;"></div>
                    </div>

                    <div class="col-md-6">
                      <label for="telephone" class="form-label">Téléphone</label>
                      <input type="text" name="telephone" id="telephone" class="form-control" value="<?= htmlspecialchars($utilisateur['telephone'] ?? '') ?>" placeholder="+216 XX XXX XXX">
                    </div>

                    <div class="col-md-6">
                      <label class="form-label">Rôle</label>
                      <input type="text" class="form-control" value="<?= htmlspecialchars(ucfirst($utilisateur['role'])) ?>" disabled>
                    </div>

                    <div class="col-12">
                      <label for="bio" class="form-label">Bio / Description</label>
                      <textarea name="bio" id="bio" class="form-control" rows="4" placeholder="Parlez de vous..."><?= htmlspecialchars($profil['bio']) ?></textarea>
                    </div>

                    <div class="col-12">
                      <label for="competences" class="form-label">Compétences</label>
                      <input type="text" name="competences" id="competences" class="form-control"
                             value="<?= htmlspecialchars($profil['competences']) ?>"
                             placeholder="ex: PHP, MySQL, React">
                      <small class="text-muted">Séparez par des virgules</small>
                    </div>

                    <div class="col-md-6">
                      <label for="localisation" class="form-label">Localisation</label>
                      <input type="text" name="localisation" id="localisation" class="form-control"
                             value="<?= htmlspecialchars($profil['localisation']) ?>"
                             placeholder="ex: Tunis, Tunisie">
                    </div>

                    <div class="col-md-6">
                      <label for="site_web" class="form-label">Site Web</label>
                      <input type="url" name="site_web" id="site_web" class="form-control"
                             value="<?= htmlspecialchars($profil['site_web']) ?>"
                             placeholder="https://monsite.com">
                    </div>

                    <div class="col-12">
                      <label for="photo" class="form-label">Photo de profil</label>
                      <?php if (!empty($utilisateur['photo'])): ?>
                        <div class="mb-2">
                          <img src="assets/img/profile/<?= htmlspecialchars($utilisateur['photo']) ?>"
                               style="width:60px; height:60px; border-radius:50%; object-fit:cover;">
                          <small class="text-muted ms-2">Photo actuelle</small>
                        </div>
                      <?php endif; ?>
                      <input type="file" name="photo" id="photo" class="form-control" accept="image/*">
                    </div>

                    <div class="col-md-6">
                      <label for="new_password" class="form-label">Nouveau mot de passe</label>
                      <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Laisser vide pour ne pas changer">
                      <div id="newpwd-error" class="text-danger mt-1" style="font-size:0.85rem; display:none;"></div>
                    </div>

                    <div class="col-md-6">
                      <label for="confirm_new_password" class="form-label">Confirmer le mot de passe</label>
                      <input type="password" name="confirm_new_password" id="confirm_new_password" class="form-control" placeholder="Répétez le nouveau mot de passe">
                      <div id="confirmpwd-error" class="text-danger mt-1" style="font-size:0.85rem; display:none;"></div>
                    </div>

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
    document.getElementById('profilForm').addEventListener('submit', function(e) {
      let valid = true;

      ['nom','prenom','email','new_password','confirm_new_password'].forEach(function(id) {
        const field = document.getElementById(id);
        if (field) field.classList.remove('is-invalid', 'is-valid');
      });
      ['nom-error','prenom-error','email-error','newpwd-error','confirmpwd-error'].forEach(function(id) {
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

      const nom    = document.getElementById('nom').value.trim();
      const prenom = document.getElementById('prenom').value.trim();
      const email  = document.getElementById('email').value.trim();
      const newPwd = document.getElementById('new_password').value;
      const confPwd = document.getElementById('confirm_new_password').value;
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (nom === '')   showError('nom',   'nom-error',   'Le nom est obligatoire.');
      if (prenom === '') showError('prenom', 'prenom-error', 'Le prénom est obligatoire.');
      if (email === '')  showError('email',  'email-error',  "L'email est obligatoire.");
      else if (!emailRegex.test(email)) showError('email', 'email-error', 'Format invalide.');
      if (newPwd !== '' && newPwd.length < 8) showError('new_password', 'newpwd-error', 'Minimum 8 caractères.');
      if (newPwd !== '' && newPwd !== confPwd) showError('confirm_new_password', 'confirmpwd-error', 'Les mots de passe ne correspondent pas.');

      if (!valid) e.preventDefault();
    });
  </script>
  <!-- Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
  <div id="toastSuccess" class="toast align-items-center text-white bg-success border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body">
        <i class="bi bi-check-circle me-2"></i>
        <span id="toastSuccessMsg"></span>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>

  <div id="toastError" class="toast align-items-center text-white bg-danger border-0 mt-2" role="alert">
    <div class="d-flex">
      <div class="toast-body">
        <i class="bi bi-exclamation-circle me-2"></i>
        <span id="toastErrorMsg"></span>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
<script>
function showToast(type, message) {
  const toastEl = document.getElementById('toast' + type);
  const msgEl   = document.getElementById('toast' + type + 'Msg');
  msgEl.textContent = message;
  const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
  toast.show();
}

// Afficher automatiquement si session message
<?php if (isset($_SESSION['success'])): ?>
  showToast('Success', '<?= addslashes($_SESSION['success']) ?>');
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
  showToast('Error', '<?= addslashes($_SESSION['error']) ?>');
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>
</script>

</body>

</html>