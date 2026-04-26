<?php
require_once __DIR__ . '/../../config.php';
ensure_session_started();
require_login();

if (is_admin() || is_freelancer()) {
    header('Location: ' . front_url('mes-demandes.php'));
    exit;
}

$propositions = [];
$demande = null;
$error = '';
$successMessage = '';
$sort = (isset($_GET['sort']) && $_GET['sort'] === 'oldest') ? 'oldest' : 'recent';
$search = trim((string) ($_GET['search'] ?? ''));

if (!isset($_GET['demande_id']) || !is_numeric($_GET['demande_id'])) {
    die('Demande non trouvee.');
}

$demande_id = (int) $_GET['demande_id'];

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare('SELECT * FROM demandes WHERE id = ?');
    $stmt->execute([$demande_id]);
    $demande = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$demande || (int) ($demande['user_id'] ?? 0) !== current_user_id()) {
        header('Location: ' . front_url('mes-demandes.php'));
        exit;
    }

    $order = $sort === 'oldest' ? 'ASC' : 'DESC';
    $sql = 'SELECT p.*, d.title AS demande_title
            FROM propositions p
            INNER JOIN demandes d ON d.id = p.demande_id
            WHERE p.demande_id = :demande_id';
    $params = [':demande_id' => $demande_id];

    if ($search !== '') {
        $sql .= ' AND d.title LIKE :search';
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY p.created_at {$order}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $propositions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = db_error_message($e);
}

if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $successMessage = 'Proposition modifiee avec succes.';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Propositions - SkillBridge</title>
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

    .btn-back {
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

    .btn-back:hover {
      background: #e65c00;
      color: #fff;
    }

    .btn-outline-sb {
      background: #fff;
      color: #ff6600;
      border: 1px solid #ffb366;
      padding: 11px 24px;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      transition: 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-outline-sb:hover {
      background: #fff3eb;
      color: #e65c00;
    }

    .propositions-section {
      padding: 50px 0;
    }

    .stats-bar {
      display: flex;
      gap: 1rem;
      margin-bottom: 2rem;
      flex-wrap: wrap;
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

    .btn-filter:hover {
      background: #e65c00;
      color: #fff;
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

    .btn-reset:hover {
      background: #fff3eb;
      color: #e65c00;
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

    .demande-card {
      background: linear-gradient(135deg, #fff8f0 0%, #ffffff 100%);
      border: 1px solid #ffe0cc;
      border-radius: 14px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
    }

    .demande-card h4 {
      font-weight: 700;
      color: #1a1a2e;
      margin-bottom: 0.75rem;
    }

    .demande-card p {
      color: #4b5563;
      line-height: 1.6;
      margin-bottom: 1rem;
    }

    .card-meta {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .price-badge {
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

    .deadline-badge {
      border-radius: 20px;
      padding: 4px 14px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 0.85rem;
      background: #f0fdf4;
      border: 1px solid #86efac;
      color: #166534;
    }

    .proposition-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      padding: 1.5rem;
      margin-bottom: 1rem;
      transition: all 0.3s ease;
    }

    .proposition-card:hover {
      border-color: #ff6600;
      box-shadow: 0 8px 25px rgba(255, 102, 0, 0.1);
    }

    .proposition-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 1rem;
      margin-bottom: 0.75rem;
    }

    .proposition-author {
      font-weight: 700;
      font-size: 1.05rem;
      color: #1a1a2e;
      margin: 0;
    }

    .proposition-subtitle {
      color: #9ca3af;
      font-size: 0.82rem;
      display: flex;
      align-items: center;
      gap: 6px;
      margin-top: 0.2rem;
    }

    .proposition-message {
      color: #4b5563;
      line-height: 1.7;
      margin: 0;
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

    .alert-error-sb {
      background: #fff5f5;
      border: 1px solid #fca5a5;
      border-radius: 8px;
      padding: 14px 20px;
      color: #b91c1c;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert-success-sb {
      background: #fff3eb;
      border: 1px solid #ffb366;
      border-radius: 8px;
      padding: 14px 20px;
      color: #cc5200;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .proposition-actions {
      display: flex;
      justify-content: flex-end;
      margin-top: 1rem;
    }

    .btn-edit-sb {
      background: #fff;
      color: #ff6600;
      border: 1px solid #ffb366;
      padding: 8px 14px;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      transition: 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .btn-edit-sb:hover {
      background: #fff3eb;
      color: #e65c00;
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
        <li><a href="<?= front_url('mes-demandes.php') ?>" class="active"><?= front_demands_label() ?></a></li>
        <li><a href="<?= front_url('Addrequest.php') ?>">Publier une demande</a></li>
      </ul>
    </nav>
  </header>

  <div class="page-header">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h1>Propositions recues</h1>
        <p>Consulte les reponses envoyees pour cette demande et compare rapidement les offres disponibles.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="<?= front_url('mes-demandes.php') ?>" class="btn-back">
          <i class="bi bi-arrow-left"></i>
          Retour aux demandes
        </a>
      </div>
    </div>
  </div>

  <main class="propositions-section container">

    <?php if ($successMessage !== ''): ?>
      <div class="alert-success-sb"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert-error-sb"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($demande): ?>
      <div class="stats-bar">
        <div class="stat-badge">
          <i class="bi bi-chat-dots"></i>
          <div>
            <div class="stat-val"><?= count($propositions) ?></div>
            <div class="stat-lbl">Proposition<?= count($propositions) > 1 ? 's' : '' ?></div>
          </div>
        </div>
        <div class="stat-badge">
          <i class="bi bi-wallet2"></i>
          <div>
            <div class="stat-val">
              <?= empty($propositions) ? '0 DT' : number_format((float) min(array_column($propositions, 'price')), 0, ',', ' ') . ' DT' ?>
            </div>
            <div class="stat-lbl">Prix le plus bas</div>
          </div>
        </div>
        <div class="stat-badge">
          <i class="bi bi-calendar-event"></i>
          <div>
            <div class="stat-val"><?= date('d/m/Y', strtotime($demande['deadline'])) ?></div>
            <div class="stat-lbl">Date limite</div>
          </div>
        </div>
      </div>

      <div class="demande-card">
        <h4><?= htmlspecialchars($demande['title']) ?></h4>
        <p><?= htmlspecialchars($demande['description']) ?></p>
        <div class="card-meta">
          <span class="price-badge"><i class="bi bi-wallet2"></i> <?= number_format((float) $demande['price'], 0, ',', ' ') ?> DT</span>
          <span class="deadline-badge"><i class="bi bi-clock-history"></i> Avant le <?= date('d/m/Y', strtotime($demande['deadline'])) ?></span>
        </div>
      </div>

      <form method="get" class="filters-bar">
        <input type="hidden" name="demande_id" value="<?= (int) $demande_id ?>">
        <div class="row g-3 align-items-end">
          <div class="col-md-6">
            <label for="search" class="form-label fw-semibold">Recherche par titre</label>
            <input type="text" class="form-control" id="search" name="search" placeholder="Titre de la demande" value="<?= htmlspecialchars($search) ?>">
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
            <a href="<?= front_url('proposition.php?demande_id=' . (int) $demande_id) ?>" class="btn-reset">Reinitialiser</a>
          </div>
        </div>
      </form>
    <?php endif; ?>

    <?php if (!$demande && !$error): ?>
      <div class="empty-state">
        <i class="bi bi-search"></i>
        <h4>Demande introuvable</h4>
        <p>Cette demande n'existe pas ou n'est plus disponible.</p>
      </div>
    <?php elseif (empty($propositions)): ?>
      <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <h4>Aucune proposition pour l'instant</h4>
        <p>Les freelancers n'ont pas encore repondu a cette demande.</p>
      </div>
    <?php else: ?>
      <?php foreach ($propositions as $p): ?>
        <div class="proposition-card">
          <div class="proposition-top">
            <div>
              <p class="proposition-author mb-0"><?= htmlspecialchars($p['freelancer_name']) ?></p>
              <div class="proposition-subtitle">
                <i class="bi bi-clock-history"></i>
                Envoye le <?= date('d/m/Y a H:i', strtotime($p['created_at'])) ?>
              </div>
            </div>
            <span class="price-badge"><i class="bi bi-cash-stack"></i> <?= number_format((float) $p['price'], 0, ',', ' ') ?> DT</span>
          </div>

          <p class="proposition-message"><?= nl2br(htmlspecialchars($p['message'])) ?></p>

          <div class="proposition-actions">
            <a href="<?= front_url('edit-proposition.php?id=' . (int) $p['id']) ?>" class="btn-edit-sb">
              <i class="bi bi-pencil"></i>
              Modifier
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </main>

  <footer>
    <p>© <strong>SkillBridge</strong> - Tous droits reserves</p>
    <p>Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a></p>
  </footer>

</body>

</html>
