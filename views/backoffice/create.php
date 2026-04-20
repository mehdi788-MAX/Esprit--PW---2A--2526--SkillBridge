<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SkillBridge — Ajouter un test</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Nunito', sans-serif; background: #f8f9fc; color: #5a5c69; margin: 0; }
    .sb-topbar { background: white; height: 56px; border-bottom: 1px solid #e3e6f0; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 100; box-shadow: 0 0.15rem 1.75rem rgba(58,59,69,0.08); }
    .sb-topbar-brand { font-size: 1rem; font-weight: 800; color: #4e73df; text-decoration: none; }
    .sb-body { display: flex; min-height: calc(100vh - 56px); }
    .sb-sidebar { width: 224px; flex-shrink: 0; background: linear-gradient(180deg, #4e73df 10%, #224abe 100%); position: sticky; top: 56px; height: calc(100vh - 56px); }
    .sb-brand { padding: 18px 16px; border-bottom: 1px solid rgba(255,255,255,0.15); }
    .sb-brand-text { color: white; font-weight: 800; font-size: 0.9rem; }
    .sb-heading { color: rgba(255,255,255,0.4); font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; padding: 10px 16px 4px; }
    .sb-nav { list-style: none; padding: 0; margin: 0; }
    .sb-nav a { display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: rgba(255,255,255,0.8); font-size: 0.82rem; font-weight: 700; text-decoration: none; border-left: 3px solid transparent; transition: 0.2s; }
    .sb-nav a:hover, .sb-nav a.active { color: white; background: rgba(255,255,255,0.1); border-left-color: white; }
    .sb-content { flex: 1; padding: 24px; }
    .sb-card { background: white; border-radius: 8px; box-shadow: 0 0.15rem 1.75rem rgba(58,59,69,0.08); margin-bottom: 24px; }
    .sb-card-header { padding: 12px 20px; border-bottom: 1px solid #e3e6f0; }
    .sb-card-header h6 { font-weight: 800; color: #4e73df; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; margin: 0; }
    .sb-card-body { padding: 28px; }
    label { font-size: 0.78rem; font-weight: 700; color: #858796; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; display: block; }
    .form-input {
      width: 100%; padding: 10px 14px; border: 1px solid #d1d3e2;
      border-radius: 6px; font-family: 'Nunito', sans-serif;
      font-size: 0.88rem; outline: none; color: #5a5c69;
      transition: border-color 0.2s;
    }
    .form-input:focus { border-color: #4e73df; box-shadow: 0 0 0 3px rgba(78,115,223,0.15); }
    /* Message d'erreur JS */
    .erreur-msg { color: #e74a3b; font-size: 0.78rem; margin-top: 4px; display: none; font-weight: 600; }
    .btn-save { background: #4e73df; color: white; padding: 10px 28px; border-radius: 6px; border: none; font-weight: 700; font-size: 0.88rem; cursor: pointer; font-family: 'Nunito', sans-serif; }
    .btn-save:hover { background: #2e59d9; }
    .btn-cancel { background: #858796; color: white; padding: 10px 20px; border-radius: 6px; border: none; font-weight: 700; font-size: 0.88rem; cursor: pointer; text-decoration: none; display: inline-block; }
  </style>
</head>
<body>

  <!-- TOPBAR -->
  <div class="sb-topbar">
    <a href="index.php" class="sb-topbar-brand">SkillBridge | Admin</a>
    <a href="index.php?action=frontoffice" style="font-size:0.82rem;color:#4e73df;font-weight:700;text-decoration:none;">👤 Vue Client</a>
  </div>

  <div class="sb-body">
    <!-- SIDEBAR -->
    <div class="sb-sidebar">
      <div class="sb-brand"><div class="sb-brand-text">📋 Test & Validation</div></div>
      <div class="sb-heading">Navigation</div>
      <ul class="sb-nav">
        <li><a href="index.php?action=index">🏠 Dashboard</a></li>
        <li><a href="index.php?action=create" class="active">➕ Ajouter un test</a></li>
        <li><a href="index.php?action=frontoffice">🌐 Vue client</a></li>
      </ul>
    </div>

    <!-- CONTENU -->
    <div class="sb-content">
      <h4 style="font-weight:800;color:#5a5c69;margin-bottom:4px;">Ajouter un test</h4>
      <p style="font-size:0.82rem;color:#858796;margin-bottom:20px;">
        <a href="index.php?action=index" style="color:#4e73df;text-decoration:none;">Tests</a> › Nouveau
      </p>

      <div class="sb-card">
        <div class="sb-card-header"><h6>📋 Nouveau test</h6></div>
        <div class="sb-card-body">

          <!-- Le formulaire envoie vers store -->
          <form action="index.php?action=store" method="POST" onsubmit="return validerFormTest()">

            <div class="row g-4">

              <!-- Titre -->
              <div class="col-md-6">
                <label for="title">Titre du test</label>
                <input type="text" id="title" name="title" class="form-input" placeholder="ex: Développement Web Frontend">
                <div id="error_title" class="erreur-msg"></div>
              </div>

              <!-- Catégorie -->
              <div class="col-md-6">
                <label for="category_id">Catégorie</label>
                <select id="category_id" name="category_id" class="form-input">
                  <option value="">-- Choisir une catégorie --</option>
                  <?php while ($cat = $categories->fetch(PDO::FETCH_ASSOC)): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                  <?php endwhile; ?>
                </select>
                <div id="error_category" class="erreur-msg"></div>
              </div>

              <!-- Durée -->
              <div class="col-md-4">
                <label for="duration">Durée (minutes)</label>
                <input type="text" id="duration" name="duration" class="form-input" placeholder="ex: 60">
                <div id="error_duration" class="erreur-msg"></div>
              </div>

              <!-- Niveau -->
              <div class="col-md-4">
                <label for="level">Niveau</label>
                <select id="level" name="level" class="form-input">
                  <option value="">-- Choisir un niveau --</option>
                  <option value="Débutant">Débutant</option>
                  <option value="Moyen">Moyen</option>
                  <option value="Avancé">Avancé</option>
                </select>
                <div id="error_level" class="erreur-msg"></div>
              </div>

              <!-- Score moyen -->
              <div class="col-md-4">
                <label for="average_score">Score moyen (%)</label>
                <input type="text" id="average_score" name="average_score" class="form-input" placeholder="ex: 75">
                <div id="error_score" class="erreur-msg"></div>
              </div>

              <!-- Boutons -->
              <div class="col-12 d-flex gap-3 mt-2">
                <button type="submit" class="btn-save">💾 Enregistrer</button>
                <a href="index.php?action=index" class="btn-cancel">Annuler</a>
              </div>

            </div>
          </form>

        </div>
      </div>
    </div>
  </div>

  <script src="assets/js/validation.js"></script>
</body>
</html>
