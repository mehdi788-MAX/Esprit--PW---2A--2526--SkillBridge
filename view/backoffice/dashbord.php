<?php
require_once __DIR__ . '/auth_check_admin.php';
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../model/utilisateur.php';

$utilisateurModel = new Utilisateur($pdo);

// ============ Date helpers (MySQL + SQLite portable) ============
$today      = date('Y-m-d');
$yesterday  = date('Y-m-d', strtotime('-1 day'));
$cutoff7    = date('Y-m-d 00:00:00', strtotime('-7 days'));
$cutoff14   = date('Y-m-d 00:00:00', strtotime('-14 days'));
$cutoff30   = date('Y-m-d 00:00:00', strtotime('-30 days'));

// ============ TOTALS ============
$total_users        = $utilisateurModel->countAll();
$total_freelancers  = $utilisateurModel->countByRole('freelancer');
$total_clients      = $utilisateurModel->countByRole('client');
$total_admins       = $utilisateurModel->countByRole('admin');
$total_active       = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE is_active = 1")->fetchColumn();
$total_inactive     = $total_users - $total_active;
$total_unverified   = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE is_verified = 0")->fetchColumn();
$total_conversations = (int)$pdo->query("SELECT COUNT(*) FROM conversations")->fetchColumn();
$total_messages      = (int)$pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();

// ============ TODAY KPIs ============
$today_signups = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE DATE(date_inscription) = '" . $today . "'")->fetchColumn();
$today_messages = (int)$pdo->query("SELECT COUNT(*) FROM messages WHERE DATE(date_envoi) = '" . $today . "'")->fetchColumn();
$today_conversations = (int)$pdo->query("SELECT COUNT(*) FROM conversations WHERE DATE(date_creation) = '" . $today . "'")->fetchColumn();

// Yesterday for delta hints
$y_signups  = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE DATE(date_inscription) = '" . $yesterday . "'")->fetchColumn();
$y_messages = (int)$pdo->query("SELECT COUNT(*) FROM messages    WHERE DATE(date_envoi)       = '" . $yesterday . "'")->fetchColumn();
function delta($cur, $prev) {
    if ($prev == 0) return $cur > 0 ? '+∞' : '0';
    $pct = round((($cur - $prev) / $prev) * 100);
    return ($pct >= 0 ? '+' : '') . $pct . '%';
}

// ============ 7-DAY KPIs ============
$stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE date_inscription >= :c");
$stmt->execute([':c' => $cutoff7]);
$signups_7d = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE date_envoi >= :c");
$stmt->execute([':c' => $cutoff7]);
$messages_7d = (int)$stmt->fetchColumn();

// Active conversations = at least 1 message in the last 7 days
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT id_conversation) FROM messages WHERE date_envoi >= :c");
$stmt->execute([':c' => $cutoff7]);
$active_conversations = (int)$stmt->fetchColumn();

// Unread notifications platform-wide
$total_unread_notifs = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn();

// Profils incomplets — pas de bio
$incomplete_profiles = 0;
try {
    $incomplete_profiles = (int)$pdo->query("
        SELECT COUNT(u.id) FROM utilisateurs u
        LEFT JOIN profils p ON p.utilisateur_id = u.id
        WHERE u.role IN ('freelancer','client') AND (p.bio IS NULL OR p.bio = '')
    ")->fetchColumn();
} catch (Throwable $e) {}

// ============ 14-DAY TREND ============
$days14 = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $days14[$d] = ['signups' => 0, 'messages' => 0];
}
$stmt = $pdo->prepare("SELECT DATE(date_inscription) AS day, COUNT(*) AS n FROM utilisateurs WHERE date_inscription >= :c GROUP BY DATE(date_inscription)");
$stmt->execute([':c' => $cutoff14]);
foreach ($stmt as $r) { if (isset($days14[$r['day']])) $days14[$r['day']]['signups'] = (int)$r['n']; }

$stmt = $pdo->prepare("SELECT DATE(date_envoi) AS day, COUNT(*) AS n FROM messages WHERE date_envoi >= :c GROUP BY DATE(date_envoi)");
$stmt->execute([':c' => $cutoff14]);
foreach ($stmt as $r) { if (isset($days14[$r['day']])) $days14[$r['day']]['messages'] = (int)$r['n']; }

$labels14   = json_encode(array_map(fn($d) => date('d/m', strtotime($d)), array_keys($days14)));
$signups14  = json_encode(array_values(array_map(fn($r) => $r['signups'],  $days14)));
$messages14 = json_encode(array_values(array_map(fn($r) => $r['messages'], $days14)));

// ============ Recent activity feed ============
$latest_users = $pdo->query("
    SELECT id, nom, prenom, email, role, photo, is_active, date_inscription
    FROM utilisateurs
    ORDER BY date_inscription DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$latest_messages = [];
try {
    $latest_messages = $pdo->query("
        SELECT m.id_message, m.id_conversation, m.contenu, m.type, m.date_envoi,
               u.prenom, u.nom, u.photo
        FROM messages m
        JOIN utilisateurs u ON u.id = m.sender_id
        ORDER BY m.id_message DESC
        LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$pageTitle  = 'Dashboard';
$pageActive = 'dashboard';
$pageIcon   = 'bi-speedometer2';

include __DIR__ . '/_partials/header.php';
?>

<style>
  .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 16px; }
  @media (max-width: 991.98px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
  .kpi {
    background: var(--paper); border: 1px solid var(--rule); border-radius: 14px;
    padding: 18px 18px 16px;
    transition: all .18s ease;
    display: flex; flex-direction: column; gap: 6px;
  }
  .kpi:hover { border-color: var(--sage); transform: translateY(-2px); box-shadow: 0 14px 28px -16px rgba(31,95,77,.18); }
  .kpi .head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
  .kpi .lbl { font-size: .68rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--ink-mute); }
  .kpi .ic-sm { width: 32px; height: 32px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; font-size: .92rem; flex-shrink: 0; }
  .kpi .ic-sm.t-sage   { background: var(--sage-soft);  color: var(--sage); }
  .kpi .ic-sm.t-honey  { background: var(--honey-soft); color: #92660A; }
  .kpi .ic-sm.t-info   { background: var(--info-soft);  color: var(--info); }
  .kpi .ic-sm.t-danger { background: var(--danger-soft); color: var(--danger); }
  .kpi .num { font-size: 1.85rem; font-weight: 800; color: var(--ink); line-height: 1; letter-spacing: -.02em; margin-top: 2px; }
  .kpi .sub { font-size: .78rem; color: var(--ink-soft); display: flex; align-items: center; gap: 6px; }
  .kpi .delta { font-family: ui-monospace, monospace; font-size: .72rem; padding: 2px 7px; border-radius: 999px; font-weight: 700; }
  .kpi .delta.up   { background: var(--sage-soft); color: var(--sage); }
  .kpi .delta.down { background: var(--danger-soft); color: var(--danger); }
  .kpi .delta.flat { background: var(--bg); color: var(--ink-mute); }

  .activity-feed { display: flex; flex-direction: column; }
  .activity-row { display: flex; gap: 12px; padding: 12px 22px; border-bottom: 1px solid var(--rule); align-items: center; transition: background .12s; }
  .activity-row:last-child { border-bottom: none; }
  .activity-row:hover { background: var(--bg); }
  .activity-row .ic { width: 36px; height: 36px; border-radius: 10px; display:inline-flex; align-items:center; justify-content:center; flex-shrink: 0; }
  .activity-row .body { flex: 1; min-width: 0; font-size: .9rem; }
  .activity-row .body strong { color: var(--ink); }
  .activity-row .preview { color: var(--ink-mute); font-size: .85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
  .activity-row .ts { font-size: .75rem; color: var(--ink-soft); flex-shrink: 0; }
</style>

<!-- Hero -->
<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
  <div>
    <span class="ad-eyebrow"><span class="dot"></span> Vue d'ensemble</span>
    <h2 style="font-size: 1.65rem; font-weight: 800; margin: 10px 0 4px;">Bonjour, <?= htmlspecialchars($adminFirstName) ?>.</h2>
    <p style="color: var(--ink-mute); margin: 0; font-size: .92rem;">Voici l'activité de la plateforme — pulse <strong style="color: var(--ink);">aujourd'hui</strong> et <strong style="color: var(--ink);">7 derniers jours</strong>.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= $BO ?>/users_list.php" class="ad-btn ad-btn-ghost"><i class="bi bi-people-fill"></i> Utilisateurs</a>
    <a href="<?= $BOCHAT ?>/conversations.php" class="ad-btn ad-btn-sage"><i class="bi bi-chat-square-dots-fill"></i> Conversations</a>
  </div>
</div>

<!-- Row 1 — Pulse aujourd'hui -->
<div class="kpi-grid">
  <div class="kpi">
    <div class="head">
      <span class="lbl">Inscriptions · auj.</span>
      <span class="ic-sm t-sage"><i class="bi bi-person-plus-fill"></i></span>
    </div>
    <div class="num"><?= $today_signups ?></div>
    <div class="sub">
      <span>vs hier (<?= $y_signups ?>)</span>
      <?php $d = delta($today_signups, $y_signups); $cls = $d === '0' || $d === '+0%' ? 'flat' : (str_starts_with($d, '-') ? 'down' : 'up'); ?>
      <span class="delta <?= $cls ?>"><?= $d ?></span>
    </div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Messages · auj.</span>
      <span class="ic-sm t-honey"><i class="bi bi-envelope-paper-fill"></i></span>
    </div>
    <div class="num"><?= $today_messages ?></div>
    <div class="sub">
      <span>vs hier (<?= $y_messages ?>)</span>
      <?php $d = delta($today_messages, $y_messages); $cls = $d === '0' || $d === '+0%' ? 'flat' : (str_starts_with($d, '-') ? 'down' : 'up'); ?>
      <span class="delta <?= $cls ?>"><?= $d ?></span>
    </div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Conv. actives · 7j</span>
      <span class="ic-sm t-info"><i class="bi bi-chat-dots-fill"></i></span>
    </div>
    <div class="num"><?= $active_conversations ?></div>
    <div class="sub"><span>au moins 1 message en 7 jours</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Notifs non lues</span>
      <span class="ic-sm t-danger"><i class="bi bi-bell-fill"></i></span>
    </div>
    <div class="num"><?= $total_unread_notifs ?></div>
    <div class="sub"><span>tous utilisateurs confondus</span></div>
  </div>
</div>

<!-- Row 2 — Santé / actions admin -->
<div class="kpi-grid">
  <div class="kpi">
    <div class="head">
      <span class="lbl">Total utilisateurs</span>
      <span class="ic-sm t-sage"><i class="bi bi-people-fill"></i></span>
    </div>
    <div class="num"><?= $total_users ?></div>
    <div class="sub"><span><?= $signups_7d ?> nouveaux sur 7 jours</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Comptes désactivés</span>
      <span class="ic-sm t-danger"><i class="bi bi-person-fill-slash"></i></span>
    </div>
    <div class="num"><?= $total_inactive ?></div>
    <div class="sub"><span><?= $total_users > 0 ? round(($total_inactive / $total_users) * 100) : 0 ?>% du parc</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Email non vérifié</span>
      <span class="ic-sm t-honey"><i class="bi bi-envelope-exclamation-fill"></i></span>
    </div>
    <div class="num"><?= $total_unverified ?></div>
    <div class="sub"><span>à valider ou relancer</span></div>
  </div>
  <div class="kpi">
    <div class="head">
      <span class="lbl">Profils incomplets</span>
      <span class="ic-sm t-info"><i class="bi bi-person-vcard-fill"></i></span>
    </div>
    <div class="num"><?= $incomplete_profiles ?></div>
    <div class="sub"><span>bio manquante</span></div>
  </div>
</div>

<!-- Row 3 — Trend chart + role donut -->
<div class="row g-3 mb-3">
  <div class="col-xl-8">
    <div class="ad-card h-100">
      <div class="ad-card-head">
        <h6><i class="bi bi-graph-up-arrow"></i> Activité — 14 derniers jours</h6>
        <span class="count">14j</span>
      </div>
      <div class="ad-card-body">
        <canvas id="trendChart" height="110"></canvas>
        <div class="d-flex gap-3 justify-content-center mt-3" style="font-size:.82rem; color: var(--ink-mute);">
          <span><i class="bi bi-circle-fill" style="color:#1F5F4D;"></i> Inscriptions <strong style="color:var(--ink);"><?= $signups_7d ?></strong> /7j</span>
          <span><i class="bi bi-circle-fill" style="color:#F5C842;"></i> Messages <strong style="color:var(--ink);"><?= $messages_7d ?></strong> /7j</span>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="ad-card h-100">
      <div class="ad-card-head"><h6><i class="bi bi-pie-chart-fill"></i> Répartition rôles</h6></div>
      <div class="ad-card-body">
        <canvas id="rolesChart"></canvas>
        <div class="mt-3 small d-flex flex-wrap gap-3 justify-content-center">
          <span><i class="bi bi-circle-fill" style="color:#1F5F4D;"></i> Clients (<?= $total_clients ?>)</span>
          <span><i class="bi bi-circle-fill" style="color:#F5C842;"></i> Freelancers (<?= $total_freelancers ?>)</span>
          <span><i class="bi bi-circle-fill" style="color:#DC2626;"></i> Admins (<?= $total_admins ?>)</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Row 4 — Latest signups + active donut + activity -->
<div class="row g-3 mb-3">
  <div class="col-xl-8">
    <div class="ad-card h-100">
      <div class="ad-card-head">
        <h6><i class="bi bi-person-plus-fill"></i> Derniers inscrits</h6>
        <a href="<?= $BO ?>/users_list.php" class="ad-btn ad-btn-ghost ad-btn-sm">Tout voir <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="ad-card-body tight">
        <table class="ad-table">
          <thead><tr><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Statut</th><th>Inscrit</th></tr></thead>
          <tbody>
          <?php foreach ($latest_users as $user):
              $initial = strtoupper(mb_substr($user['prenom'] ?? '?', 0, 1, 'UTF-8')); ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <?php if (!empty($user['photo'])): ?>
                    <img src="<?= htmlspecialchars($FO) ?>/assets/img/profile/<?= htmlspecialchars($user['photo']) ?>" class="ad-avatar" alt="">
                  <?php else: ?>
                    <span class="ad-avatar-fb"><?= htmlspecialchars($initial) ?></span>
                  <?php endif; ?>
                  <span style="font-weight:600;"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></span>
                </div>
              </td>
              <td style="color: var(--ink-mute); font-size:.88rem;"><?= htmlspecialchars($user['email']) ?></td>
              <td><span class="ad-badge b-<?= htmlspecialchars($user['role']) ?>"><?= ucfirst(htmlspecialchars($user['role'])) ?></span></td>
              <td>
                <?php if ($user['is_active']): ?>
                  <span class="ad-badge b-active"><i class="bi bi-check-circle-fill"></i> Actif</span>
                <?php else: ?>
                  <span class="ad-badge b-inactive"><i class="bi bi-x-circle-fill"></i> Inactif</span>
                <?php endif; ?>
              </td>
              <td style="color: var(--ink-mute); font-size: .82rem;"><?= date('d/m H:i', strtotime($user['date_inscription'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="ad-card h-100">
      <div class="ad-card-head"><h6><i class="bi bi-toggles"></i> Comptes actifs</h6></div>
      <div class="ad-card-body">
        <canvas id="activeChart"></canvas>
        <div class="mt-3 small d-flex flex-wrap gap-3 justify-content-center">
          <span><i class="bi bi-circle-fill" style="color:#1F5F4D;"></i> Actifs (<?= $total_active ?>)</span>
          <span><i class="bi bi-circle-fill" style="color:#DC2626;"></i> Inactifs (<?= $total_inactive ?>)</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Row 5 — Recent messages feed -->
<?php if (!empty($latest_messages)): ?>
<div class="row g-3">
  <div class="col-12">
    <div class="ad-card">
      <div class="ad-card-head">
        <h6><i class="bi bi-chat-text-fill"></i> Derniers messages</h6>
        <a href="<?= $BOCHAT ?>/messages.php" class="ad-btn ad-btn-ghost ad-btn-sm">Modération <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="ad-card-body tight">
        <div class="activity-feed">
          <?php foreach ($latest_messages as $m):
              $type = $m['type'] ?? 'text';
              $preview = $type === 'text'
                  ? mb_substr($m['contenu'] ?? '', 0, 90, 'UTF-8')
                  : ($type === 'image' ? '🖼  Image partagée' : '📎 Fichier partagé');
              $bgIcon = $type === 'text' ? 't-sage' : ($type === 'image' ? 't-honey' : 't-info');
              $iconCls = $type === 'text' ? 'bi-chat-fill' : ($type === 'image' ? 'bi-image-fill' : 'bi-paperclip');
              $initial = strtoupper(mb_substr($m['prenom'] ?? '?', 0, 1, 'UTF-8')); ?>
            <a href="<?= $BOCHAT ?>/chat.php?id=<?= (int)$m['id_conversation'] ?>" class="activity-row" style="text-decoration:none; color:inherit;">
              <span class="ic <?= $bgIcon ?> ic-sm" style="background: var(--<?= $type === 'image' ? 'honey-soft' : ($type === 'file' ? 'info-soft' : 'sage-soft') ?>); color: var(--<?= $type === 'image' ? 'honey-d' : ($type === 'file' ? 'info' : 'sage') ?>);"><i class="bi <?= $iconCls ?>"></i></span>
              <div class="body">
                <div><strong><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></strong> <span style="color:var(--ink-soft);">→ conv #<?= (int)$m['id_conversation'] ?></span></div>
                <div class="preview"><?= htmlspecialchars($preview) ?><?= ($type === 'text' && mb_strlen($m['contenu'], 'UTF-8') > 90) ? '…' : '' ?></div>
              </div>
              <span class="ts"><?= date('d/m H:i', strtotime($m['date_envoi'])) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
  Chart.defaults.font.family = "'Manrope', system-ui, sans-serif";
  Chart.defaults.color = '#5C5C5C';
  Chart.defaults.borderColor = '#E8E2D5';

  // 14-day activity (dual line)
  new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
      labels: <?= $labels14 ?>,
      datasets: [
        {
          label: 'Inscriptions',
          data: <?= $signups14 ?>,
          borderColor: '#1F5F4D',
          backgroundColor: 'rgba(31,95,77,0.10)',
          borderWidth: 2.5,
          pointBackgroundColor: '#1F5F4D',
          pointRadius: 3,
          pointHoverRadius: 6,
          fill: true,
          tension: 0.35
        },
        {
          label: 'Messages',
          data: <?= $messages14 ?>,
          borderColor: '#E0B033',
          backgroundColor: 'rgba(245,200,66,0.18)',
          borderWidth: 2.5,
          pointBackgroundColor: '#F5C842',
          pointBorderColor: '#E0B033',
          pointRadius: 3,
          pointHoverRadius: 6,
          fill: true,
          tension: 0.35,
          yAxisID: 'y1'
        }
      ]
    },
    options: {
      responsive: true,
      interaction: { intersect: false, mode: 'index' },
      plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0F0F0F', titleColor: '#F5C842', bodyColor: '#fff', padding: 10, cornerRadius: 8 } },
      scales: {
        y:  { beginAtZero: true, ticks: { stepSize: 1, color: '#1F5F4D' }, grid: { color: '#E8E2D5' }, title: { display: false } },
        y1: { beginAtZero: true, position: 'right', ticks: { stepSize: 1, color: '#E0B033' }, grid: { display: false } },
        x:  { grid: { display: false } }
      }
    }
  });

  new Chart(document.getElementById('rolesChart'), {
    type: 'doughnut',
    data: {
      labels: ['Clients', 'Freelancers', 'Admins'],
      datasets: [{
        data: [<?= $total_clients ?>, <?= $total_freelancers ?>, <?= $total_admins ?>],
        backgroundColor: ['#1F5F4D', '#F5C842', '#DC2626'],
        borderWidth: 3,
        borderColor: '#FFFFFF'
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0F0F0F', titleColor: '#F5C842', bodyColor: '#fff', padding: 10, cornerRadius: 8 } },
      cutout: '64%'
    }
  });

  new Chart(document.getElementById('activeChart'), {
    type: 'doughnut',
    data: {
      labels: ['Actifs', 'Inactifs'],
      datasets: [{
        data: [<?= $total_active ?>, <?= $total_inactive ?>],
        backgroundColor: ['#1F5F4D', '#DC2626'],
        borderWidth: 3,
        borderColor: '#FFFFFF'
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0F0F0F', titleColor: '#F5C842', bodyColor: '#fff', padding: 10, cornerRadius: 8 } },
      cutout: '70%'
    }
  });
</script>

<?php include __DIR__ . '/_partials/footer.php'; ?>
