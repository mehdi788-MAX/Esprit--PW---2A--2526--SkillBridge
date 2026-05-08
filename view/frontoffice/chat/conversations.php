<?php
require_once __DIR__ . '/../../../controller/ChatController.php';

$chatController = new ChatController();

require_once __DIR__ . "/_auth.php";

$successMsg = '';
$errorMsg = '';

// Traitement suppression
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $result = $chatController->deleteConversation($id);
    if ($result['success']) {
        $successMsg = "Conversation supprimée avec succès.";
    } else {
        $errorMsg = implode('<br>', $result['errors']);
    }
}

// Récupérer les conversations du client
$conversations = $chatController->listConversationsByUser($currentUserId)->fetchAll(PDO::FETCH_ASSOC);

$templateBase = '../EasyFolio';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>SkillBridge - Mes Conversations</title>
    <link href="<?= $templateBase ?>/assets/img/favicon.png" rel="icon">
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Noto+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= $templateBase ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $templateBase ?>/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= $templateBase ?>/assets/css/main.css" rel="stylesheet">
    <style>
        .chat-section { padding: 80px 0 60px; min-height: 100vh; }
        .conversation-card { transition: all 0.3s ease; border-left: 4px solid transparent; }
        .conversation-card:hover { border-left-color: var(--accent-color, #0d6efd); transform: translateX(5px); }
        .badge-unread { background-color: #dc3545; }
    </style>
</head>
<body class="index-page">
    <!-- Header -->
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
                    <li id="bellSlot" style="display:flex;align-items:center;"></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
        </div>
    </header>

    <main class="main">
        <section class="chat-section">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Mes Conversations</h2>
                        <p class="text-muted">Bienvenue, Sarra Trabelsi</p>
                    </div>
                    <a href="new_conversation.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Nouvelle Conversation
                    </a>
                </div>

                <?php if ($successMsg): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $successMsg ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($errorMsg): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $errorMsg ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($conversations)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-chat-dots" style="font-size: 4rem; color: #ccc;"></i>
                        <h4 class="mt-3 text-muted">Aucune conversation</h4>
                        <p class="text-muted">Commencez à discuter avec un freelancer !</p>
                        <a href="new_conversation.php" class="btn btn-primary mt-2">
                            <i class="bi bi-plus-circle"></i> Démarrer une conversation
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($conversations as $conv): ?>
                            <?php
                            $otherUser = ($conv['user1_id'] == $currentUserId)
                                ? $conv['user2_prenom'] . ' ' . $conv['user2_nom']
                                : $conv['user1_prenom'] . ' ' . $conv['user1_nom'];
                            ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card conversation-card shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:45px;height:45px;font-size:1.2rem;">
                                                <?= strtoupper(substr($otherUser, 0, 1)) ?>
                                            </div>
                                            <div class="ms-3">
                                                <h6 class="mb-0"><?= htmlspecialchars($otherUser) ?></h6>
                                                <small class="text-muted">Freelancer</small>
                                            </div>
                                        </div>

                                        <?php if (!empty($conv['dernier_message'])): ?>
                                            <p class="text-muted small mb-2">
                                                <i class="bi bi-chat-text"></i>
                                                <?= htmlspecialchars(substr($conv['dernier_message'], 0, 60)) ?>...
                                            </p>
                                        <?php else: ?>
                                            <p class="text-muted small mb-2 fst-italic">Aucun message encore</p>
                                        <?php endif; ?>

                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> <?= htmlspecialchars($conv['date_creation']) ?>
                                        </small>
                                    </div>
                                    <div class="card-footer bg-transparent border-top-0 d-flex gap-2">
                                        <a href="chat.php?id=<?= $conv['id_conversation'] ?>" class="btn btn-sm btn-primary flex-grow-1">
                                            <i class="bi bi-chat-dots"></i> Ouvrir
                                        </a>
                                        <a href="edit_conversation.php?id=<?= $conv['id_conversation'] ?>" class="btn btn-sm btn-outline-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="conversations.php?action=delete&id=<?= $conv['id_conversation'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette conversation ?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
    <script src="../../shared/chatbus.js"></script>
    <script>
        ChatBus.init({ apiBase: '../../../api/chat.php', user: <?= (int)$currentUserId ?>, conv: 0 });
        ChatBus.mountBell('#bellSlot');
    </script>
</body>
</html>
