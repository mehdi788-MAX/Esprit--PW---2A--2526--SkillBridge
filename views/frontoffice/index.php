<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkillBridge — Gestion Test & Validation</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Questrial&family=Roboto:wght@400;500;700&display=swap');
    :root { --accent: #f97316; --dark: #111111; --light: #f5f5f5; }
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

    /* HERO */
    .ef-hero { background: white; padding: 60px 0 80px; position: relative; overflow: hidden; border-bottom: 1px solid #f0f0f0; }
    .ef-hero::before { content: ''; position: absolute; top: -100px; right: -100px; width: 400px; height: 400px; border-radius: 50%; background: radial-gradient(circle, rgba(249,115,22,0.1), transparent 70%); }
    .ef-hero::after  { content: ''; position: absolute; bottom: -80px; left: -80px; width: 300px; height: 300px; border-radius: 50%; background: radial-gradient(circle, rgba(249,115,22,0.05), transparent 70%); }
    .ef-hero-badge { display: inline-block; background: rgba(249,115,22,0.1); color: var(--accent); font-size: 0.68rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; padding: 6px 18px; border-radius: 99px; border: 1px solid rgba(249,115,22,0.3); margin-bottom: 20px; }
    .ef-hero h1 { font-family: 'Questrial', sans-serif; font-size: 2.9rem; color: var(--dark); line-height: 1.2; }
    .ef-hero h1 span { color: var(--accent); }
    .ef-hero p { color: #555; font-size: 1rem; line-height: 1.8; max-width: 480px; }
    .btn-primary-ef { background: var(--accent); color: white; border: none; padding: 13px 30px; border-radius: 50px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; font-size: 0.95rem; transition: 0.2s; }
    .btn-primary-ef:hover { background: #ea6c0a; color: white; }

    /* STATS CARD */
    .cert-card { background: #fdfdfd; border: 1px solid #eee; border-radius: 20px; padding: 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); }
    .cert-card h3 { color: var(--dark); font-family: 'Questrial', sans-serif; font-size: 1rem; margin-bottom: 20px; }
    .cert-stats { display: flex; justify-content: space-around; border-top: 1px solid #eee; padding-top: 18px; }
    .cert-stats .val { color: var(--accent); font-size: 1.5rem; font-weight: 700; display: block; }
    .cert-stats .lbl { color: #999; font-size: 0.62rem; text-transform: uppercase; letter-spacing: 1px; }

    /* FILTERS */
    .filters-section { background: white; padding: 32px 0 0; border-bottom: 1px solid #e5e5e5; }
    .filters-title { font-family: 'Questrial', sans-serif; font-size: 1rem; font-weight: 700; color: var(--dark); margin-bottom: 16px; }
    .filter-group { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; padding-bottom: 24px; }
    .filter-item { display: flex; flex-direction: column; gap: 5px; }
    .filter-item label { font-size: 0.72rem; font-weight: 700; color: #888; text-transform: uppercase; letter-spacing: 1px; }
    .filter-select { padding: 9px 14px; border: 2px solid #e5e5e5; border-radius: 8px; font-family: 'Roboto', sans-serif; font-size: 0.87rem; color: var(--dark); background: white; cursor: pointer; outline: none; min-width: 180px; transition: 0.2s; }
    .filter-select:focus { border-color: var(--accent); }
    .btn-filter { background: var(--accent); color: white; border: none; padding: 11px 24px; border-radius: 8px; font-weight: 600; font-size: 0.87rem; cursor: pointer; transition: 0.2s; }
    .btn-filter:hover { background: #ea6c0a; }
    .btn-reset { background: white; color: var(--dark); border: 2px solid #e5e5e5; padding: 9px 20px; border-radius: 8px; font-weight: 600; font-size: 0.87rem; cursor: pointer; text-decoration: none; display: inline-block; transition: 0.2s; }
    .btn-reset:hover { border-color: var(--dark); color: var(--dark); }
    .filter-active-badge { background: rgba(249,115,22,0.1); color: var(--accent); border: 1px solid rgba(249,115,22,0.3); border-radius: 99px; font-size: 0.72rem; font-weight: 700; padding: 4px 12px; display: inline-block; margin-bottom: 12px; }

    /* SECTIONS */
    .section-white { padding: 60px 0; background: white; }
    .section-light { padding: 60px 0; background: var(--light); }
    .section-title-wrap { padding-bottom: 36px; }
    .section-title-wrap h2 { font-family: 'Questrial', sans-serif; font-size: 2rem; color: var(--dark); }
    .section-title-wrap h2 span { color: var(--accent); }
    .section-title-wrap p { color: #777; font-size: 0.95rem; max-width: 560px; line-height: 1.8; }
    .results-count { font-size: 0.82rem; color: #888; font-weight: 600; margin-top: -20px; margin-bottom: 24px; }
    .results-count span { color: var(--accent); font-weight: 700; }

    /* TEST CARDS */
    .ef-card { background: white; border-radius: 14px; padding: 24px; box-shadow: 0 2px 16px rgba(0,0,0,0.06); border: 1px solid #f0f0f0; height: 100%; transition: all 0.3s; display: flex; flex-direction: column; }
    .ef-card:hover { box-shadow: 0 10px 40px rgba(249,115,22,0.12); border-color: rgba(249,115,22,0.25); transform: translateY(-4px); }
    .ef-card h3 { font-family: 'Questrial', sans-serif; font-size: 1rem; color: var(--dark); margin-bottom: 4px; }
    .ef-card:hover h3 { color: var(--accent); }
    .ef-card .cat { font-size: 0.75rem; color: #aaa; margin-bottom: 14px; }
    .level-badge { font-size: 0.65rem; font-weight: 700; padding: 3px 10px; border-radius: 99px; border: 1px solid; }
    .level-debut  { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
    .level-moyen  { background: #fffbeb; color: #d97706; border-color: #fde68a; }
    .level-avance { background: #fff1f2; color: #e11d48; border-color: #fecdd3; }
    .ef-bar { height: 4px; background: #f0f0f0; border-radius: 99px; overflow: hidden; margin-top: 10px; }
    .ef-bar-fill { height: 100%; background: var(--accent); border-radius: 99px; }
    .no-results { text-align: center; padding: 60px 20px; color: #aaa; }
    .no-results .icon { font-size: 3rem; margin-bottom: 16px; }
    .no-results p { font-size: 0.95rem; }

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
          <li><a href="#hero">Accueil</a></li>
          <li><a href="#tests">Tests</a></li>
          <li><a href="index.php?action=history">Historique</a></li>
          <?php if (isset($_SESSION['user_nom'])): ?>
            <li><a href="../gestion%20utulisateur/view/frontoffice/EasyFolio/profil.php" style="color: var(--accent); font-weight: bold;">Mon Profil (<?= htmlspecialchars($_SESSION['user_nom']) ?>)</a></li>
          <?php else: ?>
            <li><a href="../gestion%20utulisateur/view/frontoffice/EasyFolio/login.php" style="color: var(--accent); font-weight: bold;">Connexion</a></li>
          <?php endif; ?>
        </ul>
        <a href="index.php?action=index" class="btn-admin">🛡️ Vue Admin</a>
      </div>
    </div>
  </header>

  <!-- HERO -->
  <section class="ef-hero" id="hero">
    <div class="container" style="position:relative;z-index:2;">
      <div class="row align-items-center g-5">
        <div class="col-lg-6">
          <div class="ef-hero-badge">La confiance par la preuve</div>
          <h1>Recrutez les meilleurs <span>talents vérifiés</span></h1>
          <p>SkillBridge élimine l'incertitude du recrutement. Accédez à un vivier de freelancers dont les compétences ont été rigoureusement testées.</p>
          <div class="d-flex gap-3 flex-wrap mt-4">
            <a href="#tests" class="btn-primary-ef">Explorer les tests →</a>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="cert-card">
            <h3>⚡ Statistiques</h3>
            <?php
              $total_all = count($all_tests_raw);
            ?>
            <div class="cert-stats">
              <div><span class="val"><?= $total_all ?></span><span class="lbl">Tests dispo</span></div>
              <div><span class="val"><?= count($categories) ?></span><span class="lbl">Catégories</span></div>
              <div><span class="val">92%</span><span class="lbl">Meilleur score</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FILTRES -->
  <div class="filters-section" id="tests">
    <div class="container">
      <div class="filters-title">🔍 Filtrer les tests</div>
      <form action="index.php" method="GET">
        <input type="hidden" name="action" value="frontoffice">
        <div class="filter-group">
          <div class="filter-item">
            <label for="search_keyword">Recherche</label>
            <input type="text" id="search_keyword" class="filter-select" placeholder="Nom du test..." style="border: 2px solid #e5e5e5; border-radius: 8px;">
          </div>
          <div class="filter-item">
            <label for="filter_cat">Catégorie</label>
            <select name="cat" id="filter_cat" class="filter-select">
              <option value="">Toutes les catégories</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= ($filter_cat == $cat['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-item">
            <label for="filter_level">Niveau</label>
            <select name="level" id="filter_level" class="filter-select">
              <option value="">Tous les niveaux</option>
              <option value="Débutant" <?= ($filter_level == 'Débutant') ? 'selected' : '' ?>>🟢 Débutant</option>
              <option value="Moyen"    <?= ($filter_level == 'Moyen')    ? 'selected' : '' ?>>🟡 Moyen</option>
              <option value="Avancé"   <?= ($filter_level == 'Avancé')   ? 'selected' : '' ?>>🔴 Avancé</option>
            </select>
          </div>
          <div class="filter-item" style="justify-content:flex-end;">
            <label>&nbsp;</label>
            <button type="submit" class="btn-filter">Filtrer</button>
          </div>
          <?php if ($filter_cat !== '' || $filter_level !== ''): ?>
          <div class="filter-item" style="justify-content:flex-end;">
            <label>&nbsp;</label>
            <a href="index.php?action=frontoffice" class="btn-reset">✕ Réinitialiser</a>
          </div>
          <?php endif; ?>
        </div>
      </form>

      <?php if ($filter_cat !== '' || $filter_level !== ''): ?>
        <div>
          <?php if ($filter_cat !== ''): ?>
            <?php foreach ($categories as $cat): ?>
              <?php if ($cat['id'] == $filter_cat): ?>
                <span class="filter-active-badge">📂 <?= htmlspecialchars($cat['name']) ?></span>&nbsp;
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endif; ?>
          <?php if ($filter_level !== ''): ?>
            <span class="filter-active-badge">📊 <?= htmlspecialchars($filter_level) ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TESTS -->
  <section class="section-light">
    <div class="container">
      <div class="section-title-wrap">
        <h2>Nos <span>certifications</span></h2>
        <p>Explorez les domaines de compétences que nous validons. Chaque test est mis à jour régulièrement.</p>
      </div>

      <p class="results-count">
        <span><?= count($all_tests) ?></span> test<?= count($all_tests) > 1 ? 's' : '' ?> trouvé<?= count($all_tests) > 1 ? 's' : '' ?>
      </p>

      <!-- Message d'erreur -->
      <?php if (isset($_GET['error']) && $_GET['error'] == 'not_generated'): ?>
        <div class="alert alert-warning" style="border-radius:10px;font-weight:600;">
          ⚠️ Ce test n'a pas encore été généré par l'IA. Veuillez contacter l'administrateur.
        </div>
      <?php endif; ?>

      <?php if (count($all_tests) === 0): ?>
        <div class="no-results">
          <div class="icon">🔍</div>
          <p>Aucun test ne correspond à vos filtres.<br>
            <a href="index.php?action=frontoffice" style="color:var(--accent);font-weight:700;">Voir tous les tests</a>
          </p>
        </div>
      <?php else: ?>
      <div class="row g-4">
        <?php foreach ($all_tests as $row):
          $badge = 'level-debut';
          if ($row['level'] == 'Moyen')  $badge = 'level-moyen';
          if ($row['level'] == 'Avancé') $badge = 'level-avance';
        ?>
        <div class="col-md-6 col-lg-4">
          <div class="ef-card">
            <div>
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div style="font-size:1.6rem;">📊</div>
                <span class="level-badge <?= $badge ?>"><?= htmlspecialchars($row['level']) ?></span>
              </div>
              <h3><?= htmlspecialchars($row['title']) ?></h3>
              <p class="cat"><?= htmlspecialchars($row['category_name']) ?></p>
              <div class="d-flex justify-content-between" style="font-size:0.75rem;color:#aaa;">
                <span>⏱ <?= $row['duration'] ?> min</span>
                <span style="color:var(--accent);font-weight:700;"><?= $row['average_score'] ?>%</span>
              </div>
              <div class="ef-bar">
                <div class="ef-bar-fill" style="width:<?= $row['average_score'] ?>%;"></div>
              </div>
            </div>
            
            <div class="mt-auto pt-4">
                <a href="index.php?action=take_test&id=<?= $row['id'] ?>" class="btn-primary-ef w-100 text-center" style="padding:10px;font-size:0.85rem;">Passer le test</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>
  </section>

  <!-- FOOTER -->
  <footer class="ef-footer">
    <strong>SkillBridge</strong> — Module : <strong>Gestion Test &amp; Validation</strong> · <span class="accent">Mohamed Emin</span> · NEXT STEP · Esprit 2025
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const searchInput = document.getElementById('search_keyword');
      if (searchInput) {
        searchInput.addEventListener('input', function() {
          const keyword = this.value.toLowerCase();
          const cards = document.querySelectorAll('.ef-card');
          
          cards.forEach(card => {
            const container = card.closest('.col-md-6');
            const title = card.querySelector('h3').textContent.toLowerCase();
            if (title.includes(keyword)) {
              container.style.display = '';
            } else {
              container.style.display = 'none';
            }
          });
        });
      }
    });
  </script>
</body>
</html>
