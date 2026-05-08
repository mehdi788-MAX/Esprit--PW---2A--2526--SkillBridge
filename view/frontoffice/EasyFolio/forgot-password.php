<?php
session_start();
require_once '../../../config.php';
require_once '../../../model/utilisateur.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../../../libs/PHPMailer/Exception.php';
require_once '../../../libs/PHPMailer/PHPMailer.php';
require_once '../../../libs/PHPMailer/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Adresse email invalide.";
    } else {
        $utilisateurModel = new Utilisateur($pdo);
        $utilisateurModel->email = $email;

        if ($utilisateurModel->emailExists()) {
            $token     = bin2hex(random_bytes(32));
            $expiry    = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $resetLink = base_url() . '/view/frontoffice/EasyFolio/reset-password.php?token=' . $token;

            $utilisateurModel->setResetToken($token, $expiry);

            // Send email with PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'mehdiharrabi7@gmail.com'; // ← change this
                $mail->Password   = 'qbgejzyalymxgzah';    // ← change this
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom('your_email@gmail.com', 'SkillBridge');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Réinitialisation de votre mot de passe - SkillBridge';
                $mail->Body    = '
                    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
                        <div style="text-align: center; margin-bottom: 30px;">
                            <h1 style="color: #4e73df;">SkillBridge</h1>
                        </div>
                        <p style="font-size: 16px;">Bonjour,</p>
                        <p style="font-size: 16px;">Vous avez demandé la réinitialisation de votre mot de passe.</p>
                        <p style="font-size: 16px;">Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :</p>
                        <div style="text-align: center; margin: 30px 0;">
                            <a href="' . $resetLink . '" 
                               style="background: #4e73df; color: white; padding: 14px 35px; 
                                      border-radius: 5px; text-decoration: none; font-size: 16px;
                                      display: inline-block;">
                                Réinitialiser mon mot de passe
                            </a>
                        </div>
                        <p style="color: #888; font-size: 0.85rem; margin-top: 30px;">
                            ⏱ Ce lien expire dans <strong>1 heure</strong>.<br><br>
                            Si vous n\'avez pas demandé cette réinitialisation, ignorez cet email.
                        </p>
                        <hr style="border: none; border-top: 1px solid #eee; margin-top: 30px;">
                        <p style="color: #aaa; font-size: 0.75rem; text-align: center;">
                            © ' . date('Y') . ' SkillBridge. Tous droits réservés.
                        </p>
                    </div>
                ';

                if ($mail->send()) {
    $_SESSION['success'] = "Email envoyé avec succès !";
} else {
    $_SESSION['error'] = "Erreur: " . $mail->ErrorInfo;
}

            } catch (Exception $e) {
                error_log('Mailer Error: ' . $mail->ErrorInfo);
                $_SESSION['error'] = "Erreur lors de l'envoi de l'email. Veuillez réessayer.";
            }

        } else {
            // Don't reveal if email exists (security)
            $_SESSION['success'] = "Si cet email existe, un lien de réinitialisation vous sera envoyé.";
        }
    }

    header('Location: forgot-password.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Mot de passe oublié - SkillBridge</title>
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
      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.html">Accueil</a></li>
          <li><a href="login.php">Connexion</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="main">
    <section class="contact section light-background" style="min-height:85vh; display:flex; align-items:center;">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-5">

            <div class="text-center mb-5">
              <div class="section-category mb-3">Sécurité</div>
              <h2 class="display-5 mb-3">Mot de passe oublié ?</h2>
              <p class="lead">Entrez votre email pour recevoir un lien de réinitialisation.</p>
            </div>

            <div class="contact-form card">
              <div class="card-body p-4 p-lg-5">

                <?php if (isset($_SESSION['success'])): ?>
                  <div class="alert alert-success">
                    <?= $_SESSION['success'] ?>
                  </div>
                  <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                  <div class="alert alert-danger">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                  </div>
                  <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <form method="POST" action="forgot-password.php">
                  <div class="row gy-4">

                    <div class="col-12">
                      <label for="email" class="form-label">Adresse Email</label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" id="email" class="form-control"
                               placeholder="example@email.com" required>
                      </div>
                    </div>

                    <div class="col-12 text-center">
                      <button type="submit" class="btn btn-submit w-100">
                        <i class="bi bi-send me-2"></i>Envoyer le lien
                      </button>
                    </div>

                    <div class="col-12 text-center">
                      <a href="login.php">← Retour à la connexion</a>
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
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/js/main.js"></script>

</body>
</html>