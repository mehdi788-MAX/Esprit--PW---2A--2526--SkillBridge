<?php
require_once 'auth_check.php';
require_once '../../../config.php';
require_once '../../../controller/DemandeController.php';
require_once '../../../model/utilisateur.php';

// Restriction au rôle "client"
if (($_SESSION['user_role'] ?? '') !== 'client') {
    $_SESSION['error'] = "Cet espace est réservé aux clients.";
    header('Location: index.php');
    exit;
}

$BASE = base_url();

$ctrl = new DemandeController();
$userId = (int)$_SESSION['user_id'];

// Suppression : POST + ?delete=ID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delId = (int)$_POST['delete_id'];
    if ($delId > 0) {
        $ctrl->deleteDemande($delId, $userId);
    }
    header('Location: mes_demandes.php?deleted=1');
    exit;
}

// Filtres GET
$sort = (isset($_GET['sort']) && $_GET['sort'] === 'oldest') ? 'oldest' : 'recent';
$searchRaw = trim((string)($_GET['search'] ?? ''));
$search = $searchRaw !== '' ? $searchRaw : null;

// Récupération des demandes
$stmt = $ctrl->listDemandesByUser($userId, $sort, $search);
$demandes = [];
if ($stmt) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $demandes[] = $row;
    }
}

// KPIs (calculés sur l'ensemble — non filtrés par recherche, pour donner une vraie vue d'ensemble)
$allStmt = $ctrl->listDemandesByUser($userId, 'recent', null);
$totalCount = 0;
$activeCount = 0;
$cumulBudget = 0.0;
$today = date('Y-m-d');
if ($allStmt) {
    while ($r = $allStmt->fetch(PDO::FETCH_ASSOC)) {
        $totalCount++;
        $cumulBudget += (float)$r['price'];
        if (!empty($r['deadline']) && $r['deadline'] >= $today) {
            $activeCount++;
        }
    }
}

// Flash
$flashCreated = isset($_GET['created']);
$flashUpdated = isset($_GET['updated']);
$flashDeleted = isset($_GET['deleted']);

// Charger l'utilisateur connecté pour la nav
$utilisateurModel = new Utilisateur($pdo);
$utilisateurModel->id = $_SESSION['user_id'];
$utilisateurModel->readOne();

$navFirstName = trim(explode(' ', trim($utilisateurModel->prenom ?? ''))[0] ?? '') ?: 'Profil';
$navAvatarSrc = !empty($utilisateurModel->photo)
    ? 'assets/img/profile/' . htmlspecialchars($utilisateurModel->photo)
    : 'https://ui-avatars.com/api/?name=' . urlencode(trim(($utilisateurModel->prenom ?? '') . ' ' . ($utilisateurModel->nom ?? '')) ?: 'SkillBridge') . '&background=1F5F4D&color=fff&bold=true&size=80';

function fmt_price($v) {
    return number_format((float)$v, 2, ',', ' ');
}
function fmt_date($d) {
    if (empty($d)) return '';
    $ts = strtotime($d);
    return $ts ? date('d/m/Y', $ts) : htmlspecialchars($d);
}
function is_soon($deadline) {
    if (empty($deadline)) return false;
    $ts = strtotime($deadline);
    if (!$ts) return false;
    $today = strtotime(date('Y-m-d'));
    $diff = ($ts - $today) / 86400;
    return $diff >= 0 && $diff <= 7;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Mes demandes — SkillBridge</title>

  <link href="assets/img/favicon.png" rel="icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">

  <style>
    :root {
      --bg:          #F7F4ED;
      --paper:       #FFFFFF;
      --ink:         #0F0F0F;
      --ink-2:       #2A2A2A;
      --ink-mute:    #5C5C5C;
      --ink-soft:    #A3A3A3;
      --rule:        #E8E2D5;
      --sage:        #1F5F4D;
      --sage-d:      #134438;
      --sage-soft:   #E8F0EC;
      --honey:       #F5C842;
      --honey-d:     #E0B033;
      --honey-soft:  #FBF1D0;
    }
    *, *::before, *::after { box-sizing: border-box; }
    body {
      font-family: 'Manrope', system-ui, -apple-system, sans-serif;
      background: var(--bg); color: var(--ink); letter-spacing: -.005em;
      -webkit-font-smoothing: antialiased; margin: 0;
    }
    ::selection { background: var(--sage); color: var(--honey); }

    h1, h2, h3, h4, h5 { font-family: 'Manrope', sans-serif; font-weight: 700; letter-spacing: -.022em; color: var(--ink); }
    .display-x { font-size: clamp(2rem, 3.6vw, 2.8rem); line-height: 1.05; font-weight: 800; letter-spacing: -.025em; }
    .lead-x    { font-size: 1rem; line-height: 1.55; color: var(--ink-mute); font-weight: 400; }
    .accent    { font-style: italic; font-weight: 700; color: var(--sage); }

    .eyebrow {
      display:inline-flex; align-items:center; gap:8px;
      font-size: .8rem; font-weight: 600;
      color: var(--sage); padding: 6px 12px;
      background: var(--sage-soft); border-radius: 999px;
    }
    .eyebrow .dot { width:6px; height:6px; border-radius:50%; background: var(--sage); }
    .eyebrow.honey { color: #92660A; background: var(--honey-soft); }
    .eyebrow.honey .dot { background: var(--honey-d); }

    /* Header */
    .sb-header {
      position: sticky; top: 0; z-index: 100;
      background: rgba(247,244,237,.85); backdrop-filter: blur(14px);
      border-bottom: 1px solid var(--rule);
    }
    .sb-header .container { display:flex; align-items:center; justify-content:space-between; padding: 14px 0; }
    .sb-logo { display:inline-flex; align-items:center; text-decoration:none; color: var(--ink); }
    .sb-logo .logo-img { height: 38px; width: auto; display: block; }
    .sb-nav { display:flex; align-items:center; gap: 28px; }
    .sb-nav a { color: var(--ink-mute); text-decoration:none; font-weight:500; font-size:.92rem; transition: color .15s; }
    .sb-nav a:hover, .sb-nav a.active { color: var(--ink); }
    .sb-nav a.active { color: var(--sage); }
    .sb-cta {
      display:inline-flex; align-items:center; gap:8px;
      background: var(--ink); color: var(--bg); padding: 10px 20px; border-radius: 999px;
      text-decoration:none; font-weight:600; font-size:.92rem; transition: all .2s ease;
    }
    .sb-cta:hover { background: var(--sage); color: var(--paper); transform: translateY(-1px); }
    .sb-bell-btn {
      width:42px; height:42px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center;
      background: transparent; color: var(--ink); position: relative; transition: all .2s;
    }
    .sb-bell-btn:hover { background: var(--paper); }
    .sb-profile-chip {
      display:inline-flex; align-items:center; gap:8px;
      padding: 4px 14px 4px 4px; border-radius: 999px;
      background: var(--paper); border: 1px solid var(--rule);
      color: var(--ink); text-decoration:none; font-weight:600; font-size:.9rem;
      transition: all .2s;
    }
    .sb-profile-chip:hover { border-color: var(--sage); transform: translateY(-1px); }
    .sb-profile-chip .avatar { width:30px; height:30px; border-radius:50%; object-fit:cover; }
    @media (max-width: 991.98px) { .sb-nav { display: none; } }

    /* Page canvas */
    .page-bg {
      position: relative; overflow: hidden; min-height: calc(100vh - 64px);
      padding: 56px 0 80px;
    }
    .blob { position: absolute; border-radius: 50%; filter: blur(60px); opacity: .55; pointer-events: none; z-index: 0; }
    .blob.sage  { background: var(--sage-soft); }
    .blob.honey { background: var(--honey-soft); }
    .blob-1 { width: 380px; height: 380px; left: -120px; top: -80px; }
    .blob-2 { width: 340px; height: 340px; right: -100px; bottom: 200px; }
    .page-bg .container { position: relative; z-index: 1; }

    /* Card */
    .auth-card {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 22px;
      padding: 26px 26px;
      box-shadow: 0 30px 60px -25px rgba(31,95,77,.18);
    }

    /* KPI cards */
    .kpi {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 14px;
      padding: 18px 18px 16px;
      transition: all .18s ease;
      display: flex; flex-direction: column; gap: 6px;
      height: 100%;
    }
    .kpi:hover { border-color: var(--sage); transform: translateY(-2px); box-shadow: 0 14px 28px -16px rgba(31,95,77,.18); }
    .kpi .head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .kpi .lbl { font-size: .68rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--ink-mute); }
    .kpi .ic-sm { width: 32px; height: 32px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; font-size: .92rem; flex-shrink: 0; }
    .kpi .ic-sm.t-sage   { background: var(--sage-soft);  color: var(--sage); }
    .kpi .ic-sm.t-honey  { background: var(--honey-soft); color: #92660A; }
    .kpi .num { font-size: 1.85rem; font-weight: 800; color: var(--ink); line-height: 1; letter-spacing: -.02em; margin-top: 2px; }
    .kpi .sub { font-size: .78rem; color: var(--ink-soft); display: flex; align-items: center; gap: 6px; }

    /* Filters */
    .filters-bar {
      display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
      background: var(--paper); border: 1px solid var(--rule); border-radius: 16px;
      padding: 14px 16px; margin-bottom: 22px;
    }
    .filters-bar .input-group { display: flex; gap: 8px; align-items: center; flex: 1 1 240px; }
    .form-control, .form-select {
      border-radius: 11px; border: 1px solid var(--rule); padding: 10px 12px;
      font-size: .92rem; background: var(--paper); color: var(--ink);
      transition: border-color .2s, box-shadow .2s;
      font-family: 'Manrope', sans-serif;
    }
    .form-control:focus, .form-select:focus {
      outline: none; border-color: var(--sage);
      box-shadow: 0 0 0 4px rgba(31,95,77,.12);
    }

    /* Demande card */
    .demande-card {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 16px;
      padding: 22px 22px 18px;
      transition: all .18s ease;
      display: flex; flex-direction: column; gap: 12px;
      height: 100%;
    }
    .demande-card:hover { border-color: var(--sage); transform: translateY(-2px); box-shadow: 0 14px 28px -16px rgba(31,95,77,.18); }
    .demande-card.is-closed { background: linear-gradient(180deg, var(--paper) 0%, var(--bg) 100%); border-color: rgba(31,95,77,.2); }
    .demande-card.is-closed h3 { color: var(--ink-mute); }
    .demande-card h3 { font-size: 1.1rem; font-weight: 800; margin: 0; line-height: 1.25; }
    .demande-card .desc {
      color: var(--ink-mute); font-size: .92rem; line-height: 1.5;
      display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;
      overflow: hidden; text-overflow: ellipsis;
    }
    .chip-row { display: flex; flex-wrap: wrap; gap: 8px; }
    .chip {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 5px 11px; border-radius: 999px;
      font-size: .8rem; font-weight: 700; letter-spacing: -.005em;
    }
    .chip.sage  { background: var(--sage-soft); color: var(--sage); }
    .chip.honey { background: var(--honey-soft); color: #92660A; }
    .meta { color: var(--ink-soft); font-size: .8rem; }

    .actions {
      display: flex; flex-wrap: wrap; gap: 8px; margin-top: auto;
      padding-top: 12px; border-top: 1px dashed var(--rule);
    }
    .btn-mini {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 8px 13px; border-radius: 9px; border: 1px solid var(--rule);
      background: var(--paper); color: var(--ink); font-weight: 600; font-size: .82rem;
      text-decoration: none; cursor: pointer; transition: all .15s ease;
    }
    .btn-mini:hover { border-color: var(--sage); color: var(--sage); }
    .btn-mini.primary { background: var(--sage); color: var(--paper); border-color: var(--sage); }
    .btn-mini.primary:hover { background: var(--sage-d); border-color: var(--sage-d); color: var(--paper); }
    .btn-mini.danger:hover { border-color: #DC2626; color: #DC2626; }
    .badge-count {
      background: var(--honey); color: var(--ink); font-weight: 800; font-size: .72rem;
      padding: 2px 7px; border-radius: 999px; line-height: 1;
    }
    .btn-mini.primary .badge-count { background: var(--honey); color: var(--ink); }

    /* Buttons */
    .btn-sage {
      display: inline-flex; align-items: center; justify-content: center; gap: 10px;
      padding: 12px 20px; border-radius: 12px; border: none;
      background: var(--sage); color: var(--paper);
      font-weight: 700; font-size: .95rem; cursor: pointer;
      transition: all .2s ease; text-decoration: none;
    }
    .btn-sage:hover {
      background: var(--sage-d); transform: translateY(-2px);
      box-shadow: 0 14px 28px -12px rgba(31,95,77,.4);
      color: var(--paper);
    }
    .btn-ghost {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--paper); color: var(--ink);
      padding: 10px 16px; border-radius: 10px;
      border: 1px solid var(--rule);
      text-decoration: none; font-weight: 600; font-size: .9rem;
      transition: all .2s ease;
    }
    .btn-ghost:hover { border-color: var(--sage); color: var(--sage); }

    /* Alerts */
    .ad-alert {
      border-radius: 14px; padding: 14px 16px; border: 1px solid; margin-bottom: 18px;
      display: flex; align-items: flex-start; gap: 12px; font-size: .92rem;
    }
    .ad-alert.success { background: var(--sage-soft); border-color: rgba(31,95,77,.2); color: var(--sage-d); }
    .ad-alert.danger  { background: #FEF2F2; border-color: #FECACA; color: #991B1B; }

    /* Empty state */
    .empty-card {
      text-align: center; padding: 60px 30px;
      background: var(--sage-soft); border: 1px solid rgba(31,95,77,.12);
      border-radius: 22px;
    }
    .empty-card .emoji { font-size: 3rem; color: var(--sage); margin-bottom: 12px; }
    .empty-card h3 { font-size: 1.4rem; font-weight: 800; margin-bottom: 6px; }
    .empty-card p  { color: var(--ink-mute); margin-bottom: 22px; }

    /* Footer */
    .sb-footer { background: var(--ink); color: rgba(255,255,255,.65); padding: 22px 0; font-size: .88rem; text-align: center; }
    .sb-footer strong { color: var(--paper); }

    @media (max-width: 991.98px) {
      .auth-card { padding: 22px 20px; }
      .page-bg { padding: 40px 0 60px; }
    }
  </style>
</head>
<body>

  <header class="sb-header">
    <div class="container">
      <a href="index.php" class="sb-logo">
        <img src="assets/img/skillbridge-logo.png" alt="SkillBridge" class="logo-img" loading="eager">
      </a>
      <nav class="sb-nav">
        <?= frontoffice_main_nav('mes_demandes', '.', '../chat') ?>
      </nav>
      <div class="d-flex align-items-center gap-2">
        <span id="bellSlot" class="sb-bell-btn" style="display:inline-flex;"></span>
        <a href="profil.php" class="sb-profile-chip" title="Mon Profil">
          <img src="<?= $navAvatarSrc ?>" alt="" class="avatar"
               onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?= urlencode($navFirstName) ?>&background=1F5F4D&color=fff&bold=true&size=80';">
          <span><?= htmlspecialchars($navFirstName) ?></span>
        </a>
        <a href="<?= $BASE ?>/controller/utilisateurcontroller.php?action=logout" class="sb-cta d-none d-md-inline-flex">
          <i class="bi bi-box-arrow-right"></i><span>Quitter</span>
        </a>
      </div>
    </div>
  </header>

  <main>
    <section class="page-bg">
      <div class="blob sage  blob-1"></div>
      <div class="blob honey blob-2"></div>

      <div class="container">

        <!-- Page header -->
        <div class="text-center mb-4" data-aos="fade-up" style="max-width: 740px; margin: 0 auto;">
          <span class="eyebrow"><span class="dot"></span> Mes demandes</span>
          <h1 class="display-x mt-3 mb-2">Vos <span class="accent">projets</span> publiés.</h1>
          <p class="lead-x mb-0">
            <?php if ($totalCount === 0): ?>
              Vous n'avez encore publié aucune demande. Lancez votre premier projet en quelques clics.
            <?php else: ?>
              Vous avez actuellement <strong><?= $totalCount ?></strong> demande<?= $totalCount > 1 ? 's' : '' ?> publiée<?= $totalCount > 1 ? 's' : '' ?>. Consultez les propositions reçues et faites avancer vos projets.
            <?php endif; ?>
          </p>
        </div>

        <!-- Flash messages -->
        <?php if ($flashCreated): ?>
          <div class="ad-alert success" data-aos="fade-up">
            <i class="bi bi-check-circle-fill fs-5 mt-1"></i>
            <div><strong>Demande publiée !</strong> Elle est maintenant visible des freelancers de la communauté.</div>
          </div>
        <?php endif; ?>
        <?php if ($flashUpdated): ?>
          <div class="ad-alert success" data-aos="fade-up">
            <i class="bi bi-check-circle-fill fs-5 mt-1"></i>
            <div><strong>Demande mise à jour</strong> avec succès.</div>
          </div>
        <?php endif; ?>
        <?php if ($flashDeleted): ?>
          <div class="ad-alert success" data-aos="fade-up">
            <i class="bi bi-trash3 fs-5 mt-1"></i>
            <div><strong>Demande supprimée.</strong></div>
          </div>
        <?php endif; ?>

        <!-- KPIs -->
        <div class="row g-3 mb-4" data-aos="fade-up" data-aos-delay="80">
          <div class="col-md-4">
            <div class="kpi">
              <div class="head">
                <span class="lbl">Total demandes</span>
                <span class="ic-sm t-sage"><i class="bi bi-collection"></i></span>
              </div>
              <div class="num"><?= $totalCount ?></div>
              <div class="sub">Toutes les demandes publiées</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="kpi">
              <div class="head">
                <span class="lbl">En cours</span>
                <span class="ic-sm t-honey"><i class="bi bi-hourglass-split"></i></span>
              </div>
              <div class="num"><?= $activeCount ?></div>
              <div class="sub">Échéance à venir</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="kpi">
              <div class="head">
                <span class="lbl">Budget cumulé</span>
                <span class="ic-sm t-sage"><i class="bi bi-cash-coin"></i></span>
              </div>
              <div class="num"><?= fmt_price($cumulBudget) ?> <span style="font-size:.95rem; font-weight:700; color: var(--ink-mute);">TND</span></div>
              <div class="sub">Tous projets confondus</div>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <form method="GET" action="mes_demandes.php" class="filters-bar" data-aos="fade-up" data-aos-delay="120">
          <div class="input-group">
            <i class="bi bi-search" style="color: var(--ink-soft);"></i>
            <input type="text" name="search" class="form-control" placeholder="Rechercher dans le titre..."
                   value="<?= htmlspecialchars($searchRaw) ?>">
          </div>
          <select name="sort" class="form-select" style="max-width: 200px;">
            <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Plus récent</option>
            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Plus ancien</option>
          </select>
          <button type="submit" class="btn-sage" style="padding: 10px 18px; font-size: .9rem;">
            <i class="bi bi-funnel"></i> Filtrer
          </button>
          <a href="add_demande.php" class="btn-sage" style="padding: 10px 18px; font-size: .9rem; background: var(--honey); color: var(--ink);">
            <i class="bi bi-plus-lg"></i> Nouvelle demande
          </a>
        </form>

        <!-- Liste -->
        <?php if (empty($demandes)): ?>
          <?php if ($totalCount === 0): ?>
            <div class="empty-card" data-aos="fade-up" data-aos-delay="160">
              <div class="emoji"><i class="bi bi-chat-square-text"></i></div>
              <h3>Aucune demande encore</h3>
              <p>Publiez votre première demande pour recevoir des propositions de freelancers qualifiés.</p>
              <a href="add_demande.php" class="btn-sage">
                <i class="bi bi-plus-circle"></i> Publier ma première demande
              </a>
            </div>
          <?php else: ?>
            <div class="auth-card text-center" data-aos="fade-up" data-aos-delay="160">
              <div style="font-size: 2rem; color: var(--ink-soft);"><i class="bi bi-search"></i></div>
              <h3 style="font-size: 1.15rem; margin: 8px 0 6px;">Aucun résultat</h3>
              <p style="color: var(--ink-mute); margin-bottom: 14px;">Aucune demande ne correspond à votre recherche.</p>
              <a href="mes_demandes.php" class="btn-ghost">
                <i class="bi bi-arrow-counterclockwise"></i> Réinitialiser
              </a>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="row g-3">
            <?php foreach ($demandes as $d): ?>
              <?php
                $id       = (int)$d['id'];
                $count    = $ctrl->countPropositionsByDemande($id);
                $soon     = is_soon($d['deadline']);
                $isClosed = ($d['status'] ?? 'open') === 'closed';
              ?>
              <div class="col-12 col-md-6 col-lg-4" data-aos="fade-up">
                <div class="demande-card<?= $isClosed ? ' is-closed' : '' ?>">
                  <div class="d-flex justify-content-between align-items-start gap-2">
                    <div style="min-width:0; flex:1;">
                      <h3><?= htmlspecialchars($d['title']) ?></h3>
                      <div class="meta mt-1">
                        <i class="bi bi-clock"></i>
                        Publiée le <?= fmt_date($d['created_at']) ?>
                      </div>
                    </div>
                    <?php if ($isClosed): ?>
                      <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:999px;font-size:.7rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;background:var(--sage-soft);color:var(--sage);border:1px solid rgba(31,95,77,.25);white-space:nowrap;">
                        <i class="bi bi-lock-fill"></i> Fermée
                      </span>
                    <?php endif; ?>
                  </div>
                  <p class="desc"><?= htmlspecialchars($d['description']) ?></p>
                  <div class="chip-row">
                    <span class="chip sage"><i class="bi bi-cash"></i> <?= fmt_price($d['price']) ?> TND</span>
                    <span class="chip <?= $soon ? 'honey' : 'sage' ?>">
                      <i class="bi bi-calendar-event"></i> <?= fmt_date($d['deadline']) ?>
                    </span>
                  </div>
                  <div class="actions">
                    <a href="demande_propositions.php?id=<?= $id ?>" class="btn-mini primary">
                      <i class="bi bi-inboxes"></i> Voir propositions <span class="badge-count"><?= $count ?></span>
                    </a>
                    <?php if (!$isClosed): ?>
                      <a href="edit_demande.php?id=<?= $id ?>" class="btn-mini">
                        <i class="bi bi-pencil"></i> Modifier
                      </a>
                      <form method="POST" action="mes_demandes.php" style="display:inline;"
                            onsubmit="return confirm('Supprimer définitivement cette demande ? Cette action est irréversible.');">
                        <input type="hidden" name="delete_id" value="<?= $id ?>">
                        <button type="submit" class="btn-mini danger">
                          <i class="bi bi-trash3"></i> Supprimer
                        </button>
                      </form>
                    <?php else: ?>
                      <a href="../chat/conversations.php" class="btn-mini">
                        <i class="bi bi-chat-dots"></i> Conversation
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
    if (typeof AOS !== 'undefined') {
      AOS.init({ duration: 600, easing: 'ease-out-cubic', once: true });
    }
  </script>
</body>
</html>
