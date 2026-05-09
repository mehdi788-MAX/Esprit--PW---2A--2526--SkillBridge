<?php
session_start();
require_once __DIR__ . '/../../config.php';

// Already logged in as admin? Skip straight to dashboard.
if (isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'admin') {
    header('Location: ' . backoffice_url() . '/dashbord.php');
    exit;
}

$error  = $_SESSION['error']  ?? null;
$notice = $_SESSION['notice'] ?? null;
unset($_SESSION['error'], $_SESSION['notice']);

$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email   = trim((string)($_POST['email']    ?? ''));
    $password =       (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = "Email et mot de passe sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email invalide.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, nom, prenom, email, password, role, is_active FROM utilisateurs WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                $error = "Identifiants invalides.";
            } elseif ((string)$user['role'] !== 'admin') {
                $error = "Cet espace est réservé aux administrateurs. Connectez-vous au site principal pour accéder à votre compte.";
            } elseif ((int)$user['is_active'] !== 1) {
                $error = "Votre compte administrateur est désactivé.";
            } else {
                // OK — authenticate
                $_SESSION['user_id']    = (int)$user['id'];
                $_SESSION['user_nom']   = trim($user['prenom'] . ' ' . $user['nom']);
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role']  = 'admin';
                header('Location: ' . backoffice_url() . '/dashbord.php');
                exit;
            }
        } catch (Throwable $e) {
            $error = "Erreur d'authentification : " . $e->getMessage();
        }
    }
}

$LOGO_SRC = frontoffice_url() . '/assets/img/skillbridge-logo.png';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Connexion administrateur — SkillBridge</title>
  <link href="<?= htmlspecialchars($LOGO_SRC) ?>" rel="icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    :root {
      --bg:#F7F4ED; --paper:#FFFFFF; --ink:#0F0F0F; --ink-2:#2A2A2A;
      --ink-mute:#5C5C5C; --ink-soft:#A3A3A3; --rule:#E8E2D5;
      --sage:#1F5F4D; --sage-d:#134438; --sage-soft:#E8F0EC;
      --honey:#F5C842; --honey-d:#E0B033; --honey-soft:#FBF1D0;
      --danger:#DC2626; --danger-soft:#FEF2F2;
    }
    *, *::before, *::after { box-sizing: border-box; }
    body {
      font-family:'Manrope', system-ui, -apple-system, sans-serif;
      background: var(--bg); color: var(--ink); letter-spacing:-.005em;
      -webkit-font-smoothing:antialiased; margin:0; min-height:100vh;
      display: flex; flex-direction: column;
    }
    ::selection { background: var(--sage); color: var(--honey); }

    h1, h2, h3 { font-family:'Manrope', sans-serif; font-weight:700; letter-spacing:-.022em; color: var(--ink); }
    .display-x { font-size: clamp(1.9rem, 3.4vw, 2.6rem); line-height:1.05; font-weight:800; letter-spacing:-.025em; }
    .accent    { font-style: italic; font-weight:700; color: var(--sage); }

    .top-strip {
      background: rgba(247,244,237,.85); backdrop-filter: blur(14px);
      border-bottom: 1px solid var(--rule);
      padding: 14px 26px;
      display: flex; align-items: center; justify-content: space-between;
    }
    .top-strip .brand { display: inline-flex; align-items: center; text-decoration: none; }
    .top-strip .brand img { height: 38px; width: auto; display: block; }
    .top-strip .back { color: var(--ink-mute); text-decoration: none; font-size: .9rem; font-weight: 500; transition: color .15s; display:inline-flex; align-items:center; gap:6px; }
    .top-strip .back:hover { color: var(--sage); }

    .auth-canvas {
      flex: 1; position: relative; display: flex; align-items: center; justify-content: center;
      padding: 60px 20px; overflow: hidden;
    }
    .blob { position: absolute; border-radius: 50%; filter: blur(70px); opacity: .55; pointer-events: none; z-index: 0; }
    .blob.sage  { background: var(--sage-soft); width: 420px; height: 420px; left: -120px; top: -100px; }
    .blob.honey { background: var(--honey-soft); width: 360px; height: 360px; right: -100px; bottom: -80px; }

    .auth-card {
      background: var(--paper); border: 1px solid var(--rule); border-radius: 24px;
      padding: 40px 38px;
      box-shadow: 0 30px 60px -25px rgba(31,95,77,.22);
      width: 100%; max-width: 460px; position: relative; z-index: 1;
    }
    .auth-card .lock {
      width: 56px; height: 56px; border-radius: 16px;
      background: var(--ink); color: var(--honey);
      display: inline-flex; align-items: center; justify-content: center;
      font-size: 1.6rem; margin-bottom: 18px;
    }

    .eyebrow {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 5px 12px; border-radius: 999px;
      background: var(--ink); color: var(--honey);
      font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
      margin-bottom: 4px;
    }
    .eyebrow .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--honey); }

    .auth-card .lead-x { font-size: .94rem; line-height: 1.55; color: var(--ink-mute); margin: 14px 0 28px; }

    .form-label { display: block; font-weight: 600; color: var(--ink-2); font-size: .87rem; margin-bottom: 6px; }
    .form-control {
      width: 100%; border-radius: 12px; padding: 13px 14px; font-size: .96rem;
      border: 1px solid var(--rule); background: var(--paper); color: var(--ink);
      transition: border-color .18s, box-shadow .18s;
      font-family:'Manrope', sans-serif;
    }
    .form-control:focus { outline: none; border-color: var(--sage); box-shadow: 0 0 0 4px rgba(31,95,77,.12); }
    .form-control.is-invalid { border-color: var(--danger); }

    .pwd-wrap { position: relative; }
    .pwd-wrap .form-control { padding-right: 48px; }
    .pwd-toggle {
      position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
      width: 36px; height: 36px; border-radius: 9px; border: none;
      background: transparent; color: var(--ink-mute);
      display: inline-flex; align-items: center; justify-content: center;
      cursor: pointer; transition: all .15s ease; padding: 0;
    }
    .pwd-toggle:hover { background: var(--bg); color: var(--ink); }

    .btn-sage {
      display: flex; align-items: center; justify-content: center; gap: 10px;
      width: 100%; padding: 14px 22px; border-radius: 12px; border: none;
      background: var(--sage); color: var(--paper);
      font-weight: 700; font-size: 1rem; cursor: pointer;
      transition: all .2s ease;
      font-family:'Manrope', sans-serif;
    }
    .btn-sage:hover { background: var(--sage-d); transform: translateY(-2px); box-shadow: 0 14px 28px -12px rgba(31,95,77,.4); }

    .ad-alert { border-radius: 12px; padding: 12px 14px; border: 1px solid; margin-bottom: 18px; display: flex; align-items: flex-start; gap: 10px; font-size: .9rem; }
    .ad-alert.danger { background: var(--danger-soft); border-color: #FECACA; color: #991B1B; }
    .ad-alert.success { background: var(--sage-soft); border-color: rgba(31,95,77,.2); color: var(--sage-d); }

    .restricted-note {
      margin-top: 22px; padding-top: 22px; border-top: 1px solid var(--rule);
      display: flex; align-items: flex-start; gap: 10px;
      font-size: .82rem; color: var(--ink-mute);
    }
    .restricted-note i { color: var(--honey-d); font-size: 1rem; flex-shrink: 0; margin-top: 2px; }

    .footer-strip {
      padding: 18px 26px; border-top: 1px solid var(--rule);
      color: rgba(15,15,15,.5); font-size: .82rem; text-align: center;
    }
    .footer-strip strong { color: var(--ink); }
  </style>
</head>
<body>

<div class="top-strip">
  <a href="<?= backoffice_url() ?>/login.php" class="brand">
    <img src="<?= htmlspecialchars($LOGO_SRC) ?>" alt="SkillBridge">
  </a>
  <a href="<?= frontoffice_url() ?>/index.php" class="back">
    <i class="bi bi-arrow-left"></i> Retour au site
  </a>
</div>

<section class="auth-canvas">
  <div class="blob sage"></div>
  <div class="blob honey"></div>

  <div class="auth-card">
    <div class="lock"><i class="bi bi-shield-lock-fill"></i></div>
    <span class="eyebrow"><span class="dot"></span> Backoffice</span>
    <h1 class="display-x mt-3 mb-2">Connexion <span class="accent">administrateur</span>.</h1>
    <p class="lead-x">Espace réservé aux administrateurs SkillBridge — gestion des utilisateurs, modération du chat, supervision de la plateforme.</p>

    <?php if ($error): ?>
      <div class="ad-alert danger">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>
    <?php if ($notice): ?>
      <div class="ad-alert success">
        <i class="bi bi-info-circle-fill"></i>
        <span><?= htmlspecialchars($notice) ?></span>
      </div>
    <?php endif; ?>

    <form id="adminLoginForm" action="<?= backoffice_url() ?>/login.php" method="POST" novalidate>
      <div class="mb-3">
        <label for="email" class="form-label">Adresse email</label>
        <input type="email" name="email" id="email" class="form-control" placeholder="admin@skillbridge.com" value="<?= htmlspecialchars($email) ?>" autocomplete="username">
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Mot de passe</label>
        <div class="pwd-wrap">
          <input type="password" name="password" id="password" class="form-control" placeholder="Votre mot de passe" autocomplete="current-password">
          <button type="button" class="pwd-toggle" id="togglePassword" aria-label="Afficher le mot de passe">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-sage">
        <i class="bi bi-shield-lock-fill"></i> Accéder au backoffice
      </button>
    </form>

    <div class="restricted-note">
      <i class="bi bi-info-circle-fill"></i>
      <span>Cet espace est strictement réservé aux administrateurs. Les comptes <strong>client</strong> et <strong>freelancer</strong> doivent se connecter sur le <a href="<?= frontoffice_url() ?>/login.php" style="color:var(--sage); text-decoration:underline;">site principal</a>.</span>
    </div>
  </div>
</section>

<footer class="footer-strip">
  © <?= date('Y') ?> <strong>SkillBridge</strong> — Espace administrateur.
</footer>

<script>
  document.getElementById('togglePassword').addEventListener('click', function () {
    const p = document.getElementById('password');
    const i = document.getElementById('eyeIcon');
    p.type = (p.type === 'password') ? 'text' : 'password';
    i.classList.toggle('bi-eye');
    i.classList.toggle('bi-eye-slash');
  });

  document.getElementById('adminLoginForm').addEventListener('submit', function (e) {
    const email = document.getElementById('email');
    const pwd   = document.getElementById('password');
    email.classList.remove('is-invalid');
    pwd.classList.remove('is-invalid');
    let valid = true;
    if (!email.value.trim()) { email.classList.add('is-invalid'); valid = false; }
    if (!pwd.value)          { pwd.classList.add('is-invalid');   valid = false; }
    if (!valid) e.preventDefault();
  });
</script>

</body>
</html>
