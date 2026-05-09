<?php
// models/Question.php
// Modèle pour interagir avec la table 'questions'

class Question {
    private $conn;
    private $table = "questions";

    // Propriétés
    public $id;
    public $test_id;
    public $question;
    public $type;
    public $option_a;
    public $option_b;
    public $option_c;
    public $option_d;
    public $bonne_reponse;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Récupérer toutes les questions pour un test spécifique
    public function getByTestId($test_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE test_id = :test_id ORDER BY id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":test_id", $test_id);
        $stmt->execute();
        return $stmt;
    }

    // Créer une nouvelle question
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                  (test_id, question, type, option_a, option_b, option_c, option_d, bonne_reponse)
                  VALUES (:test_id, :question, :type, :option_a, :option_b, :option_c, :option_d, :bonne_reponse)";

        $stmt = $this->conn->prepare($query);

        // Lier les valeurs
        $stmt->bindParam(":test_id",       $this->test_id);
        $stmt->bindParam(":question",      $this->question);
        $stmt->bindParam(":type",          $this->type);
        $stmt->bindParam(":option_a",      $this->option_a);
        $stmt->bindParam(":option_b",      $this->option_b);
        $stmt->bindParam(":option_c",      $this->option_c);
        $stmt->bindParam(":option_d",      $this->option_d);
        $stmt->bindParam(":bonne_reponse", $this->bonne_reponse);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Supprimer toutes les questions d'un test (utile si on veut regénérer le test)
    public function deleteAllByTestId($test_id) {
        $query = "DELETE FROM " . $this->table . " WHERE test_id = :test_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":test_id", $test_id);
        return $stmt->execute();
    }

    // Compter le nombre de questions pour un test
    public function countByTestId($test_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE test_id = :test_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":test_id", $test_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
?>
