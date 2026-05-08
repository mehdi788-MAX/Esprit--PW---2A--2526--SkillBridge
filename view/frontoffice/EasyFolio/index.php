<?php
session_start();
require_once __DIR__ . '/../../../config.php';

$BASE       = base_url();
$isLoggedIn = !empty($_SESSION['user_id']);
$userId     = (int)($_SESSION['user_id']   ?? 0);
$userNom    = trim((string)($_SESSION['user_nom']  ?? ''));
$userRole   = (string)($_SESSION['user_role'] ?? '');
$isClient     = $userRole === 'client';
$isFreelancer = $userRole === 'freelancer';

// ---------- Avatar URL helper (CDN fallback) ----------
function avatarUrl(?string $localPhoto, string $name, string $bgHex = 'F97316', int $size = 120): string {
    if (!empty($localPhoto)) return 'assets/img/profile/' . htmlspecialchars($localPhoto);
    $clean = preg_replace('/[^A-Za-zÀ-ÿ\s]/u', '', $name) ?: 'SkillBridge';
    $url   = 'https://ui-avatars.com/api/?name=' . urlencode($clean)
           . '&background=' . $bgHex . '&color=fff&bold=true&size=' . $size;
    return $url;
}

// ---------- Live data ----------
$stats = ['freelancers' => 0, 'clients' => 0, 'conversations' => 0];
try {
    $stats['freelancers']   = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role='freelancer' AND is_active=1")->fetchColumn();
    $stats['clients']       = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role='client'     AND is_active=1")->fetchColumn();
    $stats['conversations'] = (int)$pdo->query("SELECT COUNT(*) FROM conversations")->fetchColumn();
} catch (Throwable $e) {}

// Featured freelancers (exclude self when logged in)
$featured = [];
try {
    $sql = "SELECT u.id, u.prenom, u.nom, u.photo,
                   p.bio, p.competences, p.localisation
              FROM utilisateurs u
              LEFT JOIN profils p ON p.utilisateur_id = u.id
             WHERE u.role = 'freelancer' AND u.is_active = 1";
    if ($isLoggedIn) $sql .= " AND u.id != :me";
    $sql .= " ORDER BY u.id DESC LIMIT 6";
    $stmt = $pdo->prepare($sql);
    if ($isLoggedIn) $stmt->bindValue(':me', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $featured = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {}

// ---------- Dashboard data (logged-in only) ----------
$dashboard = [
    'unread_count'    => 0,
    'conversations'   => [],
    'profile_pct'     => 0,
];
if ($isLoggedIn) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
        $stmt->execute([':uid' => $userId]);
        $dashboard['unread_count'] = (int)$stmt->fetchColumn();
    } catch (Throwable $e) {}

    try {
        $stmt = $pdo->prepare("
            SELECT c.id_conversation, c.user1_id, c.user2_id, c.date_creation,
                   u1.prenom AS u1_prenom, u1.nom AS u1_nom, u1.photo AS u1_photo,
                   u2.prenom AS u2_prenom, u2.nom AS u2_nom, u2.photo AS u2_photo,
                   (SELECT contenu FROM messages m
                      WHERE m.id_conversation = c.id_conversation
                      ORDER BY m.id_message DESC LIMIT 1) AS last_message,
                   (SELECT COUNT(*) FROM messages m
                      WHERE m.id_conversation = c.id_conversation
                        AND m.sender_id != :uid AND m.is_seen = 0) AS unseen
              FROM conversations c
              JOIN utilisateurs u1 ON u1.id = c.user1_id
              JOIN utilisateurs u2 ON u2.id = c.user2_id
             WHERE c.user1_id = :uid OR c.user2_id = :uid
             ORDER BY c.id_conversation DESC
             LIMIT 4");
        $stmt->execute([':uid' => $userId]);
        $dashboard['conversations'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {}

    // Profile completeness
    try {
        $stmt = $pdo->prepare("SELECT u.photo, u.telephone, p.bio, p.competences, p.localisation, p.site_web
                                 FROM utilisateurs u
                                 LEFT JOIN profils p ON p.utilisateur_id = u.id
                                WHERE u.id = :id");
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $checks = [
                !empty($row['photo']),
                !empty($row['telephone']),
                !empty($row['bio']),
                !empty($row['competences']),
                !empty($row['localisation']),
                !empty($row['site_web']),
            ];
            $done = count(array_filter($checks));
            $dashboard['profile_pct'] = (int)round(($done / count($checks)) * 100);
        }
    } catch (Throwable $e) {}
}

$pickSkills = fn($csv, $max = 3) => $csv ? array_slice(array_filter(array_map('trim', explode(',', $csv))), 0, $max) : [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>SkillBridge — La marketplace freelance qui matche les bons talents</title>
  <meta name="description" content="SkillBridge connecte clients et freelancers vérifiés. Publiez un projet, recevez des offres et collaborez via une messagerie temps réel.">

  <link href="assets/img/favicon.png" rel="icon">
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    :root {
      --sb-blue:   #2563eb;
      --sb-orange: #f97316;
      --sb-dark:   #0f172a;
      --sb-soft:   #f8fafc;
    }
    body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }

    /* ---------- Hero (logged-out) ---------- */
    .hero-marketing {
      background:
        radial-gradient(1200px 600px at 110% -10%, rgba(249,115,22,.18), transparent 60%),
        radial-gradient(900px 500px at -10% 110%, rgba(37,99,235,.15), transparent 60%),
        #fff;
      padding: 80px 0 60px;
    }
    .hero-marketing h1 { font-weight: 800; line-height: 1.05; letter-spacing: -.02em; color: var(--sb-dark); }
    .hero-marketing h1 .accent { background: linear-gradient(90deg, var(--sb-orange), var(--sb-blue)); -webkit-background-clip: text; background-clip: text; color: transparent; }
    .hero-img-wrap { border-radius: 28px; overflow: hidden; box-shadow: 0 30px 60px -20px rgba(15,23,42,.3); }
    .hero-img-wrap img { display: block; width: 100%; height: auto; }
    .hero-floating-card {
      position: absolute; background: #fff; border-radius: 14px; padding: 12px 16px;
      box-shadow: 0 20px 40px -10px rgba(15,23,42,.18); display: flex; gap: 10px; align-items: center;
    }

    /* ---------- Dashboard hero (logged-in) ---------- */
    .dash-hero {
      background: linear-gradient(135deg, #2563eb 0%, #7c3aed 50%, #f97316 100%);
      color: #fff; border-radius: 22px; padding: 40px 38px;
      box-shadow: 0 30px 60px -25px rgba(37,99,235,.5);
    }
    .dash-hero h1 { font-weight: 800; letter-spacing: -.01em; }
    .role-pill {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(255,255,255,.18); padding: 4px 12px; border-radius: 999px;
      font-size: .8rem; font-weight: 600; backdrop-filter: blur(8px);
    }

    /* ---------- Action cards ---------- */
    .action-card {
      background:#fff; border:1px solid #e2e8f0; border-radius:18px;
      padding: 26px; transition: all .25s ease; height: 100%;
      display: flex; flex-direction: column; text-decoration: none; color: inherit;
    }
    .action-card:hover {
      transform: translateY(-4px); box-shadow: 0 20px 40px -15px rgba(15,23,42,.12);
      border-color: var(--sb-blue); color: inherit;
    }
    .action-card .icon-wrap {
      width: 52px; height: 52px; border-radius: 13px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.4rem; margin-bottom: 16px;
    }
    .action-card .icon-blue   { background: rgba(37,99,235,.1);  color: var(--sb-blue); }
    .action-card .icon-orange { background: rgba(249,115,22,.1); color: var(--sb-orange); }
    .action-card .icon-green  { background: rgba(16,185,129,.1); color: #10b981; }
    .action-card .icon-purple { background: rgba(124,58,237,.1); color: #7c3aed; }
    .action-card .badge-count {
      display:inline-block; background: var(--sb-orange); color:#fff;
      font-size:.7rem; font-weight:700; padding:2px 8px; border-radius:999px; margin-left:6px;
    }

    /* ---------- Stats pill (hero, logged-out) ---------- */
    .stat-pill {
      background:#fff; border:1px solid #e2e8f0; border-radius:14px;
      padding:18px 14px; text-align:center;
    }
    .stat-pill .num { font-size:1.8rem; font-weight:800; line-height:1;
      background: linear-gradient(135deg, var(--sb-blue), var(--sb-orange));
      -webkit-background-clip: text; background-clip: text; color: transparent; }
    .stat-pill .lbl { font-size:.78rem; color:#64748b; margin-top:4px; font-weight:500; }

    /* ---------- Step / Feature card ---------- */
    .step-card, .feature-card {
      background:#fff; border-radius:18px; padding:30px 26px; height:100%;
      border:1px solid #e2e8f0; transition: all .22s ease;
    }
    .step-card:hover, .feature-card:hover { transform: translateY(-4px); box-shadow:0 20px 40px -15px rgba(15,23,42,.1); }
    .step-num {
      width:48px; height:48px; border-radius:14px;
      background: linear-gradient(135deg, var(--sb-blue), var(--sb-orange)); color:#fff;
      display:inline-flex; align-items:center; justify-content:center;
      font-weight:800; font-size:1.25rem; margin-bottom:16px;
    }
    .feature-card .feature-icon {
      width:54px; height:54px; border-radius:14px;
      display:flex; align-items:center; justify-content:center;
      font-size:1.5rem; margin-bottom:14px;
      background: rgba(249,115,22,.1); color: var(--sb-orange);
    }

    /* ---------- Freelancer card ---------- */
    .talent-card {
      background:#fff; border:1px solid #e2e8f0; border-radius:18px;
      overflow:hidden; height:100%; transition:all .22s ease;
      display:flex; flex-direction:column;
    }
    .talent-card:hover { transform:translateY(-5px); box-shadow:0 24px 50px -20px rgba(15,23,42,.16); }
    .talent-banner {
      height: 64px; background: linear-gradient(135deg, var(--sb-blue), var(--sb-orange));
    }
    .talent-avatar {
      width:84px; height:84px; border-radius:50%; object-fit:cover;
      border:4px solid #fff; margin: -42px auto 8px; display:block;
      box-shadow: 0 8px 20px rgba(15,23,42,.1);
    }
    .skill-chip {
      display:inline-block; padding:3px 10px; margin:2px;
      border:1px solid #e2e8f0; border-radius:999px;
      font-size:.72rem; color:#475569; background:#f8fafc; font-weight:500;
    }

    /* ---------- Category card ---------- */
    .category-card {
      background:#fff; border:1px solid #e2e8f0; border-radius:18px;
      padding:28px 22px; text-align:center; height:100%;
      transition:all .22s ease; cursor:pointer; text-decoration:none; color: inherit;
    }
    .category-card:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -15px rgba(15,23,42,.12); border-color: var(--sb-orange); color: inherit; }
    .category-card .cat-icon {
      width: 60px; height: 60px; border-radius: 16px; margin: 0 auto 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.7rem;
    }

    /* ---------- Conversation row ---------- */
    .conv-row {
      display:flex; align-items:center; gap:14px; padding:14px;
      border-radius:14px; transition: background .15s; text-decoration:none; color:inherit;
    }
    .conv-row:hover { background:#f8fafc; color:inherit; }
    .conv-row .avatar { width:48px; height:48px; border-radius:50%; object-fit:cover; flex-shrink:0; }
    .conv-row .name { font-weight:600; color: var(--sb-dark); }
    .conv-row .preview { color:#64748b; font-size:.88rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .conv-row .unseen {
      background: var(--sb-orange); color:#fff; padding:2px 8px;
      border-radius:999px; font-size:.7rem; font-weight:700;
    }

    /* ---------- Section spacing ---------- */
    .section-pad { padding: 70px 0; }
    .section-tag {
      display: inline-block; padding: 4px 14px; border-radius: 999px;
      background: rgba(37,99,235,.08); color: var(--sb-blue);
      font-weight: 600; font-size: .82rem; margin-bottom: 12px;
    }
    h2.section-title { font-weight: 800; letter-spacing: -.01em; color: var(--sb-dark); }
  </style>
</head>

<body class="index-page">

  <!-- ================== HEADER ================== -->
  <header id="header" class="header d-flex align-items-center sticky-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
      <a href="index.php" class="logo d-flex align-items-center me-auto me-xl-0">
        <h1 class="sitename">SkillBridge</h1>
      </a>
      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.php" class="active">Accueil</a></li>
          <?php if ($isLoggedIn): ?>
            <li><a href="../chat/conversations.php">Mes Conversations<?php if ($dashboard['unread_count'] > 0): ?> <span class="badge bg-danger"><?= $dashboard['unread_count'] ?></span><?php endif; ?></a></li>
            <li><a href="profil.php">Mon Profil</a></li>
            <li><a href="<?= $BASE ?>/controller/utilisateurcontroller.php?action=logout">Déconnexion</a></li>
          <?php else: ?>
            <li><a href="#how-it-works">Comment ça marche</a></li>
            <li><a href="#featured">Freelancers</a></li>
            <li><a href="#why">Pourquoi nous</a></li>
            <li><a href="login.php">Connexion</a></li>
            <li><a href="register.php">Inscription</a></li>
          <?php endif; ?>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>
    </div>
  </header>

  <main class="main">

  <?php if ($isLoggedIn): /* =================================================== DASHBOARD (logged-in) =================================================== */
    $firstName = explode(' ', $userNom)[0] ?: 'à toi';
    $roleLabel = $isClient ? 'Client' : ($isFreelancer ? 'Freelancer' : 'Administrateur');
    $roleIcon  = $isClient ? 'bi-briefcase-fill' : ($isFreelancer ? 'bi-tools' : 'bi-shield-check');
    $roleColor = $isClient ? '37,99,235' : ($isFreelancer ? '249,115,22' : '124,58,237'); // RGB
    // Image qui colle au rôle
    $heroImg   = $isClient
        ? 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?w=900&auto=format&fit=crop&q=80'
        : ($isFreelancer
            ? 'https://images.unsplash.com/photo-1531497865144-0464ef8fb9a9?w=900&auto=format&fit=crop&q=80'
            : 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=900&auto=format&fit=crop&q=80');
  ?>

    <!-- ================== HERO DASHBOARD ================== -->
    <section class="hero-marketing">
      <div class="container">
        <div class="row align-items-center g-5">
          <div class="col-lg-6" data-aos="fade-right">
            <span class="section-tag" style="background: rgba(<?= $roleColor ?>,.1); color: rgb(<?= $roleColor ?>);">
              <i class="bi <?= $roleIcon ?> me-1"></i> Espace <?= $roleLabel ?>
            </span>
            <h1 class="display-3 mt-3 mb-4">
              Bonjour <span class="accent"><?= htmlspecialchars($firstName) ?></span>,<br>
              ravi de vous revoir.
            </h1>
            <p class="lead text-muted mb-4">
              <?php if ($isFreelancer): ?>
                Continuez vos conversations, mettez votre profil à jour et restez visible auprès des clients.
              <?php elseif ($isClient): ?>
                Continuez vos collaborations, découvrez de nouveaux talents et démarrez de nouveaux projets.
              <?php else: ?>
                Continuez votre travail d'administration sur SkillBridge.
              <?php endif; ?>
            </p>
            <div class="d-flex flex-wrap gap-2 mb-4">
              <a href="../chat/conversations.php" class="btn btn-lg" style="background: linear-gradient(135deg, var(--sb-orange), var(--sb-blue)); color:#fff; font-weight:600; padding: 14px 28px; border-radius:14px;">
                <i class="bi bi-chat-dots me-1"></i> Mes Conversations
                <?php if ($dashboard['unread_count'] > 0): ?>
                  <span class="badge bg-light text-dark ms-2"><?= $dashboard['unread_count'] ?> non lu<?= $dashboard['unread_count'] > 1 ? 's' : '' ?></span>
                <?php endif; ?>
              </a>
              <a href="profil.php" class="btn btn-lg btn-outline-dark" style="border-radius:14px; padding: 14px 28px;">
                <i class="bi bi-person-circle me-1"></i> Mon profil
              </a>
            </div>
            <!-- Stats personnels -->
            <div class="row g-2 mt-4">
              <div class="col-4"><div class="stat-pill"><div class="num"><?= count($dashboard['conversations']) ?></div><div class="lbl">Conversations</div></div></div>
              <div class="col-4"><div class="stat-pill"><div class="num"><?= $dashboard['unread_count'] ?></div><div class="lbl">Non lus</div></div></div>
              <div class="col-4"><div class="stat-pill"><div class="num"><?= $dashboard['profile_pct'] ?>%</div><div class="lbl">Profil complété</div></div></div>
            </div>
          </div>

          <div class="col-lg-6 d-none d-lg-block position-relative" data-aos="fade-left">
            <div class="hero-img-wrap">
              <img src="<?= $heroImg ?>" alt="Espace personnel <?= $roleLabel ?>" loading="eager">
            </div>
            <!-- Cards flottantes contextuelles -->
            <div class="hero-floating-card" style="top:30px; left:-30px;">
              <div style="width:38px;height:38px;border-radius:10px;background:rgba(37,99,235,.1);display:flex;align-items:center;justify-content:center;color:var(--sb-blue);"><i class="bi bi-bell-fill"></i></div>
              <div>
                <div class="fw-bold small"><?= $dashboard['unread_count'] ?> notification<?= $dashboard['unread_count'] !== 1 ? 's' : '' ?></div>
                <div class="small text-muted" style="font-size:.75rem;"><?= $dashboard['unread_count'] > 0 ? 'À consulter' : 'Tout est lu' ?></div>
              </div>
            </div>
            <div class="hero-floating-card" style="bottom:30px; right:-20px;">
              <div style="width:38px;height:38px;border-radius:10px;background:rgba(249,115,22,.1);display:flex;align-items:center;justify-content:center;color:var(--sb-orange);"><i class="bi bi-graph-up-arrow"></i></div>
              <div>
                <div class="fw-bold small">Profil <?= $dashboard['profile_pct'] ?>%</div>
                <div class="small text-muted" style="font-size:.75rem;"><?= $dashboard['profile_pct'] >= 80 ? 'Excellent !' : 'À compléter' ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ================== ACTIONS RAPIDES ================== -->
    <section class="section-pad" style="background:#f8fafc;">
      <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
          <span class="section-tag">Que voulez-vous faire ?</span>
          <h2 class="section-title display-5 mb-3">Vos accès rapides</h2>
          <p class="lead text-muted">Tout ce dont vous avez besoin pour avancer aujourd'hui.</p>
        </div>
        <div class="row g-4">

          <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
            <a href="../chat/conversations.php" class="step-card text-decoration-none" style="display:block; color:inherit;">
              <span class="step-num"><i class="bi bi-chat-dots-fill"></i></span>
              <h4 class="fw-bold">Mes Conversations
                <?php if ($dashboard['unread_count'] > 0): ?>
                  <span class="badge ms-1" style="background:var(--sb-orange);"><?= $dashboard['unread_count'] ?></span>
                <?php endif; ?>
              </h4>
              <p class="text-muted">Reprenez vos discussions là où vous les avez laissées.</p>
              <span class="small fw-semibold" style="color:var(--sb-blue);">Ouvrir <i class="bi bi-arrow-right ms-1"></i></span>
            </a>
          </div>

          <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
            <a href="profil.php" class="step-card text-decoration-none" style="display:block; color:inherit;">
              <span class="step-num"><i class="bi bi-person-circle"></i></span>
              <h4 class="fw-bold">Mon Profil</h4>
              <div class="d-flex align-items-center gap-2 mb-2">
                <div class="progress flex-grow-1" style="height:6px;">
                  <div class="progress-bar" role="progressbar" style="width:<?= $dashboard['profile_pct'] ?>%; background: linear-gradient(90deg, var(--sb-blue), var(--sb-orange));"></div>
                </div>
                <small class="fw-semibold"><?= $dashboard['profile_pct'] ?>%</small>
              </div>
              <p class="text-muted small mb-2"><?= $dashboard['profile_pct'] >= 80 ? 'Profil bien renseigné !' : 'Complétez pour gagner en visibilité.' ?></p>
              <span class="small fw-semibold" style="color:var(--sb-orange);">Modifier <i class="bi bi-arrow-right ms-1"></i></span>
            </a>
          </div>

          <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
            <a href="../chat/new_conversation.php" class="step-card text-decoration-none" style="display:block; color:inherit;">
              <span class="step-num" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="bi bi-plus-circle-fill"></i></span>
              <h4 class="fw-bold">Nouvelle conversation</h4>
              <p class="text-muted"><?= $isClient ? 'Contactez un freelancer pour démarrer un projet.' : 'Démarrez une discussion avec un client.' ?></p>
              <span class="small fw-semibold" style="color:#10b981;">Démarrer <i class="bi bi-arrow-right ms-1"></i></span>
            </a>
          </div>

          <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
            <a href="#talents" class="step-card text-decoration-none" style="display:block; color:inherit;">
              <span class="step-num" style="background: linear-gradient(135deg, #7c3aed, #a855f7);"><i class="bi bi-stars"></i></span>
              <h4 class="fw-bold"><?= $isClient ? 'Trouver un talent' : 'Voir la communauté' ?></h4>
              <p class="text-muted"><?= $isClient ? 'Parcourez les freelancers disponibles.' : 'Découvrez les autres freelancers SkillBridge.' ?></p>
              <span class="small fw-semibold" style="color:#7c3aed;">Découvrir <i class="bi bi-arrow-right ms-1"></i></span>
            </a>
          </div>

        </div>
      </div>
    </section>

    <!-- ================== CONVERSATIONS RÉCENTES ================== -->
    <section class="section-pad">
      <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
          <span class="section-tag" style="background: rgba(37,99,235,.08); color: var(--sb-blue);">Activité récente</span>
          <h2 class="section-title display-5 mb-3">Conversations récentes</h2>
          <p class="lead text-muted">Vos derniers échanges sur SkillBridge.</p>
        </div>

        <?php if (empty($dashboard['conversations'])): ?>
          <div class="text-center py-5" data-aos="fade-up">
            <div style="width:96px;height:96px;border-radius:24px;background:#f1f5f9;display:inline-flex;align-items:center;justify-content:center;margin-bottom:18px;">
              <i class="bi bi-chat-square-dots" style="font-size:2.5rem; color:#94a3b8;"></i>
            </div>
            <h4 class="fw-bold">Aucune conversation pour l'instant</h4>
            <p class="text-muted mb-3">Démarrez votre première discussion sur SkillBridge.</p>
            <a href="../chat/new_conversation.php" class="btn btn-lg" style="background:linear-gradient(135deg, var(--sb-orange), var(--sb-blue));color:#fff;border-radius:14px;padding:12px 28px;font-weight:600;">
              <i class="bi bi-plus-circle me-1"></i> Démarrer une conversation
            </a>
          </div>
        <?php else: ?>
          <div class="row g-4">
            <?php foreach ($dashboard['conversations'] as $c):
                $isU1     = ((int)$c['user1_id'] === $userId);
                $otherFn  = $isU1 ? $c['u2_prenom'] : $c['u1_prenom'];
                $otherLn  = $isU1 ? $c['u2_nom']    : $c['u1_nom'];
                $otherPh  = $isU1 ? $c['u2_photo']  : $c['u1_photo'];
                $otherFul = trim($otherFn . ' ' . $otherLn);
                $avatarSrc = avatarUrl($otherPh, $otherFul, '2563EB', 96);
                $preview = $c['last_message'] ?: 'Aucun message pour l\'instant.';
                $unseen  = (int)$c['unseen'];
            ?>
              <div class="col-md-6" data-aos="fade-up">
                <a href="../chat/chat.php?id=<?= (int)$c['id_conversation'] ?>"
                   class="step-card text-decoration-none d-flex gap-3 align-items-start"
                   style="display:flex; color:inherit;">
                  <img src="<?= $avatarSrc ?>" alt="<?= htmlspecialchars($otherFul) ?>"
                       style="width:64px;height:64px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                  <div class="flex-grow-1 min-width-0">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                      <h5 class="fw-bold mb-0"><?= htmlspecialchars($otherFul) ?></h5>
                      <?php if ($unseen > 0): ?>
                        <span class="badge" style="background:var(--sb-orange);"><?= $unseen ?> non lu<?= $unseen > 1 ? 's' : '' ?></span>
                      <?php endif; ?>
                    </div>
                    <p class="text-muted small mb-2" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                      <?= htmlspecialchars(mb_substr($preview, 0, 80)) ?>
                    </p>
                    <span class="small fw-semibold" style="color:var(--sb-blue);">
                      Ouvrir la conversation <i class="bi bi-arrow-right ms-1"></i>
                    </span>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="text-center mt-4">
            <a href="../chat/conversations.php" class="text-decoration-none fw-semibold" style="color:var(--sb-blue);">
              Voir toutes mes conversations <i class="bi bi-arrow-right ms-1"></i>
            </a>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- ================== TALENTS RECOMMANDÉS ================== -->
    <?php if (!empty($featured)): ?>
      <section class="section-pad" id="talents" style="background:#f8fafc;">
        <div class="container">
          <div class="text-center mb-5" data-aos="fade-up">
            <span class="section-tag" style="background: rgba(249,115,22,.1); color: var(--sb-orange);">Talents vérifiés</span>
            <h2 class="section-title display-5 mb-3"><?= $isClient ? 'Talents recommandés pour vous' : 'Autres freelancers SkillBridge' ?></h2>
            <p class="lead text-muted"><?= $isClient ? 'Quelques freelancers qui pourraient correspondre à vos besoins.' : 'Découvrez la communauté des freelancers SkillBridge.' ?></p>
          </div>
          <div class="row g-4">
            <?php foreach ($featured as $f):
                $skills    = $pickSkills($f['competences'] ?? '', 3);
                $bio       = htmlspecialchars(mb_substr((string)($f['bio'] ?? ''), 0, 110));
                $location  = htmlspecialchars((string)($f['localisation'] ?? ''));
                $fullName  = htmlspecialchars(trim($f['prenom'] . ' ' . $f['nom']));
                $avatar    = avatarUrl($f['photo'], $fullName, 'F97316', 168);
            ?>
              <div class="col-md-6 col-lg-4" data-aos="fade-up">
                <div class="talent-card">
                  <div class="talent-banner"></div>
                  <img src="<?= $avatar ?>" alt="<?= $fullName ?>" class="talent-avatar">
                  <div class="text-center px-3 pb-3 d-flex flex-column flex-grow-1">
                    <h5 class="mb-1"><?= $fullName ?></h5>
                    <?php if ($location): ?>
                      <div class="small text-muted mb-2"><i class="bi bi-geo-alt"></i> <?= $location ?></div>
                    <?php endif; ?>
                    <?php if ($bio): ?><p class="small text-muted mb-3"><?= $bio ?>...</p><?php endif; ?>
                    <?php if (!empty($skills)): ?>
                      <div class="mb-3">
                        <?php foreach ($skills as $s): ?><span class="skill-chip"><?= htmlspecialchars($s) ?></span><?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                    <a href="../chat/new_conversation.php?user2=<?= (int)$f['id'] ?>"
                       class="btn btn-sm w-100 mt-auto"
                       style="background: linear-gradient(135deg, var(--sb-blue), var(--sb-orange)); color:#fff; font-weight:600; border-radius:10px;">
                      <i class="bi bi-chat-dots me-1"></i> Contacter
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

  <?php else: /* =================================================== MARKETING (LOGGED-OUT) =================================================== */ ?>

    <!-- ================== HERO MARKETING ================== -->
    <section class="hero-marketing">
      <div class="container">
        <div class="row align-items-center g-5">
          <div class="col-lg-6" data-aos="fade-right">
            <span class="section-tag" style="background: rgba(249,115,22,.1); color: var(--sb-orange);">
              <i class="bi bi-rocket-takeoff me-1"></i> Marketplace freelance
            </span>
            <h1 class="display-3 mt-3 mb-4">
              Trouvez le <span class="accent">talent parfait</span><br>
              pour votre projet.
            </h1>
            <p class="lead text-muted mb-4">
              SkillBridge connecte clients et freelancers vérifiés. Publiez un projet, recevez des offres ciblées et collaborez via une <strong>messagerie temps réel</strong>.
            </p>
            <div class="d-flex flex-wrap gap-2 mb-4">
              <a href="register.php" class="btn btn-lg" style="background: linear-gradient(135deg, var(--sb-orange), var(--sb-blue)); color:#fff; font-weight:600; padding: 14px 28px; border-radius:14px;">
                <i class="bi bi-rocket-takeoff me-1"></i> Commencer gratuitement
              </a>
              <a href="#how-it-works" class="btn btn-lg btn-outline-dark" style="border-radius:14px; padding: 14px 28px;">
                Comment ça marche <i class="bi bi-arrow-down ms-1"></i>
              </a>
            </div>
            <!-- Stats -->
            <div class="row g-2 mt-4">
              <div class="col-4"><div class="stat-pill"><div class="num"><?= max(1, $stats['freelancers']) ?>+</div><div class="lbl">Freelancers</div></div></div>
              <div class="col-4"><div class="stat-pill"><div class="num"><?= max(1, $stats['clients']) ?>+</div><div class="lbl">Clients</div></div></div>
              <div class="col-4"><div class="stat-pill"><div class="num"><?= max(1, $stats['conversations']) ?>+</div><div class="lbl">Collaborations</div></div></div>
            </div>
          </div>

          <div class="col-lg-6 d-none d-lg-block position-relative" data-aos="fade-left">
            <div class="hero-img-wrap">
              <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=900&auto=format&fit=crop&q=80"
                   alt="Équipe collaborant sur un projet" loading="eager">
            </div>
            <!-- Floating cards on top of the image -->
            <div class="hero-floating-card" style="top:30px; left:-30px;">
              <div style="width:38px;height:38px;border-radius:10px;background:rgba(37,99,235,.1);display:flex;align-items:center;justify-content:center;color:var(--sb-blue);"><i class="bi bi-shield-check"></i></div>
              <div>
                <div class="fw-bold small">Profils vérifiés</div>
                <div class="small text-muted" style="font-size:.75rem;">Email + identité</div>
              </div>
            </div>
            <div class="hero-floating-card" style="bottom:30px; right:-20px;">
              <div style="width:38px;height:38px;border-radius:10px;background:rgba(249,115,22,.1);display:flex;align-items:center;justify-content:center;color:var(--sb-orange);"><i class="bi bi-chat-dots-fill"></i></div>
              <div>
                <div class="fw-bold small">Chat temps réel</div>
                <div class="small text-muted" style="font-size:.75rem;">Messages instantanés</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ================== COMMENT ÇA MARCHE ================== -->
    <section id="how-it-works" class="section-pad" style="background:#f8fafc;">
      <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
          <span class="section-tag">Démarrer en 3 étapes</span>
          <h2 class="section-title display-5 mb-3">Comment ça marche</h2>
          <p class="lead text-muted">De l'inscription à la première mission, SkillBridge vous accompagne.</p>
        </div>
        <div class="row g-4">
          <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
            <div class="step-card">
              <span class="step-num">1</span>
              <h4 class="fw-bold">Créez votre compte</h4>
              <p class="text-muted">Inscription en quelques secondes — email, Google, GitHub ou même reconnaissance faciale. Choisissez votre rôle (client ou freelancer).</p>
            </div>
          </div>
          <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
            <div class="step-card">
              <span class="step-num">2</span>
              <h4 class="fw-bold">Trouvez ou présentez-vous</h4>
              <p class="text-muted">Côté <strong style="color:var(--sb-blue)">client</strong> : parcourez les freelancers vérifiés. Côté <strong style="color:var(--sb-orange)">freelancer</strong> : créez un profil qui vous distingue.</p>
            </div>
          </div>
          <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
            <div class="step-card">
              <span class="step-num">3</span>
              <h4 class="fw-bold">Collaborez en direct</h4>
              <p class="text-muted">Échanges via la messagerie temps réel : messages, fichiers, photos, réactions. Tout reste sur la plateforme.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ================== FREELANCERS EN VEDETTE ================== -->
    <?php if (!empty($featured)): ?>
    <section id="featured" class="section-pad">
      <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
          <span class="section-tag" style="background: rgba(249,115,22,.1); color: var(--sb-orange);">Talents vérifiés</span>
          <h2 class="section-title display-5 mb-3">Freelancers en vedette</h2>
          <p class="lead text-muted">Quelques-uns des talents disponibles dès aujourd'hui.</p>
        </div>
        <div class="row g-4">
          <?php foreach ($featured as $f):
              $skills    = $pickSkills($f['competences'] ?? '', 3);
              $bio       = htmlspecialchars(mb_substr((string)($f['bio'] ?? ''), 0, 110));
              $location  = htmlspecialchars((string)($f['localisation'] ?? ''));
              $fullName  = htmlspecialchars(trim($f['prenom'] . ' ' . $f['nom']));
              $avatar    = avatarUrl($f['photo'], $fullName, 'F97316', 168);
          ?>
            <div class="col-md-6 col-lg-4" data-aos="fade-up">
              <div class="talent-card">
                <div class="talent-banner"></div>
                <img src="<?= $avatar ?>" alt="<?= $fullName ?>" class="talent-avatar">
                <div class="text-center px-3 pb-3 d-flex flex-column flex-grow-1">
                  <h5 class="mb-1"><?= $fullName ?></h5>
                  <?php if ($location): ?><div class="small text-muted mb-2"><i class="bi bi-geo-alt"></i> <?= $location ?></div><?php endif; ?>
                  <?php if ($bio): ?><p class="small text-muted mb-3"><?= $bio ?>...</p><?php endif; ?>
                  <?php if (!empty($skills)): ?>
                    <div class="mb-3">
                      <?php foreach ($skills as $s): ?><span class="skill-chip"><?= htmlspecialchars($s) ?></span><?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                  <a href="register.php" class="btn btn-sm btn-outline-primary w-100 mt-auto" style="border-radius:10px;">
                    Se connecter pour contacter
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <!-- ================== POURQUOI SKILLBRIDGE ================== -->
    <section id="why" class="section-pad" style="background:#f8fafc;">
      <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
          <span class="section-tag">Pourquoi SkillBridge</span>
          <h2 class="section-title display-5 mb-3">Une plateforme pensée pour les pros</h2>
          <p class="lead text-muted">Toutes les fonctionnalités dont vous avez besoin pour bien collaborer.</p>
        </div>
        <div class="row g-4">
          <?php
            $features = [
              ['bi-chat-dots-fill',     'Messagerie temps réel',      'Discussions instantanées avec accusés de réception, indicateur de saisie, réactions emoji et partage de fichiers.'],
              ['bi-shield-lock-fill',   'Authentification sécurisée', 'Connexion classique, OAuth Google / GitHub / Discord, ou reconnaissance faciale.'],
              ['bi-bell-fill',          'Notifications instantanées', 'Cloche + toasts en direct dès qu\'un message ou changement vous concerne.'],
              ['bi-person-badge-fill',  'Profils vérifiés',           'Email vérifié, photos, bio, compétences et localisation pour collaborer en confiance.'],
            ];
            foreach ($features as $i => [$icon, $title, $desc]):
          ?>
            <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="<?= ($i + 1) * 100 ?>">
              <div class="feature-card">
                <div class="feature-icon"><i class="bi <?= $icon ?>"></i></div>
                <h5 class="fw-bold mb-2"><?= $title ?></h5>
                <p class="text-muted small mb-0"><?= $desc ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ================== CATÉGORIES ================== -->
    <section class="section-pad">
      <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
          <span class="section-tag">Explorez par domaine</span>
          <h2 class="section-title display-5 mb-3">Catégories de services</h2>
          <p class="lead text-muted">Tous les domaines couverts par les freelancers SkillBridge.</p>
        </div>
        <div class="row g-4">
          <?php
            $cats = [
              ['bi-code-slash',     'Développement web &amp; mobile', 'Sites, apps, intégrations, API.',         '#2563eb', 'rgba(37,99,235,.1)'],
              ['bi-palette-fill',   'Design &amp; UI/UX',             'Identité, maquettes, prototypes.',        '#f97316', 'rgba(249,115,22,.1)'],
              ['bi-megaphone-fill', 'Marketing digital &amp; SEO',    'Campagnes, référencement, content.',      '#10b981', 'rgba(16,185,129,.1)'],
              ['bi-pencil-square',  'Rédaction &amp; traduction',     'Articles, traductions, copywriting.',     '#7c3aed', 'rgba(124,58,237,.1)'],
            ];
            foreach ($cats as $i => [$icon, $title, $desc, $color, $bg]):
          ?>
            <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?= ($i + 1) * 100 ?>">
              <a href="register.php" class="category-card">
                <div class="cat-icon" style="background: <?= $bg ?>; color: <?= $color ?>;"><i class="bi <?= $icon ?>"></i></div>
                <h6 class="fw-bold mb-2"><?= $title ?></h6>
                <p class="small text-muted mb-0"><?= $desc ?></p>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- ================== FAQ ================== -->
    <section id="faq" class="section-pad" style="background:#f8fafc;">
      <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
          <span class="section-tag">Vous vous demandez ?</span>
          <h2 class="section-title display-5 mb-3">Questions fréquentes</h2>
        </div>
        <div class="row justify-content-center"><div class="col-lg-9">
          <div class="accordion" id="faqAccordion">
            <?php
              $faq = [
                ['L\'inscription est-elle gratuite ?',
                 'Oui, créer un compte sur SkillBridge est totalement gratuit, que vous soyez client ou freelancer. Inscription par email, Google, GitHub, Discord ou reconnaissance faciale.'],
                ['Comment contacter un freelancer ?',
                 'Une fois connecté, ouvrez le profil d\'un freelancer et cliquez sur "Contacter". Une conversation est créée et vous pouvez démarrer immédiatement la discussion.'],
                ['Puis-je partager des fichiers et photos via la messagerie ?',
                 'Oui, jusqu\'à 10 Mo par fichier (JPG/PNG/WebP, PDF, Word, Excel, ZIP, etc.). Les fichiers sont stockés de manière sécurisée et accessibles uniquement aux participants.'],
                ['Comment fonctionne la connexion par reconnaissance faciale ?',
                 'À l\'inscription, vous pouvez enregistrer votre visage. Aux connexions suivantes, vous activez la caméra et la plateforme vous reconnaît automatiquement.'],
                ['J\'ai oublié mon mot de passe, que faire ?',
                 'Cliquez sur "Mot de passe oublié ?" depuis la page de connexion. Vous recevrez un lien de réinitialisation par email, valable 1 heure.'],
              ];
              foreach ($faq as $i => [$q, $a]): $id = 'faq' . ($i + 1); ?>
              <div class="accordion-item border-0 mb-2 rounded-3 overflow-hidden" style="box-shadow:0 1px 3px rgba(0,0,0,.04);">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $id ?>"><?= htmlspecialchars($q) ?></button>
                </h2>
                <div id="<?= $id ?>" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                  <div class="accordion-body text-muted"><?= htmlspecialchars($a) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div></div>
      </div>
    </section>

    <!-- ================== CTA FINAL DUAL ================== -->
    <section class="section-pad">
      <div class="container">
        <div class="row g-4">
          <div class="col-md-6" data-aos="fade-right">
            <div class="step-card text-center" style="border-top: 4px solid var(--sb-blue);">
              <div style="width:78px;height:78px;border-radius:20px;background:rgba(37,99,235,.1);display:flex;align-items:center;justify-content:center;font-size:2rem;color:var(--sb-blue);margin:0 auto 18px;">
                <i class="bi bi-briefcase-fill"></i>
              </div>
              <h3 class="fw-bold">Vous êtes Client ?</h3>
              <p class="text-muted">Trouvez le freelancer idéal pour votre prochain projet et collaborez en toute confiance.</p>
              <a href="register.php" class="btn btn-lg" style="background:var(--sb-blue);color:#fff;border-radius:12px;padding:12px 28px;font-weight:600;">
                Trouver un freelancer <i class="bi bi-arrow-right ms-1"></i>
              </a>
            </div>
          </div>
          <div class="col-md-6" data-aos="fade-left">
            <div class="step-card text-center" style="border-top: 4px solid var(--sb-orange);">
              <div style="width:78px;height:78px;border-radius:20px;background:rgba(249,115,22,.1);display:flex;align-items:center;justify-content:center;font-size:2rem;color:var(--sb-orange);margin:0 auto 18px;">
                <i class="bi bi-tools"></i>
              </div>
              <h3 class="fw-bold">Vous êtes Freelancer ?</h3>
              <p class="text-muted">Mettez en avant vos compétences et trouvez de nouvelles missions adaptées à votre profil.</p>
              <a href="register.php" class="btn btn-lg" style="background:var(--sb-orange);color:#fff;border-radius:12px;padding:12px 28px;font-weight:600;">
                Créer mon profil <i class="bi bi-arrow-right ms-1"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

  <?php endif; /* /flow split */ ?>

  </main>

  <!-- ================== FOOTER ================== -->
  <footer id="footer" class="footer" style="background:var(--sb-dark); color:#cbd5e1;">
    <div class="container py-5">
      <div class="row gy-4">
        <div class="col-lg-4">
          <a href="index.php" class="logo d-flex align-items-center text-white text-decoration-none">
            <span class="sitename" style="color:#fff; font-weight:800; font-size:1.5rem;">SkillBridge</span>
          </a>
          <p class="mt-3 mb-0 small">La marketplace freelance qui matche les bons talents avec les bons projets.</p>
        </div>
        <div class="col-lg-2 col-6">
          <h6 class="fw-bold text-white mb-3">Plateforme</h6>
          <ul class="list-unstyled small">
            <li class="mb-2"><a href="#how-it-works" class="text-decoration-none" style="color:#94a3b8;">Comment ça marche</a></li>
            <li class="mb-2"><a href="#featured" class="text-decoration-none" style="color:#94a3b8;">Freelancers</a></li>
            <li class="mb-2"><a href="#why" class="text-decoration-none" style="color:#94a3b8;">Pourquoi nous</a></li>
            <li class="mb-2"><a href="#faq" class="text-decoration-none" style="color:#94a3b8;">FAQ</a></li>
          </ul>
        </div>
        <div class="col-lg-3 col-6">
          <h6 class="fw-bold text-white mb-3">Compte</h6>
          <ul class="list-unstyled small">
            <?php if ($isLoggedIn): ?>
              <li class="mb-2"><a href="profil.php" class="text-decoration-none" style="color:#94a3b8;">Mon profil</a></li>
              <li class="mb-2"><a href="../chat/conversations.php" class="text-decoration-none" style="color:#94a3b8;">Mes conversations</a></li>
              <li class="mb-2"><a href="<?= $BASE ?>/controller/utilisateurcontroller.php?action=logout" class="text-decoration-none" style="color:#94a3b8;">Déconnexion</a></li>
            <?php else: ?>
              <li class="mb-2"><a href="login.php" class="text-decoration-none" style="color:#94a3b8;">Connexion</a></li>
              <li class="mb-2"><a href="register.php" class="text-decoration-none" style="color:#94a3b8;">Inscription</a></li>
              <li class="mb-2"><a href="forgot-password.php" class="text-decoration-none" style="color:#94a3b8;">Mot de passe oublié</a></li>
            <?php endif; ?>
          </ul>
        </div>
        <div class="col-lg-3">
          <h6 class="fw-bold text-white mb-3">Contact</h6>
          <p class="small mb-1" style="color:#94a3b8;">Esprit, Z.I. Charguia 2</p>
          <p class="small mb-0" style="color:#94a3b8;">2035 Ariana, Tunisie</p>
        </div>
      </div>
      <hr style="border-color:#1e293b; margin:30px 0 20px;">
      <div class="text-center small" style="color:#64748b;">
        © <?= date('Y') ?> <strong style="color:#cbd5e1;">SkillBridge</strong> — Tous droits réservés.
      </div>
    </div>
  </footer>

  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/js/main.js"></script>
</body>
</html>
