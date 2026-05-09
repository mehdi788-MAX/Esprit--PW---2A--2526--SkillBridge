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
$userId = (int)$_SESSION['user_id'];

// Récupérer la demande à éditer (GET ou POST)
$demandeId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($demandeId <= 0) {
    $_SESSION['error'] = "Demande introuvable.";
    header('Location: mes_demandes.php');
    exit;
}

$existing = $ctrl->getDemande($demandeId);
if (!$existing) {
    $_SESSION['error'] = "Demande introuvable.";
    header('Location: mes_demandes.php');
    exit;
}
if ((int)$existing['user_id'] !== $userId) {
    $_SESSION['error'] = "Vous n'avez pas accès à cette demande.";
    header('Location: mes_demandes.php');
    exit;
}

// Valeurs initiales (préremplies depuis la BDD)
$formValues = [
    'title'       => $existing['title'],
    'price'       => $existing['price'],
    'deadline'    => $existing['deadline'],
    'description' => $existing['description'],
];
$errors = [];

// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formValues['title']       = trim($_POST['title']       ?? '');
    $formValues['price']       = trim($_POST['price']       ?? '');
    $formValues['deadline']    = trim($_POST['deadline']    ?? '');
    $formValues['description'] = trim($_POST['description'] ?? '');

    $result = $ctrl->updateDemande(
        $demandeId,
        $userId,
        $formValues['title'],
        $formValues['price'],
        $formValues['deadline'],
        $formValues['description']
    );

    if (!empty($result['success'])) {
        header('Location: mes_demandes.php?updated=1');
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

// Pour le min HTML5 du champ date : on autorise la deadline existante même si elle est passée
$today = date('Y-m-d');
$dateMin = (!empty($existing['deadline']) && $existing['deadline'] < $today) ? $existing['deadline'] : $today;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Modifier la demande — SkillBridge</title>

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

    /* Page */
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

    .auth-card {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 22px;
      padding: 30px 28px;
      box-shadow: 0 30px 60px -25px rgba(31,95,77,.18);
    }

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

    .btn-sage {
      display: flex; align-items: center; justify-content: center; gap: 10px;
      padding: 14px 22px; border-radius: 12px; border: none;
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

    .ad-alert {
      border-radius: 14px; padding: 14px 16px; border: 1px solid; margin-bottom: 18px;
      display: flex; align-items: flex-start; gap: 12px; font-size: .92rem;
    }
    .ad-alert.success { background: var(--sage-soft); border-color: rgba(31,95,77,.2); color: var(--sage-d); }
    .ad-alert.danger  { background: #FEF2F2; border-color: #FECACA; color: #991B1B; }
    .ad-alert ul { margin: 4px 0 0 18px; padding: 0; }
    .ad-alert ul li { margin-top: 2px; }

    .sb-footer { background: var(--ink); color: rgba(255,255,255,.65); padding: 22px 0; font-size: .88rem; text-align: center; }
    .sb-footer strong { color: var(--paper); }

    @media (max-width: 991.98px) {
      .auth-card { padding: 24px 22px; }
      .page-bg { padding: 40px 0 60px; }
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
        <a href="index.php">Accueil</a>
        <a href="../chat/conversations.php">Mes Conversations</a>
        <a href="mes_demandes.php" class="active">Mes Demandes</a>
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

        <div class="text-center mb-5" data-aos="fade-up" style="max-width: 720px; margin: 0 auto;">
          <span class="eyebrow honey"><span class="dot"></span> Modifier une demande</span>
          <h1 class="display-x mt-3 mb-2">Mettre à <span class="accent">jour</span> votre projet.</h1>
          <p class="lead-x mb-0">
            Affinez le titre, le budget, la date limite ou la description. Les freelancers verront immédiatement vos changements.
          </p>
        </div>

        <div class="row justify-content-center">
          <div class="col-12 col-lg-7" style="max-width: 640px;" data-aos="fade-up" data-aos-delay="100">

            <div class="mb-3">
              <a href="mes_demandes.php" class="btn-ghost">
                <i class="bi bi-arrow-left"></i> Retour à mes demandes
              </a>
            </div>

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

              <form id="demandeForm" method="POST" action="edit_demande.php" novalidate>
                <input type="hidden" name="id" value="<?= (int)$demandeId ?>">

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
                           min="<?= $dateMin ?>"
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
                  <a href="mes_demandes.php" class="btn-ghost justify-content-center" style="flex: 0 0 auto;">
                    <i class="bi bi-x-lg"></i> Annuler
                  </a>
                  <button type="submit" class="btn-sage" style="flex: 1 1 auto;">
                    <i class="bi bi-check2-circle"></i> Enregistrer les modifications
                  </button>
                </div>

              </form>
            </div>
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
      const form    = document.getElementById('demandeForm');
      const today   = '<?= $today ?>';
      const dateMin = '<?= $dateMin ?>';

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
        else if (deadline < dateMin) showError('deadline', "La date limite doit être aujourd'hui ou ultérieure.", errs);

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
  </script>
</body>
</html>
