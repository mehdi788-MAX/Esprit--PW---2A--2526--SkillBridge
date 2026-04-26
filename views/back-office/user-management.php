<?php
require_once __DIR__ . '/../../config.php';
ensure_session_started();
require_admin();
require_once __DIR__ . '/../../controllers/DemandeController.php';
require_once __DIR__ . '/../../models/DemandeModel.php';
require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../components/backoffice-topbar.php';
require_once __DIR__ . '/../components/imports.php';

$controller = new DemandeController();
$loadError = '';
$flash = '';
$editingId = isset($_GET['edit']) && is_numeric($_GET['edit']) ? (int) $_GET['edit'] : null;
$editingDemande = null;
$sort = (isset($_GET['sort']) && $_GET['sort'] === 'oldest') ? 'oldest' : 'recent';
$search = trim((string) ($_GET['search'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save') {
            $demande = new Demande(
                null,
                trim((string) ($_POST['title'] ?? '')),
                (float) ($_POST['price'] ?? 0),
                trim((string) ($_POST['deadline'] ?? '')),
                trim((string) ($_POST['description'] ?? '')),
                date('Y-m-d H:i:s'),
                null
            );
            $controller->save($demande);
            header('Location: user-management.php?success=created');
            exit;
        }

        if ($action === 'update' && isset($_POST['id']) && is_numeric($_POST['id'])) {
            $id = (int) $_POST['id'];
            $demande = new Demande(
                $id,
                trim((string) ($_POST['title'] ?? '')),
                (float) ($_POST['price'] ?? 0),
                trim((string) ($_POST['deadline'] ?? '')),
                trim((string) ($_POST['description'] ?? '')),
                null,
                null
            );
            $controller->update($id, $demande);
            header('Location: user-management.php?success=updated');
            exit;
        }

        if ($action === 'delete' && isset($_POST['id']) && is_numeric($_POST['id'])) {
            $controller->delete((int) $_POST['id']);
            header('Location: user-management.php?success=deleted');
            exit;
        }
    } catch (Throwable $e) {
        $loadError = $e->getMessage();
    }
}

if (isset($_GET['success'])) {
    $success = (string) $_GET['success'];
    if ($success === 'created') {
        $flash = 'Demande ajoutee avec succes.';
    } elseif ($success === 'updated') {
        $flash = 'Demande modifiee avec succes.';
    } elseif ($success === 'deleted') {
        $flash = 'Demande supprimee avec succes.';
    }
}

$demandes = [];
try {
    $demandes = $controller->getAll($sort, $search);
    if ($editingId !== null) {
        $editingDemande = $controller->getById($editingId);
    }
} catch (Throwable $e) {
    $loadError = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <?php echo head("Demandes", '../..'); ?>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php echo sidebar("user-management"); ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php echo topbar(); ?>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Gestion des demandes</h1>
                    </div>

                    <?php if ($flash !== ''): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
                    <?php endif; ?>

                    <?php if ($loadError !== ''): ?>
                        <div class="alert alert-danger">
                            Impossible de traiter les demandes.<br>
                            <small><?= htmlspecialchars($loadError) ?></small>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-4 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <?= $editingDemande ? 'Modifier la demande' : 'Ajouter une demande' ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <input type="hidden" name="action" value="<?= $editingDemande ? 'update' : 'save' ?>">
                                        <?php if ($editingDemande): ?>
                                            <input type="hidden" name="id" value="<?= (int) $editingDemande['id'] ?>">
                                        <?php endif; ?>

                                        <div class="form-group">
                                            <label for="title">Titre</label>
                                            <input class="form-control" id="title" name="title" required value="<?= htmlspecialchars($editingDemande['title'] ?? '') ?>">
                                        </div>

                                        <div class="form-group">
                                            <label for="price">Prix (DT)</label>
                                            <input class="form-control" id="price" name="price" type="number" min="1" step="0.01" required value="<?= htmlspecialchars((string) ($editingDemande['price'] ?? '')) ?>">
                                        </div>

                                        <div class="form-group">
                                            <label for="deadline">Date limite</label>
                                            <input class="form-control" id="deadline" name="deadline" type="date" required value="<?= htmlspecialchars($editingDemande['deadline'] ?? '') ?>">
                                        </div>

                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea class="form-control" id="description" name="description" rows="5" required><?= htmlspecialchars($editingDemande['description'] ?? '') ?></textarea>
                                        </div>

                                        <div class="d-flex">
                                            <button type="submit" class="btn btn-primary mr-2"><?= $editingDemande ? 'Enregistrer' : 'Ajouter' ?></button>
                                            <?php if ($editingDemande): ?>
                                                <a href="user-management.php" class="btn btn-secondary">Annuler</a>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Liste des demandes</h6>
                                </div>
                                <div class="card-body">
                                    <form method="get" class="mb-3">
                                        <div class="form-row align-items-end">
                                            <div class="col-md-5 mb-2">
                                                <label for="search" class="font-weight-bold">Recherche par titre</label>
                                                <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Ex: Application mobile">
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
                                                <a href="user-management.php" class="btn btn-outline-secondary">Reinitialiser</a>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($demandes)): ?>
                                        <div class="p-4 text-muted">Aucune demande trouvee.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Titre</th>
                                                        <th>Prix</th>
                                                        <th>Date limite</th>
                                                        <th>Description</th>
                                                        <th>Propositions</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($demandes as $d): ?>
                                                        <tr>
                                                            <td><?= (int) $d['id'] ?></td>
                                                            <td><?= htmlspecialchars($d['title']) ?></td>
                                                            <td><?= htmlspecialchars((string) $d['price']) ?> DT</td>
                                                            <td><?= htmlspecialchars((string) $d['deadline']) ?></td>
                                                            <td><?= htmlspecialchars(strlen((string) $d['description']) > 80 ? substr((string) $d['description'], 0, 80) . '...' : (string) $d['description']) ?></td>
                                                            <td>
                                                                <a href="propositions-list.php?demande_id=<?= (int) $d['id'] ?>" class="btn btn-sm btn-outline-primary">Voir propositions</a>
                                                            </td>
                                                            <td class="d-flex">
                                                                <a href="user-management.php?edit=<?= (int) $d['id'] ?>" class="btn btn-sm btn-info mr-2">Modifier</a>
                                                                <form method="post" onsubmit="return confirm('Supprimer cette demande ?');">
                                                                    <input type="hidden" name="action" value="delete">
                                                                    <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
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
