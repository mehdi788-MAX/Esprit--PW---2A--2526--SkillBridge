<?php
// Garde-fou admin pour toutes les pages du chat backoffice.
// Si non admin, redirige vers le login backoffice (séparé du frontoffice).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    if (isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "Cet espace est réservé aux administrateurs.";
    } else {
        $_SESSION['error'] = "Veuillez vous connecter pour accéder au backoffice.";
    }
    header('Location: ' . backoffice_url() . '/login.php');
    exit;
}

$currentUserId   = (int)$_SESSION['user_id'];
$currentUserRole = $_SESSION['user_role'] ?? '';
$currentUserName = $_SESSION['user_nom']  ?? 'Admin';
