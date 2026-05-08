<?php
require_once __DIR__ . '/../../../controller/ChatController.php';

$chatController = new ChatController();

require_once __DIR__ . "/_auth.php";

// Récupérer toutes les conversations (pour le menu déroulant)
$conversations = $chatController->listConversations()->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire de recherche
$messages = [];
$selectedConversation = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_conversation']) && isset($_POST['search'])) {
    $id_conversation = intval($_POST['id_conversation']);

    // Jointure : récupérer les messages de la conversation sélectionnée
    $messages = $chatController->listMessagesByConversation($id_conversation)->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les détails de la conversation sélectionnée
    $selectedConversation = $chatController->getConversation($id_conversation);
}

$templateBase = '..';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>SkillBridge - Recherche de Messages par Conversation</title>
    <link href="<?= $templateBase ?>/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="<?= $templateBase ?>/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php $activePage = 'search'; include 'sidebar.php'; ?>

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
                        <h1 class="h3 mb-0 text-gray-800">Recherche de Messages par Conversation</h1>
                    </div>

                    <!-- Formulaire de recherche (Jointure Conversation -> Message) -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-search"></i> Sélectionner une Conversation
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php
                            // Récupérer la liste des conversations depuis le controller
                            $conversations = $chatController->listConversations()->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <form action="" method="POST" id="searchForm" novalidate>
                                <div class="form-group row align-items-center">
                                    <label for="id_conversation" class="col-sm-2 col-form-label font-weight-bold">
                                        Sélectionnez une conversation :
                                    </label>
                                    <div class="col-sm-6">
                                        <select name="id_conversation" id="id_conversation" class="form-control">
                                            <option value="">-- Choisir une conversation --</option>
                                            <?php foreach ($conversations as $conv): ?>
                                                <option value="<?= htmlspecialchars($conv['id_conversation']) ?>"
                                                    <?= (isset($_POST['id_conversation']) && $_POST['id_conversation'] == $conv['id_conversation']) ? 'selected' : '' ?>>
                                                    Conversation #<?= htmlspecialchars($conv['id_conversation']) ?> :
                                                    <?= htmlspecialchars($conv['user1_prenom'] . ' ' . $conv['user1_nom']) ?>
                                                    ↔
                                                    <?= htmlspecialchars($conv['user2_prenom'] . ' ' . $conv['user2_nom']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-sm-2">
                                        <button type="submit" name="search" value="1" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Rechercher
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Affichage des messages correspondants à la conversation sélectionnée -->
                    <?php if (isset($selectedConversation) && $selectedConversation): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-success">
                                    <i class="fas fa-comments"></i>
                                    Messages correspondants à la conversation sélectionnée :
                                    <?= htmlspecialchars($selectedConversation['user1_prenom'] . ' ' . $selectedConversation['user1_nom']) ?>
                                    ↔
                                    <?= htmlspecialchars($selectedConversation['user2_prenom'] . ' ' . $selectedConversation['user2_nom']) ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($messages)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-gray-300 mb-2"></i>
                                        <p class="text-muted">Aucun message dans cette conversation.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Expéditeur</th>
                                                    <th>Contenu</th>
                                                    <th>Date d'envoi</th>
                                                    <th>Lu</th>
                                                    <th>Type</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($messages as $msg): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($msg['id_message']) ?></td>
                                                        <td>
                                                            <strong><?= htmlspecialchars($msg['sender_prenom'] . ' ' . $msg['sender_nom']) ?></strong>
                                                        </td>
                                                        <td><?= htmlspecialchars($msg['contenu']) ?></td>
                                                        <td><?= htmlspecialchars($msg['date_envoi']) ?></td>
                                                        <td>
                                                            <?php if ($msg['is_seen']): ?>
                                                                <span class="badge badge-success"><i class="fas fa-check"></i> Lu</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-warning"><i class="fas fa-clock"></i> Non lu</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-info"><?= htmlspecialchars($msg['type']) ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p class="text-muted mt-2">
                                        <strong><?= count($messages) ?></strong> message(s) trouvé(s).
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

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

    <script src="<?= $templateBase ?>/vendor/jquery/jquery.min.js"></script>
    <script src="<?= $templateBase ?>/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $templateBase ?>/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="<?= $templateBase ?>/js/sb-admin-2.min.js"></script>

    <script>
        // Controle de saisie JavaScript (remplace l'attribut HTML5 "required")
        document.getElementById('searchForm').addEventListener('submit', function (e) {
            var select = document.getElementById('id_conversation');
            var errorDiv = document.getElementById('js-search-error');

            if (errorDiv) { errorDiv.remove(); }

            if (!select.value || select.value === '') {
                e.preventDefault();

                var msg = document.createElement('div');
                msg.id = 'js-search-error';
                msg.className = 'alert alert-danger mt-2';
                msg.innerHTML = '<i class="fas fa-exclamation-circle"></i> Veuillez selectionner une conversation avant de rechercher.';
                select.parentNode.appendChild(msg);
                select.classList.add('is-invalid');
            } else {
                select.classList.remove('is-invalid');
            }
        });

        document.getElementById('id_conversation').addEventListener('change', function () {
            this.classList.remove('is-invalid');
            var errorDiv = document.getElementById('js-search-error');
            if (errorDiv) { errorDiv.remove(); }
        });
    </script>
</body>
</html>