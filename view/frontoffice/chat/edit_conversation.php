<?php
require_once __DIR__ . '/../../../controller/ChatController.php';

$chatController = new ChatController();

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

$users = $chatController->getUsers();
$user1_id = $conversation['user1_id'];
$user2_id = $conversation['user2_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user1_id = isset($_POST['user1_id']) ? trim($_POST['user1_id']) : '';
    $user2_id = isset($_POST['user2_id']) ? trim($_POST['user2_id']) : '';

    $errors = $chatController->validateConversation($user1_id, $user2_id);

    if (empty($errors)) {
        $result = $chatController->updateConversation($id, $user1_id, $user2_id);
        if ($result['success']) {
            $successMsg = "Conversation modifiée avec succès.";
            $conversation = $chatController->getConversation($id);
        } else {
            $errors = $result['errors'];
        }
    }
}

$templateBase = '../EasyFolio';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>SkillBridge - Modifier Conversation</title>
    <link href="<?= $templateBase ?>/assets/img/favicon.png" rel="icon">
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Noto+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= $templateBase ?>/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $templateBase ?>/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= $templateBase ?>/assets/css/main.css" rel="stylesheet">
    <style>
        .chat-section { padding: 80px 0 60px; min-height: 100vh; }
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
                    <li><a href="conversations.php">Mes Conversations</a></li>
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
                    <div class="col-md-8 col-lg-6">
                        <div class="mb-4">
                            <a href="conversations.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left"></i> Retour aux conversations
                            </a>
                        </div>

                        <div class="card shadow">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="bi bi-pencil"></i> Modifier Conversation #<?= $id ?></h5>
                            </div>
                            <div class="card-body">
                                <?php if ($successMsg): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <?= $successMsg ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($errors)): ?>
                                    <div class="alert alert-danger">
                                        <strong><i class="bi bi-exclamation-triangle"></i> Erreur(s) de validation :</strong>
                                        <ul class="mb-0 mt-2">
                                            <?php foreach ($errors as $error): ?>
                                                <li><?= htmlspecialchars($error) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="edit_conversation.php?id=<?= $id ?>" novalidate>
                                    <div class="mb-3">
                                        <label for="user1_id" class="form-label fw-bold">
                                            Participant 1 <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="user1_id" name="user1_id">
                                            <option value="">-- Choisir --</option>
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?= htmlspecialchars($user['id']) ?>" <?= ($user1_id == $user['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom'] . ' (' . $user['role'] . ')') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-4">
                                        <label for="user2_id" class="form-label fw-bold">
                                            Participant 2 <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="user2_id" name="user2_id">
                                            <option value="">-- Choisir --</option>
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?= htmlspecialchars($user['id']) ?>" <?= ($user2_id == $user['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom'] . ' (' . $user['role'] . ')') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-warning btn-lg">
                                            <i class="bi bi-check-circle"></i> Enregistrer les modifications
                                        </button>
                                        <a href="conversations.php" class="btn btn-outline-secondary">Annuler</a>
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
</body>
</html>
