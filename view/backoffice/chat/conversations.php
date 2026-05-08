<?php
require_once __DIR__ . '/../../../controller/ChatController.php';

$chatController = new ChatController();

require_once __DIR__ . "/_auth.php";
$currentUserRole = 'freelancer';

// Traitement suppression
$successMsg = '';
$errorMsg = '';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $chatController->deleteConversation($id);
    if ($result['success']) {
        $successMsg = "Conversation supprimée avec succès.";
    } else {
        $errorMsg = implode('<br>', $result['errors']);
    }
}

// Récupérer les conversations du freelancer
$conversations = $chatController->listConversationsByUser($currentUserId)->fetchAll(PDO::FETCH_ASSOC);

$templateBase = '..';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>SkillBridge - Gestion des Conversations</title>
    <link href="<?= $templateBase ?>/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="<?= $templateBase ?>/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="<?= $templateBase ?>/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php $activePage = 'conversations'; include 'sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    <ul class="navbar-nav ml-auto align-items-center">
                        <li class="nav-item mr-2"><div id="bellSlot"></div></li>
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">Mohamed Ben Ali (Freelancer)</span>
                                <img class="img-profile rounded-circle" src="<?= $templateBase ?>/img/undraw_profile.svg">
                            </a>
                        </li>
                    </ul>
                </nav>

                <!-- Page Content -->
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Mes Conversations</h1>
                        <a href="add_conversation.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-plus fa-sm text-white-50"></i> Nouvelle Conversation
                        </a>
                    </div>

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
                            <h6 class="m-0 font-weight-bold text-primary">Liste des Conversations</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($conversations)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-comments fa-3x text-gray-300 mb-3"></i>
                                    <p class="text-gray-500">Aucune conversation pour le moment.</p>
                                    <a href="add_conversation.php" class="btn btn-primary">Démarrer une conversation</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Participant 1</th>
                                                <th>Participant 2</th>
                                                <th>Dernier Message</th>
                                                <th>Date de Création</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($conversations as $conv): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($conv['id_conversation']) ?></td>
                                                    <td><?= htmlspecialchars($conv['user1_prenom'] . ' ' . $conv['user1_nom']) ?></td>
                                                    <td><?= htmlspecialchars($conv['user2_prenom'] . ' ' . $conv['user2_nom']) ?></td>
                                                    <td>
                                                        <?php if (!empty($conv['dernier_message'])): ?>
                                                            <?= htmlspecialchars(substr($conv['dernier_message'], 0, 50)) ?>...
                                                        <?php else: ?>
                                                            <span class="text-muted">Aucun message</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($conv['date_creation']) ?></td>
                                                    <td>
                                                        <a href="chat.php?id=<?= $conv['id_conversation'] ?>" class="btn btn-info btn-sm" title="Ouvrir le chat">
                                                            <i class="fas fa-comments"></i>
                                                        </a>
                                                        <a href="edit_conversation.php?id=<?= $conv['id_conversation'] ?>" class="btn btn-warning btn-sm" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="conversations.php?action=delete&id=<?= $conv['id_conversation'] ?>" class="btn btn-danger btn-sm" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette conversation et tous ses messages ?');">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
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

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; SkillBridge <?= date('Y') ?></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <script src="<?= $templateBase ?>/vendor/jquery/jquery.min.js"></script>
    <script src="<?= $templateBase ?>/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $templateBase ?>/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="<?= $templateBase ?>/js/sb-admin-2.min.js"></script>
    <script src="<?= $templateBase ?>/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="<?= $templateBase ?>/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>$(document).ready(function() { $('#dataTable').DataTable(); });</script>
    <script src="../../shared/chatbus.js"></script>
    <script>
        ChatBus.init({ apiBase: '../../../api/chat.php', user: <?= (int)$currentUserId ?>, conv: 0 });
        ChatBus.mountBell('#bellSlot');
    </script>
</body>
</html>
