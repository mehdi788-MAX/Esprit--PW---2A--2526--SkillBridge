<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkillBridge — Admin</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Nunito', sans-serif; background: #f8f9fc; color: #5a5c69; margin: 0; }
    .sb-topbar { background: white; height: 56px; border-bottom: 1px solid #e3e6f0; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 100; box-shadow: 0 0.15rem 1.75rem rgba(58,59,69,0.08); }
    .sb-topbar-brand { font-size: 1rem; font-weight: 800; color: #4e73df; text-decoration: none; }
    .sb-body { display: flex; min-height: calc(100vh - 56px); }
    .sb-sidebar { width: 224px; flex-shrink: 0; background: linear-gradient(180deg, #4e73df 10%, #224abe 100%); position: sticky; top: 56px; height: calc(100vh - 56px); }
    .sb-brand { padding: 18px 16px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.15); }
    .sb-brand-text { color: white; font-weight: 800; font-size: 0.9rem; }
    .sb-heading { color: rgba(255,255,255,0.4); font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; padding: 10px 16px 4px; }
    .sb-nav { list-style: none; padding: 0; margin: 0; }
    .sb-nav a { display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: rgba(255,255,255,0.8); font-size: 0.82rem; font-weight: 700; text-decoration: none; border-left: 3px solid transparent; transition: 0.2s; }
    .sb-nav a:hover, .sb-nav a.active { color: white; background: rgba(255,255,255,0.1); border-left-color: white; }
    .sb-content { flex: 1; padding: 24px; }
    .sb-card { background: white; border-radius: 8px; box-shadow: 0 0.15rem 1.75rem rgba(58,59,69,0.08); margin-bottom: 24px; }
    .sb-card-header { padding: 12px 20px; border-bottom: 1px solid #e3e6f0; display: flex; align-items: center; justify-content: space-between; }
    .sb-card-header h6 { font-weight: 800; color: #4e73df; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; margin: 0; }
    .sb-card-body { padding: 20px; }
    .sb-table { width: 100%; border-collapse: collapse; font-size: 0.83rem; }
    .sb-table thead th { background: #f8f9fc; color: #858796; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; padding: 10px 14px; border-bottom: 2px solid #e3e6f0; text-align: left; }
    .sb-table tbody td { padding: 10px 14px; border-bottom: 1px solid #e3e6f0; vertical-align: middle; }
    .sb-table tbody tr:last-child td { border-bottom: none; }
    .sb-table tbody tr:hover td { background: #f8f9fc; }
    .td-bold { font-weight: 700; color: #5a5c69; }
    .sb-pill { font-size: 0.65rem; font-weight: 700; padding: 3px 10px; border-radius: 99px; display: inline-block; }
    .sb-pill.success { background: #d4edda; color: #155724; }
    .sb-pill.warning { background: #fff3cd; color: #856404; }
    .sb-pill.danger  { background: #f8d7da; color: #721c24; }
    .btn-edit { background: #cce5ff; color: #004085; padding: 5px 12px; border-radius: 5px; border: none; cursor: pointer; font-size: 0.78rem; font-weight: 700; text-decoration: none; display: inline-block; }
    .btn-del  { background: #f8d7da; color: #721c24; padding: 5px 12px; border-radius: 5px; border: none; cursor: pointer; font-size: 0.78rem; font-weight: 700; }
    .btn-add  { background: #4e73df; color: white; padding: 8px 18px; border-radius: 6px; border: none; font-weight: 700; font-size: 0.82rem; cursor: pointer; text-decoration: none; display: inline-block; }
    .btn-add:hover { background: #2e59d9; color: white; }
    .alert-success { background: #d4edda; color: #155724; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; border: 1px solid #c3e6cb; font-size: 0.85rem; }
  </style>
</head>
<body>

  <!-- TOPBAR -->
  <div class="sb-topbar">
    <a href="index.php" class="sb-topbar-brand">SkillBridge | Admin</a>
    <a href="index.php?action=frontoffice" style="font-size:0.82rem;color:#4e73df;font-weight:700;text-decoration:none;">
      👤 Vue Client
    </a>
  </div>

  <div class="sb-body">

    <!-- SIDEBAR -->
    <div class="sb-sidebar">
      <div class="sb-brand">
        <div class="sb-brand-text">📋 Test & Validation</div>
      </div>
      <div class="sb-heading">Navigation</div>
      <ul class="sb-nav">
        <li><a href="index.php?action=index" class="active">🏠 Dashboard</a></li>
        <li><a href="index.php?action=create">➕ Ajouter un test</a></li>
        <li><a href="index.php?action=frontoffice">🌐 Vue client</a></li>
      </ul>
    </div>

    <!-- CONTENU -->
    <div class="sb-content">

      <h4 style="font-weight:800;color:#5a5c69;margin-bottom:4px;">Gestion des Tests</h4>
      <p style="font-size:0.82rem;color:#858796;margin-bottom:20px;">
        <a href="index.php" style="color:#4e73df;text-decoration:none;">Accueil</a> › Tests
      </p>

      <!-- Message de succès -->
      <?php if (isset($_GET['success'])): ?>
        <div class="alert-success">
          ✅
          <?php
            if ($_GET['success'] == 'ajout')       echo "Test ajouté avec succès !";
            if ($_GET['success'] == 'modif')        echo "Test modifié avec succès !";
            if ($_GET['success'] == 'suppression')  echo "Test supprimé avec succès !";
          ?>
        </div>
      <?php endif; ?>

      <!-- CARD TESTS -->
      <div class="sb-card">
        <div class="sb-card-header">
          <h6>📋 Liste des tests</h6>
          <a href="index.php?action=create" class="btn-add">＋ Nouveau test</a>
        </div>
        <div class="sb-card-body">
          <div class="table-responsive">
            <table class="sb-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Titre</th>
                  <th>Catégorie</th>
                  <th>Durée</th>
                  <th>Niveau</th>
                  <th>Score Moyen</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $i = 1;
                  while ($row = $tests->fetch(PDO::FETCH_ASSOC)):
                    // Choisir la couleur du badge niveau
                    $badge = 'success';
                    if ($row['level'] == 'Moyen')   $badge = 'warning';
                    if ($row['level'] == 'Avancé')  $badge = 'danger';
                ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td class="td-bold"><?= htmlspecialchars($row['title']) ?></td>
                  <td><?= htmlspecialchars($row['category_name']) ?></td>
                  <td><?= $row['duration'] ?> min</td>
                  <td><span class="sb-pill <?= $badge ?>"><?= $row['level'] ?></span></td>
                  <td><?= $row['average_score'] ?>%</td>
                  <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                  <td>
                    <a href="index.php?action=edit&id=<?= $row['id'] ?>" class="btn-edit">✏️ Modifier</a>
                    &nbsp;
                    <button class="btn-del" onclick="confirmerSuppression(<?= $row['id'] ?>)">🗑️ Supprimer</button>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script src="assets/js/validation.js"></script>
</body>
</html>
