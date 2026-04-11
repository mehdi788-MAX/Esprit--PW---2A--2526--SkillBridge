<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controllers/DemandeController.php';
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/backoffice-topbar.php';
require_once __DIR__ . '/../components/imports.php';

$loadError = '';
$propositions = [];

try {
    $controller = new DemandeController();
    $propositions = $controller->getAllPropositionsWithDemande();
} catch (Throwable $e) {
    $loadError = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <?php echo head("Propositions", '../'); ?>
</head>

<body id="page-top">

<div id="wrapper">

    <?php echo sidebar("propositions-list"); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <?php echo topbar(); ?>

            <div class="container-fluid">

                <h1 class="mb-4">Liste des propositions</h1>

                <?php if ($loadError !== ''): ?>
                    <div class="alert alert-danger">
                        Impossible de charger les propositions. Vérifiez la base <code>skillbridge</code> et les tables
                        <code>demandes</code> / <code>propositions</code>.<br>
                        <small><?= htmlspecialchars($loadError) ?></small>
                    </div>
                <?php elseif (empty($propositions)): ?>
                    <div class="alert alert-warning">Aucune proposition enregistrée.</div>
                <?php else: ?>
                    <div class="card shadow mb-4">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="thead-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Demande</th>
                                        <th>Freelancer</th>
                                        <th>Message</th>
                                        <th>Prix</th>
                                        <th>Date</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($propositions as $p): ?>
                                        <tr>
                                            <td><?= (int) $p['id'] ?></td>
                                            <td><?= htmlspecialchars($p['demande_title'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($p['freelancer_name'] ?? '') ?></td>
                                            <td><?php
                                                $m = (string) ($p['message'] ?? '');
                                                echo htmlspecialchars(strlen($m) > 120 ? substr($m, 0, 120) . '…' : $m);
                                                ?></td>
                                            <td><?= htmlspecialchars((string) ($p['price'] ?? '')) ?> DT</td>
                                            <td><small><?= htmlspecialchars((string) ($p['created_at'] ?? '')) ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

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
