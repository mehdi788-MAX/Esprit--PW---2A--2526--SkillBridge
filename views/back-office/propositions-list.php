<?php
require_once __DIR__ . '/../../config.php';
ensure_session_started();
require_admin();
require_once __DIR__ . '/../../controllers/PropositionController.php';
require_once __DIR__ . '/../../models/PropositionModel.php';
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/backoffice-topbar.php';
require_once __DIR__ . '/../components/imports.php';

$controller = new PropositionController();
$loadError = '';
$flash = '';
$editingId = isset($_GET['edit']) && is_numeric($_GET['edit']) ? (int) $_GET['edit'] : null;
$demandeFilterId = isset($_GET['demande_id']) && is_numeric($_GET['demande_id']) ? (int) $_GET['demande_id'] : null;
$editingProposition = null;
$demandeOptions = [];
$sort = (isset($_GET['sort']) && $_GET['sort'] === 'oldest') ? 'oldest' : 'recent';
$search = trim((string) ($_GET['search'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save') {
            $proposition = new Proposition(
                null,
                (int) ($_POST['demande_id'] ?? 0),
                trim((string) ($_POST['freelancer_name'] ?? '')),
                trim((string) ($_POST['message'] ?? '')),
                (float) ($_POST['price'] ?? 0),
                date('Y-m-d H:i:s'),
                null
            );
            $controller->save($proposition);
            header('Location: propositions-list.php?success=created');
            exit;
        }

        if ($action === 'update' && isset($_POST['id']) && is_numeric($_POST['id'])) {
            $id = (int) $_POST['id'];
            $proposition = new Proposition(
                $id,
                (int) ($_POST['demande_id'] ?? 0),
                trim((string) ($_POST['freelancer_name'] ?? '')),
                trim((string) ($_POST['message'] ?? '')),
                (float) ($_POST['price'] ?? 0),
                null,
                null
            );
            $controller->update($id, $proposition);
            header('Location: propositions-list.php?success=updated');
            exit;
        }

        if ($action === 'delete' && isset($_POST['id']) && is_numeric($_POST['id'])) {
            $controller->delete((int) $_POST['id']);
            header('Location: propositions-list.php?success=deleted');
            exit;
        }
    } catch (Throwable $e) {
        $loadError = $e->getMessage();
    }
}

if (isset($_GET['success'])) {
    $success = (string) $_GET['success'];
    if ($success === 'created') {
        $flash = 'Proposition ajoutee avec succes.';
    } elseif ($success === 'updated') {
        $flash = 'Proposition modifiee avec succes.';
    } elseif ($success === 'deleted') {
        $flash = 'Proposition supprimee avec succes.';
    }
}

$propositions = [];
try {
    $propositions = $controller->getAll($sort, $search, $demandeFilterId);
    $demandeOptions = $controller->getDemandesOptions();
    if ($editingId !== null) {
        $editingObject = $controller->getById($editingId);
        $editingProposition = $editingObject ? $editingObject->toArray() : null;
    }
} catch (Throwable $e) {
    $loadError = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <?php echo head("Propositions", '../..'); ?>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php echo sidebar("propositions-list"); ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php echo topbar(); ?>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Gestion des propositions</h1>
                        <?php if ($demandeFilterId !== null): ?>
                            <a href="propositions-list.php" class="btn btn-secondary btn-sm">Voir toutes les propositions</a>
                        <?php endif; ?>
                    </div>

                    <?php if ($flash !== ''): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
                    <?php endif; ?>

                    <?php if ($demandeFilterId !== null): ?>
                        <div class="alert alert-info">
                            Consultation des propositions pour la demande #<?= (int) $demandeFilterId ?>.
                        </div>
                    <?php endif; ?>

                    <?php if ($loadError !== ''): ?>
                        <div class="alert alert-danger">
                            Impossible de traiter les propositions.<br>
                            <small><?= htmlspecialchars($loadError) ?></small>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <?= $editingProposition ? 'Modifier la proposition' : 'Ajouter une proposition' ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <input type="hidden" name="action" value="<?= $editingProposition ? 'update' : 'save' ?>">
                                        <?php if ($editingProposition): ?>
                                            <input type="hidden" name="id" value="<?= (int) $editingProposition['id'] ?>">
                                        <?php endif; ?>

                                        <div class="form-group">
                                            <label for="demande_id">Demande</label>
                                            <select class="form-control" id="demande_id" name="demande_id" required>
                                                <option value="">Choisir une demande</option>
                                                <?php foreach ($demandeOptions as $option): ?>
                                                    <option value="<?= (int) $option['id'] ?>" <?= (string) ($editingProposition['demande_id'] ?? '') === (string) $option['id'] ? 'selected' : '' ?>>
                                                        #<?= (int) $option['id'] ?> - <?= htmlspecialchars($option['title']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="freelancer_name">Freelancer</label>
                                            <input class="form-control" id="freelancer_name" name="freelancer_name" required value="<?= htmlspecialchars($editingProposition['freelancer_name'] ?? '') ?>">
                                        </div>

                                        <div class="form-group">
                                            <label for="price">Prix (DT)</label>
                                            <input class="form-control" id="price" name="price" type="number" min="1" step="0.01" required value="<?= htmlspecialchars((string) ($editingProposition['price'] ?? '')) ?>">
                                        </div>

                                        <div class="form-group">
                                            <label for="message">Message</label>
                                            <textarea class="form-control" id="message" name="message" rows="5" required><?= htmlspecialchars($editingProposition['message'] ?? '') ?></textarea>
                                        </div>

                                        <div class="d-flex">
                                            <button type="submit" class="btn btn-primary mr-2"><?= $editingProposition ? 'Enregistrer' : 'Ajouter' ?></button>
                                            <?php if ($editingProposition): ?>
                                                <a href="propositions-list.php" class="btn btn-secondary">Annuler</a>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Liste des propositions</h6>
                                </div>
                                <div class="card-body">
                                    <form method="get" class="mb-3">
                                        <?php if ($demandeFilterId !== null): ?>
                                            <input type="hidden" name="demande_id" value="<?= (int) $demandeFilterId ?>">
                                        <?php endif; ?>
                                        <div class="form-row align-items-end">
                                            <div class="col-md-5 mb-2">
                                                <label for="search" class="font-weight-bold">Recherche par titre</label>
                                                <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Titre de la demande">
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <label for="sort" class="font-weight-bold">Trier par date</label>
                                                <select class="form-control" id="sort" name="sort">
                                                    <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Plus recente</option>
                                                    <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Plus ancienne</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-2 d-flex">
                                                <button type="submit" class="btn btn-primary mr-2">Filtrer</button>
                                                <a href="<?= $demandeFilterId !== null ? 'propositions-list.php?demande_id=' . (int) $demandeFilterId : 'propositions-list.php' ?>" class="btn btn-outline-secondary">Reinitialiser</a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($propositions)): ?>
                                        <div class="p-4 text-muted">Aucune proposition enregistree.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>ID demande</th>
                                                        <th>Demande</th>
                                                        <th>Freelancer</th>
                                                        <th>Message</th>
                                                        <th>Prix</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($propositions as $p): ?>
                                                        <tr>
                                                            <td><?= (int) $p['id'] ?></td>
                                                            <td><?= (int) ($p['demande_id'] ?? 0) ?></td>
                                                            <td><?= htmlspecialchars($p['demande_title'] ?? '') ?></td>
                                                            <td><?= htmlspecialchars($p['freelancer_name'] ?? '') ?></td>
                                                            <td><?= htmlspecialchars(strlen((string) ($p['message'] ?? '')) > 90 ? substr((string) ($p['message'] ?? ''), 0, 90) . '...' : (string) ($p['message'] ?? '')) ?></td>
                                                            <td><?= htmlspecialchars((string) ($p['price'] ?? '')) ?> DT</td>
                                                            <td class="d-flex">
                                                                <a href="propositions-list.php?edit=<?= (int) $p['id'] ?>" class="btn btn-sm btn-info mr-2">Modifier</a>
                                                                <form method="post" onsubmit="return confirm('Supprimer cette proposition ?');">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                                                    <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                                                </form>
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

    <?php echo imports('../..'); ?>
</body>

</html>
