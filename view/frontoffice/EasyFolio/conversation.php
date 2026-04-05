<?php
// 🔴 DEBUG (remove later in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// ✅ Try loading controller (optional)
$conversations = [];

try {
    if (file_exists('../../controllers/ConversationController.php')) {
        require_once '../../controllers/ConversationController.php';

        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $conversations = ConversationController::getUserConversations($user_id);
        }
    }
} catch (Exception $e) {
    // fallback if controller fails
    $conversations = [];
}

// ✅ TEMP DATA (REMOVE when backend works)
if (empty($conversations)) {
    $conversations = [
        [
            'id_conversation' => 1,
            'photo' => '',
            'nom_contact' => 'John Doe',
            'dernier_message' => 'Salut, ça va ?',
            'date_envoi' => date('Y-m-d H:i:s'),
            'unread_count' => 2
        ],
        [
            'id_conversation' => 2,
            'photo' => '',
            'nom_contact' => 'Jane Smith',
            'dernier_message' => 'Merci pour ton aide !',
            'date_envoi' => date('Y-m-d H:i:s'),
            'unread_count' => 0
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mes Conversations - SkillBridge</title>

  <!-- CSS -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    .conversation-list { max-width: 750px; margin: 0 auto; }
    .conversation-item {
      display: flex; align-items: center; gap: 15px;
      padding: 15px 20px; border-radius: 12px;
      background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      margin-bottom: 12px; text-decoration: none; color: inherit;
      transition: 0.2s;
    }
    .conversation-item:hover { transform: translateY(-2px); }
    .conversation-item.unread { border-left: 4px solid #0ea2bd; }
    .conv-avatar, .conv-avatar-placeholder {
      width: 52px; height: 52px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
    }
    .conv-avatar-placeholder { background: #e0f7fa; color: #0ea2bd; }
    .conv-info { flex: 1; }
    .conv-name { font-weight: 600; }
    .conv-last-msg { font-size: 0.85rem; color: #888; }
    .conv-meta { text-align: right; }
    .conv-badge {
      background: #0ea2bd; color: #fff;
      border-radius: 50%; width: 20px; height: 20px;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.7rem;
    }
    .new-chat-btn {
      position: fixed; bottom: 30px; right: 30px;
      width: 55px; height: 55px; border-radius: 50%;
      background: #0ea2bd; color: #fff;
      display: flex; align-items: center; justify-content: center;
    }
  </style>
</head>

<body>

<header class="p-3 shadow-sm bg-white">
  <div class="container d-flex justify-content-between">
    <h4>SkillBridge</h4>
    <nav>
      <a href="index.html">Accueil</a> |
      <a href="profil.php">Profil</a> |
      <a href="conversations.php"><b>Messages</b></a>
    </nav>
  </div>
</header>

<main class="container mt-4">

  <h2 class="text-center mb-4">Mes Messages</h2>

  <!-- Search -->
  <input type="text" id="searchConv" class="form-control mb-3" placeholder="Rechercher...">

  <!-- Conversations -->
  <div class="conversation-list">

    <?php if (!empty($conversations)): ?>
      <?php foreach ($conversations as $conv): ?>

        <a href="chat.php?id=<?= $conv['id_conversation'] ?>" 
           class="conversation-item <?= ($conv['unread_count'] ?? 0) > 0 ? 'unread' : '' ?>">

          <!-- Avatar -->
          <?php if (!empty($conv['photo'])): ?>
            <img src="assets/img/profile/<?= htmlspecialchars($conv['photo']) ?>" class="conv-avatar">
          <?php else: ?>
            <div class="conv-avatar-placeholder">
              <i class="bi bi-person"></i>
            </div>
          <?php endif; ?>

          <!-- Info -->
          <div class="conv-info">
            <div class="conv-name"><?= htmlspecialchars($conv['nom_contact'] ?? '') ?></div>
            <div class="conv-last-msg">
              <?= htmlspecialchars($conv['dernier_message'] ?? 'Aucun message') ?>
            </div>
          </div>

          <!-- Meta -->
          <div class="conv-meta">
            <div>
              <?= !empty($conv['date_envoi']) ? date('d/m H:i', strtotime($conv['date_envoi'])) : '' ?>
            </div>
            <?php if (($conv['unread_count'] ?? 0) > 0): ?>
              <div class="conv-badge"><?= $conv['unread_count'] ?></div>
            <?php endif; ?>
          </div>

        </a>

      <?php endforeach; ?>
    <?php else: ?>

      <div class="text-center text-muted mt-5">
        <p>Aucune conversation.</p>
      </div>

    <?php endif; ?>

  </div>

</main>

<!-- New Chat -->
<a href="new_conversation.php" class="new-chat-btn">
  <i class="bi bi-pencil"></i>
</a>

<script>
document.getElementById('searchConv').addEventListener('input', function() {
  let search = this.value.toLowerCase();
  document.querySelectorAll('.conversation-item').forEach(item => {
    let name = item.querySelector('.conv-name').textContent.toLowerCase();
    let msg = item.querySelector('.conv-last-msg').textContent.toLowerCase();
    item.style.display = (name.includes(search) || msg.includes(search)) ? '' : 'none';
  });
});
</script>

</body>
</html>