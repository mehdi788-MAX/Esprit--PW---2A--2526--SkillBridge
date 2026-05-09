<?php
require_once __DIR__ . '/auth_check_admin.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../controller/DemandeController.php';

$ctrl = new DemandeController();

// =============================================
// Handle delete (admin can delete any demande)
// =============================================
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $d  = $ctrl->getDemande($id);
    if ($d) {
        // Bypass ownership by passing the demande's own user_id back to deleteDemande.
        $r = $ctrl->deleteDemande($id, (int)$d['user_id']);
        if ($r['success']) {
            $_SESSION['success'] = "Demande supprimée.";
        } else {
            $_SESSION['error'] = implode('<br>', $r['errors']);
        }
    } else {
        $_SESSION['error'] = "Demande introuvable.";
    }
    header('Location: ' . backoffice_url() . '/demandes_list.php');
    exit;
}

// =============================================
// Fetch demandes (PDOStatement → array)
// =============================================
$demandesStmt = $ctrl->listDemandes('recent', null);
$demandes     = $demandesStmt ? $demandesStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Map utilisateurs by id (single query, in-memory join for the author cell)
$usersById = [];
try {
    $rs = $pdo->query("SELECT id, prenom, nom, photo FROM utilisateurs");
    if ($rs) {
        foreach ($rs->fetchAll(PDO::FETCH_ASSOC) as $u) {
            $usersById[(int)$u['id']] = $u;
        }
    }
} catch (Throwable $e) {
    // silent — we degrade to "—" in the author cell
}

// =============================================
// KPIs
// =============================================
$today       = date('Y-m-d');
$total       = count($demandes);
$open        = 0;
$expired     = 0;
$totalBudget = 0.0;
foreach ($demandes as $d) {
    $deadline = (string)($d['deadline'] ?? '');
    if ($deadline !== '' && $deadline >= $today) {
        $open++;
    } else {
        $expired++;
    }
    $totalBudget += (float)($d['price'] ?? 0);
}

$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$pageTitle  = 'Demandes';
$pageActive = 'demandes_list';
$pageIcon   = 'bi-file-earmark-text-fill';
$useDataTables = true;

include __DIR__ . '/_partials/header.php';
?>

<!-- Hero -->
<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
  <div>
    <span class="ad-eyebrow"><span class="dot"></span> Modération</span>
    <h2 style="font-size: 1.65rem; font-weight: 800; margin: 10px 0 4px;">Toutes les demandes</h2>
    <p style="color: var(--ink-mute); margin: 0; font-size: .92rem;">Parcourez et modérez les demandes publiées sur SkillBridge — supprimez celles qui posent problème.</p>
  </div>
  <a href="<?= $BO ?>/propositions_list.php" class="ad-btn ad-btn-ghost"><i class="bi bi-megaphone-fill"></i> Voir les propositions</a>
</div>

<?php if ($success): ?>
  <div class="ad-alert success"><i class="bi bi-check-circle-fill"></i><span><?= htmlspecialchars($success) ?></span></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="ad-alert danger"><i class="bi bi-exclamation-triangle-fill"></i><span><?= $error ?></span></div>
<?php endif; ?>

<!-- KPI grid -->
<div class="kpi-grid mb-4" style="display:grid; grid-template-columns: repeat(4, 1fr); gap: 14px;">
  <div class="kpi">
    <div class="head">
      <span class="lbl">Total</span>
      <span class="ic-sm t-sage"><i class="bi bi-file-earmark-text-fill"></i></span>
    </div>
    <div class="num"><?= $total ?></div>
    <div class="sub"><span><?= $open ?> ouvertes · <?= $expired ?> expirées</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Ouvertes</span>
      <span class="ic-sm t-honey"><i class="bi bi-circle-fill"></i></span>
    </div>
    <div class="num"><?= $open ?></div>
    <div class="sub"><span>Deadline &ge; aujourd'hui</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Expirées</span>
      <span class="ic-sm t-danger"><i class="bi bi-x-circle-fill"></i></span>
    </div>
    <div class="num"><?= $expired ?></div>
    <div class="sub"><span><?= $total > 0 ? round($expired / $total * 100) : 0 ?>% des demandes</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Budget cumulé</span>
      <span class="ic-sm t-info"><i class="bi bi-cash-coin"></i></span>
    </div>
    <div class="num"><?= number_format($totalBudget, 0, ',', ' ') ?> DT</div>
    <div class="sub"><span>Total des demandes publiées</span></div>
  </div>
</div>

<div class="ad-card">
  <div class="ad-card-head">
    <h6><i class="bi bi-list-ul"></i> Liste des demandes</h6>
    <span class="count"><?= count($demandes) ?></span>
  </div>
  <div class="ad-card-body tight">
    <?php if (empty($demandes)): ?>
      <div class="ad-empty">
        <div class="ic"><i class="bi bi-file-earmark-text"></i></div>
        <h5>Aucune demande</h5>
        <p>Aucune demande n'a encore été publiée sur la plateforme.</p>
      </div>
    <?php else: ?>
    <table class="ad-table ad-datatable">
      <thead>
        <tr>
          <th>#</th>
          <th>Titre</th>
          <th>Auteur</th>
          <th>Budget</th>
          <th>Deadline</th>
          <th>Propositions</th>
          <th>Créée le</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($demandes as $d):
          $did       = (int)($d['id'] ?? 0);
          $title     = (string)($d['title'] ?? '');
          $descRaw   = (string)($d['description'] ?? '');
          $descPrev  = mb_strimwidth(html_entity_decode($descRaw, ENT_QUOTES, 'UTF-8'), 0, 60, '…', 'UTF-8');
          $price     = (float)($d['price'] ?? 0);
          $deadline  = (string)($d['deadline'] ?? '');
          $isExpired = ($deadline === '' || $deadline < $today);
          $created   = (string)($d['created_at'] ?? '');
          $uid       = isset($d['user_id']) && $d['user_id'] !== null ? (int)$d['user_id'] : 0;
          $author    = $uid > 0 && isset($usersById[$uid]) ? $usersById[$uid] : null;
          $propCount = $ctrl->countPropositionsByDemande($did);
          $initial   = $author ? strtoupper(mb_substr($author['prenom'] ?? '?', 0, 1, 'UTF-8')) : '?';
      ?>
        <tr>
          <td style="color: var(--ink-soft); font-family: ui-monospace, monospace; font-size: .82rem;"><?= $did ?></td>
          <td>
            <div style="font-weight: 700; color: var(--ink); line-height: 1.25;"><?= htmlspecialchars($title) ?></div>
            <?php if ($descPrev !== ''): ?>
              <div style="color: var(--ink-mute); font-size: .8rem; margin-top: 3px;"><?= htmlspecialchars($descPrev) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($author): ?>
              <div class="d-flex align-items-center gap-2">
                <?php if (!empty($author['photo'])): ?>
                  <img src="<?= htmlspecialchars($FO) ?>/assets/img/profile/<?= htmlspecialchars($author['photo']) ?>" class="ad-avatar" alt="">
                <?php else: ?>
                  <span class="ad-avatar-fb"><?= htmlspecialchars($initial) ?></span>
                <?php endif; ?>
                <span style="font-weight: 600; font-size: .88rem;"><?= htmlspecialchars(($author['prenom'] ?? '') . ' ' . ($author['nom'] ?? '')) ?></span>
              </div>
            <?php else: ?>
              <span style="color: var(--ink-soft);">— anonyme</span>
            <?php endif; ?>
          </td>
          <td style="font-weight: 700; color: var(--ink);"><?= number_format($price, 0, ',', ' ') ?> DT</td>
          <td>
            <?php if ($isExpired): ?>
              <span class="ad-badge b-inactive"><i class="bi bi-x-circle-fill"></i> <?= $deadline !== '' ? htmlspecialchars(date('d/m/Y', strtotime($deadline))) : '—' ?></span>
            <?php else: ?>
              <span class="ad-badge b-active"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars(date('d/m/Y', strtotime($deadline))) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($propCount > 0): ?>
              <span class="ad-badge b-info"><i class="bi bi-megaphone-fill"></i> <?= $propCount ?></span>
            <?php else: ?>
              <span class="ad-badge b-neutral">0</span>
            <?php endif; ?>
          </td>
          <td style="color: var(--ink-mute); font-size: .82rem;">
            <?= $created !== '' ? htmlspecialchars(date('d/m/Y', strtotime($created))) : '—' ?>
          </td>
          <td>
            <div class="d-flex align-items-center gap-1">
              <a href="<?= $BO ?>/propositions_list.php?demande_id=<?= $did ?>" class="ad-iconbtn open" title="Voir les propositions">
                <i class="bi bi-megaphone-fill"></i>
              </a>
              <a href="<?= $BO ?>/demandes_list.php?action=delete&id=<?= $did ?>"
                 class="ad-iconbtn delete" title="Supprimer"
                 onclick="return confirm('Supprimer définitivement cette demande ? Toutes ses propositions seront aussi perdues.');">
                <i class="bi bi-trash3"></i>
              </a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/_partials/footer.php'; ?>
