<?php
// index.php
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
        break;
}
?>
