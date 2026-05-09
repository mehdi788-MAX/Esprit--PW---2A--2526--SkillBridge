<?php
// =====================================================
// SkillBridge — Admin shortcut
// -----------------------------------------------------
// /admin  ou  /admin/  →  view/backoffice/login.php
// Si une session admin est déjà active, on saute la
// connexion et on file directement au tableau de bord.
// =====================================================
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$alreadyAdmin = !empty($_SESSION['admin_id']) || (($_SESSION['user_role'] ?? '') === 'admin');
$target = $alreadyAdmin
    ? backoffice_url() . '/dashbord.php'
    : backoffice_url() . '/login.php';

header('Location: ' . $target, true, 302);
exit;
