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

$demandeId = (int)($_GET['id'] ?? 0);
if ($demandeId <= 0) {
    $_SESSION['error'] = "Demande introuvable.";
    header('Location: mes_demandes.php');
    exit;
}

// Vérifier existence + ownership
$demande = $ctrl->getDemande($demandeId);
if (!$demande) {
    $_SESSION['error'] = "Demande introuvable.";
    header('Location: mes_demandes.php');
    exit;
}
if ((int)$demande['user_id'] !== $userId) {
    $_SESSION['error'] = "Vous n'avez pas accès aux propositions de cette demande.";
    header('Location: mes_demandes.php');
    exit;
}

// Action : accepter une proposition
$flashAccepted = null;
$flashError    = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'accept') {
    $pid = (int)($_POST['proposition_id'] ?? 0);
    if ($pid > 0) {
        $res = $ctrl->acceptProposition($pid, $userId);
        if ($res['success']) {
            // Refresh demande state and redirect to the new conversation
            header('Location: ../chat/chat.php?id=' . (int)$res['conversation_id'] . '&accepted=1');
            exit;
        }
        $flashError = implode(' ', $res['errors']);
    }
}

// Tri
$sort = (isset($_GET['sort']) && $_GET['sort'] === 'oldest') ? 'oldest' : 'recent';

// Récupérer les propositions
$stmt = $ctrl->listPropositionsByDemande($demandeId, $sort);
$propositions = [];
if ($stmt) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $propositions[] = $row;
    }
}

// Pour chaque proposition, récupérer la fiche du freelancer (photo + nom officiel
// + localisation) en une seule requête batchée pour rester efficace.
$freelancers = [];
$fIds = array_filter(array_unique(array_map(function ($p) { return (int)($p['user_id'] ?? 0); }, $propositions)));
if (!empty($fIds)) {
    $in = implode(',', array_map('intval', $fIds));
    $fStmt = $pdo->query("SELECT u.id, u.prenom, u.nom, u.photo, p.localisation, p.competences
                            FROM utilisateurs u
                            LEFT JOIN profils p ON p.utilisateur_id = u.id
                           WHERE u.id IN ($in)");
    foreach ($fStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $freelancers[(int)$row['id']] = $row;
    }
}

// KPIs
$count = count($propositions);
$prices = array_map(function ($p) { return (float)$p['price']; }, $propositions);
$minPrice = $count > 0 ? min($prices) : 0;
$avgPrice = $count > 0 ? array_sum($prices) / $count : 0;

// Charger l'utilisateur connecté pour la nav
$utilisateurModel = new Utilisateur($pdo);
$utilisateurModel->id = $_SESSION['user_id'];
$utilisateurModel->readOne();

$navFirstName = trim(explode(' ', trim($utilisateurModel->prenom ?? ''))[0] ?? '') ?: 'Profil';
$navAvatarSrc = !empty($utilisateurModel->photo)
    ? 'assets/img/profile/' . htmlspecialchars($utilisateurModel->photo)
    : 'https://ui-avatars.com/api/?name=' . urlencode(trim(($utilisateurModel->prenom ?? '') . ' ' . ($utilisateurModel->nom ?? '')) ?: 'SkillBridge') . '&background=1F5F4D&color=fff&bold=true&size=80';

function fmt_price($v) { return number_format((float)$v, 2, ',', ' '); }
function fmt_date($d) {
    if (empty($d)) return '';
    $ts = strtotime($d);
    return $ts ? date('d/m/Y', $ts) : htmlspecialchars($d);
}
function fmt_datetime($d) {
    if (empty($d)) return '';
    $ts = strtotime($d);
    return $ts ? date('d/m/Y à H:i', $ts) : htmlspecialchars($d);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Propositions reçues — SkillBridge</title>

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

    /* Page */
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

    /* Cards */
    .auth-card {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 20px;
      padding: 24px 24px;
      box-shadow: 0 30px 60px -25px rgba(31,95,77,.18);
    }

    /* Demande summary */
    .summary-card {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 20px;
      padding: 22px 24px;
      display: grid; grid-template-columns: 1fr auto; gap: 14px; align-items: start;
      box-shadow: 0 18px 40px -24px rgba(31,95,77,.16);
    }
    @media (max-width: 767.98px) { .summary-card { grid-template-columns: 1fr; } }
    .summary-card h2 { font-size: 1.25rem; font-weight: 800; margin: 0 0 6px; }
    .summary-card .desc {
      color: var(--ink-mute); font-size: .92rem; line-height: 1.5; margin: 8px 0 0;
      display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;
      overflow: hidden; text-overflow: ellipsis;
    }
    .chip-row { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
    .chip {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 5px 11px; border-radius: 999px;
      font-size: .8rem; font-weight: 700; letter-spacing: -.005em;
    }
    .chip.sage  { background: var(--sage-soft); color: var(--sage); }
    .chip.honey { background: var(--honey-soft); color: #92660A; }

    /* KPI */
    .kpi {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 14px;
      padding: 18px 18px 16px;
      transition: all .18s ease;
      display: flex; flex-direction: column; gap: 6px; height: 100%;
    }
    .kpi:hover { border-color: var(--sage); transform: translateY(-2px); box-shadow: 0 14px 28px -16px rgba(31,95,77,.18); }
    .kpi .head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .kpi .lbl { font-size: .68rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--ink-mute); }
    .kpi .ic-sm { width: 32px; height: 32px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; font-size: .92rem; flex-shrink: 0; }
    .kpi .ic-sm.t-sage   { background: var(--sage-soft);  color: var(--sage); }
    .kpi .ic-sm.t-honey  { background: var(--honey-soft); color: #92660A; }
    .kpi .num { font-size: 1.7rem; font-weight: 800; color: var(--ink); line-height: 1; letter-spacing: -.02em; margin-top: 2px; }
    .kpi .sub { font-size: .78rem; color: var(--ink-soft); display: flex; align-items: center; gap: 6px; }

    /* Proposition card */
    .proposition-card {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 16px;
      padding: 20px 22px;
      transition: all .18s ease;
      display: flex; flex-direction: column; gap: 10px;
    }
    .proposition-card:hover { border-color: var(--sage); transform: translateY(-2px); box-shadow: 0 14px 28px -16px rgba(31,95,77,.18); }
    .proposition-card .head-row {
      display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
    }
    .proposition-card .freelancer {
      display: flex; align-items: center; gap: 12px;
    }
    .proposition-card .avatar-disc {
      width: 46px; height: 46px; border-radius: 50%;
      background: var(--sage); color: var(--paper);
      display: inline-flex; align-items: center; justify-content: center;
      font-weight: 800; font-size: 1rem; flex-shrink: 0;
      box-shadow: 0 4px 10px -4px rgba(31,95,77,.4);
      object-fit: cover; overflow: hidden;
    }
    .proposition-card img.avatar-disc { padding: 0; }
    .proposition-card .name {
      font-weight: 800; color: var(--ink); font-size: 1rem; line-height: 1.2;
      display: inline-flex; align-items: center; gap: 6px;
    }
    .proposition-card .name .verif {
      width: 16px; height: 16px; border-radius: 50%;
      background: var(--sage); color: #fff; display:inline-flex;
      align-items:center; justify-content:center; font-size: .65rem;
    }
    .proposition-card .role-loc {
      color: var(--ink-soft); font-size: .78rem; margin-top: 2px;
      display: inline-flex; align-items: center; gap: 4px;
    }
    .proposition-card .meta {
      color: var(--ink-soft); font-size: .78rem;
    }
    .proposition-card .price-badge {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 14px; border-radius: 999px;
      background: var(--honey-soft); color: #92660A;
      font-weight: 800; font-size: .9rem;
    }
    .proposition-card .message {
      color: var(--ink-2); line-height: 1.55; font-size: .94rem;
      background: var(--bg); border-radius: 12px;
      padding: 12px 14px; border: 1px dashed var(--rule);
      white-space: pre-wrap;
    }
    .status-badge {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 4px 10px; border-radius: 999px;
      font-size: .72rem; font-weight: 700; letter-spacing: .04em;
      text-transform: uppercase;
    }
    .status-badge.pending  { background: var(--paper); color: var(--ink-mute); border: 1px solid var(--rule); }
    .status-badge.accepted { background: var(--sage-soft); color: var(--sage); border: 1px solid rgba(31,95,77,.25); }
    .status-badge.declined { background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; }
    .proposition-card.is-accepted { border-color: var(--sage); box-shadow: 0 18px 40px -22px rgba(31,95,77,.35); }
    .proposition-card.is-declined { opacity: .6; }
    .accept-bar {
      display: flex; align-items: center; justify-content: space-between; gap: 12px;
      padding-top: 10px; border-top: 1px dashed var(--rule); flex-wrap: wrap;
    }
    .btn-accept {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--sage); color: var(--paper);
      padding: 10px 18px; border-radius: 10px;
      border: none; font-weight: 700; font-size: .88rem;
      cursor: pointer; transition: all .15s;
    }
    .btn-accept:hover { background: #174634; transform: translateY(-1px); box-shadow: 0 12px 24px -10px rgba(31,95,77,.45); }
    .closed-banner {
      background: var(--sage-soft); border: 1px solid rgba(31,95,77,.18);
      border-radius: 14px; padding: 14px 18px; margin-bottom: 18px;
      display: flex; align-items: center; gap: 12px; color: var(--sage-d);
    }
    .closed-banner i { font-size: 1.2rem; }

    /* Empty */
    .empty-card {
      text-align: center; padding: 60px 30px;
      background: var(--sage-soft); border: 1px solid rgba(31,95,77,.12);
      border-radius: 22px;
    }
    .empty-card .emoji { font-size: 3rem; color: var(--sage); margin-bottom: 12px; }
    .empty-card h3 { font-size: 1.4rem; font-weight: 800; margin-bottom: 6px; }
    .empty-card p  { color: var(--ink-mute); margin-bottom: 0; max-width: 460px; margin-left:auto; margin-right:auto; }

    /* Buttons */
    .btn-ghost {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--paper); color: var(--ink);
      padding: 10px 16px; border-radius: 10px;
      border: 1px solid var(--rule);
      text-decoration: none; font-weight: 600; font-size: .9rem;
      transition: all .2s ease;
    }
    .btn-ghost:hover { border-color: var(--sage); color: var(--sage); }

    .form-control, .form-select {
      width: 100%;
      border-radius: 11px; border: 1px solid var(--rule); padding: 10px 12px;
      font-size: .92rem; background: var(--paper); color: var(--ink);
      transition: border-color .2s, box-shadow .2s;
      font-family: 'Manrope', sans-serif;
    }
    .form-control:focus, .form-select:focus {
      outline: none; border-color: var(--sage);
      box-shadow: 0 0 0 4px rgba(31,95,77,.12);
    }

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

        <!-- Back link -->
        <div class="mb-3" data-aos="fade-up">
          <a href="mes_demandes.php" class="btn-ghost">
            <i class="bi bi-arrow-left"></i> Retour à mes demandes
          </a>
        </div>

        <!-- Page header -->
        <div class="text-center mb-4" data-aos="fade-up" style="max-width: 760px; margin: 0 auto;">
          <span class="eyebrow honey"><span class="dot"></span> Propositions reçues</span>
          <h1 class="display-x mt-3 mb-2">Les <span class="accent">offres</span> des freelancers.</h1>
          <p class="lead-x mb-0">
            <?php if ($count === 0): ?>
              Aucune proposition n'a encore été reçue pour cette demande.
            <?php else: ?>
              <?= $count ?> proposition<?= $count > 1 ? 's' : '' ?> reçue<?= $count > 1 ? 's' : '' ?> — comparez les profils, les budgets et les messages avant de choisir.
            <?php endif; ?>
          </p>
        </div>

        <!-- Demande summary -->
        <div class="summary-card mb-4" data-aos="fade-up" data-aos-delay="60">
          <div>
            <span style="font-size:.7rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--ink-soft);">Demande</span>
            <h2><?= htmlspecialchars($demande['title']) ?></h2>
            <div class="chip-row">
              <span class="chip sage"><i class="bi bi-cash"></i> <?= fmt_price($demande['price']) ?> TND</span>
              <span class="chip honey"><i class="bi bi-calendar-event"></i> Échéance : <?= fmt_date($demande['deadline']) ?></span>
            </div>
            <p class="desc"><?= htmlspecialchars($demande['description']) ?></p>
          </div>
          <div class="d-flex gap-2 align-items-start" style="white-space: nowrap;">
            <a href="edit_demande.php?id=<?= (int)$demande['id'] ?>" class="btn-ghost">
              <i class="bi bi-pencil"></i> Modifier
            </a>
          </div>
        </div>

        <!-- KPIs -->
        <div class="row g-3 mb-4" data-aos="fade-up" data-aos-delay="100">
          <div class="col-md-4">
            <div class="kpi">
              <div class="head">
                <span class="lbl">Propositions</span>
                <span class="ic-sm t-sage"><i class="bi bi-inboxes"></i></span>
              </div>
              <div class="num"><?= $count ?></div>
              <div class="sub">Reçues sur cette demande</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="kpi">
              <div class="head">
                <span class="lbl">Prix minimum</span>
                <span class="ic-sm t-sage"><i class="bi bi-arrow-down-circle"></i></span>
              </div>
              <div class="num"><?= $count > 0 ? fmt_price($minPrice) : '—' ?> <span style="font-size:.85rem; font-weight:700; color: var(--ink-mute);"><?= $count > 0 ? 'TND' : '' ?></span></div>
              <div class="sub">Offre la plus basse</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="kpi">
              <div class="head">
                <span class="lbl">Prix moyen</span>
                <span class="ic-sm t-honey"><i class="bi bi-bar-chart"></i></span>
              </div>
              <div class="num"><?= $count > 0 ? fmt_price($avgPrice) : '—' ?> <span style="font-size:.85rem; font-weight:700; color: var(--ink-mute);"><?= $count > 0 ? 'TND' : '' ?></span></div>
              <div class="sub">Sur l'ensemble des offres</div>
            </div>
          </div>
        </div>

        <!-- Tri (visible uniquement si propositions) -->
        <?php if ($count > 0): ?>
          <form method="GET" action="demande_propositions.php"
                class="d-flex justify-content-end align-items-center gap-2 mb-3"
                data-aos="fade-up" data-aos-delay="140">
            <input type="hidden" name="id" value="<?= (int)$demandeId ?>">
            <label for="sort" style="font-size:.85rem; color: var(--ink-mute); font-weight:600;">Trier :</label>
            <select id="sort" name="sort" class="form-select" style="max-width: 180px;" onchange="this.form.submit()">
              <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Plus récent</option>
              <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Plus ancien</option>
            </select>
          </form>
        <?php endif; ?>

        <?php $isClosed = ($demande['status'] ?? 'open') === 'closed'; ?>
        <?php if ($flashError): ?>
          <div class="closed-banner" style="background:#FEF2F2; border-color:#FECACA; color:#991B1B;">
            <i class="bi bi-exclamation-circle"></i>
            <div><?= htmlspecialchars($flashError) ?></div>
          </div>
        <?php endif; ?>
        <?php if ($isClosed): ?>
          <div class="closed-banner" data-aos="fade-up">
            <i class="bi bi-lock-fill"></i>
            <div><strong>Demande clôturée.</strong> Vous avez accepté une proposition — la conversation est ouverte côté <em>Mes Conversations</em>.</div>
          </div>
        <?php endif; ?>

        <!-- Liste -->
        <?php if ($count === 0): ?>
          <div class="empty-card" data-aos="fade-up" data-aos-delay="160">
            <div class="emoji"><i class="bi bi-hourglass-split"></i></div>
            <h3>Aucune proposition reçue pour l'instant</h3>
            <p>Patientez — les freelancers vont arriver. Vous pouvez aussi affiner votre demande pour la rendre plus attractive.</p>
          </div>
        <?php else: ?>
          <div class="row g-3">
            <?php foreach ($propositions as $p):
                $fl       = $freelancers[(int)($p['user_id'] ?? 0)] ?? null;
                $realName = $fl ? trim(($fl['prenom'] ?? '') . ' ' . ($fl['nom'] ?? '')) : '';
                $name     = $realName !== '' ? $realName : trim($p['freelancer_name'] ?? '');
                if ($name === '') { $name = 'Freelancer SkillBridge'; }
                $initial  = strtoupper(mb_substr($name, 0, 1, 'UTF-8') ?: '?');
                $hasPhoto = $fl && !empty($fl['photo']);
                $photoSrc = $hasPhoto
                    ? 'assets/img/profile/' . htmlspecialchars($fl['photo'])
                    : 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=1F5F4D&color=fff&bold=true&size=120';
                $location = $fl['localisation'] ?? '';
                $skills   = !empty($fl['competences']) ? array_slice(array_filter(array_map('trim', explode(',', $fl['competences']))), 0, 1) : [];
                $topSkill = $skills[0] ?? '';
                $pStatus  = $p['status'] ?? 'pending';
                $cardCls  = 'proposition-card' . ($pStatus === 'accepted' ? ' is-accepted' : ($pStatus === 'declined' ? ' is-declined' : ''));
                $statusLabel = ['pending' => 'En attente', 'accepted' => 'Acceptée', 'declined' => 'Refusée'][$pStatus] ?? 'En attente';
            ?>
              <div class="col-12 col-lg-6" data-aos="fade-up">
                <div class="<?= $cardCls ?>">
                  <div class="head-row">
                    <div class="freelancer">
                      <img class="avatar-disc" src="<?= $photoSrc ?>" alt=""
                           onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?= urlencode($name) ?>&background=1F5F4D&color=fff&bold=true&size=120';">
                      <div>
                        <div class="name">
                          <?php if ($fl): ?>
                            <a href="profil.php?id=<?= (int)$fl['id'] ?>" style="color:inherit;text-decoration:none;"><?= htmlspecialchars($name) ?></a>
                            <span class="verif" title="Profil vérifié"><i class="bi bi-check-lg"></i></span>
                          <?php else: ?>
                            <?= htmlspecialchars($name) ?>
                          <?php endif; ?>
                        </div>
                        <?php if ($topSkill || $location): ?>
                          <div class="role-loc">
                            <?php if ($topSkill): ?><i class="bi bi-stars"></i> <?= htmlspecialchars($topSkill) ?><?php endif; ?>
                            <?php if ($topSkill && $location): ?><span style="opacity:.5;">·</span><?php endif; ?>
                            <?php if ($location): ?><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($location) ?><?php endif; ?>
                          </div>
                        <?php endif; ?>
                        <div class="meta"><i class="bi bi-clock"></i> <?= fmt_datetime($p['created_at']) ?></div>
                      </div>
                    </div>
                    <div style="display:flex; flex-direction:column; align-items:flex-end; gap:6px;">
                      <span class="price-badge">
                        <i class="bi bi-cash"></i> <?= fmt_price($p['price']) ?> TND
                      </span>
                      <span class="status-badge <?= $pStatus ?>">
                        <?php if ($pStatus === 'accepted'): ?><i class="bi bi-check-circle-fill"></i><?php endif; ?>
                        <?php if ($pStatus === 'declined'): ?><i class="bi bi-x-circle-fill"></i><?php endif; ?>
                        <?php if ($pStatus === 'pending'):  ?><i class="bi bi-hourglass-split"></i><?php endif; ?>
                        <?= $statusLabel ?>
                      </span>
                    </div>
                  </div>
                  <div class="message"><?= htmlspecialchars($p['message']) ?></div>

                  <div class="accept-bar">
                    <?php if ($fl): ?>
                      <a href="../chat/new_conversation.php?user2=<?= (int)$fl['id'] ?>"
                         class="btn-ghost" style="padding:8px 14px; font-size:.85rem;">
                        <i class="bi bi-chat-dots"></i> Contacter
                      </a>
                    <?php else: ?>
                      <span></span>
                    <?php endif; ?>
                    <?php if (!$isClosed && $pStatus === 'pending'): ?>
                      <form method="POST" action="demande_propositions.php?id=<?= (int)$demandeId ?>"
                            onsubmit="return confirm('Accepter cette proposition ? Toutes les autres seront automatiquement refusées et la demande clôturée.');"
                            style="margin:0;">
                        <input type="hidden" name="action" value="accept">
                        <input type="hidden" name="proposition_id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn-accept">
                          <i class="bi bi-check-circle-fill"></i> Accepter cette offre
                        </button>
                      </form>
                    <?php elseif ($pStatus === 'accepted'): ?>
                      <a href="../chat/conversations.php" class="btn-sage" style="padding:8px 14px; font-size:.85rem;">
                        <i class="bi bi-chat-dots-fill"></i> Ouvrir la conversation
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
