<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
// index.php
// Point d'entrée de l'application

// Configuration globale
require_once "config/credentials.php";

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
    case 'generate_ai':
        $testController->generateAI();
        break;
    case 'take_test':
        $testController->takeTest();
        break;
    case 'submit_test':
        $testController->submitTest();
        break;
    case 'export_pdf':
        $testController->exportPdf();
        break;
    case 'history':
        $testController->history();
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
