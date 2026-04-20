<?php
// controllers/TestController.php
<<<<<<< HEAD

=======
// Ce fichier reçoit les actions de l'utilisateur et appelle le bon Model et la bonne View

// Inclure la connexion et les modèles
>>>>>>> c266bb3be7031baaa66b638b43aaf96cbdcebd0d
require_once "config/Database.php";
require_once "models/Test.php";
require_once "models/Category.php";

class TestController {

    private $db;
    private $test;
    private $category;

<<<<<<< HEAD
=======
    // Constructeur : prépare la connexion et les objets
>>>>>>> c266bb3be7031baaa66b638b43aaf96cbdcebd0d
    public function __construct() {
        $database       = new Database();
        $this->db       = $database->getConnection();
        $this->test     = new Test($this->db);
        $this->category = new Category($this->db);
    }

<<<<<<< HEAD
    // --- GETTERS ---
    public function getDb() {
        return $this->db;
    }

    public function getTest() {
        return $this->test;
    }

    public function getCategory() {
        return $this->category;
    }

    // --- SETTERS ---
    public function setDb($db) {
        $this->db = $db;
    }

    public function setTest($test) {
        $this->test = $test;
    }

    public function setCategory($category) {
        $this->category = $category;
    }

    // Backoffice — page unifiée (tests + catégories)
=======
    // -------------------------------------------------------
    // Afficher tous les tests (BackOffice)
    // -------------------------------------------------------
>>>>>>> c266bb3be7031baaa66b638b43aaf96cbdcebd0d
    public function index() {
        $tests      = $this->test->getAll();
        $categories = $this->category->getAll();
        require_once "views/backoffice/index.php";
    }

<<<<<<< HEAD
    // Formulaire d'ajout de test
=======
    // -------------------------------------------------------
    // Afficher le formulaire d'ajout
    // -------------------------------------------------------
>>>>>>> c266bb3be7031baaa66b638b43aaf96cbdcebd0d
    public function create() {
        $categories = $this->category->getAll();
        require_once "views/backoffice/create.php";
    }

<<<<<<< HEAD
    // Enregistrer un nouveau test (POST)
=======
    // -------------------------------------------------------
    // Enregistrer le nouveau test (POST)
    // -------------------------------------------------------
>>>>>>> c266bb3be7031baaa66b638b43aaf96cbdcebd0d
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

<<<<<<< HEAD
    // Formulaire de modification de test
=======
    // -------------------------------------------------------
    // Afficher le formulaire de modification
    // -------------------------------------------------------
>>>>>>> c266bb3be7031baaa66b638b43aaf96cbdcebd0d
    public function edit() {
        $this->test->id = $_GET['id'];
        $stmt           = $this->test->getById();
        $test_data      = $stmt->fetch(PDO::FETCH_ASSOC);
        $categories     = $this->category->getAll();
        require_once "views/backoffice/edit.php";
    }

<<<<<<< HEAD
    // Enregistrer les modifications d'un test (POST)
=======
    // -------------------------------------------------------
    // Enregistrer les modifications (POST)
    // -------------------------------------------------------
>>>>>>> c266bb3be7031baaa66b638b43aaf96cbdcebd0d
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

<<<<<<< HEAD
    // Supprimer un test
=======
    // -------------------------------------------------------
    // Supprimer un test
    // -------------------------------------------------------
>>>>>>> c266bb3be7031baaa66b638b43aaf96cbdcebd0d
    public function delete() {
        $this->test->id = $_GET['id'];

        if ($this->test->delete()) {
            header("Location: index.php?action=index&success=suppression");
        } else {
            header("Location: index.php?action=index&error=1");
        }
        exit();
    }

<<<<<<< HEAD
    // Frontoffice — vue client avec filtres
    public function frontoffice() {
        $all_tests_raw = $this->test->getAll()->fetchAll(PDO::FETCH_ASSOC);
        $categories    = $this->category->getAll()->fetchAll(PDO::FETCH_ASSOC);

        $filter_cat   = isset($_GET['cat'])   ? $_GET['cat']   : '';
        $filter_level = isset($_GET['level']) ? $_GET['level'] : '';

        $all_tests = array_filter($all_tests_raw, function($test) use ($filter_cat, $filter_level) {
            $match_cat   = ($filter_cat   === '' || $test['category_id'] == $filter_cat);
            $match_level = ($filter_level === '' || $test['level'] == $filter_level);
            return $match_cat && $match_level;
        });

=======
    // -------------------------------------------------------
    // Afficher la vue FrontOffice (pour les clients)
    // -------------------------------------------------------
    public function frontoffice() {
        $tests      = $this->test->getAll();
        $categories = $this->category->getAll();
>>>>>>> c266bb3be7031baaa66b638b43aaf96cbdcebd0d
        require_once "views/frontoffice/index.php";
    }
}
?>
