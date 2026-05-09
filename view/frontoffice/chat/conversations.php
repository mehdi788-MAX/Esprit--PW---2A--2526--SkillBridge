<?php
require_once __DIR__ . '/../../../controller/ChatController.php';

$chatController = new ChatController();

require_once __DIR__ . "/_auth.php";

$successMsg = '';
$errorMsg = '';

// Traitement suppression
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $chatController->deleteConversation($id);
    if ($result['success']) {
        $successMsg = "Conversation supprimée avec succès.";
    } else {
        $errorMsg = implode('<br>', $result['errors']);
    }
}

// Récupérer les conversations du client
$conversations = $chatController->listConversationsByUser($currentUserId)->fetchAll(PDO::FETCH_ASSOC);

$templateBase  = '../EasyFolio';
$BASE          = base_url();
$userNom       = trim((string)($_SESSION['user_nom'] ?? ''));
$userFirstName = trim(explode(' ', $userNom)[0] ?? '') ?: 'Profil';

// Load current user's photo for nav chip (matches dashboard look)
$photoStmt = $pdo->prepare("SELECT photo FROM utilisateurs WHERE id = :id");
$photoStmt->execute([':id' => $currentUserId]);
$myPhoto = (string)($photoStmt->fetchColumn() ?: '');

$navAvatarSrc = !empty($myPhoto)
    ? $templateBase . '/assets/img/profile/' . htmlspecialchars($myPhoto)
    : 'https://ui-avatars.com/api/?name=' . urlencode($userNom ?: 'SkillBridge') . '&background=1F5F4D&color=fff&bold=true&size=80';
$navAvatarFallback = 'https://ui-avatars.com/api/?name=' . urlencode($userNom ?: 'SkillBridge') . '&background=1F5F4D&color=fff&bold=true&size=80';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Mes Conversations — SkillBridge</title>
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

      h1, h2, h3, h4, h5 { font-family:'Manrope', sans-serif; font-weight:700; letter-spacing:-.022em; color: var(--ink); }
      .display-x { font-size: clamp(2rem, 3.6vw, 2.8rem); line-height:1.05; font-weight:800; letter-spacing:-.025em; }
      .display-l { font-size: clamp(1.6rem, 2.4vw, 2.1rem); line-height:1.1; letter-spacing:-.02em; font-weight:800; }
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

      /* Page background */
      .page-bg { position:relative; overflow:hidden; min-height: calc(100vh - 64px); padding: 56px 0 80px; }
      .blob { position:absolute; border-radius:50%; filter: blur(60px); opacity:.55; pointer-events:none; z-index:0; }
      .blob.sage { background: var(--sage-soft); }
      .blob.honey { background: var(--honey-soft); }
      .blob-1 { width:380px; height:380px; left:-120px; top:-100px; }
      .blob-2 { width:340px; height:340px; right:-100px; bottom: 100px; }
      .page-bg .container { position:relative; z-index:1; }

      /* Buttons */
      .btn-sage { display:inline-flex; align-items:center; gap:10px; background: var(--sage); color: var(--paper); padding:13px 22px; border-radius:12px; border:none; text-decoration:none; font-weight:700; font-size:.95rem; transition: all .2s ease; cursor:pointer; }
      .btn-sage:hover { background: var(--sage-d); transform: translateY(-2px); color: var(--paper); box-shadow:0 14px 28px -12px rgba(31,95,77,.4); }
      .btn-ghost { display:inline-flex; align-items:center; gap:8px; background: var(--paper); color: var(--ink); padding:11px 18px; border-radius:10px; border:1px solid var(--rule); text-decoration:none; font-weight:600; font-size:.9rem; transition: all .2s ease; }
      .btn-ghost:hover { border-color: var(--sage); color: var(--sage); }

      /* Conversation cards */
      .conv-grid { display:grid; grid-template-columns: repeat(3, 1fr); gap:18px; }
      @media (max-width: 991.98px) { .conv-grid { grid-template-columns: repeat(2, 1fr); } }
      @media (max-width: 575.98px)  { .conv-grid { grid-template-columns: 1fr; } }

      .conv-card { background: var(--paper); border:1px solid var(--rule); border-radius:20px; padding:22px; display:flex; flex-direction:column; transition: all .25s ease; box-shadow:0 1px 2px rgba(15,15,15,.02); }
      .conv-card:hover { transform: translateY(-3px); border-color: var(--sage); box-shadow:0 24px 44px -22px rgba(31,95,77,.18); }
      .conv-card .row-top { display:flex; align-items:center; gap:12px; margin-bottom:14px; }
      .conv-card .ava {
        width:50px; height:50px; border-radius:50%; flex-shrink:0;
        background: var(--sage); color: var(--honey);
        display:flex; align-items:center; justify-content:center;
        font-weight:800; font-size:1.05rem; letter-spacing:-.02em;
      }
      .conv-card .name { font-weight:700; color: var(--ink); font-size:1rem; line-height:1.2; }
      .conv-card .role { font-size:.8rem; color: var(--ink-mute); }
      .conv-card .preview {
        font-size:.9rem; color: var(--ink-mute); margin:0;
        line-height:1.5;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow:hidden;
        min-height:2.7rem;
      }
      .conv-card .preview.empty { font-style:italic; color: var(--ink-soft); }
      .conv-card .meta { font-size:.78rem; color: var(--ink-soft); margin-top:8px; display:flex; align-items:center; gap:6px; }

      .conv-actions { display:flex; gap:8px; margin-top:16px; padding-top:14px; border-top:1px solid var(--rule); }
      .conv-actions .btn-open {
        flex:1; display:inline-flex; align-items:center; justify-content:center; gap:8px;
        padding:10px 14px; border-radius:10px; background: var(--sage); color: var(--paper);
        text-decoration:none; font-weight:600; font-size:.88rem; transition: all .2s;
      }
      .conv-actions .btn-open:hover { background: var(--sage-d); color: var(--paper); }
      .conv-actions .btn-icon {
        width:40px; height:40px; flex-shrink:0; border-radius:10px;
        display:inline-flex; align-items:center; justify-content:center;
        text-decoration:none; transition: all .2s;
        border:1px solid var(--rule); background: var(--paper);
        color: var(--ink-mute); font-size: .98rem;
      }
      .conv-actions .btn-icon:hover { transform: translateY(-1px); }
      .conv-actions .btn-icon.edit:hover   { border-color: var(--honey-d); color:#92660A; background: var(--honey-soft); }
      .conv-actions .btn-icon.delete:hover { border-color:#DC2626; color:#991B1B; background:#FEF2F2; }

      /* Empty state */
      .empty-state { text-align:center; padding: 60px 30px; background: var(--paper); border:1px solid var(--rule); border-radius:24px; }
      .empty-state .ic { width:84px; height:84px; border-radius:24px; background: var(--sage-soft); color: var(--sage); display:inline-flex; align-items:center; justify-content:center; font-size:2.4rem; margin-bottom:16px; }
      .empty-state h4 { font-weight:800; font-size:1.4rem; margin-bottom:6px; }
      .empty-state p { color: var(--ink-mute); margin-bottom:22px; }

      /* Alerts */
      .sb-alert { border-radius:14px; padding:14px 16px; border:1px solid; margin-bottom:18px; display:flex; align-items:center; gap:12px; }
      .sb-alert.success { background: var(--sage-soft); border-color: rgba(31,95,77,.2); color: var(--sage-d); }
      .sb-alert.danger  { background: #FEF2F2; border-color: #FECACA; color:#991B1B; }
      .sb-alert .close { margin-left:auto; cursor:pointer; background:none; border:none; color:inherit; opacity:.6; font-size:1.1rem; }
      .sb-alert .close:hover { opacity:1; }

      /* Footer */
      .sb-footer { background: var(--ink); color: rgba(255,255,255,.65); padding:22px 0; font-size:.88rem; text-align:center; }
      .sb-footer strong { color: var(--paper); }

      /* legacy hooks */
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
      <a href="<?= $templateBase ?>/index.php">Accueil</a>
      <a href="conversations.php" class="active">Conversations</a>
      <?php if (($currentUserRole ?? '') === 'freelancer'): ?>
        <a href="<?= $templateBase ?>/browse_demandes.php">Demandes</a>
        <a href="<?= $templateBase ?>/mes_propositions.php">Mes propositions</a>
      <?php elseif (($currentUserRole ?? '') === 'client'): ?>
        <a href="<?= $templateBase ?>/mes_demandes.php">Mes demandes</a>
      <?php endif; ?>
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
    <div class="blob sage  blob-1"></div>
    <div class="blob honey blob-2"></div>

    <div class="container">

      <!-- Hero -->
      <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-5">
        <div>
          <span class="eyebrow"><span class="dot"></span> Messagerie</span>
          <h1 class="display-x mt-3 mb-2">Mes <span class="accent">conversations</span>.</h1>
          <p class="lead-x mb-0">Bienvenue, <strong style="color:var(--ink);"><?= htmlspecialchars($userNom ?: 'sur SkillBridge') ?></strong>. Reprenez vos discussions là où vous les avez laissées.</p>
        </div>
        <a href="new_conversation.php" class="btn-sage">
          <i class="bi bi-plus-circle"></i> Nouvelle conversation
        </a>
      </div>

      <?php if ($successMsg): ?>
        <div class="sb-alert success">
          <i class="bi bi-check-circle-fill"></i>
          <span><?= $successMsg ?></span>
          <button class="close" onclick="this.parentElement.style.display='none'">×</button>
        </div>
      <?php endif; ?>

      <?php if ($errorMsg): ?>
        <div class="sb-alert danger">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <span><?= $errorMsg ?></span>
          <button class="close" onclick="this.parentElement.style.display='none'">×</button>
        </div>
      <?php endif; ?>

      <?php if (empty($conversations)): ?>
        <div class="empty-state">
          <div class="ic"><i class="bi bi-chat-square-dots"></i></div>
          <h4>Aucune conversation pour l'instant</h4>
          <p>Démarrez votre première discussion avec un freelancer.</p>
          <a href="new_conversation.php" class="btn-sage">
            <i class="bi bi-plus-circle"></i> Démarrer une conversation
          </a>
        </div>
      <?php else: ?>
        <div class="conv-grid">
          <?php foreach ($conversations as $conv):
              $otherUser = ($conv['user1_id'] == $currentUserId)
                  ? $conv['user2_prenom'] . ' ' . $conv['user2_nom']
                  : $conv['user1_prenom'] . ' ' . $conv['user1_nom'];
              $initials  = strtoupper(mb_substr($otherUser, 0, 1, 'UTF-8'));
              $preview   = !empty($conv['dernier_message']) ? chat_message_preview($conv['dernier_message'], 120) : '';
              $created   = $conv['date_creation'] ?? '';
          ?>
            <article class="conv-card">
              <div class="row-top">
                <div class="ava"><?= htmlspecialchars($initials) ?></div>
                <div style="min-width:0;">
                  <div class="name"><?= htmlspecialchars($otherUser) ?></div>
                  <div class="role">Freelancer</div>
                </div>
              </div>

              <?php if ($preview): ?>
                <p class="preview">
                  <i class="bi bi-chat-text" style="color:var(--sage);"></i>
                  <?= htmlspecialchars($preview) ?>
                </p>
              <?php else: ?>
                <p class="preview empty">Aucun message encore.</p>
              <?php endif; ?>

              <?php if ($created): ?>
                <div class="meta"><i class="bi bi-clock"></i> <?= htmlspecialchars($created) ?></div>
              <?php endif; ?>

              <div class="conv-actions">
                <a href="chat.php?id=<?= $conv['id_conversation'] ?>" class="btn-open">
                  <i class="bi bi-chat-dots"></i> Ouvrir
                </a>
                <a href="edit_conversation.php?id=<?= $conv['id_conversation'] ?>" class="btn-icon edit" title="Modifier">
                  <i class="bi bi-pencil"></i>
                </a>
                <a href="conversations.php?action=delete&id=<?= $conv['id_conversation'] ?>"
                   class="btn-icon delete"
                   title="Supprimer"
                   onclick="return confirm('Supprimer cette conversation ?');">
                  <i class="bi bi-trash"></i>
                </a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

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
