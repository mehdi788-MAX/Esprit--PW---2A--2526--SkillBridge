<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/utilisateur.php';

header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email requis.']);
    exit;
}

$utilisateur = new Utilisateur($pdo);
$utilisateur->email = $email;
$user = $utilisateur->readByEmail();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']);
    exit;
}

if (empty($user['photo'])) {
    echo json_encode(['success' => false, 'message' => 'Aucune photo de profil. Veuillez d\'abord uploader une photo dans votre profil.']);
    exit;
}

if (!$user['is_active']) {
    echo json_encode(['success' => false, 'message' => 'desactivated']);
    exit;
}

echo json_encode([
    'success'  => true,
    'photo'    => 'assets/img/profile/' . $user['photo'],
    'user_id'  => $user['id'],
    'user_nom' => $user['prenom'] . ' ' . $user['nom'],
    'user_role'=> $user['role'],
]);
exit;