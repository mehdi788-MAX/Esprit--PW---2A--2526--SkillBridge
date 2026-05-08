<?php
require_once 'auth_check_admin.php';
 
// Connexion BDD
require_once '../../config.php';
require_once '../../model/utilisateur.php';
 
$utilisateurModel = new Utilisateur($pdo);
 
// Récupérer tous les utilisateurs avec leur profil (jointure)
$stmt         = $utilisateurModel->readAllWithProfil();
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
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
  <title>Utilisateurs & Profils - SkillBridge Admin</title>
 
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">
  <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
 
  <style>
    .user-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; }
    .user-avatar-placeholder {
      width: 45px; height: 45px; border-radius: 50%;
      background: #e0f7fa; display: flex; align-items: center;
      justify-content: center; color: #4e73df; font-size: 1.1rem;
    }
    .badge-freelancer { background: #1cc88a22; color: #1cc88a; }
    .badge-client     { background: #36b9cc22; color: #36b9cc; }
    .badge-admin      { background: #e74a3b22; color: #e74a3b; }
    .badge-role { font-size: 0.75rem; padding: 4px 10px; border-radius: 20px; font-weight: 600; }
    .competence-tag {
      display: inline-block; background: #4e73df22; color: #4e73df;
      padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; margin: 2px;
    }
    .empty-field { color: #ccc; font-style: italic; font-size: 0.85rem; }
  </style>
</head>
 
<body id="page-top">
 
  <div id="wrapper">
 
    <!-- Sidebar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
      <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
        <div class="sidebar-brand-text mx-3">Skill <sup>Bridge</sup></div>
      </a>
      <hr class="sidebar-divider my-0">
      <li class="nav-item">
        <a class="nav-link" href="index.php"><i class="fas fa-fw fa-tachometer-alt"></i><span>Dashboard</span></a>
      </li>
      <hr class="sidebar-divider">
      <div class="sidebar-heading">Gestion Utilisateurs</div>
      <li class="nav-item">
        <a class="nav-link" href="users_list.php"><i class="fas fa-fw fa-users"></i><span>Liste Utilisateurs</span></a>
      </li>
      <li class="nav-item active">
        <a class="nav-link" href="users_profils.php"><i class="fas fa-fw fa-id-card"></i><span>Utilisateurs & Profils</span></a>
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
              <i class="fas fa-id-card mr-2"></i>Utilisateurs & Profils
              <small class="text-muted" style="font-size:0.6em;">Jointure utilisateurs ⟶ profils</small>
            </h1>
            <a href="users_list.php" class="btn btn-secondary btn-sm">
              <i class="fas fa-arrow-left mr-1"></i> Retour
            </a>
          </div>
 
          <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
              <?= htmlspecialchars($success) ?>
              <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
          <?php endif; ?>
 
          
 
          <!-- Table -->
          <div class="card shadow mb-4">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary">
                Liste des Utilisateurs avec leurs Profils
                <span class="badge badge-primary ml-2"><?= count($utilisateurs) ?> utilisateurs</span>
              </h6>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                  <thead class="thead-light">
                    <tr>
                      <th>#</th>
                      <th>Utilisateur</th>
                      <th>Email</th>
                      <th>Rôle</th>
                      <th>Bio</th>
                      <th>Compétences</th>
                      <th>Localisation</th>
                      <th>Site Web</th>
                      <th>Inscrit le</th>
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
                              <img src="../frontoffice/EasyFolio/assets/img/profile/<?= htmlspecialchars($user['photo']) ?>"
                                   class="user-avatar mr-2" alt="">
                            <?php else: ?>
                              <div class="user-avatar-placeholder mr-2">
                                <i class="fas fa-user"></i>
                              </div>
                            <?php endif; ?>
                            <div>
                              <div class="font-weight-bold"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>
                              <?php if (!empty($user['telephone'])): ?>
                                <small class="text-muted"><i class="fas fa-phone mr-1"></i><?= htmlspecialchars($user['telephone']) ?></small>
                              <?php endif; ?>
                            </div>
                          </div>
                        </td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                          <span class="badge badge-role badge-<?= $user['role'] ?>">
                            <?= ucfirst(htmlspecialchars($user['role'])) ?>
                          </span>
                        </td>
                        <td style="max-width: 150px;">
                          <?php if (!empty($user['bio'])): ?>
                            <small><?= htmlspecialchars(substr($user['bio'], 0, 80)) ?><?= strlen($user['bio']) > 80 ? '...' : '' ?></small>
                          <?php else: ?>
                            <span class="empty-field">Non renseigné</span>
                          <?php endif; ?>
                        </td>
                        <td style="max-width: 150px;">
                          <?php if (!empty($user['competences'])): ?>
                            <?php foreach (explode(',', $user['competences']) as $comp): ?>
                              <span class="competence-tag"><?= htmlspecialchars(trim($comp)) ?></span>
                            <?php endforeach; ?>
                          <?php else: ?>
                            <span class="empty-field">Non renseigné</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if (!empty($user['localisation'])): ?>
                            <i class="fas fa-map-marker-alt text-danger mr-1"></i><?= htmlspecialchars($user['localisation']) ?>
                          <?php else: ?>
                            <span class="empty-field">Non renseigné</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if (!empty($user['site_web'])): ?>
                            <a href="<?= htmlspecialchars($user['site_web']) ?>" target="_blank">
                              <i class="fas fa-external-link-alt mr-1"></i>Voir
                            </a>
                          <?php else: ?>
                            <span class="empty-field">Non renseigné</span>
                          <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($user['date_inscription']) ?></td>
                      </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="9" class="text-center text-muted py-4">Aucun utilisateur trouvé.</td>
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
  <script>
    $(document).ready(function() {
      $('#dataTable').DataTable({
        language: {
          url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/French.json'
        }
      });
    });
  </script>
 
</body>
</html>