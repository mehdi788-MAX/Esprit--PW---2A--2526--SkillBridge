<?php
require_once __DIR__ . '/../../../controller/ChatController.php';

$chatController = new ChatController();

// Simuler le client connecté (id=3)
$currentUserId = 3;

$errors = [];
$user2_id = '';

// Récupérer les freelancers
$freelancers = $chatController->getFreelancers();

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

$templateBase = '../EasyFolio';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>SkillBridge - Nouvelle Conversation</title>
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
                    <li><a href="new_conversation.php" class="active">Nouveau Chat</a></li>
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
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Nouvelle Conversation</h5>
                            </div>
                            <div class="card-body">
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

                                <form method="POST" action="new_conversation.php" novalidate>
                                    <div class="mb-4">
                                        <label for="user2_id" class="form-label fw-bold">
                                            Choisir un freelancer <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select form-select-lg" id="user2_id" name="user2_id">
                                            <option value="">-- Sélectionnez un freelancer --</option>
                                            <?php foreach ($freelancers as $freelancer): ?>
                                                <option value="<?= htmlspecialchars($freelancer['id']) ?>" <?= ($user2_id == $freelancer['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($freelancer['prenom'] . ' ' . $freelancer['nom']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">Sélectionnez le freelancer avec qui vous souhaitez discuter.</div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="bi bi-chat-dots"></i> Démarrer la Conversation
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
