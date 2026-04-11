<?php

class Conversation {

    private $conn;
    private $table = 'conversations';

    // Attributs
    public $id_conversation;
    public $user1_id;
    public $user2_id;
    public $date_creation;

    public function __construct($db) {
        $this->conn = $db;
    }

    // =====================
    // CREATE
    // =====================
    public function create() {
        $now = date('Y-m-d H:i:s');
        $query = "INSERT INTO " . $this->table . " (user1_id, user2_id, date_creation)
                  VALUES (:user1_id, :user2_id, :date_creation)";

        $stmt = $this->conn->prepare($query);

        $this->user1_id = htmlspecialchars(strip_tags($this->user1_id));
        $this->user2_id = htmlspecialchars(strip_tags($this->user2_id));

        $stmt->bindParam(':user1_id', $this->user1_id);
        $stmt->bindParam(':user2_id', $this->user2_id);
        $stmt->bindParam(':date_creation', $now);

        if ($stmt->execute()) {
            $this->id_conversation = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // =====================
    // READ ALL
    // =====================
    public function readAll() {
        $query = "SELECT c.id_conversation, c.user1_id, c.user2_id, c.date_creation,
                         u1.nom AS user1_nom, u1.prenom AS user1_prenom,
                         u2.nom AS user2_nom, u2.prenom AS user2_prenom
                  FROM " . $this->table . " c
                  JOIN utilisateurs u1 ON c.user1_id = u1.id
                  JOIN utilisateurs u2 ON c.user2_id = u2.id
                  ORDER BY c.date_creation DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // =====================
    // READ BY USER (conversations d'un utilisateur)
    // =====================
    public function readByUser($userId) {
        $query = "SELECT c.id_conversation, c.user1_id, c.user2_id, c.date_creation,
                         u1.nom AS user1_nom, u1.prenom AS user1_prenom,
                         u2.nom AS user2_nom, u2.prenom AS user2_prenom,
                         (SELECT contenu FROM messages m WHERE m.id_conversation = c.id_conversation ORDER BY m.date_envoi DESC LIMIT 1) AS dernier_message
                  FROM " . $this->table . " c
                  JOIN utilisateurs u1 ON c.user1_id = u1.id
                  JOIN utilisateurs u2 ON c.user2_id = u2.id
                  WHERE c.user1_id = :user_id OR c.user2_id = :user_id2
                  ORDER BY c.date_creation DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':user_id2', $userId);
        $stmt->execute();
        return $stmt;
    }

    // =====================
    // READ ONE
    // =====================
    public function readOne() {
        $query = "SELECT c.id_conversation, c.user1_id, c.user2_id, c.date_creation,
                         u1.nom AS user1_nom, u1.prenom AS user1_prenom,
                         u2.nom AS user2_nom, u2.prenom AS user2_prenom
                  FROM " . $this->table . " c
                  JOIN utilisateurs u1 ON c.user1_id = u1.id
                  JOIN utilisateurs u2 ON c.user2_id = u2.id
                  WHERE c.id_conversation = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id_conversation);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->user1_id = $row['user1_id'];
            $this->user2_id = $row['user2_id'];
            $this->date_creation = $row['date_creation'];
            return $row;
        }
        return false;
    }

    // =====================
    // UPDATE
    // =====================
    public function update() {
        $query = "UPDATE " . $this->table . "
                  SET user1_id = :user1_id,
                      user2_id = :user2_id
                  WHERE id_conversation = :id";

        $stmt = $this->conn->prepare($query);

        $this->user1_id = htmlspecialchars(strip_tags($this->user1_id));
        $this->user2_id = htmlspecialchars(strip_tags($this->user2_id));

        $stmt->bindParam(':user1_id', $this->user1_id);
        $stmt->bindParam(':user2_id', $this->user2_id);
        $stmt->bindParam(':id', $this->id_conversation);

        return $stmt->execute();
    }

    // =====================
    // DELETE
    // =====================
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id_conversation = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id_conversation);
        return $stmt->execute();
    }

    // =====================
    // CHECK IF EXISTS BETWEEN TWO USERS
    // =====================
    public function existsBetweenUsers() {
        $query = "SELECT id_conversation FROM " . $this->table . "
                  WHERE (user1_id = :u1 AND user2_id = :u2)
                     OR (user1_id = :u3 AND user2_id = :u4)
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':u1', $this->user1_id);
        $stmt->bindParam(':u2', $this->user2_id);
        $stmt->bindParam(':u3', $this->user2_id);
        $stmt->bindParam(':u4', $this->user1_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id_conversation = $row['id_conversation'];
            return true;
        }
        return false;
    }

    // =====================
    // COUNT
    // =====================
    public function countAll() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM " . $this->table);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
}
