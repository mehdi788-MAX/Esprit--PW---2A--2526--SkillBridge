<?php
require_once __DIR__ . '/../../../controller/ChatController.php';

$chatController = new ChatController();

require_once __DIR__ . "/_auth.php";

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

$otherUser = ($conversation['user1_id'] == $currentUserId)
    ? $conversation['user2_prenom'] . ' ' . $conversation['user2_nom']
    : $conversation['user1_prenom'] . ' ' . $conversation['user1_nom'];

$otherUserId = ($conversation['user1_id'] == $currentUserId)
    ? (int)$conversation['user2_id']
    : (int)$conversation['user1_id'];

// Other user's photo (for the chat header avatar)
$otherPhotoStmt = $pdo->prepare("SELECT photo FROM utilisateurs WHERE id = :id");
$otherPhotoStmt->execute([':id' => $otherUserId]);
$otherPhoto = (string)($otherPhotoStmt->fetchColumn() ?: '');

$chatController->markMessagesAsSeen($id, $currentUserId);

$messages    = $chatController->listMessagesByConversation($id)->fetchAll(PDO::FETCH_ASSOC);
$lastMsgId   = !empty($messages) ? max(array_map(fn($m) => (int)$m['id_message'], $messages)) : 0;
$templateBase = '../EasyFolio';

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

$otherInitial  = strtoupper(mb_substr($otherUser, 0, 1, 'UTF-8'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Chat avec <?= htmlspecialchars($otherUser) ?> — SkillBridge</title>
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

      .page-bg { background: var(--bg); padding: 32px 0 48px; min-height: calc(100vh - 64px); }
      .btn-back { display:inline-flex; align-items:center; gap:6px; color: var(--ink-mute); text-decoration:none; font-weight:600; font-size:.92rem; transition: color .15s; }
      .btn-back:hover { color: var(--sage); }

      /* Chat shell */
      .chat-shell {
        background: var(--paper); border:1px solid var(--rule); border-radius:22px;
        box-shadow: 0 30px 60px -25px rgba(31,95,77,.18);
        overflow:hidden; display:flex; flex-direction:column;
        max-height: 78vh; min-height: 540px;
      }
      .chat-head {
        display:flex; align-items:center; gap:14px;
        padding:18px 22px; background: var(--sage); color: var(--paper);
        border-bottom:1px solid var(--rule);
      }
      .chat-head .ava {
        width:46px; height:46px; border-radius:50%;
        background: var(--honey); color: var(--ink);
        display:flex; align-items:center; justify-content:center;
        font-weight:800; font-size:1rem; flex-shrink:0;
        overflow:hidden; border: 2px solid rgba(255,255,255,.2);
        box-shadow: 0 4px 12px rgba(0,0,0,.18);
      }
      .chat-head .ava img { width:100%; height:100%; object-fit:cover; display:block; }
      .chat-head .name { font-weight:700; color: var(--paper); margin:0; font-size:1.02rem; line-height:1.2; }
      .chat-head .role { color: rgba(255,255,255,.7); font-size:.78rem; }
      .chat-head .pulse {
        width:8px; height:8px; border-radius:50%; background: var(--honey);
        box-shadow: 0 0 0 3px rgba(245,200,66,.3);
        animation: pulse 2s ease-out infinite;
      }
      @keyframes pulse { 0%,100% { box-shadow: 0 0 0 3px rgba(245,200,66,.3); } 50% { box-shadow: 0 0 0 7px transparent; } }

      .chat-container {
        flex:1; overflow-y:auto;
        padding: 22px;
        background: linear-gradient(180deg, #FAFAF6 0%, var(--paper) 100%);
      }
      .chat-container::-webkit-scrollbar { width: 8px; }
      .chat-container::-webkit-scrollbar-thumb { background: var(--rule); border-radius:4px; }
      .chat-container::-webkit-scrollbar-thumb:hover { background: var(--ink-soft); }

      /* Bubbles */
      .msg-bubble { max-width: 70%; padding: 12px 16px; border-radius: 18px; margin-bottom: 8px; word-wrap: break-word; line-height:1.5; font-size: .94rem; box-shadow: 0 1px 2px rgba(15,15,15,.04); }
      .msg-sent {
        background: var(--sage); color: var(--paper);
        margin-left: auto; border-bottom-right-radius: 6px;
      }
      .msg-received {
        background: var(--paper); color: var(--ink);
        margin-right: auto; border-bottom-left-radius: 6px;
        border:1px solid var(--rule);
      }
      .msg-meta { font-size: 0.72rem; opacity: 0.78; margin-top: 4px; font-weight:500; }
      .msg-bubble.optimistic { opacity: 0.55; }
      .seen-tick i { color: inherit; }
      .moderation-warning { font-size: 0.72rem; color: #92660A; background: var(--honey-soft); border-radius: 6px; padding: 6px 9px; margin-top: 6px; }
      .typing-indicator { color: var(--ink-mute); font-style: italic; font-size: 0.85rem; min-height: 22px; padding: 4px 18px 0; }
      .typing-dots span { display:inline-block; width:6px; height:6px; border-radius:50%; background: var(--sage); margin:0 1px; animation: tdots 1.2s infinite; }
      .typing-dots span:nth-child(2){ animation-delay:.2s }
      .typing-dots span:nth-child(3){ animation-delay:.4s }
      @keyframes tdots { 0%,80%,100% { transform: translateY(0); opacity:.4 } 40% { transform: translateY(-4px); opacity:1 } }

      .chat-image { cursor: zoom-in; }
      .file-card { display:flex; align-items:center; gap:12px; min-width:240px; max-width:340px; }
      .file-card .file-name { font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
      .file-card .file-size { font-size:11px; opacity:.85; }

      .img-lightbox { display:none; position:fixed; inset:0; background:rgba(0,0,0,.85); z-index:4000; align-items:center; justify-content:center; cursor:zoom-out; }
      .img-lightbox.open { display:flex; }
      .img-lightbox img { max-width:92vw; max-height:92vh; box-shadow:0 8px 30px rgba(0,0,0,.5); border-radius:6px; }
      .upload-progress { font-size:12px; color: var(--ink-mute); padding:6px 8px; }

      /* Composer */
      .chat-foot {
        background: var(--paper);
        border-top: 1px solid var(--rule);
        padding: 14px 18px;
      }
      .composer-row {
        display:flex; align-items:flex-end; gap:8px;
        background: var(--paper);
        border:1.5px solid var(--rule);
        border-radius: 16px;
        padding: 6px 8px 6px 14px;
        transition: all .2s ease;
      }
      .composer-row:focus-within {
        border-color: var(--sage);
        background: var(--paper);
        box-shadow: 0 0 0 4px rgba(31,95,77,.18), 0 8px 18px -10px rgba(31,95,77,.25);
      }
      .composer-row textarea {
        flex:1; border:none !important; background: transparent !important;
        resize:none; outline:none !important; box-shadow:none !important;
        font-family:'Manrope', sans-serif;
        font-size: .96rem; color: var(--ink); padding: 8px 0;
        max-height: 140px; line-height: 1.5;
        -webkit-tap-highlight-color: transparent;
      }
      .composer-row textarea:focus { outline:none !important; box-shadow:none !important; }
      .composer-row textarea::placeholder { color: var(--ink-soft); }
      .composer-icon-btn {
        width:38px; height:38px; border-radius:10px; border:none;
        background: transparent; color: var(--ink-mute);
        display:inline-flex; align-items:center; justify-content:center;
        font-size:1.05rem; cursor:pointer; transition: all .15s ease;
        flex-shrink:0;
      }
      .composer-icon-btn:hover { background: var(--paper); color: var(--sage); }
      .send-btn {
        background: var(--sage); color: var(--paper);
        border:none; width:42px; height:42px; border-radius:12px;
        display:inline-flex; align-items:center; justify-content:center;
        font-size:1rem; cursor:pointer; transition: all .15s ease;
        flex-shrink:0;
      }
      .send-btn:hover { background: var(--sage-d); transform: translateY(-1px); }

      .composer-foot { display:flex; justify-content:space-between; gap:14px; padding: 6px 4px 0; flex-wrap:wrap; }
      .composer-foot small { font-size:.78rem; color: var(--ink-soft); }
      .composer-foot small.text-warning { color: #92660A; }
      #charError, #badWordError { color: #DC2626; font-size: .82rem; margin-top: 4px; padding: 0 4px; }

      /* Alert slot */
      .sb-alert { border-radius:14px; padding:14px 16px; border:1px solid; margin-bottom:18px; display:flex; align-items:flex-start; gap:12px; }
      .sb-alert.success { background: var(--sage-soft); border-color: rgba(31,95,77,.2); color: var(--sage-d); }
      .sb-alert.danger  { background: #FEF2F2; border-color: #FECACA; color:#991B1B; }
      .alert { border-radius:14px !important; padding:14px 16px !important; border:1px solid !important; }
      .alert-success { background: var(--sage-soft) !important; border-color: rgba(31,95,77,.2) !important; color: var(--sage-d) !important; }
      .alert-danger  { background: #FEF2F2 !important; border-color: #FECACA !important; color: #991B1B !important; }

      /* Empty state */
      .empty-chat { text-align:center; padding: 60px 30px; color: var(--ink-soft); }
      .empty-chat .ic { width:80px; height:80px; border-radius:24px; background: var(--sage-soft); color: var(--sage); display:inline-flex; align-items:center; justify-content:center; font-size:2.2rem; margin-bottom:14px; }
      .empty-chat h5 { color: var(--ink); font-weight:700; margin:0 0 4px; }
      .empty-chat p { margin:0; }

      /* Footer */
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
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-9">

          <div class="mb-3">
            <a href="conversations.php" class="btn-back">
              <i class="bi bi-arrow-left"></i> Retour aux conversations
            </a>
          </div>

          <div id="alertSlot"></div>

          <div class="chat-shell">
            <div class="chat-head">
              <div class="ava">
                <?php if (!empty($otherPhoto)): ?>
                  <img src="<?= $templateBase ?>/assets/img/profile/<?= htmlspecialchars($otherPhoto) ?>"
                       alt="<?= htmlspecialchars($otherUser) ?>"
                       onerror="this.onerror=null; this.replaceWith(Object.assign(document.createElement('span'),{textContent:'<?= htmlspecialchars($otherInitial) ?>'}));">
                <?php else: ?>
                  <span><?= htmlspecialchars($otherInitial) ?></span>
                <?php endif; ?>
              </div>
              <div style="flex:1; min-width:0;">
                <div class="name"><?= htmlspecialchars($otherUser) ?></div>
                <div class="role">Freelancer</div>
              </div>
              <span class="pulse" title="En ligne"></span>
            </div>

            <div class="chat-container" id="chatContainer">
              <?php if (empty($messages)): ?>
                <div class="empty-chat" id="emptyState">
                  <div class="ic"><i class="bi bi-chat-dots"></i></div>
                  <h5>Commencez la conversation</h5>
                  <p>Envoyez votre premier message à <?= htmlspecialchars($otherUser) ?>.</p>
                </div>
              <?php else: ?>
                <?php foreach ($messages as $msg):
                    $isSent = ($msg['sender_id'] == $currentUserId);
                    $meta = ($msg['type'] === 'image' || $msg['type'] === 'file') ? json_decode($msg['contenu'], true) : null;
                    $tickHtml = $isSent
                        ? '<span class="seen-tick">' . ($msg['is_seen'] ? '<i class="bi bi-check2-all"></i>' : '<i class="bi bi-check2"></i>') . '</span>'
                        : '';
                    $metaLine = htmlspecialchars($msg['sender_prenom']) . ' · ' . date('H:i', strtotime($msg['date_envoi'])) . ' ' . $tickHtml;
                ?>
                  <div class="d-flex <?= $isSent ? 'justify-content-end' : 'justify-content-start' ?> mb-2"
                       data-msg-id="<?= (int)$msg['id_message'] ?>">
                    <?php if ($msg['type'] === 'image' && is_array($meta)): ?>
                      <div class="msg-bubble <?= $isSent ? 'msg-sent' : 'msg-received' ?>" style="padding:4px;">
                        <img class="chat-image" src="../../../<?= htmlspecialchars($meta['url'] ?? '') ?>" alt=""
                             style="max-width:280px; max-height:280px; border-radius:14px; display:block;">
                        <div class="msg-meta" style="padding:6px 8px 2px;"><?= $metaLine ?></div>
                      </div>
                    <?php elseif ($msg['type'] === 'file' && is_array($meta)):
                        $sizeKB = round(($meta['size'] ?? 0) / 1024, 1);
                        $sizeStr = $sizeKB > 1024 ? round($sizeKB / 1024, 1) . ' MB' : $sizeKB . ' KB';
                    ?>
                      <div class="msg-bubble <?= $isSent ? 'msg-sent' : 'msg-received' ?>">
                        <a class="file-card" href="../../../<?= htmlspecialchars($meta['url'] ?? '') ?>" target="_blank" download
                           style="color:inherit;text-decoration:none;">
                          <i class="bi bi-file-earmark" style="font-size:1.8rem;"></i>
                          <div style="flex:1;min-width:0;">
                            <div class="file-name"><?= htmlspecialchars($meta['name'] ?? 'Fichier') ?></div>
                            <div class="file-size"><?= $sizeStr ?> · Cliquer pour télécharger</div>
                          </div>
                          <i class="bi bi-download"></i>
                        </a>
                        <div class="msg-meta"><?= $metaLine ?></div>
                      </div>
                    <?php else: ?>
                      <div class="msg-bubble <?= $isSent ? 'msg-sent' : 'msg-received' ?>">
                        <div class="msg-body"><?= nl2br(htmlspecialchars($msg['contenu'])) ?></div>
                        <div class="msg-meta"><?= $metaLine ?></div>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

            <div class="typing-indicator" id="typingIndicator"></div>

            <div class="chat-foot">
              <form id="sendForm" novalidate>
                <div class="composer-row">
                  <textarea id="contenu" class="form-control" name="contenu" rows="1" placeholder="Tapez votre message..."></textarea>
                  <input type="file" id="photoInput" accept="image/*" hidden>
                  <input type="file" id="fileInput" hidden>
                  <button type="button" class="composer-icon-btn" id="photoBtn" title="Envoyer une photo">
                    <i class="bi bi-image"></i>
                  </button>
                  <button type="button" class="composer-icon-btn" id="fileBtn" title="Envoyer un fichier">
                    <i class="bi bi-paperclip"></i>
                  </button>
                  <button type="button" class="composer-icon-btn" data-emoji-target="#contenu" title="Émojis">
                    <i class="bi bi-emoji-smile"></i>
                  </button>
                  <button type="submit" class="send-btn" title="Envoyer">
                    <i class="bi bi-send-fill"></i>
                  </button>
                </div>
                <div class="composer-foot">
                  <small id="moderationHint" class="text-warning"></small>
                  <small id="count">0 / 10</small>
                </div>
                <div id="charError" style="display:none;">
                  <i class="bi bi-exclamation-circle"></i> Maximum 10 caractères atteint.
                </div>
                <div id="badWordError" style="display:none;">
                  <i class="bi bi-ban"></i> Votre message contient un mot inapproprié.
                </div>
                <div id="uploadHint" class="upload-progress" style="display:none;"></div>
              </form>
            </div>

          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<div class="img-lightbox" id="imageLightbox"><img alt=""></div>

<footer class="sb-footer">
  © <?= date('Y') ?> <strong>SkillBridge</strong> — Tous droits réservés.
</footer>

<script src="<?= $templateBase ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../shared/chatbus.js"></script>
<script>
(function () {
    const CURRENT_USER = <?= (int)$currentUserId ?>;
    const CONV_ID      = <?= (int)$id ?>;
    const SINCE_MSG    = <?= (int)$lastMsgId ?>;

    const chatContainer  = document.getElementById('chatContainer');
    const typingEl       = document.getElementById('typingIndicator');
    const alertSlot      = document.getElementById('alertSlot');
    const moderationHint = document.getElementById('moderationHint');
    const sendForm       = document.getElementById('sendForm');
    const contenuEl      = document.getElementById('contenu');
    const counterEl      = document.getElementById('count');
    const empty          = document.getElementById('emptyState');

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
    function flash(html, kind) {
        kind = kind || 'success';
        alertSlot.innerHTML = '<div class="alert alert-' + kind + ' alert-dismissible fade show" role="alert">' + html
            + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    function scrollBottom() { chatContainer.scrollTop = chatContainer.scrollHeight; }
    function fmtTime(dt) {
        try {
            const d = new Date(String(dt).replace(' ', 'T'));
            return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
        } catch (e) { return ''; }
    }

    function fmtSize(bytes) {
        bytes = parseInt(bytes, 10) || 0;
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function renderImageBubble(msg) {
        let meta = {}; try { meta = JSON.parse(msg.contenu); } catch (e) {}
        const isMine = msg.is_mine || (msg.sender_id === CURRENT_USER);
        const wrap = document.createElement('div');
        wrap.className = 'd-flex mb-2 ' + (isMine ? 'justify-content-end' : 'justify-content-start');
        wrap.setAttribute('data-msg-id', msg.id_message);
        const seen = isMine
            ? '<span class="seen-tick">' + (msg.is_seen ? '<i class="bi bi-check2-all"></i>' : '<i class="bi bi-check2"></i>') + '</span>'
            : '';
        wrap.innerHTML = ''
            + '<div class="msg-bubble ' + (isMine ? 'msg-sent' : 'msg-received') + '" style="padding:4px;">'
            + '  <img class="chat-image" src="../../../' + escapeHtml(meta.url || '') + '" alt="" '
            + '       style="max-width:280px;max-height:280px;border-radius:14px;display:block;">'
            + '  <div class="msg-meta" style="padding:6px 8px 2px;">'
            +    escapeHtml(msg.sender_prenom || '') + ' · ' + fmtTime(msg.date_envoi || '') + ' ' + seen
            + '  </div>'
            + '</div>';
        return wrap;
    }

    function renderFileBubble(msg) {
        let meta = {}; try { meta = JSON.parse(msg.contenu); } catch (e) {}
        const isMine = msg.is_mine || (msg.sender_id === CURRENT_USER);
        const wrap = document.createElement('div');
        wrap.className = 'd-flex mb-2 ' + (isMine ? 'justify-content-end' : 'justify-content-start');
        wrap.setAttribute('data-msg-id', msg.id_message);
        const seen = isMine
            ? '<span class="seen-tick">' + (msg.is_seen ? '<i class="bi bi-check2-all"></i>' : '<i class="bi bi-check2"></i>') + '</span>'
            : '';
        wrap.innerHTML = ''
            + '<div class="msg-bubble ' + (isMine ? 'msg-sent' : 'msg-received') + '">'
            + '  <a class="file-card" href="../../../' + escapeHtml(meta.url || '') + '" target="_blank" download '
            + '     style="color:inherit;text-decoration:none;">'
            + '    <i class="bi bi-file-earmark" style="font-size:1.8rem;"></i>'
            + '    <div style="flex:1;min-width:0;">'
            + '      <div class="file-name">' + escapeHtml(meta.name || 'Fichier') + '</div>'
            + '      <div class="file-size">' + fmtSize(meta.size) + ' · Cliquer pour télécharger</div>'
            + '    </div>'
            + '    <i class="bi bi-download"></i>'
            + '  </a>'
            + '  <div class="msg-meta">'
            +    escapeHtml(msg.sender_prenom || '') + ' · ' + fmtTime(msg.date_envoi || '') + ' ' + seen
            + '  </div>'
            + '</div>';
        return wrap;
    }

    function renderBubble(msg) {
        if (msg.type === 'image') return renderImageBubble(msg);
        if (msg.type === 'file')  return renderFileBubble(msg);
        return renderTextBubble(msg);
    }

    function renderTextBubble(msg) {
        const isMine = msg.is_mine || (msg.sender_id === CURRENT_USER);
        const wrap = document.createElement('div');
        wrap.className = 'd-flex mb-2 ' + (isMine ? 'justify-content-end' : 'justify-content-start');
        wrap.setAttribute('data-msg-id', msg.id_message);
        const seen = isMine
            ? '<span class="seen-tick">' + (msg.is_seen ? '<i class="bi bi-check2-all"></i>' : '<i class="bi bi-check2"></i>') + '</span>'
            : '';
        const mod = (msg.moderation && msg.moderation.flagged)
            ? '<div class="moderation-warning"><i class="bi bi-shield-exclamation"></i> ' + escapeHtml(msg.moderation.message) + '</div>'
            : '';
        wrap.innerHTML = ''
            + '<div class="msg-bubble ' + (isMine ? 'msg-sent' : 'msg-received') + '">'
            + '<div class="msg-body">' + escapeHtml(msg.contenu).replace(/\n/g, '<br>') + '</div>'
            + mod
            + '<div class="msg-meta">' + escapeHtml(msg.sender_prenom || '') + ' · ' + fmtTime(msg.date_envoi) + ' ' + seen + '</div>'
            + '</div>';
        return wrap;
    }

    function appendMessage(msg) {
        if (empty) empty.remove();
        if (chatContainer.querySelector('[data-msg-id="' + msg.id_message + '"]')) return;
        chatContainer.appendChild(renderBubble(msg));
        scrollBottom();
    }

    ChatBus.init({
        apiBase: '../../../api/chat.php',
        user: CURRENT_USER,
        conv: CONV_ID,
    });
    ChatBus.mountBell('#bellSlot');
    ChatBus.installEmojiPicker();
    ChatBus.installMessageMenu({ container: '#chatContainer', currentUser: CURRENT_USER });
    ChatBus.installReactionRenderer({ container: '#chatContainer', currentUser: CURRENT_USER });
    ChatBus.installMessagesReconciler({
        container: '#chatContainer',
        render: renderBubble,
        onAppend: function () { scrollBottom(); },
    });
    ChatBus.installComposer({ textarea: '#contenu', form: '#sendForm' });

    ChatBus.on('message', appendMessage);

    ChatBus.on('typing', function (userIds) {
        if (userIds && userIds.length > 0) {
            typingEl.innerHTML = '<span class="typing-dots"><span></span><span></span><span></span></span> '
              + '<span class="ms-1">en train d\'écrire</span>';
        } else {
            typingEl.innerHTML = '';
        }
    });

    ChatBus.on('seenUpdate', function (maxSeenId) {
        const ticks = chatContainer.querySelectorAll('.msg-bubble.msg-sent .seen-tick');
        ticks.forEach(function (el) {
            const wrap = el.closest('[data-msg-id]');
            const id = parseInt(wrap.getAttribute('data-msg-id'), 10);
            if (id && id <= maxSeenId) el.innerHTML = '<i class="bi bi-check2-all"></i>';
        });
    });

    ChatBus.seen();
    document.addEventListener('visibilitychange', function () { if (!document.hidden) ChatBus.seen(); });

    sendForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const text = (contenuEl.value || '').trim();
        moderationHint.textContent = '';
        if (!text) return;
        if (containsBadWord(text)) {
            flash('<i class="bi bi-ban"></i> Votre message contient un mot inapproprié. Veuillez le modifier.', 'danger');
            return;
        }
        if (text.length > MAX_CHARS) {
            flash('Message trop long (max ' + MAX_CHARS + ' caractères).', 'danger');
            return;
        }
        const optId = 'opt-' + Date.now();
        const opt = renderTextBubble({
            id_message: optId, sender_id: CURRENT_USER, contenu: text,
            date_envoi: new Date().toISOString().slice(0, 19).replace('T', ' '),
            sender_prenom: 'Vous', is_seen: 0, is_mine: true, type: 'text', moderation: null,
        });
        opt.querySelector('.msg-bubble').classList.add('optimistic');
        chatContainer.appendChild(opt);
        scrollBottom();
        contenuEl.value = '';
        counterEl.textContent = '0 / ' + MAX_CHARS;

        const r = await ChatBus.send(text);
        if (!r.ok || !r.data || !r.data.success) {
            opt.remove();
            flash((r.data && r.data.errors) ? r.data.errors.join(', ') : 'Échec de l\'envoi.', 'danger');
            return;
        }
        opt.setAttribute('data-msg-id', r.data.id);
        opt.querySelector('.msg-bubble').classList.remove('optimistic');
        if (r.data.moderation && r.data.moderation.flagged) {
            moderationHint.innerHTML = '<i class="bi bi-shield-exclamation"></i> ' + escapeHtml(r.data.moderation.message);
        }
    });


    // Liste de mots interdits
    var BAD_WORDS = [
        'connard','connasse','salope','salop','pute','putain',
        'merde','enculer','encule','baise','baiser','foutre',
        'bordel','cretin','imbecile','abruti','fdp','ntm',
        'nique','niquer','batard',
        'fuck','fucking','fucker','shit','bitch','asshole',
        'bastard','cunt','dick','cock','pussy','whore','slut',
        'moron','stupid','dumbass','jackass','crap','piss','wanker','twat'
    ];

    function containsBadWord(text) {
        var lower = ' ' + text.toLowerCase() + ' ';
        for (var i = 0; i < BAD_WORDS.length; i++) {
            if (lower.indexOf(' ' + BAD_WORDS[i] + ' ') !== -1 ||
                lower.indexOf('\n' + BAD_WORDS[i] + '\n') !== -1 ||
                lower.indexOf(' ' + BAD_WORDS[i] + '\n') !== -1 ||
                lower.indexOf('\n' + BAD_WORDS[i] + ' ') !== -1) {
                return true;
            }
        }
        return false;
    }
    var MAX_CHARS = 10;
    var badWordErrorEl = document.getElementById('badWordError');
    var charErrorEl = document.getElementById('charError');
    contenuEl.addEventListener('input', function () {
        if (contenuEl.value.length > MAX_CHARS) {
            contenuEl.value = contenuEl.value.substring(0, MAX_CHARS);
            charErrorEl.style.display = 'block';
            counterEl.style.color = '#DC2626';
            counterEl.style.fontWeight = '700';
        } else {
            charErrorEl.style.display = 'none';
            counterEl.style.color = contenuEl.value.length === MAX_CHARS ? '#DC2626' : '';
            counterEl.style.fontWeight = contenuEl.value.length === MAX_CHARS ? '700' : '';
        }
        counterEl.textContent = contenuEl.value.length + ' / ' + MAX_CHARS;
        if (containsBadWord(contenuEl.value)) {
            badWordErrorEl.style.display = 'block';
        } else {
            badWordErrorEl.style.display = 'none';
        }
    });
    contenuEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendForm.requestSubmit();
        }
    });

    // ---------- Upload photo / fichier ----------
    const photoInput = document.getElementById('photoInput');
    const fileInput  = document.getElementById('fileInput');
    const photoBtn   = document.getElementById('photoBtn');
    const fileBtn    = document.getElementById('fileBtn');
    const uploadHint = document.getElementById('uploadHint');

    photoBtn.addEventListener('click', () => photoInput.click());
    fileBtn .addEventListener('click', () => fileInput.click());

    async function handleUpload(input) {
        const file = input.files && input.files[0];
        if (!file) return;
        if (file.size > 10 * 1024 * 1024) {
            flash('Fichier trop volumineux (max 10 Mo).', 'danger');
            input.value = '';
            return;
        }
        uploadHint.style.display = 'block';
        uploadHint.innerHTML = '<i class="bi bi-arrow-repeat"></i> Envoi de ' + escapeHtml(file.name) + '...';
        const r = await ChatBus.uploadFile(file);
        uploadHint.style.display = 'none';
        input.value = '';
        if (!r.ok || !r.data || !r.data.success) {
            const errs = (r.data && r.data.errors) ? r.data.errors.join(', ') : (r.data && r.data.error) ? r.data.error : 'Échec de l\'envoi.';
            flash(errs, 'danger');
        }
    }
    photoInput.addEventListener('change', () => handleUpload(photoInput));
    fileInput .addEventListener('change', () => handleUpload(fileInput));

    // ---------- Lightbox ----------
    const lightbox = document.getElementById('imageLightbox');
    const lightboxImg = lightbox.querySelector('img');
    chatContainer.addEventListener('click', function (e) {
        const img = e.target.closest('img.chat-image');
        if (img) {
            e.preventDefault();
            lightboxImg.src = img.src;
            lightbox.classList.add('open');
        }
    });
    lightbox.addEventListener('click', () => lightbox.classList.remove('open'));

    scrollBottom();
})();
</script>
</body>
</html>
