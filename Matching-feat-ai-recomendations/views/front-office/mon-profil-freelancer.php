<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

ensure_session_started();
require_freelancer();

$error = '';
$success = '';

$defaults = [
    'competences' => '',
    'bio' => '',
];

try {
    $pdo = db_connect();

    $stmt = $pdo->prepare('SELECT competences, bio FROM profils WHERE utilisateur_id = :uid ORDER BY id ASC LIMIT 1');
    $stmt->execute([':uid' => current_user_id()]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (is_array($row)) {
        $defaults['competences'] = (string) ($row['competences'] ?? '');
        $defaults['bio'] = (string) ($row['bio'] ?? '');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $competences = trim((string) ($_POST['competences'] ?? ''));
        $bio = trim((string) ($_POST['bio'] ?? ''));

        if (mb_strlen($competences) > 4000 || mb_strlen($bio) > 8000) {
            $error = 'Texte trop long (competences 4000 caracteres max, bio 8000 max).';
        } else {
            $uid = current_user_id();
            $check = $pdo->prepare('SELECT id FROM profils WHERE utilisateur_id = :uid ORDER BY id ASC LIMIT 1');
            $check->execute([':uid' => $uid]);
            $pid = $check->fetchColumn();
            if ($pid !== false && (int) $pid > 0) {
                $upd = $pdo->prepare('UPDATE profils SET competences = :c, bio = :b WHERE id = :id AND utilisateur_id = :uid');
                $upd->execute([
                    ':c' => $competences,
                    ':b' => $bio,
                    ':id' => (int) $pid,
                    ':uid' => $uid,
                ]);
            } else {
                $ins = $pdo->prepare('INSERT INTO profils (utilisateur_id, bio, competences) VALUES (:uid, :b, :c)');
                $ins->execute([
                    ':uid' => $uid,
                    ':b' => $bio,
                    ':c' => $competences,
                ]);
            }
            $defaults['competences'] = $competences;
            $defaults['bio'] = $bio;
            $success = 'Profil enregistre. Les recommandations de demandes utilisent ces champs.';
        }
    }
} catch (PDOException $e) {
    $error = db_error_message($e);
}

?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Mon profil – SkillBridge</title>
  <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f9f9f9; font-family: 'Roboto', sans-serif; }
    header { background: #fff; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; z-index: 999; }
    .logo { font-weight: 700; font-size: 1.5rem; color: #ff6600; text-decoration: none; }
    nav ul { list-style: none; margin: 0; padding: 0; display: flex; gap: 1rem; flex-wrap: wrap; }
    nav ul li a { color: #1a1a2e; text-decoration: none; font-weight: 500; padding: 0.5rem 1rem; }
    nav ul li a.active { color: #ff6600; }
    .page-header { background: #fff8f0; padding: 28px 0; border-bottom: 1px solid #ffe0cc; }
    .card-profil { background: #fff; border-radius: 12px; padding: 24px; max-width: 720px; margin: 24px auto; box-shadow: 0 8px 24px rgba(0,0,0,.06); }
    label { font-weight: 600; color: #ff6600; margin-bottom: 6px; display: block; }
    textarea { width: 100%; border: 1px solid #ffb366; border-radius: 8px; padding: 12px; min-height: 120px; }
    .hint { font-size: 0.9rem; color: #555; margin-top: 6px; }
    .btn-save { background: #ff6600; color: #fff; border: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; }
    .alert-ok { background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 16px; }
    .alert-err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 16px; }
  </style>
</head>

<body>

  <header class="d-flex align-items-center justify-content-between px-4 py-3">
    <a href="<?= front_url('index.php') ?>" class="logo">SkillBridge</a>
    <nav>
      <ul class="d-flex align-items-center">
        <li><a href="<?= front_url('index.php') ?>">Accueil</a></li>
        <li><a href="<?= front_url('mes-demandes.php') ?>"><?= front_demands_label() ?></a></li>
        <li><a href="<?= front_url('mes-propositions.php') ?>">Mes propositions</a></li>
        <li><a href="<?= front_url('addprop-form.php') ?>">Soumettre une offre</a></li>
        <li><a href="<?= front_url('mon-profil-freelancer.php') ?>" class="active">Mon profil</a></li>
      </ul>
    </nav>
  </header>

  <div class="page-header container">
    <h1 class="h3 mb-0">Mon profil freelancer</h1>
    <p class="text-muted mb-0 mt-2">Ces informations sont enregistrees en base (table <code>profils</code>) pour les recommandations et l aide a la redaction des propositions.</p>
  </div>

  <main class="container pb-5">
    <div class="card-profil">
      <?php if ($success): ?>
        <div class="alert-ok"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert-err"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= front_url('mon-profil-freelancer.php') ?>">
        <div class="mb-4">
          <label for="competences">Competences (mots-cles visibles par le matching)</label>
          <textarea id="competences" name="competences" rows="4" placeholder="Ex : logo, charte graphique, WordPress, SEO, montage video, PHP..."><?= htmlspecialchars($defaults['competences']) ?></textarea>
          <p class="hint">Utilisez les memes termes que dans les titres ou descriptions des demandes clients (ex. wordpress, logo, traduction) pour activer les « Demandes recommandees » sur la page Demandes disponibles.</p>
        </div>
        <div class="mb-4">
          <label for="bio">Bio</label>
          <textarea id="bio" name="bio" rows="6" placeholder="Votre experience, types de missions, outils..."><?= htmlspecialchars($defaults['bio']) ?></textarea>
        </div>
        <button type="submit" class="btn-save">Enregistrer</button>
      </form>
    </div>
  </main>

</body>

</html>
