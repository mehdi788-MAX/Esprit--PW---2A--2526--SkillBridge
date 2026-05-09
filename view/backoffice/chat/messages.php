<?php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../../../controller/ChatController.php';

$chatController = new ChatController();

$successMsg = '';
$errorMsg   = '';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $result = $chatController->deleteMessage($id);
    if ($result['success']) {
        $successMsg = "Message supprimé avec succès.";
    } else {
        $errorMsg = implode('<br>', $result['errors']);
    }
}

$messages = $chatController->listMessages()->fetchAll(PDO::FETCH_ASSOC);

// KPIs
$total_msg = count($messages);
$msg_today = $msg_seen = $msg_unseen = $msg_files = 0;
$today = date('Y-m-d');
foreach ($messages as $m) {
    if ($m['is_seen']) $msg_seen++; else $msg_unseen++;
    $type = $m['type'] ?? 'text';
    if ($type === 'image' || $type === 'file') $msg_files++;
    if (!empty($m['date_envoi']) && substr($m['date_envoi'], 0, 10) === $today) $msg_today++;
}

$pageTitle  = 'Tous les messages';
$pageActive = 'chat_messages';
$pageIcon   = 'bi-envelope-fill';
$useDataTables = true;
$useChatBus    = true;

include __DIR__ . '/../_partials/header.php';
?>

<!-- Hero -->
<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
  <div>
    <span class="ad-eyebrow"><span class="dot"></span> Modération</span>
    <h2 style="font-size: 1.65rem; font-weight: 800; margin: 10px 0 4px;">Tous les messages</h2>
    <p style="color: var(--ink-mute); margin:0; font-size:.92rem;">Tous les échanges, toutes conversations confondues — supprimez les messages problématiques.</p>
  </div>
  <a href="<?= $BOCHAT ?>/searchMessages.php" class="ad-btn ad-btn-ghost"><i class="bi bi-search-heart-fill"></i> Recherche par conversation</a>
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
      <span class="ic-sm t-sage"><i class="bi bi-envelope-fill"></i></span>
    </div>
    <div class="num"><?= $total_msg ?></div>
    <div class="sub"><span>messages échangés</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Aujourd'hui</span>
      <span class="ic-sm t-honey"><i class="bi bi-broadcast"></i></span>
    </div>
    <div class="num"><?= $msg_today ?></div>
    <div class="sub"><span><?= date('d/m/Y') ?></span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Lus</span>
      <span class="ic-sm t-info"><i class="bi bi-check-all"></i></span>
    </div>
    <div class="num"><?= $msg_seen ?></div>
    <div class="sub"><span><?= $total_msg > 0 ? round($msg_seen / $total_msg * 100) : 0 ?>% lus</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Pièces jointes</span>
      <span class="ic-sm t-danger"><i class="bi bi-paperclip"></i></span>
    </div>
    <div class="num"><?= $msg_files ?></div>
    <div class="sub"><span>images + fichiers</span></div>
  </div>
</div>

<div class="ad-card">
  <div class="ad-card-head">
    <h6><i class="bi bi-list-ul"></i> Messages</h6>
    <span class="count"><?= count($messages) ?></span>
  </div>
  <div class="ad-card-body tight">
    <?php if (empty($messages)): ?>
      <div class="ad-empty">
        <div class="ic"><i class="bi bi-envelope-x"></i></div>
        <h5>Aucun message</h5>
      </div>
    <?php else: ?>
    <table class="ad-table ad-datatable">
      <thead>
        <tr>
          <th>#</th>
          <th>Conv.</th>
          <th>Expéditeur</th>
          <th>Contenu</th>
          <th>Type</th>
          <th>Date</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($messages as $msg):
          $type = $msg['type'] ?? 'text';
          $contentPreview = $type === 'text'
              ? mb_substr($msg['contenu'] ?? '', 0, 70, 'UTF-8')
              : ($type === 'image' ? '🖼  Image' : ($type === 'file' ? '📎 Fichier' : '—')); ?>
        <tr>
          <td style="color: var(--ink-soft); font-family: ui-monospace, monospace; font-size: .8rem;">#<?= (int)$msg['id_message'] ?></td>
          <td>
            <a href="<?= $BOCHAT ?>/chat.php?id=<?= (int)$msg['id_conversation'] ?>" style="font-family: ui-monospace, monospace; font-size: .8rem;">
              #<?= (int)$msg['id_conversation'] ?>
            </a>
          </td>
          <td><span style="font-weight:600;"><?= htmlspecialchars($msg['sender_prenom'] . ' ' . $msg['sender_nom']) ?></span></td>
          <td style="max-width: 320px;"><span style="color: var(--ink-2); font-size: .88rem;"><?= htmlspecialchars($contentPreview) ?><?= ($type === 'text' && mb_strlen($msg['contenu'], 'UTF-8') > 70) ? '…' : '' ?></span></td>
          <td><span class="ad-badge b-neutral"><?= htmlspecialchars($type) ?></span></td>
          <td style="color: var(--ink-mute); font-size: .82rem;"><?= htmlspecialchars($msg['date_envoi']) ?></td>
          <td>
            <?php if ($msg['is_seen']): ?>
              <span class="ad-badge b-active"><i class="bi bi-check-all"></i> Lu</span>
            <?php else: ?>
              <span class="ad-badge b-inactive"><i class="bi bi-clock-history"></i> Non lu</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="d-flex gap-1">
              <a href="<?= $BOCHAT ?>/chat.php?id=<?= (int)$msg['id_conversation'] ?>" class="ad-iconbtn open" title="Voir dans le chat"><i class="bi bi-eye-fill"></i></a>
              <a href="<?= $BOCHAT ?>/messages.php?action=delete&id=<?= (int)$msg['id_message'] ?>"
                 class="ad-iconbtn delete" title="Supprimer"
                 onclick="return confirm('Supprimer ce message ?');"><i class="bi bi-trash3"></i></a>
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
