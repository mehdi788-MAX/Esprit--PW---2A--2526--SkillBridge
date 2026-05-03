<?php
require_once __DIR__ . '/../../config.php';
ensure_session_started();
require_admin();
require_once __DIR__ . '/../../controllers/DemandeController.php';
require_once __DIR__ . '/../../controllers/PropositionController.php';
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/backoffice-topbar.php';
require_once __DIR__ . '/../components/imports.php';

$dashboardError = '';
$recentDemandes = [];
$recentPropositions = [];
$propositionDistribution = [
    'zero_proposition' => 0,
    'one_proposition' => 0,
    'many_propositions' => 0,
];

try {
    $demandeController = new DemandeController();
    $propositionController = new PropositionController();
    $recentDemandes = array_slice($demandeController->getAll(), 0, 5);
    $recentPropositions = array_slice($propositionController->getAll(), 0, 5);
    $propositionDistribution = $demandeController->getPropositionDistribution();
} catch (Throwable $e) {
    $dashboardError = $e->getMessage();
}

$distributionLabels = [
    'Demandes sans proposition',
    'Demandes avec une proposition',
    'Demandes avec plusieurs propositions',
];
$distributionValues = [
    $propositionDistribution['zero_proposition'],
    $propositionDistribution['one_proposition'],
    $propositionDistribution['many_propositions'],
];
$totalDemandesForDistribution = array_sum($distributionValues);
$distributionPercentages = array_map(function ($value) use ($totalDemandesForDistribution) {
    if ($totalDemandesForDistribution === 0) {
        return 0;
    }

    return round(($value / $totalDemandesForDistribution) * 100, 1);
}, $distributionValues);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php echo head("Dashboard", '../..'); ?>
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
                        <div class="col-xl-7 col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Repartition des demandes par propositions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-pie pt-4 pb-2" style="height: 320px;">
                                        <canvas id="myPieChart"></canvas>
                                    </div>
                                    <div class="mt-4 text-center small">
                                        <span class="mr-3">
                                            <i class="fas fa-circle text-primary"></i> Sans proposition (<?= (int) $propositionDistribution['zero_proposition'] ?> - <?= $distributionPercentages[0] ?>%)
                                        </span>
                                        <span class="mr-3">
                                            <i class="fas fa-circle text-success"></i> Une proposition (<?= (int) $propositionDistribution['one_proposition'] ?> - <?= $distributionPercentages[1] ?>%)
                                        </span>
                                        <span class="mr-3">
                                            <i class="fas fa-circle text-info"></i> Plusieurs propositions (<?= (int) $propositionDistribution['many_propositions'] ?> - <?= $distributionPercentages[2] ?>%)
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-5 col-lg-6 mb-4">
                            <div class="card shadow h-100">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Resume</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Demandes sans proposition</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= (int) $propositionDistribution['zero_proposition'] ?> <span class="text-muted" style="font-size: 0.95rem;">(<?= $distributionPercentages[0] ?>%)</span></div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Demandes avec une proposition</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= (int) $propositionDistribution['one_proposition'] ?> <span class="text-muted" style="font-size: 0.95rem;">(<?= $distributionPercentages[1] ?>%)</span></div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Demandes avec plusieurs propositions</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= (int) $propositionDistribution['many_propositions'] ?> <span class="text-muted" style="font-size: 0.95rem;">(<?= $distributionPercentages[2] ?>%)</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

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
    <script>
        window.dashboardPieConfig = {
            labels: <?= json_encode($distributionLabels, JSON_UNESCAPED_UNICODE) ?>,
            data: <?= json_encode($distributionValues) ?>,
            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
            hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf'],
            cutoutPercentage: 65
        };
    </script>
    <?php echo imports('../..'); ?>
</body>

</html>
