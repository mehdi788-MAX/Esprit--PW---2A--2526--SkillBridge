<?php
$db_host = 'localhost';
$db_name = 'skillbridge';
$db_user = 'root';
$db_pass = '';

$propositions = [];
$error = '';

if (!isset($_GET['demande_id'])) {
    die("Demande non trouvée.");
}

$demande_id = (int) $_GET['demande_id'];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // récupérer la demande
    $stmt = $pdo->prepare("SELECT * FROM demandes WHERE id = ?");
    $stmt->execute([$demande_id]);
    $demande = $stmt->fetch();

    // récupérer les propositions
    $stmt = $pdo->prepare("SELECT * FROM propositions WHERE demande_id = ? ORDER BY created_at DESC");
    $stmt->execute([$demande_id]);
    $propositions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Propositions</title>
<link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

<style>
body { background:#f9f9f9; font-family:Roboto; }

.header { background:#fff; padding:15px; border-bottom:1px solid #e5e7eb; }
.logo { color:#ff6600; font-weight:bold; font-size:1.5rem; text-decoration:none; }

.container-main { padding:40px 0; }

.card-demande {
    background:#fff8f0;
    border:1px solid #ffe0cc;
    border-radius:12px;
    padding:20px;
    margin-bottom:30px;
}

.proposition-card {
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:14px;
    padding:20px;
    margin-bottom:15px;
    transition:0.3s;
}
.proposition-card:hover {
    border-color:#ff6600;
    box-shadow:0 8px 25px rgba(255,102,0,0.1);
}

.price-badge {
    background:#fff3eb;
    border:1px solid #ffb366;
    border-radius:20px;
    padding:5px 15px;
    color:#cc5200;
    font-weight:bold;
}

.empty-state {
    text-align:center;
    padding:50px;
    background:#fff;
    border:2px dashed #ffb366;
    border-radius:12px;
}

.btn-back {
    background:#ff6600;
    color:#fff;
    padding:10px 20px;
    border-radius:8px;
    text-decoration:none;
}
</style>
</head>

<body>

<div class="header d-flex justify-content-between">
  <a href="mes-demandes.php" class="logo">SkillBridge</a>
  <a href="mes-demandes.php" class="btn-back">← Retour</a>
</div>

<div class="container container-main">

<?php if ($error): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<?php if ($demande): ?>
<div class="card-demande">
    <h4><?= htmlspecialchars($demande['title']) ?></h4>
    <p><?= htmlspecialchars($demande['description']) ?></p>
</div>
<?php endif; ?>

<h5 class="mb-4">Propositions reçues</h5>

<?php if (empty($propositions)): ?>
<div class="empty-state">
    <i class="bi bi-inbox" style="font-size:40px;color:#ffb366;"></i>
    <p>Aucune proposition pour cette demande.</p>
</div>
<?php else: ?>

<?php foreach ($propositions as $p): ?>
<div class="proposition-card">
    <div class="d-flex justify-content-between">
        <strong><?= htmlspecialchars($p['freelancer_name']) ?></strong>
        <span class="price-badge"><?= $p['price'] ?> DT</span>
    </div>

    <p class="mt-2"><?= htmlspecialchars($p['message']) ?></p>

    <small class="text-muted">
        Envoyé le <?= date('d/m/Y', strtotime($p['created_at'])) ?>
    </small>
</div>
<?php endforeach; ?>

<?php endif; ?>

</div>

</body>
</html>