<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../../../controller/ChatController.php';

$chatController = new ChatController();

$conversations = $chatController->listConversations()->fetchAll(PDO::FETCH_ASSOC);

$messages = [];
$selectedConversation = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_conversation']) && isset($_POST['search'])) {
    $id_conversation = (int)$_POST['id_conversation'];
    if ($id_conversation > 0) {
        $messages = $chatController->listMessagesByConversation($id_conversation)->fetchAll(PDO::FETCH_ASSOC);
        $selectedConversation = $chatController->getConversation($id_conversation);
    }
}

$pageTitle  = 'Recherche messages';
$pageActive = 'chat_search';
$pageIcon   = 'bi-search-heart-fill';
$useChatBus = true;

include __DIR__ . '/../_partials/header.php';
?>

<!-- Hero -->
<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
  <div>
    <span class="ad-eyebrow"><span class="dot"></span> Inspection</span>
    <h2 style="font-size: 1.65rem; font-weight: 800; margin: 10px 0 4px;">Messages d'une conversation</h2>
    <p style="color: var(--ink-mute); margin:0; font-size:.92rem;">Sélectionnez une conversation pour inspecter tous ses messages dans l'ordre chronologique.</p>
  </div>
  <a href="<?= $BOCHAT ?>/conversations.php" class="ad-btn ad-btn-ghost"><i class="bi bi-arrow-left"></i> Conversations</a>
</div>

<div class="ad-card">
  <div class="ad-card-head"><h6><i class="bi bi-funnel-fill"></i> Sélectionner une conversation</h6></div>
  <div class="ad-card-body">
    <form action="" method="POST" id="searchForm" novalidate class="row g-3 align-items-end">
      <div class="col-md-9">
        <label for="id_conversation" class="ad-form-label">Conversation</label>
        <select name="id_conversation" id="id_conversation" class="ad-form-select">
          <option value="">— Choisir une conversation —</option>
          <?php foreach ($conversations as $conv): ?>
            <option value="<?= (int)$conv['id_conversation'] ?>" <?= (isset($_POST['id_conversation']) && (int)$_POST['id_conversation'] === (int)$conv['id_conversation']) ? 'selected' : '' ?>>
              #<?= (int)$conv['id_conversation'] ?> — <?= htmlspecialchars($conv['user1_prenom'] . ' ' . $conv['user1_nom']) ?> ↔ <?= htmlspecialchars($conv['user2_prenom'] . ' ' . $conv['user2_nom']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <button type="submit" name="search" value="1" class="ad-btn ad-btn-sage" style="width:100%;"><i class="bi bi-search"></i> Rechercher</button>
      </div>
    </form>
    <div id="js-search-error" class="ad-alert danger mt-3" style="display:none;">
      <i class="bi bi-exclamation-triangle-fill"></i><span>Veuillez sélectionner une conversation avant de rechercher.</span>
    </div>
  </div>
</div>

<?php if ($selectedConversation): ?>
  <div class="ad-card">
    <div class="ad-card-head">
      <h6><i class="bi bi-chat-text-fill"></i> Messages — <?= htmlspecialchars($selectedConversation['user1_prenom'] . ' ' . $selectedConversation['user1_nom']) ?> ↔ <?= htmlspecialchars($selectedConversation['user2_prenom'] . ' ' . $selectedConversation['user2_nom']) ?></h6>
      <span class="count"><?= count($messages) ?></span>
    </div>
    <div class="ad-card-body tight">
      <?php if (empty($messages)): ?>
        <div class="ad-empty"><div class="ic"><i class="bi bi-inbox"></i></div><h5>Aucun message</h5><p>Cette conversation est vide.</p></div>
      <?php else: ?>
      <table class="ad-table">
        <thead>
          <tr><th>#</th><th>Expéditeur</th><th>Contenu</th><th>Date</th><th>Statut</th><th>Type</th></tr>
        </thead>
        <tbody>
        <?php foreach ($messages as $msg):
            $type = $msg['type'] ?? 'text';
            $preview = $type === 'text' ? $msg['contenu'] : ($type === 'image' ? '🖼  [image]' : '📎 [fichier]'); ?>
          <tr>
            <td style="color: var(--ink-soft); font-family: ui-monospace, monospace; font-size: .8rem;">#<?= (int)$msg['id_message'] ?></td>
            <td><strong><?= htmlspecialchars($msg['sender_prenom'] . ' ' . $msg['sender_nom']) ?></strong></td>
            <td style="font-size:.9rem;"><?= htmlspecialchars($preview) ?></td>
            <td style="color: var(--ink-mute); font-size: .82rem;"><?= htmlspecialchars($msg['date_envoi']) ?></td>
            <td>
              <?php if ($msg['is_seen']): ?>
                <span class="ad-badge b-active"><i class="bi bi-check-all"></i> Lu</span>
              <?php else: ?>
                <span class="ad-badge b-inactive"><i class="bi bi-clock-history"></i> Non lu</span>
              <?php endif; ?>
            </td>
            <td><span class="ad-badge b-neutral"><?= htmlspecialchars($type) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<script>
document.getElementById('searchForm').addEventListener('submit', function (e) {
  const select = document.getElementById('id_conversation');
  const errBox = document.getElementById('js-search-error');
  if (!select.value) {
    e.preventDefault();
    errBox.style.display = 'flex';
    select.classList.add('is-invalid');
  } else {
    errBox.style.display = 'none';
    select.classList.remove('is-invalid');
  }
});
document.getElementById('id_conversation').addEventListener('change', function () {
  this.classList.remove('is-invalid');
  document.getElementById('js-search-error').style.display = 'none';
});
</script>

<?php include __DIR__ . '/../_partials/footer.php'; ?>
