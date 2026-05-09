<?php
require_once __DIR__ . '/auth_check_admin.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../controller/DemandeController.php';

$ctrl = new DemandeController();

// =============================================
// Handle delete (admin — bypass ownership)
// =============================================
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $r = $ctrl->deletePropositionAsAdmin((int)$_GET['id']);
    if ($r['success']) {
        $_SESSION['success'] = "Proposition supprimée.";
    } else {
        $_SESSION['error']   = implode('<br>', $r['errors']);
    }
    $redir = backoffice_url() . '/propositions_list.php'
           . (isset($_GET['demande_id']) ? '?demande_id=' . (int)$_GET['demande_id'] : '');
    header('Location: ' . $redir);
    exit;
}

// =============================================
// Filters
// =============================================
$filterDemande = isset($_GET['demande_id']) && $_GET['demande_id'] !== '' ? (int)$_GET['demande_id'] : null;
$search        = isset($_GET['search']) ? trim((string)$_GET['search']) : null;
if ($search === '') $search = null;

// =============================================
// Fetch propositions (PDOStatement → array)
// =============================================
$propStmt     = $ctrl->listAllPropositions('recent', $filterDemande, $search);
$propositions = $propStmt ? $propStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Map utilisateurs by id (single query, in-memory join for the freelancer cell)
$usersById = [];
try {
    $rs = $pdo->query("SELECT id, prenom, nom, photo FROM utilisateurs");
    if ($rs) {
        foreach ($rs->fetchAll(PDO::FETCH_ASSOC) as $u) {
            $usersById[(int)$u['id']] = $u;
        }
    }
} catch (Throwable $e) {}

// =============================================
// KPIs
// =============================================
$total = count($propositions);

$priceSum = 0.0;
foreach ($propositions as $p) {
    $priceSum += (float)($p['price'] ?? 0);
}
$avgPrice = $total > 0 ? $priceSum / $total : 0.0;

$uniqueDemandes    = count(array_unique(array_map(fn($p) => (int)($p['demande_id'] ?? 0), $propositions)));
$uniqueFreelancers = count(array_unique(array_filter(array_map(
    fn($p) => isset($p['user_id']) && $p['user_id'] !== null ? (int)$p['user_id'] : null,
    $propositions
))));

$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$pageTitle  = 'Propositions';
$pageActive = 'propositions_list';
$pageIcon   = 'bi-megaphone-fill';
$useDataTables = true;

include __DIR__ . '/_partials/header.php';
?>

<!-- Hero -->
<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
  <div>
    <span class="ad-eyebrow"><span class="dot"></span> Modération</span>
    <h2 style="font-size: 1.65rem; font-weight: 800; margin: 10px 0 4px;">Toutes les propositions</h2>
    <p style="color: var(--ink-mute); margin: 0; font-size: .92rem;">Toutes les propositions reçues sur SkillBridge — filtrez par demande, supprimez les propositions inappropriées.</p>
    <?php if ($filterDemande): ?>
      <div class="d-flex align-items-center gap-2 mt-2">
        <span class="ad-badge b-info"><i class="bi bi-funnel-fill"></i> Filtrées : demande #<?= (int)$filterDemande ?></span>
        <a href="<?= $BO ?>/propositions_list.php" class="ad-btn ad-btn-ghost ad-btn-sm">
          <i class="bi bi-x-lg"></i> Effacer le filtre
        </a>
      </div>
    <?php endif; ?>
  </div>
  <a href="<?= $BO ?>/demandes_list.php" class="ad-btn ad-btn-ghost"><i class="bi bi-file-earmark-text-fill"></i> Voir les demandes</a>
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
      <span class="ic-sm t-sage"><i class="bi bi-megaphone-fill"></i></span>
    </div>
    <div class="num"><?= $total ?></div>
    <div class="sub"><span><?= $filterDemande ? 'Filtrées par demande' : 'Toutes propositions' ?></span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Demandes uniques</span>
      <span class="ic-sm t-honey"><i class="bi bi-file-earmark-text-fill"></i></span>
    </div>
    <div class="num"><?= $uniqueDemandes ?></div>
    <div class="sub"><span>Demandes ayant reçu une proposition</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Freelancers actifs</span>
      <span class="ic-sm t-info"><i class="bi bi-people-fill"></i></span>
    </div>
    <div class="num"><?= $uniqueFreelancers ?></div>
    <div class="sub"><span>Comptes ayant proposé</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Prix moyen</span>
      <span class="ic-sm t-danger"><i class="bi bi-cash-coin"></i></span>
    </div>
    <div class="num"><?= number_format($avgPrice, 0, ',', ' ') ?> DT</div>
    <div class="sub"><span>Moyenne sur <?= $total ?> propositions</span></div>
  </div>
</div>

<div class="ad-card">
  <div class="ad-card-head">
    <h6><i class="bi bi-list-ul"></i> Liste des propositions</h6>
    <span class="count"><?= count($propositions) ?></span>
  </div>
  <div class="ad-card-body tight">
    <?php if (empty($propositions)): ?>
      <div class="ad-empty">
        <div class="ic"><i class="bi bi-megaphone"></i></div>
        <h5>Aucune proposition</h5>
        <p><?= $filterDemande ? "Cette demande n'a pas encore reçu de proposition." : "Aucune proposition n'a été envoyée sur la plateforme." ?></p>
      </div>
    <?php else: ?>
    <table class="ad-table ad-datatable">
      <thead>
        <tr>
          <th>#</th>
          <th>Demande</th>
          <th>Freelancer</th>
          <th>Prix</th>
          <th>Message</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($propositions as $p):
          $pid          = (int)($p['id'] ?? 0);
          $did          = (int)($p['demande_id'] ?? 0);
          $demandeTitle = (string)($p['demande_title'] ?? ('Demande #' . $did));
          $freelancerNm = trim((string)($p['freelancer_name'] ?? ''));
          $message      = (string)($p['message'] ?? '');
          $messagePrev  = mb_strimwidth(html_entity_decode($message, ENT_QUOTES, 'UTF-8'), 0, 80, '…', 'UTF-8');
          $price        = (float)($p['price'] ?? 0);
          $created      = (string)($p['created_at'] ?? '');
          $uid          = isset($p['user_id']) && $p['user_id'] !== null ? (int)$p['user_id'] : 0;
          $userRow      = $uid > 0 && isset($usersById[$uid]) ? $usersById[$uid] : null;
          $userFullName = $userRow ? trim(($userRow['prenom'] ?? '') . ' ' . ($userRow['nom'] ?? '')) : '';
          $initial      = $userRow ? strtoupper(mb_substr($userRow['prenom'] ?? '?', 0, 1, 'UTF-8'))
                                   : ($freelancerNm !== '' ? strtoupper(mb_substr($freelancerNm, 0, 1, 'UTF-8')) : '?');
      ?>
        <tr>
          <td style="color: var(--ink-soft); font-family: ui-monospace, monospace; font-size: .82rem;"><?= $pid ?></td>
          <td>
            <a href="<?= $BO ?>/propositions_list.php?demande_id=<?= $did ?>"
               style="font-weight: 700; color: var(--ink); text-decoration: none;">
              <?= htmlspecialchars($demandeTitle) ?>
            </a>
            <div style="color: var(--ink-soft); font-family: ui-monospace, monospace; font-size: .76rem; margin-top: 2px;">#<?= $did ?></div>
          </td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <?php if ($userRow && !empty($userRow['photo'])): ?>
                <img src="<?= htmlspecialchars($FO) ?>/assets/img/profile/<?= htmlspecialchars($userRow['photo']) ?>" class="ad-avatar" alt="">
              <?php else: ?>
                <span class="ad-avatar-fb"><?= htmlspecialchars($initial) ?></span>
              <?php endif; ?>
              <div style="line-height: 1.2;">
                <div style="font-weight: 600; font-size: .88rem;">
                  <?= $freelancerNm !== '' ? htmlspecialchars($freelancerNm) : '<span style="color:var(--ink-soft);">—</span>' ?>
                </div>
                <?php if ($userFullName !== '' && strcasecmp($userFullName, $freelancerNm) !== 0): ?>
                  <div style="color: var(--ink-mute); font-size: .76rem;">(<?= htmlspecialchars($userFullName) ?>)</div>
                <?php elseif (!$userRow): ?>
                  <div style="color: var(--ink-soft); font-size: .72rem;">compte non lié</div>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td style="font-weight: 700; color: var(--ink);"><?= number_format($price, 0, ',', ' ') ?> DT</td>
          <td style="color: var(--ink-mute); font-size: .88rem; max-width: 320px;">
            <?= $messagePrev !== '' ? htmlspecialchars($messagePrev) : '<span style="color:var(--ink-soft);">—</span>' ?>
          </td>
          <td style="color: var(--ink-mute); font-size: .82rem;">
            <?= $created !== '' ? htmlspecialchars(date('d/m/Y', strtotime($created))) : '—' ?>
          </td>
          <td>
            <div class="d-flex align-items-center gap-1">
              <a href="<?= $BO ?>/propositions_list.php?action=delete&id=<?= $pid ?><?= $filterDemande ? '&demande_id=' . (int)$filterDemande : '' ?>"
                 class="ad-iconbtn delete" title="Supprimer"
                 onclick="return confirm('Supprimer définitivement cette proposition ?');">
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
