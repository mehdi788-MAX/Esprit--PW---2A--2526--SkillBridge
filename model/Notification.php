<?php

class Notification {

    private $conn;
    private $table = 'notifications';

    public $id;
    public $user_id;
    public $type;
    public $conversation_id;
    public $message_id;
    public $payload_json;
    public $is_read;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . "
                    (user_id, type, conversation_id, message_id, payload_json, is_read, created_at)
                  VALUES (:user_id, :type, :conversation_id, :message_id, :payload_json, 0, :created_at)";
        $stmt = $this->conn->prepare($query);
        $now = date('Y-m-d H:i:s');
        $stmt->bindValue(':user_id', $this->user_id, PDO::PARAM_INT);
        $stmt->bindValue(':type', $this->type, PDO::PARAM_STR);
        $stmt->bindValue(':conversation_id', $this->conversation_id, $this->conversation_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':message_id', $this->message_id, $this->message_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':payload_json', $this->payload_json, $this->payload_json === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':created_at', $now);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function readForUser($userId, $sinceId = 0, $limit = 20) {
        $query = "SELECT id, user_id, type, conversation_id, message_id, payload_json, is_read, created_at
                  FROM " . $this->table . "
                  WHERE user_id = :user_id AND id > :since_id
                  ORDER BY id DESC
                  LIMIT " . intval($limit);
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':since_id', $sinceId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function recentForUser($userId, $limit = 10) {
        $query = "SELECT n.id, n.type, n.conversation_id, n.message_id, n.payload_json, n.is_read, n.created_at,
                         u.prenom AS sender_prenom, u.nom AS sender_nom, m.contenu AS message_preview
                  FROM " . $this->table . " n
                  LEFT JOIN messages m ON n.message_id = m.id_message
                  LEFT JOIN utilisateurs u ON m.sender_id = u.id
                  WHERE n.user_id = :user_id
                  ORDER BY n.id DESC
                  LIMIT " . intval($limit);
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countUnread($userId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM " . $this->table . " WHERE user_id = :uid AND is_read = 0");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function markRead($userId, ?array $ids = null) {
        if ($ids === null) {
            $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET is_read = 1 WHERE user_id = :uid AND is_read = 0");
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        }
        $ids = array_filter(array_map('intval', $ids));
        if (empty($ids)) return true;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET is_read = 1
                                       WHERE user_id = ? AND id IN ($placeholders)");
        return $stmt->execute(array_merge([$userId], $ids));
    }

    public function markReadForConversation($userId, $conversationId) {
        $stmt = $this->conn->prepare("UPDATE " . $this->table . "
                                       SET is_read = 1
                                       WHERE user_id = :uid AND conversation_id = :cid AND is_read = 0");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':cid', $conversationId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
