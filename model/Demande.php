<?php

class Demande {

    private $conn;
    private $table = 'demandes';

    // Attributs
    public $id;
    public $title;
    public $price;
    public $deadline;
    public $description;
    public $created_at;
    public $user_id;
    public $status;                  // 'open' | 'closed'
    public $accepted_proposition_id; // FK vers la proposition retenue (NULL si demande ouverte)

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
                  (title, price, deadline, description, created_at, user_id)
                  VALUES (:title, :price, :deadline, :description, :created_at, :user_id)";

        $stmt = $this->conn->prepare($query);

        $this->title       = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->deadline    = htmlspecialchars(strip_tags($this->deadline));

        $stmt->bindParam(':title',       $this->title);
        $stmt->bindParam(':price',       $this->price);
        $stmt->bindParam(':deadline',    $this->deadline);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':created_at',  $now);

        // user_id peut être NULL (demande sans propriétaire connecté)
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
    // READ ALL
    // =====================
    public function readAll($sort = 'recent', $search = null) {
        $order = ($sort === 'oldest') ? 'ASC' : 'DESC';

        $query = "SELECT id, title, price, deadline, description, created_at, user_id,
                         status, accepted_proposition_id
                  FROM " . $this->table;

        if ($search !== null && $search !== '') {
            $query .= " WHERE title LIKE :search";
        }

        $query .= " ORDER BY created_at " . $order;

        $stmt = $this->conn->prepare($query);

        if ($search !== null && $search !== '') {
            $kw = '%' . $search . '%';
            $stmt->bindParam(':search', $kw);
        }

        $stmt->execute();
        return $stmt;
    }

    // =====================
    // READ BY USER
    // =====================
    public function readByUser($userId, $sort = 'recent', $search = null) {
        $order = ($sort === 'oldest') ? 'ASC' : 'DESC';

        $query = "SELECT id, title, price, deadline, description, created_at, user_id,
                         status, accepted_proposition_id
                  FROM " . $this->table . "
                  WHERE user_id = :user_id";

        if ($search !== null && $search !== '') {
            $query .= " AND title LIKE :search";
        }

        $query .= " ORDER BY created_at " . $order;

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

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
        $query = "SELECT id, title, price, deadline, description, created_at, user_id,
                         status, accepted_proposition_id
                  FROM " . $this->table . "
                  WHERE id = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->title                   = $row['title'];
            $this->price                   = $row['price'];
            $this->deadline                = $row['deadline'];
            $this->description             = $row['description'];
            $this->created_at              = $row['created_at'];
            $this->user_id                 = $row['user_id'];
            $this->status                  = $row['status'] ?? 'open';
            $this->accepted_proposition_id = $row['accepted_proposition_id'] ?? null;
            return true;
        }
        return false;
    }

    // =====================
    // UPDATE
    // =====================
    public function update() {
        $query = "UPDATE " . $this->table . "
                  SET title = :title,
                      price = :price,
                      deadline = :deadline,
                      description = :description
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->title       = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->deadline    = htmlspecialchars(strip_tags($this->deadline));

        $stmt->bindParam(':title',       $this->title);
        $stmt->bindParam(':price',       $this->price);
        $stmt->bindParam(':deadline',    $this->deadline);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':id',          $this->id);

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
        $stmt->bindParam(':id',  $this->id,  PDO::PARAM_INT);
        $stmt->bindParam(':uid', $userId,    PDO::PARAM_INT);
        return $stmt->execute();
    }

    // =====================
    // COUNT
    // =====================
    public function countAll() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM " . $this->table);
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
    // DEADLINE SOON (next 7 days)
    // =====================
    public function getDeadlineSoon() {
        // PHP date() au lieu de NOW() pour compatibilité SQLite ↔ MySQL
        $today  = date('Y-m-d');
        $in7    = date('Y-m-d', strtotime('+7 days'));

        $query = "SELECT id, title, price, deadline, description, created_at, user_id,
                         status, accepted_proposition_id
                  FROM " . $this->table . "
                  WHERE deadline >= :today AND deadline <= :in7
                  ORDER BY deadline ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':today', $today);
        $stmt->bindParam(':in7',   $in7);
        $stmt->execute();
        return $stmt;
    }

}
?>
