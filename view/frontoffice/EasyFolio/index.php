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
function avatarUrl(?string $localPhoto, string $name, string $bgHex = '1F5F4D', int $size = 120): string {
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

// "Talent of the week" — first featured freelancer, used as a hero accent card
$weekTalent = $featured[0] ?? null;

// ---------- Dashboard data (logged-in only) ----------
$dashboard = [
    'unread_count'    => 0,
    'conversations'   => [],
    'profile_pct'     => 0,
    'photo'           => '',
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
            $dashboard['photo'] = $row['photo'] ?? '';
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

// ---------- Demandes / Propositions data ----------
$market = [
    'total_demandes'    => 0,   // # de demandes du client (si client) — sinon # global
    'open_demandes'     => 0,   // # global utilisé pour la marketing page
    'my_propositions'   => 0,   // # de propositions du freelancer
    'received_props'    => 0,   // # de propositions reçues (client)
    'latest_demandes'   => [],  // 3 dernières demandes pour les freelancers
    'my_demandes'       => [],  // 3 dernières demandes du client (avec compte de propositions)
];
try {
    require_once __DIR__ . '/../../../controller/DemandeController.php';
    $marketCtrl = new DemandeController();

    // Total global (utile pour la marketing landing + partagé partout)
    $market['open_demandes'] = (int)$pdo->query("SELECT COUNT(*) FROM demandes")->fetchColumn();

    if ($isLoggedIn && $isClient) {
        $market['total_demandes']  = (int)$pdo->query("SELECT COUNT(*) FROM demandes WHERE user_id = " . (int)$userId)->fetchColumn();
        $market['received_props']  = (int)$pdo->query("SELECT COUNT(*) FROM propositions p JOIN demandes d ON d.id = p.demande_id WHERE d.user_id = " . (int)$userId)->fetchColumn();
        $stmt = $marketCtrl->listDemandesByUser($userId, 'recent', null);
        $myDem = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $myDem = array_slice($myDem, 0, 3);
        foreach ($myDem as &$d) {
            $d['prop_count'] = $marketCtrl->countPropositionsByDemande((int)$d['id']);
        }
        unset($d);
        $market['my_demandes'] = $myDem;
    } elseif ($isLoggedIn && $isFreelancer) {
        $market['my_propositions'] = (int)$pdo->query("SELECT COUNT(*) FROM propositions WHERE user_id = " . (int)$userId)->fetchColumn();
        $stmt = $marketCtrl->listDemandes('recent', null);
        $latest = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        // Exclure ses propres demandes (au cas où un freelancer publie aussi)
        $latest = array_values(array_filter($latest, fn($d) => (int)($d['user_id'] ?? 0) !== $userId));
        $market['latest_demandes'] = array_slice($latest, 0, 3);
    }
} catch (Throwable $e) {}

$userPhoto     = $dashboard['photo'] ?? '';
$userFirstName = trim(explode(' ', $userNom)[0] ?? '') ?: 'Profil';

$pickSkills = fn($csv, $max = 3) => $csv ? array_slice(array_filter(array_map('trim', explode(',', $csv))), 0, $max) : [];

// Categories palette — alternating sage filled / paper / honey filled / paper
$cats = [
    ['bi-code-slash',     'Développement',  'Web, mobile, API, intégrations',          'sage'],
    ['bi-palette-fill',   'Design / UI',    'Identité, maquettes, prototypes',         'paper'],
    ['bi-megaphone-fill', 'Marketing',      'Campagnes, SEO, contenu',                 'honey'],
    ['bi-pencil-square',  'Rédaction',      'Articles, traductions, copywriting',      'paper'],
];

$faq = [
    ['L\'inscription est-elle gratuite ?',
     'Oui, créer un compte sur SkillBridge est totalement gratuit, que vous soyez client ou freelancer. Inscription par email, Google, GitHub, Discord ou reconnaissance faciale.'],
    ['Comment contacter un freelancer ?',
     'Une fois connecté, ouvrez le profil d\'un freelancer et cliquez sur "Contacter". Une conversation est créée et vous pouvez démarrer immédiatement la discussion.'],
    ['Puis-je partager des fichiers et photos ?',
     'Oui, jusqu\'à 10 Mo par fichier (JPG/PNG/WebP, PDF, Word, Excel, ZIP, etc.). Stockage sécurisé, accessible uniquement aux participants.'],
    ['Comment fonctionne la connexion par reconnaissance faciale ?',
     'À l\'inscription, vous enregistrez votre visage. Aux connexions suivantes, vous activez la caméra et la plateforme vous reconnaît automatiquement.'],
    ['Mot de passe oublié, que faire ?',
     'Cliquez sur "Mot de passe oublié ?" depuis la connexion. Un lien de réinitialisation arrive par email, valable 1 heure.'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>SkillBridge — Marketplace freelance</title>
  <meta name="description" content="SkillBridge connecte clients et freelancers vérifiés. Publiez un projet, recevez des offres et collaborez via une messagerie temps réel.">

  <link href="assets/img/favicon.png" rel="icon">

  <!-- Type: Manrope (single distinctive sans, weights 400-800) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">

  <style>
    /* ===========================================================
       SkillBridge — "Sage & Honey" design system
       =========================================================== */
    :root {
      /* base */
      --bg:          #F7F4ED;
      --paper:       #FFFFFF;
      --ink:         #0F0F0F;
      --ink-2:       #2A2A2A;
      --ink-mute:    #5C5C5C;
      --ink-soft:    #A3A3A3;
      --rule:        #E8E2D5;

      /* primary */
      --sage:        #1F5F4D;
      --sage-d:      #134438;
      --sage-soft:   #E8F0EC;
      --sage-tint:   #F1F6F3;

      /* pop */
      --honey:       #F5C842;
      --honey-d:     #E0B033;
      --honey-soft:  #FBF1D0;

      /* legacy hooks (kept so old code doesn't break) */
      --sb-orange: var(--honey);
      --sb-blue:   var(--sage);
      --sb-dark:   var(--ink);
    }

    *, *::before, *::after { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      font-family: 'Manrope', system-ui, -apple-system, sans-serif;
      background: var(--bg);
      color: var(--ink);
      letter-spacing: -.005em;
      -webkit-font-smoothing: antialiased;
    }
    ::selection { background: var(--sage); color: var(--honey); }

    h1, h2, h3, h4, h5, h6 { font-family: 'Manrope', sans-serif; font-weight: 700; letter-spacing: -.022em; color: var(--ink); }
    .display-x { font-size: clamp(2.4rem, 4.6vw, 4.2rem); line-height: 1.05; font-weight: 800; letter-spacing: -.03em; }
    .display-l { font-size: clamp(1.9rem, 3vw, 2.6rem); line-height: 1.1;  letter-spacing: -.02em; font-weight: 800; }
    .display-m { font-size: clamp(1.4rem, 2vw, 1.8rem); line-height: 1.15; letter-spacing: -.015em; font-weight: 700; }
    .lead-x    { font-size: clamp(1.02rem, 1.2vw, 1.12rem); line-height: 1.6; color: var(--ink-mute); font-weight: 400; }
    .accent    { font-style: italic; font-weight: 700; color: var(--sage); }

    /* eyebrow tag */
    .eyebrow {
      display:inline-flex; align-items:center; gap:8px;
      font-size: .8rem; font-weight: 600; letter-spacing: -.005em;
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
    .sb-footer .foot-logo .logo-img { height: 44px; width: auto; display: block; filter: brightness(0) invert(1); }

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

    @media (max-width: 991.98px) {
      .sb-nav { display: none; }
    }

    /* ----------------- Soft decoration blobs ----------------- */
    .blob {
      position: absolute; border-radius: 50%;
      filter: blur(60px); opacity: .55; pointer-events: none; z-index: 0;
    }
    .blob.sage  { background: var(--sage-soft); }
    .blob.honey { background: var(--honey-soft); }

    /* ----------------- Hero ----------------- */
    .hero {
      position: relative; padding: 80px 0 64px; overflow: hidden;
    }
    .hero .blob-1 { width: 380px; height: 380px; left: -100px; top: -120px; }
    .hero .blob-2 { width: 360px; height: 360px; right: -80px; bottom: -60px; }
    .hero .container { position: relative; z-index: 1; }

    .hero-grid { display:grid; grid-template-columns: 1.1fr 1fr; gap: 60px; align-items: center; }
    @media (max-width: 991.98px) { .hero-grid { grid-template-columns: 1fr; gap: 48px; } }

    .hero-cta-row { display:flex; flex-wrap:wrap; gap: 12px; margin-top: 32px; }
    .btn-sage {
      display:inline-flex; align-items:center; gap:10px;
      background: var(--sage); color: var(--paper);
      padding: 14px 24px; border-radius: 12px; border: none;
      text-decoration: none; font-weight: 600; font-size: .98rem;
      transition: all .2s ease;
    }
    .btn-sage:hover { background: var(--sage-d); color: var(--paper); transform: translateY(-2px); box-shadow: 0 14px 28px -12px rgba(31,95,77,.4); }
    .btn-ghost {
      display:inline-flex; align-items:center; gap:10px;
      background: var(--paper); color: var(--ink);
      padding: 14px 24px; border-radius: 12px;
      border: 1px solid var(--rule);
      text-decoration: none; font-weight: 600; font-size: .98rem;
      transition: all .2s ease;
    }
    .btn-ghost:hover { border-color: var(--ink); transform: translateY(-2px); }
    .btn-honey {
      display:inline-flex; align-items:center; gap:10px;
      background: var(--honey); color: var(--ink);
      padding: 14px 24px; border-radius: 12px; border: none;
      text-decoration: none; font-weight: 700; font-size: .98rem;
      transition: all .2s ease;
    }
    .btn-honey:hover { background: var(--honey-d); color: var(--ink); transform: translateY(-2px); }

    /* hero stats — soft inline */
    .hero-stats { display:flex; gap: 36px; margin-top: 44px; flex-wrap: wrap; }
    .hero-stats .cell .num { font-weight:800; font-size: 1.6rem; color: var(--ink); letter-spacing: -.02em; line-height:1; }
    .hero-stats .cell .lbl { font-size: .8rem; color: var(--ink-mute); margin-top: 6px; font-weight: 500; }
    @media (max-width: 575.98px) { .hero-stats { gap: 22px; } }

    /* hero visual: avatar cluster + photo */
    .hero-visual { position: relative; min-height: 460px; }
    .hero-photo {
      position: relative; width: 100%; max-width: 460px; margin-left: auto;
      border-radius: 28px; overflow: hidden;
      border: 1px solid var(--rule);
      box-shadow: 0 30px 60px -25px rgba(31,95,77,.22);
    }
    .hero-photo img { display: block; width: 100%; aspect-ratio: 4/5; object-fit: cover; }

    /* "talent of the week" floating card */
    .talent-mini {
      position: absolute; left: -10px; bottom: 30px;
      background: var(--paper); border: 1px solid var(--rule); border-radius: 18px;
      padding: 14px 18px 14px 14px; display: flex; gap: 12px; align-items: center;
      box-shadow: 0 24px 40px -20px rgba(15,15,15,.18);
      max-width: 280px;
    }
    .talent-mini img { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
    .talent-mini .label { font-size: .68rem; color: var(--sage); font-weight: 700; letter-spacing: .02em; text-transform: uppercase; }
    .talent-mini .name  { font-weight: 700; color: var(--ink); font-size: .95rem; line-height: 1.15; }
    .talent-mini .role  { color: var(--ink-mute); font-size: .8rem; }

    /* signal card top right */
    .signal-card {
      position: absolute; right: -8px; top: 22px;
      background: var(--paper); border: 1px solid var(--rule); border-radius: 14px;
      padding: 10px 14px; display:flex; gap:10px; align-items:center;
      box-shadow: 0 18px 30px -18px rgba(15,15,15,.16);
    }
    .signal-card .pulse {
      width:10px; height:10px; border-radius:50%; background: var(--sage);
      box-shadow: 0 0 0 4px var(--sage-soft);
      animation: pulseDot 2s ease-out infinite;
    }
    @keyframes pulseDot { 0%,100% { box-shadow: 0 0 0 4px var(--sage-soft); } 50% { box-shadow: 0 0 0 8px transparent; } }
    .signal-card .txt { font-size: .8rem; color: var(--ink); font-weight: 600; }

    @media (max-width: 991.98px) {
      .hero-photo { margin: 0 auto; }
      .talent-mini { left: 8px; bottom: 16px; }
      .signal-card { right: 8px; top: 12px; }
    }

    /* ----------------- Section ----------------- */
    section.s-pad { padding: 80px 0; position: relative; }

    .section-head { max-width: 720px; margin-bottom: 48px; }
    .section-head h2 { margin-top: 14px; }
    .section-head p { margin-top: 12px; }

    /* ----------------- 3-step method ----------------- */
    .method-grid { display:grid; grid-template-columns: repeat(3,1fr); gap: 18px; }
    @media (max-width: 991.98px) { .method-grid { grid-template-columns: 1fr; } }
    .method-card {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 22px;
      padding: 32px 28px; transition: all .25s ease;
      position: relative; overflow: hidden;
    }
    .method-card:hover { transform: translateY(-3px); border-color: var(--sage); box-shadow: 0 24px 50px -25px rgba(31,95,77,.18); }
    .method-card .num {
      width: 44px; height: 44px; border-radius: 14px; background: var(--sage-soft); color: var(--sage);
      display: inline-flex; align-items: center; justify-content: center;
      font-weight: 800; font-size: 1.05rem; margin-bottom: 18px;
    }
    .method-card h3 { font-size: 1.25rem; font-weight: 700; margin-bottom: 8px; line-height: 1.2; }
    .method-card p  { color: var(--ink-mute); font-size: .95rem; line-height: 1.55; margin: 0; }
    .method-card.featured .num { background: var(--honey-soft); color: #92660A; }

    /* ----------------- Talent card ----------------- */
    .talent-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; }
    @media (max-width: 991.98px) { .talent-grid { grid-template-columns: repeat(2,1fr); } }
    @media (max-width: 575.98px) { .talent-grid { grid-template-columns: 1fr; } }

    .talent-card {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 22px;
      padding: 28px 24px; text-align: center;
      display: flex; flex-direction: column;
      transition: all .25s ease;
    }
    .talent-card:hover { transform: translateY(-4px); border-color: var(--sage); box-shadow: 0 24px 50px -25px rgba(31,95,77,.18); }
    .talent-card .ava-wrap {
      width: 92px; height: 92px; margin: 0 auto 14px;
      border-radius: 50%; padding: 4px; background: var(--sage-soft);
      position: relative;
    }
    .talent-card .ava-wrap img {
      width: 100%; height: 100%; border-radius: 50%; object-fit: cover;
      border: 3px solid var(--paper);
    }
    .talent-card .verified {
      position: absolute; right: 0; bottom: 0;
      width: 28px; height: 28px; border-radius: 50%; background: var(--honey);
      display: flex; align-items: center; justify-content: center;
      border: 3px solid var(--paper);
      color: var(--ink); font-size: .78rem;
    }
    .talent-card h4 { font-size: 1.1rem; font-weight: 700; margin-bottom: 2px; }
    .talent-card .loc { color: var(--ink-mute); font-size: .85rem; margin-bottom: 12px; }
    .talent-card .bio { color: var(--ink-mute); font-size: .88rem; line-height: 1.5; margin: 0 0 14px; }
    .talent-card .skills { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; margin-bottom: 18px; }
    .talent-card .skill {
      font-size: .78rem; font-weight: 500; color: var(--ink);
      padding: 4px 11px; border-radius: 999px;
      background: var(--bg); border: 1px solid var(--rule);
    }
    .talent-card .btn-talent {
      margin-top: auto; padding: 11px 16px; border-radius: 10px;
      background: var(--ink); color: var(--paper);
      text-decoration: none; font-weight: 600; font-size: .9rem;
      transition: all .2s;
      display: inline-flex; align-items:center; justify-content:center; gap: 8px;
    }
    .talent-card .btn-talent:hover { background: var(--sage); color: var(--paper); }

    /* ----------------- Why grid (clean 4 cards) ----------------- */
    .why-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 18px; }
    @media (max-width: 991.98px) { .why-grid { grid-template-columns: repeat(2,1fr); } }
    @media (max-width: 575.98px) { .why-grid { grid-template-columns: 1fr; } }
    .why-card {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 20px;
      padding: 28px 24px; transition: all .2s ease;
    }
    .why-card:hover { transform: translateY(-2px); border-color: var(--sage); }
    .why-card .ic {
      width: 44px; height: 44px; border-radius: 12px;
      display:flex; align-items:center; justify-content:center; font-size: 1.1rem;
      background: var(--sage-soft); color: var(--sage);
      margin-bottom: 16px;
    }
    .why-card.honey .ic { background: var(--honey-soft); color: #92660A; }
    .why-card h5 { font-size: 1.05rem; font-weight: 700; margin-bottom: 6px; line-height: 1.2; }
    .why-card p { color: var(--ink-mute); font-size: .9rem; line-height: 1.5; margin: 0; }

    /* ----------------- Categories ----------------- */
    .cat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    @media (max-width: 991.98px) { .cat-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 575.98px) { .cat-grid { grid-template-columns: 1fr; } }
    .cat-card {
      border-radius: 22px; padding: 28px 24px; text-decoration: none;
      display: flex; flex-direction: column; gap: 14px; min-height: 220px;
      transition: all .25s ease; border: 1px solid var(--rule);
      position: relative; overflow: hidden;
    }
    .cat-card:hover { transform: translateY(-4px); box-shadow: 0 24px 50px -25px rgba(31,95,77,.22); }
    .cat-card.cat-paper { background: var(--paper); color: var(--ink); }
    .cat-card.cat-paper:hover { border-color: var(--sage); }
    .cat-card.cat-paper .cat-ic { background: var(--sage-soft); color: var(--sage); }
    .cat-card.cat-paper .cat-arrow { color: var(--sage); }

    .cat-card.cat-sage { background: var(--sage); color: var(--paper); border-color: var(--sage); }
    .cat-card.cat-sage .cat-ic { background: rgba(255,255,255,.14); color: var(--honey); }
    .cat-card.cat-sage .cat-arrow { color: var(--honey); }

    .cat-card.cat-honey { background: var(--honey); color: var(--ink); border-color: var(--honey); }
    .cat-card.cat-honey .cat-ic { background: var(--ink); color: var(--honey); }
    .cat-card.cat-honey .cat-arrow { color: var(--ink); }

    .cat-card .cat-ic {
      width: 46px; height: 46px; border-radius: 13px;
      display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
    }
    .cat-card .cat-title { font-weight: 700; font-size: 1.18rem; line-height: 1.15; letter-spacing: -.01em; }
    .cat-card .cat-desc  { font-size: .9rem; opacity: .82; line-height: 1.5; flex-grow: 1; }
    .cat-card .cat-arrow { display: inline-flex; align-items: center; gap: 6px; font-weight: 600; font-size: .85rem; }

    /* ----------------- FAQ ----------------- */
    .faq-list { max-width: 780px; margin: 0 auto; }
    .faq-item {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 16px;
      margin-bottom: 12px; overflow: hidden; transition: all .2s ease;
    }
    .faq-item:hover { border-color: var(--sage); }
    .faq-item[open] { border-color: var(--sage); box-shadow: 0 8px 20px -10px rgba(31,95,77,.18); }
    .faq-item summary {
      padding: 22px 26px; cursor: pointer; list-style: none;
      display: flex; justify-content: space-between; align-items: center; gap: 16px;
      font-weight: 700; color: var(--ink); font-size: 1.02rem;
    }
    .faq-item summary::-webkit-details-marker { display: none; }
    .faq-item summary .plus {
      width: 32px; height: 32px; border-radius: 50%; background: var(--sage-soft);
      color: var(--sage); display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 1.2rem; transition: all .25s ease; flex-shrink: 0;
    }
    .faq-item[open] summary .plus { background: var(--sage); color: var(--paper); transform: rotate(45deg); }
    .faq-item p { padding: 0 26px 24px; color: var(--ink-mute); line-height: 1.6; margin: 0; font-size: .95rem; }

    /* ----------------- Dual CTA ----------------- */
    .dual-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 991.98px) { .dual-grid { grid-template-columns: 1fr; } }
    .dual-card {
      border-radius: 28px; padding: 48px 44px;
      position: relative; overflow: hidden;
      display: flex; flex-direction: column; min-height: 320px;
    }
    .dual-card.d-sage  { background: var(--sage);  color: var(--paper); }
    .dual-card.d-honey { background: var(--honey); color: var(--ink); }
    .dual-card .role-badge {
      display: inline-flex; padding: 6px 14px; border-radius: 999px;
      font-weight: 600; font-size: .78rem; letter-spacing: -.005em;
      width: fit-content; margin-bottom: 22px;
    }
    .dual-card.d-sage  .role-badge { background: rgba(255,255,255,.18); color: var(--paper); }
    .dual-card.d-honey .role-badge { background: rgba(15,15,15,.1); color: var(--ink); }
    .dual-card h3 { font-size: clamp(1.6rem, 2.4vw, 2.1rem); line-height: 1.1; margin-bottom: 14px; color: inherit; font-weight: 800; letter-spacing: -.02em; }
    .dual-card.d-sage  h3 .accent { color: var(--honey); font-style: italic; }
    .dual-card.d-honey h3 .accent { color: var(--sage); font-style: italic; }
    .dual-card p { margin-bottom: 28px; line-height: 1.55; }
    .dual-card.d-sage  p { color: rgba(255,255,255,.82); }
    .dual-card.d-honey p { color: var(--ink-mute); }
    .dual-card .icon-deco {
      position: absolute; right: 36px; top: 36px;
      font-size: 4.5rem; opacity: .14; pointer-events: none;
    }
    .dual-card .btn-action {
      align-self: flex-start; margin-top: auto; padding: 14px 24px; border-radius: 12px;
      font-weight: 700; font-size: .95rem; text-decoration: none;
      display: inline-flex; align-items: center; gap: 10px; transition: all .2s ease;
    }
    .dual-card.d-sage  .btn-action { background: var(--honey); color: var(--ink); }
    .dual-card.d-sage  .btn-action:hover { background: var(--paper); transform: translateY(-2px); }
    .dual-card.d-honey .btn-action { background: var(--ink); color: var(--paper); }
    .dual-card.d-honey .btn-action:hover { background: var(--sage); transform: translateY(-2px); }

    /* ----------------- CTA ----------------- */
    .cta-block {
      background: var(--sage); color: var(--paper);
      border-radius: 28px; padding: 60px 56px;
      position: relative; overflow: hidden;
    }
    .cta-block .blob-cta {
      position: absolute; right: -100px; top: -100px;
      width: 380px; height: 380px; border-radius: 50%;
      background: rgba(245,200,66,.18); filter: blur(40px);
      pointer-events: none;
    }
    .cta-block h2 { color: var(--paper); }
    .cta-block .accent { color: var(--honey); }
    .cta-block .lead-x { color: rgba(255,255,255,.78); }
    @media (max-width: 767.98px) { .cta-block { padding: 44px 30px; } }

    /* ----------------- Conversation list (logged-in) ----------------- */
    .conv-list { display:flex; flex-direction:column; gap: 8px; }
    .conv-row {
      display: grid; grid-template-columns: 52px 1fr auto; gap: 14px; align-items: center;
      padding: 14px 16px; background: var(--paper); border: 1px solid var(--rule); border-radius: 14px;
      text-decoration: none; color: var(--ink); transition: all .15s;
    }
    .conv-row:hover { border-color: var(--sage); transform: translateX(2px); color: var(--ink); }
    .conv-row .ava { width: 52px; height: 52px; border-radius: 50%; object-fit: cover; }
    .conv-row .name { font-weight: 700; color: var(--ink); font-size: .98rem; }
    .conv-row .preview { font-size: .88rem; color: var(--ink-mute); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 460px; }
    .conv-row .pill {
      background: var(--honey); color: var(--ink); font-weight: 700;
      font-size: .72rem; padding: 4px 10px; border-radius: 999px;
    }

    /* ----------------- Demande / Proposition cards (logged-in) ----------------- */
    .demande-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
    @media (max-width: 991.98px) { .demande-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 575.98px)  { .demande-grid { grid-template-columns: 1fr; } }
    .demande-card {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 18px;
      padding: 22px 22px 20px; text-decoration: none; color: var(--ink);
      display: flex; flex-direction: column; gap: 10px; transition: all .18s ease;
      position: relative; overflow: hidden;
    }
    .demande-card:hover { transform: translateY(-2px); border-color: var(--sage); color: var(--ink); box-shadow: 0 22px 44px -28px rgba(31,95,77,.28); }
    .demande-card .top {
      display:flex; align-items:center; justify-content:space-between; gap:8px;
      font-size:.78rem; color: var(--ink-mute); letter-spacing:.04em;
    }
    .demande-card .price-pill {
      background: var(--sage-soft); color: var(--sage); font-weight: 800;
      padding: 4px 11px; border-radius: 999px; font-size: .82rem;
    }
    .demande-card h4 {
      font-size: 1.12rem; font-weight: 800; color: var(--ink);
      letter-spacing: -.012em; line-height: 1.25; margin: 0;
      display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .demande-card p {
      font-size: .88rem; color: var(--ink-mute); margin: 0; line-height: 1.5;
      display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
    }
    .demande-card .meta {
      margin-top: auto; padding-top: 10px; border-top: 1px dashed var(--rule);
      display: flex; align-items: center; justify-content: space-between;
      font-size: .82rem; color: var(--ink-soft); gap: 10px;
    }
    .demande-card .meta .deadline { display: inline-flex; align-items: center; gap: 6px; }
    .demande-card .meta .props {
      display: inline-flex; align-items: center; gap: 6px; font-weight: 700; color: var(--sage);
    }

    /* role banner highlighting the new feature */
    .market-banner {
      background: linear-gradient(135deg, var(--sage) 0%, #2A7B65 100%);
      color: var(--paper); border-radius: 22px; padding: 28px 32px;
      display: grid; grid-template-columns: 1fr auto; gap: 22px; align-items: center;
      box-shadow: 0 24px 48px -28px rgba(31,95,77,.45);
      position: relative; overflow: hidden;
    }
    .market-banner::before {
      content: ''; position: absolute; right: -60px; top: -60px; width: 220px; height: 220px;
      border-radius: 50%; background: rgba(245,200,66,.18);
    }
    .market-banner .mb-eyebrow {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(245,200,66,.22); color: var(--honey);
      padding: 5px 12px; border-radius: 999px;
      font-size: .76rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
      margin-bottom: 12px;
    }
    .market-banner h3 {
      color: var(--paper); font-size: 1.55rem; font-weight: 800;
      letter-spacing: -.018em; margin: 0 0 8px; line-height: 1.2;
    }
    .market-banner p { color: rgba(255,255,255,.78); margin: 0; max-width: 520px; line-height: 1.55; font-size: .95rem; }
    .market-banner .mb-cta {
      background: var(--honey); color: var(--ink); padding: 14px 22px;
      border-radius: 14px; font-weight: 700; text-decoration: none;
      display: inline-flex; align-items: center; gap: 8px; white-space: nowrap;
      transition: transform .15s, box-shadow .15s; z-index: 1;
    }
    .market-banner .mb-cta:hover { transform: translateY(-2px); box-shadow: 0 14px 28px -12px rgba(245,200,66,.55); color: var(--ink); }
    @media (max-width: 767.98px) {
      .market-banner { grid-template-columns: 1fr; padding: 24px; }
      .market-banner .mb-cta { width: 100%; justify-content: center; }
    }

    /* profile completion nudge banner */
    .complete-banner {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 22px;
      padding: 24px 28px; display: flex; align-items: center; gap: 24px;
      box-shadow: 0 18px 36px -22px rgba(31,95,77,.18);
      position: relative; overflow: hidden;
    }
    .complete-banner::before {
      content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--honey);
    }
    .complete-banner .cb-icon {
      width: 56px; height: 56px; border-radius: 16px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      background: var(--honey-soft); color: #92660A; font-size: 1.5rem;
    }
    .complete-banner .cb-body { flex: 1; min-width: 0; }
    .complete-banner .cb-ttl { font-weight: 800; font-size: 1.1rem; color: var(--ink); margin-bottom: 4px; }
    .complete-banner .cb-sub { color: var(--ink-mute); font-size: .9rem; margin: 0 0 10px; }
    .complete-banner .cb-bar { height: 6px; background: var(--bg); border-radius: 999px; overflow: hidden; max-width: 360px; }
    .complete-banner .cb-bar > span { display: block; height: 100%; background: var(--sage); border-radius: 999px; transition: width .8s ease; }
    .complete-banner .cb-pct { font-weight: 700; color: var(--sage); font-size: .85rem; margin-top: 6px; }
    .complete-banner .cb-cta {
      flex-shrink: 0;
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--ink); color: var(--paper); padding: 12px 22px; border-radius: 12px;
      text-decoration: none; font-weight: 700; font-size: .92rem; transition: all .2s;
    }
    .complete-banner .cb-cta:hover { background: var(--sage); color: var(--paper); transform: translateY(-2px); }
    @media (max-width: 767.98px) {
      .complete-banner { flex-direction: column; align-items: flex-start; padding: 22px; }
      .complete-banner .cb-cta { width: 100%; justify-content: center; }
    }

    /* dashboard quick actions */
    .actions-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; }
    @media (max-width: 991.98px) { .actions-grid { grid-template-columns: repeat(2,1fr); } }
    .action-card {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 20px;
      padding: 24px 22px; text-decoration: none; color: var(--ink); transition: all .2s;
      display: flex; flex-direction: column;
    }
    .action-card:hover { transform: translateY(-3px); border-color: var(--sage); color: var(--ink); box-shadow: 0 20px 40px -20px rgba(31,95,77,.18); }
    .action-card .ic { width: 42px; height: 42px; border-radius: 12px; display:flex; align-items:center; justify-content:center; font-size: 1.05rem; margin-bottom: 14px; background: var(--sage-soft); color: var(--sage); }
    .action-card.honey .ic { background: var(--honey-soft); color: #92660A; }
    .action-card.dark { background: var(--ink); color: var(--paper); border-color: var(--ink); }
    .action-card.dark:hover { color: var(--paper); }
    .action-card.dark .ic { background: var(--honey); color: var(--ink); }
    .action-card.dark p { color: rgba(255,255,255,.7); }
    .action-card h5 { font-size: 1rem; font-weight: 700; margin-bottom: 4px; }
    .action-card p  { font-size: .85rem; color: var(--ink-mute); margin: 0; }

    /* progress bar (sage) */
    .progress-track { height: 6px; background: var(--bg); border-radius: 999px; overflow: hidden; margin-top: 10px; }
    .progress-track > span { display:block; height: 100%; background: var(--sage); border-radius: 999px; transition: width .8s ease; }

    /* ----------------- Footer ----------------- */
    .sb-footer { background: var(--ink); color: rgba(255,255,255,.7); padding: 56px 0 32px; }
    .sb-footer h6 { color: var(--paper); font-size: .82rem; font-weight: 700; letter-spacing: -.005em; margin-bottom: 14px; }
    .sb-footer a { color: rgba(255,255,255,.65); text-decoration: none; transition: color .15s; font-size: .9rem; }
    .sb-footer a:hover { color: var(--honey); }
    .sb-footer .foot-logo { display:inline-flex; align-items:center; gap:8px; }
    .sb-footer .foot-logo .mark-circle { background: var(--honey); color: var(--sage); }
    .sb-footer .foot-logo .name { color: var(--paper); font-weight: 800; font-size: 1.1rem; }
    .sb-footer .copy { color: rgba(255,255,255,.45); font-size: .82rem; }

    /* legacy hooks left so other code referencing these doesn't break */
    .navmenu a.active { color: var(--sage) !important; }
    .nav-profile-chip > a { background: var(--sage-soft); color: var(--ink); }
  </style>
</head>

<body>

  <!-- ================== HEADER ================== -->
  <header class="sb-header">
    <div class="container">
      <a href="index.php" class="sb-logo">
        <img src="assets/img/skillbridge-logo.png" alt="SkillBridge" class="logo-img" loading="eager">
      </a>

      <nav class="sb-nav">
        <?php if ($isLoggedIn): ?>
          <a href="index.php" class="active">Accueil</a>
          <a href="../chat/conversations.php">Conversations<?php if ($dashboard['unread_count'] > 0): ?> <span style="color:var(--honey-d);">·<?= $dashboard['unread_count'] ?></span><?php endif; ?></a>
          <?php if ($isClient): ?>
            <a href="mes_demandes.php">Mes demandes</a>
          <?php elseif ($isFreelancer): ?>
            <a href="browse_demandes.php">Demandes</a>
            <a href="mes_propositions.php">Mes propositions</a>
          <?php endif; ?>
          <a href="#talents">Talents</a>
        <?php else: ?>
          <a href="#how-it-works">Méthode</a>
          <a href="#talents">Talents</a>
          <a href="#why">Plateforme</a>
          <a href="#faq">FAQ</a>
        <?php endif; ?>
      </nav>

      <div class="d-flex align-items-center gap-2">
        <?php if ($isLoggedIn): ?>
          <span id="bellSlot" class="sb-bell-btn" style="display:inline-flex;"></span>
          <a href="profil.php" class="sb-profile-chip" title="Mon Profil">
            <?php $navAvatar = avatarUrl($userPhoto, $userNom, '1F5F4D', 80); ?>
            <img src="<?= $navAvatar ?>" alt="" class="avatar"
                 onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?= urlencode($userFirstName) ?>&background=1F5F4D&color=fff&bold=true&size=80';">
            <span><?= htmlspecialchars($userFirstName) ?></span>
          </a>
          <a href="<?= $BASE ?>/controller/utilisateurcontroller.php?action=logout" class="sb-cta d-none d-md-inline-flex">
            <i class="bi bi-box-arrow-right"></i><span>Quitter</span>
          </a>
        <?php else: ?>
          <a href="login.php" style="text-decoration:none; color:var(--ink); font-weight:600; padding:8px 14px; font-size:.92rem;">Connexion</a>
          <a href="register.php" class="sb-cta">
            <span>Commencer</span><i class="bi bi-arrow-right"></i>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main>

  <?php if ($isLoggedIn): /* =================== DASHBOARD ====================== */
    $heroImg   = $isClient
        ? 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?w=900&auto=format&fit=crop&q=80'
        : 'https://images.unsplash.com/photo-1531497865144-0464ef8fb9a9?w=900&auto=format&fit=crop&q=80';
    $roleLabel = $isClient ? 'Client' : ($isFreelancer ? 'Freelancer' : 'Administrateur');
  ?>

    <!-- Hero (logged-in) -->
    <section class="hero">
      <div class="blob sage  blob-1"></div>
      <div class="blob honey blob-2"></div>
      <div class="container">
        <div class="hero-grid">

          <div data-aos="fade-up">
            <span class="eyebrow"><span class="dot"></span> Espace <?= htmlspecialchars($roleLabel) ?></span>
            <h1 class="display-x mt-3 mb-3">
              Bonjour, <span class="accent"><?= htmlspecialchars($userFirstName) ?></span>.
              <br>Ravi de vous revoir.
            </h1>
            <p class="lead-x" style="max-width:520px;">
              <?php if ($isFreelancer): ?>
                Parcourez les <strong>demandes ouvertes</strong>, envoyez vos <strong>propositions</strong> et reprenez vos conversations — tout depuis un seul espace.
              <?php elseif ($isClient): ?>
                Publiez une <strong>demande</strong>, recevez des <strong>propositions</strong> ciblées de freelancers vérifiés et collaborez en messagerie temps réel.
              <?php else: ?>
                Pilotez la plateforme depuis votre espace administrateur.
              <?php endif; ?>
            </p>

            <div class="hero-cta-row">
              <?php if ($isClient): ?>
                <a href="add_demande.php" class="btn-sage">
                  <i class="bi bi-plus-circle"></i> Publier une demande
                </a>
                <a href="../chat/conversations.php" class="btn-ghost">
                  <i class="bi bi-chat-dots"></i> Mes Conversations
                  <?php if ($dashboard['unread_count'] > 0): ?>
                    <span style="background:var(--honey); color:var(--ink); padding: 2px 9px; border-radius: 999px; font-size:.78rem; font-weight:700; margin-left:6px;"><?= $dashboard['unread_count'] ?></span>
                  <?php endif; ?>
                </a>
              <?php elseif ($isFreelancer): ?>
                <a href="browse_demandes.php" class="btn-sage">
                  <i class="bi bi-collection"></i> Voir les demandes
                  <?php if ($market['open_demandes'] > 0): ?>
                    <span style="background:var(--honey); color:var(--ink); padding: 2px 9px; border-radius: 999px; font-size:.78rem; font-weight:700; margin-left:6px;"><?= $market['open_demandes'] ?></span>
                  <?php endif; ?>
                </a>
                <a href="../chat/conversations.php" class="btn-ghost">
                  <i class="bi bi-chat-dots"></i> Conversations
                  <?php if ($dashboard['unread_count'] > 0): ?>
                    <span style="background:var(--honey); color:var(--ink); padding: 2px 9px; border-radius: 999px; font-size:.78rem; font-weight:700; margin-left:6px;"><?= $dashboard['unread_count'] ?></span>
                  <?php endif; ?>
                </a>
              <?php else: ?>
                <a href="../chat/conversations.php" class="btn-sage">
                  <i class="bi bi-chat-dots"></i> Mes Conversations
                </a>
                <a href="profil.php" class="btn-ghost">
                  <i class="bi bi-person-circle"></i> Mon profil
                </a>
              <?php endif; ?>
            </div>

            <div class="hero-stats">
              <?php if ($isClient): ?>
                <div class="cell"><div class="num"><?= (int)$market['total_demandes'] ?></div><div class="lbl">Mes demandes</div></div>
                <div class="cell"><div class="num"><?= (int)$market['received_props'] ?></div><div class="lbl">Propositions reçues</div></div>
                <div class="cell"><div class="num"><?= $dashboard['unread_count'] ?></div><div class="lbl">Messages non lus</div></div>
              <?php elseif ($isFreelancer): ?>
                <div class="cell"><div class="num"><?= (int)$market['open_demandes'] ?></div><div class="lbl">Demandes ouvertes</div></div>
                <div class="cell"><div class="num"><?= (int)$market['my_propositions'] ?></div><div class="lbl">Mes propositions</div></div>
                <div class="cell"><div class="num"><?= $dashboard['unread_count'] ?></div><div class="lbl">Messages non lus</div></div>
              <?php else: ?>
                <div class="cell"><div class="num"><?= count($dashboard['conversations']) ?></div><div class="lbl">Conversations</div></div>
                <div class="cell"><div class="num"><?= $dashboard['unread_count'] ?></div><div class="lbl">Non lus</div></div>
                <div class="cell"><div class="num"><?= $dashboard['profile_pct'] ?>%</div><div class="lbl">Profil</div></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="hero-visual d-none d-lg-block" data-aos="fade-left">
            <div class="hero-photo">
              <img src="<?= $heroImg ?>" alt="Espace personnel <?= htmlspecialchars($roleLabel) ?>">
            </div>
            <div class="signal-card">
              <span class="pulse"></span>
              <span class="txt"><?= $dashboard['unread_count'] > 0 ? $dashboard['unread_count'].' non lu'.($dashboard['unread_count']>1?'s':'') : 'Tout est lu' ?></span>
            </div>
            <div class="talent-mini">
              <img src="<?= avatarUrl($userPhoto, $userNom, '1F5F4D', 96) ?>" alt="">
              <div>
                <div class="label">Profil</div>
                <div class="name"><?= $dashboard['profile_pct'] ?>% complété</div>
                <div class="role"><?= $dashboard['profile_pct'] >= 80 ? 'Excellent !' : 'À compléter' ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <?php if ($dashboard['profile_pct'] < 80): ?>
    <!-- Profile completion nudge -->
    <section style="padding: 0 0 12px;">
      <div class="container">
        <div class="complete-banner" data-aos="fade-up">
          <div class="cb-icon"><i class="bi bi-stars"></i></div>
          <div class="cb-body">
            <div class="cb-ttl">Complétez votre profil pour gagner en visibilité.</div>
            <p class="cb-sub">Plus votre profil est rempli, plus vous attirez de <?= $isClient ? 'freelancers' : 'clients' ?>. Bio, compétences, photo — quelques minutes suffisent.</p>
            <div class="cb-bar"><span style="width:<?= $dashboard['profile_pct'] ?>%;"></span></div>
            <div class="cb-pct"><?= $dashboard['profile_pct'] ?>% complété</div>
          </div>
          <a href="profil.php" class="cb-cta">
            Compléter <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <!-- Quick actions -->
    <section class="s-pad">
      <div class="container">
        <div class="section-head" data-aos="fade-up">
          <span class="eyebrow"><span class="dot"></span> Accès rapides</span>
          <h2 class="display-l">Que voulez-vous faire <span class="accent">aujourd'hui</span> ?</h2>
        </div>
        <div class="actions-grid">
          <?php if ($isClient): ?>
            <a href="add_demande.php" class="action-card dark" data-aos="fade-up">
              <div class="ic"><i class="bi bi-file-earmark-plus"></i></div>
              <h5>Publier une demande</h5>
              <p>Décrivez votre besoin, recevez des propositions.</p>
            </a>
            <a href="mes_demandes.php" class="action-card" data-aos="fade-up" data-aos-delay="80">
              <div class="ic"><i class="bi bi-clipboard2-check-fill"></i></div>
              <h5>Mes demandes <span style="color:var(--sage); font-weight:700;">(<?= (int)$market['total_demandes'] ?>)</span></h5>
              <p><?= (int)$market['received_props'] ?> proposition<?= $market['received_props'] > 1 ? 's' : '' ?> reçue<?= $market['received_props'] > 1 ? 's' : '' ?>.</p>
            </a>
            <a href="../chat/conversations.php" class="action-card honey" data-aos="fade-up" data-aos-delay="160">
              <div class="ic"><i class="bi bi-chat-dots-fill"></i></div>
              <h5>Mes conversations</h5>
              <p><?= $dashboard['unread_count'] > 0 ? $dashboard['unread_count'].' non lu'.($dashboard['unread_count']>1?'s':'') : 'Reprenez vos discussions.' ?></p>
            </a>
            <a href="profil.php" class="action-card" data-aos="fade-up" data-aos-delay="240">
              <div class="ic"><i class="bi bi-person-circle"></i></div>
              <h5>Mon profil <span style="color:var(--sage); font-weight:700;">(<?= $dashboard['profile_pct'] ?>%)</span></h5>
              <div class="progress-track"><span style="width:<?= $dashboard['profile_pct'] ?>%;"></span></div>
            </a>
          <?php elseif ($isFreelancer): ?>
            <a href="browse_demandes.php" class="action-card dark" data-aos="fade-up">
              <div class="ic"><i class="bi bi-search"></i></div>
              <h5>Parcourir les demandes</h5>
              <p><?= (int)$market['open_demandes'] ?> demande<?= $market['open_demandes'] > 1 ? 's' : '' ?> en ligne.</p>
            </a>
            <a href="mes_propositions.php" class="action-card" data-aos="fade-up" data-aos-delay="80">
              <div class="ic"><i class="bi bi-megaphone-fill"></i></div>
              <h5>Mes propositions <span style="color:var(--sage); font-weight:700;">(<?= (int)$market['my_propositions'] ?>)</span></h5>
              <p>Suivez vos offres envoyées.</p>
            </a>
            <a href="../chat/conversations.php" class="action-card honey" data-aos="fade-up" data-aos-delay="160">
              <div class="ic"><i class="bi bi-chat-dots-fill"></i></div>
              <h5>Mes conversations</h5>
              <p><?= $dashboard['unread_count'] > 0 ? $dashboard['unread_count'].' non lu'.($dashboard['unread_count']>1?'s':'') : 'Reprenez vos discussions.' ?></p>
            </a>
            <a href="profil.php" class="action-card" data-aos="fade-up" data-aos-delay="240">
              <div class="ic"><i class="bi bi-person-circle"></i></div>
              <h5>Mon profil <span style="color:var(--sage); font-weight:700;">(<?= $dashboard['profile_pct'] ?>%)</span></h5>
              <div class="progress-track"><span style="width:<?= $dashboard['profile_pct'] ?>%;"></span></div>
            </a>
          <?php else: ?>
            <a href="../chat/new_conversation.php" class="action-card dark" data-aos="fade-up">
              <div class="ic"><i class="bi bi-plus-lg"></i></div>
              <h5>Nouvelle conversation</h5>
              <p>Initiez un échange.</p>
            </a>
            <a href="../chat/conversations.php" class="action-card" data-aos="fade-up" data-aos-delay="80">
              <div class="ic"><i class="bi bi-chat-dots-fill"></i></div>
              <h5>Mes conversations</h5>
              <p>Reprenez vos discussions.</p>
            </a>
            <a href="profil.php" class="action-card honey" data-aos="fade-up" data-aos-delay="160">
              <div class="ic"><i class="bi bi-person-circle"></i></div>
              <h5>Mon profil <span style="color:var(--honey-d); font-weight:700;">(<?= $dashboard['profile_pct'] ?>%)</span></h5>
              <div class="progress-track"><span style="width:<?= $dashboard['profile_pct'] ?>%;"></span></div>
            </a>
            <a href="#talents" class="action-card" data-aos="fade-up" data-aos-delay="240">
              <div class="ic"><i class="bi bi-stars"></i></div>
              <h5>Voir la communauté</h5>
              <p>Profils vérifiés.</p>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- Recent conversations -->
    <section class="s-pad" style="background: var(--paper); border-top: 1px solid var(--rule); border-bottom: 1px solid var(--rule);">
      <div class="container">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 section-head" data-aos="fade-up" style="margin-bottom: 32px;">
          <div>
            <span class="eyebrow"><span class="dot"></span> Activité récente</span>
            <h2 class="display-l mt-3">Vos derniers <span class="accent">échanges</span>.</h2>
          </div>
          <a href="../chat/conversations.php" class="btn-ghost"><i class="bi bi-arrow-up-right"></i> Tout voir</a>
        </div>

        <?php if (empty($dashboard['conversations'])): ?>
          <div class="text-center py-5" data-aos="fade-up">
            <div style="width:80px;height:80px;border-radius:24px;background:var(--sage-soft);display:inline-flex;align-items:center;justify-content:center;margin-bottom:18px; color: var(--sage);">
              <i class="bi bi-chat-square-dots" style="font-size:2.2rem;"></i>
            </div>
            <h4 class="display-m">Aucune conversation pour l'instant.</h4>
            <p class="text-muted mb-4">Démarrez votre première discussion sur SkillBridge.</p>
            <a href="../chat/new_conversation.php" class="btn-sage"><i class="bi bi-plus-circle"></i> Démarrer</a>
          </div>
        <?php else: ?>
          <div class="conv-list" data-aos="fade-up">
            <?php foreach ($dashboard['conversations'] as $c):
                $isU1     = ((int)$c['user1_id'] === $userId);
                $otherFn  = $isU1 ? $c['u2_prenom'] : $c['u1_prenom'];
                $otherLn  = $isU1 ? $c['u2_nom']    : $c['u1_nom'];
                $otherPh  = $isU1 ? $c['u2_photo']  : $c['u1_photo'];
                $otherFul = trim($otherFn . ' ' . $otherLn);
                $avatarSrc = avatarUrl($otherPh, $otherFul, '1F5F4D', 96);
                $rawPreview = $c['last_message'] ?? '';
                $preview    = $rawPreview !== '' ? chat_message_preview($rawPreview, 90) : 'Aucun message pour l\'instant.';
                $unseen     = (int)$c['unseen'];
            ?>
              <a href="../chat/chat.php?id=<?= (int)$c['id_conversation'] ?>" class="conv-row">
                <img src="<?= $avatarSrc ?>" alt="<?= htmlspecialchars($otherFul) ?>" class="ava">
                <div>
                  <div class="name"><?= htmlspecialchars($otherFul) ?></div>
                  <div class="preview"><?= htmlspecialchars($preview) ?></div>
                </div>
                <div class="d-flex align-items-center gap-3">
                  <?php if ($unseen > 0): ?><span class="pill"><?= $unseen ?></span><?php endif; ?>
                  <i class="bi bi-arrow-right" style="color:var(--ink-soft); font-size:1.1rem;"></i>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <?php if ($isClient): ?>
      <!-- Marketplace : mes demandes -->
      <section class="s-pad">
        <div class="container">
          <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 section-head" data-aos="fade-up" style="margin-bottom: 32px;">
            <div>
              <span class="eyebrow honey"><span class="dot"></span> Marketplace · Demandes</span>
              <h2 class="display-l mt-3">Vos <span class="accent">demandes</span> ouvertes.</h2>
            </div>
            <div class="d-flex gap-2 flex-wrap">
              <a href="add_demande.php" class="btn-sage"><i class="bi bi-plus-circle"></i> Nouvelle demande</a>
              <a href="mes_demandes.php" class="btn-ghost"><i class="bi bi-arrow-up-right"></i> Tout voir</a>
            </div>
          </div>

          <?php if (empty($market['my_demandes'])): ?>
            <div class="market-banner" data-aos="fade-up">
              <div>
                <span class="mb-eyebrow"><i class="bi bi-stars"></i> Nouveau</span>
                <h3>Publiez votre première demande.</h3>
                <p>Décrivez votre projet — titre, budget, échéance — et recevez en quelques heures des propositions ciblées de freelancers vérifiés.</p>
              </div>
              <a href="add_demande.php" class="mb-cta">Publier maintenant <i class="bi bi-arrow-right"></i></a>
            </div>
          <?php else: ?>
            <div class="demande-grid">
              <?php foreach ($market['my_demandes'] as $d):
                  $deadline = !empty($d['deadline']) ? date('d M Y', strtotime($d['deadline'])) : null;
                  $price    = isset($d['price']) ? number_format((float)$d['price'], 0, ',', ' ') : '—';
                  $count    = (int)($d['prop_count'] ?? 0);
              ?>
                <a href="demande_propositions.php?id=<?= (int)$d['id'] ?>" class="demande-card" data-aos="fade-up">
                  <div class="top">
                    <span style="text-transform:uppercase; font-weight:700; letter-spacing:.06em;">Demande</span>
                    <span class="price-pill"><?= htmlspecialchars($price) ?> DT</span>
                  </div>
                  <h4><?= htmlspecialchars(html_entity_decode($d['title'])) ?></h4>
                  <p><?= htmlspecialchars(html_entity_decode($d['description'])) ?></p>
                  <div class="meta">
                    <?php if ($deadline): ?>
                      <span class="deadline"><i class="bi bi-calendar-event"></i> <?= htmlspecialchars($deadline) ?></span>
                    <?php else: ?>
                      <span class="deadline"><i class="bi bi-clock-history"></i> Sans échéance</span>
                    <?php endif; ?>
                    <span class="props"><i class="bi bi-megaphone-fill"></i> <?= $count ?> proposition<?= $count > 1 ? 's' : '' ?></span>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>
    <?php elseif ($isFreelancer): ?>
      <!-- Marketplace : demandes ouvertes -->
      <section class="s-pad">
        <div class="container">
          <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 section-head" data-aos="fade-up" style="margin-bottom: 32px;">
            <div>
              <span class="eyebrow honey"><span class="dot"></span> Marketplace · Opportunités</span>
              <h2 class="display-l mt-3">Demandes <span class="accent">ouvertes</span>.</h2>
            </div>
            <div class="d-flex gap-2 flex-wrap">
              <a href="browse_demandes.php" class="btn-sage"><i class="bi bi-collection"></i> Toutes les demandes</a>
              <a href="mes_propositions.php" class="btn-ghost"><i class="bi bi-megaphone"></i> Mes propositions</a>
            </div>
          </div>

          <?php if (empty($market['latest_demandes'])): ?>
            <div class="market-banner" data-aos="fade-up">
              <div>
                <span class="mb-eyebrow"><i class="bi bi-hourglass-split"></i> Bientôt</span>
                <h3>Aucune demande ouverte pour le moment.</h3>
                <p>Revenez plus tard — les nouvelles missions s'affichent dès qu'un client publie. Complétez votre profil pour ne rien manquer.</p>
              </div>
              <a href="profil.php" class="mb-cta">Compléter mon profil <i class="bi bi-arrow-right"></i></a>
            </div>
          <?php else: ?>
            <div class="demande-grid">
              <?php foreach ($market['latest_demandes'] as $d):
                  $deadline = !empty($d['deadline']) ? date('d M Y', strtotime($d['deadline'])) : null;
                  $price    = isset($d['price']) ? number_format((float)$d['price'], 0, ',', ' ') : '—';
                  $created  = !empty($d['created_at']) ? date('d M', strtotime($d['created_at'])) : '';
              ?>
                <a href="add_proposition.php?demande_id=<?= (int)$d['id'] ?>" class="demande-card" data-aos="fade-up">
                  <div class="top">
                    <span style="text-transform:uppercase; font-weight:700; letter-spacing:.06em;"><?= $created ? 'Publié le '.htmlspecialchars($created) : 'Nouveau' ?></span>
                    <span class="price-pill"><?= htmlspecialchars($price) ?> DT</span>
                  </div>
                  <h4><?= htmlspecialchars(html_entity_decode($d['title'])) ?></h4>
                  <p><?= htmlspecialchars(html_entity_decode($d['description'])) ?></p>
                  <div class="meta">
                    <?php if ($deadline): ?>
                      <span class="deadline"><i class="bi bi-calendar-event"></i> <?= htmlspecialchars($deadline) ?></span>
                    <?php else: ?>
                      <span class="deadline"><i class="bi bi-clock-history"></i> Sans échéance</span>
                    <?php endif; ?>
                    <span class="props"><i class="bi bi-send-fill"></i> Proposer</span>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>
    <?php endif; ?>

  <?php else: /* =================== MARKETING ============================== */ ?>

    <!-- Hero (marketing) -->
    <section class="hero">
      <div class="blob sage  blob-1"></div>
      <div class="blob honey blob-2"></div>
      <div class="container">
        <div class="hero-grid">

          <div data-aos="fade-up">
            <span class="eyebrow"><span class="dot"></span> Marketplace freelance · Tunisie</span>
            <h1 class="display-x mt-3 mb-3">
              Publiez. Recevez des <span class="accent">propositions</span>.<br>
              Collaborez.
            </h1>
            <p class="lead-x" style="max-width:540px;">
              SkillBridge réunit clients et freelancers vérifiés.
              Publiez votre <strong>demande</strong>, comparez les <strong>propositions</strong> et discutez en messagerie temps réel —
              tout au même endroit.
            </p>

            <div class="hero-cta-row">
              <a href="register.php" class="btn-sage">
                Commencer gratuitement <i class="bi bi-arrow-right"></i>
              </a>
              <a href="#how-it-works" class="btn-ghost">
                Comment ça marche
              </a>
            </div>

            <div class="hero-stats">
              <div class="cell"><div class="num"><?= max(1, $stats['freelancers']) ?>+</div><div class="lbl">Freelancers</div></div>
              <div class="cell"><div class="num"><?= max(1, $stats['clients']) ?>+</div><div class="lbl">Clients</div></div>
              <div class="cell"><div class="num"><?= max(1, $market['open_demandes']) ?>+</div><div class="lbl">Demandes ouvertes</div></div>
              <div class="cell"><div class="num"><?= max(1, $stats['conversations']) ?>+</div><div class="lbl">Collaborations</div></div>
            </div>
          </div>

          <div class="hero-visual d-none d-lg-block" data-aos="fade-left">
            <div class="hero-photo">
              <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=900&auto=format&fit=crop&q=80" alt="Équipe collaborant sur un projet">
            </div>
            <div class="signal-card">
              <span class="pulse"></span>
              <span class="txt">Profils vérifiés</span>
            </div>
            <?php if ($weekTalent): $wtName = trim($weekTalent['prenom'].' '.$weekTalent['nom']);
                                    $wtAva  = avatarUrl($weekTalent['photo'], $wtName, '1F5F4D', 96);
                                    $wtRole = !empty($weekTalent['competences']) ? explode(',', $weekTalent['competences'])[0] : 'Freelancer'; ?>
              <div class="talent-mini">
                <img src="<?= $wtAva ?>" alt="">
                <div>
                  <div class="label">Talent du moment</div>
                  <div class="name"><?= htmlspecialchars($wtName) ?></div>
                  <div class="role"><?= htmlspecialchars(trim($wtRole)) ?></div>
                </div>
              </div>
            <?php else: ?>
              <div class="talent-mini">
                <div style="width:46px;height:46px;border-radius:50%;background:var(--sage-soft);display:flex;align-items:center;justify-content:center;color:var(--sage);font-size:1.2rem;flex-shrink:0;">
                  <i class="bi bi-chat-dots-fill"></i>
                </div>
                <div>
                  <div class="label">Chat temps réel</div>
                  <div class="name">Messagerie instantanée</div>
                  <div class="role">Fichiers, photos, réactions</div>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>

    <!-- Method (3 steps) -->
    <section class="s-pad" id="how-it-works">
      <div class="container">
        <div class="section-head text-center mx-auto" data-aos="fade-up">
          <span class="eyebrow"><span class="dot"></span> Méthode</span>
          <h2 class="display-x">Démarrez en <span class="accent">trois étapes</span>.</h2>
          <p class="lead-x">De la demande à la collaboration, un parcours pensé pour aller droit au but.</p>
        </div>

        <div class="method-grid">
          <div class="method-card" data-aos="fade-up">
            <div class="num">1</div>
            <h3>Publiez votre demande</h3>
            <p>Côté client : décrivez votre projet — titre, budget, échéance, description. La demande est aussitôt visible des freelancers vérifiés.</p>
          </div>
          <div class="method-card featured" data-aos="fade-up" data-aos-delay="100">
            <div class="num">2</div>
            <h3>Recevez des propositions</h3>
            <p>Côté freelancer : parcourez les demandes ouvertes et envoyez votre proposition (message + tarif). Côté client : comparez les offres reçues.</p>
          </div>
          <div class="method-card" data-aos="fade-up" data-aos-delay="200">
            <div class="num">3</div>
            <h3>Collaborez en direct</h3>
            <p>Une fois la proposition retenue, ouvrez la conversation : messagerie temps réel, fichiers, photos, réactions emoji — tout au même endroit.</p>
          </div>
        </div>
      </div>
    </section>

  <?php endif; /* end split */ ?>

  <!-- Talents (shared) -->
  <?php if (!empty($featured)): ?>
    <section class="s-pad" id="talents" style="background: var(--paper); border-top: 1px solid var(--rule); border-bottom: 1px solid var(--rule);">
      <div class="container">
        <div class="section-head text-center mx-auto" data-aos="fade-up">
          <span class="eyebrow honey"><span class="dot"></span> Talents vérifiés</span>
          <h2 class="display-x">
            <?php if ($isClient): ?>Recommandés <span class="accent">pour vous</span>.
            <?php elseif ($isLoggedIn): ?>La <span class="accent">communauté</span>.
            <?php else: ?>Quelques <span class="accent">talents</span> disponibles.<?php endif; ?>
          </h2>
          <p class="lead-x">Profils vérifiés, disponibles dès aujourd'hui.</p>
        </div>

        <div class="talent-grid">
          <?php foreach ($featured as $i => $f):
              $skills    = $pickSkills($f['competences'] ?? '', 3);
              $bio       = htmlspecialchars(mb_substr((string)($f['bio'] ?? ''), 0, 80));
              $location  = htmlspecialchars((string)($f['localisation'] ?? ''));
              $fullName  = htmlspecialchars(trim($f['prenom'] . ' ' . $f['nom']));
              $avatar    = avatarUrl($f['photo'], $fullName, '1F5F4D', 168);
              $contactHref = $isLoggedIn ? "../chat/new_conversation.php?user2=" . (int)$f['id'] : 'register.php';
              $contactLabel = $isLoggedIn ? 'Contacter' : 'Se connecter pour contacter';
          ?>
            <article class="talent-card" data-aos="fade-up" data-aos-delay="<?= ($i % 3) * 80 ?>">
              <div class="ava-wrap">
                <img src="<?= $avatar ?>" alt="<?= $fullName ?>">
                <span class="verified" title="Profil vérifié"><i class="bi bi-check-lg"></i></span>
              </div>
              <h4><?= $fullName ?></h4>
              <?php if ($location): ?><div class="loc"><i class="bi bi-geo-alt-fill" style="color: var(--sage);"></i> <?= $location ?></div><?php endif; ?>
              <?php if ($bio): ?><p class="bio"><?= $bio ?>…</p><?php endif; ?>
              <?php if (!empty($skills)): ?>
                <div class="skills">
                  <?php foreach ($skills as $s): ?><span class="skill"><?= htmlspecialchars($s) ?></span><?php endforeach; ?>
                </div>
              <?php endif; ?>
              <a href="<?= $contactHref ?>" class="btn-talent">
                <i class="bi bi-chat-dots"></i> <?= $contactLabel ?>
              </a>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>

  <?php if (!$isLoggedIn): ?>

    <!-- Why (clean 4-grid) -->
    <section class="s-pad" id="why">
      <div class="container">
        <div class="section-head text-center mx-auto" data-aos="fade-up">
          <span class="eyebrow"><span class="dot"></span> Plateforme</span>
          <h2 class="display-x">Pensée pour les <span class="accent">pros</span>.</h2>
          <p class="lead-x">Toutes les fonctionnalités essentielles pour collaborer en confiance — sans abonnement, sans intermédiaires opaques.</p>
        </div>

        <div class="why-grid">
          <div class="why-card" data-aos="fade-up">
            <div class="ic"><i class="bi bi-file-earmark-text-fill"></i></div>
            <h5>Demandes & propositions</h5>
            <p>Publiez votre besoin, recevez des propositions ciblées avec tarif et message — sans appel d'offres laborieux.</p>
          </div>
          <div class="why-card honey" data-aos="fade-up" data-aos-delay="80">
            <div class="ic"><i class="bi bi-chat-dots-fill"></i></div>
            <h5>Messagerie temps réel</h5>
            <p>Discussions instantanées, accusés de réception, indicateurs de saisie, réactions emoji.</p>
          </div>
          <div class="why-card" data-aos="fade-up" data-aos-delay="160">
            <div class="ic"><i class="bi bi-shield-lock-fill"></i></div>
            <h5>Auth sécurisée</h5>
            <p>Email, OAuth (Google, GitHub, Discord), ou reconnaissance faciale.</p>
          </div>
          <div class="why-card honey" data-aos="fade-up" data-aos-delay="240">
            <div class="ic"><i class="bi bi-person-badge-fill"></i></div>
            <h5>Profils vérifiés</h5>
            <p>Email vérifié, photos, bio, compétences et localisation transparentes.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Categories -->
    <section class="s-pad" id="categories" style="background: var(--paper); border-top: 1px solid var(--rule); border-bottom: 1px solid var(--rule);">
      <div class="container">
        <div class="section-head text-center mx-auto" data-aos="fade-up">
          <span class="eyebrow"><span class="dot"></span> Catégories</span>
          <h2 class="display-x">Tous les <span class="accent">domaines</span>.</h2>
          <p class="lead-x">Du code à la création, du marketing à la rédaction — tous les talents au même endroit.</p>
        </div>

        <div class="cat-grid">
          <?php foreach ($cats as $i => [$icon, $title, $desc, $kind]): ?>
            <a href="register.php" class="cat-card cat-<?= $kind ?>" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>">
              <div class="cat-ic"><i class="bi <?= $icon ?>"></i></div>
              <div class="cat-title"><?= $title ?></div>
              <div class="cat-desc"><?= $desc ?></div>
              <div class="cat-arrow">Explorer <i class="bi bi-arrow-right"></i></div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section class="s-pad" id="faq">
      <div class="container">
        <div class="section-head text-center mx-auto" data-aos="fade-up">
          <span class="eyebrow"><span class="dot"></span> FAQ</span>
          <h2 class="display-x">Vous vous <span class="accent">demandez</span> ?</h2>
          <p class="lead-x">Les questions les plus fréquentes. Si la vôtre n'y est pas, écrivez-nous.</p>
        </div>

        <div class="faq-list" data-aos="fade-up">
          <?php foreach ($faq as [$q, $a]): ?>
            <details class="faq-item">
              <summary>
                <span><?= htmlspecialchars($q) ?></span>
                <span class="plus">+</span>
              </summary>
              <p><?= htmlspecialchars($a) ?></p>
            </details>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- Dual CTA -->
    <section class="s-pad" style="background: var(--paper); border-top: 1px solid var(--rule);">
      <div class="container">
        <div class="section-head text-center mx-auto" data-aos="fade-up">
          <span class="eyebrow"><span class="dot"></span> Démarrer</span>
          <h2 class="display-x">Choisissez votre <span class="accent">rôle</span>.</h2>
          <p class="lead-x">Que vous cherchiez un freelancer ou que vous proposiez vos talents — tout commence ici.</p>
        </div>

        <div class="dual-grid">
          <div class="dual-card d-sage" data-aos="fade-right">
            <i class="bi bi-briefcase-fill icon-deco"></i>
            <span class="role-badge">Pour les clients</span>
            <h3>Trouvez le freelancer <span class="accent">idéal</span>.</h3>
            <p>Parcourez les profils vérifiés, comparez les compétences et démarrez une collaboration en quelques clics.</p>
            <a href="register.php" class="btn-action">
              Trouver un freelancer <i class="bi bi-arrow-right"></i>
            </a>
          </div>
          <div class="dual-card d-honey" data-aos="fade-left">
            <i class="bi bi-tools icon-deco"></i>
            <span class="role-badge">Pour les freelancers</span>
            <h3>Mettez en avant votre <span class="accent">talent</span>.</h3>
            <p>Créez un profil distinctif, recevez des offres ciblées et collaborez en toute simplicité.</p>
            <a href="register.php" class="btn-action">
              Créer mon profil <i class="bi bi-arrow-right"></i>
            </a>
          </div>
        </div>
      </div>
    </section>

  <?php endif; ?>

  </main>

  <!-- ================== FOOTER ================== -->
  <footer class="sb-footer">
    <div class="container">
      <div class="row gy-4">
        <div class="col-lg-5">
          <a href="index.php" class="foot-logo">
            <img src="assets/img/skillbridge-logo.png" alt="SkillBridge" class="logo-img" loading="lazy">
          </a>
          <p class="mt-3 mb-0" style="color:rgba(255,255,255,.65); max-width: 380px; font-size:.92rem; line-height:1.6;">
            La marketplace freelance qui matche les bons talents avec les bons projets. Pensée à Esprit, déployée en Tunisie.
          </p>
        </div>
        <div class="col-lg-3 col-6">
          <h6>Plateforme</h6>
          <ul class="list-unstyled" style="line-height: 2;">
            <li><a href="#how-it-works">Méthode</a></li>
            <li><a href="#talents">Talents</a></li>
            <li><a href="#why">Plateforme</a></li>
          </ul>
        </div>
        <div class="col-lg-2 col-6">
          <h6>Compte</h6>
          <ul class="list-unstyled" style="line-height: 2;">
            <?php if ($isLoggedIn): ?>
              <li><a href="profil.php">Mon profil</a></li>
              <li><a href="../chat/conversations.php">Conversations</a></li>
              <li><a href="<?= $BASE ?>/controller/utilisateurcontroller.php?action=logout">Déconnexion</a></li>
            <?php else: ?>
              <li><a href="login.php">Connexion</a></li>
              <li><a href="register.php">Inscription</a></li>
              <li><a href="forgot-password.php">Mot de passe</a></li>
            <?php endif; ?>
          </ul>
        </div>
        <div class="col-lg-2">
          <h6>Contact</h6>
          <p style="color:rgba(255,255,255,.65); font-size:.9rem; margin-bottom:4px;">Esprit, Charguia 2</p>
          <p style="color:rgba(255,255,255,.65); font-size:.9rem; margin-bottom:0;">2035 Ariana, Tunisie</p>
        </div>
      </div>
      <hr style="border-color: rgba(255,255,255,.08); margin: 36px 0 20px;">
      <div class="d-flex justify-content-between flex-wrap gap-2 copy">
        <div>© <?= date('Y') ?> SkillBridge — Tous droits réservés.</div>
        <div>Built at Esprit · 2A 2025/2026</div>
      </div>
    </div>
  </footer>

  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script>
    if (typeof AOS !== 'undefined') {
      AOS.init({ duration: 600, easing: 'ease-out-cubic', once: true });
    }
  </script>
  <?php if ($isLoggedIn): ?>
  <script src="../../shared/chatbus.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (typeof ChatBus !== 'undefined') {
        ChatBus.init({ apiBase: '<?= $BASE ?>/api/chat.php', user: <?= (int)$userId ?>, conv: 0 });
        ChatBus.mountBell('#bellSlot');
      }
    });
  </script>
  <?php endif; ?>
</body>
</html>
