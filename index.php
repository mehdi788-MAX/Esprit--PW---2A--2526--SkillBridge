<?php
// index.php
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
        break;
}
?>
