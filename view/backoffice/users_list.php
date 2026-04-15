<?php
session_start();

// Vérifier si admin connecté
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/EasyFolio/login.php');
    exit;
}

// Connexion BDD
require_once '../../config.php';
require_once '../../model/utilisateur.php';

$utilisateurModel   = new Utilisateur($pdo);
$total_utilisateurs = $utilisateurModel->countAll();
$total_freelancers  = $utilisateurModel->countByRole('freelancer');
$total_clients      = $utilisateurModel->countByRole('client');
$stmt               = $utilisateurModel->readAll();
$utilisateurs       = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = isset($_SESSION['success']) ? $_SESSION['success'] : null;
$error   = isset($_SESSION['error'])   ? $_SESSION['error']   : null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Gestion Utilisateurs - SkillBridge Admin</title>

  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">
  <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
  <style>
    .user-avatar { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; }
    .user-avatar-placeholder { width: 38px; height: 38px; border-radius: 50%; background: #e0f7fa; display: flex; align-items: center; justify-content: center; color: #4e73df; font-size: 1rem; }
    .badge-freelancer { background: #1cc88a22; color: #1cc88a; }
    .badge-client { background: #36b9cc22; color: #36b9cc; }
    .badge-admin { background: #e74a3b22; color: #e74a3b; }
    .badge-role { font-size: 0.75rem; padding: 4px 10px; border-radius: 20px; }
  </style>
</head>

<body id="page-top">

  <div id="wrapper">

    <!-- Sidebar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
      <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.html">
        <div class="sidebar-brand-text mx-3">Skill <sup>Bridge</sup></div>
      </a>
      <hr class="sidebar-divider my-0">
      <li class="nav-item">
        <a class="nav-link" href="index.html"><i class="fas fa-fw fa-tachometer-alt"></i><span>Dashboard</span></a>
      </li>
      <hr class="sidebar-divider">
      <div class="sidebar-heading">Gestion Utilisateurs</div>
      <li class="nav-item active">
        <a class="nav-link" href="users_list.php"><i class="fas fa-fw fa-users"></i><span> liste utilisateur</span></a>

      </li>
      <li class="nav-item">
  <a class="nav-link" href="users_profils.php"><i class="fas fa-fw fa-id-card"></i><span>Utilisateurs & Profils</span></a>
</li>
 <li class="nav-item">
  <a class="nav-link" href="search_utilisateurs.php"><i class="fas fa-fw fa-id-card"></i><span>recherche par role </span></a>
</li>
      <hr class="sidebar-divider">
      <div class="sidebar-heading">Gestion Chat</div>
      <li class="nav-item">
        <a class="nav-link" href="chat/conversations.php"><i class="fas fa-fw fa-comments"></i><span>Conversations</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="chat/add_conversation.php"><i class="fas fa-fw fa-plus-circle"></i><span>Nouvelle Conversation</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="chat/messages.php"><i class="fas fa-fw fa-envelope"></i><span>Tous les Messages</span></a>
      </li>
      <hr class="sidebar-divider">
      <div class="sidebar-heading">Interface</div>
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
          <i class="fas fa-fw fa-cog"></i><span>Components</span>
        </a>
        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Custom Components:</h6>
            <a class="collapse-item" href="buttons.html">Buttons</a>
            <a class="collapse-item" href="cards.html">Cards</a>
          </div>
        </div>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseUtilities" aria-expanded="true" aria-controls="collapseUtilities">
          <i class="fas fa-fw fa-wrench"></i><span>Utilities</span>
        </a>
        <div id="collapseUtilities" class="collapse" aria-labelledby="headingUtilities" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Custom Utilities:</h6>
            <a class="collapse-item" href="utilities-color.html">Colors</a>
            <a class="collapse-item" href="utilities-border.html">Borders</a>
            <a class="collapse-item" href="utilities-animation.html">Animations</a>
            <a class="collapse-item" href="utilities-other.html">Other</a>
          </div>
        </div>
      </li>
      <hr class="sidebar-divider">
      <div class="sidebar-heading">Addons</div>
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePages" aria-expanded="true" aria-controls="collapsePages">
          <i class="fas fa-fw fa-folder"></i><span>Pages</span>
        </a>
        <div id="collapsePages" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Login Screens:</h6>
            <a class="collapse-item" href="login.html">Login</a>
            <a class="collapse-item" href="register.html">Register</a>
            <a class="collapse-item" href="forgot-password.html">Forgot Password</a>
            <div class="collapse-divider"></div>
            <h6 class="collapse-header">Other Pages:</h6>
            <a class="collapse-item" href="404.html">404 Page</a>
            <a class="collapse-item" href="blank.html">Blank Page</a>
          </div>
        </div>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="charts.html"><i class="fas fa-fw fa-chart-area"></i><span>Charts</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="tables.html"><i class="fas fa-fw fa-table"></i><span>Tables</span></a>
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
            <li class="nav-item dropdown no-arrow">
              <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                  <i class="fas fa-user-circle mr-1"></i> <?= htmlspecialchars($_SESSION['user_nom'] ?? 'Admin') ?>
                </span>
                <img class="img-profile rounded-circle" src="img/undraw_profile.svg">
              </a>
            </li>
          </ul>
        </nav>

        <!-- Page Content -->
        <div class="container-fluid">

          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-users mr-2"></i>Gestion des Utilisateurs</h1>
          </div>

          <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <?= $success ?>
              <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
          <?php endif; ?>
          <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?= $error ?>
              <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
          <?php endif; ?>

          <!-- Stats Cards -->
          <div class="row">
            <div class="col-xl-4 col-md-6 mb-4">
              <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Utilisateurs</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_utilisateurs ?? 0 ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
              <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Freelancers</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_freelancers ?? 0 ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-user-tie fa-2x text-gray-300"></i></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
              <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                  <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                      <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Clients</div>
                      <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $total_clients ?? 0 ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-user fa-2x text-gray-300"></i></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Users Table -->
          <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
              <h6 class="m-0 font-weight-bold text-primary">Liste des Utilisateurs</h6>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
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
                          <div class="d-flex align-items-center">
                            <?php if (!empty($user['photo'])): ?>
                              <img src="../frontoffice/EasyFolio/assets/img/profile/<?= htmlspecialchars($user['photo']) ?>" class="user-avatar mr-2" alt="">
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
                            <?= ucfirst(htmlspecialchars($user['role'])) ?>
                          </span>
                        </td>
                        <td><?= !empty($user['telephone']) ? htmlspecialchars($user['telephone']) : '-' ?></td>
                        <td><?= htmlspecialchars($user['date_inscription']) ?></td>
                        <td>
                          <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-warning" title="Modifier">
                            <i class="fas fa-edit"></i>
                          </a>
                          <a href="../../controller/utilisateurcontroller.php?action=delete&id=<?= $user['id'] ?>"
                             class="btn btn-sm btn-danger"
                             title="Supprimer"
                             onclick="return confirm('Confirmer la suppression de cet utilisateur ?')">
                            <i class="fas fa-trash"></i>
                          </a>
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
  <script src="vendor/datatables/jquery.dataTables.min.js"></script>
  <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
  <script>$(document).ready(function() { $('#dataTable').DataTable(); });</script>

</body>

</html>
