<?php
$db_host = 'localhost';
$db_name = 'skillbridge';
$db_user = 'root';
$db_pass = '';

$demandes = [];
$error = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->prepare("DELETE FROM demandes WHERE id = :id")->execute([':id' => (int)$_GET['delete']]);
        header("Location: mes-demandes.php?deleted=1");
        exit;
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $demandes = $pdo->query("SELECT * FROM demandes ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Mes Demandes – SkillBridge</title>
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background:#f9f9f9; font-family: 'Roboto', sans-serif; }
    header { background:#fff; border-bottom:1px solid #e5e7eb; position:sticky; top:0; z-index:999; }
    .logo { font-weight:700; font-size:1.5rem; color:#ff6600; text-decoration:none; }
    nav ul { list-style:none; margin:0; padding:0; display:flex; gap:1rem; }
    nav ul li a { color:#1a1a2e; text-decoration:none; font-weight:500; padding:0.5rem 1rem; }
    nav ul li a.active { color:#ff6600; }
    .page-header { background:#fff8f0; padding:40px 0 30px; border-bottom:1px solid #ffe0cc; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; }
    .page-header h1 { color:#1a1a2e; font-weight:700; font-size:2rem; margin:0; }
    .btn-add { background:#ff6600; color:#fff; border:none; padding:11px 24px; border-radius:8px; font-weight:600; text-decoration:none; transition:0.3s; }
    .btn-add:hover { background:#e65c00; color:#fff; }
    .stats-bar { display:flex; gap:1rem; margin-bottom:2rem; flex-wrap:wrap; }
    .stat-badge { background:#fff; border:1px solid #ffe0cc; border-radius:10px; padding:12px 20px; display:flex; align-items:center; gap:10px; }
    .stat-badge i { color:#ff6600; font-size:1.2rem; }
    .stat-val { font-weight:700; color:#1a1a2e; }
    .stat-lbl { font-size:0.75rem; color:#6b7280; }
    .demandes-section { padding:50px 0; }
    .demande-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:1.5rem; margin-bottom:1.5rem; transition:all 0.3s ease; }
    .demande-card:hover { border-color:#ff6600; box-shadow:0 8px 25px rgba(255,102,0,0.1); }
    .card-title { font-weight:700; font-size:1.1rem; color:#1a1a2e; }
    .card-date { font-size:0.78rem; color:#9ca3af; display:flex; align-items:center; gap:4px; }
    .card-description { margin:0.8rem 0 1rem; color:#4b5563; line-height:1.6; }
    .card-meta { display:flex; gap:1rem; flex-wrap:wrap; }
    .price-badge { background:#fff3eb; border:1px solid #ffb366; border-radius:20px; padding:4px 14px; color:#cc5200; font-weight:700; display:flex; align-items:center; gap:5px; }
    .deadline-badge { border-radius:20px; padding:4px 14px; font-weight:600; display:flex; align-items:center; gap:5px; font-size:0.85rem; }
    .deadline-badge.green { background:#f0fdf4; border:1px solid #86efac; color:#166534; }
    .deadline-badge.red { background:#fff5f5; border:1px solid #fca5a5; color:#b91c1c; }
    .card-actions { display:flex; gap:0.5rem; }
    .btn-edit { background:#fff3eb; color:#ff6600; border:1px solid #ffb366; padding:7px 16px; border-radius:7px; font-weight:600; text-decoration:none; transition:0.25s; }
    .btn-edit:hover { background:#ff6600; color:#fff; border-color:#ff6600; }
    .btn-delete { background:#fff5f5; color:#b91c1c; border:1px solid #fca5a5; padding:7px 16px; border-radius:7px; font-weight:600; cursor:pointer; transition:0.25s; }
    .btn-delete:hover { background:#b91c1c; color:#fff; border-color:#b91c1c; }
    .alert-success-sb { background:#fff3eb; border:1px solid #ffb366; border-radius:8px; padding:14px 20px; color:#cc5200; margin-bottom:1.5rem; display:flex; align-items:center; gap:10px; font-weight:500; }
    .alert-error-sb { background:#fff5f5; border:1px solid #fca5a5; border-radius:8px; padding:14px 20px; color:#b91c1c; margin-bottom:1.5rem; display:flex; align-items:center; gap:10px; }
    .empty-state { text-align:center; padding:60px 20px; background:#fff; border-radius:12px; border:2px dashed #ffb366; }
    .empty-state i { font-size:3rem; color:#ffb366; margin-bottom:1rem; display:block; }
    .modal-content { border-radius:14px; }
    footer { background:#1a1a2e; color:#fff; padding:30px 0; text-align:center; }
    footer a { color:#ff6600; text-decoration:none; }
  </style>
</head>
<body>

<header class="d-flex align-items-center justify-content-between px-4 py-3">
  <a href="index.html" class="logo">SkillBridge</a>
  <nav>
    <ul class="d-flex align-items-center">
      <li><a href="index.html">Accueil</a></li>
      <li><a href="index.html#propositions">Propositions</a></li>
      <li><a href="mes-demandes.php" class="active">Mes Demandes</a></li>
      <li><a href="Addrequest.php">Publier une demande</a></li>
    </ul>
  </nav>
</header>

<div class="page-header container">
  <h1>Mes Demandes</h1>
  <a href="Addrequest.php" class="btn-add"><i class="bi bi-plus-lg"></i> Publier une demande</a>
</div>

<main class="demandes-section container">

  <?php if (isset($_GET['deleted'])): ?>
    <div class="alert-success-sb"><i class="bi bi-check-circle-fill"></i> Demande supprimée avec succès.</div>
  <?php endif; ?>

  <?php if (isset($_GET['updated'])): ?>
    <div class="alert-success-sb"><i class="bi bi-check-circle-fill"></i> Demande modifiée avec succès.</div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert-error-sb"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (!empty($demandes)): ?>
    <div class="stats-bar">
      <div class="stat-badge">
        <i class="bi bi-collection"></i>
        <div>
          <div class="stat-val"><?= count($demandes) ?></div>
          <div class="stat-lbl">Demande<?= count($demandes) > 1 ? 's' : '' ?></div>
        </div>
      </div>
      <div class="stat-badge">
        <i class="bi bi-wallet2"></i>
        <div>
          <div class="stat-val"><?= number_format(array_sum(array_column($demandes, 'price')), 0, ',', ' ') ?> DT</div>
          <div class="stat-lbl">Budget total</div>
        </div>
      </div>
      <div class="stat-badge">
        <i class="bi bi-calendar-check"></i>
        <div>
          <div class="stat-val"><?= count(array_filter($demandes, fn($d) => $d['deadline'] >= date('Y-m-d'))) ?></div>
          <div class="stat-lbl">En cours</div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if (empty($demandes) && !$error): ?>
    <div class="empty-state">
      <i class="bi bi-inbox"></i>
      <h4>Aucune demande publiée pour l'instant</h4>
      <p>Publiez votre première demande et recevez des propositions de freelancers qualifiés.</p>
      <a href="Addrequest.php" class="btn-add"><i class="bi bi-plus-lg"></i> Publier ma première demande</a>
    </div>
  <?php else: ?>
    <?php foreach ($demandes as $i => $d):
      $isExpired = $d['deadline'] < date('Y-m-d');
    ?>
      <div class="demande-card">
     <div class="d-flex justify-content-between align-items-start">
      <div>
        <div class="card-title"><?= htmlspecialchars($d['title']) ?></div>
        <div class="card-date">
          <i class="bi bi-clock-history"></i>
          Publiée le <?= date('d/m/Y à H:i', strtotime($d['created_at'])) ?>
        </div>
      </div>

      <div class="card-actions">
        <a href="edit-demande.php?id=<?= $d['id'] ?>" class="btn-edit">
          <i class="bi bi-pencil"></i> Modifier
        </a>

        <button class="btn-delete"
          onclick="confirmDelete(<?= $d['id'] ?>, '<?= htmlspecialchars(addslashes($d['title'])) ?>')">
          <i class="bi bi-trash"></i> Supprimer
        </button>

        <!-- 🔥 BOUTON PROPOSITIONS -->
        <a href="/myprojectwithnade/matching-main/front-office/proposition.php?demande_id=<?= $d['id'] ?>">
          <i class="bi bi-eye"></i> Voir propositions
        </a>
      </div>
    </div> <!-- ✅ TRÈS IMPORTANT -->
        <p class="card-description"><?= htmlspecialchars($d['description']) ?></p>
        <div class="card-meta">
          <span class="price-badge"><i class="bi bi-wallet2"></i> <?= number_format($d['price'], 0, ',', ' ') ?> DT</span>
          <span class="deadline-badge <?= $isExpired ? 'red' : 'green' ?>">
            <i class="bi bi-<?= $isExpired ? 'exclamation-circle' : 'calendar-event' ?>"></i>
            <?= $isExpired ? 'Expiré le ' : 'Avant le ' ?><?= date('d/m/Y', strtotime($d['deadline'])) ?>
          </span>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</main>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-3">
      <h5><i class="bi bi-exclamation-triangle text-danger"></i> Confirmer la suppression</h5>
      <p>Êtes-vous sûr de vouloir supprimer <strong id="deleteTitle"></strong> ?</p>
      <div class="d-flex gap-2 justify-content-end">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
        <a href="#" id="deleteConfirmBtn" class="btn btn-danger">Supprimer</a>
      </div>
    </div>
  </div>
</div>

<footer>
  <p>© <strong>SkillBridge</strong> - Tous droits réservés</p>
  <p>Designed by <a href="https://bootstrapmade.com/">BootstrapMade</a></p>
</footer>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(id, title) {
  document.getElementById('deleteTitle').textContent = '"' + title + '"';
  document.getElementById('deleteConfirmBtn').href = 'mes-demandes.php?delete=' + id;
  new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

</body>
</html>