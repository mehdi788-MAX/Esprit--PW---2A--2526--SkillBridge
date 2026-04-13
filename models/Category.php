<?php
// models/Category.php
// Ce fichier représente la table "categories" dans la base de données

class Category {
    // La connexion à la base de données
    private $conn;
    private $table = "categories";

    // Propriétés de la catégorie
    public $id;
    public $name;
    public $created_at;

    // Le constructeur reçoit la connexion
    public function __construct($db) {
        $this->conn = $db;
    }

    // Récupérer toutes les catégories
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY name ASC";
        $stmt  = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>
