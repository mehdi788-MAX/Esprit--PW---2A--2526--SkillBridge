<?php
// ──────────────────────────────────────────────
//  SkillBridge – Modifier une demande
//  BDD : matching | Table : request
// ──────────────────────────────────────────────
 
$db_host = 'localhost';
$db_name = 'matching';
$db_user = 'root';
$db_pass = '';
 
// userId en dur en attendant le système de session/login
$userId = 1;
 
$demande = null;
$error   = '';
 
// Vérification de l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: mes-demandes.php");
    exit;
}
$id = (int)$_GET['id'];
 
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass,
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
 
    // ── Traitement POST ──
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title       = trim($_POST['title']       ?? '');
        $price       = trim($_POST['price']       ?? '');
        $description = trim($_POST['description'] ?? '');
 
        if ($title && $price && $description) {
            $stmt = $pdo->prepare("
                UPDATE request
                SET title = :title,
                    description = :description,
                    price = :price,
                    updatedAt = CURDATE()
                WHERE id = :id AND userId = :userId
            ");
            $stmt->execute([
                ':title'       => $title,
                ':description' => $description,
                ':price'       => $price,
                ':id'          => $id,
                ':userId'      => $userId,
            ]);
            header("Location: mes-demandes.php?updated=1");
            exit;
        } else {
            $error = 'Veuillez remplir tous les champs du formulaire.';
        }
    }
 
    // ── Récupération de la demande ──
    $stmt = $pdo->prepare("SELECT * FROM request WHERE id = :id AND userId = :userId");
    $stmt->execute([':id' => $id, ':userId' => $userId]);
    $demande = $stmt->fetch(PDO::FETCH_ASSOC);
 
    if (!$demande) {
        header("Location: mes-demandes.php");
        exit;
    }
 
} catch (PDOException $e) {
    $error = 'Erreur de connexion à la base de données : ' . $e->getMessage();
}
 
// Valeurs affichées : POST si erreur, sinon BDD
$val_title       = $_POST['title']       ?? $demande['title']       ?? '';
$val_price       = $_POST['price']       ?? $demande['price']       ?? '';
$val_description = $_POST['description'] ?? $demande['description'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
 
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Modifier la demande – SkillBridge</title>
  <meta name="description" content="Modifiez votre demande publiée sur SkillBridge.">
 
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
    /* ── BREADCRUMB ── */
    .page-header {
      background: #fff8f0;
      padding: 30px 0 25px;
      border-bottom: 1px solid #ffe0cc;
    }
 
    .page-header h1 {
      font-size: 1.8rem;
      font-weight: 700;
      color: #1a1a2e;
      margin-bottom: 5px;
    }
 
    .page-header p { color: #6b7280; font-size: 0.9rem; margin: 0; }
 
    .breadcrumb-nav a {
      color: #ff6600;
      text-decoration: none;
      font-size: 0.85rem;
    }
 
    .breadcrumb-nav span {
      color: #9ca3af;
      margin: 0 6px;
      font-size: 0.85rem;
    }
 
    .breadcrumb-nav .current { color: #6b7280; font-size: 0.85rem; }
 
    /* ── FORM  (même style que Addrequest.php) ── */
    .edit-section { padding: 50px 0 80px; background: #f9f9f9; }
 
    .request-form {
      background: #fff8f0;
      padding: 50px 20px;
      border-radius: 12px;
      max-width: 700px;
      margin: 0 auto;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      font-family: 'Roboto', sans-serif;
    }
 
    .section-title h2 { color: #ff6600; font-weight: 700; margin-bottom: 10px; }
    .section-title p { color: #333; font-size: 16px; }
 
    .request-form-container { display: flex; flex-direction: column; gap: 20px; }
    .form-group { display: flex; flex-direction: column; }
 
    label { margin-bottom: 8px; font-weight: 500; color: #ff6600; }
 
    input, textarea {
      padding: 12px 15px;
      border-radius: 8px;
      border: 1px solid #ffb366;
      outline: none;
      font-size: 15px;
      transition: 0.3s;
      background: #fff;
    }
 
    input:focus, textarea:focus {
      border-color: #ff6600;
      box-shadow: 0 0 6px rgba(255,102,0,0.4);
    }
 
    textarea { resize: none; }
 
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
 
    .btn-submit:hover { background-color: #e65c00; }
 
    .btn-cancel-link {
      background: #f3f4f6;
      color: #374151;
      border: none;
      padding: 14px 24px;
      font-size: 15px;
      font-weight: 600;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.3s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
 
    .btn-cancel-link:hover { background: #e5e7eb; color: #1a1a2e; }
 
    .alert-error-sb {
      background: #fff5f5;
      border: 1px solid #fca5a5;
      border-radius: 8px;
      padding: 14px 18px;
      color: #b91c1c;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 500;
    }
 
    .original-info {
      background: #f3f4f6;
      border-radius: 8px;
      padding: 10px 16px;
      font-size: 0.82rem;
      color: #6b7280;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }
 
    .original-info i { color: #ff6600; }
 
    @media (max-width: 768px) { .request-form { padding: 30px 15px; } }
  </style>
</head>
 
<body class="index-page">
 
  <!-- ═══════════════════════ HEADER -->
  <header id="header" class="header d-flex align-items-center sticky-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
 
      <a href="index.html" class="logo d-flex align-items-center me-auto me-xl-0">
        <h1 class="sitename">SkillBridge</h1>
      </a>
 
      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.html">Accueil</a></li>
          <li><a href="index.html#propositions">Propositions</a></li>
          <li><a href="mes-demandes.php" class="active">Mes Demandes</a></li>
          <li><a href="Addrequest.php">Publier une demande</a></li>
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
 
    <!-- ═══════════════════════ PAGE HEADER -->
    <div class="page-header">
      <div class="container">
        <h1>Modifier la demande</h1>
        <p>Mettez à jour les informations de votre demande.</p>
        <div class="breadcrumb-nav mt-2">
          <a href="index.html"><i class="bi bi-house-fill"></i> Accueil</a>
          <span>›</span>
          <a href="mes-demandes.php">Mes Demandes</a>
          <span>›</span>
          <span class="current">Modifier</span>
        </div>
      </div>
    </div>
 
    <!-- ═══════════════════════ FORMULAIRE -->
    <section class="edit-section">
      <div class="container">
        <div class="request-form">
 
          <div class="section-title text-center mb-4">
            <h2>Modifier votre demande</h2>
            <p>Vos modifications seront enregistrées immédiatement dans la base de données.</p>
          </div>
 
          <?php if ($error): ?>
            <div class="alert-error-sb">
              <i class="bi bi-exclamation-triangle-fill"></i>
              <?= htmlspecialchars($error) ?>
            </div>
          <?php endif; ?>
 
          <?php if ($demande): ?>
            <div class="original-info">
              <i class="bi bi-info-circle-fill"></i>
              Demande #<?= $demande['id'] ?> · Publiée le <?= date('d/m/Y', strtotime($demande['createdAt'])) ?>
              <?php if ($demande['updatedAt'] !== $demande['createdAt']): ?>
                · Dernière modification le <?= date('d/m/Y', strtotime($demande['updatedAt'])) ?>
              <?php endif; ?>
            </div>
          <?php endif; ?>
 
          <form action="edit-demande.php?id=<?= $id ?>" method="POST" class="request-form-container">
 
            <div class="form-group">
              <label for="title">Titre de la demande</label>
              <input
                type="text"
                name="title"
                id="title"
                placeholder="Ex : Création d'un logo pour une startup tech"
                value="<?= htmlspecialchars($val_title) ?>"
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
                value="<?= htmlspecialchars($val_price) ?>"
                min="1"
                required
              >
            </div>
 
            <div class="form-group">
              <label for="description">Description du projet</label>
              <textarea
                name="description"
                id="description"
                rows="5"
                placeholder="Décrivez votre projet en détail…"
                required
              ><?= htmlspecialchars($val_description) ?></textarea>
            </div>
 
            <div class="form-group d-flex gap-3 flex-wrap">
              <button type="submit" class="btn-submit">
                <i class="bi bi-check-lg"></i> Enregistrer les modifications
              </button>
              <a href="mes-demandes.php" class="btn-cancel-link">
                <i class="bi bi-arrow-left"></i> Annuler
              </a>
            </div>
 
          </form>
 
        </div>
      </div>
    </section>
 
  </main>
 
  <!-- ═══════════════════════ FOOTER -->
  <footer id="footer" class="footer">
    <div class="container">
      <div class="copyright text-center">
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
 
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
 
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/js/main.js"></script>
 
</body>
<?php
// ──────────────────────────────────────────────
//  SkillBridge – Modifier une demande
//  BDD : skillbridge | Table : demandes
// ──────────────────────────────────────────────

$db_host = 'localhost';
$db_name = 'skillbridge';
$db_user = 'root';
$db_pass = '';

$demande = null;
$error   = '';

// Vérification ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: mes-demandes.php");
    exit;
}
$id = (int)$_GET['id'];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass,
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // ── Traitement POST ──
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title       = trim($_POST['title']       ?? '');
        $price       = trim($_POST['price']       ?? '');
        $deadline    = trim($_POST['deadline']    ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($title && $price && $deadline && $description) {
            $stmt = $pdo->prepare("
                UPDATE demandes
                SET title = :title,
                    price = :price,
                    deadline = :deadline,
                    description = :description
                WHERE id = :id
            ");
            $stmt->execute([
                ':title'       => $title,
                ':price'       => $price,
                ':deadline'    => $deadline,
                ':description' => $description,
                ':id'          => $id,
            ]);
            header("Location: mes-demandes.php?updated=1");
            exit;
        } else {
            $error = 'Veuillez remplir tous les champs.';
        }
    }

    // ── Récupération de la demande ──
    $stmt = $pdo->prepare("SELECT * FROM demandes WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $demande = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$demande) {
        header("Location: mes-demandes.php");
        exit;
    }

} catch (PDOException $e) {
    $error = 'Erreur BDD : ' . $e->getMessage();
}

$val_title       = $_POST['title']       ?? $demande['title']       ?? '';
$val_price       = $_POST['price']       ?? $demande['price']       ?? '';
$val_deadline    = $_POST['deadline']    ?? $demande['deadline']    ?? '';
$val_description = $_POST['description'] ?? $demande['description'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Modifier la demande – SkillBridge</title>

  <link href="assets/img/favicon.png" rel="icon">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Questrial&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    .page-header {
      background: #fff8f0;
      padding: 30px 0 25px;
      border-bottom: 1px solid #ffe0cc;
    }
    .page-header h1 { font-size: 1.8rem; font-weight: 700; color: #1a1a2e; margin-bottom: 5px; }
    .page-header p { color: #6b7280; font-size: 0.9rem; margin: 0; }
    .breadcrumb-nav a { color: #ff6600; text-decoration: none; font-size: 0.85rem; }
    .breadcrumb-nav span { color: #9ca3af; margin: 0 6px; font-size: 0.85rem; }
    .breadcrumb-nav .current { color: #6b7280; font-size: 0.85rem; }

    .edit-section { padding: 50px 0 80px; background: #f9f9f9; }

    /* Même style que Addrequest.php */
    .request-form {
      background: #fff8f0;
      padding: 50px 20px;
      border-radius: 12px;
      max-width: 700px;
      margin: 0 auto;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .section-title h2 { color: #ff6600; font-weight: 700; margin-bottom: 10px; }
    .section-title p { color: #333; font-size: 16px; }
    .request-form-container { display: flex; flex-direction: column; gap: 20px; }
    .form-group { display: flex; flex-direction: column; }
    label { margin-bottom: 8px; font-weight: 500; color: #ff6600; }
    input, textarea {
      padding: 12px 15px;
      border-radius: 8px;
      border: 1px solid #ffb366;
      outline: none;
      font-size: 15px;
      transition: 0.3s;
      background: #fff;
    }
    input:focus, textarea:focus {
      border-color: #ff6600;
      box-shadow: 0 0 6px rgba(255,102,0,0.4);
    }
    textarea { resize: none; }
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
    .btn-submit:hover { background-color: #e65c00; }
    .btn-cancel-link {
      background: #f3f4f6;
      color: #374151;
      border: none;
      padding: 14px 24px;
      font-size: 15px;
      font-weight: 600;
      border-radius: 8px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: 0.3s;
    }
    .btn-cancel-link:hover { background: #e5e7eb; color: #1a1a2e; }
    .alert-error-sb {
      background: #fff5f5;
      border: 1px solid #fca5a5;
      border-radius: 8px;
      padding: 14px 18px;
      color: #b91c1c;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .info-bar {
      background: #f3f4f6;
      border-radius: 8px;
      padding: 10px 16px;
      font-size: 0.82rem;
      color: #6b7280;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .info-bar i { color: #ff6600; }
    @media (max-width: 768px) { .request-form { padding: 30px 15px; } }
  </style>
</head>

<body class="index-page">

  <!-- HEADER -->
  <header id="header" class="header d-flex align-items-center sticky-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
      <a href="index.html" class="logo d-flex align-items-center me-auto me-xl-0">
        <h1 class="sitename">SkillBridge</h1>
      </a>
      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.html">Accueil</a></li>
          <li><a href="index.html#propositions">Propositions</a></li>
          <li><a href="mes-demandes.php" class="active">Mes Demandes</a></li>
          <li><a href="Addrequest.php">Publier une demande</a></li>
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

    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="container">
        <h1>Modifier la demande</h1>
        <p>Mettez à jour les informations de votre demande.</p>
        <div class="breadcrumb-nav mt-2">
          <a href="index.html"><i class="bi bi-house-fill"></i> Accueil</a>
          <span>›</span>
          <a href="mes-demandes.php">Mes Demandes</a>
          <span>›</span>
          <span class="current">Modifier</span>
        </div>
      </div>
    </div>

    <!-- FORMULAIRE -->
    <section class="edit-section">
      <div class="container">
        <div class="request-form">

          <div class="section-title text-center mb-4">
            <h2>Modifier votre demande</h2>
            <p>Vos modifications seront enregistrées immédiatement.</p>
          </div>

          <?php if ($error): ?>
            <div class="alert-error-sb">
              <i class="bi bi-exclamation-triangle-fill"></i>
              <?= htmlspecialchars($error) ?>
            </div>
          <?php endif; ?>

          <?php if ($demande): ?>
            <div class="info-bar">
              <i class="bi bi-info-circle-fill"></i>
              Demande #<?= $demande['id'] ?> · Créée le <?= date('d/m/Y', strtotime($demande['created_at'])) ?>
            </div>
          <?php endif; ?>

          <form action="edit-demande.php?id=<?= $id ?>" method="POST" class="request-form-container">

            <div class="form-group">
              <label for="title">Titre de la demande</label>
              <input type="text" name="title" id="title"
                placeholder="Ex : Création d'un logo pour une startup tech"
                value="<?= htmlspecialchars($val_title) ?>"
                required maxlength="150">
            </div>

            <div class="form-group">
              <label for="price">Budget proposé (DT)</label>
              <input type="number" name="price" id="price"
                placeholder="Ex : 150"
                value="<?= htmlspecialchars($val_price) ?>"
                min="1" required>
            </div>

            <div class="form-group">
              <label for="deadline">Date limite de livraison</label>
              <input type="date" name="deadline" id="deadline"
                value="<?= htmlspecialchars($val_deadline) ?>"
                required>
            </div>

            <div class="form-group">
              <label for="description">Description du projet</label>
              <textarea name="description" id="description" rows="5"
                placeholder="Décrivez votre projet en détail…"
                required><?= htmlspecialchars($val_description) ?></textarea>
            </div>

            <div class="form-group d-flex gap-3 flex-wrap">
              <button type="submit" class="btn-submit">
                <i class="bi bi-check-lg"></i> Enregistrer les modifications
              </button>
              <a href="mes-demandes.php" class="btn-cancel-link">
                <i class="bi bi-arrow-left"></i> Annuler
              </a>
            </div>

          </form>
        </div>
      </div>
    </section>

  </main>

  <!-- FOOTER -->
  <footer id="footer" class="footer">
    <div class="container">
      <div class="copyright text-center">
        <p>© <span>Copyright</span> <strong class="px-1 sitename">SkillBridge</strong> <span>Tous droits réservés</span></p>
      </div>
      <div class="social-links d-flex justify-content-center">
        <a href=""><i class="bi bi-twitter-x"></i></a>
        <a href=""><i class="bi bi-facebook"></i></a>
        <a href=""><i class="bi bi-instagram"></i></a>
        <a href=""><i class="bi bi-linkedin"></i></a>
      </div>
      <div class="credits">
        Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a>
      </div>
    </div>
  </footer>

  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short"></i>
  </a>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/js/main.js"></script>

</body>
</html>
</html>