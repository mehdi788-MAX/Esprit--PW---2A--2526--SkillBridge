<?php
session_start();
require_once __DIR__ . '/../config.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $_SESSION['error'] = "Token invalide.";
    header('Location: http://localhost/skillbridgeutilisateur/view/frontoffice/EasyFolio/login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, nom, prenom, role FROM utilisateurs WHERE verification_token = ? AND is_verified = 0 LIMIT 1");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "Lien de vérification invalide ou déjà utilisé.";
    header('Location: http://localhost/skillbridgeutilisateur/view/frontoffice/EasyFolio/login.php');
    exit;
}

// Marquer comme vérifié
$pdo->prepare("UPDATE utilisateurs SET is_verified = 1, verification_token = NULL WHERE id = ?")
    ->execute([$user['id']]);

// Connecter automatiquement
$_SESSION['user_id']   = $user['id'];
$_SESSION['user_nom']  = $user['prenom'] . ' ' . $user['nom'];
$_SESSION['user_role'] = $user['role'];

$_SESSION['success'] = "✅ Email vérifié ! Bienvenue sur SkillBridge.";

// Rediriger selon le rôle
if ($user['role'] === 'admin') {
    header('Location: http://localhost/skillbridgeutilisateur/view/backoffice/users_list.php');
} else {
    header('Location: http://localhost/skillbridgeutilisateur/view/frontoffice/EasyFolio/profil.php');
}
exit;