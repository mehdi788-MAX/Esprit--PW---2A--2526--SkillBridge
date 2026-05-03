<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../models/AiRecommendationService.php';
ensure_session_started();
require_freelancer();

$flashOk = isset($_GET['ok']) && (string) $_GET['ok'] === '1';
$flashErr = isset($_GET['err']) ? trim((string) $_GET['err']) : '';

$demandes = [];
$selectedDemandeId = isset($_GET['demande_id']) ? (int) $_GET['demande_id'] : 0;
$formValues = [
  'demande_id' => $selectedDemandeId > 0 ? (string) $selectedDemandeId : '',
  'freelancer_name' => trim((string) ($_GET['freelancer_name'] ?? '')),
  'price' => trim((string) ($_GET['price'] ?? '')),
  'message' => trim((string) ($_GET['message'] ?? '')),
];

try {
  $pdo = db_connect();
  $displayNames = current_user_display_names($pdo);
  if ($formValues['freelancer_name'] === '' && !empty($displayNames)) {
    $formValues['freelancer_name'] = $displayNames[0];
  }
  $stmt = $pdo->query('SELECT id, title, price, deadline, description FROM demandes ORDER BY created_at DESC');
  $demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (isset($_GET['ai_draft']) && $_GET['ai_draft'] === '1' && $selectedDemandeId > 0 && $formValues['message'] === '') {
    $selectedDemande = null;
    foreach ($demandes as $demande) {
      if ((int) $demande['id'] === $selectedDemandeId) {
        $selectedDemande = $demande;
        break;
      }
    }

    $profileStmt = $pdo->prepare('SELECT competences, bio FROM profils WHERE utilisateur_id = :user_id LIMIT 1');
    $profileStmt->execute([':user_id' => current_user_id()]);
    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    if ($selectedDemande !== null) {
      $formValues['message'] = generateProposalDraft($selectedDemande, $profile);
    }
  }
} catch (PDOException $e) {
  $demandes = [];
}

if ($formValues['demande_id'] === '' && !empty($demandes)) {
  $formValues['demande_id'] = (string) $demandes[0]['id'];
}

$demandeSelectDisabled = empty($demandes);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Soumettre une proposition – SkillBridge</title>
  <meta name="description" content="Envoyez votre offre pour une demande client sur SkillBridge.">

  <link href="../../assets/images/favicon.png" rel="icon">
  <link href="../../assets/images/apple-touch-icon.png" rel="apple-touch-icon">

  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Noto+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Questrial:wght@400&display=swap" rel="stylesheet">

  <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../../assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="../../assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="../../assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <link href="../../assets/css/main.css" rel="stylesheet">

  <style>
    .request-form {
      background: #fff8f0;
      padding: 50px 20px;
      border-radius: 12px;
      max-width: 700px;
      margin: 0 auto;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
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

    .request-form-container {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
    }

    .form-group label {
      margin-bottom: 8px;
      font-weight: 500;
      color: #ff6600;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
      padding: 12px 15px;
      border-radius: 8px;
      border: 1px solid #ffb366;
      outline: none;
      font-size: 15px;
      transition: 0.3s;
      background: #fff;
    }

    .form-group select {
      cursor: pointer;
      appearance: auto;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
      border-color: #ff6600;
      box-shadow: 0 0 6px rgba(255, 102, 0, 0.4);
    }

    .form-group textarea {
      resize: none;
    }

    .form-group select:disabled {
      opacity: 0.65;
      cursor: not-allowed;
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

    .btn-outline-skillbridge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 10px;
      padding: 10px 18px;
      border-radius: 8px;
      border: 1px solid #ffb366;
      color: #cc5200;
      text-decoration: none;
      font-weight: 600;
      font-size: 0.95rem;
      transition: 0.3s;
    }

    .btn-outline-skillbridge:hover {
      background: #fff3eb;
      color: #b34700;
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

    .proposal-help {
      background: #f8fbff;
      border: 1px solid #bfdbfe;
      border-radius: 8px;
      color: #1e3a8a;
      font-size: 0.92rem;
      line-height: 1.5;
      padding: 12px 14px;
      margin-bottom: 8px;
    }

    .field-error {
      color: #b91c1c;
      font-size: 0.9rem;
      margin-top: 6px;
      min-height: 20px;
    }

    .input-error {
      border-color: #dc2626 !important;
      box-shadow: 0 0 0 0.15rem rgba(220, 38, 38, 0.12);
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

      <a href="<?= front_url('index.php') ?>" class="logo d-flex align-items-center me-auto me-xl-0">
        <h1 class="sitename">SkillBridge</h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="<?= front_url('index.php') ?>">Accueil</a></li>
          <li><a href="<?= front_url('index.php#propositions') ?>">Propositions</a></li>
          <li><a href="<?= front_url('mes-demandes.php') ?>"><?= front_demands_label() ?></a></li>
          <li><a href="<?= front_url('mes-propositions.php') ?>">Mes propositions</a></li>
          <li><a href="<?= front_url('mon-profil-freelancer.php') ?>">Mon profil</a></li>
          <li><a href="<?= front_url('addprop-form.php') ?>" class="active">Soumettre une offre</a></li>
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
    <section id="proposal-form" class="request-form">
      <div class="container">
        <div class="section-title text-center mb-4">
          <h2>Soumettre une proposition</h2>
          <p>Choisissez la demande concernee, indiquez votre prix et detaillez votre offre comme pour une demande claire et complete.</p>
        </div>

        <?php if ($flashOk): ?>
          <div class="alert-success-skillbridge text-center">
            <strong>Proposition enregistree avec succes.</strong><br>
            Le client pourra consulter votre offre sur la demande associee.<br><br>
            <a href="<?= front_url('mes-propositions.php') ?>" class="btn-submit" style="text-decoration:none; display:inline-block; margin-right:10px;">Voir mes propositions</a>
            <a href="<?= front_url('addprop-form.php') ?>" class="btn-submit" style="text-decoration:none; display:inline-block;">Soumettre une autre offre</a>
          </div>
        <?php else: ?>

          <?php if ($flashErr !== ''): ?>
            <div class="alert-error-skillbridge text-center">
              <?= htmlspecialchars($flashErr) ?>
            </div>
          <?php endif; ?>

          <div class="proposal-help">
            La proposition est liee a l'identifiant technique de la demande choisie dans la liste. Verifiez le titre et la date limite avant d'envoyer.
          </div>

          <form action="<?= front_url('addprop.php') ?>" method="post" class="request-form-container" id="proposalForm" novalidate>

            <div class="form-group">
              <label for="demande_id">Demande concernee</label>
              <select name="demande_id" id="demande_id" <?= $demandeSelectDisabled ? 'disabled' : '' ?>>
                <option value="">Choisir une demande</option>
                <?php foreach ($demandes as $demande): ?>
                  <option value="<?= (int) $demande['id'] ?>" <?= $formValues['demande_id'] === (string) $demande['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($demande['title']) ?><?= !empty($demande['deadline']) ? ' - avant le ' . date('d/m/Y', strtotime($demande['deadline'])) : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="field-error" data-error-for="demande_id"></div>
            </div>

            <div class="form-group">
              <label for="freelancer_name">Nom affiche</label>
              <input
                type="text"
                name="freelancer_name"
                id="freelancer_name"
                maxlength="120"
                placeholder="Votre nom ou pseudo"
                value="<?= htmlspecialchars($formValues['freelancer_name']) ?>">
              <div class="field-error" data-error-for="freelancer_name"></div>
            </div>

            <div class="form-group">
              <label for="price">Prix propose (DT)</label>
              <input
                type="number"
                name="price"
                id="price"
                placeholder="Ex. 200"
                value="<?= htmlspecialchars($formValues['price']) ?>">
              <div class="field-error" data-error-for="price"></div>
            </div>

            <div class="form-group">
              <label for="message">Message / detail de l'offre</label>
              <textarea
                name="message"
                id="message"
                rows="6"
                placeholder="Decrivez votre offre, delais, livrables, experience..."><?= htmlspecialchars($formValues['message']) ?></textarea>
              <div class="field-error" data-error-for="message"></div>
              <?php if ((int) ($formValues['demande_id'] ?: 0) > 0): ?>
                <a class="btn-outline-skillbridge" href="<?= front_url('addprop-form.php?demande_id=' . (int) $formValues['demande_id'] . '&ai_draft=1') ?>">
                  <i class="bi bi-stars"></i>
                  Generer une proposition avec IA locale
                </a>
              <?php endif; ?>
            </div>

            <div class="form-group text-center">
              <button type="submit" class="btn-submit">
                <i class="bi bi-send"></i>
                Envoyer la proposition
              </button>
            </div>
          </form>

        <?php endif; ?>

      </div>
    </section>
  </main>

  <footer id="footer" class="footer">
    <div class="container">
      <div class="copyright text-center ">
        <p>© <span>Copyright</span> <strong class="px-1 sitename">SkillBridge</strong> <span>Tous droits reserves</span></p>
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

  <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../../assets/vendor/php-email-form/validate.js"></script>
  <script src="../../assets/vendor/aos/aos.js"></script>
  <script src="../../assets/vendor/waypoints/noframework.waypoints.js"></script>
  <script src="../../assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="../../assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="../../assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="../../assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="../../assets/js/main.js"></script>

  <?php if (!$flashOk): ?>
  <script>
    (function() {
      var form = document.getElementById('proposalForm');
      if (!form) {
        return;
      }

      var demandeEl = document.getElementById('demande_id');
      var fields = {
        demande_id: demandeEl,
        freelancer_name: document.getElementById('freelancer_name'),
        price: document.getElementById('price'),
        message: document.getElementById('message')
      };

      function setError(name, message) {
        var field = fields[name];
        var errorBox = form.querySelector('[data-error-for="' + name + '"]');
        if (!field || !errorBox) {
          return;
        }
        errorBox.textContent = message || '';
        field.classList.toggle('input-error', Boolean(message));
      }

      function validateField(name) {
        if (name === 'demande_id') {
          if (fields.demande_id.disabled) {
            return 'Aucune demande disponible pour le moment.';
          }
          var d = fields.demande_id.value.trim();
          if (d === '' || isNaN(d) || Number(d) < 1 || !Number.isInteger(Number(d))) {
            return 'Veuillez choisir une demande valide.';
          }
          return '';
        }

        var value = fields[name].value.trim();

        if (name === 'freelancer_name') {
          if (value === '') return 'Le nom affiche est obligatoire.';
          if (value.length < 3) return 'Le nom affiche doit contenir au moins 3 caracteres.';
          if (value.length > 120) return 'Le nom affiche ne doit pas depasser 120 caracteres.';
        }

        if (name === 'price') {
          if (value === '') return 'Le prix propose est obligatoire.';
          if (isNaN(value) || Number(value) < 1) return 'Le prix propose doit etre superieur ou egal a 1 DT.';
        }

        if (name === 'message') {
          if (value === '') return 'Le message est obligatoire.';
          if (value.length < 15) return 'Le message doit contenir au moins 15 caracteres.';
        }

        return '';
      }

      if (fields.demande_id.disabled) {
        setError('demande_id', 'Aucune demande disponible pour le moment.');
      }

      Object.keys(fields).forEach(function(name) {
        fields[name].addEventListener('input', function() {
          setError(name, validateField(name));
        });
        fields[name].addEventListener('change', function() {
          setError(name, validateField(name));
        });
        fields[name].addEventListener('blur', function() {
          setError(name, validateField(name));
        });
      });

      form.addEventListener('submit', function(event) {
        var hasError = false;
        Object.keys(fields).forEach(function(name) {
          var message = validateField(name);
          setError(name, message);
          if (message) {
            hasError = true;
          }
        });
        if (hasError) {
          event.preventDefault();
        }
      });
    })();
  </script>
  <?php endif; ?>
</body>

</html>
