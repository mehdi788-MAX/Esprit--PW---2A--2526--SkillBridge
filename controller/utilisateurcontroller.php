<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
            header('Location: http://localhost/skillbridgeutilisateur/view/frontoffice/EasyFolio/login.php');
        } else {
            $_SESSION['error'] = "Erreur lors de la création du compte.";
            header('Location: http://localhost/skillbridgeutilisateur/view/frontoffice/EasyFolio/register.php');
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
            header('Location: http://localhost/skillbridgeutilisateur/view/frontoffice/EasyFolio/login.php');
            exit;
        }

        // ── LIMITATION TENTATIVES ──
        $key = 'login_attempts_' . md5($_SERVER['REMOTE_ADDR'] . $email);

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'time' => time()];
        }

        $attempts = &$_SESSION[$key];

        // Reset si 15 minutes écoulées
        if (time() - $attempts['time'] > 900) {
            $attempts = ['count' => 0, 'time' => time()];
        }

        // Bloqué ?
        if ($attempts['count'] >= 5) {
            $remaining = 900 - (time() - $attempts['time']);
            $minutes   = ceil($remaining / 60);
            $_SESSION['error'] = "Trop de tentatives échouées. Réessayez dans {$minutes} minute(s).";
            header('Location: http://localhost/skillbridgeutilisateur/view/frontoffice/EasyFolio/login.php');
            exit;
        }

        $utilisateur->email = $email;
        $user = $utilisateur->readByEmail();

        if ($user && password_verify($password, $user['password'])) {

            // Reset tentatives après succès
            unset($_SESSION[$key]);

            if (!$user['is_active']) {
                $_SESSION['error'] = "desactivated";
                header('Location: http://localhost/skillbridgeutilisateur/view/frontoffice/EasyFolio/login.php');
                exit;
            }

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nom']  = $user['prenom'] . ' ' . $user['nom'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: http://localhost/skillbridgeutilisateur/view/backoffice/users_list.php');
            } else {
                header('Location: http://localhost/skillbridgeutilisateur/view/frontoffice/EasyFolio/profil.php');
            }

        } else {
            // Incrémenter tentatives
            $attempts['count']++;
            if ($attempts['count'] === 1) {
                $attempts['time'] = time();
            }

            $restantes = 5 - $attempts['count'];

            if ($restantes > 0) {
                $_SESSION['error'] = "Email ou mot de passe incorrect. Il vous reste {$restantes} tentative(s).";
            } else {
                $_SESSION['error'] = "Trop de tentatives échouées. Réessayez dans 15 minute(s).";
            }

            header('Location: http://localhost/skillbridgeutilisateur/view/frontoffice/EasyFolio/login.php');
        }
        exit;

    // =====================
    // MODIFIER PROFIL
    // =====================
    case 'update_profile':
        if (!isset($_SESSION['user_id'])) {
            header('Location: http://localhost/skillbridgeutilisateur/view/frontoffice/EasyFolio/login.php');
            exit;
        }

        $utilisateur->id        = $_POST['id'];
        $utilisateur->nom       = trim($_POST['nom'] ?? '');
        $utilisateur->prenom    = trim($_POST['prenom'] ?? '');
        $utilisateur->email     = trim($_POST['email'] ?? '');
        $utilisateur->telephone = trim($_POST['telephone'] ?? '');
        $utilisateur->photo     = $_POST['old_photo'] ?? '';

        if (!empty($_FILES['photo']['name'])) {
            $upload_dir = '../view/frontoffice/EasyFolio/assets/img/profile/';
            $ext        = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename   = 'user_' . $utilisateur->id . '_' . time() . '.' . $ext;
            $allowed    = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array(strtolower($ext), $allowed)) {
                $oldPath = $upload_dir . $_POST['old_photo'];
                if (!empty($_POST['old_photo']) && file_exists($oldPath)) {
                    unlink($oldPath);
                }
                move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename);
                $utilisateur->photo = $filename;
            }
        }

        $profil->utilisateur_id = $utilisateur->id;
        $profil->bio            = trim($_POST['bio'] ?? '');
        $profil->competences    = trim($_POST['competences'] ?? '');
        $profil->localisation   = trim($_POST['localisation'] ?? '');
        $profil->site_web       = trim($_POST['site_web'] ?? '');
        $profil->update();

        if ($utilisateur->update()) {
            if (!empty($_POST['new_password']) && $_POST['new_password'] === $_POST['confirm_new_password']) {
                if (strlen($_POST['new_password']) >= 8) {
                    $utilisateur->password = $_POST['new_password'];
                    $utilisateur->updatePassword();
                }
            }
            $_SESSION['success'] = "Profil mis à jour avec succès.";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour.";
        }

        header('Location: http://localhost/skillbridgeutilisateur/view/frontoffice/EasyFolio/profil.php');
        exit;

    // =====================
    // TOGGLE ACTIVE
    // =====================
    case 'toggle_active':
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: http://localhost/skillbridgeutilisateur/view/frontoffice/EasyFolio/login.php');
            exit;
        }

        $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$id) {
            $_SESSION['error'] = "ID invalide.";
            header('Location: http://localhost/skillbridgeutilisateur/view/backoffice/users_list.php');
            exit;
        }

        $utilisateur->id        = $id;
        $utilisateur->is_active = intval($_POST['is_active'] ?? 1);

        if ($utilisateur->toggleActive()) {
            $_SESSION['success'] = $utilisateur->is_active
                ? "Compte activé avec succès."
                : "Compte désactivé avec succès.";
        } else {
            $_SESSION['error'] = "Erreur lors de la modification.";
        }

        header('Location: http://localhost/skillbridgeutilisateur/view/backoffice/users_list.php');
        exit;

    // =====================
    // SUPPRIMER UTILISATEUR
    // =====================
    case 'delete':
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: http://localhost/skillbridgeutilisateur/view/frontoffice/EasyFolio/login.php');
            exit;
        }

        $id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
        if (!$id) {
            $_SESSION['error'] = "ID invalide.";
            header('Location: http://localhost/skillbridgeutilisateur/view/backoffice/users_list.php');
            exit;
        }

        $utilisateur->id        = $id;
        $profil->utilisateur_id = $id;
        $profil->delete();

        if ($utilisateur->delete()) {
            $_SESSION['success'] = "Utilisateur supprimé avec succès.";
        } else {
            $_SESSION['error'] = "Erreur lors de la suppression.";
        }

        header('Location: http://localhost/skillbridgeutilisateur/view/backoffice/users_list.php');
        exit;

    // =====================
    // DÉCONNEXION
    // =====================
    case 'logout':
        session_destroy();
        header('Location: http://localhost/skillbridgeutilisateur/view/frontoffice/EasyFolio/login.php');
        exit;

    default:
        header('Location: http://localhost/skillbridgeutilisateur/view/frontoffice/EasyFolio/login.php');
        exit;
}
?>