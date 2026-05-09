<?php
require_once __DIR__ . '/auth_check_admin.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/utilisateur.php';

$utilisateurModel   = new Utilisateur($pdo);
$total_utilisateurs = $utilisateurModel->countAll();
$total_freelancers  = $utilisateurModel->countByRole('freelancer');
$total_clients      = $utilisateurModel->countByRole('client');
$total_admins       = $utilisateurModel->countByRole('admin');
$total_active       = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE is_active = 1")->fetchColumn();
$total_inactive     = $total_utilisateurs - $total_active;
$utilisateurs       = $utilisateurModel->readAll()->fetchAll(PDO::FETCH_ASSOC);

$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$pageTitle  = 'Liste utilisateurs';
$pageActive = 'users_list';
$pageIcon   = 'bi-people-fill';
$useDataTables = true;

include __DIR__ . '/_partials/header.php';
?>

<!-- Hero -->
<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
  <div>
    <span class="ad-eyebrow"><span class="dot"></span> Gestion utilisateurs</span>
    <h2 style="font-size: 1.65rem; font-weight: 800; margin: 10px 0 4px;">Tous les comptes</h2>
    <p style="color: var(--ink-mute); margin: 0; font-size: .92rem;">Gérez les utilisateurs SkillBridge — modifiez, désactivez, supprimez.</p>
  </div>
  <a href="<?= $BO ?>/search_utilisateurs.php" class="ad-btn ad-btn-ghost"><i class="bi bi-funnel-fill"></i> Filtrer par rôle</a>
</div>

<?php if ($success): ?>
  <div class="ad-alert success"><i class="bi bi-check-circle-fill"></i><span><?= htmlspecialchars($success) ?></span></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="ad-alert danger"><i class="bi bi-exclamation-triangle-fill"></i><span><?= htmlspecialchars($error) ?></span></div>
<?php endif; ?>

<!-- KPI grid (dashboard-style) -->
<div class="kpi-grid mb-4" style="display:grid; grid-template-columns: repeat(4, 1fr); gap: 14px;">
  <div class="kpi">
    <div class="head">
      <span class="lbl">Total</span>
      <span class="ic-sm t-sage"><i class="bi bi-people-fill"></i></span>
    </div>
    <div class="num"><?= $total_utilisateurs ?></div>
    <div class="sub"><span><?= $total_active ?> actifs · <?= $total_inactive ?> inactifs</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Freelancers</span>
      <span class="ic-sm t-honey"><i class="bi bi-tools"></i></span>
    </div>
    <div class="num"><?= $total_freelancers ?></div>
    <div class="sub"><span><?= $total_utilisateurs > 0 ? round($total_freelancers / $total_utilisateurs * 100) : 0 ?>% des comptes</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Clients</span>
      <span class="ic-sm t-info"><i class="bi bi-briefcase-fill"></i></span>
    </div>
    <div class="num"><?= $total_clients ?></div>
    <div class="sub"><span><?= $total_utilisateurs > 0 ? round($total_clients / $total_utilisateurs * 100) : 0 ?>% des comptes</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Admins</span>
      <span class="ic-sm t-danger"><i class="bi bi-shield-lock-fill"></i></span>
    </div>
    <div class="num"><?= $total_admins ?></div>
    <div class="sub"><span>Comptes privilégiés</span></div>
  </div>
</div>

<div class="ad-card">
  <div class="ad-card-head">
    <h6><i class="bi bi-list-ul"></i> Liste des utilisateurs</h6>
    <span class="count"><?= count($utilisateurs) ?></span>
  </div>
  <div class="ad-card-body tight">
    <?php if (empty($utilisateurs)): ?>
      <div class="ad-empty">
        <div class="ic"><i class="bi bi-people"></i></div>
        <h5>Aucun utilisateur</h5>
        <p>La table utilisateurs est vide.</p>
      </div>
    <?php else: ?>
    <table class="ad-table ad-datatable">
      <thead>
        <tr>
          <th>#</th>
          <th>Utilisateur</th>
          <th>Email</th>
          <th>Rôle</th>
          <th>Téléphone</th>
          <th>Statut</th>
          <th>Inscrit le</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($utilisateurs as $i => $user):
          $initial = strtoupper(mb_substr($user['prenom'] ?? '?', 0, 1, 'UTF-8')); ?>
        <tr>
          <td style="color: var(--ink-soft); font-family: ui-monospace, monospace; font-size: .82rem;"><?= $i + 1 ?></td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <?php if (!empty($user['photo'])): ?>
                <img src="<?= htmlspecialchars($FO) ?>/assets/img/profile/<?= htmlspecialchars($user['photo']) ?>" class="ad-avatar" alt="">
              <?php else: ?>
                <span class="ad-avatar-fb"><?= htmlspecialchars($initial) ?></span>
              <?php endif; ?>
              <span style="font-weight: 600;"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></span>
            </div>
          </td>
          <td style="color: var(--ink-mute);"><?= htmlspecialchars($user['email']) ?></td>
          <td><span class="ad-badge b-<?= htmlspecialchars($user['role']) ?>"><?= ucfirst(htmlspecialchars($user['role'])) ?></span></td>
          <td style="color: var(--ink-mute); font-size: .88rem;"><?= !empty($user['telephone']) ? htmlspecialchars($user['telephone']) : '<span style="color:var(--ink-soft);">—</span>' ?></td>
          <td>
            <?php if ($user['is_active']): ?>
              <span class="ad-badge b-active"><i class="bi bi-check-circle-fill"></i> Actif</span>
            <?php else: ?>
              <span class="ad-badge b-inactive"><i class="bi bi-x-circle-fill"></i> Inactif</span>
            <?php endif; ?>
          </td>
          <td style="color: var(--ink-mute); font-size: .82rem;"><?= date('d/m/Y', strtotime($user['date_inscription'])) ?></td>
          <td>
            <div class="d-flex align-items-center gap-1">
              <a href="<?= $BO ?>/edit_user.php?id=<?= (int)$user['id'] ?>" class="ad-iconbtn edit" title="Modifier"><i class="bi bi-pencil"></i></a>

              <form method="POST" action="<?= controller_url() ?>/utilisateurcontroller.php" style="display:inline;" onsubmit="return confirm('<?= $user['is_active'] ? "Désactiver ce compte ?" : "Activer ce compte ?" ?>');">
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                <input type="hidden" name="is_active" value="<?= $user['is_active'] ? 0 : 1 ?>">
                <button type="submit" class="ad-iconbtn toggle" title="<?= $user['is_active'] ? 'Désactiver' : 'Activer' ?>">
                  <i class="bi <?= $user['is_active'] ? 'bi-person-slash' : 'bi-person-check' ?>"></i>
                </button>
              </form>

              <form method="POST" action="<?= controller_url() ?>/utilisateurcontroller.php" style="display:inline;" onsubmit="return confirm('Supprimer définitivement cet utilisateur ?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                <button type="submit" class="ad-iconbtn delete" title="Supprimer"><i class="bi bi-trash3"></i></button>
              </form>
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
