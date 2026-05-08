<?php
require_once __DIR__ . '/../../../controller/ChatController.php';

$chatController = new ChatController();

require_once __DIR__ . "/_auth.php";

$errors = [];
$successMsg = '';
$user2_id = '';

// Récupérer les clients pour le select
$clients = $chatController->getClients();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user2_id = isset($_POST['user2_id']) ? trim($_POST['user2_id']) : '';

    // Validation côté serveur
    $errors = $chatController->validateConversation($currentUserId, $user2_id);

    if (empty($errors)) {
        $result = $chatController->createConversation($currentUserId, $user2_id);
        if ($result['success']) {
            header('Location: chat.php?id=' . $result['id']);
            exit;
        } else {
            $errors = $result['errors'];
        }
    }
}

$templateBase = '..';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>SkillBridge - Nouvelle Conversation</title>
    <link href="<?= $templateBase ?>/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="<?= $templateBase ?>/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php $activePage = 'add_conversation'; include 'sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
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

                <!-- Page Content -->
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Nouvelle Conversation</h1>
                        <a href="conversations.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Retour
                        </a>
                    </div>

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
                            <h6 class="m-0 font-weight-bold text-primary">Démarrer une conversation avec un client</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="add_conversation.php" novalidate>
                                <div class="form-group">
                                    <label for="user2_id">Sélectionner un client <span class="text-danger">*</span></label>
                                    <select class="form-control" id="user2_id" name="user2_id">
                                        <option value="">-- Choisir un client --</option>
                                        <?php foreach ($clients as $client): ?>
                                            <option value="<?= htmlspecialchars($client['id']) ?>" <?= ($user2_id == $client['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($client['prenom'] . ' ' . $client['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Créer la Conversation
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
