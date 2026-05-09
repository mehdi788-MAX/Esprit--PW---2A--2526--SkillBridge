<?php
// models/Resultat.php
// Modèle pour interagir avec la table 'resultats'

class Resultat {
    private $conn;
    private $table = "resultats";

    // Propriétés
    public $id;
    public $test_id;
    public $score;
    public $total;
    public $details;
    public $user_name;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Créer un nouveau résultat
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                  (test_id, score, total, details, user_name)
                  VALUES (:test_id, :score, :total, :details, :user_name)";

        $stmt = $this->conn->prepare($query);

        // Lier les valeurs
        $stmt->bindParam(":test_id", $this->test_id);
        $stmt->bindParam(":score",   $this->score);
        $stmt->bindParam(":total",   $this->total);
        $stmt->bindParam(":details", $this->details);
        $stmt->bindParam(":user_name", $this->user_name);

        if ($stmt->execute()) {
            // Optionnel : mettre à jour le score moyen du test
            $this->updateAverageScore($this->test_id);
            return true;
        }
        return false;
    }

    // Met à jour le score moyen du test dans la table tests
    private function updateAverageScore($test_id) {
        // Calculer la moyenne
        $queryAvg = "SELECT AVG((score/total)*100) as avg_score FROM " . $this->table . " WHERE test_id = :test_id";
        $stmtAvg = $this->conn->prepare($queryAvg);
        $stmtAvg->bindParam(":test_id", $test_id);
        $stmtAvg->execute();
        $row = $stmtAvg->fetch(PDO::FETCH_ASSOC);
        $avg = $row['avg_score'] ? number_format($row['avg_score'], 2, '.', '') : 0.00;

        // Mettre à jour la table tests
        $queryUpdate = "UPDATE tests SET average_score = :avg WHERE id = :test_id";
        $stmtUpdate = $this->conn->prepare($queryUpdate);
        $stmtUpdate->bindParam(":avg", $avg);
        $stmtUpdate->bindParam(":test_id", $test_id);
        $stmtUpdate->execute();
    }

    // Récupérer les résultats pour un test spécifique
    public function getByTestId($test_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE test_id = :test_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":test_id", $test_id);
        $stmt->execute();
        return $stmt;
    }

    // Récupérer l'historique récent
    public function getRecent($limit = 20) {
        $query = "SELECT r.*, t.title AS test_title, c.name AS category_name
                  FROM " . $this->table . " r
                  JOIN tests t ON r.test_id = t.id
                  LEFT JOIN categories c ON t.category_id = c.id
                  ORDER BY r.created_at DESC
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        // Using bindValue because bindParam requires a reference which might not work with literal in strict mode
        $stmt->bindValue(":limit", (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }
}
?>
