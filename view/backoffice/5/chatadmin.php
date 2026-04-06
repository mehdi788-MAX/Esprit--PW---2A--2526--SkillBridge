<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Gestion Chat - SkillBridge Admin</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Noto+Sans:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">

  <style>
    body { background: #f4f6f9; }

    /* Sidebar */
    .sidebar {
      width: 250px;
      min-height: 100vh;
      background: #1a1a2e;
      color: #fff;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 100;
      padding-top: 20px;
    }
    .sidebar .logo {
      padding: 20px 25px;
      font-size: 1.4rem;
      font-weight: 700;
      color: #0ea2bd;
      border-bottom: 1px solid #2a2a4a;
      margin-bottom: 10px;
    }
    .sidebar .nav-link {
      color: #ccc;
      padding: 12px 25px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 0.95rem;
      transition: 0.2s;
    }
    .sidebar .nav-link:hover,
    .sidebar .nav-link.active {
      color: #fff;
      background: #0ea2bd22;
      border-left: 3px solid #0ea2bd;
    }

    .main-content { margin-left: 250px; padding: 30px; }

    .topbar {
      background: #fff;
      padding: 15px 25px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }
    .topbar h4 { margin: 0; font-weight: 700; color: #1a1a2e; }

    /* Stats */
    .stat-card {
      background: #fff;
      border-radius: 12px;
      padding: 20px 25px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 25px;
    }
    .stat-card .icon {
      width: 55px; height: 55px;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem; color: #fff;
    }
    .stat-card .icon.blue { background: #0ea2bd; }
    .stat-card .icon.green { background: #28a745; }
    .stat-card .icon.orange { background: #fd7e14; }
    .stat-card .stat-info h3 { font-size: 1.6rem; font-weight: 700; margin: 0; color: #1a1a2e; }
    .stat-card .stat-info p { margin: 0; color: #888; font-size: 0.85rem; }

    /* Table */
    .table-card {
      background: #fff;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      margin-bottom: 25px;
    }
    .table-card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .table-card h5 { font-weight: 700; color: #1a1a2e; margin: 0; }
    .table th {
      background: #f8f9fa;
      font-weight: 600;
      font-size: 0.85rem;
      color: #555;
      text-transform: uppercase;
    }
    .table td { vertical-align: middle; font-size: 0.9rem; }

    /* Chat preview panel */
    .chat-preview-card {
      background: #fff;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .chat-preview-card h5 { font-weight: 700; color: #1a1a2e; margin-bottom: 15px; }
    .msg-preview {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      padding: 10px 0;
      border-bottom: 1px solid #f5f5f5;
    }
    .msg-preview:last-child { border-bottom: none; }
    .msg-preview-avatar {
      width: 36px; height: 36px;
      border-radius: 50%;
      background: #e0f7fa;
      display: flex; align-items: center; justify-content: center;
      color: #0ea2bd; font-size: 0.9rem;
      flex-shrink: 0;
    }
    .msg-preview-content .sender { font-weight: 600; font-size: 0.85rem; }
    .msg-preview-content .text { font-size: 0.85rem; color: #666; }
    .msg-preview-content .time { font-size: 0.75rem; color: #aaa; }

    .search-bar { position: relative; max-width: 250px; }
    .search-bar input { padding-left: 35px; border-radius: 20px; font-size: 0.9rem; }
    .search-bar i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #888; }
  </style>
</head>

<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <div class="logo"><i class="bi bi-layers me-2"></i> SkillBridge</div>
    <nav>
      <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a href="users_list.php" class="nav-link"><i class="bi bi-people-fill"></i> Utilisateurs</a>
      <a href="chat_admin.php" class="nav-link active"><i class="bi bi-chat-dots-fill"></i> Chat</a>
      <a href="offres.php" class="nav-link"><i class="bi bi-briefcase-fill"></i> Offres</a>
      <a href="projets.php" class="nav-link"><i class="bi bi-folder-fill"></i> Projets</a>
      <a href="settings.php" class="nav-link"><i class="bi bi-gear-fill"></i> Paramètres</a>
      <a href="../../controllers/ConversationController.php?action=logout" class="nav-link mt-5">
        <i class="bi bi-box-arrow-left"></i> Déconnexion
      </a>
    </nav>
  </div>

  <!-- Main Content -->
  <div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
      <h4><i class="bi bi-chat-dots-fill me-2"></i>Gestion du Chat</h4>
      <span class="text-muted" style="font-size:0.9rem;"><i class="bi bi-person-circle me-1"></i> Admin</span>
    </div>

    <?php if (isset($success)): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row">
      <div class="col-md-4">
        <div class="stat-card">
          <div class="icon blue"><i class="bi bi-chat-dots-fill"></i></div>
          <div class="stat-info">
            <h3><?= $total_conversations ?? 0 ?></h3>
            <p>Total Conversations</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="icon green"><i class="bi bi-envelope-fill"></i></div>
          <div class="stat-info">
            <h3><?= $total_messages ?? 0 ?></h3>
            <p>Total Messages</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="icon orange"><i class="bi bi-envelope-open-fill"></i></div>
          <div class="stat-info">
            <h3><?= $messages_non_lus ?? 0 ?></h3>
            <p>Messages Non Lus</p>
          </div>
        </div>
      </div>
    </div>

    <div class="row">

      <!-- Conversations Table -->
      <div class="col-lg-7">
        <div class="table-card" data-aos="fade-up">
          <div class="table-card-header">
            <h5>Toutes les Conversations</h5>
            <div class="search-bar">
              <i class="bi bi-search"></i>
              <input type="text" id="searchConv" class="form-control" placeholder="Rechercher...">
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-hover" id="convTable">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Participants</th>
                  <th>Messages</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($conversations)): ?>
                  <?php foreach ($conversations as $index => $conv): ?>
                  <tr>
                    <td><?= $index + 1 ?></td>
                    <td>
                      <div style="font-size:0.85rem;">
                        <span class="fw-semibold"><?= htmlspecialchars($conv['user1_nom']) ?></span>
                        <i class="bi bi-arrow-left-right mx-1 text-muted"></i>
                        <span class="fw-semibold"><?= htmlspecialchars($conv['user2_nom']) ?></span>
                      </div>
                    </td>
                    <td><span class="badge bg-primary"><?= $conv['nb_messages'] ?? 0 ?></span></td>
                    <td style="font-size:0.82rem;"><?= htmlspecialchars($conv['date_creation']) ?></td>
                    <td>
                      <div class="d-flex gap-2">
                        <a href="view_conversation.php?id=<?= $conv['id_conversation'] ?>"
                           class="btn btn-sm btn-outline-primary" title="Voir">
                          <i class="bi bi-eye-fill"></i>
                        </a>
                        <a href="../../controllers/ConversationController.php?action=delete&id=<?= $conv['id_conversation'] ?>"
                           class="btn btn-sm btn-outline-danger" title="Supprimer"
                           onclick="return confirm('Supprimer cette conversation et tous ses messages ?')">
                          <i class="bi bi-trash-fill"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted py-4">Aucune conversation trouvée.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Recent Messages -->
      <div class="col-lg-5">
        <div class="chat-preview-card" data-aos="fade-up">
          <h5><i class="bi bi-clock-history me-2"></i>Messages Récents</h5>

          <?php if (!empty($recent_messages)): ?>
            <?php foreach ($recent_messages as $msg): ?>
            <div class="msg-preview">
              <div class="msg-preview-avatar">
                <i class="bi bi-person-fill"></i>
              </div>
              <div class="msg-preview-content">
                <div class="sender"><?= htmlspecialchars($msg['sender_nom']) ?></div>
                <div class="text"><?= htmlspecialchars(substr($msg['contenu'], 0, 60)) ?>...</div>
                <div class="time"><?= htmlspecialchars($msg['date_envoi']) ?></div>
              </div>
              <?php if (!$msg['is_seen']): ?>
                <span class="badge bg-danger ms-auto align-self-start">Non lu</span>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-muted text-center py-3">Aucun message récent.</p>
          <?php endif; ?>

        </div>
      </div>

    </div>

  </div>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/js/main.js"></script>

  <script>
    document.getElementById('searchConv').addEventListener('input', function() {
      const search = this.value.toLowerCase();
      document.querySelectorAll('#convTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(search) ? '' : 'none';
      });
    });
  </script>

</body>
</html>