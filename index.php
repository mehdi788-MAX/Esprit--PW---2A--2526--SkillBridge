<?php
// index.php
<<<<<<< HEAD
// Point d'entrée de l'application

require_once "controllers/TestController.php";
require_once "controllers/CategoryController.php";

$action = isset($_GET['action']) ? $_GET['action'] : 'frontoffice';

$testController     = new TestController();
$categoryController = new CategoryController();

switch ($action) {
    // --- TESTS ---
    case 'index':
        $testController->index();
        break;
    case 'create':
        $testController->create();
        break;
    case 'store':
        $testController->store();
        break;
    case 'edit':
        $testController->edit();
        break;
    case 'update':
        $testController->update();
        break;
    case 'delete':
        $testController->delete();
        break;

    // --- CATEGORIES ---
    case 'cat_store':
        $categoryController->store();
        break;
    case 'cat_update':
        $categoryController->update();
        break;
    case 'cat_delete':
        $categoryController->delete();
        break;

    // --- FRONTOFFICE ---
    case 'frontoffice':
    default:
        $testController->frontoffice();
=======
// Point d'entrée de l'application — dirige vers le bon controller/action

require_once "controllers/TestController.php";

// Lire l'action dans l'URL (ex: index.php?action=create)
$action = isset($_GET['action']) ? $_GET['action'] : 'frontoffice';

// Créer le controller
$controller = new TestController();

// Appeler la bonne méthode selon l'action
switch ($action) {
    case 'index':
        $controller->index();
        break;
    case 'create':
        $controller->create();
        break;
    case 'store':
        $controller->store();
        break;
    case 'edit':
        $controller->edit();
        break;
    case 'update':
        $controller->update();
        break;
    case 'delete':
        $controller->delete();
        break;
    case 'frontoffice':
    default:
        $controller->frontoffice();
>>>>>>> c266bb3be7031baaa66b638b43aaf96cbdcebd0d
        break;
}
?>
