<?php
session_start();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$user_id   = intval($_POST['user_id'] ?? 0);
$user_nom  = $_POST['user_nom'] ?? '';
$user_role = $_POST['user_role'] ?? '';

if (!$user_id) {
    echo json_encode(['success' => false]);
    exit;
}

$_SESSION['user_id']   = $user_id;
$_SESSION['user_nom']  = $user_nom;
$_SESSION['user_role'] = $user_role;

$redirect = $user_role === 'admin'
    ? '' . base_url() . '/view/backoffice/users_list.php'
    : '' . base_url() . '/view/frontoffice/EasyFolio/profil.php';

echo json_encode(['success' => true, 'redirect' => $redirect]);
exit;