<?php
require_once __DIR__ . '/../../../controller/ChatController.php';

$chatController = new ChatController();
require_once __DIR__ . "/_auth.php";

$errors = [];
$successMsg = '';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: conversations.php');
    exit;
}

$conversation = $chatController->getConversation($id);
if (!$conversation) {
    header('Location: conversations.php');
    exit;
}

$users = $chatController->getUsers();
$user1_id = $conversation['user1_id'];
$user2_id = $conversation['user2_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user1_id = isset($_POST['user1_id']) ? trim($_POST['user1_id']) : '';
    $user2_id = isset($_POST['user2_id']) ? trim($_POST['user2_id']) : '';

    $errors = $chatController->validateConversation($user1_id, $user2_id);

    if (empty($errors)) {
        $result = $chatController->updateConversation($id, $user1_id, $user2_id);
        if ($result['success']) {
            $successMsg = "Conversation modifiée avec succès.";
            $conversation = $chatController->getConversation($id);
        } else {
            $errors = $result['errors'];
        }
    }
}

$templateBase  = '../EasyFolio';
$BASE          = base_url();
$userNom       = trim((string)($_SESSION['user_nom'] ?? ''));
$userFirstName = trim(explode(' ', $userNom)[0] ?? '') ?: 'Profil';

// Load current user's photo for nav chip
$photoStmt = $pdo->prepare("SELECT photo FROM utilisateurs WHERE id = :id");
$photoStmt->execute([':id' => $currentUserId]);
$myPhoto = (string)($photoStmt->fetchColumn() ?: '');

$navAvatarFallback = 'https://ui-avatars.com/api/?name=' . urlencode($userNom ?: 'SkillBridge') . '&background=1F5F4D&color=fff&bold=true&size=80';
$navAvatarSrc = !empty($myPhoto)
    ? $templateBase . '/assets/img/profile/' . htmlspecialchars($myPhoto)
    : $navAvatarFallback;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Modifier conversation — SkillBridge</title>
    <link href="<?= $templateBase ?>/assets/img/favicon.png" rel="icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= $templateBase ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $templateBase ?>/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

    <style>
      :root {
        --bg:#F7F4ED; --paper:#FFFFFF; --ink:#0F0F0F; --ink-2:#2A2A2A;
        --ink-mute:#5C5C5C; --ink-soft:#A3A3A3; --rule:#E8E2D5;
        --sage:#1F5F4D; --sage-d:#134438; --sage-soft:#E8F0EC;
        --honey:#F5C842; --honey-d:#E0B033; --honey-soft:#FBF1D0;
      }
      *, *::before, *::after { box-sizing: border-box; }
      body { font-family:'Manrope', system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--ink); letter-spacing:-.005em; -webkit-font-smoothing:antialiased; margin:0; }
      ::selection { background: var(--sage); color: var(--honey); }
      h1, h2, h3, h4 { font-family:'Manrope', sans-serif; font-weight:700; letter-spacing:-.022em; color: var(--ink); }
      .display-x { font-size: clamp(1.8rem, 3vw, 2.4rem); line-height:1.05; font-weight:800; letter-spacing:-.025em; }
      .lead-x    { font-size:1rem; line-height:1.55; color: var(--ink-mute); font-weight:400; }
      .accent    { font-style: italic; font-weight:700; color: var(--sage); }

      .eyebrow { display:inline-flex; align-items:center; gap:8px; font-size:.8rem; font-weight:600; color: var(--sage); padding: 6px 12px; background: var(--sage-soft); border-radius:999px; }
      .eyebrow .dot { width:6px; height:6px; border-radius:50%; background: var(--sage); }
      .eyebrow.honey { color:#92660A; background: var(--honey-soft); }
      .eyebrow.honey .dot { background: var(--honey-d); }

      /* Header */
      .sb-header { position:sticky; top:0; z-index:100; background: rgba(247,244,237,.85); backdrop-filter: blur(14px); border-bottom:1px solid var(--rule); }
      .sb-header .container { display:flex; align-items:center; justify-content:space-between; padding:14px 0; }
      .sb-logo { display:inline-flex; align-items:center; text-decoration:none; color: var(--ink); }
      .sb-logo .logo-img { height:38px; width:auto; display:block; }
      .sb-nav { display:flex; align-items:center; gap:28px; }
      .sb-nav a { color: var(--ink-mute); text-decoration:none; font-weight:500; font-size:.92rem; transition: color .15s; }
      .sb-nav a:hover, .sb-nav a.active { color: var(--ink); }
      .sb-nav a.active { color: var(--sage); }
      .sb-cta { display:inline-flex; align-items:center; gap:8px; background: var(--ink); color: var(--bg); padding:10px 20px; border-radius:999px; text-decoration:none; font-weight:600; font-size:.92rem; transition: all .2s ease; }
      .sb-cta:hover { background: var(--sage); color: var(--paper); transform: translateY(-1px); }
      .sb-bell-btn { width:42px; height:42px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; background: transparent; color: var(--ink); position:relative; transition: all .2s; }
      .sb-bell-btn:hover { background: var(--paper); }
      .sb-profile-chip { display:inline-flex; align-items:center; gap:8px; padding:4px 14px 4px 4px; border-radius:999px; background: var(--paper); border:1px solid var(--rule); color: var(--ink); text-decoration:none; font-weight:600; font-size:.9rem; transition: all .2s; }
      .sb-profile-chip:hover { border-color: var(--sage); transform: translateY(-1px); }
      .sb-profile-chip .avatar { width:30px; height:30px; border-radius:50%; object-fit:cover; }
      @media (max-width: 991.98px) { .sb-nav { display:none; } }

      .page-bg { position:relative; overflow:hidden; min-height: calc(100vh - 64px); padding: 56px 0 80px; }
      .blob { position:absolute; border-radius:50%; filter: blur(60px); opacity:.55; pointer-events:none; z-index:0; }
      .blob.sage { background: var(--sage-soft); }
      .blob.honey { background: var(--honey-soft); }
      .blob-1 { width:380px; height:380px; left:-120px; top:-100px; }
      .blob-2 { width:340px; height:340px; right:-120px; bottom:-80px; }
      .page-bg .container { position:relative; z-index:1; }

      .auth-card { background: var(--paper); border:1px solid var(--rule); border-radius:24px; padding:36px 34px; box-shadow:0 30px 60px -25px rgba(31,95,77,.18); }

      .form-label { font-weight:600; color: var(--ink-2); font-size:.88rem; margin-bottom:6px; display:block; }
      .form-control, .form-select { width:100%; border-radius:12px; border:1px solid var(--rule); padding:13px 14px; font-size:.96rem; background: var(--paper); color: var(--ink); transition: border-color .18s, box-shadow .18s; font-family:'Manrope', sans-serif; }
      .form-control:focus, .form-select:focus { outline:none; border-color: var(--sage); box-shadow:0 0 0 4px rgba(31,95,77,.12); }
      .form-select { padding:14px 16px; }

      .btn-sage { display:flex; align-items:center; justify-content:center; gap:10px; width:100%; padding:14px 22px; border-radius:12px; border:none; background: var(--sage); color: var(--paper) !important; font-weight:700; font-size:1rem; transition: all .2s ease; text-decoration:none; cursor:pointer; }
      .btn-sage:hover { background: var(--sage-d); transform: translateY(-2px); box-shadow:0 14px 28px -12px rgba(31,95,77,.4); }
      .btn-ghost { display:flex; align-items:center; justify-content:center; gap:8px; width:100%; padding:13px 18px; border-radius:12px; background: var(--paper); color: var(--ink-mute); border:1px solid var(--rule); text-decoration:none; font-weight:600; font-size:.95rem; transition: all .2s ease; }
      .btn-ghost:hover { border-color: var(--ink); color: var(--ink); }
      .btn-back { display:inline-flex; align-items:center; gap:6px; color: var(--ink-mute); text-decoration:none; font-weight:600; font-size:.92rem; transition: color .15s; }
      .btn-back:hover { color: var(--sage); }

      .sb-alert { border-radius:14px; padding:14px 16px; border:1px solid; margin-bottom:18px; }
      .sb-alert.success { background: var(--sage-soft); border-color: rgba(31,95,77,.2); color: var(--sage-d); }
      .sb-alert.danger  { background: #FEF2F2; border-color: #FECACA; color:#991B1B; }
      .sb-alert ul { margin:6px 0 0; padding-left:18px; }

      .sb-footer { background: var(--ink); color: rgba(255,255,255,.65); padding:22px 0; font-size:.88rem; text-align:center; }
      .sb-footer strong { color: var(--paper); }

      .navmenu a.active { color: var(--sage) !important; }
    </style>
</head>
<body>

<header class="sb-header">
  <div class="container">
    <a href="<?= $templateBase ?>/index.php" class="sb-logo">
      <img src="<?= $templateBase ?>/assets/img/skillbridge-logo.png" alt="SkillBridge" class="logo-img" loading="eager">
    </a>
    <nav class="sb-nav">
      <?= frontoffice_main_nav('conversations', $templateBase, '.') ?>
    </nav>
    <div class="d-flex align-items-center gap-2">
      <span id="bellSlot" class="sb-bell-btn" style="display:inline-flex;"></span>
      <a href="<?= $templateBase ?>/profil.php" class="sb-profile-chip" title="Mon Profil">
        <img src="<?= $navAvatarSrc ?>" alt="" class="avatar"
             onerror="this.onerror=null;this.src='<?= htmlspecialchars($navAvatarFallback) ?>';">
        <span><?= htmlspecialchars($userFirstName) ?></span>
      </a>
      <a href="<?= base_url() ?>/controller/utilisateurcontroller.php?action=logout" class="sb-cta d-none d-md-inline-flex">
        <i class="bi bi-box-arrow-right"></i><span>Quitter</span>
      </a>
    </div>
  </div>
</header>

<main>
  <section class="page-bg">
    <div class="blob honey blob-1"></div>
    <div class="blob sage  blob-2"></div>

    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8 col-xl-7">

          <div class="mb-3">
            <a href="conversations.php" class="btn-back">
              <i class="bi bi-arrow-left"></i> Retour aux conversations
            </a>
          </div>

          <div class="text-center mb-4">
            <span class="eyebrow honey"><span class="dot"></span> Édition · #<?= $id ?></span>
            <h1 class="display-x mt-3 mb-2">Modifier la <span class="accent">conversation</span>.</h1>
            <p class="lead-x mb-0">Mettez à jour les participants de cette conversation.</p>
          </div>

          <div class="auth-card">

            <?php if ($successMsg): ?>
              <div class="sb-alert success">
                <strong><i class="bi bi-check-circle-fill"></i></strong> <?= htmlspecialchars($successMsg) ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
              <div class="sb-alert danger">
                <strong><i class="bi bi-exclamation-triangle-fill"></i> Erreur(s) de validation :</strong>
                <ul>
                  <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <form method="POST" action="edit_conversation.php?id=<?= $id ?>" novalidate>
              <div class="mb-3">
                <label for="user1_id" class="form-label">Participant 1 <span style="color:#DC2626;">*</span></label>
                <select class="form-select" id="user1_id" name="user1_id">
                  <option value="">-- Choisir --</option>
                  <?php foreach ($users as $user): ?>
                    <option value="<?= htmlspecialchars($user['id']) ?>" <?= ($user1_id == $user['id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom'] . ' (' . $user['role'] . ')') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="mb-4">
                <label for="user2_id" class="form-label">Participant 2 <span style="color:#DC2626;">*</span></label>
                <select class="form-select" id="user2_id" name="user2_id">
                  <option value="">-- Choisir --</option>
                  <?php foreach ($users as $user): ?>
                    <option value="<?= htmlspecialchars($user['id']) ?>" <?= ($user2_id == $user['id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom'] . ' (' . $user['role'] . ')') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <button type="submit" class="btn-sage mb-2">
                <i class="bi bi-check-circle"></i> Enregistrer les modifications
              </button>
              <a href="conversations.php" class="btn-ghost">
                <i class="bi bi-x-lg"></i> Annuler
              </a>
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

<script src="<?= $templateBase ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../shared/chatbus.js"></script>
<script>
  ChatBus.init({ apiBase: '../../../api/chat.php', user: <?= (int)$currentUserId ?>, conv: 0 });
  ChatBus.mountBell('#bellSlot');
</script>
</body>
</html>
