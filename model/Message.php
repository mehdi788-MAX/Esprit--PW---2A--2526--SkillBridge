<?php

class Message {

    private $conn;
    private $table = 'messages';

    // Attributs
    public $id_message;
    public $id_conversation;
    public $sender_id;
    public $contenu;
    public $date_envoi;
    public $is_seen;
    public $type;

    public function __construct($db) {
        $this->conn = $db;
    }

    // =====================
    // CREATE
    // =====================
    public function create() {
        $now = date('Y-m-d H:i:s');
        $query = "INSERT INTO " . $this->table . " (id_conversation, sender_id, contenu, date_envoi, is_seen, type)
                  VALUES (:id_conversation, :sender_id, :contenu, :date_envoi, 0, :type)";

        $stmt = $this->conn->prepare($query);

        $this->id_conversation = htmlspecialchars(strip_tags($this->id_conversation));
        $this->sender_id = htmlspecialchars(strip_tags($this->sender_id));
        $this->contenu = htmlspecialchars(strip_tags($this->contenu));
        $this->type = htmlspecialchars(strip_tags($this->type));

        $stmt->bindParam(':id_conversation', $this->id_conversation);
        $stmt->bindParam(':sender_id', $this->sender_id);
        $stmt->bindParam(':contenu', $this->contenu);
        $stmt->bindParam(':date_envoi', $now);
        $stmt->bindParam(':type', $this->type);

        if ($stmt->execute()) {
            $this->id_message = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // =====================
    // READ ALL (tous les messages)
    // =====================
    public function readAll() {
        $query = "SELECT m.*, u.nom AS sender_nom, u.prenom AS sender_prenom
                  FROM " . $this->table . " m
                  JOIN utilisateurs u ON m.sender_id = u.id
                  ORDER BY m.date_envoi DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // =====================
    // READ BY CONVERSATION (JOINTURE Conversation -> Message)
    // Jointure entre la table messages et utilisateurs
    // pour afficher les messages d'une conversation donnée
    // =====================
    public function readByConversation() {
        // JOINTURE : messages JOIN utilisateurs ON sender_id = utilisateurs.id
        // WHERE id_conversation = :id_conversation (clé étrangère FK)
        $query = "SELECT m.id_message,
                         m.id_conversation,
                         m.sender_id,
                         m.contenu,
                         m.date_envoi,
                         m.is_seen,
                         m.type,
                         u.nom AS sender_nom,
                         u.prenom AS sender_prenom
                  FROM " . $this->table . " m
                  JOIN utilisateurs u ON m.sender_id = u.id
                  WHERE m.id_conversation = :id_conversation
                  ORDER BY m.date_envoi ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_conversation', $this->id_conversation);
        $stmt->execute();
        return $stmt;
    }

    // =====================
    // JOINTURE COMPLÈTE : messages + conversations + utilisateurs
    // Permet d'afficher pour chaque message : l'expéditeur ET les participants de la conversation
    // =====================
    public function readByConversationWithFullJoin() {
        // Double jointure :
        //   messages JOIN utilisateurs u ON m.sender_id = u.id  (pour l'expéditeur)
        //   messages JOIN conversations c ON m.id_conversation = c.id_conversation (pour les participants)
        $query = "SELECT m.id_message,
                         m.id_conversation,
                         m.sender_id,
                         m.contenu,
                         m.date_envoi,
                         m.is_seen,
                         m.type,
                         u.nom AS sender_nom,
                         u.prenom AS sender_prenom,
                         c.user1_id,
                         c.user2_id,
                         c.date_creation AS conversation_date
                  FROM " . $this->table . " m
                  JOIN utilisateurs u ON m.sender_id = u.id
                  JOIN conversations c ON m.id_conversation = c.id_conversation
                  WHERE m.id_conversation = :id_conversation
                  ORDER BY m.date_envoi ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_conversation', $this->id_conversation);
        $stmt->execute();
        return $stmt;
    }

    // =====================
    // READ ONE
    // =====================
    public function readOne() {
        $query = "SELECT m.*, u.nom AS sender_nom, u.prenom AS sender_prenom
                  FROM " . $this->table . " m
                  JOIN utilisateurs u ON m.sender_id = u.id
                  WHERE m.id_message = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id_message);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id_conversation = $row['id_conversation'];
            $this->sender_id = $row['sender_id'];
            $this->contenu = $row['contenu'];
            $this->date_envoi = $row['date_envoi'];
            $this->is_seen = $row['is_seen'];
            $this->type = $row['type'];
            return $row;
        }
        return false;
    }

    // =====================
    // UPDATE
    // =====================
    public function update() {
        $query = "UPDATE " . $this->table . "
                  SET contenu = :contenu,
                      type = :type
                  WHERE id_message = :id";

        $stmt = $this->conn->prepare($query);

        $this->contenu = htmlspecialchars(strip_tags($this->contenu));
        $this->type = htmlspecialchars(strip_tags($this->type));

        $stmt->bindParam(':contenu', $this->contenu);
        $stmt->bindParam(':type', $this->type);
        $stmt->bindParam(':id', $this->id_message);

        return $stmt->execute();
    }

    // =====================
    // DELETE
    // =====================
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id_message = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id_message);
        return $stmt->execute();
    }

    // =====================
    // MARK AS SEEN
    // =====================
    public function markAsSeen() {
        $query = "UPDATE " . $this->table . "
                  SET is_seen = 1
                  WHERE id_conversation = :id_conversation AND sender_id != :user_id AND is_seen = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_conversation', $this->id_conversation);
        $stmt->bindParam(':user_id', $this->sender_id);
        return $stmt->execute();
    }

    // =====================
    // COUNT UNSEEN
    // =====================
    public function countUnseen($userId) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . "
                  WHERE sender_id != :user_id AND is_seen = 0
                  AND id_conversation IN (
                      SELECT id_conversation FROM conversations
                      WHERE user1_id = :uid1 OR user2_id = :uid2
                  )";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':uid1', $userId);
        $stmt->bindParam(':uid2', $userId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // =====================
    // COUNT BY CONVERSATION
    // =====================
    public function countByConversation() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE id_conversation = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id_conversation);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
}