<?php
// controllers/TestController.php
// Ce fichier reçoit les actions de l'utilisateur et appelle le bon Model et la bonne View

// Inclure la connexion et les modèles
require_once "config/Database.php";
require_once "models/Test.php";
require_once "models/Category.php";

class TestController {

    private $db;
    private $test;
    private $category;

    // Constructeur : prépare la connexion et les objets
    public function __construct() {
        $database       = new Database();
        $this->db       = $database->getConnection();
        $this->test     = new Test($this->db);
        $this->category = new Category($this->db);
    }

    // -------------------------------------------------------
    // Afficher tous les tests (BackOffice)
    // -------------------------------------------------------
    public function index() {
        $tests      = $this->test->getAll();
        $categories = $this->category->getAll();
        require_once "views/backoffice/index.php";
    }

    // -------------------------------------------------------
    // Afficher le formulaire d'ajout
    // -------------------------------------------------------
    public function create() {
        $categories = $this->category->getAll();
        require_once "views/backoffice/create.php";
    }

    // -------------------------------------------------------
    // Enregistrer le nouveau test (POST)
    // -------------------------------------------------------
    public function store() {
        $this->test->title         = $_POST['title'];
        $this->test->category_id   = $_POST['category_id'];
        $this->test->duration      = $_POST['duration'];
        $this->test->level         = $_POST['level'];
        $this->test->average_score = $_POST['average_score'];

        if ($this->test->create()) {
            header("Location: index.php?action=index&success=ajout");
        } else {
            header("Location: index.php?action=create&error=1");
        }
        exit();
    }

    // -------------------------------------------------------
    // Afficher le formulaire de modification
    // -------------------------------------------------------
    public function edit() {
        $this->test->id = $_GET['id'];
        $stmt           = $this->test->getById();
        $test_data      = $stmt->fetch(PDO::FETCH_ASSOC);
        $categories     = $this->category->getAll();
        require_once "views/backoffice/edit.php";
    }

    // -------------------------------------------------------
    // Enregistrer les modifications (POST)
    // -------------------------------------------------------
    public function update() {
        $this->test->id            = $_POST['id'];
        $this->test->title         = $_POST['title'];
        $this->test->category_id   = $_POST['category_id'];
        $this->test->duration      = $_POST['duration'];
        $this->test->level         = $_POST['level'];
        $this->test->average_score = $_POST['average_score'];

        if ($this->test->update()) {
            header("Location: index.php?action=index&success=modif");
        } else {
            header("Location: index.php?action=edit&id=" . $_POST['id'] . "&error=1");
        }
        exit();
    }

    // -------------------------------------------------------
    // Supprimer un test
    // -------------------------------------------------------
    public function delete() {
        $this->test->id = $_GET['id'];

        if ($this->test->delete()) {
            header("Location: index.php?action=index&success=suppression");
        } else {
            header("Location: index.php?action=index&error=1");
        }
        exit();
    }

    // -------------------------------------------------------
    // Afficher la vue FrontOffice (pour les clients)
    // -------------------------------------------------------
    public function frontoffice() {
        $tests      = $this->test->getAll();
        $categories = $this->category->getAll();
        require_once "views/frontoffice/index.php";
    }
}
?>
