<?php
require_once 'auth_check.php';
require_once '../../../config.php';
require_once '../../../controller/DemandeController.php';

$BASE = base_url();
$ctrl = new DemandeController();

// Role check
$role = strtolower($_SESSION['user_role'] ?? '');
if ($role !== 'freelancer') {
    $_SESSION['error'] = "Cette page est réservée aux freelancers.";
    header('Location: profil.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Inline delete handler (POST + confirm)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $pid = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($pid > 0) {
        $res = $ctrl->deleteProposition($pid, $userId);
        if ($res['success']) {
            header('Location: mes_propositions.php?deleted=1');
            exit;
        }
        $_SESSION['error'] = implode(' ', $res['errors']);
    }
    header('Location: mes_propositions.php');
    exit;
}

// Filters
$sort = ($_GET['sort'] ?? 'recent') === 'oldest' ? 'oldest' : 'recent';

$stmt = $ctrl->listPropositionsByUser($userId, $sort);
$rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

// Need deadline for chips — fetch demande deadlines for these propositions
$dmap = [];
$dIds = array_unique(array_map(function($r){ return (int)$r['demande_id']; }, $rows));
if (!empty($dIds)) {
    $in = implode(',', array_map('intval', $dIds));
    $dStmt = $pdo->query("SELECT id, deadline FROM demandes WHERE id IN ($in)");
    foreach ($dStmt->fetchAll(PDO::FETCH_ASSOC) as $d) {
        $dmap[(int)$d['id']] = $d['deadline'];
    }
}

// KPIs
$total = count($rows);
$uniqueDemandes = count(array_unique(array_map(function($r){ return (int)$r['demande_id']; }, $rows)));
$avgPrice = 0;
$mostRecent = null;
if ($total > 0) {
    $sum = 0;
    foreach ($rows as $r) {
        $sum += (float)$r['price'];
        if ($mostRecent === null || strtotime($r['created_at']) > strtotime($mostRecent)) {
            $mostRecent = $r['created_at'];
        }
    }
    $avgPrice = $sum / $total;
}

// Helpers
function relativeTime($dt) {
    $ts = strtotime($dt); $diff = time() - $ts;
    if ($diff < 60) return "à l'instant";
    if ($diff < 3600) { $m = floor($diff/60); return "il y a {$m} min"; }
    if ($diff < 86400) { $h = floor($diff/3600); return "il y a {$h} h"; }
    $d = floor($diff/86400);
    if ($d < 30) return "il y a {$d} j";
    return "il y a " . floor($d/30) . " mois";
}
function isDeadlineSoon($d) {
    $today = strtotime(date('Y-m-d'));
    $dl = strtotime($d);
    return ($dl - $today) <= 7 * 86400 && $dl >= $today;
}

$flashCreated = isset($_GET['created']);
$flashUpdated = isset($_GET['updated']);
$flashDeleted = isset($_GET['deleted']);
$flashError   = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

$navName = trim(explode(' ', trim($_SESSION['user_nom'] ?? ''))[0] ?? '') ?: 'Profil';
$navAvatarSrc = 'https://ui-avatars.com/api/?name=' . urlencode($navName) . '&background=1F5F4D&color=fff&bold=true&size=80';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Mes propositions — SkillBridge</title>
  <link href="assets/img/favicon.png" rel="icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <style>
    :root{--bg:#F7F4ED;--paper:#FFF;--ink:#0F0F0F;--ink-2:#2A2A2A;--ink-mute:#5C5C5C;--ink-soft:#A3A3A3;--rule:#E8E2D5;--sage:#1F5F4D;--sage-d:#134438;--sage-soft:#E8F0EC;--honey:#F5C842;--honey-d:#E0B033;--honey-soft:#FBF1D0;}
    *,*::before,*::after{box-sizing:border-box}
    body{font-family:'Manrope',system-ui,sans-serif;background:var(--bg);color:var(--ink);letter-spacing:-.005em;-webkit-font-smoothing:antialiased;margin:0}
    h1,h2,h3,h4,h5{font-family:'Manrope',sans-serif;font-weight:700;letter-spacing:-.022em;color:var(--ink)}
    .display-x{font-size:clamp(2rem,3.6vw,2.8rem);line-height:1.05;font-weight:800;letter-spacing:-.025em}
    .lead-x{font-size:1rem;line-height:1.55;color:var(--ink-mute);font-weight:400}
    .accent{font-style:italic;font-weight:700;color:var(--sage)}
    .eyebrow{display:inline-flex;align-items:center;gap:8px;font-size:.8rem;font-weight:600;color:var(--sage);padding:6px 12px;background:var(--sage-soft);border-radius:999px}
    .eyebrow .dot{width:6px;height:6px;border-radius:50%;background:var(--sage)}
    .eyebrow.honey{color:#92660A;background:var(--honey-soft)} .eyebrow.honey .dot{background:var(--honey-d)}
    .sb-header{position:sticky;top:0;z-index:100;background:rgba(247,244,237,.85);backdrop-filter:blur(14px);border-bottom:1px solid var(--rule)}
    .sb-header .container{display:flex;align-items:center;justify-content:space-between;padding:14px 0}
    .sb-logo{display:inline-flex;align-items:center;text-decoration:none;color:var(--ink)} .sb-logo .logo-img{height:38px;width:auto;display:block}
    .sb-nav{display:flex;align-items:center;gap:28px}
    .sb-nav a{color:var(--ink-mute);text-decoration:none;font-weight:500;font-size:.92rem;transition:color .15s}
    .sb-nav a:hover,.sb-nav a.active{color:var(--ink)} .sb-nav a.active{color:var(--sage)}
    .sb-cta{display:inline-flex;align-items:center;gap:8px;background:var(--ink);color:var(--bg);padding:10px 20px;border-radius:999px;text-decoration:none;font-weight:600;font-size:.92rem;transition:all .2s}
    .sb-cta:hover{background:var(--sage);color:var(--paper);transform:translateY(-1px)}
    .sb-bell-btn{width:42px;height:42px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:transparent;color:var(--ink);position:relative} .sb-bell-btn:hover{background:var(--paper)}
    .sb-profile-chip{display:inline-flex;align-items:center;gap:8px;padding:4px 14px 4px 4px;border-radius:999px;background:var(--paper);border:1px solid var(--rule);color:var(--ink);text-decoration:none;font-weight:600;font-size:.9rem;transition:all .2s}
    .sb-profile-chip:hover{border-color:var(--sage)} .sb-profile-chip .avatar{width:30px;height:30px;border-radius:50%;object-fit:cover}
    @media (max-width:991.98px){.sb-nav{display:none}}
    .page-bg{position:relative;overflow:hidden;min-height:calc(100vh - 64px);padding:48px 0 80px}
    .blob{position:absolute;border-radius:50%;filter:blur(60px);opacity:.55;pointer-events:none;z-index:0}
    .blob.sage{background:var(--sage-soft)} .blob.honey{background:var(--honey-soft)}
    .blob-1{width:380px;height:380px;left:-120px;top:-80px} .blob-2{width:340px;height:340px;right:-100px;bottom:200px}
    .page-bg .container{position:relative;z-index:1}
    .auth-card{background:var(--paper);border:1px solid var(--rule);border-radius:22px;padding:24px;box-shadow:0 30px 60px -25px rgba(31,95,77,.18)}
    .form-control,.form-select{width:100%;border-radius:12px;border:1px solid var(--rule);padding:11px 14px;font-size:.95rem;background:var(--paper);color:var(--ink);transition:border-color .2s,box-shadow .2s;font-family:'Manrope',sans-serif}
    .form-control:focus,.form-select:focus{outline:none;border-color:var(--sage);box-shadow:0 0 0 4px rgba(31,95,77,.12)}
    .btn-sage{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 20px;border-radius:12px;border:none;background:var(--sage);color:var(--paper);font-weight:700;font-size:.95rem;cursor:pointer;transition:all .2s;text-decoration:none}
    .btn-sage:hover{background:var(--sage-d);transform:translateY(-2px);color:var(--paper)}
    .btn-ghost{display:inline-flex;align-items:center;gap:8px;background:var(--paper);color:var(--ink);padding:9px 14px;border-radius:10px;border:1px solid var(--rule);text-decoration:none;font-weight:600;font-size:.85rem;transition:all .2s;cursor:pointer}
    .btn-ghost:hover{border-color:var(--sage);color:var(--sage)}
    .btn-danger{display:inline-flex;align-items:center;gap:8px;background:#FEF2F2;color:#991B1B;padding:9px 14px;border-radius:10px;border:1px solid #FECACA;font-weight:600;font-size:.85rem;cursor:pointer;transition:all .2s}
    .btn-danger:hover{background:#DC2626;color:#FFF;border-color:#DC2626}
    .sb-footer{background:var(--ink);color:rgba(255,255,255,.65);padding:22px 0;font-size:.88rem;text-align:center} .sb-footer strong{color:var(--paper)}
    .kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:32px}
    @media (max-width:768px){.kpi-grid{grid-template-columns:repeat(2,1fr)}}
    .kpi{background:var(--paper);border:1px solid var(--rule);border-radius:16px;padding:18px}
    .kpi .lbl{font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-soft);font-weight:700}
    .kpi .val{font-size:1.5rem;font-weight:800;color:var(--ink);margin-top:6px;line-height:1}
    .kpi .sub{font-size:.78rem;color:var(--ink-mute);margin-top:4px}
    .p-card{background:var(--paper);border:1px solid var(--rule);border-radius:18px;padding:22px;display:flex;flex-direction:column;gap:14px;transition:all .2s}
    .p-card:hover{border-color:var(--sage);transform:translateY(-2px);box-shadow:0 14px 32px -20px rgba(31,95,77,.2)}
    .p-card .ttl{font-size:1.05rem;font-weight:800;color:var(--sage);line-height:1.3;margin:0}
    .p-card .msg{font-size:.92rem;color:var(--ink-mute);line-height:1.5;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;margin:0}
    .p-card .meta{display:flex;flex-wrap:wrap;gap:8px}
    .p-card .actions{display:flex;gap:8px;justify-content:flex-end;padding-top:8px;border-top:1px dashed var(--rule)}
    .p-card .when{font-size:.78rem;color:var(--ink-soft);font-weight:500}
    .chip{display:inline-flex;align-items:center;gap:6px;padding:5px 11px;border-radius:999px;font-size:.78rem;font-weight:700}
    .chip.sage{background:var(--sage-soft);color:var(--sage)}
    .chip.honey{background:var(--honey-soft);color:#92660A}
    .empty-state{background:var(--paper);border:1px solid var(--rule);border-radius:22px;padding:60px 30px;text-align:center}
    .empty-state .icon-box{width:80px;height:80px;border-radius:18px;background:var(--bg);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;color:var(--sage);font-size:2rem}
    .empty-state h4{font-weight:800;font-size:1.2rem;margin-bottom:8px}
    .empty-state p{color:var(--ink-mute);margin-bottom:24px}
    .flash{border-radius:14px;padding:12px 16px;margin-bottom:16px;font-size:.92rem;font-weight:600;display:flex;align-items:center;gap:10px}
    .flash.success{background:var(--sage-soft);color:var(--sage-d);border:1px solid rgba(31,95,77,.2)}
    .flash.danger{background:#FEF2F2;color:#991B1B;border:1px solid #FECACA}
  </style>
</head>
<body>

  <header class="sb-header">
    <div class="container">
      <a href="index.php" class="sb-logo"><img src="assets/img/skillbridge-logo.png" alt="SkillBridge" class="logo-img"></a>
      <nav class="sb-nav">
        <a href="index.php">Accueil</a>
        <a href="browse_demandes.php">Parcourir les demandes</a>
        <a href="mes_propositions.php" class="active">Mes propositions</a>
        <a href="../chat/conversations.php">Mes Conversations</a>
      </nav>
      <div class="d-flex align-items-center gap-2">
        <span id="bellSlot" class="sb-bell-btn"></span>
        <a href="profil.php" class="sb-profile-chip" title="Mon Profil">
          <img src="<?= $navAvatarSrc ?>" alt="" class="avatar">
          <span><?= htmlspecialchars($navName) ?></span>
        </a>
        <a href="<?= $BASE ?>/controller/utilisateurcontroller.php?action=logout" class="sb-cta d-none d-md-inline-flex">
          <i class="bi bi-box-arrow-right"></i><span>Quitter</span>
        </a>
      </div>
    </div>
  </header>

  <main>
    <section class="page-bg">
      <div class="blob sage blob-1"></div>
      <div class="blob honey blob-2"></div>

      <div class="container">

        <div class="text-center mb-5" data-aos="fade-up" style="max-width:720px;margin:0 auto;">
          <span class="eyebrow"><span class="dot"></span> Espace freelancer</span>
          <h1 class="display-x mt-3 mb-2">Mes <span class="accent">propositions</span>.</h1>
          <p class="lead-x mb-0">Suivez et gérez l'ensemble de vos offres envoyées aux clients.</p>
        </div>

        <?php if ($flashCreated): ?><div class="flash success"><i class="bi bi-check-circle-fill"></i> Proposition envoyée avec succès.</div><?php endif; ?>
        <?php if ($flashUpdated): ?><div class="flash success"><i class="bi bi-check-circle-fill"></i> Proposition mise à jour.</div><?php endif; ?>
        <?php if ($flashDeleted): ?><div class="flash success"><i class="bi bi-trash-fill"></i> Proposition supprimée.</div><?php endif; ?>
        <?php if ($flashError):   ?><div class="flash danger"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($flashError) ?></div><?php endif; ?>

        <div class="kpi-grid" data-aos="fade-up">
          <div class="kpi"><div class="lbl">Total propositions</div><div class="val"><?= $total ?></div><div class="sub">envoyées</div></div>
          <div class="kpi"><div class="lbl">Demandes uniques</div><div class="val"><?= $uniqueDemandes ?></div><div class="sub">projets ciblés</div></div>
          <div class="kpi"><div class="lbl">Tarif moyen</div><div class="val"><?= number_format($avgPrice, 0) ?> DT</div><div class="sub">par proposition</div></div>
          <div class="kpi"><div class="lbl">Plus récente</div><div class="val" style="font-size:1.05rem;font-weight:700;"><?= $mostRecent ? date('d/m/Y', strtotime($mostRecent)) : '—' ?></div><div class="sub">date d'envoi</div></div>
        </div>

        <div class="auth-card mb-4" data-aos="fade-up">
          <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-9">
              <label class="form-label fw-semibold" style="font-size:.85rem;">Trier mes propositions</label>
              <select name="sort" class="form-select">
                <option value="recent"  <?= $sort==='recent'  ? 'selected' : '' ?>>Plus récentes</option>
                <option value="oldest"  <?= $sort==='oldest'  ? 'selected' : '' ?>>Plus anciennes</option>
              </select>
            </div>
            <div class="col-md-3">
              <button type="submit" class="btn-sage w-100"><i class="bi bi-funnel"></i> Appliquer</button>
            </div>
          </form>
        </div>

        <?php if ($total === 0): ?>
          <div class="empty-state" data-aos="fade-up">
            <div class="icon-box"><i class="bi bi-send"></i></div>
            <h4>Vous n'avez pas encore proposé</h4>
            <p>Parcourez les demandes ouvertes et envoyez votre première proposition.</p>
            <a href="browse_demandes.php" class="btn-sage"><i class="bi bi-search"></i> Parcourir les demandes</a>
          </div>
        <?php else: ?>
          <div class="row g-4">
            <?php foreach ($rows as $p):
              $deadline = $dmap[(int)$p['demande_id']] ?? null;
              $soon = $deadline ? isDeadlineSoon($deadline) : false;
            ?>
              <div class="col-lg-6" data-aos="fade-up">
                <div class="p-card">
                  <h4 class="ttl"><?= htmlspecialchars(html_entity_decode($p['demande_title'], ENT_QUOTES, 'UTF-8')) ?></h4>
                  <div class="meta">
                    <span class="chip honey"><i class="bi bi-cash-coin"></i> <?= number_format((float)$p['price'], 0) ?> DT</span>
                    <?php if ($deadline): ?>
                      <span class="chip <?= $soon ? 'honey' : 'sage' ?>"><i class="bi bi-calendar-event"></i> <?= htmlspecialchars(date('d/m/Y', strtotime($deadline))) ?></span>
                    <?php endif; ?>
                  </div>
                  <p class="msg"><?= htmlspecialchars(html_entity_decode($p['message'], ENT_QUOTES, 'UTF-8')) ?></p>
                  <div class="when"><i class="bi bi-clock"></i> <?= relativeTime($p['created_at']) ?></div>
                  <div class="actions">
                    <a href="edit_proposition.php?id=<?= (int)$p['id'] ?>" class="btn-ghost"><i class="bi bi-pencil"></i> Modifier</a>
                    <form method="POST" onsubmit="return confirm('Supprimer cette proposition ?');" style="display:inline;">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                      <button type="submit" class="btn-danger"><i class="bi bi-trash"></i> Supprimer</button>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div>
    </section>
  </main>

  <footer class="sb-footer">
    © <?= date('Y') ?> <strong>SkillBridge</strong> — Tous droits réservés.
  </footer>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="../../shared/chatbus.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (typeof ChatBus !== 'undefined') {
        ChatBus.init({ apiBase: '../../../api/chat.php', user: <?= (int)$_SESSION['user_id'] ?>, conv: 0 });
        ChatBus.mountBell('#bellSlot');
      }
    });
    if (typeof AOS !== 'undefined') AOS.init({ duration: 600, easing: 'ease-out-cubic', once: true });
  </script>
</body>
</html>
