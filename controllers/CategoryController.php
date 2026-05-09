<?php
// controllers/CategoryController.php

require_once "config/Database.php";
require_once "models/Category.php";

class CategoryController {

    private $db;
    private $category;

    public function __construct() {
        $database       = new Database();
        $this->db       = $database->getConnection();
        $this->category = new Category($this->db);
    }

    // --- GETTERS ---
    public function getDb() {
        return $this->db;
    }

    public function getCategory() {
        return $this->category;
    }

    // --- SETTERS ---
    public function setDb($db) {
        $this->db = $db;
    }

    public function setCategory($category) {
        $this->category = $category;
    }

    // CREATE — Enregistrer une nouvelle catégorie (POST)
    public function store() {
        $this->category->name = $_POST['cat_name'];

        if ($this->category->create()) {
            header("Location: index.php?action=index&success=cat_ajout");
        } else {
            header("Location: index.php?action=index&error=cat_1");
        }
        exit();
    }

    // UPDATE — Enregistrer les modifications d'une catégorie (POST)
    public function update() {
        $this->category->id   = $_POST['cat_id'];
        $this->category->name = $_POST['cat_name'];

        if ($this->category->update()) {
            header("Location: index.php?action=index&success=cat_modif");
        } else {
            header("Location: index.php?action=index&error=cat_1");
        }
        exit();
    }

    // DELETE — Supprimer une catégorie
    public function delete() {
        $this->category->id = $_GET['id'];

        if ($this->category->delete()) {
            header("Location: index.php?action=index&success=cat_suppression");
        } else {
            header("Location: index.php?action=index&error=cat_1");
        }
        exit();
    }
}
?>
