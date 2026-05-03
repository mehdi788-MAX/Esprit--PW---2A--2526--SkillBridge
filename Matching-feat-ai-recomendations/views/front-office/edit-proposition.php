<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../models/proposition_validation.php';
ensure_session_started();
require_freelancer();

$error = '';
$proposition = null;
$demande = null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . front_url('mes-propositions.php'));
    exit;
}

$id = (int) $_GET['id'];

try {
    $pdo = db_connect();
    ensure_propositions_user_id_column($pdo);
    $displayNames = current_user_display_names($pdo);

    $params = [':id' => $id];
    $ownershipSql = proposition_ownership_sql('p', $displayNames, $params);
    $stmt = $pdo->prepare("SELECT p.* FROM propositions p WHERE p.id = :id AND {$ownershipSql}");
    $stmt->execute($params);
    $proposition = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$proposition) {
        header('Location: ' . front_url('mes-propositions.php'));
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM demandes WHERE id = :id');
    $stmt->execute([':id' => $proposition['demande_id']]);
    $demande = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $freelancerName = trim((string) ($_POST['freelancer_name'] ?? ''));
        $price = trim((string) ($_POST['price'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        $validationErrors = validate_proposition_form_input($_POST, false);
        if ($validationErrors !== []) {
            $error = implode(' ', $validationErrors);
        } else {
            $update = $pdo->prepare(
                'UPDATE propositions
                 SET freelancer_name = :freelancer_name,
                     price = :price,
                     message = :message,
                     user_id = :user_id
                 WHERE id = :id'
            );
            $update->execute([
                ':freelancer_name' => $freelancerName,
                ':price' => $price,
                ':message' => $message,
                ':user_id' => current_user_id(),
                ':id' => $id,
            ]);

            header('Location: ' . front_url('mes-propositions.php?updated=1'));
            exit;
        }

        $proposition['freelancer_name'] = $freelancerName;
        $proposition['price'] = $price;
        $proposition['message'] = $message;
    }
} catch (PDOException $e) {
    $error = db_error_message($e);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Modifier une proposition - SkillBridge</title>
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

    .edit-section {
      padding: 50px 0;
    }

    .info-card,
    .edit-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      box-shadow: 0 8px 25px rgba(255, 102, 0, 0.06);
    }

    .info-card {
      padding: 1.5rem;
      margin-bottom: 1.5rem;
    }

    .edit-card {
      padding: 2rem;
    }

    .info-card h2,
    .edit-card h2 {
      color: #1a1a2e;
      font-weight: 700;
      margin-bottom: 0.75rem;
    }

    .info-card p,
    .edit-card .lead {
      color: #4b5563;
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

    .form-control:focus {
      border-color: #ff6600;
      box-shadow: 0 0 0 0.2rem rgba(255, 102, 0, 0.15);
    }

    .alert-error-sb {
      background: #fff5f5;
      border: 1px solid #fca5a5;
      border-radius: 8px;
      padding: 14px 20px;
      color: #b91c1c;
      margin-bottom: 1.5rem;
    }

    .meta-row {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
      margin-top: 1rem;
    }

    .meta-badge {
      background: #fff3eb;
      border: 1px solid #ffb366;
      border-radius: 20px;
      padding: 4px 14px;
      color: #cc5200;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 5px;
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
    .form-group textarea {
      padding: 12px 15px;
      border-radius: 8px;
      border: 1px solid #ffb366;
      outline: none;
      font-size: 15px;
      transition: 0.3s;
    }

    .form-group input:focus,
    .form-group textarea:focus {
      border-color: #ff6600;
      box-shadow: 0 0 6px rgba(255, 102, 0, 0.4);
    }

    .form-group textarea {
      resize: none;
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
  </style>
</head>

<body>
  <header class="d-flex align-items-center justify-content-between px-4 py-3">
    <a href="<?= front_url('index.php') ?>" class="logo">SkillBridge</a>
    <nav>
      <ul class="d-flex align-items-center">
        <li><a href="<?= front_url('index.php') ?>">Accueil</a></li>
        <li><a href="<?= front_url('index.php#propositions') ?>">Propositions</a></li>
        <li><a href="<?= front_url('mes-demandes.php') ?>">Demandes</a></li>
        <li><a href="<?= front_url('mes-propositions.php') ?>" class="active">Mes propositions</a></li>
      </ul>
    </nav>
  </header>

  <div class="page-header">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h1>Modifier une proposition</h1>
        <p>Met a jour ton offre sans perdre le lien avec la demande concernee.</p>
      </div>
      <a href="<?= front_url('mes-propositions.php') ?>" class="btn-back">
        <i class="bi bi-arrow-left"></i>
        Retour a mes propositions
      </a>
    </div>
  </div>

  <main class="edit-section container">
    <?php if ($error !== ''): ?>
      <div class="alert-error-sb text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($demande): ?>
      <div class="info-card">
        <h2><?= htmlspecialchars($demande['title']) ?></h2>
        <p><?= htmlspecialchars($demande['description']) ?></p>
        <div class="meta-row">
          <span class="meta-badge"><i class="bi bi-wallet2"></i> <?= htmlspecialchars((string) $demande['price']) ?> DT</span>
          <span class="meta-badge"><i class="bi bi-calendar-event"></i> Avant le <?= htmlspecialchars((string) $demande['deadline']) ?></span>
        </div>
      </div>
    <?php endif; ?>

    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="edit-card">
          <h2>Edition de la proposition</h2>
          <p class="lead">Modifie le nom affiche, le prix et le message de ta proposition.</p>

          <form method="post" class="d-flex flex-column gap-3" id="editProposalForm" novalidate>
            <div class="form-group">
              <label for="freelancer_name">Nom affiche</label>
              <input type="text" id="freelancer_name" name="freelancer_name" maxlength="120" value="<?= htmlspecialchars($proposition['freelancer_name'] ?? '') ?>">
              <div class="field-error" data-error-for="freelancer_name"></div>
            </div>

            <div class="form-group">
              <label for="price">Prix propose (DT)</label>
              <input type="number" id="price" name="price" min="1" step="0.01" value="<?= htmlspecialchars((string) ($proposition['price'] ?? '')) ?>">
              <div class="field-error" data-error-for="price"></div>
            </div>

            <div class="form-group">
              <label for="message">Message / detail de l'offre</label>
              <textarea id="message" name="message" rows="6"><?= htmlspecialchars($proposition['message'] ?? '') ?></textarea>
              <div class="field-error" data-error-for="message"></div>
            </div>

            <div class="form-group text-center">
              <button type="submit" class="btn-submit">
                <i class="bi bi-check2-circle"></i>
                Enregistrer les modifications
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>

  <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    (function () {
      var form = document.getElementById('editProposalForm');
      if (!form) {
        return;
      }

      var fields = {
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

      Object.keys(fields).forEach(function (name) {
        fields[name].addEventListener('input', function () {
          setError(name, validateField(name));
        });
        fields[name].addEventListener('blur', function () {
          setError(name, validateField(name));
        });
      });

      form.addEventListener('submit', function (event) {
        var hasError = false;
        Object.keys(fields).forEach(function (name) {
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
</body>

</html>
