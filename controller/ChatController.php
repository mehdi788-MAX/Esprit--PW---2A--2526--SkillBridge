<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/Conversation.php';
require_once __DIR__ . '/../model/Message.php';

class ChatController {

    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    // =============================================
    // MÉTHODE JOINTURE (Workshop : Partie 3)
    // Équivalent de afficherAlbums($idGenre) du workshop
    // Affiche les messages d'une conversation via la clé étrangère id_conversation (FK)
    // =============================================

    /**
     * afficherMessages($idConversation)
     * Retourne tous les messages d'une conversation donnée
     * en faisant une JOINTURE entre messages et utilisateurs (pour le nom de l'expéditeur)
     * C'est la jointure : messages.id_conversation (FK) -> conversations.id_conversation (PK)
     */
    public function afficherMessages($idConversation) {
        try {
            $pdo = $this->pdo;
            // Jointure : messages JOIN utilisateurs ON sender_id = id
            // WHERE id_conversation = :id (clé étrangère FK vers conversations)
            $query = $pdo->prepare("SELECT m.*, u.nom AS sender_nom, u.prenom AS sender_prenom
                                    FROM messages m
                                    JOIN utilisateurs u ON m.sender_id = u.id
                                    WHERE m.id_conversation = :id
                                    ORDER BY m.date_envoi ASC");
            $query->execute([':id' => $idConversation]);
            return $query->fetchAll();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * afficherConversations()
     * Retourne toutes les conversations avec les noms des participants (jointure)
     * Utilisé pour remplir le menu déroulant dans searchMessages.php
     */
    public function afficherConversations() {
        try {
            $pdo = $this->pdo;
            $query = $pdo->prepare("SELECT c.id_conversation,
                                           u1.nom AS user1_nom, u1.prenom AS user1_prenom,
                                           u2.nom AS user2_nom, u2.prenom AS user2_prenom
                                    FROM conversations c
                                    JOIN utilisateurs u1 ON c.user1_id = u1.id
                                    JOIN utilisateurs u2 ON c.user2_id = u2.id
                                    ORDER BY c.date_creation DESC");
            $query->execute();
            return $query->fetchAll();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    // =============================================
    // VALIDATION - Contrôle de saisie côté serveur
    // =============================================

    public function validateConversation($user1_id, $user2_id) {
        $errors = [];

        if (empty($user1_id)) {
            $errors[] = "L'utilisateur 1 est obligatoire.";
        } elseif (!is_numeric($user1_id) || intval($user1_id) <= 0) {
            $errors[] = "L'identifiant de l'utilisateur 1 doit être un nombre positif.";
        }

        if (empty($user2_id)) {
            $errors[] = "L'utilisateur 2 est obligatoire.";
        } elseif (!is_numeric($user2_id) || intval($user2_id) <= 0) {
            $errors[] = "L'identifiant de l'utilisateur 2 doit être un nombre positif.";
        }

        if (!empty($user1_id) && !empty($user2_id) && $user1_id == $user2_id) {
            $errors[] = "Vous ne pouvez pas créer une conversation avec vous-même.";
        }

        if (empty($errors)) {
            $stmt = $this->pdo->prepare("SELECT id FROM utilisateurs WHERE id = :id");
            $stmt->execute([':id' => $user1_id]);
            if (!$stmt->fetch()) {
                $errors[] = "L'utilisateur 1 n'existe pas.";
            }
            $stmt->execute([':id' => $user2_id]);
            if (!$stmt->fetch()) {
                $errors[] = "L'utilisateur 2 n'existe pas.";
            }
        }

        return $errors;
    }

    public function validateMessage($id_conversation, $sender_id, $contenu) {
        $errors = [];

        if (empty($id_conversation)) {
            $errors[] = "La conversation est obligatoire.";
        } elseif (!is_numeric($id_conversation) || intval($id_conversation) <= 0) {
            $errors[] = "L'identifiant de la conversation est invalide.";
        }

        if (empty($sender_id)) {
            $errors[] = "L'expéditeur est obligatoire.";
        } elseif (!is_numeric($sender_id) || intval($sender_id) <= 0) {
            $errors[] = "L'identifiant de l'expéditeur est invalide.";
        }

        if (empty(trim($contenu))) {
            $errors[] = "Le message ne peut pas être vide.";
        } elseif (strlen(trim($contenu)) > 1000) {
            $errors[] = "Le message ne peut pas dépasser 1000 caractères.";
        }

        if (!empty($id_conversation) && is_numeric($id_conversation)) {
            $stmt = $this->pdo->prepare("SELECT id_conversation FROM conversations WHERE id_conversation = :id");
            $stmt->execute([':id' => $id_conversation]);
            if (!$stmt->fetch()) {
                $errors[] = "La conversation n'existe pas.";
            }
        }

        if (!empty($sender_id) && is_numeric($sender_id)) {
            $stmt = $this->pdo->prepare("SELECT id FROM utilisateurs WHERE id = :id");
            $stmt->execute([':id' => $sender_id]);
            if (!$stmt->fetch()) {
                $errors[] = "L'expéditeur n'existe pas.";
            }
        }

        return $errors;
    }

    public function validateUpdateMessage($contenu) {
        $errors = [];

        if (empty(trim($contenu))) {
            $errors[] = "Le message ne peut pas être vide.";
        } elseif (strlen(trim($contenu)) > 1000) {
            $errors[] = "Le message ne peut pas dépasser 1000 caractères.";
        }

        return $errors;
    }

    // =============================================
    // CONVERSATION CRUD
    // =============================================

    public function listConversations() {
        $conv = new Conversation($this->pdo);
        return $conv->readAll();
    }

    public function listConversationsByUser($userId) {
        $conv = new Conversation($this->pdo);
        return $conv->readByUser($userId);
    }

    public function getConversation($id) {
        $conv = new Conversation($this->pdo);
        $conv->id_conversation = $id;
        return $conv->readOne();
    }

    public function createConversation($user1_id, $user2_id) {
        $errors = $this->validateConversation($user1_id, $user2_id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $conv = new Conversation($this->pdo);
        $conv->user1_id = $user1_id;
        $conv->user2_id = $user2_id;

        if ($conv->existsBetweenUsers()) {
            return ['success' => true, 'id' => $conv->id_conversation, 'existing' => true];
        }

        if ($conv->create()) {
            return ['success' => true, 'id' => $conv->id_conversation];
        }
        return ['success' => false, 'errors' => ['Erreur lors de la création de la conversation.']];
    }

    public function updateConversation($id, $user1_id, $user2_id) {
        $errors = $this->validateConversation($user1_id, $user2_id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $conv = new Conversation($this->pdo);
        $conv->id_conversation = $id;
        $conv->user1_id = $user1_id;
        $conv->user2_id = $user2_id;

        if ($conv->update()) {
            return ['success' => true];
        }
        return ['success' => false, 'errors' => ['Erreur lors de la modification de la conversation.']];
    }

    public function deleteConversation($id) {
        $conv = new Conversation($this->pdo);
        $conv->id_conversation = $id;

        if ($conv->delete()) {
            return ['success' => true];
        }
        return ['success' => false, 'errors' => ['Erreur lors de la suppression de la conversation.']];
    }

    // =============================================
    // MESSAGE CRUD
    // =============================================

    public function listMessages() {
        $msg = new Message($this->pdo);
        return $msg->readAll();
    }

    public function listMessagesByConversation($id_conversation) {
        $msg = new Message($this->pdo);
        $msg->id_conversation = $id_conversation;
        return $msg->readByConversation();
    }

    public function getMessage($id) {
        $msg = new Message($this->pdo);
        $msg->id_message = $id;
        return $msg->readOne();
    }

    public function sendMessage($id_conversation, $sender_id, $contenu, $type = 'text') {
        $errors = $this->validateMessage($id_conversation, $sender_id, $contenu);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $msg = new Message($this->pdo);
        $msg->id_conversation = $id_conversation;
        $msg->sender_id = $sender_id;
        $msg->contenu = $contenu;
        $msg->type = $type;

        if ($msg->create()) {
            return ['success' => true, 'id' => $msg->id_message];
        }
        return ['success' => false, 'errors' => ['Erreur lors de l\'envoi du message.']];
    }

    public function updateMessage($id, $contenu, $type = 'text') {
        $errors = $this->validateUpdateMessage($contenu);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $msg = new Message($this->pdo);
        $msg->id_message = $id;
        $msg->contenu = $contenu;
        $msg->type = $type;

        if ($msg->update()) {
            return ['success' => true];
        }
        return ['success' => false, 'errors' => ['Erreur lors de la modification du message.']];
    }

    public function deleteMessage($id) {
        $msg = new Message($this->pdo);
        $msg->id_message = $id;

        if ($msg->delete()) {
            return ['success' => true];
        }
        return ['success' => false, 'errors' => ['Erreur lors de la suppression du message.']];
    }

    public function markMessagesAsSeen($id_conversation, $userId) {
        $msg = new Message($this->pdo);
        $msg->id_conversation = $id_conversation;
        $msg->sender_id = $userId;
        return $msg->markAsSeen();
    }

    public function getUsers() {
        $stmt = $this->pdo->prepare("SELECT id, nom, prenom, role FROM utilisateurs ORDER BY nom, prenom");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFreelancers() {
        $stmt = $this->pdo->prepare("SELECT id, nom, prenom FROM utilisateurs WHERE role = 'freelancer' ORDER BY nom, prenom");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getClients() {
        $stmt = $this->pdo->prepare("SELECT id, nom, prenom FROM utilisateurs WHERE role = 'client' ORDER BY nom, prenom");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}