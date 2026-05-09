<?php

class Proposition {

    private $conn;
    private $table = 'propositions';

    // Attributs
    public $id;
    public $demande_id;
    public $user_id;
    public $freelancer_name;
    public $message;
    public $price;
    public $status;     // 'pending' | 'accepted' | 'declined'
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // =====================
    // CREATE
    // =====================
    public function create() {
        // PHP date() au lieu de NOW() pour compatibilité SQLite ↔ MySQL
        $now = date('Y-m-d H:i:s');
        $query = "INSERT INTO " . $this->table . "
                  (demande_id, user_id, freelancer_name, message, price, created_at)
                  VALUES (:demande_id, :user_id, :freelancer_name, :message, :price, :created_at)";

        $stmt = $this->conn->prepare($query);

        // Texte stocké en clair ; échappement uniquement à l'affichage.
        $this->freelancer_name = strip_tags((string)$this->freelancer_name);
        $this->message         = strip_tags((string)$this->message);

        $stmt->bindParam(':demande_id',      $this->demande_id, PDO::PARAM_INT);
        $stmt->bindParam(':freelancer_name', $this->freelancer_name);
        $stmt->bindParam(':message',         $this->message);
        $stmt->bindParam(':price',           $this->price);
        $stmt->bindParam(':created_at',      $now);

        // user_id peut être NULL (proposition envoyée sans utilisateur connecté)
        if ($this->user_id === null || $this->user_id === '') {
            $stmt->bindValue(':user_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
        }

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // =====================
    // READ BY DEMANDE
    // =====================
    public function readByDemande($demandeId, $sort = 'recent') {
        $order = ($sort === 'oldest') ? 'ASC' : 'DESC';

        $query = "SELECT p.id, p.demande_id, p.user_id, p.freelancer_name,
                         p.message, p.price, p.status, p.created_at,
                         d.title AS demande_title
                  FROM " . $this->table . " p
                  JOIN demandes d ON d.id = p.demande_id
                  WHERE p.demande_id = :demande_id
                  ORDER BY p.created_at " . $order;

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':demande_id', $demandeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    // =====================
    // READ BY USER
    // =====================
    public function readByUser($userId, $sort = 'recent') {
        $order = ($sort === 'oldest') ? 'ASC' : 'DESC';

        $query = "SELECT p.id, p.demande_id, p.user_id, p.freelancer_name,
                         p.message, p.price, p.status, p.created_at,
                         d.title AS demande_title
                  FROM " . $this->table . " p
                  JOIN demandes d ON d.id = p.demande_id
                  WHERE p.user_id = :user_id
                  ORDER BY p.created_at " . $order;

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    // =====================
    // READ ALL (admin view)
    // =====================
    public function readAll($sort = 'recent', $demandeId = null, $search = null) {
        $order = ($sort === 'oldest') ? 'ASC' : 'DESC';

        $query = "SELECT p.id, p.demande_id, p.user_id, p.freelancer_name,
                         p.message, p.price, p.status, p.created_at,
                         d.title AS demande_title
                  FROM " . $this->table . " p
                  JOIN demandes d ON d.id = p.demande_id";

        $conditions = [];
        if ($demandeId !== null && $demandeId !== '') {
            $conditions[] = "p.demande_id = :demande_id";
        }
        if ($search !== null && $search !== '') {
            $conditions[] = "d.title LIKE :search";
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }

        $query .= " ORDER BY p.created_at " . $order;

        $stmt = $this->conn->prepare($query);

        if ($demandeId !== null && $demandeId !== '') {
            $stmt->bindParam(':demande_id', $demandeId, PDO::PARAM_INT);
        }
        if ($search !== null && $search !== '') {
            $kw = '%' . $search . '%';
            $stmt->bindParam(':search', $kw);
        }

        $stmt->execute();
        return $stmt;
    }

    // =====================
    // READ ONE
    // =====================
    public function readOne() {
        $query = "SELECT id, demande_id, user_id, freelancer_name, message, price, status, created_at
                  FROM " . $this->table . "
                  WHERE id = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->demande_id      = $row['demande_id'];
            $this->user_id         = $row['user_id'];
            $this->freelancer_name = $row['freelancer_name'];
            $this->message         = $row['message'];
            $this->price           = $row['price'];
            $this->status          = $row['status'] ?? 'pending';
            $this->created_at      = $row['created_at'];
            return true;
        }
        return false;
    }

    // =====================
    // UPDATE
    // =====================
    public function update() {
        $query = "UPDATE " . $this->table . "
                  SET freelancer_name = :freelancer_name,
                      message = :message,
                      price = :price
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Texte stocké en clair ; échappement uniquement à l'affichage.
        $this->freelancer_name = strip_tags((string)$this->freelancer_name);
        $this->message         = strip_tags((string)$this->message);

        $stmt->bindParam(':freelancer_name', $this->freelancer_name);
        $stmt->bindParam(':message',         $this->message);
        $stmt->bindParam(':price',           $this->price);
        $stmt->bindParam(':id',              $this->id);

        return $stmt->execute();
    }

    // =====================
    // DELETE
    // =====================
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    // =====================
    // DELETE BY OWNER
    // =====================
    public function deleteByOwner($userId) {
        $query = "DELETE FROM " . $this->table . "
                  WHERE id = :id AND user_id = :uid";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':id',  $this->id, PDO::PARAM_INT);
        $stmt->bindParam(':uid', $userId,   PDO::PARAM_INT);
        return $stmt->execute();
    }

    // =====================
    // COUNT
    // =====================
    public function countByDemande($demandeId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM " . $this->table . " WHERE demande_id = :demande_id");
        $stmt->bindParam(':demande_id', $demandeId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function countByUser($userId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM " . $this->table . " WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // =====================
    // DEMANDES OPTIONS (dropdown helper)
    // =====================
    public function getDemandesOptions() {
        $query = "SELECT id, title, deadline
                  FROM demandes
                  ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
?>
