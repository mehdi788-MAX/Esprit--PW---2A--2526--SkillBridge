<?php
require_once 'auth_check.php';
require_once '../../../config.php';
require_once '../../../controller/DemandeController.php';

$BASE = base_url();
$ctrl = new DemandeController();

$role        = strtolower($_SESSION['user_role'] ?? '');
$isFreelancer = ($role === 'freelancer');
$isClient     = ($role === 'client');
$navAvatar    = frontoffice_nav_avatar($pdo, $_SESSION['user_id'] ?? 0);
$navName      = $navAvatar['name'];
$navAvatarSrc = $navAvatar['src'];
$navFallback  = $navAvatar['fallback'];

// Filters
$sort   = ($_GET['sort']   ?? 'recent') === 'oldest' ? 'oldest' : 'recent';
$search = trim((string)($_GET['search'] ?? ''));
$searchParam = $search !== '' ? $search : null;

$stmt = $ctrl->listDemandes($sort, $searchParam);
$rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
// On ne montre aux freelancers que les demandes encore ouvertes — les
// demandes clôturées (proposition acceptée) disparaissent du marketplace.
$rows = array_values(array_filter($rows, function ($r) {
    return ($r['status'] ?? 'open') === 'open';
}));

// Author lookup (utilisateurs + profils en une seule requête, pour afficher
// photo + localisation sur chaque carte demande).
$authors = [];
$userIds = array_filter(array_unique(array_map(function($r){ return (int)$r['user_id']; }, $rows)));
if (!empty($userIds)) {
    $in = implode(',', array_map('intval', $userIds));
    $aStmt = $pdo->query("SELECT u.id, u.nom, u.prenom, u.photo,
                                 p.localisation
                            FROM utilisateurs u
                            LEFT JOIN profils p ON p.utilisateur_id = u.id
                           WHERE u.id IN ($in)");
    foreach ($aStmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
        $authors[(int)$u['id']] = $u;
    }
}

// KPIs
$total = count($rows);
$avgBudget = 0;
$mostRecent = null;
$nearestDeadline = null;
if ($total > 0) {
    $sum = 0;
    foreach ($rows as $r) {
        $sum += (float)$r['price'];
        if ($mostRecent === null || strtotime($r['created_at']) > strtotime($mostRecent)) {
            $mostRecent = $r['created_at'];
        }
        $today = date('Y-m-d');
        if ($r['deadline'] >= $today) {
            if ($nearestDeadline === null || $r['deadline'] < $nearestDeadline) {
                $nearestDeadline = $r['deadline'];
            }
        }
    }
    $avgBudget = $sum / $total;
}

// Helpers
function relativeTime($dt) {
    $ts = strtotime($dt);
    $diff = time() - $ts;
    if ($diff < 60) return "à l'instant";
    if ($diff < 3600) { $m = floor($diff/60); return "il y a {$m} min"; }
    if ($diff < 86400) { $h = floor($diff/3600); return "il y a {$h} h"; }
    $d = floor($diff/86400);
    if ($d < 30) return "il y a {$d} j";
    $mo = floor($d/30);
    return "il y a {$mo} mois";
}
function isDeadlineSoon($d) {
    $today = strtotime(date('Y-m-d'));
    $dl = strtotime($d);
    return ($dl - $today) <= 7 * 86400 && $dl >= $today;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Parcourir les demandes — SkillBridge</title>
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
    ::selection{background:var(--sage);color:var(--honey)}
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
    .sb-bell-btn{width:42px;height:42px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:transparent;color:var(--ink);position:relative;transition:all .2s} .sb-bell-btn:hover{background:var(--paper)}
    .sb-profile-chip{display:inline-flex;align-items:center;gap:8px;padding:4px 14px 4px 4px;border-radius:999px;background:var(--paper);border:1px solid var(--rule);color:var(--ink);text-decoration:none;font-weight:600;font-size:.9rem;transition:all .2s}
    .sb-profile-chip:hover{border-color:var(--sage);transform:translateY(-1px)} .sb-profile-chip .avatar{width:30px;height:30px;border-radius:50%;object-fit:cover}
    @media (max-width:991.98px){.sb-nav{display:none}}
    .page-bg{position:relative;overflow:hidden;min-height:calc(100vh - 64px);padding:56px 0 80px}
    .blob{position:absolute;border-radius:50%;filter:blur(60px);opacity:.55;pointer-events:none;z-index:0}
    .blob.sage{background:var(--sage-soft)} .blob.honey{background:var(--honey-soft)}
    .blob-1{width:380px;height:380px;left:-120px;top:-80px} .blob-2{width:340px;height:340px;right:-100px;bottom:200px}
    .page-bg .container{position:relative;z-index:1}
    .auth-card{background:var(--paper);border:1px solid var(--rule);border-radius:22px;padding:24px;box-shadow:0 30px 60px -25px rgba(31,95,77,.18)}
    .form-control,.form-select{width:100%;border-radius:12px;border:1px solid var(--rule);padding:11px 14px;font-size:.95rem;background:var(--paper);color:var(--ink);transition:border-color .2s,box-shadow .2s;font-family:'Manrope',sans-serif}
    .form-control:focus,.form-select:focus{outline:none;border-color:var(--sage);box-shadow:0 0 0 4px rgba(31,95,77,.12)}
    .btn-sage{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 20px;border-radius:12px;border:none;background:var(--sage);color:var(--paper);font-weight:700;font-size:.95rem;cursor:pointer;transition:all .2s;text-decoration:none}
    .btn-sage:hover{background:var(--sage-d);transform:translateY(-2px);box-shadow:0 14px 28px -12px rgba(31,95,77,.4);color:var(--paper)}
    .btn-ghost{display:inline-flex;align-items:center;gap:8px;background:var(--paper);color:var(--ink);padding:10px 16px;border-radius:10px;border:1px solid var(--rule);text-decoration:none;font-weight:600;font-size:.9rem;transition:all .2s}
    .btn-ghost:hover{border-color:var(--sage);color:var(--sage)}
    .sb-footer{background:var(--ink);color:rgba(255,255,255,.65);padding:22px 0;font-size:.88rem;text-align:center} .sb-footer strong{color:var(--paper)}
    /* KPI tiles */
    .kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:32px}
    @media (max-width:768px){.kpi-grid{grid-template-columns:repeat(2,1fr)}}
    .kpi{background:var(--paper);border:1px solid var(--rule);border-radius:16px;padding:18px}
    .kpi .lbl{font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-soft);font-weight:700}
    .kpi .val{font-size:1.5rem;font-weight:800;color:var(--ink);margin-top:6px;line-height:1}
    .kpi .sub{font-size:.78rem;color:var(--ink-mute);margin-top:4px}
    /* Demande card */
    .d-card{background:var(--paper);border:1px solid var(--rule);border-radius:18px;padding:22px;display:flex;flex-direction:column;gap:14px;transition:all .2s;height:100%}
    .d-card:hover{border-color:var(--sage);transform:translateY(-3px);box-shadow:0 18px 40px -22px rgba(31,95,77,.22)}
    .d-card .title{font-size:1.15rem;font-weight:800;color:var(--ink);line-height:1.25;margin:0}
    .d-card .desc{font-size:.92rem;color:var(--ink-mute);line-height:1.5;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;margin:0}
    .d-card .meta{display:flex;flex-wrap:wrap;gap:8px}
    .chip{display:inline-flex;align-items:center;gap:6px;padding:5px 11px;border-radius:999px;font-size:.78rem;font-weight:700}
    .chip.sage{background:var(--sage-soft);color:var(--sage)}
    .chip.honey{background:var(--honey-soft);color:#92660A}
    .chip i{font-size:.85rem}
    .author{display:flex;align-items:center;gap:10px;margin-top:auto;padding-top:12px;border-top:1px dashed var(--rule)}
    .author img{width:32px;height:32px;border-radius:50%;object-fit:cover}
    .author .who{font-weight:700;font-size:.85rem;color:var(--ink-2)}
    .author .when{font-size:.75rem;color:var(--ink-soft)}
    .empty-state{background:var(--paper);border:1px solid var(--rule);border-radius:22px;padding:60px 30px;text-align:center}
    .empty-state .icon-box{width:80px;height:80px;border-radius:18px;background:var(--bg);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;color:var(--sage);font-size:2rem}
    .empty-state h4{font-weight:800;font-size:1.2rem;margin-bottom:8px}
    .empty-state p{color:var(--ink-mute);margin-bottom:24px}
    .role-info-banner{background:var(--honey-soft);border:1px solid rgba(224,176,51,.3);border-radius:14px;padding:12px 16px;color:#7a4f08;font-size:.9rem;font-weight:600;display:flex;align-items:center;gap:10px;margin-bottom:24px}
  </style>
</head>
<body>

  <header class="sb-header">
    <div class="container">
      <a href="index.php" class="sb-logo"><img src="assets/img/skillbridge-logo.png" alt="SkillBridge" class="logo-img"></a>
      <nav class="sb-nav">
        <?= frontoffice_main_nav('demandes', '.', '../chat') ?>
      </nav>
      <div class="d-flex align-items-center gap-2">
        <span id="bellSlot" class="sb-bell-btn"></span>
        <a href="profil.php" class="sb-profile-chip" title="Mon Profil">
          <img src="<?= $navAvatarSrc ?>" alt="" class="avatar"
               onerror="this.onerror=null;this.src='<?= htmlspecialchars($navFallback) ?>';">
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
          <span class="eyebrow"><span class="dot"></span> Marketplace</span>
          <h1 class="display-x mt-3 mb-2">Parcourir les <span class="accent">demandes</span>.</h1>
          <p class="lead-x mb-0">Explorez les opportunités publiées par les clients et envoyez vos meilleures propositions.</p>
        </div>

        <?php if ($isClient): ?>
          <div class="role-info-banner" data-aos="fade-up">
            <i class="bi bi-info-circle-fill"></i>
            Vous êtes connecté en tant que client. Cette page est destinée aux freelancers.
          </div>
        <?php endif; ?>

        <!-- KPI -->
        <div class="kpi-grid" data-aos="fade-up">
          <div class="kpi"><div class="lbl">Total demandes</div><div class="val"><?= $total ?></div><div class="sub">opportunités ouvertes</div></div>
          <div class="kpi"><div class="lbl">Budget moyen</div><div class="val"><?= number_format($avgBudget, 0) ?> DT</div><div class="sub">par demande</div></div>
          <div class="kpi"><div class="lbl">Plus récente</div><div class="val" style="font-size:1.05rem;font-weight:700;"><?= $mostRecent ? date('d/m/Y', strtotime($mostRecent)) : '—' ?></div><div class="sub">date de publication</div></div>
          <div class="kpi"><div class="lbl">Deadline proche</div><div class="val" style="font-size:1.05rem;font-weight:700;"><?= $nearestDeadline ? date('d/m/Y', strtotime($nearestDeadline)) : '—' ?></div><div class="sub">prochaine échéance</div></div>
        </div>

        <!-- Filters -->
        <div class="auth-card mb-4" data-aos="fade-up">
          <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-7">
              <label class="form-label fw-semibold" style="font-size:.85rem;">Rechercher par titre</label>
              <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="ex : développeur React, logo, traduction...">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold" style="font-size:.85rem;">Trier par</label>
              <select name="sort" class="form-select">
                <option value="recent"  <?= $sort==='recent'  ? 'selected' : '' ?>>Plus récentes</option>
                <option value="oldest"  <?= $sort==='oldest'  ? 'selected' : '' ?>>Plus anciennes</option>
              </select>
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn-sage w-100"><i class="bi bi-funnel"></i> Filtrer</button>
            </div>
          </form>
        </div>

        <!-- Cards or empty -->
        <?php if ($total === 0): ?>
          <div class="empty-state" data-aos="fade-up">
            <div class="icon-box"><i class="bi bi-inbox"></i></div>
            <h4>Aucune demande publiée pour l'instant</h4>
            <p>Revenez plus tard pour découvrir les nouvelles opportunités.</p>
            <a href="index.php" class="btn-sage"><i class="bi bi-arrow-left"></i> Retour à l'accueil</a>
          </div>
        <?php else: ?>
          <div class="row g-4">
            <?php foreach ($rows as $r):
              $author = $authors[(int)$r['user_id']] ?? null;
              $authorName = $author ? trim($author['prenom'] . ' ' . $author['nom']) : 'Client SkillBridge';
              $authorAvatar = ($author && !empty($author['photo'])) ? 'assets/img/profile/' . htmlspecialchars($author['photo']) : 'https://ui-avatars.com/api/?name=' . urlencode($authorName) . '&background=1F5F4D&color=fff&size=80';
              $authorLoc  = $author['localisation'] ?? '';
              $authorId   = $author ? (int)$author['id'] : 0;
              $soon = isDeadlineSoon($r['deadline']);
            ?>
              <div class="col-md-6" data-aos="fade-up">
                <div class="d-card">
                  <h3 class="title"><?= htmlspecialchars(html_entity_decode($r['title'], ENT_QUOTES, 'UTF-8')) ?></h3>
                  <div class="meta">
                    <span class="chip sage"><i class="bi bi-cash-coin"></i> <?= number_format((float)$r['price'], 0) ?> DT</span>
                    <span class="chip <?= $soon ? 'honey' : 'sage' ?>"><i class="bi bi-calendar-event"></i> <?= htmlspecialchars(date('d/m/Y', strtotime($r['deadline']))) ?></span>
                  </div>
                  <p class="desc"><?= htmlspecialchars(html_entity_decode($r['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                  <div class="author">
                    <img src="<?= $authorAvatar ?>" alt="" onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?= urlencode($authorName) ?>&background=1F5F4D&color=fff';">
                    <div class="flex-grow-1" style="min-width:0;">
                      <div class="who">
                        <?php if ($authorId): ?>
                          <a href="profil.php?id=<?= $authorId ?>" style="color:inherit;text-decoration:none;"><?= htmlspecialchars($authorName) ?></a>
                        <?php else: ?>
                          <?= htmlspecialchars($authorName) ?>
                        <?php endif; ?>
                      </div>
                      <div class="when">
                        <?php if ($authorLoc): ?><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($authorLoc) ?> · <?php endif; ?>
                        <?= relativeTime($r['created_at']) ?>
                      </div>
                    </div>
                    <?php if ($isFreelancer): ?>
                      <a href="add_proposition.php?demande_id=<?= (int)$r['id'] ?>" class="btn-sage" style="padding:9px 14px;font-size:.85rem;">
                        <i class="bi bi-send"></i> Faire une proposition
                      </a>
                    <?php elseif ($isClient): ?>
                      <span class="btn-ghost" style="opacity:.65;cursor:default;"><i class="bi bi-eye"></i> Voir</span>
                    <?php else: ?>
                      <a href="add_proposition.php?demande_id=<?= (int)$r['id'] ?>" class="btn-sage" style="padding:9px 14px;font-size:.85rem;">
                        <i class="bi bi-send"></i> Proposer
                      </a>
                    <?php endif; ?>
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
