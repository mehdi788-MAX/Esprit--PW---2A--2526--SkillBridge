<?php
// config/Database.php
// Ce fichier gère la connexion à la base de données MySQL

class Database {
    // Informations de connexion
    private $host     = "localhost";
    private $db_name  = "skillbridge";
    private $username = "root";
    private $password = "";

    // La connexion PDO
    private $conn;

    // Méthode pour obtenir la connexion
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            // Afficher les erreurs SQL clairement
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Erreur de connexion : " . $e->getMessage();
        }

        return $this->conn;
    }
}
?>
