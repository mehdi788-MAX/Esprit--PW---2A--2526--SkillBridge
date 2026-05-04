<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkillBridge — Historique des Tests</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Questrial&family=Roboto:wght@400;500;700&display=swap');
    :root { --accent: #f97316; --dark: #111111; --light: #f5f5f5; --success: #16a34a; --danger: #e11d48; --warning: #d97706; }
    body { font-family: 'Roboto', sans-serif; background: #fff; margin: 0; color: var(--dark); }

    /* HEADER */
    .ef-header { background: #fff; padding: 16px 0; position: sticky; top: 0; z-index: 100; border-bottom: 1px solid #e5e5e5; }
    .ef-header-container { display: flex; align-items: center; justify-content: space-between; }
    .ef-logo { font-family: 'Questrial', sans-serif; font-size: 1.4rem; color: var(--dark); text-decoration: none; font-weight: 700; }
    .ef-logo span { color: var(--accent); }
    .ef-nav { display: flex; gap: 4px; list-style: none; margin: 0; padding: 0; }
    .ef-nav a { font-size: 0.88rem; color: var(--dark); padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: 500; }
    .ef-nav a:hover { color: var(--accent); }
    .btn-admin { border: 2px solid var(--dark); color: var(--dark); padding: 7px 18px; border-radius: 50px; font-weight: 600; background: transparent; text-decoration: none; font-size: 0.82rem; transition: 0.2s; }
    .btn-admin:hover { background: var(--dark); color: white; }

    /* TABLE */
    .section-white { padding: 60px 0; background: white; min-height: 70vh;}
    .ef-card { background: white; border-radius: 14px; padding: 24px; box-shadow: 0 2px 16px rgba(0,0,0,0.06); border: 1px solid #f0f0f0; }
    .history-table { width: 100%; border-collapse: collapse; }
    .history-table th { font-family: 'Questrial', sans-serif; text-transform: uppercase; font-size: 0.85rem; color: #888; border-bottom: 2px solid #f0f0f0; padding: 16px 12px; text-align: left; }
    .history-table td { padding: 16px 12px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; font-size: 0.95rem; }
    .history-table tr:last-child td { border-bottom: none; }
    .history-table tbody tr:hover { background: #fafafa; }
    
    .badge-status { padding: 6px 12px; border-radius: 99px; font-size: 0.75rem; font-weight: 700; display: inline-block; }
    .badge-success { background: #f0fdf4; color: var(--success); border: 1px solid #bbf7d0; }
    .badge-warning { background: #fffbeb; color: var(--warning); border: 1px solid #fde68a; }
    .badge-danger { background: #fff1f2; color: var(--danger); border: 1px solid #fecdd3; }

    .test-title { font-weight: 700; color: var(--dark); }
    .test-cat { font-size: 0.8rem; color: #888; }
    
    /* FOOTER */
    .ef-footer { background: var(--dark); color: rgba(255,255,255,0.4); text-align: center; padding: 24px; font-size: 0.82rem; }
    .ef-footer strong { color: white; }
    .ef-footer .accent { color: var(--accent); }
  </style>
</head>
<body>

  <!-- HEADER -->
  <header class="ef-header">
    <div class="container">
      <div class="ef-header-container">
        <a href="index.php" class="ef-logo">Skill<span>Bridge</span></a>
        <ul class="ef-nav">
          <li><a href="index.php">Accueil</a></li>
          <li><a href="index.php#tests">Tests</a></li>
          <li><a href="index.php?action=history" style="color: var(--accent);">Historique</a></li>
        </ul>
        <a href="index.php?action=index" class="btn-admin">🛡️ Vue Admin</a>
      </div>
    </div>
  </header>

  <!-- CONTENT -->
  <section class="section-white">
    <div class="container">
      <h2 style="font-family: 'Questrial', sans-serif; margin-bottom: 30px;">Historique des <span>Tests</span></h2>
      
      <div class="ef-card">
        <div class="table-responsive">
          <table class="history-table">
            <thead>
              <tr>
                <th>Test</th>
                <th>Catégorie</th>
                <th>Score</th>
                <th>Pourcentage</th>
                <th>Statut</th>
                <th>Date & Heure</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recentResults)): ?>
                <tr>
                  <td colspan="6" class="text-center" style="padding: 40px; color: #888;">
                    Aucun historique de test trouvé.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($recentResults as $row): 
                  $percentage = ($row['total'] > 0) ? round(($row['score'] / $row['total']) * 100) : 0;
                  $badgeClass = 'badge-danger';
                  $statusText = 'Échoué';
                  
                  if ($percentage >= 70) {
                      $badgeClass = 'badge-success';
                      $statusText = 'Réussi';
                  } elseif ($percentage >= 50) {
                      $badgeClass = 'badge-warning';
                      $statusText = 'Passable';
                  }
                ?>
                <tr>
                  <td>
                    <div class="test-title"><?= htmlspecialchars($row['test_title'] ?? 'Test supprimé') ?></div>
                  </td>
                  <td>
                    <span class="test-cat"><?= htmlspecialchars($row['category_name'] ?? 'N/A') ?></span>
                  </td>
                  <td style="font-weight: 700;">
                    <?= $row['score'] ?> / <?= $row['total'] ?>
                  </td>
                  <td style="font-weight: 700; color: <?= $percentage >= 50 ? 'var(--success)' : 'var(--danger)' ?>;">
                    <?= $percentage ?>%
                  </td>
                  <td>
                    <span class="badge-status <?= $badgeClass ?>"><?= $statusText ?></span>
                  </td>
                  <td style="font-size: 0.85rem; color: #666;">
                    <?= date('d/m/Y', strtotime($row['created_at'])) ?><br>
                    <?= date('H:i', strtotime($row['created_at'])) ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="ef-footer">
    <strong>SkillBridge</strong> — Module : <strong>Gestion Test &amp; Validation</strong> · <span class="accent">Mohamed Emin</span> · NEXT STEP · Esprit 2025
  </footer>

</body>
</html>
