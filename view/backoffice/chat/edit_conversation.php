<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../../../controller/ChatController.php';

$chatController = new ChatController();

$errors = [];
$successMsg = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: ' . backoffice_url('chat') . '/conversations.php'); exit; }

$conversation = $chatController->getConversation($id);
if (!$conversation) { header('Location: ' . backoffice_url('chat') . '/conversations.php'); exit; }

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

$pageTitle  = 'Modifier conversation #' . $id;
$pageActive = 'chat_conversations';
$pageIcon   = 'bi-pencil-square';
$useChatBus = true;

include __DIR__ . '/../_partials/header.php';
?>

<!-- Hero -->
<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
  <div>
    <span class="ad-eyebrow"><span class="dot"></span> Édition · #<?= $id ?></span>
    <h2 style="font-size: 1.65rem; font-weight: 800; margin: 10px 0 4px;">Modifier la conversation</h2>
    <p style="color: var(--ink-mute); margin:0; font-size:.92rem;">Changez les participants — utile pour corriger une conversation mal initialisée.</p>
  </div>
  <a href="<?= $BOCHAT ?>/conversations.php" class="ad-btn ad-btn-ghost"><i class="bi bi-arrow-left"></i> Retour</a>
</div>

<?php if ($successMsg): ?>
  <div class="ad-alert success"><i class="bi bi-check-circle-fill"></i><span><?= htmlspecialchars($successMsg) ?></span></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="ad-alert danger">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>
      <strong>Erreur(s) de validation :</strong>
      <ul style="margin: 4px 0 0; padding-left: 18px;">
        <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
      </ul>
    </div>
  </div>
<?php endif; ?>

<div class="ad-card">
  <div class="ad-card-head"><h6><i class="bi bi-people-fill"></i> Modifier les participants</h6></div>
  <div class="ad-card-body">
    <form method="POST" action="<?= $BOCHAT ?>/edit_conversation.php?id=<?= $id ?>" novalidate>
      <div class="row g-3">
        <div class="col-md-6">
          <label for="user1_id" class="ad-form-label">Participant 1 <span style="color:var(--danger);">*</span></label>
          <select class="ad-form-select" id="user1_id" name="user1_id">
            <option value="">— Choisir —</option>
            <?php foreach ($users as $u): ?>
              <option value="<?= (int)$u['id'] ?>" <?= ($user1_id == $u['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?> · <?= htmlspecialchars($u['role']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label for="user2_id" class="ad-form-label">Participant 2 <span style="color:var(--danger);">*</span></label>
          <select class="ad-form-select" id="user2_id" name="user2_id">
            <option value="">— Choisir —</option>
            <?php foreach ($users as $u): ?>
              <option value="<?= (int)$u['id'] ?>" <?= ($user2_id == $u['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?> · <?= htmlspecialchars($u['role']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="<?= $BOCHAT ?>/conversations.php" class="ad-btn ad-btn-ghost">Annuler</a>
        <button type="submit" class="ad-btn ad-btn-sage"><i class="bi bi-check2-circle"></i> Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../_partials/footer.php'; ?>
