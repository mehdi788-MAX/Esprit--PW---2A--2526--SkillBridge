<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/EasyFolio/login.php');
    exit;
}

require_once '../../config.php';
require_once '../../model/utilisateur.php';

$utilisateurModel = new Utilisateur($pdo);

// Stats
$total_utilisateurs = $utilisateurModel->countAll();
$total_freelancers  = $utilisateurModel->countByRole('freelancer');
$total_clients      = $utilisateurModel->countByRole('client');
$total_admins       = $utilisateurModel->countByRole('admin');

// Active vs Inactive
$stmt_active   = $pdo->query("SELECT COUNT(*) as total FROM utilisateurs WHERE is_active = 1");
$total_active  = $stmt_active->fetch()['total'];
$total_inactive = $total_utilisateurs - $total_active;

// Registrations per month (last 6 months)
$stmt_months = $pdo->query("
    SELECT DATE_FORMAT(date_inscription, '%M %Y') as mois,
           COUNT(*) as total
    FROM utilisateurs
    WHERE date_inscription >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(date_inscription, '%Y-%m')
    ORDER BY date_inscription ASC
");
$months_data = $stmt_months->fetchAll(PDO::FETCH_ASSOC);

$months_labels = json_encode(array_column($months_data, 'mois'));
$months_values = json_encode(array_column($months_data, 'total'));

// Latest 5 users
$stmt_latest = $pdo->query("
    SELECT nom, prenom, email, role, photo, is_active, date_inscription
    FROM utilisateurs
    ORDER BY date_inscription DESC
    LIMIT 5
");
$latest_users = $stmt_latest->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Dashboard - SkillBridge Admin</title>

  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">

  <style>
    .stat-icon { font-size: 2rem; opacity: 0.3; }
    .user-avatar { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; }
    .user-avatar-placeholder {
      width: 38px; height: 38px; border-radius: 50%;
      background: #e0f7fa; display: flex; align-items: center;
      justify-content: center; color: #4e73df; font-size: 1rem;
    }
    .badge-freelancer { background: #1cc88a22; color: #1cc88a; }
    .badge-client     { background: #36b9cc22; color: #36b9cc; }
    .badge-admin      { background: #e74a3b22; color: #e74a3b; }
    .badge-role { font-size: 0.75rem; padding: 4px 10px; border-radius: 20px; font-weight: 600; }
  </style>
</head>

<body id="page-top">
  <div id="wrapper">

    <!-- Sidebar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
      <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
        <div class="sidebar-brand-text mx-3">Skill <sup>Bridge</sup></div>
      </a>
      <hr class="sidebar-divider my-0">
      <li class="nav-item active">
        <a class="nav-link" href="dashboard.php">
          <i class="fas fa-fw fa-tachometer-alt"></i><span>Dashboard</span>
        </a>
      </li>
      <hr class="sidebar-divider">
      <div class="sidebar-heading">Gestion Utilisateurs</div>
      <li class="nav-item">
        <a class="nav-link" href="users_list.php"><i class="fas fa-fw fa-users"></i><span>Liste Utilisateurs</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="users_profils.php"><i class="fas fa-fw fa-id-card"></i><span>Utilisateurs & Profils</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="search_utilisateurs.php"><i class="fas fa-fw fa-search"></i><span>Recherche par Rôle</span></a>
      </li>
      <hr class="sidebar-divider">
      <div class="sidebar-heading">Gestion Chat</div>
      <li class="nav-item">
        <a class="nav-link" href="chat/conversations.php"><i class="fas fa-fw fa-comments"></i><span>Conversations</span></a>
      </li>
      <hr class="sidebar-divider d-none d-md-block">
      <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
      </div>
    </ul>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">

        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
          <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
            <i class="fa fa-bars"></i>
          </button>
          <ul class="navbar-nav ml-auto">
            <li class="nav-item">
              <a class="nav-link" href="../../controller/utilisateurcontroller.php?action=logout">
                <i class="fas fa-sign-out-alt mr-1"></i> Déconnexion
              </a>
            </li>
            <li class="nav-item">
              <span class="nav-link text-gray-600">
                <i class="fas fa-user-circle mr-1"></i> <?= htmlspecialchars($_SESSION['user_nom'] ?? 'Admin') ?>
              </span>
            </li>
          </ul>
        </nav>

        <!-- Page Content -->
        <div class="container-fluid">

          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
              <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
            </h1>
            <a href="users_list.php" class="btn btn-primary btn-sm shadow-sm">
              <i class="fas fa-users fa-sm mr-1"></i> Gérer les utilisateurs
            </a>
          </div>

          <!-- Stats Row -->
          <div class="row">

            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Utilisateurs</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_utilisateurs ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Freelancers</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_freelancers ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-user-tie fa-2x text-gray-300"></i></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Clients</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_clients ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-user fa-2x text-gray-300"></i></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
              <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Admins</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_admins ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-user-shield fa-2x text-gray-300"></i></div>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- Charts Row -->
          <div class="row">

            <!-- Line Chart - Registrations per month -->
            <div class="col-xl-8 col-lg-7 mb-4">
              <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                  <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-line mr-2"></i>Inscriptions des 6 derniers mois
                  </h6>
                </div>
                <div class="card-body">
                  <canvas id="registrationsChart" height="100"></canvas>
                </div>
              </div>
            </div>

            <!-- Pie Chart - Role distribution -->
            <div class="col-xl-4 col-lg-5 mb-4">
              <div class="card shadow">
                <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-chart-pie mr-2"></i>Répartition par rôle
                  </h6>
                </div>
                <div class="card-body">
                  <canvas id="rolesChart"></canvas>
                  <div class="mt-4 text-center small">
                    <span class="mr-2"><i class="fas fa-circle" style="color:#4e73df"></i> Freelancers</span>
                    <span class="mr-2"><i class="fas fa-circle" style="color:#1cc88a"></i> Clients</span>
                    <span class="mr-2"><i class="fas fa-circle" style="color:#e74a3b"></i> Admins</span>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- Second Charts Row -->
          <div class="row">

            <!-- Donut Chart - Active vs Inactive -->
            <div class="col-xl-4 col-lg-5 mb-4">
              <div class="card shadow">
                <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-toggle-on mr-2"></i>Comptes Actifs / Inactifs
                  </h6>
                </div>
                <div class="card-body">
                  <canvas id="activeChart"></canvas>
                  <div class="mt-4 text-center small">
                    <span class="mr-2"><i class="fas fa-circle text-success"></i> Actifs (<?= $total_active ?>)</span>
                    <span class="mr-2"><i class="fas fa-circle text-danger"></i> Inactifs (<?= $total_inactive ?>)</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Latest Users -->
            <div class="col-xl-8 col-lg-7 mb-4">
              <div class="card shadow">
                <div class="card-header py-3">
                  <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-user-plus mr-2"></i>Derniers inscrits
                  </h6>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead class="thead-light">
                        <tr>
                          <th>Utilisateur</th>
                          <th>Email</th>
                          <th>Rôle</th>
                          <th>Statut</th>
                          <th>Inscrit le</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($latest_users as $user): ?>
                        <tr>
                          <td>
                            <div class="d-flex align-items-center">
                              <?php if (!empty($user['photo'])): ?>
                                <img src="../frontoffice/EasyFolio/assets/img/profile/<?= htmlspecialchars($user['photo']) ?>"
                                     class="user-avatar mr-2" alt="">
                              <?php else: ?>
                                <div class="user-avatar-placeholder mr-2">
                                  <i class="fas fa-user"></i>
                                </div>
                              <?php endif; ?>
                              <span><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></span>
                            </div>
                          </td>
                          <td><?= htmlspecialchars($user['email']) ?></td>
                          <td>
                            <span class="badge badge-role badge-<?= $user['role'] ?>">
                              <?= ucfirst($user['role']) ?>
                            </span>
                          </td>
                          <td>
                            <?php if ($user['is_active']): ?>
                              <span class="badge badge-role" style="background:#1cc88a22; color:#1cc88a;">Actif</span>
                            <?php else: ?>
                              <span class="badge badge-role" style="background:#e74a3b22; color:#e74a3b;">Inactif</span>
                            <?php endif; ?>
                          </td>
                          <td><?= date('d/m/Y', strtotime($user['date_inscription'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                  <div class="text-center mt-2">
                    <a href="users_list.php" class="btn btn-sm btn-primary">
                      Voir tous les utilisateurs <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>

          </div>

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

  <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="js/sb-admin-2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <script>
    // Line Chart - Registrations per month
    new Chart(document.getElementById('registrationsChart'), {
      type: 'line',
      data: {
        labels: <?= $months_labels ?>,
        datasets: [{
          label: 'Inscriptions',
          data: <?= $months_values ?>,
          borderColor: '#4e73df',
          backgroundColor: 'rgba(78, 115, 223, 0.1)',
          borderWidth: 2,
          pointBackgroundColor: '#4e73df',
          pointRadius: 4,
          fill: true,
          tension: 0.3
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { stepSize: 1 }
          }
        }
      }
    });

    // Pie Chart - Roles
    new Chart(document.getElementById('rolesChart'), {
      type: 'pie',
      data: {
        labels: ['Freelancers', 'Clients', 'Admins'],
        datasets: [{
          data: [<?= $total_freelancers ?>, <?= $total_clients ?>, <?= $total_admins ?>],
          backgroundColor: ['#4e73df', '#1cc88a', '#e74a3b'],
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false }
        }
      }
    });

    // Donut Chart - Active vs Inactive
    new Chart(document.getElementById('activeChart'), {
      type: 'doughnut',
      data: {
        labels: ['Actifs', 'Inactifs'],
        datasets: [{
          data: [<?= $total_active ?>, <?= $total_inactive ?>],
          backgroundColor: ['#1cc88a', '#e74a3b'],
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false }
        },
        cutout: '70%'
      }
    });
  </script>

</body>
</html>