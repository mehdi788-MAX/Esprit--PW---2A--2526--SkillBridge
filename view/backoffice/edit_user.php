<?php
session_start();

// Vérifier si admin connecté
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../frontoffice/EasyFolio/login.php');
    exit;
}

// Connexion BDD
$db = new PDO("mysql:host=localhost;dbname=skillbridge;charset=utf8", "root", "");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

require_once '../../model/utilisateur.php';

$utilisateurModel = new Utilisateur($db);

// Récupérer l'utilisateur à modifier
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: users_list.php');
    exit;
}

$utilisateurModel->id = $_GET['id'];
$utilisateurModel->readOne();

$utilisateur = [
    'id'        => $utilisateurModel->id,
    'nom'       => $utilisateurModel->nom,
    'prenom'    => $utilisateurModel->prenom,
    'email'     => $utilisateurModel->email,
    'role'      => $utilisateurModel->role,
    'telephone' => $utilisateurModel->telephone,
    'photo'     => $utilisateurModel->photo,
];

// Traitement formulaire
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom       = trim($_POST['nom'] ?? '');
    $prenom    = trim($_POST['prenom'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $role      = $_POST['role'] ?? '';
    $telephone = trim($_POST['telephone'] ?? '');

    // Validation PHP
    if (empty($nom))    $errors[] = "Le nom est obligatoire.";
    if (empty($prenom)) $errors[] = "Le prénom est obligatoire.";
    if (empty($email))  $errors[] = "L'email est obligatoire.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email invalide.";
    if (empty($role))   $errors[] = "Le rôle est obligatoire.";

    if (empty($errors)) {
        $utilisateurModel->id        = $_POST['id'];
        $utilisateurModel->nom       = $nom;
        $utilisateurModel->prenom    = $prenom;
        $utilisateurModel->email     = $email;
        $utilisateurModel->role      = $role;
        $utilisateurModel->telephone = $telephone;
        $utilisateurModel->photo     = $utilisateur['photo'];

        // Gestion photo
        if (!empty($_FILES['photo']['name'])) {
            $ext      = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $_POST['id'] . '_' . time() . '.' . $ext;
            $allowed  = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array(strtolower($ext), $allowed)) {
                move_uploaded_file($_FILES['photo']['tmp_name'], '../frontoffice/EasyFolio/assets/img/profile/' . $filename);
                $utilisateurModel->photo = $filename;
            }
        }

        if ($utilisateurModel->update()) {
            // Changer mot de passe si demandé
            if (!empty($_POST['new_password'])) {
                if ($_POST['new_password'] === $_POST['confirm_new_password']) {
                    $utilisateurModel->password = $_POST['new_password'];
                    $utilisateurModel->updatePassword();
                } else {
                    $errors[] = "Les mots de passe ne correspondent pas.";
                }
            }

            if (empty($errors)) {
                $_SESSION['success'] = "Utilisateur modifié avec succès.";
                header('Location: users_list.php');
                exit;
            }
        } else {
            $errors[] = "Erreur lors de la modification.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Modifier Utilisateur - SkillBridge Admin</title>

  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Noto+Sans:wght@400;600;700&display=swap" rel="stylesheet">

  <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">

  <div id="wrapper">

    <!-- Sidebar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
      <a class="sidebar-brand d-flex align-items-center justify-content-center" href="#">
        <div class="sidebar-brand-text mx-3">SkillBridge</div>
      </a>
      <hr class="sidebar-divider my-0">
      <li class="nav-item">
        <a class="nav-link" href="index.html"><i class="fas fa-fw fa-tachometer-alt"></i><span>Dashboard</span></a>
      </li>
      <hr class="sidebar-divider">
      <li class="nav-item active">
        <a class="nav-link" href="users_list.php"><i class="fas fa-fw fa-users"></i><span>Utilisateurs</span></a>
      </li>
      <hr class="sidebar-divider d-none d-md-block">
      <li class="nav-item">
        <a class="nav-link" href="../../controller/utilisateurcontroller.php?action=logout">
          <i class="fas fa-fw fa-sign-out-alt"></i><span>Déconnexion</span>
        </a>
      </li>
    </ul>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">

        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
          <span class="navbar-text ml-auto mr-3">
            <i class="fas fa-user-circle mr-1"></i> <?= htmlspecialchars($_SESSION['user_nom']) ?>
          </span>
        </nav>

        <!-- Main Content -->
        <div class="container-fluid">

          <div class="d-flex align-items-center mb-4">
            <a href="users_list.php" class="btn btn-secondary btn-sm mr-3">
              <i class="fas fa-arrow-left"></i> Retour
            </a>
            <h1 class="h3 mb-0 text-gray-800">Modifier l'utilisateur</h1>
          </div>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <?php foreach ($errors as $err): ?>
                <div><?= htmlspecialchars($err) ?></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <div class="card shadow mb-4">
            <div class="card-header py-3">
              <h6 class="m-0 font-weight-bold text-primary">Informations de l'utilisateur</h6>
            </div>
            <div class="card-body">

              <form id="editForm" action="edit_user.php?id=<?= $utilisateur['id'] ?>" method="POST" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="id" value="<?= htmlspecialchars($utilisateur['id']) ?>">

                <div class="row">

                  <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold">Nom <span class="text-danger">*</span></label>
                    <input type="text" name="nom" id="nom" class="form-control" value="<?= htmlspecialchars($utilisateur['nom']) ?>">
                    <div id="nom-error" class="text-danger mt-1" style="font-size:0.85rem; display:none;"></div>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold">Prénom <span class="text-danger">*</span></label>
                    <input type="text" name="prenom" id="prenom" class="form-control" value="<?= htmlspecialchars($utilisateur['prenom']) ?>">
                    <div id="prenom-error" class="text-danger mt-1" style="font-size:0.85rem; display:none;"></div>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold">Email <span class="text-danger">*</span></label>
                    <input type="text" name="email" id="email" class="form-control" value="<?= htmlspecialchars($utilisateur['email']) ?>">
                    <div id="email-error" class="text-danger mt-1" style="font-size:0.85rem; display:none;"></div>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold">Téléphone</label>
                    <input type="text" name="telephone" id="telephone" class="form-control" value="<?= htmlspecialchars($utilisateur['telephone'] ?? '') ?>">
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold">Rôle <span class="text-danger">*</span></label>
                    <select name="role" id="role" class="form-control">
                      <option value="freelancer" <?= $utilisateur['role'] === 'freelancer' ? 'selected' : '' ?>>Freelancer</option>
                      <option value="client"     <?= $utilisateur['role'] === 'client'     ? 'selected' : '' ?>>Client</option>
                      <option value="admin"      <?= $utilisateur['role'] === 'admin'      ? 'selected' : '' ?>>Admin</option>
                    </select>
                    <div id="role-error" class="text-danger mt-1" style="font-size:0.85rem; display:none;"></div>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold">Photo de profil</label>
                    <input type="file" name="photo" class="form-control-file" accept="image/*">
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold">Nouveau mot de passe</label>
                    <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Laisser vide pour ne pas changer">
                    <div id="pwd-error" class="text-danger mt-1" style="font-size:0.85rem; display:none;"></div>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold">Confirmer mot de passe</label>
                    <input type="password" name="confirm_new_password" id="confirm_new_password" class="form-control" placeholder="Répétez le nouveau mot de passe">
                    <div id="confirmpwd-error" class="text-danger mt-1" style="font-size:0.85rem; display:none;"></div>
                  </div>

                </div>

                <div class="text-center mt-3">
                  <button type="submit" class="btn btn-primary px-5">
                    <i class="fas fa-save mr-2"></i> Enregistrer
                  </button>
                  <a href="users_list.php" class="btn btn-secondary px-5 ml-2">Annuler</a>
                </div>

              </form>

            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="js/sb-admin-2.min.js"></script>

  <script>
    document.getElementById('editForm').addEventListener('submit', function(e) {

      let valid = true;

      ['nom','prenom','email','role'].forEach(function(id) {
        const field = document.getElementById(id);
        if (field) field.classList.remove('is-invalid', 'is-valid');
      });
      ['nom-error','prenom-error','email-error','role-error','pwd-error','confirmpwd-error'].forEach(function(id) {
        const el = document.getElementById(id);
        if (el) { el.textContent = ''; el.style.display = 'none'; }
      });

      function showError(fieldId, errorId, msg) {
        const field = document.getElementById(fieldId);
        const err   = document.getElementById(errorId);
        if (field) field.classList.add('is-invalid');
        if (err)   { err.textContent = msg; err.style.display = 'block'; }
        valid = false;
      }

      const nom    = document.getElementById('nom').value.trim();
      const prenom = document.getElementById('prenom').value.trim();
      const email  = document.getElementById('email').value.trim();
      const role   = document.getElementById('role').value;
      const pwd    = document.getElementById('new_password').value;
      const cpwd   = document.getElementById('confirm_new_password').value;
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (nom === '')    showError('nom',    'nom-error',    'Le nom est obligatoire.');
      if (prenom === '') showError('prenom', 'prenom-error', 'Le prénom est obligatoire.');
      if (email === '')  showError('email',  'email-error',  "L'email est obligatoire.");
      else if (!emailRegex.test(email)) showError('email', 'email-error', 'Format invalide.');
      if (role === '')   showError('role',   'role-error',   'Le rôle est obligatoire.');
      if (pwd !== '' && pwd.length < 8) showError('new_password', 'pwd-error', 'Minimum 8 caractères.');
      if (pwd !== '' && pwd !== cpwd)   showError('confirm_new_password', 'confirmpwd-error', 'Les mots de passe ne correspondent pas.');

      if (!valid) e.preventDefault();
    });
  </script>

</body>
</html>