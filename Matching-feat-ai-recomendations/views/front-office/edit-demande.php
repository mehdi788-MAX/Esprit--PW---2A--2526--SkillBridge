<?php
require_once __DIR__ . '/../../config.php';
ensure_session_started();
require_client();

$error = '';
$demande = null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: mes-demandes.php');
    exit;
}

$id = (int) $_GET['id'];

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare('SELECT * FROM demandes WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $demande = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$demande || (int) ($demande['user_id'] ?? 0) !== current_user_id()) {
        header('Location: mes-demandes.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $price = trim((string) ($_POST['price'] ?? ''));
        $deadline = trim((string) ($_POST['deadline'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));

        if ($title === '' || $price === '' || !is_numeric($price) || $deadline === '' || $description === '') {
            $error = 'Veuillez remplir tous les champs correctement.';
        } else {
            $update = $pdo->prepare(
                'UPDATE demandes
                 SET title = :title,
                     price = :price,
                     deadline = :deadline,
                     description = :description
                 WHERE id = :id'
            );

            $update->execute([
                ':title' => $title,
                ':price' => $price,
                ':deadline' => $deadline,
                ':description' => $description,
                ':id' => $id,
            ]);

            header('Location: mes-demandes.php?updated=1');
            exit;
        }

        $demande['title'] = $title;
        $demande['price'] = $price;
        $demande['deadline'] = $deadline;
        $demande['description'] = $description;
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
  <title>Modifier une demande - SkillBridge</title>
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
        <li><a href="<?= front_url('Addrequest.php') ?>">Publier une demande</a></li>
      </ul>
    </nav>
  </header>

  <div class="page-header">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h1>Modifier une demande</h1>
        <p>Met a jour le titre, le budget, la date limite et la description de ta demande.</p>
      </div>
      <a href="<?= front_url('mes-demandes.php') ?>" class="btn-back">
        <i class="bi bi-arrow-left"></i>
        Retour aux demandes
      </a>
    </div>
  </div>

  <main class="edit-section container">
    <?php if ($error !== ''): ?>
      <div class="alert-error-sb"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="info-card">
      <h2><?= htmlspecialchars($demande['title'] ?? '') ?></h2>
      <p><?= htmlspecialchars($demande['description'] ?? '') ?></p>
      <div class="meta-row">
        <span class="meta-badge"><i class="bi bi-wallet2"></i> <?= htmlspecialchars((string) ($demande['price'] ?? '')) ?> DT</span>
        <span class="meta-badge"><i class="bi bi-calendar-event"></i> Avant le <?= htmlspecialchars((string) ($demande['deadline'] ?? '')) ?></span>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="edit-card">
          <h2>Edition de la demande</h2>
          <p class="lead">Les changements seront visibles directement dans la liste de tes demandes.</p>

          <form method="post" class="d-flex flex-column gap-3">
            <div>
              <label for="title" class="form-label">Titre</label>
              <input type="text" class="form-control" id="title" name="title" required maxlength="150" value="<?= htmlspecialchars($demande['title'] ?? '') ?>">
            </div>

            <div>
              <label for="price" class="form-label">Budget (DT)</label>
              <input type="number" class="form-control" id="price" name="price" min="1" step="0.01" required value="<?= htmlspecialchars((string) ($demande['price'] ?? '')) ?>">
            </div>

            <div>
              <label for="deadline" class="form-label">Date limite</label>
              <input type="date" class="form-control" id="deadline" name="deadline" required value="<?= htmlspecialchars((string) ($demande['deadline'] ?? '')) ?>">
            </div>

            <div>
              <label for="description" class="form-label">Description</label>
              <textarea class="form-control" id="description" name="description" rows="6" required><?= htmlspecialchars($demande['description'] ?? '') ?></textarea>
            </div>

            <div>
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
</body>

</html>
