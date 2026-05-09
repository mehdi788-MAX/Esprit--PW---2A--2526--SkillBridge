<?php
// =====================================================
// SkillBridge — Front-door router (project root)
// -----------------------------------------------------
// Redirects from the project root to the public-facing
// landing page (frontoffice). Examples :
//   http://localhost/skillbridge/        → view/frontoffice/EasyFolio/index.php
//   http://localhost:8000/                → view/frontoffice/EasyFolio/index.php
//
// L'admin a son propre raccourci :  /admin  →  view/backoffice/login.php
// (cf. /admin/index.php)
// =====================================================
require_once __DIR__ . '/config.php';

$target = frontoffice_url('EasyFolio') . '/index.php';
header('Location: ' . $target, true, 302);
exit;
