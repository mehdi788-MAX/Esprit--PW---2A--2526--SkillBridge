<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Gestion Utilisateurs - SkillBridge Admin</title>

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
    body {
      background: #f4f6f9;
    }

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
    .sidebar .nav-link i {
      font-size: 1.1rem;
    }

    /* Main content */
    .main-content {
      margin-left: 250px;
      padding: 30px;
    }

    /* Topbar */
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
    .topbar h4 {
      margin: 0;
      font-weight: 700;
      color: #1a1a2e;
    }

    /* Stats cards */
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
      width: 55px;
      height: 55px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      color: #fff;
    }
    .stat-card .icon.blue { background: #0ea2bd; }
    .stat-card .icon.green { background: #28a745; }
    .stat-card .icon.orange { background: #fd7e14; }
    .stat-card .stat-info h3 {
      font-size: 1.6rem;
      font-weight: 700;
      margin: 0;
      color: #1a1a2e;
    }
    .stat-card .stat-info p {
      margin: 0;
      color: #888;
      font-size: 0.85rem;
    }

    /* Table card */
    .table-card {
      background: #fff;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .table-card .card-header-custom {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .table-card h5 {
      font-weight: 700;
      color: #1a1a2e;
      margin: 0;
    }

    /* Table */
    .table th {
      background: #f8f9fa;
      font-weight: 600;
      font-size: 0.85rem;
      color: #555;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .table td {
      vertical-align: middle;
      font-size: 0.9rem;
    }
    .user-avatar {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      object-fit: cover;
    }
    .user-avatar-placeholder {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: #e0f7fa;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #0ea2bd;
      font-size: 1rem;
    }
    .badge-role {
      font-size: 0.75rem;
      padding: 4px 10px;
      border-radius: 20px;
    }
    .badge-freelancer { background: #0ea2bd22; color: #0ea2bd; }
    .badge-client { background: #28a74522; color: #28a745; }
    .badge-admin { background: #dc354522; color: #dc3545; }

    /* Search bar */
    .search-bar {
      position: relative;
      max-width: 280px;
    }
    .search-bar input {
      padding-left: 35px;
      border-radius: 20px;
      font-size: 0.9rem;
    }
    .search-bar i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #888;
    }
  </style>
</head>

<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <div class="logo">
      <i class="bi bi-layers me-2"></i> SkillBridge
    </div>
    <nav>
      <a href="dashboard.php" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
      </a>
      <a href="users_list.php" class="nav-link active">
        <i class="bi bi-people-fill"></i> Utilisateurs
      </a>
      <a href="offres.php" class="nav-link">
        <i class="bi bi-briefcase-fill"></i> Offres
      </a>
      <a href="projets.php" class="nav-link">
        <i class="bi bi-folder-fill"></i> Projets
      </a>
      <a href="settings.php" class="nav-link">
        <i class="bi bi-gear-fill"></i> Paramètres
      </a>
      <a href="../../controllers/UtilisateurController.php?action=logout" class="nav-link mt-5">
        <i class="bi bi-box-arrow-left"></i> Déconnexion
      </a>
    </nav>
  </div>

  <!-- Main Content -->
  <div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
      <h4><i class="bi bi-people-fill me-2"></i>Gestion des Utilisateurs</h4>
      <div class="d-flex align-items-center gap-3">
        <span class="text-muted" style="font-size:0.9rem;">
          <i class="bi bi-person-circle me-1"></i> Admin
        </span>
      </div>
    </div>

    <?php if (isset($success)): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row">
      <div class="col-md-4">
        <div class="stat-card">
          <div class="icon blue"><i class="bi bi-people-fill"></i></div>
          <div class="stat-info">
            <h3><?= $total_utilisateurs ?? 0 ?></h3>
            <p>Total Utilisateurs</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="icon green"><i class="bi bi-person-check-fill"></i></div>
          <div class="stat-info">
            <h3><?= $total_freelancers ?? 0 ?></h3>
            <p>Freelancers</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="icon orange"><i class="bi bi-person-fill"></i></div>
          <div class="stat-info">
            <h3><?= $total_clients ?? 0 ?></h3>
            <p>Clients</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="table-card" data-aos="fade-up">
      <div class="card-header-custom">
        <h5>Liste des Utilisateurs</h5>
        <div class="d-flex gap-3 align-items-center">
          <!-- Recherche -->
          <div class="search-bar">
            <i class="bi bi-search"></i>
            <input type="text" id="searchInput" class="form-control" placeholder="Rechercher...">
          </div>
          <!-- Filtre rôle -->
          <select id="filterRole" class="form-select form-select-sm" style="width:140px; border-radius:20px;">
            <option value="">Tous les rôles</option>
            <option value="freelancer">Freelancer</option>
            <option value="client">Client</option>
            <option value="admin">Admin</option>
          </select>
          <!-- Ajouter -->
          <a href="add_user.php" class="btn btn-submit btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Ajouter
          </a>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover" id="usersTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Utilisateur</th>
              <th>Email</th>
              <th>Rôle</th>
              <th>Téléphone</th>
              <th>Date d'inscription</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($utilisateurs)): ?>
              <?php foreach ($utilisateurs as $index => $user): ?>
              <tr>
                <td><?= $index + 1 ?></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <?php if (!empty($user['photo'])): ?>
                      <img src="assets/img/profile/<?= htmlspecialchars($user['photo']) ?>" class="user-avatar" alt="">
                    <?php else: ?>
                      <div class="user-avatar-placeholder">
                        <i class="bi bi-person-fill"></i>
                      </div>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></span>
                  </div>
                </td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td>
                  <span class="badge badge-<?= $user['role'] ?>">
                    <?= ucfirst(htmlspecialchars($user['role'])) ?>
                  </span>
                </td>
                <td><?= !empty($user['telephone']) ? htmlspecialchars($user['telephone']) : '-' ?></td>
                <td><?= htmlspecialchars($user['date_inscription']) ?></td>
                <td>
                  <div class="d-flex gap-2">
                    <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary" title="Modifier">
                      <i class="bi bi-pencil-fill"></i>
                    </a>
                    <a href="../../controllers/UtilisateurController.php?action=delete&id=<?= $user['id'] ?>"
                       class="btn btn-sm btn-outline-danger"
                       title="Supprimer"
                       onclick="return confirm('Confirmer la suppression de cet utilisateur ?')">
                      <i class="bi bi-trash-fill"></i>
                    </a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">Aucun utilisateur trouvé.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>

  </div>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/js/main.js"></script>

  <script>
    // Recherche en temps réel
    document.getElementById('searchInput').addEventListener('input', filterTable);
    document.getElementById('filterRole').addEventListener('change', filterTable);

    function filterTable() {
      const search = document.getElementById('searchInput').value.toLowerCase();
      const role = document.getElementById('filterRole').value.toLowerCase();
      const rows = document.querySelectorAll('#usersTable tbody tr');

      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const roleCell = row.querySelector('.badge') ? row.querySelector('.badge').textContent.toLowerCase() : '';
        const matchSearch = text.includes(search);
        const matchRole = role === '' || roleCell.includes(role);
        row.style.display = matchSearch && matchRole ? '' : 'none';
      });
    }
  </script>

</body>

</html>