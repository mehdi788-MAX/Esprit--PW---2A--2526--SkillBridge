<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Chat - SkillBridge</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Noto+Sans:wght@400;600;700&family=Questrial:wght@400&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    .chat-wrapper {
      max-width: 800px;
      margin: 0 auto;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      height: 75vh;
    }

    /* Chat Header */
    .chat-header {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 15px 20px;
      background: #fff;
      border-bottom: 1px solid #f0f0f0;
      flex-shrink: 0;
    }
    .chat-header .back-btn {
      color: #0ea2bd;
      font-size: 1.3rem;
      text-decoration: none;
    }
    .chat-header-avatar {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      object-fit: cover;
    }
    .chat-header-avatar-placeholder {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: #e0f7fa;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #0ea2bd;
      font-size: 1.1rem;
    }
    .chat-header-info h6 {
      margin: 0;
      font-weight: 600;
      font-size: 0.95rem;
    }
    .chat-header-info small {
      color: #aaa;
      font-size: 0.78rem;
    }

    /* Messages area */
    .chat-messages {
      flex: 1;
      overflow-y: auto;
      padding: 20px;
      background: #f8f9fa;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    /* Message bubble */
    .message-row {
      display: flex;
      align-items: flex-end;
      gap: 8px;
    }
    .message-row.sent {
      flex-direction: row-reverse;
    }
    .msg-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      object-fit: cover;
      flex-shrink: 0;
    }
    .msg-avatar-placeholder {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: #e0f7fa;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #0ea2bd;
      font-size: 0.85rem;
      flex-shrink: 0;
    }
    .msg-bubble {
      max-width: 65%;
      padding: 10px 14px;
      border-radius: 16px;
      font-size: 0.9rem;
      line-height: 1.5;
      position: relative;
    }
    .message-row.received .msg-bubble {
      background: #fff;
      border-bottom-left-radius: 4px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.07);
      color: #333;
    }
    .message-row.sent .msg-bubble {
      background: #0ea2bd;
      border-bottom-right-radius: 4px;
      color: #fff;
    }
    .msg-time {
      font-size: 0.7rem;
      color: #aaa;
      margin-top: 4px;
      text-align: right;
    }
    .message-row.sent .msg-time {
      text-align: left;
      color: #ccc;
    }
    .msg-seen {
      font-size: 0.7rem;
      color: #ccc;
    }

    /* Date separator */
    .date-separator {
      text-align: center;
      font-size: 0.78rem;
      color: #aaa;
      margin: 5px 0;
      position: relative;
    }
    .date-separator::before,
    .date-separator::after {
      content: '';
      position: absolute;
      top: 50%;
      width: 35%;
      height: 1px;
      background: #e0e0e0;
    }
    .date-separator::before { left: 0; }
    .date-separator::after { right: 0; }

    /* Input area */
    .chat-input {
      padding: 15px 20px;
      background: #fff;
      border-top: 1px solid #f0f0f0;
      display: flex;
      align-items: center;
      gap: 10px;
      flex-shrink: 0;
    }
    .chat-input textarea {
      flex: 1;
      border-radius: 25px;
      resize: none;
      padding: 10px 18px;
      font-size: 0.9rem;
      border: 1px solid #e0e0e0;
      outline: none;
      max-height: 100px;
    }
    .chat-input textarea:focus {
      border-color: #0ea2bd;
    }
    .send-btn {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: #0ea2bd;
      color: #fff;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      cursor: pointer;
      transition: 0.2s;
      flex-shrink: 0;
    }
    .send-btn:hover {
      background: #0c8fa8;
    }
    .attach-btn {
      color: #aaa;
      font-size: 1.2rem;
      cursor: pointer;
      transition: 0.2s;
    }
    .attach-btn:hover {
      color: #0ea2bd;
    }
  </style>
</head>

<body class="index-page">

  <!-- Header -->
  <header id="header" class="header d-flex align-items-center sticky-top">
    <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-between">
      <a href="index.html" class="logo d-flex align-items-center me-auto me-xl-0">
        <h1 class="sitename">SkillBridge</h1>
      </a>
      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="index.html">Accueil</a></li>
          <li><a href="profil.php">Mon Profil</a></li>
          <li><a href="conversations.php" class="active">Messages</a></li>
          <li><a href="../../controllers/ConversationController.php?action=logout">Déconnexion</a></li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>
      <div class="header-social-links">
        <a href="#" class="twitter"><i class="bi bi-twitter-x"></i></a>
        <a href="#" class="facebook"><i class="bi bi-facebook"></i></a>
        <a href="#" class="instagram"><i class="bi bi-instagram"></i></a>
        <a href="#" class="linkedin"><i class="bi bi-linkedin"></i></a>
      </div>
    </div>
  </header>

  <main class="main">
    <section class="section light-background" style="min-height: 85vh; padding: 30px 0;">
      <div class="container" data-aos="fade-up">

        <div class="chat-wrapper">

          <!-- Chat Header -->
          <div class="chat-header">
            <a href="conversations.php" class="back-btn"><i class="bi bi-arrow-left"></i></a>

            <?php if (!empty($contact['photo'])): ?>
              <img src="assets/img/profile/<?= htmlspecialchars($contact['photo']) ?>" class="chat-header-avatar" alt="">
            <?php else: ?>
              <div class="chat-header-avatar-placeholder">
                <i class="bi bi-person-fill"></i>
              </div>
            <?php endif; ?>

            <div class="chat-header-info">
              <h6><?= htmlspecialchars($contact['prenom'] . ' ' . $contact['nom']) ?></h6>
              <small><?= htmlspecialchars(ucfirst($contact['role'])) ?></small>
            </div>
          </div>

          <!-- Messages -->
          <div class="chat-messages" id="chatMessages">

            <?php if (!empty($messages)): ?>
              <?php $last_date = ''; ?>
              <?php foreach ($messages as $msg): ?>

                <?php
                  $msg_date = date('d/m/Y', strtotime($msg['date_envoi']));
                  if ($msg_date !== $last_date):
                    $last_date = $msg_date;
                ?>
                  <div class="date-separator"><?= $msg_date ?></div>
                <?php endif; ?>

                <?php $is_sent = $msg['sender_id'] == $current_user_id; ?>
                <div class="message-row <?= $is_sent ? 'sent' : 'received' ?>">

                  <?php if (!$is_sent): ?>
                    <div class="msg-avatar-placeholder">
                      <i class="bi bi-person-fill"></i>
                    </div>
                  <?php endif; ?>

                  <div>
                    <div class="msg-bubble">
                      <?= htmlspecialchars($msg['contenu']) ?>
                    </div>
                    <div class="msg-time">
                      <?= date('H:i', strtotime($msg['date_envoi'])) ?>
                      <?php if ($is_sent): ?>
                        <span class="msg-seen">
                          <?= $msg['is_seen'] ? '✓✓' : '✓' ?>
                        </span>
                      <?php endif; ?>
                    </div>
                  </div>

                </div>

              <?php endforeach; ?>
            <?php else: ?>
              <div class="text-center text-muted my-auto">
                <i class="bi bi-chat" style="font-size: 2.5rem; opacity:0.3;"></i>
                <p class="mt-2">Commencez la conversation !</p>
              </div>
            <?php endif; ?>

          </div>

          <!-- Input -->
          <form action="../../controllers/MessageController.php" method="POST">
            <input type="hidden" name="action" value="send">
            <input type="hidden" name="id_conversation" value="<?= htmlspecialchars($conversation['id_conversation'] ?? '') ?>">
            <div class="chat-input">
              <label for="fileInput" class="attach-btn" title="Joindre un fichier">
                <i class="bi bi-paperclip"></i>
              </label>
              <input type="file" id="fileInput" name="fichier" style="display:none;">
              <textarea name="contenu" id="messageInput" placeholder="Écrire un message..." rows="1" required></textarea>
              <button type="submit" class="send-btn" title="Envoyer">
                <i class="bi bi-send-fill"></i>
              </button>
            </div>
          </form>

        </div>

      </div>
    </section>
  </main>

  <!-- Footer -->
  <footer id="footer" class="footer">
    <div class="container">
      <div class="copyright text-center">
        <p>© <span>Copyright</span> <strong class="px-1 sitename">SkillBridge</strong> <span>All Rights Reserved</span></p>
      </div>
    </div>
  </footer>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/js/main.js"></script>

  <script>
    // Scroll to bottom
    const chatMessages = document.getElementById('chatMessages');
    chatMessages.scrollTop = chatMessages.scrollHeight;

    // Auto resize textarea
    const textarea = document.getElementById('messageInput');
    textarea.addEventListener('input', function() {
      this.style.height = 'auto';
      this.style.height = Math.min(this.scrollHeight, 100) + 'px';
    });
  </script>

</body>
</html>
