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
    // VALIDATION - Contrôle de saisie côté serveur
    // =============================================

    /**
     * Valider les données d'une conversation
     */
    public function validateConversation($user1_id, $user2_id) {
        $errors = [];

        // user1_id obligatoire et numérique
        if (empty($user1_id)) {
            $errors[] = "L'utilisateur 1 est obligatoire.";
        } elseif (!is_numeric($user1_id) || intval($user1_id) <= 0) {
            $errors[] = "L'identifiant de l'utilisateur 1 doit être un nombre positif.";
        }

        // user2_id obligatoire et numérique
        if (empty($user2_id)) {
            $errors[] = "L'utilisateur 2 est obligatoire.";
        } elseif (!is_numeric($user2_id) || intval($user2_id) <= 0) {
            $errors[] = "L'identifiant de l'utilisateur 2 doit être un nombre positif.";
        }

        // Les deux utilisateurs doivent être différents
        if (!empty($user1_id) && !empty($user2_id) && $user1_id == $user2_id) {
            $errors[] = "Vous ne pouvez pas créer une conversation avec vous-même.";
        }

        // Vérifier que les utilisateurs existent dans la base
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

    /**
     * Valider les données d'un message
     */
    public function validateMessage($id_conversation, $sender_id, $contenu) {
        $errors = [];

        // id_conversation obligatoire
        if (empty($id_conversation)) {
            $errors[] = "La conversation est obligatoire.";
        } elseif (!is_numeric($id_conversation) || intval($id_conversation) <= 0) {
            $errors[] = "L'identifiant de la conversation est invalide.";
        }

        // sender_id obligatoire
        if (empty($sender_id)) {
            $errors[] = "L'expéditeur est obligatoire.";
        } elseif (!is_numeric($sender_id) || intval($sender_id) <= 0) {
            $errors[] = "L'identifiant de l'expéditeur est invalide.";
        }

        // contenu obligatoire
        if (empty(trim($contenu))) {
            $errors[] = "Le message ne peut pas être vide.";
        } elseif (strlen(trim($contenu)) < 1) {
            $errors[] = "Le message doit contenir au moins 1 caractère.";
        } elseif (strlen(trim($contenu)) > 1000) {
            $errors[] = "Le message ne peut pas dépasser 1000 caractères.";
        }

        // Vérifier que la conversation existe
        if (!empty($id_conversation) && is_numeric($id_conversation)) {
            $stmt = $this->pdo->prepare("SELECT id_conversation FROM conversations WHERE id_conversation = :id");
            $stmt->execute([':id' => $id_conversation]);
            if (!$stmt->fetch()) {
                $errors[] = "La conversation n'existe pas.";
            }
        }

        // Vérifier que l'expéditeur existe
        if (!empty($sender_id) && is_numeric($sender_id)) {
            $stmt = $this->pdo->prepare("SELECT id FROM utilisateurs WHERE id = :id");
            $stmt->execute([':id' => $sender_id]);
            if (!$stmt->fetch()) {
                $errors[] = "L'expéditeur n'existe pas.";
            }
        }

        return $errors;
    }

    /**
     * Valider la modification d'un message
     */
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

    /**
     * Lister toutes les conversations
     */
    public function listConversations() {
        $conv = new Conversation($this->pdo);
        return $conv->readAll();
    }

    /**
     * Lister les conversations d'un utilisateur
     */
    public function listConversationsByUser($userId) {
        $conv = new Conversation($this->pdo);
        return $conv->readByUser($userId);
    }

    /**
     * Obtenir une conversation par ID
     */
    public function getConversation($id) {
        $conv = new Conversation($this->pdo);
        $conv->id_conversation = $id;
        return $conv->readOne();
    }

    /**
     * Créer une conversation
     */
    public function createConversation($user1_id, $user2_id) {
        $errors = $this->validateConversation($user1_id, $user2_id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $conv = new Conversation($this->pdo);
        $conv->user1_id = $user1_id;
        $conv->user2_id = $user2_id;

        // Vérifier si une conversation existe déjà entre ces 2 users
        if ($conv->existsBetweenUsers()) {
            return ['success' => true, 'id' => $conv->id_conversation, 'existing' => true];
        }

        if ($conv->create()) {
            return ['success' => true, 'id' => $conv->id_conversation];
        }
        return ['success' => false, 'errors' => ['Erreur lors de la création de la conversation.']];
    }

    /**
     * Modifier une conversation
     */
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

    /**
     * Supprimer une conversation
     */
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

    /**
     * Lister tous les messages
     */
    public function listMessages() {
        $msg = new Message($this->pdo);
        return $msg->readAll();
    }

    /**
     * Lister les messages d'une conversation
     */
    public function listMessagesByConversation($id_conversation) {
        $msg = new Message($this->pdo);
        $msg->id_conversation = $id_conversation;
        return $msg->readByConversation();
    }

    /**
     * Obtenir un message par ID
     */
    public function getMessage($id) {
        $msg = new Message($this->pdo);
        $msg->id_message = $id;
        return $msg->readOne();
    }

    /**
     * Envoyer un message
     */
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

    /**
     * Modifier un message
     */
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

    /**
     * Supprimer un message
     */
    public function deleteMessage($id) {
        $msg = new Message($this->pdo);
        $msg->id_message = $id;

        if ($msg->delete()) {
            return ['success' => true];
        }
        return ['success' => false, 'errors' => ['Erreur lors de la suppression du message.']];
    }

    /**
     * Marquer les messages comme lus
     */
    public function markMessagesAsSeen($id_conversation, $userId) {
        $msg = new Message($this->pdo);
        $msg->id_conversation = $id_conversation;
        $msg->sender_id = $userId;
        return $msg->markAsSeen();
    }

    /**
     * Récupérer la liste des utilisateurs (pour sélection dans formulaires)
     */
    public function getUsers() {
        $stmt = $this->pdo->prepare("SELECT id, nom, prenom, role FROM utilisateurs ORDER BY nom, prenom");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les freelancers
     */
    public function getFreelancers() {
        $stmt = $this->pdo->prepare("SELECT id, nom, prenom FROM utilisateurs WHERE role = 'freelancer' ORDER BY nom, prenom");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer les clients
     */
    public function getClients() {
        $stmt = $this->pdo->prepare("SELECT id, nom, prenom FROM utilisateurs WHERE role = 'client' ORDER BY nom, prenom");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
