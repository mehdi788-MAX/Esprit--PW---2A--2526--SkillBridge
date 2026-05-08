<?php
// Garde-fou de session pour toutes les pages du chat backoffice.
// Définit $currentUserId et $currentUserRole à partir de la session.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vous devez être connecté pour accéder au chat.";
    header('Location: ../../frontoffice/EasyFolio/login.php');
    exit;
}
$currentUserId   = (int)$_SESSION['user_id'];
$currentUserRole = $_SESSION['user_role'] ?? '';
$currentUserName = $_SESSION['user_nom'] ?? 'Utilisateur';
