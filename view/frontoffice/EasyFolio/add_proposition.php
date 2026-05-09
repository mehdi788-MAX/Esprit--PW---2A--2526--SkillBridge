<?php
require_once 'auth_check.php';
require_once '../../../config.php';
require_once '../../../controller/DemandeController.php';

$BASE = base_url();
$ctrl = new DemandeController();

// Role check
$role = strtolower($_SESSION['user_role'] ?? '');
if ($role !== 'freelancer') {
    $_SESSION['error'] = "Seuls les freelancers peuvent envoyer des propositions.";
    header('Location: browse_demandes.php');
    exit;
}

// Demande validation
$demande_id = isset($_GET['demande_id']) ? intval($_GET['demande_id']) : 0;
if ($demande_id <= 0 || !$ctrl->demandeExists($demande_id)) {
    $_SESSION['error'] = "Demande introuvable.";
    header('Location: browse_demandes.php');
    exit;
}

$demande = $ctrl->getDemande($demande_id);

// Author lookup for the demande summary card
$authorRow = null;
if (!empty($demande['user_id'])) {
    $aStmt = $pdo->prepare("SELECT nom, prenom, photo FROM utilisateurs WHERE id = :id");
    $aStmt->execute([':id' => (int)$demande['user_id']]);
    $authorRow = $aStmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
$authorName = $authorRow ? trim($authorRow['prenom'] . ' ' . $authorRow['nom']) : 'Client SkillBridge';
$authorAvatar = ($authorRow && !empty($authorRow['photo'])) ? 'assets/img/profile/' . htmlspecialchars($authorRow['photo']) : 'https://ui-avatars.com/api/?name=' . urlencode($authorName) . '&background=1F5F4D&color=fff&size=120';

// Form state
$errors = [];
$old = [
    'freelancer_name' => $_SESSION['user_nom'] ?? '',
    'price' => '',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $freelancer_name = trim((string)($_POST['freelancer_name'] ?? ''));
    $price           = $_POST['price']   ?? '';
    $message         = trim((string)($_POST['message'] ?? ''));

    $old['freelancer_name'] = $freelancer_name;
    $old['price']           = $price;
    $old['message']         = $message;

    $res = $ctrl->createProposition($_SESSION['user_id'], $demande_id, $freelancer_name, $message, $price);
    if ($res['success']) {
        header('Location: mes_propositions.php?created=1');
        exit;
    }
    $errors = $res['errors'];
}

$navName = trim(explode(' ', trim($_SESSION['user_nom'] ?? ''))[0] ?? '') ?: 'Profil';
$navAvatarSrc = 'https://ui-avatars.com/api/?name=' . urlencode($navName) . '&background=1F5F4D&color=fff&bold=true&size=80';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Faire une proposition — SkillBridge</title>
  <link href="assets/img/favicon.png" rel="icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <style>
    :root{--bg:#F7F4ED;--paper:#FFF;--ink:#0F0F0F;--ink-2:#2A2A2A;--ink-mute:#5C5C5C;--ink-soft:#A3A3A3;--rule:#E8E2D5;--sage:#1F5F4D;--sage-d:#134438;--sage-soft:#E8F0EC;--honey:#F5C842;--honey-d:#E0B033;--honey-soft:#FBF1D0;}
    *,*::before,*::after{box-sizing:border-box}
    body{font-family:'Manrope',system-ui,sans-serif;background:var(--bg);color:var(--ink);letter-spacing:-.005em;-webkit-font-smoothing:antialiased;margin:0}
    ::selection{background:var(--sage);color:var(--honey)}
    h1,h2,h3,h4,h5{font-family:'Manrope',sans-serif;font-weight:700;letter-spacing:-.022em;color:var(--ink)}
    .display-x{font-size:clamp(2rem,3.6vw,2.8rem);line-height:1.05;font-weight:800;letter-spacing:-.025em}
    .lead-x{font-size:1rem;line-height:1.55;color:var(--ink-mute);font-weight:400}
    .accent{font-style:italic;font-weight:700;color:var(--sage)}
    .eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:.8rem;font-weight:600;color:var(--sage);padding:6px 12px;background:var(--sage-soft);border-radius:999px}
    .eyebrow .dot{width:6px;height:6px;border-radius:50%;background:var(--sage)}
    .eyebrow.honey{color:#92660A;background:var(--honey-soft)} .eyebrow.honey .dot{background:var(--honey-d)}
    .sb-header{position:sticky;top:0;z-index:100;background:rgba(247,244,237,.85);backdrop-filter:blur(14px);border-bottom:1px solid var(--rule)}
    .sb-header .container{display:flex;align-items:center;justify-content:space-between;padding:14px 0}
    .sb-logo{display:inline-flex;align-items:center;text-decoration:none;color:var(--ink)} .sb-logo .logo-img{height:38px;width:auto;display:block}
    .sb-nav{display:flex;align-items:center;gap:28px}
    .sb-nav a{color:var(--ink-mute);text-decoration:none;font-weight:500;font-size:.92rem;transition:color .15s}
    .sb-nav a:hover,.sb-nav a.active{color:var(--ink)} .sb-nav a.active{color:var(--sage)}
    .sb-cta{display:inline-flex;align-items:center;gap:8px;background:var(--ink);color:var(--bg);padding:10px 20px;border-radius:999px;text-decoration:none;font-weight:600;font-size:.92rem;transition:all .2s}
    .sb-cta:hover{background:var(--sage);color:var(--paper);transform:translateY(-1px)}
    .sb-bell-btn{width:42px;height:42px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:transparent;color:var(--ink);position:relative;transition:all .2s} .sb-bell-btn:hover{background:var(--paper)}
    .sb-profile-chip{display:inline-flex;align-items:center;gap:8px;padding:4px 14px 4px 4px;border-radius:999px;background:var(--paper);border:1px solid var(--rule);color:var(--ink);text-decoration:none;font-weight:600;font-size:.9rem;transition:all .2s}
    .sb-profile-chip:hover{border-color:var(--sage);transform:translateY(-1px)} .sb-profile-chip .avatar{width:30px;height:30px;border-radius:50%;object-fit:cover}
    @media (max-width:991.98px){.sb-nav{display:none}}
    .page-bg{position:relative;overflow:hidden;min-height:calc(100vh - 64px);padding:48px 0 80px}
    .blob{position:absolute;border-radius:50%;filter:blur(60px);opacity:.55;pointer-events:none;z-index:0}
    .blob.sage{background:var(--sage-soft)} .blob.honey{background:var(--honey-soft)}
    .blob-1{width:380px;height:380px;left:-120px;top:-80px} .blob-2{width:340px;height:340px;right:-100px;bottom:200px}
    .page-bg .container{position:relative;z-index:1}
    .auth-card{background:var(--paper);border:1px solid var(--rule);border-radius:22px;padding:30px 28px;box-shadow:0 30px 60px -25px rgba(31,95,77,.18)}
    .form-label{font-weight:600;color:var(--ink-2);font-size:.87rem;margin-bottom:6px;display:block}
    .form-control,.form-select{width:100%;border-radius:12px;border:1px solid var(--rule);padding:11px 14px;font-size:.95rem;background:var(--paper);color:var(--ink);transition:border-color .2s,box-shadow .2s;font-family:'Manrope',sans-serif}
    .form-control:focus,.form-select:focus{outline:none;border-color:var(--sage);box-shadow:0 0 0 4px rgba(31,95,77,.12)}
    .form-control.is-invalid{border-color:#DC2626}
    textarea.form-control{resize:vertical;min-height:120px;line-height:1.55}
    .form-text{color:var(--ink-soft);font-size:.82rem}
    .btn-sage{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:14px 22px;border-radius:12px;border:none;background:var(--sage);color:var(--paper);font-weight:700;font-size:1rem;cursor:pointer;transition:all .2s;text-decoration:none}
    .btn-sage:hover{background:var(--sage-d);transform:translateY(-2px);box-shadow:0 14px 28px -12px rgba(31,95,77,.4);color:var(--paper)}
    .btn-ghost{display:inline-flex;align-items:center;gap:8px;background:var(--paper);color:var(--ink);padding:11px 18px;border-radius:10px;border:1px solid var(--rule);text-decoration:none;font-weight:600;font-size:.9rem;transition:all .2s}
    .btn-ghost:hover{border-color:var(--sage);color:var(--sage)}
    .sb-footer{background:var(--ink);color:rgba(255,255,255,.65);padding:22px 0;font-size:.88rem;text-align:center} .sb-footer strong{color:var(--paper)}
    .summary-card{background:var(--paper);border:1px solid var(--rule);border-radius:18px;padding:20px;display:flex;flex-direction:column;gap:12px}
    .summary-card .ttl{font-size:1.1rem;font-weight:800;color:var(--ink);margin:0}
    .summary-card .desc{font-size:.9rem;color:var(--ink-mute);line-height:1.55;margin:0}
    .summary-card .meta{display:flex;flex-wrap:wrap;gap:8px}
    .chip{display:inline-flex;align-items:center;gap:6px;padding:5px 11px;border-radius:999px;font-size:.78rem;font-weight:700}
    .chip.sage{background:var(--sage-soft);color:var(--sage)}
    .chip.honey{background:var(--honey-soft);color:#92660A}
    .author-row{display:flex;align-items:center;gap:10px;padding-top:12px;border-top:1px dashed var(--rule)}
    .author-row img{width:36px;height:36px;border-radius:50%;object-fit:cover}
    .author-row .lbl{font-size:.7rem;color:var(--ink-soft);text-transform:uppercase;letter-spacing:.06em;font-weight:700}
    .author-row .who{font-weight:700;color:var(--ink-2);font-size:.92rem}
    .alert-danger{background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;border-radius:14px;padding:14px 16px;margin-bottom:16px;font-size:.92rem}
    .alert-danger ul{margin:6px 0 0;padding-left:18px}
  </style>
</head>
<body>

  <header class="sb-header">
    <div class="container">
      <a href="index.php" class="sb-logo"><img src="assets/img/skillbridge-logo.png" alt="SkillBridge" class="logo-img"></a>
      <nav class="sb-nav">
        <a href="index.php">Accueil</a>
        <a href="browse_demandes.php" class="active">Parcourir les demandes</a>
        <a href="mes_propositions.php">Mes propositions</a>
        <a href="../chat/conversations.php">Mes Conversations</a>
      </nav>
      <div class="d-flex align-items-center gap-2">
        <span id="bellSlot" class="sb-bell-btn"></span>
        <a href="profil.php" class="sb-profile-chip" title="Mon Profil">
          <img src="<?= $navAvatarSrc ?>" alt="" class="avatar">
          <span><?= htmlspecialchars($navName) ?></span>
        </a>
        <a href="<?= $BASE ?>/controller/utilisateurcontroller.php?action=logout" class="sb-cta d-none d-md-inline-flex">
          <i class="bi bi-box-arrow-right"></i><span>Quitter</span>
        </a>
      </div>
    </div>
  </header>

  <main>
    <section class="page-bg">
      <div class="blob sage blob-1"></div>
      <div class="blob honey blob-2"></div>

      <div class="container">

        <div class="text-center mb-5" data-aos="fade-up" style="max-width:720px;margin:0 auto;">
          <span class="eyebrow honey"><span class="dot"></span> Faire une proposition</span>
          <h1 class="display-x mt-3 mb-2">Décrochez ce <span class="accent">projet</span>.</h1>
          <p class="lead-x mb-0">Présentez votre approche, votre tarif et convainquez le client de vous choisir.</p>
        </div>

        <div class="row g-4 justify-content-center">

          <!-- Demande summary -->
          <div class="col-lg-5" data-aos="fade-right">
            <div class="summary-card mb-3">
              <span class="eyebrow"><span class="dot"></span> Demande du client</span>
              <h3 class="ttl"><?= htmlspecialchars(html_entity_decode($demande['title'], ENT_QUOTES, 'UTF-8')) ?></h3>
              <div class="meta">
                <span class="chip sage"><i class="bi bi-cash-coin"></i> <?= number_format((float)$demande['price'], 0) ?> DT</span>
                <span class="chip honey"><i class="bi bi-calendar-event"></i> <?= htmlspecialchars(date('d/m/Y', strtotime($demande['deadline']))) ?></span>
              </div>
              <p class="desc"><?= nl2br(htmlspecialchars(html_entity_decode($demande['description'], ENT_QUOTES, 'UTF-8'))) ?></p>
              <div class="author-row">
                <img src="<?= $authorAvatar ?>" alt="" onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?= urlencode($authorName) ?>&background=1F5F4D&color=fff';">
                <div>
                  <div class="lbl">Posté par</div>
                  <div class="who"><?= htmlspecialchars($authorName) ?></div>
                </div>
              </div>
            </div>
            <a href="browse_demandes.php" class="btn-ghost"><i class="bi bi-arrow-left"></i> Retour aux demandes</a>
          </div>

          <!-- Form -->
          <div class="col-lg-7" data-aos="fade-left">
            <div class="auth-card">

              <?php if (!empty($errors)): ?>
                <div class="alert-danger">
                  <strong><i class="bi bi-exclamation-circle me-1"></i> Veuillez corriger :</strong>
                  <ul>
                    <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>

              <form method="POST" id="propForm" novalidate>
                <div class="mb-3">
                  <label for="freelancer_name" class="form-label">Votre nom (visible par le client)</label>
                  <input type="text" name="freelancer_name" id="freelancer_name" class="form-control" maxlength="120" minlength="3" required value="<?= htmlspecialchars($old['freelancer_name']) ?>" placeholder="ex : Mohamed Ben Ali">
                  <div class="form-text">Entre 3 et 120 caractères.</div>
                </div>

                <div class="mb-3">
                  <label for="price" class="form-label">Votre tarif proposé (DT)</label>
                  <input type="number" name="price" id="price" class="form-control" min="1" step="0.01" required value="<?= htmlspecialchars((string)$old['price']) ?>" placeholder="ex : 250">
                  <div class="form-text">Tarif minimum 1 DT.</div>
                </div>

                <div class="mb-4">
                  <label for="message" class="form-label">Votre message au client</label>
                  <textarea name="message" id="message" class="form-control" rows="5" required minlength="15" placeholder="Présentez votre approche, votre expérience, ce qui vous distingue..."><?= htmlspecialchars($old['message']) ?></textarea>
                  <div class="form-text">Minimum 15 caractères.</div>
                </div>

                <button type="submit" class="btn-sage">
                  <i class="bi bi-send-fill"></i> Envoyer ma proposition
                </button>
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
    if (typeof AOS !== 'undefined') AOS.init({ duration: 600, easing: 'ease-out-cubic', once: true });

    document.getElementById('propForm').addEventListener('submit', function(e) {
      let valid = true;
      const name = document.getElementById('freelancer_name');
      const price = document.getElementById('price');
      const message = document.getElementById('message');

      [name, price, message].forEach(f => f.classList.remove('is-invalid'));

      if (name.value.trim().length < 3 || name.value.trim().length > 120) { name.classList.add('is-invalid'); valid = false; }
      if (price.value === '' || isNaN(parseFloat(price.value)) || parseFloat(price.value) < 1) { price.classList.add('is-invalid'); valid = false; }
      if (message.value.trim().length < 15) { message.classList.add('is-invalid'); valid = false; }

      if (!valid) e.preventDefault();
    });
  </script>
</body>
</html>
