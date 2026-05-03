<?php
require_once __DIR__ . '/../../config.php';
ensure_session_started();
require_freelancer();

$propositions = [];
$error = '';
$sort = (isset($_GET['sort']) && $_GET['sort'] === 'oldest') ? 'oldest' : 'recent';
$search = trim((string) ($_GET['search'] ?? ''));

try {
  $pdo = db_connect();
  ensure_propositions_user_id_column($pdo);
  $displayNames = current_user_display_names($pdo);

  if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteParams = [':id' => (int) $_GET['delete']];
    $ownershipSql = proposition_ownership_sql('p', $displayNames, $deleteParams);
    $delete = $pdo->prepare("DELETE p FROM propositions p WHERE p.id = :id AND {$ownershipSql}");
    $delete->execute($deleteParams);

    header('Location: ' . front_url('mes-propositions.php?deleted=1'));
    exit;
  }

  $order = $sort === 'oldest' ? 'ASC' : 'DESC';
  $params = [];
  $ownershipSql = proposition_ownership_sql('p', $displayNames, $params);

  $sql = "SELECT p.*, d.title AS demande_title, d.description AS demande_description, d.deadline AS demande_deadline
          FROM propositions p
          INNER JOIN demandes d ON d.id = p.demande_id
          WHERE {$ownershipSql}";

  if ($search !== '') {
    $sql .= ' AND (d.title LIKE :search OR p.message LIKE :search OR p.freelancer_name LIKE :search)';
    $params[':search'] = '%' . $search . '%';
  }

  $sql .= " ORDER BY p.created_at {$order}";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $propositions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $error = db_error_message($e);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Mes propositions - SkillBridge</title>
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

    nav ul li a.active {
      color: #ff6600;
    }

    .page-header {
      background: #fff8f0;
      padding: 40px 0 30px;
      border-bottom: 1px solid #ffe0cc;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .page-header h1 {
      color: #1a1a2e;
      font-weight: 700;
      font-size: 2rem;
      margin: 0;
    }

    .btn-add {
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

    .btn-add:hover {
      background: #e65c00;
      color: #fff;
    }

    .propositions-section {
      padding: 50px 0;
    }

    .filters-bar {
      background: #fff;
      border: 1px solid #ffe0cc;
      border-radius: 14px;
      padding: 1rem;
      margin-bottom: 1.5rem;
    }

    .filters-bar .form-control,
    .filters-bar .form-select {
      min-height: 46px;
      border-radius: 10px;
      border-color: #ffd2ad;
    }

    .btn-filter {
      background: #ff6600;
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 11px 20px;
      font-weight: 600;
    }

    .btn-reset {
      background: #fff;
      color: #ff6600;
      border: 1px solid #ffb366;
      border-radius: 10px;
      padding: 11px 20px;
      font-weight: 600;
      text-decoration: none;
    }

    .stats-bar {
      display: flex;
      gap: 1rem;
      margin-bottom: 2rem;
      flex-wrap: wrap;
    }

    .stat-badge {
      background: #fff;
      border: 1px solid #ffe0cc;
      border-radius: 10px;
      padding: 12px 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .stat-badge i {
      color: #ff6600;
      font-size: 1.2rem;
    }

    .stat-val {
      font-weight: 700;
      color: #1a1a2e;
    }

    .stat-lbl {
      font-size: 0.75rem;
      color: #6b7280;
    }

    .proposal-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      transition: all 0.3s ease;
    }

    .proposal-card:hover {
      border-color: #ff6600;
      box-shadow: 0 8px 25px rgba(255, 102, 0, 0.1);
    }

    .card-title {
      font-weight: 700;
      font-size: 1.1rem;
      color: #1a1a2e;
      margin-bottom: 0.25rem;
    }

    .card-date {
      font-size: 0.78rem;
      color: #9ca3af;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .proposal-message {
      margin: 0.8rem 0 1rem;
      color: #4b5563;
      line-height: 1.6;
    }

    .card-meta {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .price-badge,
    .deadline-badge {
      border-radius: 20px;
      padding: 4px 14px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .price-badge {
      background: #fff3eb;
      border: 1px solid #ffb366;
      color: #cc5200;
    }

    .deadline-badge {
      background: #f0fdf4;
      border: 1px solid #86efac;
      color: #166534;
      font-size: 0.85rem;
    }

    .card-actions {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }

    .btn-edit,
    .btn-delete {
      padding: 7px 16px;
      border-radius: 7px;
      font-weight: 600;
      text-decoration: none;
      transition: 0.25s;
    }

    .btn-edit {
      background: #fff3eb;
      color: #ff6600;
      border: 1px solid #ffb366;
    }

    .btn-delete {
      background: #fff5f5;
      color: #b91c1c;
      border: 1px solid #fca5a5;
      cursor: pointer;
    }

    .alert-success-sb,
    .alert-error-sb {
      border-radius: 8px;
      padding: 14px 20px;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert-success-sb {
      background: #fff3eb;
      border: 1px solid #ffb366;
      color: #cc5200;
    }

    .alert-error-sb {
      background: #fff5f5;
      border: 1px solid #fca5a5;
      color: #b91c1c;
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      background: #fff;
      border-radius: 12px;
      border: 2px dashed #ffb366;
    }

    .empty-state i {
      font-size: 3rem;
      color: #ffb366;
      margin-bottom: 1rem;
      display: block;
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
        <li><a href="<?= front_url('mes-demandes.php') ?>">Demandes</a></li>
        <li><a href="<?= front_url('mes-propositions.php') ?>" class="active">Mes propositions</a></li>
        <li><a href="<?= front_url('mon-profil-freelancer.php') ?>">Mon profil</a></li>
      </ul>
    </nav>
  </header>

  <div class="page-header container">
    <h1>Mes propositions</h1>
    <a href="<?= front_url('addprop-form.php') ?>" class="btn-add"><i class="bi bi-plus-lg"></i> Ajouter proposition</a>
  </div>

  <main class="propositions-section container">
    <?php if (isset($_GET['deleted'])): ?>
      <div class="alert-success-sb"><i class="bi bi-check-circle-fill"></i> Proposition supprimee avec succes.</div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
      <div class="alert-success-sb"><i class="bi bi-check-circle-fill"></i> Proposition modifiee avec succes.</div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert-error-sb"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="get" class="filters-bar">
      <div class="row g-3 align-items-end">
        <div class="col-md-6">
          <label for="search" class="form-label fw-semibold">Recherche</label>
          <input type="text" class="form-control" id="search" name="search" placeholder="Titre de demande, message, nom..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
          <label for="sort" class="form-label fw-semibold">Trier par date</label>
          <select class="form-select" id="sort" name="sort">
            <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Plus recente</option>
            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Plus ancienne</option>
          </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-filter flex-fill"><i class="bi bi-funnel"></i> Filtrer</button>
          <a href="<?= front_url('mes-propositions.php') ?>" class="btn-reset">Reinitialiser</a>
        </div>
      </div>
    </form>

    <?php if (!empty($propositions)): ?>
      <div class="stats-bar">
        <div class="stat-badge">
          <i class="bi bi-send-check"></i>
          <div>
            <div class="stat-val"><?= count($propositions) ?></div>
            <div class="stat-lbl">Proposition<?= count($propositions) > 1 ? 's' : '' ?></div>
          </div>
        </div>
        <div class="stat-badge">
          <i class="bi bi-wallet2"></i>
          <div>
            <div class="stat-val"><?= number_format(array_sum(array_map('floatval', array_column($propositions, 'price'))), 0, ',', ' ') ?> DT</div>
            <div class="stat-lbl">Total propose</div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (empty($propositions) && !$error): ?>
      <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <h4>Aucune proposition pour l'instant</h4>
        <p>Consulte les demandes disponibles et envoie ta premiere proposition.</p>
        <a href="<?= front_url('mes-demandes.php') ?>" class="btn-add"><i class="bi bi-search"></i> Voir les demandes</a>
      </div>
    <?php else: ?>
      <?php foreach ($propositions as $p): ?>
        <div class="proposal-card">
          <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
              <div class="card-title"><?= htmlspecialchars($p['demande_title']) ?></div>
              <div class="card-date">
                <i class="bi bi-clock-history"></i>
                Envoyee le <?= date('d/m/Y a H:i', strtotime($p['created_at'])) ?>
              </div>
            </div>
            <div class="card-actions">
              <a href="<?= front_url('edit-proposition.php?id=' . (int) $p['id']) ?>" class="btn-edit">
                <i class="bi bi-pencil"></i> Modifier
              </a>
              <button class="btn-delete" onclick="confirmDelete(<?= (int) $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['demande_title'])) ?>')">
                <i class="bi bi-trash"></i> Supprimer
              </button>
            </div>
          </div>

          <p class="proposal-message"><?= nl2br(htmlspecialchars($p['message'])) ?></p>
          <div class="card-meta">
            <span class="price-badge"><i class="bi bi-cash-stack"></i> <?= number_format((float) $p['price'], 0, ',', ' ') ?> DT</span>
            <span class="deadline-badge"><i class="bi bi-calendar-event"></i> Demande avant le <?= date('d/m/Y', strtotime($p['demande_deadline'])) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </main>

  <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content p-3">
        <h5><i class="bi bi-exclamation-triangle text-danger"></i> Confirmer la suppression</h5>
        <p>Supprimer la proposition pour <strong id="deleteTitle"></strong> ?</p>
        <div class="d-flex gap-2 justify-content-end">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
          <a href="#" id="deleteConfirmBtn" class="btn btn-danger">Supprimer</a>
        </div>
      </div>
    </div>
  </div>

  <footer>
    <p>Â© <strong>SkillBridge</strong> - Tous droits reserves</p>
    <p>Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a></p>
  </footer>

  <script src="../../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script>
    function confirmDelete(id, title) {
      document.getElementById('deleteTitle').textContent = '"' + title + '"';
      document.getElementById('deleteConfirmBtn').href = 'mes-propositions.php?delete=' + id;
      new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
  </script>
</body>

</html>
