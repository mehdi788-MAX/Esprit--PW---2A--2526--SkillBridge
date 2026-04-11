
<?php

class Profil {

    private $conn;
    private $table = 'profils';

    // Attributs
    public $id;
    public $utilisateur_id;
    public $bio;
    public $competences;
    public $localisation;
    public $site_web;
    public $date_naissance;

    public function __construct($db) {
        $this->conn = $db;
    }

    // =====================
    // CREATE
    // =====================
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                  (utilisateur_id, bio, competences, localisation, site_web, date_naissance)
                  VALUES (:utilisateur_id, :bio, :competences, :localisation, :site_web, :date_naissance)";

        $stmt = $this->conn->prepare($query);

        $this->bio           = htmlspecialchars(strip_tags($this->bio));
        $this->competences   = htmlspecialchars(strip_tags($this->competences));
        $this->localisation  = htmlspecialchars(strip_tags($this->localisation));
        $this->site_web      = htmlspecialchars(strip_tags($this->site_web));

        $stmt->bindParam(':utilisateur_id', $this->utilisateur_id);
        $stmt->bindParam(':bio',            $this->bio);
        $stmt->bindParam(':competences',    $this->competences);
        $stmt->bindParam(':localisation',   $this->localisation);
        $stmt->bindParam(':site_web',       $this->site_web);
        $stmt->bindParam(':date_naissance', $this->date_naissance);

        return $stmt->execute();
    }

    // =====================
    // READ BY USER ID
    // =====================
    public function readByUserId() {
        $query = "SELECT * FROM " . $this->table . "
                  WHERE utilisateur_id = :utilisateur_id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':utilisateur_id', $this->utilisateur_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id            = $row['id'];
            $this->bio           = $row['bio'];
            $this->competences   = $row['competences'];
            $this->localisation  = $row['localisation'];
            $this->site_web      = $row['site_web'];
            $this->date_naissance = $row['date_naissance'];
            return true;
        }
        return false;
    }

    // =====================
    // UPDATE
    // =====================
    public function update() {
        $query = "UPDATE " . $this->table . "
                  SET bio = :bio,
                      competences = :competences,
                      localisation = :localisation,
                      site_web = :site_web,
                      date_naissance = :date_naissance
                  WHERE utilisateur_id = :utilisateur_id";

        $stmt = $this->conn->prepare($query);

        $this->bio          = htmlspecialchars(strip_tags($this->bio));
        $this->competences  = htmlspecialchars(strip_tags($this->competences));
        $this->localisation = htmlspecialchars(strip_tags($this->localisation));
        $this->site_web     = htmlspecialchars(strip_tags($this->site_web));

        $stmt->bindParam(':bio',            $this->bio);
        $stmt->bindParam(':competences',    $this->competences);
        $stmt->bindParam(':localisation',   $this->localisation);
        $stmt->bindParam(':site_web',       $this->site_web);
        $stmt->bindParam(':date_naissance', $this->date_naissance);
        $stmt->bindParam(':utilisateur_id', $this->utilisateur_id);

        return $stmt->execute();
    }

    // =====================
    // DELETE
    // =====================
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE utilisateur_id = :utilisateur_id";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':utilisateur_id', $this->utilisateur_id);
        return $stmt->execute();
    }
}
?>
