<?php
require_once 'auth_check.php';

require_once '../../../config.php';
require_once '../../../model/utilisateur.php';
require_once '../../../model/profil.php';

$BASE = base_url();

// Charger l'utilisateur connecté
$utilisateurModel = new Utilisateur($pdo);
$utilisateurModel->id = $_SESSION['user_id'];
$utilisateurModel->readOne();

$utilisateur = [
    'id'               => $utilisateurModel->id,
    'nom'              => $utilisateurModel->nom,
    'prenom'           => $utilisateurModel->prenom,
    'email'            => $utilisateurModel->email,
    'role'             => $utilisateurModel->role,
    'telephone'        => $utilisateurModel->telephone,
    'photo'            => $utilisateurModel->photo,
    'date_inscription' => $utilisateurModel->date_inscription,
];

// Charger le profil
$profilModel = new Profil($pdo);
$profilModel->utilisateur_id = $_SESSION['user_id'];
$profilModel->readByUserId();

$profil = [
    'bio'          => $profilModel->bio ?? '',
    'competences'  => $profilModel->competences ?? '',
    'localisation' => $profilModel->localisation ?? '',
    'site_web'     => $profilModel->site_web ?? '',
];

// Profile completion
$completion_items = [
    'Photo'        => !empty($utilisateur['photo']),
    'Téléphone'    => !empty($utilisateur['telephone']),
    'Bio'          => !empty($profil['bio']),
    'Compétences'  => !empty($profil['competences']),
    'Localisation' => !empty($profil['localisation']),
    'Site Web'     => !empty($profil['site_web']),
];

$completed = count(array_filter($completion_items));
$total     = count($completion_items);
$percent   = $total > 0 ? round(($completed / $total) * 100) : 0;

if ($percent === 100)      { $bar_label = 'Profil complet'; }
elseif ($percent >= 80)    { $bar_label = 'Presque terminé'; }
elseif ($percent >= 40)    { $bar_label = 'En cours'; }
else                       { $bar_label = 'À compléter'; }

$avatar_fallback = 'https://ui-avatars.com/api/?name=' . urlencode(trim($utilisateur['prenom'] . ' ' . $utilisateur['nom'])) . '&background=1F5F4D&color=fff&size=256&font-size=0.4&bold=true';
$role_label    = ucfirst($utilisateur['role']);
$is_freelancer = strtolower($utilisateur['role']) === 'freelancer';

$navFirstName = trim(explode(' ', trim($utilisateur['prenom'] ?? ''))[0] ?? '') ?: 'Profil';
$navAvatarSrc = !empty($utilisateur['photo'])
    ? 'assets/img/profile/' . htmlspecialchars($utilisateur['photo'])
    : 'https://ui-avatars.com/api/?name=' . urlencode(trim(($utilisateur['prenom'] ?? '') . ' ' . ($utilisateur['nom'] ?? '')) ?: 'SkillBridge') . '&background=1F5F4D&color=fff&bold=true&size=80';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Mon Profil — SkillBridge</title>

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
    .display-l { font-size: clamp(1.5rem, 2.2vw, 2rem); line-height: 1.1; font-weight: 800; letter-spacing: -.02em; }
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

    /* ----------------- Header ----------------- */
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
    .sb-profile-chip.is-active { border-color: var(--sage); background: var(--sage-soft); color: var(--sage); }
    .sb-profile-chip .avatar { width:30px; height:30px; border-radius:50%; object-fit:cover; }
    @media (max-width: 991.98px) { .sb-nav { display: none; } }

    /* ----------------- Page canvas ----------------- */
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

    /* ----------------- Cards ----------------- */
    .auth-card {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 22px;
      padding: 30px 28px;
      box-shadow: 0 30px 60px -25px rgba(31,95,77,.18);
    }
    .auth-card .card-head {
      display: flex; align-items: center; gap: 10px; margin-bottom: 22px;
    }
    .auth-card .card-head h4 { font-weight:800; font-size: 1.25rem; margin: 0; }

    /* ----------------- Avatar with sage ring + honey verified ----------------- */
    .avatar-wrap {
      position: relative; width: 152px; height: 152px; margin: 0 auto;
    }
    .avatar-wrap::before {
      content:''; position: absolute; inset: -6px; border-radius: 50%;
      background: var(--sage); z-index: 0;
    }
    .avatar-wrap img {
      position: relative; z-index: 1;
      width: 152px; height: 152px; border-radius: 50%;
      object-fit: cover; border: 5px solid var(--paper); background: var(--paper);
      box-shadow: 0 10px 24px -10px rgba(15,15,15,.3);
    }
    .avatar-wrap .verified {
      position: absolute; right: 4px; bottom: 4px; z-index: 2;
      width: 36px; height: 36px; border-radius: 50%;
      background: var(--honey); color: var(--ink);
      display: flex; align-items: center; justify-content: center;
      border: 4px solid var(--paper); font-size: 1rem; font-weight: 700;
    }

    .role-pill {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 5px 14px; border-radius: 999px;
      font-weight: 700; font-size: .8rem; letter-spacing: -.005em;
    }
    .role-pill.client     { background: var(--sage-soft);  color: var(--sage); }
    .role-pill.freelancer { background: var(--honey-soft); color: #92660A; }

    /* ----------------- Completion ----------------- */
    .completion-head {
      display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px;
    }
    .completion-head .label { font-size: .82rem; font-weight: 600; color: var(--ink); }
    .completion-head .pct {
      font-size: .82rem; font-weight: 700; color: var(--sage);
    }
    .completion-bar {
      height: 8px; border-radius: 999px; background: var(--bg); overflow: hidden;
    }
    .completion-bar .fill {
      height: 100%; background: var(--sage); transition: width 1s ease; border-radius: 999px;
    }

    .checklist { list-style: none; padding: 0; margin: 16px 0 0; display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
    .checklist li {
      display: flex; align-items: center; gap: 8px;
      padding: 8px 10px; border-radius: 10px;
      font-size: .82rem; transition: background .2s ease;
    }
    .checklist li.done {
      background: var(--sage-soft); color: var(--sage); font-weight: 600;
    }
    .checklist li.pending {
      background: var(--bg); color: var(--ink-mute);
    }
    .checklist li i { font-size: .95rem; }
    @media (max-width: 575.98px) { .checklist { grid-template-columns: 1fr; } }

    /* ----------------- Info tiles ----------------- */
    .info-tile {
      display: flex; align-items: flex-start; gap: 12px;
      padding: 12px 14px; border-radius: 13px; background: var(--bg);
      margin-bottom: 8px;
    }
    .info-tile .icon-bubble {
      width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      background: var(--paper); color: var(--sage);
      box-shadow: 0 2px 6px rgba(15,15,15,.04);
    }
    .info-tile .label { font-size: .7rem; color: var(--ink-soft); text-transform: uppercase; letter-spacing: .06em; font-weight: 600; }
    .info-tile .value { font-weight: 600; color: var(--ink); font-size: .92rem; word-break: break-word; }

    /* ----------------- Form ----------------- */
    .field-section-title {
      font-size: .8rem; text-transform: uppercase; letter-spacing: .08em;
      color: var(--ink-soft); font-weight: 700; margin: 4px 0 10px;
    }
    .form-label { font-weight: 600; color: var(--ink-2); font-size: .87rem; margin-bottom: 6px; display: block; }
    .form-control, .form-select {
      width: 100%;
      border-radius: 12px; border: 1px solid var(--rule); padding: 11px 14px;
      font-size: .95rem; background: var(--paper); color: var(--ink);
      transition: border-color .2s, box-shadow .2s;
      font-family: 'Manrope', sans-serif;
    }
    .form-control:focus, .form-select:focus {
      outline: none;
      border-color: var(--sage);
      box-shadow: 0 0 0 4px rgba(31,95,77,.12);
    }
    .form-control:disabled, .form-control[readonly] { background: var(--bg); color: var(--ink-mute); }
    .form-control.is-invalid { border-color: #DC2626; }
    .form-text { color: var(--ink-soft); font-size: .82rem; }
    textarea.form-control { resize: vertical; min-height: 120px; line-height: 1.55; }
    input[type="file"].form-control { padding: 10px 14px; }

    /* photo current preview */
    .photo-current {
      display: flex; align-items: center; gap: 12px;
      padding: 12px 14px; background: var(--sage-soft); border-radius: 12px;
      margin-bottom: 10px;
    }
    .photo-current img { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
    .photo-current .ttl { font-weight: 700; color: var(--ink); font-size: .9rem; }
    .photo-current .sub { color: var(--ink-mute); font-size: .8rem; }

    /* ----------------- Submit ----------------- */
    .btn-sage {
      display: flex; align-items: center; justify-content: center; gap: 10px;
      width: 100%; padding: 14px 22px; border-radius: 12px; border: none;
      background: var(--sage); color: var(--paper);
      font-weight: 700; font-size: 1rem; cursor: pointer;
      transition: all .2s ease;
    }
    .btn-sage:hover {
      background: var(--sage-d); transform: translateY(-2px);
      box-shadow: 0 14px 28px -12px rgba(31,95,77,.4);
    }
    .btn-ghost {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--paper); color: var(--ink);
      padding: 11px 18px; border-radius: 10px;
      border: 1px solid var(--rule);
      text-decoration: none; font-weight: 600; font-size: .9rem;
      transition: all .2s ease;
    }
    .btn-ghost:hover { border-color: var(--sage); color: var(--sage); }

    /* ----------------- Footer ----------------- */
    .sb-footer { background: var(--ink); color: rgba(255,255,255,.65); padding: 22px 0; font-size: .88rem; text-align: center; }
    .sb-footer strong { color: var(--paper); }

    /* ----------------- Toast ----------------- */
    .toast { background: var(--paper) !important; border: 1px solid var(--rule) !important; border-radius: 14px !important; box-shadow: 0 18px 36px -16px rgba(15,15,15,.18); }
    .toast .toast-body { color: var(--ink); padding: 14px 16px; font-weight: 500; }
    .toast.bg-success { border-left: 4px solid var(--sage) !important; }
    .toast.bg-danger  { border-left: 4px solid #DC2626 !important; }
    .toast.bg-success .toast-body { color: var(--sage); }
    .toast.bg-danger  .toast-body { color: #991B1B; }
    .toast .btn-close-white { filter: none; }

    /* legacy hooks */
    .navmenu a.active { color: var(--sage) !important; }

    @media (max-width: 991.98px) {
      .auth-card { padding: 24px 22px; }
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
        <a href="index.php">Accueil</a>
        <a href="../chat/conversations.php">Conversations</a>
        <?php if ($is_freelancer): ?>
          <a href="browse_demandes.php">Demandes</a>
          <a href="mes_propositions.php">Mes propositions</a>
        <?php else: ?>
          <a href="mes_demandes.php">Mes demandes</a>
        <?php endif; ?>
      </nav>
      <div class="d-flex align-items-center gap-2">
        <span id="bellSlot" class="sb-bell-btn" style="display:inline-flex;"></span>
        <a href="profil.php" class="sb-profile-chip is-active" title="Mon Profil">
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
        <div class="text-center mb-5" data-aos="fade-up" style="max-width: 720px; margin: 0 auto;">
          <span class="eyebrow"><span class="dot"></span> Espace personnel</span>
          <h1 class="display-x mt-3 mb-2">Mon <span class="accent">profil</span>.</h1>
          <p class="lead-x mb-0">
            Gérez vos informations personnelles et complétez votre profil pour gagner en visibilité.
          </p>
        </div>

        <div class="row g-4 justify-content-center">

          <!-- Left column : avatar + completion + info -->
          <div class="col-lg-4" data-aos="fade-right" data-aos-delay="100">
            <div class="auth-card text-center">

              <!-- Avatar -->
              <div class="avatar-wrap mb-3">
                <?php if (!empty($utilisateur['photo'])): ?>
                  <img src="assets/img/profile/<?= htmlspecialchars($utilisateur['photo']) ?>" alt="Avatar" onerror="this.onerror=null;this.src='<?= htmlspecialchars($avatar_fallback) ?>';">
                <?php else: ?>
                  <img src="<?= htmlspecialchars($avatar_fallback) ?>" alt="Avatar">
                <?php endif; ?>
                <span class="verified" title="Profil vérifié"><i class="bi bi-check-lg"></i></span>
              </div>

              <h3 class="mb-1" style="font-weight: 800; font-size: 1.4rem;">
                <?= htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?>
              </h3>

              <span class="role-pill <?= $is_freelancer ? 'freelancer' : 'client' ?> mb-4">
                <i class="bi <?= $is_freelancer ? 'bi-tools' : 'bi-briefcase-fill' ?>"></i>
                <?= htmlspecialchars($role_label) ?>
              </span>

              <!-- Completion -->
              <div class="text-start mt-3">
                <div class="completion-head">
                  <span class="label">Complétion du profil</span>
                  <span class="pct"><?= $percent ?>% — <?= $bar_label ?></span>
                </div>
                <div class="completion-bar">
                  <div class="fill" style="width: <?= $percent ?>%;"></div>
                </div>

                <ul class="checklist">
                  <?php foreach ($completion_items as $label => $done): ?>
                    <li class="<?= $done ? 'done' : 'pending' ?>">
                      <i class="bi <?= $done ? 'bi-check-circle-fill' : 'bi-circle' ?>"></i>
                      <span><?= $label ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <!-- Info tiles -->
              <div class="text-start mt-4">
                <div class="info-tile">
                  <div class="icon-bubble"><i class="bi bi-envelope"></i></div>
                  <div class="flex-grow-1" style="min-width:0;">
                    <div class="label">Email</div>
                    <div class="value"><?= htmlspecialchars($utilisateur['email']) ?></div>
                  </div>
                </div>
                <div class="info-tile">
                  <div class="icon-bubble"><i class="bi bi-telephone"></i></div>
                  <div class="flex-grow-1">
                    <div class="label">Téléphone</div>
                    <div class="value">
                      <?= !empty($utilisateur['telephone']) ? htmlspecialchars($utilisateur['telephone']) : '<span style="color:var(--ink-soft); font-weight:500;">Non renseigné</span>' ?>
                    </div>
                  </div>
                </div>
                <div class="info-tile">
                  <div class="icon-bubble"><i class="bi bi-calendar-check"></i></div>
                  <div class="flex-grow-1">
                    <div class="label">Membre depuis</div>
                    <div class="value"><?= date('d/m/Y', strtotime($utilisateur['date_inscription'])) ?></div>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <!-- Right column : edit form -->
          <div class="col-lg-8" data-aos="fade-left" data-aos-delay="200">
            <div class="auth-card">

              <div class="card-head">
                <span class="eyebrow honey"><span class="dot"></span> Modifier</span>
                <h4>Mes informations</h4>
              </div>

              <form id="profilForm" action="../../../controller/utilisateurcontroller.php" method="POST" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="id" value="<?= htmlspecialchars($utilisateur['id']) ?>">
                <input type="hidden" name="old_photo" value="<?= htmlspecialchars($utilisateur['photo'] ?? '') ?>">

                <!-- Identité -->
                <div class="field-section-title">Identité</div>
                <div class="row gy-3 mb-2">
                  <div class="col-md-6">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" name="nom" id="nom" class="form-control" value="<?= htmlspecialchars($utilisateur['nom']) ?>">
                    <div id="nom-error" class="text-danger mt-1" style="font-size:.85rem; display:none;"></div>
                  </div>
                  <div class="col-md-6">
                    <label for="prenom" class="form-label">Prénom</label>
                    <input type="text" name="prenom" id="prenom" class="form-control" value="<?= htmlspecialchars($utilisateur['prenom']) ?>">
                    <div id="prenom-error" class="text-danger mt-1" style="font-size:.85rem; display:none;"></div>
                  </div>

                  <div class="col-md-8">
                    <label for="email" class="form-label">Adresse email</label>
                    <input type="text" name="email" id="email" class="form-control" value="<?= htmlspecialchars($utilisateur['email']) ?>">
                    <div id="email-error" class="text-danger mt-1" style="font-size:.85rem; display:none;"></div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Rôle</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($role_label) ?>" disabled>
                  </div>

                  <div class="col-md-6">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <input type="text" name="telephone" id="telephone" class="form-control" value="<?= htmlspecialchars($utilisateur['telephone'] ?? '') ?>" placeholder="+216 XX XXX XXX">
                  </div>
                  <div class="col-md-6">
                    <label for="localisation" class="form-label">Localisation</label>
                    <input type="text" name="localisation" id="localisation" class="form-control"
                           value="<?= htmlspecialchars($profil['localisation']) ?>"
                           placeholder="ex: Tunis, Tunisie">
                  </div>
                </div>

                <!-- À propos -->
                <div class="field-section-title mt-4">À propos</div>
                <div class="row gy-3 mb-2">
                  <div class="col-12">
                    <label for="bio" class="form-label">Bio / Description</label>
                    <textarea name="bio" id="bio" class="form-control" rows="4" placeholder="Présentez-vous en quelques mots, parlez de votre parcours, vos passions..."><?= htmlspecialchars($profil['bio']) ?></textarea>
                  </div>
                  <div class="col-md-8">
                    <label for="competences" class="form-label">Compétences</label>
                    <input type="text" name="competences" id="competences" class="form-control"
                           value="<?= htmlspecialchars($profil['competences']) ?>"
                           placeholder="ex: PHP, MySQL, React, UX Design">
                    <div class="form-text"><i class="bi bi-info-circle me-1"></i>Séparez chaque compétence par une virgule</div>
                  </div>
                  <div class="col-md-4">
                    <label for="site_web" class="form-label">Site web</label>
                    <input type="url" name="site_web" id="site_web" class="form-control"
                           value="<?= htmlspecialchars($profil['site_web']) ?>"
                           placeholder="https://...">
                  </div>
                </div>

                <!-- Photo -->
                <div class="field-section-title mt-4">Photo de profil</div>
                <div class="row gy-3 mb-2">
                  <div class="col-12">
                    <?php if (!empty($utilisateur['photo'])): ?>
                      <div class="photo-current">
                        <img src="assets/img/profile/<?= htmlspecialchars($utilisateur['photo']) ?>" alt="Photo actuelle">
                        <div>
                          <div class="ttl">Photo actuelle</div>
                          <small class="sub">Choisissez un fichier ci-dessous pour la remplacer</small>
                        </div>
                      </div>
                    <?php endif; ?>
                    <input type="file" name="photo" id="photo" class="form-control" accept="image/*">
                    <div class="form-text"><i class="bi bi-image me-1"></i>JPG ou PNG, idéalement carré (max 5 Mo)</div>
                  </div>
                </div>

                <!-- Sécurité -->
                <div class="field-section-title mt-4">Sécurité</div>
                <div class="row gy-3 mb-4">
                  <div class="col-md-6">
                    <label for="new_password" class="form-label">Nouveau mot de passe</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Laisser vide pour ne pas changer">
                    <div id="newpwd-error" class="text-danger mt-1" style="font-size:.85rem; display:none;"></div>
                  </div>
                  <div class="col-md-6">
                    <label for="confirm_new_password" class="form-label">Confirmation</label>
                    <input type="password" name="confirm_new_password" id="confirm_new_password" class="form-control" placeholder="Répétez le nouveau mot de passe">
                    <div id="confirmpwd-error" class="text-danger mt-1" style="font-size:.85rem; display:none;"></div>
                  </div>
                </div>

                <button type="submit" class="btn-sage">
                  <i class="bi bi-check2-circle"></i>
                  Enregistrer les modifications
                </button>

              </form>

            </div>
          </div>

        </div>
      </div>
    </section>
  </main>

  <footer class="sb-footer">
    © <?= date('Y') ?> <strong>SkillBridge</strong> — Tous droits réservés.
  </footer>

  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="../../shared/chatbus.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (typeof ChatBus !== 'undefined') {
        ChatBus.init({ apiBase: '../../../api/chat.php', user: <?= (int)$utilisateur['id'] ?>, conv: 0 });
        ChatBus.mountBell('#bellSlot');
      }
    });

    if (typeof AOS !== 'undefined') {
      AOS.init({ duration: 600, easing: 'ease-out-cubic', once: true });
    }

    document.getElementById('profilForm').addEventListener('submit', function(e) {
      let valid = true;

      ['nom','prenom','email','new_password','confirm_new_password'].forEach(function(id) {
        const field = document.getElementById(id);
        if (field) field.classList.remove('is-invalid', 'is-valid');
      });
      ['nom-error','prenom-error','email-error','newpwd-error','confirmpwd-error'].forEach(function(id) {
        const el = document.getElementById(id);
        if (el) { el.textContent = ''; el.style.display = 'none'; }
      });

      function showError(fieldId, errorId, msg) {
        const field = document.getElementById(fieldId);
        const err   = document.getElementById(errorId);
        if (field) field.classList.add('is-invalid');
        if (err)   { err.textContent = msg; err.style.display = 'block'; }
        valid = false;
      }

      const nom    = document.getElementById('nom').value.trim();
      const prenom = document.getElementById('prenom').value.trim();
      const email  = document.getElementById('email').value.trim();
      const newPwd = document.getElementById('new_password').value;
      const confPwd = document.getElementById('confirm_new_password').value;
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (nom === '')   showError('nom',   'nom-error',   'Le nom est obligatoire.');
      if (prenom === '') showError('prenom', 'prenom-error', 'Le prénom est obligatoire.');
      if (email === '')  showError('email',  'email-error',  "L'email est obligatoire.");
      else if (!emailRegex.test(email)) showError('email', 'email-error', 'Format invalide.');
      if (newPwd !== '' && newPwd.length < 8) showError('new_password', 'newpwd-error', 'Minimum 8 caractères.');
      if (newPwd !== '' && newPwd !== confPwd) showError('confirm_new_password', 'confirmpwd-error', 'Les mots de passe ne correspondent pas.');

      if (!valid) e.preventDefault();
    });
  </script>

  <!-- Toast Container -->
  <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
    <div id="toastSuccess" class="toast align-items-center bg-success border-0" role="alert">
      <div class="d-flex">
        <div class="toast-body">
          <i class="bi bi-check-circle me-2"></i>
          <span id="toastSuccessMsg"></span>
        </div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>

    <div id="toastError" class="toast align-items-center bg-danger border-0 mt-2" role="alert">
      <div class="d-flex">
        <div class="toast-body">
          <i class="bi bi-exclamation-circle me-2"></i>
          <span id="toastErrorMsg"></span>
        </div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  </div>

  <script>
    function showToast(type, message) {
      const toastEl = document.getElementById('toast' + type);
      const msgEl   = document.getElementById('toast' + type + 'Msg');
      msgEl.textContent = message;
      const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
      toast.show();
    }

    <?php if (isset($_SESSION['success'])): ?>
      showToast('Success', '<?= addslashes($_SESSION['success']) ?>');
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
      showToast('Error', '<?= addslashes($_SESSION['error']) ?>');
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
  </script>

</body>
</html>
