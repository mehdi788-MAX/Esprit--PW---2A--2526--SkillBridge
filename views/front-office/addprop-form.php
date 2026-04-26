<?php
require_once __DIR__ . '/../../config.php';
ensure_session_started();
require_freelancer();

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
  $stmt = $pdo->query('SELECT id, title, deadline FROM demandes ORDER BY created_at DESC');
  $demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $demandes = [];
}

if ($formValues['demande_id'] === '' && !empty($demandes)) {
  $formValues['demande_id'] = (string) $demandes[0]['id'];
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Ajouter une proposition - SkillBridge</title>
  <link href="../../assets/images/favicon.png" rel="icon">
  <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: #f9f9f9;
      font-family: 'Roboto', sans-serif;
    }

    header {
      background: #fff;
      border-bottom: 1px solid #e5e7eb;
      position: sticky;
      top: 0;
      z-index: 999;
    }

    .logo {
      font-weight: 700;
      font-size: 1.5rem;
      color: #ff6600;
      text-decoration: none;
    }

    nav ul {
      list-style: none;
      margin: 0;
      padding: 0;
      display: flex;
      gap: 1rem;
    }

    nav ul li a {
      color: #1a1a2e;
      text-decoration: none;
      font-weight: 500;
      padding: 0.5rem 1rem;
    }

    .page-header {
      background: #fff8f0;
      padding: 40px 0 30px;
      border-bottom: 1px solid #ffe0cc;
    }

    .page-header h1 {
      color: #1a1a2e;
      font-weight: 700;
      font-size: 2rem;
      margin: 0 0 0.5rem;
    }

    .page-header p {
      margin: 0;
      color: #6b7280;
      max-width: 760px;
    }

    .btn-back,
    .btn-submit {
      background: #ff6600;
      color: #fff;
      border: none;
      padding: 11px 24px;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      transition: 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-back:hover,
    .btn-submit:hover {
      background: #e65c00;
      color: #fff;
    }

    .addprop-section {
      padding: 50px 0;
    }

    .addprop-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      padding: 2rem;
      box-shadow: 0 8px 25px rgba(255, 102, 0, 0.06);
    }

    .addprop-card h2 {
      color: #1a1a2e;
      font-weight: 700;
      margin-bottom: 0.75rem;
    }

    .addprop-card .lead {
      color: #4b5563;
      margin-bottom: 1.5rem;
    }

    .form-label {
      font-weight: 600;
      color: #cc5200;
    }

    .form-control {
      border-radius: 8px;
      border-color: #ffb366;
      padding: 0.85rem 1rem;
    }

    .form-select {
      border-radius: 8px;
      border-color: #ffb366;
      padding: 0.85rem 1rem;
      min-height: 54px;
    }

    .form-control:focus,
    .form-select:focus {
      border-color: #ff6600;
      box-shadow: 0 0 0 0.2rem rgba(255, 102, 0, 0.15);
    }

    .help-box {
      background: #fff8f0;
      border: 1px solid #ffe0cc;
      border-radius: 12px;
      padding: 1rem 1.25rem;
      color: #6b7280;
      margin-bottom: 1.5rem;
    }

    .alert-success-sb {
      background: #fff3eb;
      border: 1px solid #ffb366;
      border-radius: 8px;
      padding: 14px 20px;
      color: #cc5200;
      margin-bottom: 1.5rem;
    }

    .alert-error-sb {
      background: #fff5f5;
      border: 1px solid #fca5a5;
      border-radius: 8px;
      padding: 14px 20px;
      color: #b91c1c;
      margin-bottom: 1.5rem;
    }

    .field-error {
      color: #b91c1c;
      font-size: 0.9rem;
      margin-top: 0.4rem;
      min-height: 20px;
    }

    .input-error {
      border-color: #dc2626 !important;
      box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.12) !important;
    }

    footer {
      background: #1a1a2e;
      color: #fff;
      padding: 30px 0;
      text-align: center;
    }

    footer a {
      color: #ff6600;
      text-decoration: none;
    }
  </style>
</head>

<body>

  <header class="d-flex align-items-center justify-content-between px-4 py-3">
    <a href="<?= front_url('index.php') ?>" class="logo">SkillBridge</a>
    <nav>
      <ul class="d-flex align-items-center">
        <li><a href="<?= front_url('index.php') ?>">Accueil</a></li>
        <li><a href="<?= front_url('index.php#propositions') ?>">Propositions</a></li>
        <li><a href="<?= front_url('mes-demandes.php') ?>"><?= front_demands_label() ?></a></li>
      </ul>
    </nav>
  </header>

  <div class="page-header">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h1>Ajouter une proposition</h1>
        <p>Envoie ton offre pour cette demande avec ton prix, ton message et tes details de livraison.</p>
      </div>
      <a href="javascript:history.back()" class="btn-back">
        <i class="bi bi-arrow-left"></i>
        Retour
      </a>
    </div>
  </div>

  <main class="addprop-section container">
    <div id="flash-ok" class="alert-success-sb d-none">Proposition enregistree avec succes.</div>
    <div id="flash-err" class="alert-error-sb d-none"></div>

    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="addprop-card">
          <h2>Formulaire de proposition</h2>
          <p class="lead">Reponds directement a une demande existante. Si tu arrives depuis une demande precise, son titre est deja selectionne.</p>

          <div class="help-box">
            Choisis la demande concernee dans la liste. En base de donnees, la proposition restera liee a l'identifiant technique de cette demande.
          </div>

          <form action="<?= front_url('addprop.php') ?>" method="post" class="d-flex flex-column gap-3" id="proposalForm" novalidate>
            <div>
              <label for="demande_id" class="form-label">Titre de la demande</label>
              <select class="form-select" name="demande_id" id="demande_id" <?= empty($demandes) ? 'disabled' : '' ?>>
                <option value="">Choisir une demande</option>
                <?php foreach ($demandes as $demande): ?>
                  <option value="<?= (int) $demande['id'] ?>" <?= $formValues['demande_id'] === (string) $demande['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($demande['title']) ?><?= !empty($demande['deadline']) ? ' - avant le ' . date('d/m/Y', strtotime($demande['deadline'])) : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="field-error" data-error-for="demande_id"></div>
            </div>

            <div>
              <label for="freelancer_name" class="form-label">Nom affiche</label>
              <input type="text" class="form-control" name="freelancer_name" id="freelancer_name" maxlength="120" placeholder="Votre nom ou pseudo" value="<?= htmlspecialchars($formValues['freelancer_name']) ?>">
              <div class="field-error" data-error-for="freelancer_name"></div>
            </div>

            <div>
              <label for="price" class="form-label">Prix propose (DT)</label>
              <input type="number" class="form-control" name="price" id="price" step="1" min="1" placeholder="Ex. 200" value="<?= htmlspecialchars($formValues['price']) ?>">
              <div class="field-error" data-error-for="price"></div>
            </div>

            <div>
              <label for="message" class="form-label">Message / detail de l'offre</label>
              <textarea class="form-control" name="message" id="message" rows="6" placeholder="Decrivez votre offre, delais, livrables, experience..."><?= htmlspecialchars($formValues['message']) ?></textarea>
              <div class="field-error" data-error-for="message"></div>
            </div>

            <div>
              <button type="submit" class="btn-submit">
                <i class="bi bi-send"></i>
                Envoyer la proposition
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>

  <footer>
    <p>© <strong>SkillBridge</strong> - Tous droits reserves</p>
    <p>Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a></p>
  </footer>

  <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    (function () {
      var q = new URLSearchParams(window.location.search);
      if (q.get('ok') === '1') {
        document.getElementById('flash-ok').classList.remove('d-none');
      }

      var err = q.get('err');
      if (err) {
        var el = document.getElementById('flash-err');
        el.textContent = err;
        el.classList.remove('d-none');
      }

      var form = document.getElementById('proposalForm');
      var fields = ['demande_id', 'freelancer_name', 'price', 'message'];

      if (document.getElementById('demande_id').disabled) {
        setError('demande_id', 'Aucune demande disponible pour le moment.');
      }

      function setError(field, message) {
        var input = document.getElementById(field);
        var error = document.querySelector('[data-error-for="' + field + '"]');
        if (input) input.classList.add('input-error');
        if (error) error.textContent = message;
      }

      function clearError(field) {
        var input = document.getElementById(field);
        var error = document.querySelector('[data-error-for="' + field + '"]');
        if (input) input.classList.remove('input-error');
        if (error) error.textContent = '';
      }

      fields.forEach(function (field) {
        var input = document.getElementById(field);
        if (!input) {
          return;
        }

        input.addEventListener('input', function () {
          clearError(field);
        });

        input.addEventListener('change', function () {
          clearError(field);
        });
      });

      form.addEventListener('submit', function (e) {
        var hasError = false;
        fields.forEach(clearError);

        var demande = document.getElementById('demande_id').value.trim();
        var name = document.getElementById('freelancer_name').value.trim();
        var price = document.getElementById('price').value.trim();
        var message = document.getElementById('message').value.trim();

        if (!demande || Number(demande) < 1) {
          setError('demande_id', 'Veuillez choisir une demande valide.');
          hasError = true;
        }

        if (document.getElementById('demande_id').disabled) {
          setError('demande_id', 'Aucune demande disponible pour le moment.');
          hasError = true;
        }

        if (name.length < 3) {
          setError('freelancer_name', 'Le nom affiche doit contenir au moins 3 caracteres.');
          hasError = true;
        }

        if (!price || Number(price) < 1) {
          setError('price', 'Le prix doit etre superieur ou egal a 1 DT.');
          hasError = true;
        }

        if (message.length < 15) {
          setError('message', 'Le message doit contenir au moins 15 caracteres.');
          hasError = true;
        }

        if (hasError) {
          e.preventDefault();
        }
      });
    })();
  </script>
</body>

</html>
