<?php
require_once __DIR__ . '/../../../controller/ChatController.php';

$chatController = new ChatController();

$currentUserId = 2;
$successMsg = '';
$errorMsg = '';

// Traitement suppression
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $chatController->deleteMessage($id);
    if ($result['success']) {
        $successMsg = "Message supprimé avec succès.";
    } else {
        $errorMsg = implode('<br>', $result['errors']);
    }
}

// Récupérer tous les messages
$messages = $chatController->listMessages()->fetchAll(PDO::FETCH_ASSOC);

$templateBase = '../startbootstrap-sb-admin-2-gh-pages';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>SkillBridge - Gestion des Messages</title>
    <link href="<?= $templateBase ?>/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="<?= $templateBase ?>/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="<?= $templateBase ?>/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
                <div class="sidebar-brand-text mx-3">Skill <sup>Bridge</sup></div>
            </a>
            <hr class="sidebar-divider my-0">
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Chat</div>
            <li class="nav-item">
                <a class="nav-link" href="conversations.php">
                    <i class="fas fa-fw fa-comments"></i>
                    <span>Conversations</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="add_conversation.php">
                    <i class="fas fa-fw fa-plus-circle"></i>
                    <span>Nouvelle Conversation</span>
                </a>
            </li>
            <li class="nav-item active">
                <a class="nav-link" href="messages.php">
                    <i class="fas fa-fw fa-envelope"></i>
                    <span>Tous les Messages</span>
                </a>
            </li>
            <hr class="sidebar-divider d-none d-md-block">
        </ul>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">Mohamed Ben Ali (Freelancer)</span>
                                <img class="img-profile rounded-circle" src="<?= $templateBase ?>/img/undraw_profile.svg">
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Tous les Messages</h1>

                    <?php if ($successMsg): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $successMsg ?>
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($errorMsg): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $errorMsg ?>
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Liste des Messages</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Conversation</th>
                                            <th>Expéditeur</th>
                                            <th>Contenu</th>
                                            <th>Date</th>
                                            <th>Lu</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($messages as $msg): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($msg['id_message']) ?></td>
                                                <td>
                                                    <a href="chat.php?id=<?= htmlspecialchars($msg['id_conversation']) ?>">
                                                        #<?= htmlspecialchars($msg['id_conversation']) ?>
                                                    </a>
                                                </td>
                                                <td><?= htmlspecialchars($msg['sender_prenom'] . ' ' . $msg['sender_nom']) ?></td>
                                                <td><?= htmlspecialchars(substr($msg['contenu'], 0, 80)) ?><?= strlen($msg['contenu']) > 80 ? '...' : '' ?></td>
                                                <td><?= htmlspecialchars($msg['date_envoi']) ?></td>
                                                <td>
                                                    <?php if ($msg['is_seen']): ?>
                                                        <span class="badge badge-success">Lu</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Non lu</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="chat.php?id=<?= $msg['id_conversation'] ?>" class="btn btn-info btn-sm" title="Voir dans le chat">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="messages.php?action=delete&id=<?= $msg['id_message'] ?>" class="btn btn-danger btn-sm" title="Supprimer" onclick="return confirm('Supprimer ce message ?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; SkillBridge <?= date('Y') ?></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="<?= $templateBase ?>/vendor/jquery/jquery.min.js"></script>
    <script src="<?= $templateBase ?>/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $templateBase ?>/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="<?= $templateBase ?>/js/sb-admin-2.min.js"></script>
    <script src="<?= $templateBase ?>/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="<?= $templateBase ?>/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>$(document).ready(function() { $('#dataTable').DataTable(); });</script>
</body>
</html>
