<?php
// models/Test.php
// Ce fichier gère toutes les opérations sur la table "tests"

class Test {
    // La connexion à la base de données
    private $conn;
    private $table = "tests";

    // Propriétés du test (correspondent aux colonnes de la table)
    public $id;
    public $title;
    public $category_id;
    public $duration;
    public $level;
    public $average_score;
    public $created_at;

    // Le constructeur reçoit la connexion
    public function __construct($db) {
        $this->conn = $db;
    }

    // -------------------------------------------------------
    // READ — Récupérer tous les tests (avec le nom de catégorie)
    // -------------------------------------------------------
    public function getAll() {
        $query = "SELECT t.*, c.name AS category_name
                  FROM " . $this->table . " t
                  LEFT JOIN categories c ON t.category_id = c.id
                  ORDER BY t.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // -------------------------------------------------------
    // READ — Récupérer un seul test par son ID
    // -------------------------------------------------------
    public function getById() {
        $query = "SELECT t.*, c.name AS category_name
                  FROM " . $this->table . " t
                  LEFT JOIN categories c ON t.category_id = c.id
                  WHERE t.id = :id
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        return $stmt;
    }

    // -------------------------------------------------------
<<<<<<< HEAD
    // ajouter un nouveau test
=======
    // CREATE — Ajouter un nouveau test
>>>>>>> c266bb3be7031baaa66b638b43aaf96cbdcebd0d
    // -------------------------------------------------------
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                  (title, category_id, duration, level, average_score)
                  VALUES (:title, :category_id, :duration, :level, :average_score)";

        $stmt = $this->conn->prepare($query);

        // Nettoyer les données avant insertion
        $this->title         = htmlspecialchars(strip_tags($this->title));
        $this->category_id   = htmlspecialchars(strip_tags($this->category_id));
        $this->duration      = htmlspecialchars(strip_tags($this->duration));
        $this->level         = htmlspecialchars(strip_tags($this->level));
        $this->average_score = htmlspecialchars(strip_tags($this->average_score));

        // Lier les valeurs
        $stmt->bindParam(":title",         $this->title);
        $stmt->bindParam(":category_id",   $this->category_id);
        $stmt->bindParam(":duration",      $this->duration);
        $stmt->bindParam(":level",         $this->level);
        $stmt->bindParam(":average_score", $this->average_score);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // -------------------------------------------------------
<<<<<<< HEAD
    // modifier un test existant
=======
    // UPDATE — Modifier un test existant
>>>>>>> c266bb3be7031baaa66b638b43aaf96cbdcebd0d
    // -------------------------------------------------------
    public function update() {
        $query = "UPDATE " . $this->table . "
                  SET title         = :title,
                      category_id   = :category_id,
                      duration      = :duration,
                      level         = :level,
                      average_score = :average_score
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Nettoyer les données
        $this->title         = htmlspecialchars(strip_tags($this->title));
        $this->category_id   = htmlspecialchars(strip_tags($this->category_id));
        $this->duration      = htmlspecialchars(strip_tags($this->duration));
        $this->level         = htmlspecialchars(strip_tags($this->level));
        $this->average_score = htmlspecialchars(strip_tags($this->average_score));
        $this->id            = htmlspecialchars(strip_tags($this->id));

        // Lier les valeurs
        $stmt->bindParam(":title",         $this->title);
        $stmt->bindParam(":category_id",   $this->category_id);
        $stmt->bindParam(":duration",      $this->duration);
        $stmt->bindParam(":level",         $this->level);
        $stmt->bindParam(":average_score", $this->average_score);
        $stmt->bindParam(":id",            $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // -------------------------------------------------------
<<<<<<< HEAD
    //  Supprimer un test
=======
    // DELETE — Supprimer un test
>>>>>>> c266bb3be7031baaa66b638b43aaf96cbdcebd0d
    // -------------------------------------------------------
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
}
?>
