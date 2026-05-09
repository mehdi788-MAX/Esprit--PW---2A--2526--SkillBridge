<?php
require_once __DIR__ . '/auth_check_admin.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/utilisateur.php';

$utilisateurModel = new Utilisateur($pdo);
$utilisateurs     = $utilisateurModel->readAllWithProfil()->fetchAll(PDO::FETCH_ASSOC);

$total = count($utilisateurs);

// Profile completeness KPIs — counted only on freelancer/client (admins skipped, they don't need profiles)
$counted_total = 0;
$with_bio = $with_skills = $with_loc = $with_site = $fully_complete = 0;
foreach ($utilisateurs as $u) {
    if (!in_array($u['role'], ['freelancer', 'client'], true)) continue;
    $counted_total++;
    $hasBio   = !empty($u['bio']);
    $hasSkill = !empty($u['competences']);
    $hasLoc   = !empty($u['localisation']);
    $hasSite  = !empty($u['site_web']);
    if ($hasBio)   $with_bio++;
    if ($hasSkill) $with_skills++;
    if ($hasLoc)   $with_loc++;
    if ($hasSite)  $with_site++;
    if ($hasBio && $hasSkill && $hasLoc && $hasSite) $fully_complete++;
}
$pct = fn($n) => $counted_total > 0 ? round($n / $counted_total * 100) : 0;

$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$pageTitle  = 'Profils complets';
$pageActive = 'users_profils';
$pageIcon   = 'bi-person-vcard-fill';
$useDataTables = true;

include __DIR__ . '/_partials/header.php';
?>

<!-- Hero -->
<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
  <div>
    <span class="ad-eyebrow"><span class="dot"></span> Vue jointe</span>
    <h2 style="font-size: 1.65rem; font-weight: 800; margin: 10px 0 4px;">Utilisateurs &amp; profils</h2>
    <p style="color: var(--ink-mute); margin:0; font-size:.92rem;">Jointure <code>utilisateurs</code> ⟶ <code>profils</code> — état de complétude pour <strong style="color: var(--ink);"><?= $counted_total ?></strong> profils freelancer/client.</p>
  </div>
  <a href="<?= $BO ?>/users_list.php" class="ad-btn ad-btn-ghost"><i class="bi bi-arrow-left"></i> Retour à la liste</a>
</div>

<?php if ($success): ?>
  <div class="ad-alert success"><i class="bi bi-check-circle-fill"></i><span><?= htmlspecialchars($success) ?></span></div>
<?php endif; ?>

<!-- KPI grid — profile completeness -->
<div class="kpi-grid mb-4" style="display:grid; grid-template-columns: repeat(4, 1fr); gap: 14px;">
  <div class="kpi">
    <div class="head">
      <span class="lbl">100% complets</span>
      <span class="ic-sm t-sage"><i class="bi bi-check-circle-fill"></i></span>
    </div>
    <div class="num"><?= $fully_complete ?></div>
    <div class="sub"><span><?= $pct($fully_complete) ?>% des profils — bio, compétences, localisation et site web</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Avec bio</span>
      <span class="ic-sm t-info"><i class="bi bi-card-text"></i></span>
    </div>
    <div class="num"><?= $with_bio ?></div>
    <div class="sub"><span><?= $pct($with_bio) ?>% renseignés</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Avec compétences</span>
      <span class="ic-sm t-honey"><i class="bi bi-stars"></i></span>
    </div>
    <div class="num"><?= $with_skills ?></div>
    <div class="sub"><span><?= $pct($with_skills) ?>% renseignés</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Avec localisation</span>
      <span class="ic-sm t-danger"><i class="bi bi-geo-alt-fill"></i></span>
    </div>
    <div class="num"><?= $with_loc ?></div>
    <div class="sub"><span><?= $pct($with_loc) ?>% renseignés</span></div>
  </div>
</div>

<div class="ad-card">
  <div class="ad-card-head">
    <h6><i class="bi bi-table"></i> Profils détaillés</h6>
    <span class="count"><?= count($utilisateurs) ?></span>
  </div>
  <div class="ad-card-body tight">
    <?php if (empty($utilisateurs)): ?>
      <div class="ad-empty">
        <div class="ic"><i class="bi bi-person-x"></i></div>
        <h5>Aucun profil</h5>
      </div>
    <?php else: ?>
    <table class="ad-table ad-datatable">
      <thead>
        <tr>
          <th>#</th>
          <th>Utilisateur</th>
          <th>Email</th>
          <th>Rôle</th>
          <th>Bio</th>
          <th>Compétences</th>
          <th>Localisation</th>
          <th>Site Web</th>
          <th>Inscrit</th>
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
              <div>
                <div style="font-weight: 600;"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>
                <?php if (!empty($user['telephone'])): ?>
                  <small style="color: var(--ink-soft); font-size: .75rem;"><i class="bi bi-telephone-fill"></i> <?= htmlspecialchars($user['telephone']) ?></small>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td style="color: var(--ink-mute);"><?= htmlspecialchars($user['email']) ?></td>
          <td><span class="ad-badge b-<?= htmlspecialchars($user['role']) ?>"><?= ucfirst(htmlspecialchars($user['role'])) ?></span></td>
          <td style="max-width: 200px;">
            <?php if (!empty($user['bio'])): ?>
              <span style="color: var(--ink-2); font-size: .85rem;"><?= htmlspecialchars(mb_substr($user['bio'], 0, 80, 'UTF-8')) ?><?= mb_strlen($user['bio'], 'UTF-8') > 80 ? '…' : '' ?></span>
            <?php else: ?>
              <span style="color: var(--ink-soft); font-style: italic; font-size: .82rem;">Non renseigné</span>
            <?php endif; ?>
          </td>
          <td style="max-width: 200px;">
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
              <span style="color: var(--ink); font-size: .88rem;"><i class="bi bi-geo-alt-fill" style="color:var(--sage);"></i> <?= htmlspecialchars($user['localisation']) ?></span>
            <?php else: ?>
              <span style="color: var(--ink-soft); font-style: italic; font-size: .82rem;">—</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($user['site_web'])): ?>
              <a href="<?= htmlspecialchars($user['site_web']) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> Voir</a>
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

<?php include __DIR__ . '/_partials/footer.php'; ?>
