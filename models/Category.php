<?php
// models/Category.php
<<<<<<< HEAD
// Gère toutes les opérations sur la table "categories"

class Category {
    private $conn;
    private $table = "categories";

=======
// Ce fichier représente la table "categories" dans la base de données

class Category {
    // La connexion à la base de données
    private $conn;
    private $table = "categories";

    // Propriétés de la catégorie
>>>>>>> c266bb3be7031baaa66b638b43aaf96cbdcebd0d
    public $id;
    public $name;
    public $created_at;

<<<<<<< HEAD
=======
    // Le constructeur reçoit la connexion
>>>>>>> c266bb3be7031baaa66b638b43aaf96cbdcebd0d
    public function __construct($db) {
        $this->conn = $db;
    }

<<<<<<< HEAD
    // READ — Récupérer toutes les catégories
=======
    // Récupérer toutes les catégories
>>>>>>> c266bb3be7031baaa66b638b43aaf96cbdcebd0d
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY name ASC";
        $stmt  = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
<<<<<<< HEAD

    // READ — Récupérer une catégorie par ID
    public function getById() {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        return $stmt;
    }

    // CREATE — Ajouter une nouvelle catégorie
    public function create() {
        $query = "INSERT INTO " . $this->table . " (name) VALUES (:name)";
        $stmt  = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $stmt->bindParam(":name", $this->name);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // UPDATE — Modifier une catégorie existante
    public function update() {
        $query = "UPDATE " . $this->table . " SET name = :name WHERE id = :id";
        $stmt  = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->id   = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":id",   $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // DELETE — Supprimer une catégorie
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt  = $this->conn->prepare($query);

        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
=======
>>>>>>> c266bb3be7031baaa66b638b43aaf96cbdcebd0d
}
?>
