<?php
session_start();
require_once __DIR__ . '/../../../config.php';

$BASE       = base_url();
$isLoggedIn = !empty($_SESSION['user_id']);
$userNom    = $isLoggedIn ? trim((string)($_SESSION['user_nom'] ?? '')) : '';
$userRole   = $_SESSION['user_role'] ?? '';

// ----------- Données live pour la landing ---------------------
// Freelancers en vedette (max 6)
$featured = [];
try {
    $stmt = $pdo->query("
        SELECT u.id, u.prenom, u.nom, u.photo,
               p.bio, p.competences, p.localisation
          FROM utilisateurs u
          LEFT JOIN profils p ON p.utilisateur_id = u.id
         WHERE u.role = 'freelancer' AND u.is_active = 1
         ORDER BY u.id DESC
         LIMIT 6
    ");
    $featured = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) { /* table peut ne pas exister sur premier run */ }

// Statistiques live
$stats = ['freelancers' => 0, 'clients' => 0, 'conversations' => 0];
try {
    $stats['freelancers']   = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role='freelancer' AND is_active=1")->fetchColumn();
    $stats['clients']       = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role='client'     AND is_active=1")->fetchColumn();
    $stats['conversations'] = (int)$pdo->query("SELECT COUNT(*) FROM conversations")->fetchColumn();
} catch (Throwable $e) {}

// Petit helper pour limiter les chips de compétences
$pickSkills = function ($csv, $max = 3) {
    if (!$csv) return [];
    $arr = array_filter(array_map('trim', explode(',', $csv)));
    return array_slice($arr, 0, $max);
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>SkillBridge — La marketplace freelance qui matche les bons talents</title>
  <meta name="description" content="SkillBridge connecte clients et freelancers vérifiés. Publiez un projet, recevez des offres et collaborez via une messagerie temps réel.">
  <meta name="keywords" content="freelance, marketplace, freelancers, missions, projets, chat temps réel, Tunisie">

  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900&family=Noto+Sans:ital,wght@0,100;0,400;0,700&family=Questrial:wght@400&display=swap" rel="stylesheet">
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    .feature-card {
      background:#fff; border:1px solid #e9ecef; border-radius:14px;
      padding:28px 22px; height:100%; transition:all .22s ease;
    }
    .feature-card:hover { transform: translateY(-4px); box-shadow:0 12px 28px rgba(0,0,0,.08); }
    .feature-card .feature-icon {
      width:54px; height:54px; border-radius:12px;
      display:flex; align-items:center; justify-content:center;
      font-size:1.5rem; margin-bottom:14px;
      background: var(--background-color-tinted, rgba(249,115,22,.12));
      color: var(--accent-color, #f97316);
    }
    .step-card {
      background:#fff; border-radius:16px; padding:30px 24px; height:100%;
      border:1px solid #f1f3f5; text-align:center;
    }
    .step-num {
      width:44px; height:44px; border-radius:50%;
      background: var(--accent-color, #f97316); color:#fff;
      display:inline-flex; align-items:center; justify-content:center;
      font-weight:700; font-size:1.1rem; margin-bottom:14px;
    }
    .freelancer-card {
      background:#fff; border:1px solid #eee; border-radius:14px;
      overflow:hidden; height:100%; transition:all .22s ease;
      display:flex; flex-direction:column;
    }
    .freelancer-card:hover { transform: translateY(-4px); box-shadow:0 14px 30px rgba(0,0,0,.08); }
    .freelancer-avatar {
      width:88px; height:88px; border-radius:50%; object-fit:cover;
      border:3px solid var(--accent-color, #f97316); margin:24px auto 12px; display:block;
    }
    .freelancer-avatar-placeholder {
      width:88px; height:88px; border-radius:50%;
      display:flex; align-items:center; justify-content:center;
      background:#fff3e6; color: var(--accent-color, #f97316); font-size:2rem;
      border:3px solid var(--accent-color, #f97316); margin:24px auto 12px;
    }
    .skill-chip {
      display:inline-block; padding:3px 10px; margin:2px;
      border:1px solid #e3e6f0; border-radius:14px;
      font-size:.75rem; color:#5a5c69; background:#f8f9fc;
    }
    .stat-pill {
      background:#fff; border:1px solid #eee; border-radius:14px;
      padding:20px 16px; text-align:center;
    }
    .stat-pill .num { font-size:2rem; font-weight:800; color: var(--accent-color, #f97316); line-height:1; }
    .stat-pill .lbl { font-size:.85rem; color:#6c757d; margin-top:4px; }
    .category-card {
      background:#fff; border:1px solid #eee; border-radius:14px;
      padding:26px 20px; text-align:center; height:100%;
      transition:all .22s ease; cursor:pointer;
    }
    .category-card:hover { transform: translateY(-3px); border-color: var(--accent-color, #f97316); }
    .category-card i { font-size:2rem; color: var(--accent-color, #f97316); margin-bottom:10px; }
  </style>
</head>

<body class="index-page">

  <!-- ===== HEADER session-aware ===== -->
  <header id="header" class="header d-flex align-items-center sticky-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

      <a href="index.php" class="logo d-flex align-items-center me-auto me-xl-0">
        <h1 class="sitename">SkillBridge</h1>
      </a>

      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.php" class="active">Accueil</a></li>
          <li><a href="#how-it-works">Comment ça marche</a></li>
          <li><a href="#featured">Freelancers</a></li>
          <li><a href="#why">Pourquoi nous</a></li>
          <?php if ($isLoggedIn): ?>
            <li><a href="profil.php">Mon Profil</a></li>
            <li><a href="../chat/conversations.php">Mes Conversations</a></li>
            <li><a href="<?= $BASE ?>/controller/utilisateurcontroller.php?action=logout">Déconnexion</a></li>
          <?php else: ?>
            <li><a href="login.php">Connexion</a></li>
            <li><a href="register.php">Inscription</a></li>
          <?php endif; ?>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>
    </div>
  </header>

  <main class="main">

    <!-- ===== HERO ===== -->
    <section id="hero" class="hero section">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-7" data-aos="fade-up">
            <?php if ($isLoggedIn): ?>
              <div class="section-category mb-3">Bonjour, <?= htmlspecialchars($userNom ?: 'à toi') ?> 👋</div>
              <h1 class="display-4 mb-4">Reprenez là où<br>vous vous étiez arrêté.</h1>
              <p class="lead mb-4">Continuez vos conversations, mettez à jour votre profil ou découvrez de nouveaux talents sur SkillBridge.</p>
              <div class="d-flex flex-wrap gap-2">
                <a href="../chat/conversations.php" class="btn btn-submit">
                  <i class="bi bi-chat-dots me-1"></i> Mes conversations
                </a>
                <a href="profil.php" class="btn btn-outline-primary">
                  <i class="bi bi-person-circle me-1"></i> Mon profil
                </a>
              </div>
            <?php else: ?>
              <div class="section-category mb-3">Marketplace freelance</div>
              <h1 class="display-4 mb-4">Trouvez le talent<br>qui fera décoller<br>votre projet.</h1>
              <p class="lead mb-4">SkillBridge connecte clients et freelancers vérifiés. Publiez un projet, recevez des offres et collaborez via une messagerie temps réel.</p>
              <div class="d-flex flex-wrap gap-2">
                <a href="register.php" class="btn btn-submit">
                  <i class="bi bi-rocket-takeoff me-1"></i> Commencer gratuitement
                </a>
                <a href="#how-it-works" class="btn btn-outline-primary">
                  Comment ça marche <i class="bi bi-arrow-right ms-1"></i>
                </a>
              </div>
            <?php endif; ?>

            <!-- Stats live -->
            <div class="row g-3 mt-5">
              <div class="col-4">
                <div class="stat-pill">
                  <div class="num"><?= $stats['freelancers'] ?>+</div>
                  <div class="lbl">Freelancers actifs</div>
                </div>
              </div>
              <div class="col-4">
                <div class="stat-pill">
                  <div class="num"><?= $stats['clients'] ?>+</div>
                  <div class="lbl">Clients inscrits</div>
                </div>
              </div>
              <div class="col-4">
                <div class="stat-pill">
                  <div class="num"><?= $stats['conversations'] ?>+</div>
                  <div class="lbl">Collaborations en cours</div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-5 d-none d-lg-block" data-aos="fade-left" data-aos-delay="200">
            <img src="assets/img/hero/hero-img.webp" alt="Plateforme SkillBridge — collaboration client/freelancer" class="img-fluid">
          </div>
        </div>
      </div>
    </section>

    <!-- ===== COMMENT ÇA MARCHE ===== -->
    <section id="how-it-works" class="section light-background">
      <div class="container section-title text-center" data-aos="fade-up">
        <div class="section-category mb-2">Démarrer en 3 étapes</div>
        <h2>Comment ça marche</h2>
        <p>De l'inscription à la première mission, SkillBridge guide chacun des deux côtés.</p>
      </div>
      <div class="container">
        <div class="row g-4">
          <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
            <div class="step-card">
              <span class="step-num">1</span>
              <h4>Créez votre compte</h4>
              <p class="text-muted">Inscrivez-vous en quelques secondes — email, Google, GitHub ou même reconnaissance faciale. Choisissez votre rôle (client ou freelancer).</p>
            </div>
          </div>
          <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
            <div class="step-card">
              <span class="step-num">2</span>
              <h4>Trouvez ou postez</h4>
              <p class="text-muted">Côté <strong>client</strong> : parcourez les profils freelancers vérifiés. Côté <strong>freelancer</strong> : présentez vos compétences sur votre profil.</p>
            </div>
          </div>
          <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
            <div class="step-card">
              <span class="step-num">3</span>
              <h4>Collaborez en direct</h4>
              <p class="text-muted">Échangez via la messagerie temps réel : messages, fichiers, photos, réactions. Tout reste sur la plateforme — sans dispersion.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== POURQUOI SKILLBRIDGE ===== -->
    <section id="why" class="section">
      <div class="container section-title text-center" data-aos="fade-up">
        <div class="section-category mb-2">Pourquoi SkillBridge</div>
        <h2>Une plateforme pensée pour les pros</h2>
        <p>Toutes les fonctionnalités dont les freelancers et leurs clients ont besoin pour bien travailler ensemble.</p>
      </div>
      <div class="container">
        <div class="row g-4">
          <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="100">
            <div class="feature-card">
              <div class="feature-icon"><i class="bi bi-chat-dots-fill"></i></div>
              <h5>Messagerie temps réel</h5>
              <p class="text-muted small mb-0">Discussions instantanées avec accusés de réception, indicateur de saisie, réactions emoji et partage de fichiers.</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="200">
            <div class="feature-card">
              <div class="feature-icon"><i class="bi bi-shield-lock-fill"></i></div>
              <h5>Authentification sécurisée</h5>
              <p class="text-muted small mb-0">Connexion classique, OAuth Google / GitHub / Discord, ou même reconnaissance faciale — au choix.</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="300">
            <div class="feature-card">
              <div class="feature-icon"><i class="bi bi-bell-fill"></i></div>
              <h5>Notifications instantanées</h5>
              <p class="text-muted small mb-0">Cloche + toasts en direct dès qu'un message, une réaction ou un changement vous concerne. Plus rien ne vous échappe.</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3" data-aos="zoom-in" data-aos-delay="400">
            <div class="feature-card">
              <div class="feature-icon"><i class="bi bi-person-badge-fill"></i></div>
              <h5>Profils vérifiés</h5>
              <p class="text-muted small mb-0">Email vérifié, photos de profil, bio, compétences et localisation — pour collaborer en confiance.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== FREELANCERS EN VEDETTE ===== -->
    <?php if (!empty($featured)): ?>
    <section id="featured" class="section light-background">
      <div class="container section-title text-center" data-aos="fade-up">
        <div class="section-category mb-2">Talents vérifiés</div>
        <h2>Freelancers en vedette</h2>
        <p>Quelques-uns des freelancers récemment actifs sur la plateforme.</p>
      </div>
      <div class="container">
        <div class="row g-4">
          <?php foreach ($featured as $f): ?>
            <?php
              $skills    = $pickSkills($f['competences'] ?? '', 3);
              $bio       = htmlspecialchars(mb_substr((string)($f['bio'] ?? ''), 0, 100));
              $photoSrc  = !empty($f['photo']) ? 'assets/img/profile/' . htmlspecialchars($f['photo']) : '';
              $location  = htmlspecialchars((string)($f['localisation'] ?? ''));
              $fullName  = htmlspecialchars(trim($f['prenom'] . ' ' . $f['nom']));
              $initial   = strtoupper(mb_substr($f['prenom'] ?? 'F', 0, 1));
            ?>
            <div class="col-md-6 col-lg-4" data-aos="fade-up">
              <div class="freelancer-card">
                <?php if ($photoSrc): ?>
                  <img src="<?= $photoSrc ?>" alt="<?= $fullName ?>" class="freelancer-avatar">
                <?php else: ?>
                  <div class="freelancer-avatar-placeholder"><?= $initial ?></div>
                <?php endif; ?>
                <div class="text-center px-3 pb-3 d-flex flex-column flex-grow-1">
                  <h5 class="mb-1"><?= $fullName ?></h5>
                  <?php if ($location): ?>
                    <div class="small text-muted mb-2"><i class="bi bi-geo-alt"></i> <?= $location ?></div>
                  <?php endif; ?>
                  <?php if ($bio): ?>
                    <p class="small text-muted mb-3"><?= $bio ?>...</p>
                  <?php endif; ?>
                  <?php if (!empty($skills)): ?>
                    <div class="mb-3">
                      <?php foreach ($skills as $s): ?>
                        <span class="skill-chip"><?= htmlspecialchars($s) ?></span>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                  <div class="mt-auto">
                    <?php if ($isLoggedIn && (int)$f['id'] !== (int)$_SESSION['user_id']): ?>
                      <a href="../chat/new_conversation.php?user2=<?= (int)$f['id'] ?>" class="btn btn-sm btn-submit w-100">
                        <i class="bi bi-chat-dots"></i> Contacter
                      </a>
                    <?php else: ?>
                      <a href="register.php" class="btn btn-sm btn-outline-primary w-100">
                        Se connecter pour contacter
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <!-- ===== CATÉGORIES ===== -->
    <section class="section">
      <div class="container section-title text-center" data-aos="fade-up">
        <div class="section-category mb-2">Explorez par domaine</div>
        <h2>Catégories de services</h2>
        <p>Tous les domaines couverts par les freelancers SkillBridge.</p>
      </div>
      <div class="container">
        <div class="row g-4">
          <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
            <div class="category-card">
              <i class="bi bi-code-slash"></i>
              <h6 class="mt-2 mb-1">Développement web &amp; mobile</h6>
              <p class="small text-muted mb-0">Sites, apps, intégrations, API.</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
            <div class="category-card">
              <i class="bi bi-palette"></i>
              <h6 class="mt-2 mb-1">Design &amp; UI/UX</h6>
              <p class="small text-muted mb-0">Identité, maquettes, prototypes.</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
            <div class="category-card">
              <i class="bi bi-megaphone"></i>
              <h6 class="mt-2 mb-1">Marketing digital &amp; SEO</h6>
              <p class="small text-muted mb-0">Campagnes, référencement, content.</p>
            </div>
          </div>
          <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="400">
            <div class="category-card">
              <i class="bi bi-pencil-square"></i>
              <h6 class="mt-2 mb-1">Rédaction &amp; traduction</h6>
              <p class="small text-muted mb-0">Articles, traductions, copywriting.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== FAQ ===== -->
    <section id="faq" class="section light-background">
      <div class="container section-title text-center" data-aos="fade-up">
        <div class="section-category mb-2">Vous vous demandez ?</div>
        <h2>Questions fréquentes</h2>
      </div>
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-9">
            <div class="accordion" id="faqAccordion">

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                    L'inscription est-elle gratuite ?
                  </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                  <div class="accordion-body">Oui, créer un compte sur SkillBridge est totalement gratuit, que vous soyez client ou freelancer. Vous pouvez vous inscrire avec email, Google, GitHub, Discord ou via reconnaissance faciale.</div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                    Comment contacter un freelancer ?
                  </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                  <div class="accordion-body">Une fois connecté en tant que client, ouvrez le profil d'un freelancer et cliquez sur "Contacter". Une conversation est créée et vous pouvez démarrer immédiatement la discussion via la messagerie temps réel.</div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                    Puis-je partager des fichiers et photos via la messagerie ?
                  </button>
                </h2>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                  <div class="accordion-body">Oui, jusqu'à 10 Mo par fichier (images JPG/PNG/WebP, documents PDF, Word, Excel, ZIP, etc.). Les fichiers sont stockés de manière sécurisée et accessibles uniquement aux participants de la conversation.</div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                    Comment fonctionne la connexion par reconnaissance faciale ?
                  </button>
                </h2>
                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                  <div class="accordion-body">Lors de votre première inscription, vous pouvez enregistrer votre visage. Aux connexions suivantes, vous activez la caméra : la plateforme reconnaît votre visage et vous connecte automatiquement, sans saisir votre mot de passe.</div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                    J'ai oublié mon mot de passe, que faire ?
                  </button>
                </h2>
                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                  <div class="accordion-body">Cliquez sur "Mot de passe oublié ?" depuis la page de connexion. Vous recevrez un lien de réinitialisation par email, valable 1 heure.</div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== CTA FINAL ===== -->
    <section class="section">
      <div class="container">
        <?php if ($isLoggedIn): ?>
          <div class="text-center" data-aos="fade-up">
            <h2 class="display-6 mb-3">Prêt à reprendre ?</h2>
            <p class="lead mb-4">Vos conversations vous attendent.</p>
            <a href="../chat/conversations.php" class="btn btn-submit btn-lg">
              <i class="bi bi-chat-dots-fill me-2"></i> Ouvrir la messagerie
            </a>
          </div>
        <?php else: ?>
          <div class="row g-4">
            <div class="col-md-6" data-aos="fade-right">
              <div class="step-card text-center">
                <i class="bi bi-briefcase-fill" style="font-size:2.5rem; color:#0d6efd;"></i>
                <h4 class="mt-3">Vous êtes Client ?</h4>
                <p class="text-muted">Trouvez le freelancer idéal pour votre prochain projet.</p>
                <a href="register.php" class="btn btn-primary mt-2">
                  Poster un projet <i class="bi bi-arrow-right ms-1"></i>
                </a>
              </div>
            </div>
            <div class="col-md-6" data-aos="fade-left">
              <div class="step-card text-center">
                <i class="bi bi-tools" style="font-size:2.5rem; color:#f97316;"></i>
                <h4 class="mt-3">Vous êtes Freelancer ?</h4>
                <p class="text-muted">Mettez en avant vos compétences et trouvez de nouvelles missions.</p>
                <a href="register.php" class="btn btn-warning text-white mt-2">
                  Créer mon profil <i class="bi bi-arrow-right ms-1"></i>
                </a>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </section>

  </main>

  <!-- ===== FOOTER ===== -->
  <footer id="footer" class="footer">
    <div class="container">
      <div class="row gy-4 py-4">
        <div class="col-lg-4">
          <a href="index.php" class="logo d-flex align-items-center">
            <span class="sitename">SkillBridge</span>
          </a>
          <p class="mt-3 mb-0">La marketplace freelance qui matche les bons talents avec les bons projets.</p>
        </div>
        <div class="col-lg-2 col-6">
          <h6 class="fw-bold">Plateforme</h6>
          <ul class="list-unstyled small">
            <li><a href="#how-it-works" class="text-decoration-none text-muted">Comment ça marche</a></li>
            <li><a href="#featured" class="text-decoration-none text-muted">Freelancers</a></li>
            <li><a href="#why" class="text-decoration-none text-muted">Pourquoi nous</a></li>
            <li><a href="#faq" class="text-decoration-none text-muted">FAQ</a></li>
          </ul>
        </div>
        <div class="col-lg-3 col-6">
          <h6 class="fw-bold">Compte</h6>
          <ul class="list-unstyled small">
            <?php if ($isLoggedIn): ?>
              <li><a href="profil.php" class="text-decoration-none text-muted">Mon profil</a></li>
              <li><a href="../chat/conversations.php" class="text-decoration-none text-muted">Mes conversations</a></li>
              <li><a href="<?= $BASE ?>/controller/utilisateurcontroller.php?action=logout" class="text-decoration-none text-muted">Déconnexion</a></li>
            <?php else: ?>
              <li><a href="login.php" class="text-decoration-none text-muted">Connexion</a></li>
              <li><a href="register.php" class="text-decoration-none text-muted">Inscription</a></li>
              <li><a href="forgot-password.php" class="text-decoration-none text-muted">Mot de passe oublié</a></li>
            <?php endif; ?>
          </ul>
        </div>
        <div class="col-lg-3">
          <h6 class="fw-bold">Contact</h6>
          <p class="small text-muted mb-0">Esprit, Z.I. Charguia 2<br>2035 Ariana, Tunisie</p>
        </div>
      </div>
      <hr>
      <div class="copyright text-center small text-muted py-3">
        © <?= date('Y') ?> <strong class="sitename">SkillBridge</strong> — Tous droits réservés.
      </div>
    </div>
  </footer>

  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/js/main.js"></script>
</body>
</html>
