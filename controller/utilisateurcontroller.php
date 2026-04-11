<?php

session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/utilisateur.php';
require_once __DIR__ . '/../model/profil.php';

$utilisateur = new Utilisateur($pdo);
$profil      = new Profil($pdo);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // =====================
    // INSCRIPTION
    // =====================
    case 'register':
        $nom              = trim($_POST['nom'] ?? '');
        $prenom           = trim($_POST['prenom'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role             = $_POST['role'] ?? '';
        $telephone        = trim($_POST['telephone'] ?? '');

        if (empty($nom) || empty($prenom) || empty($email) || empty($password) || empty($role)) {
            $_SESSION['error'] = "Tous les champs obligatoires doivent être remplis.";
            header('Location: ../view/frontoffice/EasyFolio/register.php');
            exit;
        }

        if ($password !== $confirm_password) {
            $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
            header('Location: ../view/frontoffice/EasyFolio/register.php');
            exit;
        }

        if (strlen($password) < 8) {
            $_SESSION['error'] = "Le mot de passe doit contenir au moins 8 caractères.";
            header('Location: ../view/frontoffice/EasyFolio/register.php');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Adresse email invalide.";
            header('Location: ../view/frontoffice/EasyFolio/register.php');
            exit;
        }

        $utilisateur->email = $email;
        if ($utilisateur->emailExists()) {
            $_SESSION['error'] = "Cette adresse email est déjà utilisée.";
            header('Location: ../view/frontoffice/EasyFolio/register.php');
            exit;
        }

        $utilisateur->nom       = $nom;
        $utilisateur->prenom    = $prenom;
        $utilisateur->email     = $email;
        $utilisateur->password  = $password;
        $utilisateur->role      = $role;
        $utilisateur->telephone = $telephone;

        if ($utilisateur->create()) {
            $profil->utilisateur_id = $utilisateur->id;
            $profil->bio            = '';
            $profil->competences    = '';
            $profil->localisation   = '';
            $profil->site_web       = '';
            $profil->create();

            $_SESSION['success'] = "Compte créé avec succès !";
            header('Location: ../view/frontoffice/EasyFolio/login.php');
        } else {
            $_SESSION['error'] = "Erreur lors de la création du compte.";
            header('Location: ../view/frontoffice/EasyFolio/register.php');
        }
        exit;

    // =====================
    // CONNEXION
    // =====================
    case 'login':
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['error'] = "Email et mot de passe sont obligatoires.";
            header('Location: ../view/frontoffice/EasyFolio/login.php');
            exit;
        }

        $utilisateur->email = $email;
        $user = $utilisateur->readByEmail();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nom']  = $user['prenom'] . ' ' . $user['nom'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: ../view/backoffice/users_list.php');
            } else {
                header('Location: ../view/frontoffice/EasyFolio/profil.php');
            }
        } else {
            $_SESSION['error'] = "Email ou mot de passe incorrect.";
            header('Location: ../view/frontoffice/EasyFolio/login.php');
        }
        exit;

    // =====================
    // MODIFIER PROFIL
    // =====================
    case 'update_profile':
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../view/frontoffice/EasyFolio/login.php');
            exit;
        }

        $utilisateur->id        = $_POST['id'];
        $utilisateur->nom       = trim($_POST['nom'] ?? '');
        $utilisateur->prenom    = trim($_POST['prenom'] ?? '');
        $utilisateur->email     = trim($_POST['email'] ?? '');
        $utilisateur->telephone = trim($_POST['telephone'] ?? '');
        $utilisateur->photo     = '';

        if (!empty($_FILES['photo']['name'])) {
            $upload_dir = '../view/frontoffice/EasyFolio/assets/img/profile/';
            $ext        = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename   = 'user_' . $utilisateur->id . '_' . time() . '.' . $ext;
            $allowed    = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array(strtolower($ext), $allowed)) {
                move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename);
                $utilisateur->photo = $filename;
            }
        }

        $profil->utilisateur_id = $utilisateur->id;
        $profil->bio            = trim($_POST['bio'] ?? '');
        $profil->competences    = '';
        $profil->localisation   = '';
        $profil->site_web       = '';
        $profil->update();

        if ($utilisateur->update()) {
            if (!empty($_POST['new_password']) && $_POST['new_password'] === $_POST['confirm_new_password']) {
                $utilisateur->password = $_POST['new_password'];
                $utilisateur->updatePassword();
            }
            $_SESSION['success'] = "Profil mis à jour avec succès.";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour.";
        }

        header('Location: ../view/frontoffice/EasyFolio/profil.php');
        exit;

    // =====================
    // SUPPRIMER UTILISATEUR
    // =====================
    case 'delete':
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: ../view/frontoffice/EasyFolio/login.php');
            exit;
        }

        $utilisateur->id        = $_GET['id'];
        $profil->utilisateur_id = $utilisateur->id;
        $profil->delete();

        if ($utilisateur->delete()) {
            $_SESSION['success'] = "Utilisateur supprimé avec succès.";
        } else {
            $_SESSION['error'] = "Erreur lors de la suppression.";
        }

        header('Location: ../view/backoffice/users_list.php');
        exit;

    // =====================
    // DÉCONNEXION
    // =====================
    case 'logout':
        session_destroy();
        header('Location: ../view/frontoffice/EasyFolio/login.php');
        exit;

    default:
        header('Location: ../view/frontoffice/EasyFolio/login.php');
        exit;
}
?>
