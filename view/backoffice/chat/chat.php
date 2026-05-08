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

// Marquer comme lus à l'arrivée
$chatController->markMessagesAsSeen($id, $currentUserId);

// Charger l'historique initial (la page reste fonctionnelle sans JS)
$messages    = $chatController->listMessagesByConversation($id)->fetchAll(PDO::FETCH_ASSOC);
$lastMsgId   = !empty($messages) ? max(array_map(fn($m) => (int)$m['id_message'], $messages)) : 0;
$templateBase = '..';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>SkillBridge - Chat avec <?= htmlspecialchars($otherUser) ?></title>
    <link href="<?= $templateBase ?>/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="<?= $templateBase ?>/css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .chat-container { max-height: 500px; overflow-y: auto; padding: 20px; background: #f8f9fc; border-radius: 5px; }
        .message-bubble { max-width: 70%; padding: 10px 15px; border-radius: 15px; margin-bottom: 10px; word-wrap: break-word; }
        .message-sent { background-color: #4e73df; color: white; margin-left: auto; border-bottom-right-radius: 5px; }
        .message-received { background-color: #e2e8f0; color: #333; margin-right: auto; border-bottom-left-radius: 5px; }
        .message-meta { font-size: 0.75rem; opacity: 0.8; margin-top: 5px; }
        .message-bubble.optimistic { opacity: 0.55; }
        .moderation-warning { font-size: 0.72rem; color: #856404; background: #fff3cd; border-radius: 6px; padding: 6px 9px; margin-top: 6px; }
        .typing-indicator { color: #858796; font-style: italic; font-size: 0.85rem; min-height: 22px; padding: 4px 8px; }
        .typing-dots span { display:inline-block;width:6px;height:6px;border-radius:50%;background:#858796;margin:0 1px;animation:tdots 1.2s infinite; }
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
        .upload-progress { font-size:12px; color:#5a5c69; padding:6px 8px; }
    </style>
</head>
<body id="page-top">
<div id="wrapper">
    <?php $activePage = 'chat'; include 'sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3"><i class="fa fa-bars"></i></button>
                <ul class="navbar-nav ml-auto align-items-center">
                    <li class="nav-item mr-2"><div id="bellSlot"></div></li>
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                            <span class="mr-2 d-none d-lg-inline text-gray-600 small">Mohamed Ben Ali (Freelancer)</span>
                            <img class="img-profile rounded-circle" src="<?= $templateBase ?>/img/undraw_profile.svg">
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-comments text-primary"></i>
                        Chat avec <?= htmlspecialchars($otherUser) ?>
                    </h1>
                    <a href="conversations.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Retour
                    </a>
                </div>

                <div id="alertSlot"></div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-user-circle"></i> <?= htmlspecialchars($otherUser) ?>
                        </h6>
                        <small class="text-muted">Conversation créée le <?= htmlspecialchars($conversation['date_creation']) ?></small>
                    </div>
                    <div class="card-body">

                        <div class="chat-container" id="chatContainer">
                            <?php if (empty($messages)): ?>
                                <div class="text-center text-muted py-5" id="emptyState">
                                    <i class="fas fa-paper-plane fa-2x mb-3"></i>
                                    <p>Aucun message. Envoyez le premier !</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $msg):
                                    $isSent = ($msg['sender_id'] == $currentUserId);
                                    $meta = ($msg['type'] === 'image' || $msg['type'] === 'file') ? json_decode($msg['contenu'], true) : null;
                                    $tickHtml = $isSent
                                        ? '<span class="seen-tick">' . ($msg['is_seen'] ? '<i class="fas fa-check-double"></i>' : '<i class="fas fa-check"></i>') . '</span>'
                                        : '';
                                    $metaLine = htmlspecialchars($msg['sender_prenom']) . ' · ' . date('H:i', strtotime($msg['date_envoi'])) . ' ' . $tickHtml;
                                ?>
                                    <div class="d-flex <?= $isSent ? 'justify-content-end' : 'justify-content-start' ?> mb-2"
                                         data-msg-id="<?= (int)$msg['id_message'] ?>">
                                        <?php if ($msg['type'] === 'image' && is_array($meta)): ?>
                                            <div class="message-bubble <?= $isSent ? 'message-sent' : 'message-received' ?>" style="padding:4px;">
                                                <img class="chat-image" src="../../../<?= htmlspecialchars($meta['url'] ?? '') ?>"
                                                     style="max-width:280px;max-height:280px;border-radius:10px;display:block;">
                                                <div class="message-meta" style="padding:6px 8px 2px;"><?= $metaLine ?></div>
                                            </div>
                                        <?php elseif ($msg['type'] === 'file' && is_array($meta)):
                                            $sizeKB = round(($meta['size'] ?? 0) / 1024, 1);
                                            $sizeStr = $sizeKB > 1024 ? round($sizeKB / 1024, 1) . ' MB' : $sizeKB . ' KB';
                                        ?>
                                            <div class="message-bubble <?= $isSent ? 'message-sent' : 'message-received' ?>">
                                                <a href="../../../<?= htmlspecialchars($meta['url'] ?? '') ?>" target="_blank" download
                                                   class="file-card" style="color:inherit;text-decoration:none;">
                                                    <i class="fas fa-file fa-2x"></i>
                                                    <div style="flex:1;min-width:0;">
                                                        <div class="file-name"><?= htmlspecialchars($meta['name'] ?? 'Fichier') ?></div>
                                                        <div class="file-size"><?= $sizeStr ?> · Cliquer pour télécharger</div>
                                                    </div>
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <div class="message-meta"><?= $metaLine ?></div>
                                            </div>
                                        <?php else: ?>
                                            <div class="message-bubble <?= $isSent ? 'message-sent' : 'message-received' ?>">
                                                <div class="msg-body"><?= nl2br(htmlspecialchars($msg['contenu'])) ?></div>
                                                <div class="message-meta"><?= $metaLine ?></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="typing-indicator" id="typingIndicator"></div>

                        <hr>

                        <form id="sendForm" novalidate>
                            <div class="input-group">
                                <textarea id="contenu" class="form-control" name="contenu" rows="2"
                                          placeholder="Tapez votre message..."></textarea>
                                <div class="input-group-append">
                                    <input type="file" id="photoInput" accept="image/*" hidden>
                                    <input type="file" id="fileInput" hidden>
                                    <button type="button" class="btn btn-light border" id="photoBtn" title="Envoyer une photo">
                                        <i class="far fa-image"></i>
                                    </button>
                                    <button type="button" class="btn btn-light border" id="fileBtn" title="Envoyer un fichier">
                                        <i class="fas fa-paperclip"></i>
                                    </button>
                                    <button type="button" class="btn btn-light border" data-emoji-target="#contenu" title="Émojis">
                                        <i class="far fa-smile"></i>
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Envoyer
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small id="moderationHint" class="text-warning"></small>
                                <small id="count" class="text-muted">0 / 10</small>
                            </div>
                            <div id="charError" class="text-danger mt-1" style="display:none; font-size:0.85rem;">
                                <i class="fas fa-exclamation-circle"></i> Maximum 10 caractères atteint.
                            </div>
                            <div id="badWordError" class="text-danger mt-1" style="display:none; font-size:0.85rem;">
                                <i class="fas fa-ban"></i> Votre message contient un mot inapproprié.
                            </div>
                            <div id="uploadHint" class="upload-progress" style="display:none;"></div>
                        </form>

                    </div>
                </div>
            </div>
        </div>

        <!-- Lightbox image -->
        <div class="img-lightbox" id="imageLightbox"><img alt=""></div>

        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Copyright &copy; SkillBridge <?= date('Y') ?></span>
                </div>
            </div>
        </footer>
    </div>
</div>

<script src="<?= $templateBase ?>/vendor/jquery/jquery.min.js"></script>
<script src="<?= $templateBase ?>/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= $templateBase ?>/js/sb-admin-2.min.js"></script>
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
            + '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>';
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
            ? '<span class="seen-tick">' + (msg.is_seen ? '<i class="fas fa-check-double"></i>' : '<i class="fas fa-check"></i>') + '</span>'
            : '';
        wrap.innerHTML = ''
            + '<div class="message-bubble ' + (isMine ? 'message-sent' : 'message-received') + '" style="padding:4px;">'
            + '  <img class="chat-image" src="../../../' + escapeHtml(meta.url || '') + '" alt="" '
            + '       style="max-width:280px;max-height:280px;border-radius:10px;display:block;">'
            + '  <div class="message-meta" style="padding:6px 8px 2px;">'
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
            ? '<span class="seen-tick">' + (msg.is_seen ? '<i class="fas fa-check-double"></i>' : '<i class="fas fa-check"></i>') + '</span>'
            : '';
        wrap.innerHTML = ''
            + '<div class="message-bubble ' + (isMine ? 'message-sent' : 'message-received') + '">'
            + '  <a class="file-card" href="../../../' + escapeHtml(meta.url || '') + '" target="_blank" download '
            + '     style="color:inherit;text-decoration:none;">'
            + '    <i class="fas fa-file fa-2x"></i>'
            + '    <div style="flex:1;min-width:0;">'
            + '      <div class="file-name">' + escapeHtml(meta.name || 'Fichier') + '</div>'
            + '      <div class="file-size">' + fmtSize(meta.size) + ' · Cliquer pour télécharger</div>'
            + '    </div>'
            + '    <i class="fas fa-download"></i>'
            + '  </a>'
            + '  <div class="message-meta">'
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
            ? '<span class="seen-tick">' + (msg.is_seen ? '<i class="fas fa-check-double"></i>' : '<i class="fas fa-check"></i>') + '</span>'
            : '';
        const mod = (msg.moderation && msg.moderation.flagged)
            ? '<div class="moderation-warning"><i class="fas fa-shield-alt"></i> ' + escapeHtml(msg.moderation.message) + '</div>'
            : '';
        wrap.innerHTML = ''
            + '<div class="message-bubble ' + (isMine ? 'message-sent' : 'message-received') + '">'
            + '<div class="msg-body">' + escapeHtml(msg.contenu).replace(/\n/g, '<br>') + '</div>'
            + mod
            + '<div class="message-meta">'
            +   escapeHtml(msg.sender_prenom || '') + ' · ' + fmtTime(msg.date_envoi || '') + ' '
            +   seen
            + '</div>'
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
              + '<span class="ml-1">en train d\'écrire</span>';
        } else {
            typingEl.innerHTML = '';
        }
    });

    ChatBus.on('seenUpdate', function (maxSeenId) {
        const ticks = chatContainer.querySelectorAll('.message-bubble.message-sent .seen-tick');
        ticks.forEach(function (el) {
            const wrap = el.closest('[data-msg-id]');
            const id = parseInt(wrap.getAttribute('data-msg-id'), 10);
            if (id && id <= maxSeenId) el.innerHTML = '<i class="fas fa-check-double"></i>';
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
            flash('<i class="fas fa-ban"></i> Votre message contient un mot inapproprié. Veuillez le modifier.', 'danger');
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
        opt.querySelector('.message-bubble').classList.add('optimistic');
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
        opt.querySelector('.message-bubble').classList.remove('optimistic');
        if (r.data.moderation && r.data.moderation.flagged) {
            moderationHint.innerHTML = '<i class="fas fa-shield-alt"></i> ' + escapeHtml(r.data.moderation.message);
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
        // Physically block typing beyond MAX_CHARS
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
        // Mot interdit
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
        uploadHint.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi de ' + escapeHtml(file.name) + '...';
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