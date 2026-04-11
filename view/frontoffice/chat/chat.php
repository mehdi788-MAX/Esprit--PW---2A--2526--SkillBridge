<?php
require_once __DIR__ . '/../../../controller/ChatController.php';

$chatController = new ChatController();

// Simuler le client connecté (id=3)
$currentUserId = 3;

$errors = [];
$successMsg = '';

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

// Mode édition
$editMsgId = isset($_GET['edit_msg']) ? intval($_GET['edit_msg']) : 0;
$editMsgContent = '';
if ($editMsgId > 0) {
    $editMsg = $chatController->getMessage($editMsgId);
    if ($editMsg) {
        $editMsgContent = $editMsg['contenu'];
    }
}

$templateBase = '../EasyFolio';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>SkillBridge - Chat avec <?= htmlspecialchars($otherUser) ?></title>
    <link href="<?= $templateBase ?>/assets/img/favicon.png" rel="icon">
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Noto+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= $templateBase ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $templateBase ?>/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= $templateBase ?>/assets/css/main.css" rel="stylesheet">
    <style>
        .chat-section { padding: 80px 0 60px; min-height: 100vh; }
        .chat-container { max-height: 500px; overflow-y: auto; padding: 20px; background: #f8f9fa; border-radius: 10px; }
        .msg-bubble { max-width: 70%; padding: 12px 16px; border-radius: 18px; margin-bottom: 8px; position: relative; }
        .msg-sent { background: linear-gradient(135deg, #0d6efd, #0a58ca); color: white; margin-left: auto; border-bottom-right-radius: 4px; }
        .msg-received { background: #e9ecef; color: #333; margin-right: auto; border-bottom-left-radius: 4px; }
        .msg-meta { font-size: 0.7rem; opacity: 0.7; margin-top: 4px; }
        .msg-actions { display: none; position: absolute; top: 5px; right: 8px; }
        .msg-bubble:hover .msg-actions { display: flex; gap: 4px; }
        .msg-actions a { color: inherit; opacity: 0.6; font-size: 0.75rem; }
        .msg-actions a:hover { opacity: 1; }
    </style>
</head>
<body class="index-page">
    <header id="header" class="header d-flex align-items-center sticky-top">
        <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
            <a href="#" class="logo d-flex align-items-center me-auto me-xl-0">
                <h1 class="sitename">SkillBridge</h1>
            </a>
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="<?= $templateBase ?>/index.html">Accueil</a></li>
                    <li><a href="conversations.php" class="active">Mes Conversations</a></li>
                    <li><a href="new_conversation.php">Nouveau Chat</a></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
        </div>
    </header>

    <main class="main">
        <section class="chat-section">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="mb-3">
                            <a href="conversations.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left"></i> Retour
                            </a>
                        </div>

                        <?php if ($successMsg): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= $successMsg ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <strong><i class="bi bi-exclamation-triangle"></i> Erreur(s) :</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="card shadow">
                            <!-- Chat Header -->
                            <div class="card-header bg-primary text-white d-flex align-items-center">
                                <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center me-3" style="width:40px;height:40px;font-weight:bold;">
                                    <?= strtoupper(substr($otherUser, 0, 1)) ?>
                                </div>
                                <div>
                                    <h6 class="mb-0 text-white"><?= htmlspecialchars($otherUser) ?></h6>
                                    <small class="opacity-75">Freelancer</small>
                                </div>
                            </div>

                            <!-- Messages -->
                            <div class="card-body p-0">
                                <div class="chat-container" id="chatContainer">
                                    <?php if (empty($messages)): ?>
                                        <div class="text-center text-muted py-5">
                                            <i class="bi bi-chat-dots" style="font-size: 2.5rem;"></i>
                                            <p class="mt-2">Commencez la conversation !</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($messages as $msg): ?>
                                            <?php $isSent = ($msg['sender_id'] == $currentUserId); ?>
                                            <div class="d-flex <?= $isSent ? 'justify-content-end' : 'justify-content-start' ?> mb-2">
                                                <div class="msg-bubble <?= $isSent ? 'msg-sent' : 'msg-received' ?>">
                                                    <?php if ($isSent): ?>
                                                        <div class="msg-actions">
                                                            <a href="chat.php?id=<?= $id ?>&edit_msg=<?= $msg['id_message'] ?>" title="Modifier">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <a href="chat.php?id=<?= $id ?>&action=delete_msg&msg_id=<?= $msg['id_message'] ?>" title="Supprimer" onclick="return confirm('Supprimer ce message ?');">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div><?= htmlspecialchars($msg['contenu']) ?></div>
                                                    <div class="msg-meta">
                                                        <?= htmlspecialchars($msg['sender_prenom']) ?> - <?= date('H:i', strtotime($msg['date_envoi'])) ?>
                                                        <?php if ($isSent && $msg['is_seen']): ?>
                                                            <i class="bi bi-check2-all"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Input Area -->
                            <div class="card-footer">
                                <?php if ($editMsgId > 0): ?>
                                    <form method="POST" action="chat.php?id=<?= $id ?>" novalidate class="mb-3">
                                        <input type="hidden" name="action" value="edit_msg">
                                        <input type="hidden" name="msg_id" value="<?= $editMsgId ?>">
                                        <label class="form-label fw-bold text-warning">
                                            <i class="bi bi-pencil"></i> Modifier le message
                                        </label>
                                        <div class="input-group">
                                            <textarea class="form-control" name="contenu" rows="2"><?= htmlspecialchars($editMsgContent) ?></textarea>
                                            <button type="submit" class="btn btn-warning">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </div>
                                        <a href="chat.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary mt-2">Annuler</a>
                                    </form>
                                    <hr>
                                <?php endif; ?>

                                <form method="POST" action="chat.php?id=<?= $id ?>" novalidate>
                                    <input type="hidden" name="action" value="send">
                                    <div class="input-group">
                                        <textarea class="form-control" name="contenu" rows="2" placeholder="Tapez votre message..."></textarea>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-send"></i> Envoyer
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer id="footer" class="footer dark-background">
        <div class="container">
            <div class="copyright text-center">
                <p>&copy; <span>Copyright</span> <strong class="px-1 sitename">SkillBridge</strong> <span><?= date('Y') ?></span></p>
            </div>
        </div>
    </footer>

    <script src="<?= $templateBase ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $templateBase ?>/assets/js/main.js"></script>
    <script>
        var chatContainer = document.getElementById('chatContainer');
        chatContainer.scrollTop = chatContainer.scrollHeight;
    </script>
</body>
</html>
