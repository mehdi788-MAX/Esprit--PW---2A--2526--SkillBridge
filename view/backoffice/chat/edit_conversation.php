<?php
require_once __DIR__ . '/../../../controller/ChatController.php';

$chatController = new ChatController();

$currentUserId = 2;
$errors = [];
$successMsg = '';

// Récupérer l'ID de la conversation
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: conversations.php');
    exit;
}

// Récupérer les données de la conversation
$conversation = $chatController->getConversation($id);
if (!$conversation) {
    header('Location: conversations.php');
    exit;
}

$users = $chatController->getUsers();
$user1_id = $conversation['user1_id'];
$user2_id = $conversation['user2_id'];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user1_id = isset($_POST['user1_id']) ? trim($_POST['user1_id']) : '';
    $user2_id = isset($_POST['user2_id']) ? trim($_POST['user2_id']) : '';

    $errors = $chatController->validateConversation($user1_id, $user2_id);

    if (empty($errors)) {
        $result = $chatController->updateConversation($id, $user1_id, $user2_id);
        if ($result['success']) {
            $successMsg = "Conversation modifiée avec succès.";
            // Recharger les données
            $conversation = $chatController->getConversation($id);
        } else {
            $errors = $result['errors'];
        }
    }
}

$templateBase = '../startbootstrap-sb-admin-2-gh-pages';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>SkillBridge - Modifier Conversation</title>
    <link href="<?= $templateBase ?>/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="<?= $templateBase ?>/css/sb-admin-2.min.css" rel="stylesheet">
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
            <li class="nav-item">
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
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Modifier la Conversation #<?= $id ?></h1>
                        <a href="conversations.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Retour
                        </a>
                    </div>

                    <?php if ($successMsg): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $successMsg ?>
                            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <strong>Erreur(s) de validation :</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Modifier les participants</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="edit_conversation.php?id=<?= $id ?>" novalidate>
                                <div class="form-group">
                                    <label for="user1_id">Participant 1 <span class="text-danger">*</span></label>
                                    <select class="form-control" id="user1_id" name="user1_id">
                                        <option value="">-- Choisir un utilisateur --</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= htmlspecialchars($user['id']) ?>" <?= ($user1_id == $user['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom'] . ' (' . $user['role'] . ')') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="user2_id">Participant 2 <span class="text-danger">*</span></label>
                                    <select class="form-control" id="user2_id" name="user2_id">
                                        <option value="">-- Choisir un utilisateur --</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= htmlspecialchars($user['id']) ?>" <?= ($user2_id == $user['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom'] . ' (' . $user['role'] . ')') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Enregistrer
                                </button>
                                <a href="conversations.php" class="btn btn-secondary">Annuler</a>
                            </form>
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
</body>
</html>
