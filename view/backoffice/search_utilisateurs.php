<?php
require_once __DIR__ . '/auth_check_admin.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/utilisateur.php';

$utilisateurModel = new Utilisateur($pdo);
$list = null;
$role = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role'])) {
    $role = $_POST['role'];
    $list = $utilisateurModel->readByRoleWithProfil($role);
}

$pageTitle  = 'Recherche par rôle';
$pageActive = 'users_search';
$pageIcon   = 'bi-search';
$useDataTables = false;

include __DIR__ . '/_partials/header.php';
?>

<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
  <div>
    <span class="ad-eyebrow"><span class="dot"></span> Filtrer</span>
    <h2 style="font-size: 1.5rem; font-weight: 800; margin: 10px 0 4px;">Recherche par rôle</h2>
    <p style="color: var(--ink-mute); margin:0; font-size:.9rem;">Listez les utilisateurs d'un rôle spécifique avec leurs informations de profil.</p>
  </div>
</div>

<div class="ad-card">
  <div class="ad-card-head">
    <h6><i class="bi bi-funnel-fill"></i> Sélectionner un rôle</h6>
  </div>
  <div class="ad-card-body">
    <form action="<?= $BO ?>/search_utilisateurs.php" method="POST" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label for="role" class="ad-form-label">Rôle</label>
        <select name="role" id="role" class="ad-form-select">
          <option value="freelancer" <?= $role === 'freelancer' ? 'selected' : '' ?>>Freelancer</option>
          <option value="client"     <?= $role === 'client'     ? 'selected' : '' ?>>Client</option>
          <option value="admin"      <?= $role === 'admin'      ? 'selected' : '' ?>>Admin</option>
        </select>
      </div>
      <div class="col-md-3">
        <button type="submit" name="search" value="1" class="ad-btn ad-btn-sage" style="width:100%;">
          <i class="bi bi-search"></i> Rechercher
        </button>
      </div>
    </form>
  </div>
</div>

<?php if ($list !== null):
    // readByRoleWithProfil() returns an already-fetched array, not a PDOStatement
    $rows = is_array($list) ? $list : $list->fetchAll(PDO::FETCH_ASSOC); ?>
  <div class="ad-card">
    <div class="ad-card-head">
      <h6>
        <i class="bi bi-list-ul"></i> Résultats —
        <span class="ad-badge b-<?= htmlspecialchars($role) ?>" style="margin-left:6px;"><?= ucfirst(htmlspecialchars($role)) ?></span>
      </h6>
      <span class="count"><?= count($rows) ?> trouvé<?= count($rows) > 1 ? 's' : '' ?></span>
    </div>
    <div class="ad-card-body tight">
      <?php if (empty($rows)): ?>
        <div class="ad-empty">
          <div class="ic"><i class="bi bi-search"></i></div>
          <h5>Aucun utilisateur</h5>
          <p>Pas de comptes correspondant au rôle <strong><?= ucfirst(htmlspecialchars($role)) ?></strong>.</p>
        </div>
      <?php else: ?>
      <table class="ad-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Utilisateur</th>
            <th>Email</th>
            <th>Bio</th>
            <th>Compétences</th>
            <th>Localisation</th>
            <th>Inscrit</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $i => $user):
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
                <div>
                  <div style="font-weight:600;"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>
                  <?php if (!empty($user['telephone'])): ?>
                    <small style="color: var(--ink-soft); font-size: .75rem;"><i class="bi bi-telephone-fill"></i> <?= htmlspecialchars($user['telephone']) ?></small>
                  <?php endif; ?>
                </div>
              </div>
            </td>
            <td style="color: var(--ink-mute);"><?= htmlspecialchars($user['email']) ?></td>
            <td style="max-width: 200px;">
              <?php if (!empty($user['bio'])): ?>
                <span style="font-size: .85rem;"><?= htmlspecialchars(mb_substr($user['bio'], 0, 80, 'UTF-8')) ?><?= mb_strlen($user['bio'], 'UTF-8') > 80 ? '…' : '' ?></span>
              <?php else: ?>
                <span style="color: var(--ink-soft); font-style: italic; font-size: .82rem;">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($user['competences'])): ?>
                <?php foreach (array_slice(array_filter(array_map('trim', explode(',', $user['competences']))), 0, 4) as $comp): ?>
                  <span class="ad-chip"><?= htmlspecialchars($comp) ?></span>
                <?php endforeach; ?>
              <?php else: ?>
                <span style="color: var(--ink-soft); font-style: italic; font-size: .82rem;">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($user['localisation'])): ?>
                <span style="font-size:.88rem;"><i class="bi bi-geo-alt-fill" style="color:var(--sage);"></i> <?= htmlspecialchars($user['localisation']) ?></span>
              <?php else: ?>
                <span style="color: var(--ink-soft); font-style: italic; font-size: .82rem;">—</span>
              <?php endif; ?>
            </td>
            <td style="color: var(--ink-mute); font-size: .82rem;"><?= date('d/m/Y', strtotime($user['date_inscription'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/_partials/footer.php'; ?>
