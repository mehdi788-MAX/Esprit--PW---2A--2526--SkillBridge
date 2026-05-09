<?php
require_once 'auth_check.php';
require_once '../../../config.php';
require_once '../../../controller/DemandeController.php';
require_once '../../../model/utilisateur.php';

// Restriction au rôle "client"
if (($_SESSION['user_role'] ?? '') !== 'client') {
    $_SESSION['error'] = "Cet espace est réservé aux clients.";
    header('Location: index.php');
    exit;
}

$BASE = base_url();

$ctrl = new DemandeController();

// Préparer les valeurs (en cas de re-soumission après erreur)
$formValues = [
    'title'       => '',
    'price'       => '',
    'deadline'    => '',
    'description' => '',
];
$errors = [];

// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formValues['title']       = trim($_POST['title']       ?? '');
    $formValues['price']       = trim($_POST['price']       ?? '');
    $formValues['deadline']    = trim($_POST['deadline']    ?? '');
    $formValues['description'] = trim($_POST['description'] ?? '');

    $result = $ctrl->createDemande(
        $_SESSION['user_id'],
        $formValues['title'],
        $formValues['price'],
        $formValues['deadline'],
        $formValues['description']
    );

    if (!empty($result['success'])) {
        header('Location: mes_demandes.php?created=1');
        exit;
    }
    $errors = $result['errors'] ?? ["Erreur inattendue."];
}

// Charger l'utilisateur connecté pour la nav
$utilisateurModel = new Utilisateur($pdo);
$utilisateurModel->id = $_SESSION['user_id'];
$utilisateurModel->readOne();

$navFirstName = trim(explode(' ', trim($utilisateurModel->prenom ?? ''))[0] ?? '') ?: 'Profil';
$navAvatarSrc = !empty($utilisateurModel->photo)
    ? 'assets/img/profile/' . htmlspecialchars($utilisateurModel->photo)
    : 'https://ui-avatars.com/api/?name=' . urlencode(trim(($utilisateurModel->prenom ?? '') . ' ' . ($utilisateurModel->nom ?? '')) ?: 'SkillBridge') . '&background=1F5F4D&color=fff&bold=true&size=80';

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Publier une demande — SkillBridge</title>

  <link href="assets/img/favicon.png" rel="icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">

  <style>
    :root {
      --bg:          #F7F4ED;
      --paper:       #FFFFFF;
      --ink:         #0F0F0F;
      --ink-2:       #2A2A2A;
      --ink-mute:    #5C5C5C;
      --ink-soft:    #A3A3A3;
      --rule:        #E8E2D5;
      --sage:        #1F5F4D;
      --sage-d:      #134438;
      --sage-soft:   #E8F0EC;
      --honey:       #F5C842;
      --honey-d:     #E0B033;
      --honey-soft:  #FBF1D0;
    }
    *, *::before, *::after { box-sizing: border-box; }
    body {
      font-family: 'Manrope', system-ui, -apple-system, sans-serif;
      background: var(--bg); color: var(--ink); letter-spacing: -.005em;
      -webkit-font-smoothing: antialiased; margin: 0;
    }
    ::selection { background: var(--sage); color: var(--honey); }

    h1, h2, h3, h4, h5 { font-family: 'Manrope', sans-serif; font-weight: 700; letter-spacing: -.022em; color: var(--ink); }
    .display-x { font-size: clamp(2rem, 3.6vw, 2.8rem); line-height: 1.05; font-weight: 800; letter-spacing: -.025em; }
    .lead-x    { font-size: 1rem; line-height: 1.55; color: var(--ink-mute); font-weight: 400; }
    .accent    { font-style: italic; font-weight: 700; color: var(--sage); }

    .eyebrow {
      display:inline-flex; align-items:center; gap:8px;
      font-size: .8rem; font-weight: 600;
      color: var(--sage); padding: 6px 12px;
      background: var(--sage-soft); border-radius: 999px;
    }
    .eyebrow .dot { width:6px; height:6px; border-radius:50%; background: var(--sage); }
    .eyebrow.honey { color: #92660A; background: var(--honey-soft); }
    .eyebrow.honey .dot { background: var(--honey-d); }

    /* Header */
    .sb-header {
      position: sticky; top: 0; z-index: 100;
      background: rgba(247,244,237,.85); backdrop-filter: blur(14px);
      border-bottom: 1px solid var(--rule);
    }
    .sb-header .container { display:flex; align-items:center; justify-content:space-between; padding: 14px 0; }
    .sb-logo { display:inline-flex; align-items:center; text-decoration:none; color: var(--ink); }
    .sb-logo .logo-img { height: 38px; width: auto; display: block; }
    .sb-nav { display:flex; align-items:center; gap: 28px; }
    .sb-nav a { color: var(--ink-mute); text-decoration:none; font-weight:500; font-size:.92rem; transition: color .15s; }
    .sb-nav a:hover, .sb-nav a.active { color: var(--ink); }
    .sb-nav a.active { color: var(--sage); }
    .sb-cta {
      display:inline-flex; align-items:center; gap:8px;
      background: var(--ink); color: var(--bg); padding: 10px 20px; border-radius: 999px;
      text-decoration:none; font-weight:600; font-size:.92rem; transition: all .2s ease;
    }
    .sb-cta:hover { background: var(--sage); color: var(--paper); transform: translateY(-1px); }
    .sb-bell-btn {
      width:42px; height:42px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center;
      background: transparent; color: var(--ink); position: relative; transition: all .2s;
    }
    .sb-bell-btn:hover { background: var(--paper); }
    .sb-profile-chip {
      display:inline-flex; align-items:center; gap:8px;
      padding: 4px 14px 4px 4px; border-radius: 999px;
      background: var(--paper); border: 1px solid var(--rule);
      color: var(--ink); text-decoration:none; font-weight:600; font-size:.9rem;
      transition: all .2s;
    }
    .sb-profile-chip:hover { border-color: var(--sage); transform: translateY(-1px); }
    .sb-profile-chip .avatar { width:30px; height:30px; border-radius:50%; object-fit:cover; }
    @media (max-width: 991.98px) { .sb-nav { display: none; } }

    /* Page canvas */
    .page-bg {
      position: relative; overflow: hidden; min-height: calc(100vh - 64px);
      padding: 56px 0 80px;
    }
    .blob { position: absolute; border-radius: 50%; filter: blur(60px); opacity: .55; pointer-events: none; z-index: 0; }
    .blob.sage  { background: var(--sage-soft); }
    .blob.honey { background: var(--honey-soft); }
    .blob-1 { width: 380px; height: 380px; left: -120px; top: -80px; }
    .blob-2 { width: 340px; height: 340px; right: -100px; bottom: 200px; }
    .page-bg .container { position: relative; z-index: 1; }

    /* Cards */
    .auth-card {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 22px;
      padding: 30px 28px;
      box-shadow: 0 30px 60px -25px rgba(31,95,77,.18);
    }

    /* Form */
    .form-label { font-weight: 600; color: var(--ink-2); font-size: .87rem; margin-bottom: 6px; display: block; }
    .form-control, .form-select {
      width: 100%;
      border-radius: 12px; border: 1px solid var(--rule); padding: 11px 14px;
      font-size: .95rem; background: var(--paper); color: var(--ink);
      transition: border-color .2s, box-shadow .2s;
      font-family: 'Manrope', sans-serif;
    }
    .form-control:focus, .form-select:focus {
      outline: none;
      border-color: var(--sage);
      box-shadow: 0 0 0 4px rgba(31,95,77,.12);
    }
    .form-control.is-invalid { border-color: #DC2626; }
    .form-text { color: var(--ink-soft); font-size: .82rem; }
    textarea.form-control { resize: vertical; min-height: 120px; line-height: 1.55; }

    /* Buttons */
    .btn-sage {
      display: flex; align-items: center; justify-content: center; gap: 10px;
      width: 100%; padding: 14px 22px; border-radius: 12px; border: none;
      background: var(--sage); color: var(--paper);
      font-weight: 700; font-size: 1rem; cursor: pointer;
      transition: all .2s ease; text-decoration: none;
    }
    .btn-sage:hover {
      background: var(--sage-d); transform: translateY(-2px);
      box-shadow: 0 14px 28px -12px rgba(31,95,77,.4);
      color: var(--paper);
    }
    .btn-ghost {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--paper); color: var(--ink);
      padding: 11px 18px; border-radius: 10px;
      border: 1px solid var(--rule);
      text-decoration: none; font-weight: 600; font-size: .9rem;
      transition: all .2s ease;
    }
    .btn-ghost:hover { border-color: var(--sage); color: var(--sage); }

    /* Alerts */
    .ad-alert {
      border-radius: 14px; padding: 14px 16px; border: 1px solid; margin-bottom: 18px;
      display: flex; align-items: flex-start; gap: 12px; font-size: .92rem;
    }
    .ad-alert.success { background: var(--sage-soft); border-color: rgba(31,95,77,.2); color: var(--sage-d); }
    .ad-alert.danger  { background: #FEF2F2; border-color: #FECACA; color: #991B1B; }
    .ad-alert ul { margin: 4px 0 0 18px; padding: 0; }
    .ad-alert ul li { margin-top: 2px; }

    /* Footer */
    .sb-footer { background: var(--ink); color: rgba(255,255,255,.65); padding: 22px 0; font-size: .88rem; text-align: center; }
    .sb-footer strong { color: var(--paper); }

    /* ===== Panneau IA — analyse temps réel du brief ===== */
    .ai-panel {
      position: sticky; top: 100px;
      background: linear-gradient(180deg, var(--paper) 0%, var(--bg) 100%);
      border: 1px solid var(--rule); border-radius: 22px;
      padding: 24px 22px; display: flex; flex-direction: column; gap: 14px;
      box-shadow: 0 22px 48px -28px rgba(31,95,77,.22);
    }
    .ai-head {
      display: flex; align-items: center; gap: 12px;
      padding-bottom: 14px; border-bottom: 1px dashed var(--rule);
    }
    .ai-icon {
      width: 44px; height: 44px; border-radius: 14px;
      background: var(--sage); color: var(--honey);
      display:flex; align-items:center; justify-content:center;
      font-size: 1.25rem; flex-shrink: 0;
      box-shadow: 0 8px 18px -8px rgba(31,95,77,.55);
    }
    .ai-title { font-weight: 800; color: var(--ink); font-size: 1rem; line-height: 1.2; }
    .ai-status {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: .78rem; color: var(--ink-mute); margin-top: 3px;
    }
    .ai-status .dot {
      width: 7px; height: 7px; border-radius: 50%;
      background: var(--ink-mute);
    }
    .ai-status[data-state="thinking"] .dot { background: var(--honey); animation: aiPulse 1s ease-in-out infinite; }
    .ai-status[data-state="ready"]    .dot { background: var(--sage); }
    .ai-status[data-state="offline"]  .dot { background: #C44; }
    @keyframes aiPulse { 0%,100% { opacity: .35; } 50% { opacity: 1; } }

    .ai-block {
      background: var(--paper); border: 1px solid var(--rule);
      border-radius: 14px; padding: 14px 16px;
      display: flex; flex-direction: column; gap: 8px;
    }
    .ai-block-head {
      font-size: .72rem; font-weight: 800; letter-spacing: .06em;
      text-transform: uppercase; color: var(--sage);
      display: inline-flex; align-items: center; gap: 6px;
    }
    .ai-block-body {
      margin: 0; font-size: .92rem; line-height: 1.55; color: var(--ink-2);
    }
    .ai-suggestions {
      list-style: none; padding: 0; margin: 0;
      display: flex; flex-direction: column; gap: 8px;
    }
    .ai-suggestions li {
      font-size: .9rem; color: var(--ink-2); line-height: 1.5;
      padding-left: 22px; position: relative;
    }
    .ai-suggestions li::before {
      content: '✦'; position: absolute; left: 0; top: 1px;
      color: var(--honey-d); font-weight: 800;
    }
    .ai-empty {
      text-align: center; padding: 24px 16px;
      color: var(--ink-mute); font-size: .9rem;
    }
    .ai-empty i { font-size: 1.6rem; color: var(--sage); display:block; margin-bottom: 8px; }
    .ai-empty p { margin: 0; }
    .ai-foot {
      font-size: .75rem; color: var(--ink-soft);
      display: inline-flex; align-items: center; gap: 6px;
      padding-top: 12px; border-top: 1px dashed var(--rule);
    }
    .ai-foot i { color: var(--sage); }

    /* Skeleton loading */
    .ai-skel {
      height: 12px; border-radius: 6px;
      background: linear-gradient(90deg, var(--rule) 0%, var(--bg) 50%, var(--rule) 100%);
      background-size: 200% 100%; animation: skel 1.4s linear infinite;
    }
    @keyframes skel { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

    @media (max-width: 991.98px) {
      .auth-card { padding: 24px 22px; }
      .page-bg { padding: 40px 0 60px; }
      .ai-panel { position: static; }
    }
  </style>
</head>
<body>

  <header class="sb-header">
    <div class="container">
      <a href="index.php" class="sb-logo">
        <img src="assets/img/skillbridge-logo.png" alt="SkillBridge" class="logo-img" loading="eager">
      </a>
      <nav class="sb-nav">
        <?= frontoffice_main_nav('mes_demandes', '.', '../chat') ?>
      </nav>
      <div class="d-flex align-items-center gap-2">
        <span id="bellSlot" class="sb-bell-btn" style="display:inline-flex;"></span>
        <a href="profil.php" class="sb-profile-chip" title="Mon Profil">
          <img src="<?= $navAvatarSrc ?>" alt="" class="avatar"
               onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?= urlencode($navFirstName) ?>&background=1F5F4D&color=fff&bold=true&size=80';">
          <span><?= htmlspecialchars($navFirstName) ?></span>
        </a>
        <a href="<?= $BASE ?>/controller/utilisateurcontroller.php?action=logout" class="sb-cta d-none d-md-inline-flex">
          <i class="bi bi-box-arrow-right"></i><span>Quitter</span>
        </a>
      </div>
    </div>
  </header>

  <main>
    <section class="page-bg">
      <div class="blob sage  blob-1"></div>
      <div class="blob honey blob-2"></div>

      <div class="container">

        <!-- Page header -->
        <div class="text-center mb-5" data-aos="fade-up" style="max-width: 720px; margin: 0 auto;">
          <span class="eyebrow honey"><span class="dot"></span> Publier une demande</span>
          <h1 class="display-x mt-3 mb-2">Décrivez votre <span class="accent">besoin</span>.</h1>
          <p class="lead-x mb-0">
            Précisez votre projet, votre budget et votre échéance — les freelancers de la communauté pourront ensuite
            répondre avec leurs propositions sur mesure.
          </p>
        </div>

        <div class="row justify-content-center g-4">
          <div class="col-12 col-lg-7" data-aos="fade-up" data-aos-delay="100">
            <div class="auth-card">

              <?php if (!empty($errors)): ?>
                <div class="ad-alert danger" role="alert">
                  <i class="bi bi-exclamation-triangle-fill fs-5 mt-1"></i>
                  <div>
                    <div style="font-weight:700; margin-bottom:2px;">Vérifiez les informations saisies</div>
                    <ul>
                      <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                </div>
              <?php endif; ?>

              <div id="js-errors" class="ad-alert danger d-none" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5 mt-1"></i>
                <div>
                  <div style="font-weight:700; margin-bottom:2px;">Vérifiez les informations saisies</div>
                  <ul id="js-errors-list"></ul>
                </div>
              </div>

              <form id="demandeForm" method="POST" action="add_demande.php" novalidate>

                <div class="mb-3">
                  <label for="title" class="form-label">Titre de la demande</label>
                  <input type="text" id="title" name="title" class="form-control"
                         minlength="5" maxlength="150"
                         value="<?= htmlspecialchars($formValues['title']) ?>"
                         placeholder="ex: Création d'un site vitrine pour une PME">
                  <div class="form-text"><i class="bi bi-info-circle me-1"></i>Entre 5 et 150 caractères.</div>
                  <div id="title-error" class="text-danger mt-1" style="font-size:.85rem; display:none;"></div>
                </div>

                <div class="row gy-3 mb-3">
                  <div class="col-md-6">
                    <label for="price" class="form-label">Budget (TND)</label>
                    <input type="number" id="price" name="price" class="form-control"
                           min="1" step="0.01"
                           value="<?= htmlspecialchars($formValues['price']) ?>"
                           placeholder="ex: 500">
                    <div id="price-error" class="text-danger mt-1" style="font-size:.85rem; display:none;"></div>
                  </div>
                  <div class="col-md-6">
                    <label for="deadline" class="form-label">Date limite</label>
                    <input type="date" id="deadline" name="deadline" class="form-control"
                           min="<?= $today ?>"
                           value="<?= htmlspecialchars($formValues['deadline']) ?>">
                    <div id="deadline-error" class="text-danger mt-1" style="font-size:.85rem; display:none;"></div>
                  </div>
                </div>

                <div class="mb-4">
                  <label for="description" class="form-label">Description du projet</label>
                  <textarea id="description" name="description" class="form-control" rows="5"
                            minlength="20"
                            placeholder="Décrivez le contexte, les livrables attendus, les contraintes techniques..."><?= htmlspecialchars($formValues['description']) ?></textarea>
                  <div class="form-text"><i class="bi bi-info-circle me-1"></i>Au moins 20 caractères.</div>
                  <div id="description-error" class="text-danger mt-1" style="font-size:.85rem; display:none;"></div>
                </div>

                <div class="d-flex flex-column flex-sm-row gap-2">
                  <a href="index.php" class="btn-ghost justify-content-center" style="flex: 0 0 auto;">
                    <i class="bi bi-arrow-left"></i> Annuler
                  </a>
                  <button type="submit" class="btn-sage" style="flex: 1 1 auto;">
                    Publier la demande <i class="bi bi-arrow-right"></i>
                  </button>
                </div>

              </form>

            </div>
          </div>

          <!-- ========================================== -->
          <!-- Panneau IA — analyse en direct du brief    -->
          <!-- ========================================== -->
          <div class="col-12 col-lg-5" data-aos="fade-up" data-aos-delay="200">
            <aside class="ai-panel" id="aiPanel" aria-live="polite">
              <header class="ai-head">
                <div class="ai-icon"><i class="bi bi-stars"></i></div>
                <div class="ai-meta">
                  <div class="ai-title">Assistant IA</div>
                  <div class="ai-status" id="aiStatus">
                    <span class="dot"></span>
                    <span id="aiStatusText">Analyse en attente…</span>
                  </div>
                </div>
              </header>

              <section class="ai-block" id="aiSummaryBlock" hidden>
                <div class="ai-block-head"><i class="bi bi-lightbulb-fill"></i> Synthèse du besoin</div>
                <p class="ai-block-body" id="aiSummary"></p>
              </section>

              <section class="ai-block" id="aiPriceBlock" hidden>
                <div class="ai-block-head"><i class="bi bi-cash-coin"></i> Verdict budget</div>
                <p class="ai-block-body" id="aiPriceAdvice"></p>
              </section>

              <section class="ai-block" id="aiDeadlineBlock" hidden>
                <div class="ai-block-head"><i class="bi bi-calendar-event-fill"></i> Verdict délai</div>
                <p class="ai-block-body" id="aiDeadlineAdvice"></p>
              </section>

              <section class="ai-block" id="aiSuggestionsBlock" hidden>
                <div class="ai-block-head"><i class="bi bi-magic"></i> Suggestions pour améliorer</div>
                <ul class="ai-suggestions" id="aiSuggestions"></ul>
              </section>

              <div class="ai-empty" id="aiEmpty">
                <i class="bi bi-keyboard"></i>
                <p>Commencez à rédiger votre demande — l'analyse apparaît automatiquement après quelques secondes.</p>
              </div>

              <footer class="ai-foot">
                <i class="bi bi-shield-check"></i>
                Analyse locale (Ollama · Qwen). Vos données ne quittent pas votre serveur.
              </footer>
            </aside>
          </div>
        </div>

      </div>
    </section>
  </main>

  <footer class="sb-footer">
    © <?= date('Y') ?> <strong>SkillBridge</strong> — Tous droits réservés.
  </footer>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="../../shared/chatbus.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (typeof ChatBus !== 'undefined') {
        ChatBus.init({ apiBase: '../../../api/chat.php', user: <?= (int)$_SESSION['user_id'] ?>, conv: 0 });
        ChatBus.mountBell('#bellSlot');
      }
    });

    if (typeof AOS !== 'undefined') {
      AOS.init({ duration: 600, easing: 'ease-out-cubic', once: true });
    }

    (function () {
      const form = document.getElementById('demandeForm');
      const today = '<?= $today ?>';

      function clearErrors() {
        ['title','price','deadline','description'].forEach(function(id) {
          const f = document.getElementById(id);
          if (f) f.classList.remove('is-invalid');
          const e = document.getElementById(id + '-error');
          if (e) { e.textContent = ''; e.style.display = 'none'; }
        });
        const box = document.getElementById('js-errors');
        if (box) {
          box.classList.add('d-none');
          document.getElementById('js-errors-list').innerHTML = '';
        }
      }

      function showError(field, message, bag) {
        const f = document.getElementById(field);
        const e = document.getElementById(field + '-error');
        if (f) f.classList.add('is-invalid');
        if (e) { e.textContent = message; e.style.display = 'block'; }
        bag.push(message);
      }

      form.addEventListener('submit', function (e) {
        clearErrors();
        const errs = [];
        const title       = document.getElementById('title').value.trim();
        const price       = document.getElementById('price').value.trim();
        const deadline    = document.getElementById('deadline').value.trim();
        const description = document.getElementById('description').value.trim();

        if (title.length < 5)   showError('title', 'Le titre doit contenir au moins 5 caractères.', errs);
        else if (title.length > 150) showError('title', 'Le titre ne peut pas dépasser 150 caractères.', errs);

        if (price === '' || isNaN(parseFloat(price))) showError('price', 'Le prix doit être un nombre.', errs);
        else if (parseFloat(price) < 1) showError('price', 'Le prix doit être supérieur ou égal à 1.', errs);

        if (deadline === '') showError('deadline', 'La date limite est obligatoire.', errs);
        else if (deadline < today) showError('deadline', "La date limite doit être aujourd'hui ou ultérieure.", errs);

        if (description.length < 20) showError('description', 'La description doit contenir au moins 20 caractères.', errs);

        if (errs.length > 0) {
          e.preventDefault();
          const box  = document.getElementById('js-errors');
          const list = document.getElementById('js-errors-list');
          errs.forEach(function (m) { const li = document.createElement('li'); li.textContent = m; list.appendChild(li); });
          box.classList.remove('d-none');
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      });
    })();

    /* =========================================================
     * Panneau IA — analyse en direct du brief
     * ---------------------------------------------------------
     * Debounce 1.2 s après la dernière frappe → POST api/ai_advice.php
     * → met à jour synthèse / verdict prix / verdict délai / suggestions.
     * Annule la requête en cours si une nouvelle est lancée (AbortController).
     * ========================================================= */
    (function () {
      const fields = {
        title:       document.getElementById('title'),
        description: document.getElementById('description'),
        price:       document.getElementById('price'),
        deadline:    document.getElementById('deadline'),
      };
      const panel       = document.getElementById('aiPanel');
      const statusEl    = document.getElementById('aiStatus');
      const statusText  = document.getElementById('aiStatusText');
      const emptyEl     = document.getElementById('aiEmpty');
      const summaryEl   = document.getElementById('aiSummary');
      const summaryB    = document.getElementById('aiSummaryBlock');
      const priceEl     = document.getElementById('aiPriceAdvice');
      const priceB      = document.getElementById('aiPriceBlock');
      const deadlineEl  = document.getElementById('aiDeadlineAdvice');
      const deadlineB   = document.getElementById('aiDeadlineBlock');
      const suggEl      = document.getElementById('aiSuggestions');
      const suggB       = document.getElementById('aiSuggestionsBlock');
      if (!panel || !fields.title) return;

      let debounce = null;
      let activeController = null;

      function setStatus(state, text) {
        statusEl.dataset.state = state;
        statusText.textContent = text;
      }
      function show(block, on) { if (block) block.hidden = !on; }

      function refresh() {
        const title = fields.title.value.trim();
        const desc  = fields.description.value.trim();
        if (title.length < 3 && desc.length < 10) {
          // Trop tôt — on garde l'état initial.
          show(summaryB, false); show(priceB, false); show(deadlineB, false); show(suggB, false);
          show(emptyEl, true);
          setStatus('idle', 'Analyse en attente…');
          return;
        }

        if (activeController) { activeController.abort(); }
        activeController = new AbortController();
        const controller = activeController;

        show(emptyEl, false);
        setStatus('thinking', 'Analyse en cours…');

        const fd = new FormData();
        fd.append('title',       title);
        fd.append('description', desc);
        fd.append('price',       fields.price.value.trim());
        fd.append('deadline',    fields.deadline.value.trim());

        fetch('../../../api/ai_advice.php', {
          method: 'POST',
          body: fd,
          credentials: 'same-origin',
          signal: controller.signal,
        })
        .then(r => r.ok ? r.json() : Promise.reject(new Error('http ' + r.status)))
        .then(data => {
          if (controller !== activeController) return;
          if (data.summary) {
            summaryEl.textContent = data.summary;
            show(summaryB, true);
          } else { show(summaryB, false); }
          if (data.price_advice) {
            priceEl.textContent = data.price_advice;
            show(priceB, true);
          } else { show(priceB, false); }
          if (data.deadline_advice) {
            deadlineEl.textContent = data.deadline_advice;
            show(deadlineB, true);
          } else { show(deadlineB, false); }
          if (Array.isArray(data.suggestions) && data.suggestions.length) {
            suggEl.innerHTML = '';
            data.suggestions.forEach(function (s) {
              const li = document.createElement('li');
              li.textContent = s;
              suggEl.appendChild(li);
            });
            show(suggB, true);
          } else { show(suggB, false); }
          setStatus(data.available ? 'ready' : 'offline', data.available ? 'Analyse à jour' : 'IA hors ligne — verdicts dataset uniquement');
        })
        .catch(err => {
          if (err && err.name === 'AbortError') return;
          setStatus('offline', 'Analyse indisponible. Réessayez dans un instant.');
        });
      }

      function trigger() {
        clearTimeout(debounce);
        debounce = setTimeout(refresh, 1200);
      }

      ['input', 'change'].forEach(function (evt) {
        Object.values(fields).forEach(function (el) { if (el) el.addEventListener(evt, trigger); });
      });

      // Première analyse si le formulaire a déjà des valeurs (ex: edit_demande).
      if (fields.title.value.trim() !== '' || fields.description.value.trim() !== '') {
        refresh();
      }
    })();
  </script>
</body>
</html>
