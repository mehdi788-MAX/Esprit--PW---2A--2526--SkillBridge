<?php
require_once __DIR__ . '/../../../controller/ChatController.php';

$chatController = new ChatController();

// Simuler le freelancer connecté (id=2)
$currentUserId = 2;

$errors = [];
$successMsg = '';

// Récupérer l'ID de la conversation
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: conversations.php');
    exit;
}

$conversation = $chatController->getConversation($id);
if (!$conversation) {
    header('Location: conversations.php');
    exit;
}

// Déterminer l'autre participant
$otherUser = ($conversation['user1_id'] == $currentUserId)
    ? $conversation['user2_prenom'] . ' ' . $conversation['user2_nom']
    : $conversation['user1_prenom'] . ' ' . $conversation['user1_nom'];

// Traitement suppression d'un message
if (isset($_GET['action']) && $_GET['action'] === 'delete_msg' && isset($_GET['msg_id'])) {
    $msgId = intval($_GET['msg_id']);
    $result = $chatController->deleteMessage($msgId);
    if ($result['success']) {
        $successMsg = "Message supprimé.";
    } else {
        $errors = $result['errors'];
    }
}

// Traitement modification d'un message
if (isset($_POST['action']) && $_POST['action'] === 'edit_msg') {
    $msgId = isset($_POST['msg_id']) ? intval($_POST['msg_id']) : 0;
    $contenu = isset($_POST['contenu']) ? trim($_POST['contenu']) : '';

    $validationErrors = $chatController->validateUpdateMessage($contenu);
    if (!empty($validationErrors)) {
        $errors = $validationErrors;
    } else {
        $result = $chatController->updateMessage($msgId, $contenu);
        if ($result['success']) {
            $successMsg = "Message modifié.";
        } else {
            $errors = $result['errors'];
        }
    }
}

// Traitement envoi de message
if (isset($_POST['action']) && $_POST['action'] === 'send') {
    $contenu = isset($_POST['contenu']) ? trim($_POST['contenu']) : '';

    $validationErrors = $chatController->validateMessage($id, $currentUserId, $contenu);
    if (!empty($validationErrors)) {
        $errors = $validationErrors;
    } else {
        $result = $chatController->sendMessage($id, $currentUserId, $contenu);
        if ($result['success']) {
            header('Location: chat.php?id=' . $id);
            exit;
        } else {
            $errors = $result['errors'];
        }
    }
}

// Marquer les messages comme lus
$chatController->markMessagesAsSeen($id, $currentUserId);

// Récupérer les messages
$messages = $chatController->listMessagesByConversation($id)->fetchAll(PDO::FETCH_ASSOC);

// Vérifier si on est en mode édition
$editMsgId = isset($_GET['edit_msg']) ? intval($_GET['edit_msg']) : 0;
$editMsgContent = '';
if ($editMsgId > 0) {
    $editMsg = $chatController->getMessage($editMsgId);
    if ($editMsg) {
        $editMsgContent = $editMsg['contenu'];
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
    <title>SkillBridge - Chat avec <?= htmlspecialchars($otherUser) ?></title>
    <link href="<?= $templateBase ?>/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="<?= $templateBase ?>/css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .chat-container { max-height: 500px; overflow-y: auto; padding: 20px; background: #f8f9fc; border-radius: 5px; }
        .message-bubble { max-width: 70%; padding: 10px 15px; border-radius: 15px; margin-bottom: 10px; position: relative; }
        .message-sent { background-color: #4e73df; color: white; margin-left: auto; border-bottom-right-radius: 5px; }
        .message-received { background-color: #e2e8f0; color: #333; margin-right: auto; border-bottom-left-radius: 5px; }
        .message-meta { font-size: 0.75rem; opacity: 0.7; margin-top: 5px; }
        .message-actions { position: absolute; top: 5px; right: 10px; display: none; }
        .message-bubble:hover .message-actions { display: inline-block; }
        .message-actions a { color: inherit; opacity: 0.7; margin-left: 5px; font-size: 0.8rem; }
        .message-actions a:hover { opacity: 1; }
    </style>
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
            <li class="nav-item active">
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
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="fas fa-comments text-primary"></i>
                            Chat avec <?= htmlspecialchars($otherUser) ?>
                        </h1>
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
                            <strong>Erreur(s) :</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-user-circle"></i> <?= htmlspecialchars($otherUser) ?>
                            </h6>
                            <small class="text-muted">Conversation créée le <?= htmlspecialchars($conversation['date_creation']) ?></small>
                        </div>
                        <div class="card-body">
                            <!-- Zone des messages -->
                            <div class="chat-container" id="chatContainer">
                                <?php if (empty($messages)): ?>
                                    <div class="text-center text-muted py-5">
                                        <i class="fas fa-paper-plane fa-2x mb-3"></i>
                                        <p>Aucun message. Envoyez le premier !</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($messages as $msg): ?>
                                        <?php $isSent = ($msg['sender_id'] == $currentUserId); ?>
                                        <div class="d-flex <?= $isSent ? 'justify-content-end' : 'justify-content-start' ?> mb-2">
                                            <div class="message-bubble <?= $isSent ? 'message-sent' : 'message-received' ?>">
                                                <?php if ($isSent): ?>
                                                    <div class="message-actions">
                                                        <a href="chat.php?id=<?= $id ?>&edit_msg=<?= $msg['id_message'] ?>" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="chat.php?id=<?= $id ?>&action=delete_msg&msg_id=<?= $msg['id_message'] ?>" title="Supprimer" onclick="return confirm('Supprimer ce message ?');">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                                <div><?= htmlspecialchars($msg['contenu']) ?></div>
                                                <div class="message-meta">
                                                    <?= htmlspecialchars($msg['sender_prenom']) ?> - <?= date('H:i', strtotime($msg['date_envoi'])) ?>
                                                    <?php if ($isSent && $msg['is_seen']): ?>
                                                        <i class="fas fa-check-double"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <hr>

                            <!-- Formulaire de modification -->
                            <?php if ($editMsgId > 0): ?>
                                <form method="POST" action="chat.php?id=<?= $id ?>" novalidate>
                                    <input type="hidden" name="action" value="edit_msg">
                                    <input type="hidden" name="msg_id" value="<?= $editMsgId ?>">
                                    <div class="form-group">
                                        <label class="font-weight-bold text-warning">
                                            <i class="fas fa-edit"></i> Modifier le message
                                        </label>
                                        <textarea class="form-control" name="contenu" rows="2" placeholder="Modifier votre message..."><?= htmlspecialchars($editMsgContent) ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        <i class="fas fa-save"></i> Enregistrer
                                    </button>
                                    <a href="chat.php?id=<?= $id ?>" class="btn btn-secondary btn-sm">Annuler</a>
                                </form>
                                <hr>
                            <?php endif; ?>

                            <!-- Formulaire d'envoi -->
                            <form method="POST" action="chat.php?id=<?= $id ?>" novalidate>
                                <input type="hidden" name="action" value="send">
                                <div class="input-group">
                                    <textarea class="form-control" name="contenu" rows="2" placeholder="Tapez votre message..."></textarea>
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Envoyer
                                        </button>
                                    </div>
                                </div>
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
    <script>
        // Auto-scroll to bottom of chat
        var chatContainer = document.getElementById('chatContainer');
        chatContainer.scrollTop = chatContainer.scrollHeight;
    </script>
</body>
</html>
