<?php
// controllers/TestController.php

require_once "config/Database.php";
require_once "models/Test.php";
require_once "models/Category.php";

class TestController {

    private $db;
    private $test;
    private $category;

    public function __construct() {
        $database       = new Database();
        $this->db       = $database->getConnection();
        $this->test     = new Test($this->db);
        $this->category = new Category($this->db);
    }

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
    public function index() {
        $tests      = $this->test->getAll();
        $categories = $this->category->getAll();
        require_once "views/backoffice/index.php";
    }

    // Formulaire d'ajout de test
    public function create() {
        $categories = $this->category->getAll();
        require_once "views/backoffice/create.php";
    }

    // Enregistrer un nouveau test (POST)
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

    // Formulaire de modification de test
    public function edit() {
        $this->test->id = $_GET['id'];
        $stmt           = $this->test->getById();
        $test_data      = $stmt->fetch(PDO::FETCH_ASSOC);
        $categories     = $this->category->getAll();
        require_once "views/backoffice/edit.php";
    }

    // Enregistrer les modifications d'un test (POST)
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

    // Supprimer un test
    public function delete() {
        $this->test->id = $_GET['id'];

        if ($this->test->delete()) {
            header("Location: index.php?action=index&success=suppression");
        } else {
            header("Location: index.php?action=index&error=1");
        }
        exit();
    }

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

        require_once "views/frontoffice/index.php";
    }
}
?>
