<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    // If a non-admin user is logged in, log them out of the admin context only —
    // they may still be authenticated for the frontoffice. We just block backoffice access.
    if (isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "Cet espace est réservé aux administrateurs.";
    } else {
        $_SESSION['error'] = "Veuillez vous connecter pour accéder au backoffice.";
    }
    header('Location: ' . backoffice_url() . '/login.php');
    exit;
}