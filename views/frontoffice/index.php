<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkillBridge — Gestion Test & Validation</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Questrial&family=Roboto:wght@400;500;700&display=swap');
    :root { --heading: #0f2943; --accent: #e87532; --light: #faf9fb; }
    body { font-family: 'Roboto', sans-serif; background: #fff; margin: 0; color: #0a0f14; }

    /* HEADER */
    .ef-header { background: #fff; padding: 18px 0; position: sticky; top: 0; z-index: 100; }
    .ef-header-container { border-radius: 50px; padding: 6px 28px; box-shadow: 0 2px 15px rgba(0,0,0,0.10); display: flex; align-items: center; justify-content: space-between; }
    .ef-logo { font-family: 'Questrial', sans-serif; font-size: 1.35rem; color: var(--heading); text-decoration: none; }
    .ef-logo span { color: var(--accent); }
    .ef-nav { display: flex; gap: 4px; list-style: none; margin: 0; padding: 0; }
    .ef-nav a { font-size: 0.88rem; color: #0a0f14; padding: 8px 14px; border-radius: 20px; text-decoration: none; }
    .ef-nav a:hover { color: var(--accent); }
    .btn-admin { border: 2px solid rgba(232,117,50,0.5); color: var(--accent); padding: 7px 16px; border-radius: 50px; font-weight: 500; background: transparent; text-decoration: none; font-size: 0.82rem; }
    .btn-admin:hover { background: var(--accent); color: white; }

    /* HERO */
    .ef-hero { background: #1a3a6b; padding: 90px 0 80px; position: relative; overflow: hidden; }
    .ef-hero::before { content: ''; position: absolute; top: -120px; left: -120px; width: 420px; height: 420px; border-radius: 50%; background: radial-gradient(circle, rgba(232,117,50,0.18), transparent 70%); }
    .ef-hero-badge { display: inline-block; background: rgba(232,117,50,0.15); color: #f4a76f; font-size: 0.68rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; padding: 6px 18px; border-radius: 99px; border: 1px solid rgba(232,117,50,0.3); margin-bottom: 20px; }
    .ef-hero h1 { font-family: 'Questrial', sans-serif; font-size: 2.8rem; color: white; line-height: 1.2; }
    .ef-hero h1 span { color: var(--accent); }
    .ef-hero p { color: rgba(255,255,255,0.75); font-size: 1rem; line-height: 1.8; }
    .btn-primary-ef { background: var(--accent); color: white; border: none; padding: 12px 28px; border-radius: 50px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-block; }
    .btn-primary-ef:hover { background: #c9621f; color: white; }

    /* CERT CARD */
    .cert-card { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 24px; padding: 28px; }
    .cert-card h3 { color: white; font-family: 'Questrial', sans-serif; font-size: 1rem; margin-bottom: 20px; }
    .cert-stats { display: flex; justify-content: space-around; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 18px; }
    .cert-stats .val { color: white; font-size: 1.3rem; font-weight: 700; display: block; }
    .cert-stats .lbl { color: rgba(255,255,255,0.35); font-size: 0.6rem; text-transform: uppercase; letter-spacing: 1px; }

    /* SECTIONS */
    .section-white { padding: 70px 0; background: white; }
    .section-light { padding: 70px 0; background: var(--light); }
    .section-title-wrap { text-align: center; padding-bottom: 50px; }
    .section-title-wrap h2 { font-family: 'Questrial', sans-serif; font-size: 2.2rem; background: linear-gradient(120deg, var(--heading), var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .section-title-wrap p { color: rgba(10,15,20,0.55); font-size: 0.95rem; max-width: 580px; margin: 0 auto; line-height: 1.8; }

    /* TEST CARDS */
    .ef-card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 2px 20px rgba(0,0,0,0.06); border: 1px solid #f0f0f0; height: 100%; transition: all 0.3s; }
    .ef-card:hover { box-shadow: 0 10px 40px rgba(232,117,50,0.12); border-color: rgba(232,117,50,0.2); transform: translateY(-4px); }
    .ef-card h3 { font-family: 'Questrial', sans-serif; font-size: 1rem; color: var(--heading); margin-bottom: 4px; }
    .ef-card:hover h3 { color: var(--accent); }
    .ef-card .cat { font-size: 0.75rem; color: #9ca3af; margin-bottom: 12px; }
    .level-badge { font-size: 0.65rem; font-weight: 700; padding: 3px 10px; border-radius: 99px; border: 1px solid; }
    .level-debut  { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
    .level-moyen  { background: #fffbeb; color: #d97706; border-color: #fde68a; }
    .level-avance { background: #fff1f2; color: #e11d48; border-color: #fecdd3; }
    .ef-bar { height: 5px; background: #f0f0f0; border-radius: 99px; overflow: hidden; margin-top: 10px; }
    .ef-bar-fill { height: 100%; background: var(--accent); border-radius: 99px; }

    /* FOOTER */
    .ef-footer { background: #0f2943; color: rgba(255,255,255,0.5); text-align: center; padding: 20px; font-size: 0.82rem; }
    .ef-footer strong { color: white; }
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
            <h3>Statistiques ⚡</h3>
            <?php
              // Compter le nombre de tests depuis la base de données
              $all_tests = $tests->fetchAll(PDO::FETCH_ASSOC);
              $total = count($all_tests);
              $total_certifie = 1; // Chadi Hassen
            ?>
            <div class="cert-stats">
              <div><span class="val"><?= $total ?></span><span class="lbl">Tests dispo</span></div>
              <div><span class="val"><?= $total_certifie ?></span><span class="lbl">Certifiés</span></div>
              <div><span class="val">92%</span><span class="lbl">Meilleur score</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- TESTS -->
  <section class="section-light" id="tests">
    <div class="container">
      <div class="section-title-wrap">
        <h2>Découvrez nos certifications</h2>
        <p>Explorez les domaines de compétences que nous validons. Chaque test est mis à jour régulièrement.</p>
      </div>
      <div class="row g-4">
        <?php foreach ($all_tests as $row):
          $badge = 'level-debut';
          if ($row['level'] == 'Moyen')   $badge = 'level-moyen';
          if ($row['level'] == 'Avancé')  $badge = 'level-avance';
        ?>
        <div class="col-md-6 col-lg-4">
          <div class="ef-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div style="font-size:1.6rem;">📊</div>
              <span class="level-badge <?= $badge ?>"><?= htmlspecialchars($row['level']) ?></span>
            </div>
            <h3><?= htmlspecialchars($row['title']) ?></h3>
            <p class="cat"><?= htmlspecialchars($row['category_name']) ?></p>
            <div class="d-flex justify-content-between" style="font-size:0.75rem;color:#9ca3af;">
              <span>⏱ <?= $row['duration'] ?> min</span>
              <span style="color:#e87532;font-weight:700;"><?= $row['average_score'] ?>%</span>
            </div>
            <div class="ef-bar">
              <div class="ef-bar-fill" style="width:<?= $row['average_score'] ?>%;"></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="ef-footer">
    <strong>SkillBridge</strong> — Module : <strong>Gestion Test &amp; Validation</strong> · Mohamed Emin · NEXT STEP · Esprit 2025
  </footer>

</body>
</html>
