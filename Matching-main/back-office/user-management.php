<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controllers/DemandeController.php';
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/backoffice-topbar.php';
require_once __DIR__ . '/../components/imports.php';

$loadError = '';
$demandes = [];

try {
    $controller = new DemandeController();
    $demandes = $controller->getAllDemandes();
} catch (Throwable $e) {
    $loadError = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <?php echo head("Demandes", '../'); ?>
</head>

<body id="page-top">

<div id="wrapper">

    <?php echo sidebar("user-management"); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <?php echo topbar(); ?>

            <div class="container-fluid">

                <h1 class="mb-4">Liste des demandes</h1>

                <?php if ($loadError !== ''): ?>
                    <div class="alert alert-danger">
                        Impossible de charger les demandes. Vérifiez que la base <code>skillbridge</code> existe et que la table
                        <code>demandes</code> est créée.<br>
                        <small><?= htmlspecialchars($loadError) ?></small>
                    </div>
                <?php elseif (empty($demandes)): ?>
                    <div class="alert alert-warning">
                        Aucune demande trouvée.
                    </div>
                <?php endif; ?>

                <?php foreach ($demandes as $d): ?>
                    <div class="card shadow mb-4">
                        <div class="card-body">

                            <h4><?= htmlspecialchars($d['title']) ?></h4>
                            <p><?= htmlspecialchars($d['description']) ?></p>

                            <div class="mb-2">
                                <strong>Prix :</strong> <?= $d['price'] ?> DT
                            </div>

                            <div class="mb-3">
                                <small class="text-muted">
                                    Publié le <?= $d['created_at'] ?>
                                </small>
                            </div>

                            <hr>

                            <h5>Propositions :</h5>

                            <?php
                            $propositions = [];
                            if ($loadError === '' && isset($controller)) {
                                $propositions = $controller->getPropositionsByDemande($d['id']);
                            }
                            ?>

                            <?php if (empty($propositions)): ?>
                                <p class="text-muted">Aucune proposition</p>
                            <?php else: ?>
                                <?php foreach ($propositions as $p): ?>
                                    <div class="border rounded p-3 mb-2">

                                        <strong><?= htmlspecialchars($p['freelancer_name']) ?></strong>

                                        <p class="mb-1">
                                            <?= htmlspecialchars($p['message']) ?>
                                        </p>

                                        <span class="badge bg-warning text-dark">
                                            <?= $p['price'] ?> DT
                                        </span>

                                        <div>
                                            <small class="text-muted">
                                                Envoyé le <?= $p['created_at'] ?>
                                            </small>
                                        </div>

                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<?php echo imports('../'); ?>

</body>
</html>