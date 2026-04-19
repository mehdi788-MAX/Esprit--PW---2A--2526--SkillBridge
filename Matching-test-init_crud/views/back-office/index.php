<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../controllers/DemandeController.php';
require_once __DIR__ . '/../../controllers/PropositionController.php';
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/backoffice-topbar.php';
require_once __DIR__ . '/../components/imports.php';

$dashboardError = '';
$recentDemandes = [];
$recentPropositions = [];

try {
    $demandeController = new DemandeController();
    $propositionController = new PropositionController();
    $recentDemandes = array_slice($demandeController->getAll(), 0, 5);
    $recentPropositions = array_slice($propositionController->getAll(), 0, 5);
} catch (Throwable $e) {
    $dashboardError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php echo head("Dashboard", '../'); ?>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php echo sidebar('dashboard'); ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php echo topbar(); ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Tableau de bord</h1>
                    <p class="mb-4">Accedez aux listes gerees par le back-office.</p>

                    <?php if ($dashboardError !== ''): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($dashboardError) ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-body">
                                    <h4 class="card-title">Liste des demandes</h4>
                                    <p class="card-text text-muted">Voir, ajouter, modifier et supprimer les demandes.</p>
                                    <a href="user-management.php" class="btn btn-primary">Ouvrir</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-body">
                                    <h4 class="card-title">Liste des propositions</h4>
                                    <p class="card-text text-muted">Voir, ajouter, modifier et supprimer les propositions.</p>
                                    <a href="propositions-list.php" class="btn btn-primary">Ouvrir</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Dernieres demandes</h6>
                                    <a href="user-management.php" class="btn btn-sm btn-primary">Gerer</a>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($recentDemandes)): ?>
                                        <div class="p-4 text-muted">Aucune demande pour le moment.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="thead-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Titre</th>
                                                    <th>Prix</th>
                                                    <th>Actions</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach ($recentDemandes as $demande): ?>
                                                    <tr>
                                                        <td><?= (int) $demande['id'] ?></td>
                                                        <td><?= htmlspecialchars($demande['title']) ?></td>
                                                        <td><?= htmlspecialchars((string) $demande['price']) ?> DT</td>
                                                        <td class="d-flex">
                                                            <a href="user-management.php?edit=<?= (int) $demande['id'] ?>" class="btn btn-sm btn-info mr-2">Modifier</a>
                                                            <a href="propositions-list.php?demande_id=<?= (int) $demande['id'] ?>" class="btn btn-sm btn-outline-primary">Voir propositions</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Dernieres propositions</h6>
                                    <a href="propositions-list.php" class="btn btn-sm btn-primary">Gerer</a>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($recentPropositions)): ?>
                                        <div class="p-4 text-muted">Aucune proposition pour le moment.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="thead-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Demande</th>
                                                    <th>Freelancer</th>
                                                    <th>Prix</th>
                                                    <th>Actions</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach ($recentPropositions as $proposition): ?>
                                                    <tr>
                                                        <td><?= (int) $proposition['id'] ?></td>
                                                        <td><?= htmlspecialchars($proposition['demande_title'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars($proposition['freelancer_name'] ?? '') ?></td>
                                                        <td><?= htmlspecialchars((string) ($proposition['price'] ?? '')) ?> DT</td>
                                                        <td>
                                                            <a href="propositions-list.php?edit=<?= (int) $proposition['id'] ?>" class="btn btn-sm btn-info">Modifier</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
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
