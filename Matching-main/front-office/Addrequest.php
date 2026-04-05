<?php
// ──────────────────────────────────────────────
//  SkillBridge – Ajout d'une demande client
//  Connexion BDD via PDO (MySQL)
// ──────────────────────────────────────────────

$db_host = 'localhost';
$db_name = 'skillbridge';
$db_user = 'root';
$db_pass = '';

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']       ?? '');
    $price       = trim($_POST['price']       ?? '');
    $deadline    = trim($_POST['deadline']    ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($title && $price && $deadline && $description) {
        try {
            $pdo = new PDO(
                "mysql:host=$db_host;dbname=$db_name;charset=utf8",
                $db_user,
                $db_pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $stmt = $pdo->prepare("
                INSERT INTO demandes (title, price, deadline, description, created_at)
                VALUES (:title, :price, :deadline, :description, NOW())
            ");

            $stmt->execute([
                ':title'       => $title,
                ':price'       => $price,
                ':deadline'    => $deadline,
                ':description' => $description,
            ]);

            $success = true;

        } catch (PDOException $e) {
            $error = 'Erreur de connexion à la base de données : ' . $e->getMessage();
        }
    } else {
        $error = 'Veuillez remplir tous les champs du formulaire.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Publier une demande – SkillBridge</title>
  <meta name="description" content="Publiez votre demande et recevez des propositions de freelancers qualifiés sur SkillBridge.">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Noto+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Questrial:wght@400&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    /* Container */
    .request-form {
      background: #fff8f0;
      padding: 50px 20px;
      border-radius: 12px;
      max-width: 700px;
      margin: 0 auto;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      font-family: 'Roboto', sans-serif;
    }

    .section-title h2 {
      color: #ff6600;
      font-weight: 700;
      margin-bottom: 10px;
    }

    .section-title p {
      color: #333;
      font-size: 16px;
    }

    /* Form */
    .request-form-container {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
    }

    label {
      margin-bottom: 8px;
      font-weight: 500;
      color: #ff6600;
    }

    input, textarea {
      padding: 12px 15px;
      border-radius: 8px;
      border: 1px solid #ffb366;
      outline: none;
      font-size: 15px;
      transition: 0.3s;
    }

    input:focus, textarea:focus {
      border-color: #ff6600;
      box-shadow: 0 0 6px rgba(255,102,0,0.4);
    }

    textarea {
      resize: none;
    }

    .btn-submit {
      background-color: #ff6600;
      color: #fff;
      border: none;
      padding: 14px 30px;
      font-size: 16px;
      font-weight: 600;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.3s;
    }

    .btn-submit:hover {
      background-color: #ff8533;
    }

    .alert-success-skillbridge {
      background: #fff3eb;
      border: 1px solid #ffb366;
      border-radius: 8px;
      padding: 20px;
      color: #cc5200;
      margin-bottom: 20px;
    }

    .alert-error-skillbridge {
      background: #fff5f5;
      border: 1px solid #fca5a5;
      border-radius: 8px;
      padding: 15px;
      color: #b91c1c;
      margin-bottom: 20px;
    }

    @media (max-width: 768px) {
      .request-form {
        padding: 30px 15px;
      }
    }
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
          <li><a href="index.html#propositions">Propositions</a></li>
          <li><a href="mes-demandes.php">Mes Demandes</a></li>
          <li><a href="Addrequest.php" class="active">Publier une demande</a></li>
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
    <section id="request-form" class="request-form">
      <div class="container">
        <div class="section-title text-center mb-4">
          <h2>Publier une demande</h2>
          <p>Décrivez votre projet et recevez rapidement des propositions de freelancers qualifiés.</p>
        </div>

        <?php if ($success): ?>
          <div class="alert-success-skillbridge text-center">
            <strong>✓ Votre demande a été publiée avec succès !</strong><br>
            Les freelancers pourront la consulter et vous soumettre leurs propositions très prochainement.<br><br>
            <a href="mes-demandes.php" class="btn-submit" style="text-decoration:none; display:inline-block; margin-right:10px;">Voir mes demandes</a>
            <a href="Addrequest.php" class="btn-submit" style="text-decoration:none; display:inline-block;">Publier une autre demande</a>
          </div>

        <?php else: ?>

          <?php if ($error): ?>
            <div class="alert-error-skillbridge text-center">
              <?= htmlspecialchars($error) ?>
            </div>
          <?php endif; ?>

          <form action="Addrequest.php" method="POST" class="request-form-container">

            <div class="form-group">
              <label for="title">Titre de la demande</label>
              <input
                type="text"
                name="title"
                id="title"
                placeholder="Ex : Création d'un logo pour une startup tech"
                value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                required
                maxlength="150"
              >
            </div>

            <div class="form-group">
              <label for="price">Budget proposé (DT)</label>
              <input
                type="number"
                name="price"
                id="price"
                placeholder="Ex : 150"
                value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
                min="1"
                required
              >
            </div>

            <div class="form-group">
              <label for="deadline">Date limite de livraison</label>
              <input
                type="date"
                name="deadline"
                id="deadline"
                value="<?= htmlspecialchars($_POST['deadline'] ?? '') ?>"
                min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                required
              >
            </div>

            <div class="form-group">
              <label for="description">Description du projet</label>
              <textarea
                name="description"
                id="description"
                rows="5"
                placeholder="Décrivez votre projet en détail : style souhaité, couleurs, références, fichiers attendus…"
                required
              ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group text-center">
              <button type="submit" class="btn-submit">Publier la demande</button>
            </div>

          </form>

        <?php endif; ?>

      </div>
    </section>
  </main>

  <footer id="footer" class="footer">

    <div class="container">
      <div class="copyright text-center ">
        <p>© <span>Copyright</span> <strong class="px-1 sitename">SkillBridge</strong> <span>Tous droits réservés</span></p>
      </div>
      <div class="social-links d-flex justify-content-center">
        <a href=""><i class="bi bi-twitter-x"></i></a>
        <a href=""><i class="bi bi-facebook"></i></a>
        <a href=""><i class="bi bi-instagram"></i></a>
        <a href=""><i class="bi bi-linkedin"></i></a>
      </div>
      <div class="credits">
        Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a> | <a href="https://bootstrapmade.com/tools/">DevTools</a>
      </div>
    </div>

  </footer>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/waypoints/noframework.waypoints.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

</html>
