<?php
require_once __DIR__ . '/../../../controller/ChatController.php';

$chatController = new ChatController();

// Simuler le client connecté (id=3)
$currentUserId = 3;

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

$chatController->markMessagesAsSeen($id, $currentUserId);

$messages    = $chatController->listMessagesByConversation($id)->fetchAll(PDO::FETCH_ASSOC);
$lastMsgId   = !empty($messages) ? max(array_map(fn($m) => (int)$m['id_message'], $messages)) : 0;
$templateBase = '../EasyFolio';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>SkillBridge - Chat avec <?= htmlspecialchars($otherUser) ?></title>
    <link href="<?= $templateBase ?>/assets/img/favicon.png" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Noto+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= $templateBase ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $templateBase ?>/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="<?= $templateBase ?>/assets/css/main.css" rel="stylesheet">
    <style>
        .chat-section { padding: 80px 0 60px; min-height: 100vh; }
        .chat-container { max-height: 500px; overflow-y: auto; padding: 20px; background: #f8f9fa; border-radius: 10px; }
        .msg-bubble { max-width: 70%; padding: 12px 16px; border-radius: 18px; margin-bottom: 8px; word-wrap: break-word; }
        .msg-sent { background: linear-gradient(135deg, #0d6efd, #0a58ca); color: white; margin-left: auto; border-bottom-right-radius: 4px; }
        .msg-received { background: #e9ecef; color: #333; margin-right: auto; border-bottom-left-radius: 4px; }
        .msg-meta { font-size: 0.7rem; opacity: 0.85; margin-top: 4px; }
        .msg-bubble.optimistic { opacity: 0.55; }
        .moderation-warning { font-size: 0.72rem; color: #856404; background: #fff3cd; border-radius: 6px; padding: 6px 9px; margin-top: 6px; }
        .typing-indicator { color: #6c757d; font-style: italic; font-size: 0.85rem; min-height: 22px; padding: 4px 8px; }
        .typing-dots span { display:inline-block;width:6px;height:6px;border-radius:50%;background:#6c757d;margin:0 1px;animation:tdots 1.2s infinite; }
        .typing-dots span:nth-child(2){animation-delay:.2s}
        .typing-dots span:nth-child(3){animation-delay:.4s}
        @keyframes tdots { 0%,80%,100% { transform:translateY(0);opacity:.4 } 40% { transform:translateY(-4px);opacity:1 } }
        .chat-image { cursor: zoom-in; }
        .file-card { display:flex; align-items:center; gap:12px; min-width:240px; max-width:340px; }
        .file-card .file-name { font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .file-card .file-size { font-size:11px; opacity:.85; }
        .img-lightbox { display:none; position:fixed; inset:0; background:rgba(0,0,0,.85); z-index:4000;
                        align-items:center; justify-content:center; cursor:zoom-out; }
        .img-lightbox.open { display:flex; }
        .img-lightbox img { max-width:92vw; max-height:92vh; box-shadow:0 8px 30px rgba(0,0,0,.5); border-radius:6px; }
        .upload-progress { font-size:12px; color:#6c757d; padding:6px 8px; }
    </style>
</head>
<body class="index-page">

<header id="header" class="header d-flex align-items-center sticky-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
        <a href="#" class="logo d-flex align-items-center me-auto me-xl-0">
            <h1 class="sitename">SkillBridge</h1>
        </a>
        <nav id="navmenu" class="navmenu">
            <ul>
                <li><a href="<?= $templateBase ?>/index.html">Accueil</a></li>
                <li><a href="conversations.php" class="active">Mes Conversations</a></li>
                <li><a href="new_conversation.php">Nouveau Chat</a></li>
                <li id="bellSlot" style="display:flex;align-items:center;"></li>
            </ul>
            <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>
    </div>
</header>

<main class="main">
    <section class="chat-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <a href="conversations.php" class="text-decoration-none">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                    </div>

                    <div id="alertSlot"></div>

                    <div class="card shadow">
                        <div class="card-header bg-primary text-white d-flex align-items-center">
                            <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center me-3"
                                 style="width:40px;height:40px;font-weight:bold;">
                                <?= strtoupper(substr($otherUser, 0, 1)) ?>
                            </div>
                            <div>
                                <h6 class="mb-0 text-white"><?= htmlspecialchars($otherUser) ?></h6>
                                <small class="opacity-75">Freelancer</small>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <div class="chat-container" id="chatContainer">
                                <?php if (empty($messages)): ?>
                                    <div class="text-center text-muted py-5" id="emptyState">
                                        <i class="bi bi-chat-dots" style="font-size: 2.5rem;"></i>
                                        <p class="mt-2">Commencez la conversation !</p>
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
                                                         style="max-width:280px;max-height:280px;border-radius:10px;display:block;">
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

                            <div class="typing-indicator px-3" id="typingIndicator"></div>
                        </div>

                        <div class="card-footer">
                            <form id="sendForm" novalidate>
                                <div class="input-group">
                                    <textarea id="contenu" class="form-control" name="contenu" rows="2"
                                              placeholder="Tapez votre message..."></textarea>
                                    <input type="file" id="photoInput" accept="image/*" hidden>
                                    <input type="file" id="fileInput" hidden>
                                    <button type="button" class="btn btn-outline-secondary" id="photoBtn" title="Envoyer une photo">
                                        <i class="bi bi-image"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="fileBtn" title="Envoyer un fichier">
                                        <i class="bi bi-paperclip"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" data-emoji-target="#contenu" title="Émojis">
                                        <i class="bi bi-emoji-smile"></i>
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-send"></i> Envoyer
                                    </button>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small id="moderationHint" class="text-warning"></small>
                                    <small id="count" class="text-muted">0 / 10</small>
                                </div>
                                <div id="charError" class="text-danger mt-1" style="display:none; font-size:0.85rem;">
                                    <i class="bi bi-exclamation-circle"></i> Maximum 10 caractères atteint.
                                </div>
                                <div id="badWordError" class="text-danger mt-1" style="display:none; font-size:0.85rem;">
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

<footer id="footer" class="footer dark-background">
    <div class="container">
        <div class="copyright text-center">
            <p>&copy; <span>Copyright</span> <strong class="px-1 sitename">SkillBridge</strong> <span><?= date('Y') ?></span></p>
        </div>
    </div>
</footer>

<script src="<?= $templateBase ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= $templateBase ?>/assets/js/main.js"></script>
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
            + '       style="max-width:280px;max-height:280px;border-radius:10px;display:block;">'
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

    ChatBus.on('notif', function () {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const o = ctx.createOscillator(); const g = ctx.createGain();
            o.connect(g); g.connect(ctx.destination);
            o.frequency.value = 880; g.gain.setValueAtTime(0.04, ctx.currentTime);
            o.start(); o.stop(ctx.currentTime + 0.08);
        } catch (e) {}
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
            counterEl.style.color = 'red';
            counterEl.style.fontWeight = 'bold';
        } else {
            charErrorEl.style.display = 'none';
            counterEl.style.color = contenuEl.value.length === MAX_CHARS ? 'red' : '';
            counterEl.style.fontWeight = contenuEl.value.length === MAX_CHARS ? 'bold' : '';
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