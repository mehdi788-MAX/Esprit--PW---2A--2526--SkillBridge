<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../../../controller/ChatController.php';

$chatController = new ChatController();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: ' . backoffice_url('chat') . '/conversations.php'); exit; }

$conversation = $chatController->getConversation($id);
if (!$conversation) { header('Location: ' . backoffice_url('chat') . '/conversations.php'); exit; }

$successMsg = '';
$errorMsg   = '';

// Admin moderation: delete an individual message
if (isset($_GET['action']) && $_GET['action'] === 'delete_msg' && isset($_GET['msg'])) {
    $r = $chatController->deleteMessage((int)$_GET['msg']);
    if ($r['success']) $successMsg = "Message supprimé.";
    else               $errorMsg   = implode('<br>', $r['errors']);
}

$messages = $chatController->listMessagesByConversation($id)->fetchAll(PDO::FETCH_ASSOC);

// Load reactions for all messages in this thread (admin moderation view = read-only)
$reactionsByMsg = [];
if (!empty($messages)) {
    $msgIds = array_map(fn($m) => (int)$m['id_message'], $messages);
    $placeholders = implode(',', array_fill(0, count($msgIds), '?'));
    try {
        $stmt = $pdo->prepare("
            SELECT mr.message_id, mr.emoji, u.prenom, u.nom
            FROM message_reactions mr
            JOIN utilisateurs u ON u.id = mr.user_id
            WHERE mr.message_id IN ($placeholders)
            ORDER BY mr.created_at ASC
        ");
        $stmt->execute($msgIds);
        foreach ($stmt as $r) {
            $emoji = $r['emoji'];
            $reactor = trim($r['prenom'] . ' ' . $r['nom']);
            if (!isset($reactionsByMsg[$r['message_id']][$emoji])) {
                $reactionsByMsg[$r['message_id']][$emoji] = [];
            }
            $reactionsByMsg[$r['message_id']][$emoji][] = $reactor;
        }
    } catch (Throwable $e) {}
}

$user1 = trim($conversation['user1_prenom'] . ' ' . $conversation['user1_nom']);
$user2 = trim($conversation['user2_prenom'] . ' ' . $conversation['user2_nom']);

// Site root URL for asset references (uploads, profile pics)
$SITE_BASE = base_url();

// Stats
$total       = count($messages);
$totalText   = count(array_filter($messages, fn($m) => ($m['type'] ?? 'text') === 'text'));
$totalImage  = count(array_filter($messages, fn($m) => ($m['type'] ?? '') === 'image'));
$totalFile   = count(array_filter($messages, fn($m) => ($m['type'] ?? '') === 'file'));

$pageTitle  = 'Conversation #' . $id . ' — ' . $user1 . ' ↔ ' . $user2;
$pageActive = 'chat_conversations';
$pageIcon   = 'bi-chat-square-text-fill';
$useChatBus = true;
$chatBusConv = $id;

include __DIR__ . '/../_partials/header.php';
?>

<!-- Hero -->
<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
  <div>
    <span class="ad-eyebrow"><span class="dot"></span> Modération · #<?= $id ?></span>
    <h2 style="font-size: 1.65rem; font-weight: 800; margin: 10px 0 4px;"><?= htmlspecialchars($user1) ?> ↔ <?= htmlspecialchars($user2) ?></h2>
    <p style="color: var(--ink-mute); margin:0; font-size:.92rem;">Vue admin en lecture seule — supprimez les messages problématiques au survol d'une bulle.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= $BOCHAT ?>/edit_conversation.php?id=<?= $id ?>" class="ad-btn ad-btn-ghost"><i class="bi bi-pencil"></i> Modifier</a>
    <a href="<?= $BOCHAT ?>/conversations.php" class="ad-btn ad-btn-ghost"><i class="bi bi-arrow-left"></i> Retour</a>
  </div>
</div>

<?php if ($successMsg): ?>
  <div class="ad-alert success"><i class="bi bi-check-circle-fill"></i><span><?= htmlspecialchars($successMsg) ?></span></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
  <div class="ad-alert danger"><i class="bi bi-exclamation-triangle-fill"></i><span><?= $errorMsg ?></span></div>
<?php endif; ?>

<!-- KPI grid -->
<div class="kpi-grid mb-4" style="display:grid; grid-template-columns: repeat(4, 1fr); gap: 14px;">
  <div class="kpi">
    <div class="head">
      <span class="lbl">Messages</span>
      <span class="ic-sm t-sage"><i class="bi bi-chat-fill"></i></span>
    </div>
    <div class="num"><?= $total ?></div>
    <div class="sub"><span>total échangés</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Textes</span>
      <span class="ic-sm t-info"><i class="bi bi-card-text"></i></span>
    </div>
    <div class="num"><?= $totalText ?></div>
    <div class="sub"><span>messages écrits</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Images</span>
      <span class="ic-sm t-honey"><i class="bi bi-image-fill"></i></span>
    </div>
    <div class="num"><?= $totalImage ?></div>
    <div class="sub"><span>photos partagées</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Fichiers</span>
      <span class="ic-sm t-danger"><i class="bi bi-paperclip"></i></span>
    </div>
    <div class="num"><?= $totalFile ?></div>
    <div class="sub"><span>pièces jointes</span></div>
  </div>
</div>

<style>
  .mod-thread { display:flex; flex-direction:column; gap: 10px; padding: 24px; background: linear-gradient(180deg, var(--bg) 0%, var(--paper) 100%); }
  .mod-row { display:flex; gap: 10px; align-items: flex-start; }
  .mod-row.mine { flex-direction: row-reverse; }
  .mod-row .ava {
    width: 36px; height: 36px; border-radius: 50%; background: var(--sage-soft); color: var(--sage);
    display:inline-flex; align-items:center; justify-content:center; font-weight: 700; font-size: .82rem; flex-shrink: 0;
    overflow:hidden; border: 1px solid var(--rule);
  }
  .mod-row .ava img { width:100%; height:100%; object-fit:cover; }
  .mod-bub { max-width: 65%; padding: 12px 16px; border-radius: 16px; line-height: 1.5; font-size: .94rem; position: relative; }
  .mod-row.mine .mod-bub { background: var(--sage); color: var(--paper); border-bottom-right-radius: 6px; }
  .mod-row:not(.mine) .mod-bub { background: var(--paper); color: var(--ink); border: 1px solid var(--rule); border-bottom-left-radius: 6px; }
  .mod-bub .meta { font-size: .72rem; opacity: .78; margin-top: 6px; font-weight: 500; }
  .mod-bub .del-btn {
    position: absolute; top: -10px; right: -10px;
    width: 28px; height: 28px; border-radius: 50%; border: 1.5px solid var(--rule); background: var(--paper);
    color: var(--danger); display: inline-flex; align-items: center; justify-content: center; font-size: .82rem;
    cursor: pointer; opacity: 0; transition: all .18s;
    box-shadow: 0 4px 10px -4px rgba(15,15,15,.18);
  }
  .mod-bub:hover .del-btn { opacity: 1; }
  .mod-bub .del-btn:hover { background: var(--danger); color: var(--paper); border-color: var(--danger); transform: scale(1.05); }
  .mod-bub .file-card { display:flex; align-items:center; gap:10px; }
  .mod-bub.image-bub { padding: 4px; }
  .mod-bub.image-bub img { max-width: 280px; max-height: 280px; border-radius: 12px; display: block; cursor: zoom-in; }
  .mod-bub.image-bub .meta { padding: 6px 10px 2px; }
  .empty-thread { text-align: center; padding: 60px 20px; color: var(--ink-mute); }
  .empty-thread .ic { width: 72px; height: 72px; border-radius: 22px; background: var(--sage-soft); color: var(--sage); display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 14px; }
  .img-lightbox { display:none; position:fixed; inset:0; background:rgba(0,0,0,.88); z-index:5000; align-items:center; justify-content:center; cursor:zoom-out; }
  .img-lightbox.open { display:flex; }
  .img-lightbox img { max-width: 92vw; max-height: 92vh; border-radius: 8px; box-shadow: 0 12px 40px rgba(0,0,0,.5); }
</style>

<div class="ad-card">
  <div class="ad-card-head">
    <h6><i class="bi bi-chat-square-text-fill"></i> Fil de discussion</h6>
    <div class="d-flex align-items-center gap-2">
      <span class="ad-badge b-neutral"><?= htmlspecialchars($conversation['date_creation']) ?></span>
      <span class="count"><?= $total ?> message<?= $total > 1 ? 's' : '' ?></span>
    </div>
  </div>
  <div class="ad-card-body tight">
    <?php if (empty($messages)): ?>
      <div class="empty-thread">
        <div class="ic"><i class="bi bi-chat-dots"></i></div>
        <h5 style="font-weight:800; color:var(--ink); margin: 0 0 4px;">Aucun message</h5>
        <p style="margin:0;">Cette conversation est vide.</p>
      </div>
    <?php else: ?>
      <div class="mod-thread" id="modThread">
        <?php
        // Use user1 as left side ("not mine"), user2 as right side ("mine") for visual variety.
        // Admins are observers — neither side is "us" — but we still align by sender to keep readability.
        $rightSenderId = (int)$conversation['user2_id'];
        foreach ($messages as $msg):
            $type = $msg['type'] ?? 'text';
            $isRight = ((int)$msg['sender_id'] === $rightSenderId);
            $senderInitial = strtoupper(mb_substr($msg['sender_prenom'] ?? '?', 0, 1, 'UTF-8'));
            $time = date('d/m/Y H:i', strtotime($msg['date_envoi']));
            $meta  = ($type === 'image' || $type === 'file') ? json_decode($msg['contenu'], true) : null;
        ?>
          <?php
            $msgReactions = $reactionsByMsg[$msg['id_message']] ?? [];
            $reactionsHtml = '';
            if (!empty($msgReactions)) {
                $items = [];
                foreach ($msgReactions as $emo => $reactors) {
                    $tooltip = htmlspecialchars(implode(', ', $reactors));
                    $items[] = '<span class="reaction-chip" title="' . $tooltip . '">' . $emo . ' <span class="count">' . count($reactors) . '</span></span>';
                }
                $reactionsHtml = '<div class="reactions">' . implode('', $items) . '</div>';
            }
          ?>
          <div class="mod-row <?= $isRight ? 'mine' : '' ?>" data-msg-id="<?= (int)$msg['id_message'] ?>">
            <div class="ava"><?= htmlspecialchars($senderInitial) ?></div>
            <?php if ($type === 'image' && is_array($meta)): ?>
              <div class="mod-bub image-bub">
                <img class="chat-image" src="<?= htmlspecialchars($SITE_BASE) ?>/<?= htmlspecialchars($meta['url'] ?? '') ?>" alt=""
                     onerror="this.onerror=null; this.style.display='none'; var b=this.parentNode; var n=document.createElement('div'); n.style.cssText='padding:18px 20px; color:var(--ink-mute); font-size:.85rem;'; n.innerHTML='<i class=&quot;bi bi-image-alt&quot;></i> Image introuvable'; b.insertBefore(n,b.firstChild);">
                <div class="meta"><?= htmlspecialchars($msg['sender_prenom']) ?> · <?= $time ?> · <?= $msg['is_seen'] ? '✓✓ lu' : '✓ envoyé' ?></div>
                <?= $reactionsHtml ?>
                <a href="<?= $BOCHAT ?>/chat.php?id=<?= $id ?>&action=delete_msg&msg=<?= (int)$msg['id_message'] ?>" class="del-btn" title="Supprimer ce message" onclick="return confirm('Supprimer ce message ?');"><i class="bi bi-trash3"></i></a>
              </div>
            <?php elseif ($type === 'file' && is_array($meta)):
                $sizeKB = round(($meta['size'] ?? 0) / 1024, 1);
                $sizeStr = $sizeKB > 1024 ? round($sizeKB / 1024, 1) . ' MB' : $sizeKB . ' KB'; ?>
              <div class="mod-bub">
                <a class="file-card" href="<?= htmlspecialchars($SITE_BASE) ?>/<?= htmlspecialchars($meta['url'] ?? '') ?>" target="_blank" download style="color:inherit; text-decoration:none;">
                  <i class="bi bi-file-earmark-fill" style="font-size:1.6rem;"></i>
                  <div style="flex:1; min-width:0;">
                    <div style="font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($meta['name'] ?? 'Fichier') ?></div>
                    <div style="font-size:11px; opacity:.85;"><?= $sizeStr ?> · télécharger</div>
                  </div>
                </a>
                <div class="meta"><?= htmlspecialchars($msg['sender_prenom']) ?> · <?= $time ?> · <?= $msg['is_seen'] ? '✓✓ lu' : '✓ envoyé' ?></div>
                <?= $reactionsHtml ?>
                <a href="<?= $BOCHAT ?>/chat.php?id=<?= $id ?>&action=delete_msg&msg=<?= (int)$msg['id_message'] ?>" class="del-btn" title="Supprimer ce message" onclick="return confirm('Supprimer ce message ?');"><i class="bi bi-trash3"></i></a>
              </div>
            <?php else: ?>
              <div class="mod-bub">
                <?= nl2br(htmlspecialchars($msg['contenu'])) ?>
                <div class="meta"><?= htmlspecialchars($msg['sender_prenom']) ?> · <?= $time ?> · <?= $msg['is_seen'] ? '✓✓ lu' : '✓ envoyé' ?></div>
                <?= $reactionsHtml ?>
                <a href="<?= $BOCHAT ?>/chat.php?id=<?= $id ?>&action=delete_msg&msg=<?= (int)$msg['id_message'] ?>" class="del-btn" title="Supprimer ce message" onclick="return confirm('Supprimer ce message ?');"><i class="bi bi-trash3"></i></a>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="img-lightbox" id="imageLightbox"><img alt=""></div>

<script>
(function () {
  const lb = document.getElementById('imageLightbox');
  const lbImg = lb.querySelector('img');
  document.querySelectorAll('img.chat-image').forEach(img => {
    img.addEventListener('click', function (e) {
      e.preventDefault();
      lbImg.src = this.src;
      lb.classList.add('open');
    });
  });
  lb.addEventListener('click', () => lb.classList.remove('open'));
})();
</script>

<?php include __DIR__ . '/../_partials/footer.php'; ?>
