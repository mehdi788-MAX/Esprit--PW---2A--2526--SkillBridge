<?php
session_start();
require_once '../../../config.php';
require_once '../../../model/utilisateur.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$utilisateurModel = new Utilisateur($pdo);

// Validate token
$user = $utilisateurModel->readByResetToken($token);
if (!$user) {
    $_SESSION['error'] = "Lien invalide ou expiré.";
    header('Location: forgot-password.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 8) {
        $error = "Minimum 8 caractères.";
    } elseif ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        $utilisateurModel->id       = $user['id'];
        $utilisateurModel->password = $password;
        $utilisateurModel->updatePassword();
        $utilisateurModel->clearResetToken();

        $_SESSION['success'] = "Mot de passe réinitialisé avec succès !";
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Réinitialiser le mot de passe - SkillBridge</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
</head>
<body class="index-page">

  <header id="header" class="header d-flex align-items-center sticky-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
      <a href="index.html" class="logo d-flex align-items-center me-auto me-xl-0">
        <h1 class="sitename">SkillBridge</h1>
      </a>
    </div>
  </header>

  <main class="main">
    <section class="contact section light-background" style="min-height:85vh; display:flex; align-items:center;">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-5">

            <div class="text-center mb-5">
              <div class="section-category mb-3">Sécurité</div>
              <h2 class="display-5 mb-3">Nouveau mot de passe</h2>
              <p class="lead">Choisissez un nouveau mot de passe pour votre compte.</p>
            </div>

            <div class="contact-form card">
              <div class="card-body p-4 p-lg-5">

                <?php if (isset($error)): ?>
                  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="reset-password.php">
                  <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                  <div class="row gy-4">

                    <div class="col-12">
                      <label for="password" class="form-label">Nouveau mot de passe</label>
                      <div class="input-group">
                        <input type="password" name="password" id="password"
                               class="form-control" placeholder="Minimum 8 caractères" required>
                        <button class="btn btn-outline-secondary" type="button"
                                onclick="const p=document.getElementById('password'); p.type=p.type==='password'?'text':'password'">
                          <i class="bi bi-eye"></i>
                        </button>
                      </div>
                    </div>

                    <div class="col-12">
                      <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                      <input type="password" name="confirm_password" id="confirm_password"
                             class="form-control" placeholder="Répétez le mot de passe" required>
                      <div id="confirm-error" class="text-danger mt-1" style="font-size:0.85rem;"></div>
                    </div>

                    <div class="col-12 text-center">
                      <button type="submit" class="btn btn-submit w-100">
                        Réinitialiser le mot de passe
                      </button>
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
        <p>© <strong class="px-1 sitename">SkillBridge</strong> All Rights Reserved</p>
      </div>
    </div>
  </footer>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    document.querySelector('form').addEventListener('submit', function(e) {
      const pwd  = document.getElementById('password').value;
      const conf = document.getElementById('confirm_password').value;
      const err  = document.getElementById('confirm-error');
      if (pwd !== conf) {
        err.textContent = 'Les mots de passe ne correspondent pas.';
        e.preventDefault();
      }
    });
  </script>
</body>
</html>