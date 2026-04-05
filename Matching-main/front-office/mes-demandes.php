<?php
// ──────────────────────────────────────────────
//  matching – Mes Demandes
//  BDD : matching | Table : request
// ──────────────────────────────────────────────
 
$db_host = 'localhost';
$db_name = 'matching';
$db_user = 'root';
$db_pass = '';
 
// userId en dur en attendant le système de session/login
$userId = 1;
 
$demandes = [];
$error    = '';
 
// ── Suppression ──
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $pdo  = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass,
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $stmt = $pdo->prepare("DELETE FROM request WHERE id = :id AND userId = :userId");
        $stmt->execute([':id' => (int)$_GET['delete'], ':userId' => $userId]);
        header("Location: mes-demandes.php?deleted=1");
        exit;
    } catch (PDOException $e) {
        $error = 'Erreur lors de la suppression : ' . $e->getMessage();
    }
}
 
// ── Récupération des demandes de l'utilisateur ──
try {
    $pdo  = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->prepare("SELECT * FROM request WHERE userId = :userId ORDER BY createdAt DESC");
    $stmt->execute([':userId' => $userId]);
    $demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Erreur de connexion à la base de données : ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
 
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Mes Demandes – SkillBridge</title>
  <meta name="description" content="Consultez, modifiez ou supprimez vos demandes publiées sur SkillBridge.">
 
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
    /* ── PAGE HEADER ── */
    .page-header {
      background: #fff8f0;
      padding: 40px 0 30px;
      border-bottom: 1px solid #ffe0cc;
    }
 
    .page-header h1 {
      font-size: 2rem;
      font-weight: 700;
      color: #1a1a2e;
      margin-bottom: 6px;
    }
 
    .page-header p {
      color: #6b7280;
      font-size: 0.95rem;
      margin: 0;
    }
 
    .btn-add {
      background-color: #ff6600;
      color: #fff;
      border: none;
      padding: 11px 24px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.9rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: 0.3s;
    }
 
    .btn-add:hover { background-color: #e65c00; color: #fff; }
 
    /* ── SECTION ── */
    .demandes-section {
      padding: 50px 0 80px;
      background: #f9f9f9;
      min-height: 60vh;
    }
 
    /* ── STATS BAR ── */
    .stats-bar {
      display: flex;
      gap: 1.2rem;
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
 
    .stat-badge i { color: #ff6600; font-size: 1.2rem; }
    .stat-badge .stat-val { font-weight: 700; color: #1a1a2e; font-size: 1.1rem; line-height: 1; }
    .stat-badge .stat-lbl { font-size: 0.75rem; color: #6b7280; }
 
    /* ── ALERTS ── */
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
      font-weight: 500;
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
 
    /* ── EMPTY STATE ── */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      background: #fff;
      border-radius: 12px;
      border: 2px dashed #ffb366;
    }
 
    .empty-state i { font-size: 3.5rem; color: #ffb366; margin-bottom: 1rem; display: block; }
    .empty-state h4 { color: #1a1a2e; font-weight: 700; margin-bottom: 0.5rem; }
    .empty-state p { color: #6b7280; margin-bottom: 1.5rem; }
 
    /* ── CARD ── */
    .demande-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 14px;
      padding: 1.5rem;
      margin-bottom: 1.2rem;
      transition: all 0.3s ease;
    }
 
    .demande-card:hover {
      border-color: #ff6600;
      box-shadow: 0 8px 25px rgba(255,102,0,0.1);
    }
 
    .demande-card .card-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 1rem;
      flex-wrap: wrap;
    }
 
    .demande-card .card-title {
      font-size: 1.05rem;
      font-weight: 700;
      color: #1a1a2e;
      margin-bottom: 0.3rem;
    }
 
    .demande-card .card-date {
      font-size: 0.78rem;
      color: #9ca3af;
      display: flex;
      align-items: center;
      gap: 4px;
    }
 
    .demande-card .card-description {
      color: #4b5563;
      font-size: 0.88rem;
      line-height: 1.65;
      margin: 0.8rem 0 1rem;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
 
    .demande-card .card-meta {
      display: flex;
      align-items: center;
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
      font-size: 0.88rem;
      display: flex;
      align-items: center;
      gap: 5px;
    }
 
    .date-badge {
      background: #f3f4f6;
      border-radius: 20px;
      padding: 4px 14px;
      color: #6b7280;
      font-size: 0.8rem;
      display: flex;
      align-items: center;
      gap: 5px;
    }
 
    /* ── ACTION BUTTONS ── */
    .card-actions {
      display: flex;
      gap: 0.6rem;
      flex-shrink: 0;
    }
 
    .btn-edit {
      background: #fff3eb;
      color: #ff6600;
      border: 1px solid #ffb366;
      padding: 7px 16px;
      border-radius: 7px;
      font-size: 0.82rem;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: 0.25s;
    }
 
    .btn-edit:hover { background: #ff6600; color: #fff; border-color: #ff6600; }
 
    .btn-delete {
      background: #fff5f5;
      color: #b91c1c;
      border: 1px solid #fca5a5;
      padding: 7px 16px;
      border-radius: 7px;
      font-size: 0.82rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: 0.25s;
      cursor: pointer;
    }
 
    .btn-delete:hover { background: #b91c1c; color: #fff; border-color: #b91c1c; }
 
    /* ── MODAL ── */
    .modal-content { border-radius: 14px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
    .modal-header { border-bottom: 1px solid #f3f4f6; padding: 1.2rem 1.5rem; }
    .modal-header .modal-title { font-weight: 700; color: #1a1a2e; }
    .modal-body { padding: 1.5rem; color: #4b5563; }
    .modal-footer { border-top: 1px solid #f3f4f6; padding: 1rem 1.5rem; }
 
    .btn-confirm-delete {
      background: #b91c1c; color: #fff; border: none;
      padding: 9px 22px; border-radius: 7px; font-weight: 600; transition: 0.25s;
    }
    .btn-confirm-delete:hover { background: #991b1b; color: #fff; }
 
    .btn-cancel-modal {
      background: #f3f4f6; color: #374151; border: none;
      padding: 9px 22px; border-radius: 7px; font-weight: 600; transition: 0.25s;
    }
    .btn-cancel-modal:hover { background: #e5e7eb; }
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
      <div class="container d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
          <h1>Mes Demandes</h1>
          <p>Consultez, modifiez ou supprimez vos demandes publiées sur SkillBridge.</p>
        </div>
        <a href="Addrequest.php" class="btn-add">
          <i class="bi bi-plus-lg"></i> Publier une demande
        </a>
      </div>
    </div>
 
    <!-- ═══════════════════════ LISTE -->
    <section class="demandes-section">
      <div class="container">
 
        <!-- Alertes -->
        <?php if (isset($_GET['deleted'])): ?>
          <div class="alert-success-sb" data-aos="fade-down">
            <i class="bi bi-check-circle-fill"></i>
            Votre demande a été supprimée avec succès.
          </div>
        <?php endif; ?>
 
        <?php if (isset($_GET['updated'])): ?>
          <div class="alert-success-sb" data-aos="fade-down">
            <i class="bi bi-check-circle-fill"></i>
            Votre demande a été modifiée avec succès.
          </div>
        <?php endif; ?>
 
        <?php if ($error): ?>
          <div class="alert-error-sb">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>
 
        <!-- Stats bar -->
        <?php if (!empty($demandes)): ?>
          <div class="stats-bar" data-aos="fade-up">
            <div class="stat-badge">
              <i class="bi bi-collection"></i>
              <div>
                <div class="stat-val"><?= count($demandes) ?></div>
                <div class="stat-lbl">Demande<?= count($demandes) > 1 ? 's' : '' ?> publiée<?= count($demandes) > 1 ? 's' : '' ?></div>
              </div>
            </div>
            <div class="stat-badge">
              <i class="bi bi-currency-exchange"></i>
              <div>
                <div class="stat-val"><?= number_format(array_sum(array_column($demandes, 'price')), 0, ',', ' ') ?> DT</div>
                <div class="stat-lbl">Budget total</div>
              </div>
            </div>
            <div class="stat-badge">
              <i class="bi bi-calendar-check"></i>
              <div>
                <div class="stat-val"><?= date('d/m/Y') ?></div>
                <div class="stat-lbl">Aujourd'hui</div>
              </div>
            </div>
          </div>
        <?php endif; ?>
 
        <!-- Cartes -->
        <?php if (empty($demandes) && !$error): ?>
 
          <div class="empty-state" data-aos="fade-up">
            <i class="bi bi-inbox"></i>
            <h4>Aucune demande publiée pour l'instant</h4>
            <p>Publiez votre première demande et recevez des propositions de freelancers qualifiés.</p>
            <a href="Addrequest.php" class="btn-add">
              <i class="bi bi-plus-lg"></i> Publier ma première demande
            </a>
          </div>
 
        <?php else: ?>
 
          <?php foreach ($demandes as $i => $d): ?>
            <div class="demande-card" data-aos="fade-up" data-aos-delay="<?= $i * 50 ?>">
 
              <div class="card-top">
                <div class="flex-grow-1">
                  <div class="card-title"><?= htmlspecialchars($d['title']) ?></div>
                  <div class="card-date">
                    <i class="bi bi-clock-history"></i>
                    Publiée le <?= date('d/m/Y', strtotime($d['createdAt'])) ?>
                    <?php if ($d['updatedAt'] !== $d['createdAt']): ?>
                      &nbsp;·&nbsp; <i class="bi bi-pencil-square"></i> Modifiée le <?= date('d/m/Y', strtotime($d['updatedAt'])) ?>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="card-actions">
                  <a href="edit-demande.php?id=<?= $d['id'] ?>" class="btn-edit">
                    <i class="bi bi-pencil"></i> Modifier
                  </a>
                  <button
                    class="btn-delete"
                    onclick="confirmDelete(<?= $d['id'] ?>, '<?= htmlspecialchars(addslashes($d['title'])) ?>')"
                  >
                    <i class="bi bi-trash"></i> Supprimer
                  </button>
                </div>
              </div>
 
              <p class="card-description"><?= htmlspecialchars($d['description']) ?></p>
 
              <div class="card-meta">
                <span class="price-badge">
                  <i class="bi bi-wallet2"></i>
                  <?= number_format($d['price'], 0, ',', ' ') ?> DT
                </span>
                <span class="date-badge">
                  <i class="bi bi-calendar-event"></i>
                  Créée le <?= date('d/m/Y', strtotime($d['createdAt'])) ?>
                </span>
              </div>
 
            </div>
          <?php endforeach; ?>
 
        <?php endif; ?>
 
      </div>
    </section>
 
  </main>
 
  <!-- ═══════════════════════ MODAL SUPPRESSION -->
  <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-exclamation-triangle text-danger me-2"></i>Confirmer la suppression
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          Êtes-vous sûr de vouloir supprimer la demande <strong id="deleteTitle"></strong> ?<br><br>
          <span style="color:#9ca3af;font-size:0.85rem;">Cette action est irréversible.</span>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">Annuler</button>
          <a href="#" id="deleteConfirmBtn" class="btn-confirm-delete">
            <i class="bi bi-trash me-1"></i> Supprimer
          </a>
        </div>
      </div>
    </div>
  </div>
 
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
 
  <script>
    function confirmDelete(id, title) {
      document.getElementById('deleteTitle').textContent = '"' + title + '"';
      document.getElementById('deleteConfirmBtn').href = 'mes-demandes.php?delete=' + id;
      new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
  </script>
 
</body>
</html>
 