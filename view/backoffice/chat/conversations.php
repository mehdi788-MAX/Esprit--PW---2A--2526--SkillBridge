<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../../../controller/ChatController.php';

$chatController = new ChatController();

$successMsg = '';
$errorMsg   = '';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $result = $chatController->deleteConversation($id);
    if ($result['success']) {
        $successMsg = "Conversation supprimée avec succès.";
    } else {
        $errorMsg = implode('<br>', $result['errors']);
    }
}

// Admin → toutes les conversations
$conversations = $chatController->listConversations()->fetchAll(PDO::FETCH_ASSOC);

// KPIs
$total_conv = count($conversations);
$with_msg = $without_msg = 0;
foreach ($conversations as $c) {
    if (!empty($c['dernier_message'])) $with_msg++; else $without_msg++;
}

$cutoff7 = date('Y-m-d 00:00:00', strtotime('-7 days'));
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT id_conversation) FROM messages WHERE date_envoi >= :c");
$stmt->execute([':c' => $cutoff7]);
$active_7d = (int)$stmt->fetchColumn();

$pageTitle  = 'Conversations';
$pageActive = 'chat_conversations';
$pageIcon   = 'bi-chat-square-dots-fill';
$useDataTables = true;
$useChatBus    = true;

include __DIR__ . '/../_partials/header.php';
?>

<!-- Hero -->
<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
  <div>
    <span class="ad-eyebrow"><span class="dot"></span> Modération</span>
    <h2 style="font-size: 1.65rem; font-weight: 800; margin: 10px 0 4px;">Toutes les conversations</h2>
    <p style="color: var(--ink-mute); margin:0; font-size:.92rem;">Vue d'ensemble des échanges entre clients et freelancers.</p>
  </div>
  <a href="<?= $BOCHAT ?>/add_conversation.php" class="ad-btn ad-btn-sage"><i class="bi bi-plus-circle-fill"></i> Nouvelle conversation</a>
</div>

<?php if ($successMsg): ?>
  <div class="ad-alert success"><i class="bi bi-check-circle-fill"></i><span><?= $successMsg ?></span></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
  <div class="ad-alert danger"><i class="bi bi-exclamation-triangle-fill"></i><span><?= $errorMsg ?></span></div>
<?php endif; ?>

<!-- KPI grid -->
<div class="kpi-grid mb-4" style="display:grid; grid-template-columns: repeat(4, 1fr); gap: 14px;">
  <div class="kpi">
    <div class="head">
      <span class="lbl">Total</span>
      <span class="ic-sm t-sage"><i class="bi bi-chat-square-dots-fill"></i></span>
    </div>
    <div class="num"><?= $total_conv ?></div>
    <div class="sub"><span>conversations enregistrées</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Actives · 7j</span>
      <span class="ic-sm t-honey"><i class="bi bi-broadcast"></i></span>
    </div>
    <div class="num"><?= $active_7d ?></div>
    <div class="sub"><span>au moins 1 message en 7 jours</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Avec messages</span>
      <span class="ic-sm t-info"><i class="bi bi-chat-text-fill"></i></span>
    </div>
    <div class="num"><?= $with_msg ?></div>
    <div class="sub"><span><?= $total_conv > 0 ? round($with_msg / $total_conv * 100) : 0 ?>% du total</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Vides</span>
      <span class="ic-sm t-danger"><i class="bi bi-chat-square"></i></span>
    </div>
    <div class="num"><?= $without_msg ?></div>
    <div class="sub"><span>aucun message échangé</span></div>
  </div>
</div>

<div class="ad-card">
  <div class="ad-card-head">
    <h6><i class="bi bi-list-ul"></i> Liste des conversations</h6>
    <span class="count"><?= count($conversations) ?></span>
  </div>
  <div class="ad-card-body tight">
    <?php if (empty($conversations)): ?>
      <div class="ad-empty">
        <div class="ic"><i class="bi bi-chat-square-dots"></i></div>
        <h5>Aucune conversation</h5>
        <p>La table conversations est vide.</p>
      </div>
    <?php else: ?>
    <table class="ad-table ad-datatable">
      <thead>
        <tr>
          <th>#</th>
          <th>Participant 1</th>
          <th>Participant 2</th>
          <th>Dernier message</th>
          <th>Créée le</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($conversations as $conv): ?>
        <tr>
          <td style="color: var(--ink-soft); font-family: ui-monospace, monospace; font-size: .82rem;">#<?= (int)$conv['id_conversation'] ?></td>
          <td>
            <div style="font-weight:600;"><?= htmlspecialchars($conv['user1_prenom'] . ' ' . $conv['user1_nom']) ?></div>
          </td>
          <td>
            <div style="font-weight:600;"><?= htmlspecialchars($conv['user2_prenom'] . ' ' . $conv['user2_nom']) ?></div>
          </td>
          <td style="max-width: 280px;">
            <?php if (!empty($conv['dernier_message'])): ?>
              <span style="color: var(--ink-2); font-size: .88rem;"><?= htmlspecialchars(chat_message_preview($conv['dernier_message'], 70)) ?></span>
            <?php else: ?>
              <span style="color: var(--ink-soft); font-style: italic; font-size: .82rem;">Aucun message</span>
            <?php endif; ?>
          </td>
          <td style="color: var(--ink-mute); font-size: .82rem;"><?= htmlspecialchars($conv['date_creation']) ?></td>
          <td>
            <div class="d-flex align-items-center gap-1">
              <a href="<?= $BOCHAT ?>/chat.php?id=<?= (int)$conv['id_conversation'] ?>" class="ad-iconbtn open" title="Ouvrir"><i class="bi bi-eye-fill"></i></a>
              <a href="<?= $BOCHAT ?>/edit_conversation.php?id=<?= (int)$conv['id_conversation'] ?>" class="ad-iconbtn edit" title="Modifier"><i class="bi bi-pencil"></i></a>
              <a href="<?= $BOCHAT ?>/conversations.php?action=delete&id=<?= (int)$conv['id_conversation'] ?>"
                 class="ad-iconbtn delete" title="Supprimer"
                 onclick="return confirm('Supprimer cette conversation et tous ses messages ?');"><i class="bi bi-trash3"></i></a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../_partials/footer.php'; ?>
