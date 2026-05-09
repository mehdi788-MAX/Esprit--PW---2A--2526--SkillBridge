<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/Demande.php';
require_once __DIR__ . '/../model/Proposition.php';

class DemandeController {

    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    // =============================================
    // VALIDATION - Contrôle de saisie côté serveur
    // =============================================

    /**
     * Valider les données d'une demande
     */
    public function validateDemande($title, $price, $deadline, $description) {
        $errors = [];

        // title : obligatoire, longueur 5-150
        $title = trim((string)$title);
        if ($title === '') {
            $errors[] = "Le titre est obligatoire.";
        } else {
            $len = mb_strlen($title, 'UTF-8');
            if ($len < 5) {
                $errors[] = "Le titre doit contenir au moins 5 caractères.";
            } elseif ($len > 150) {
                $errors[] = "Le titre ne peut pas dépasser 150 caractères.";
            }
        }

        // price : numérique >= 1.0
        if ($price === null || $price === '' || !is_numeric($price)) {
            $errors[] = "Le prix doit être un nombre.";
        } elseif (floatval($price) < 1.0) {
            $errors[] = "Le prix doit être supérieur ou égal à 1.0.";
        }

        // deadline : obligatoire, date valide, >= aujourd'hui
        $deadline = trim((string)$deadline);
        if ($deadline === '') {
            $errors[] = "La date limite est obligatoire.";
        } else {
            $ts = strtotime($deadline);
            if ($ts === false) {
                $errors[] = "La date limite est invalide.";
            } else {
                $today = date('Y-m-d');
                $deadlineDate = date('Y-m-d', $ts);
                if ($deadlineDate < $today) {
                    $errors[] = "La date limite doit être aujourd'hui ou ultérieure.";
                }
            }
        }

        // description : obligatoire, longueur >= 20
        $description = trim((string)$description);
        if ($description === '') {
            $errors[] = "La description est obligatoire.";
        } elseif (mb_strlen($description, 'UTF-8') < 20) {
            $errors[] = "La description doit contenir au moins 20 caractères.";
        }

        return $errors;
    }

    /**
     * Valider les données d'une proposition
     */
    public function validateProposition($demande_id, $freelancer_name, $message, $price) {
        $errors = [];

        // demande_id : entier > 0 et doit exister
        if (empty($demande_id)) {
            $errors[] = "La demande est obligatoire.";
        } elseif (!is_numeric($demande_id) || intval($demande_id) <= 0) {
            $errors[] = "L'identifiant de la demande est invalide.";
        } elseif (!$this->demandeExists(intval($demande_id))) {
            $errors[] = "La demande n'existe pas.";
        }

        // freelancer_name : 3-120
        $freelancer_name = trim((string)$freelancer_name);
        if ($freelancer_name === '') {
            $errors[] = "Le nom du freelancer est obligatoire.";
        } else {
            $len = mb_strlen($freelancer_name, 'UTF-8');
            if ($len < 3) {
                $errors[] = "Le nom du freelancer doit contenir au moins 3 caractères.";
            } elseif ($len > 120) {
                $errors[] = "Le nom du freelancer ne peut pas dépasser 120 caractères.";
            }
        }

        // message : >= 15
        $message = trim((string)$message);
        if ($message === '') {
            $errors[] = "Le message est obligatoire.";
        } elseif (mb_strlen($message, 'UTF-8') < 15) {
            $errors[] = "Le message doit contenir au moins 15 caractères.";
        }

        // price : numérique >= 1.0
        if ($price === null || $price === '' || !is_numeric($price)) {
            $errors[] = "Le prix doit être un nombre.";
        } elseif (floatval($price) < 1.0) {
            $errors[] = "Le prix doit être supérieur ou égal à 1.0.";
        }

        return $errors;
    }

    // =============================================
    // DEMANDE CRUD
    // =============================================

    /**
     * Vérifier qu'une demande existe (helper)
     */
    public function demandeExists($id) {
        if (!is_numeric($id) || intval($id) <= 0) {
            return false;
        }
        $stmt = $this->pdo->prepare("SELECT id FROM demandes WHERE id = :id");
        $stmt->execute([':id' => intval($id)]);
        return (bool)$stmt->fetch();
    }

    /**
     * Obtenir une demande par ID
     */
    public function getDemande($id) {
        if (!is_numeric($id) || intval($id) <= 0) {
            return null;
        }
        $demande = new Demande($this->pdo);
        $demande->id = intval($id);
        $row = $demande->readOne();
        return $row ?: null;
    }

    /**
     * Lister toutes les demandes (admin / browse public)
     */
    public function listDemandes($sort = 'recent', $search = null) {
        $demande = new Demande($this->pdo);
        return $demande->readAll($sort, $search);
    }

    /**
     * Lister les demandes d'un utilisateur
     */
    public function listDemandesByUser($userId, $sort = 'recent', $search = null) {
        $demande = new Demande($this->pdo);
        return $demande->readByUser($userId, $sort, $search);
    }

    /**
     * Créer une demande
     */
    public function createDemande($userId, $title, $price, $deadline, $description) {
        $errors = $this->validateDemande($title, $price, $deadline, $description);
        if (empty($userId) || !is_numeric($userId) || intval($userId) <= 0) {
            $errors[] = "Utilisateur invalide.";
        }
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $demande = new Demande($this->pdo);
        $demande->user_id     = intval($userId);
        $demande->title       = htmlspecialchars(trim($title), ENT_QUOTES, 'UTF-8');
        $demande->price       = floatval($price);
        $demande->deadline    = trim($deadline);
        $demande->description = htmlspecialchars(trim($description), ENT_QUOTES, 'UTF-8');

        if ($demande->create()) {
            return ['success' => true, 'errors' => [], 'id' => (int)$demande->id];
        }
        return ['success' => false, 'errors' => ["Erreur lors de la création de la demande."]];
    }

    /**
     * Modifier une demande (vérifie ownership)
     */
    public function updateDemande($id, $userId, $title, $price, $deadline, $description) {
        // Vérification d'existence + ownership
        $existing = $this->getDemande($id);
        if (!$existing) {
            return ['success' => false, 'errors' => ["La demande n'existe pas."]];
        }
        if ((int)$existing['user_id'] !== (int)$userId) {
            return ['success' => false, 'errors' => ["Action non autorisée."]];
        }

        $errors = $this->validateDemande($title, $price, $deadline, $description);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $demande = new Demande($this->pdo);
        $demande->id          = intval($id);
        $demande->user_id     = intval($userId);
        $demande->title       = htmlspecialchars(trim($title), ENT_QUOTES, 'UTF-8');
        $demande->price       = floatval($price);
        $demande->deadline    = trim($deadline);
        $demande->description = htmlspecialchars(trim($description), ENT_QUOTES, 'UTF-8');

        if ($demande->update()) {
            return ['success' => true, 'errors' => [], 'id' => (int)$demande->id];
        }
        return ['success' => false, 'errors' => ["Erreur lors de la modification de la demande."]];
    }

    /**
     * Supprimer une demande (vérifie ownership)
     */
    public function deleteDemande($id, $userId) {
        $existing = $this->getDemande($id);
        if (!$existing) {
            return ['success' => false, 'errors' => ["La demande n'existe pas."]];
        }
        if ((int)$existing['user_id'] !== (int)$userId) {
            return ['success' => false, 'errors' => ["Action non autorisée."]];
        }

        $demande = new Demande($this->pdo);
        $demande->id = intval($id);

        if ($demande->delete()) {
            return ['success' => true, 'errors' => [], 'id' => (int)$id];
        }
        return ['success' => false, 'errors' => ["Erreur lors de la suppression de la demande."]];
    }

    // =============================================
    // PROPOSITION CRUD
    // =============================================

    /**
     * Obtenir une proposition par ID
     */
    public function getProposition($id) {
        if (!is_numeric($id) || intval($id) <= 0) {
            return null;
        }
        $prop = new Proposition($this->pdo);
        $prop->id = intval($id);
        $row = $prop->readOne();
        return $row ?: null;
    }

    /**
     * Lister les propositions d'une demande
     */
    public function listPropositionsByDemande($demande_id, $sort = 'recent') {
        $prop = new Proposition($this->pdo);
        return $prop->readByDemande($demande_id, $sort);
    }

    /**
     * Lister les propositions d'un utilisateur
     */
    public function listPropositionsByUser($userId, $sort = 'recent') {
        $prop = new Proposition($this->pdo);
        return $prop->readByUser($userId, $sort);
    }

    /**
     * Lister toutes les propositions (admin)
     */
    public function listAllPropositions($sort = 'recent', $demandeId = null, $search = null) {
        $prop = new Proposition($this->pdo);
        return $prop->readAll($sort, $demandeId, $search);
    }

    /**
     * Compter les propositions d'une demande
     */
    public function countPropositionsByDemande($demande_id) {
        $prop = new Proposition($this->pdo);
        return (int)$prop->countByDemande($demande_id);
    }

    /**
     * Créer une proposition
     */
    public function createProposition($userId, $demande_id, $freelancer_name, $message, $price) {
        $errors = $this->validateProposition($demande_id, $freelancer_name, $message, $price);
        if (empty($userId) || !is_numeric($userId) || intval($userId) <= 0) {
            $errors[] = "Utilisateur invalide.";
        }
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $prop = new Proposition($this->pdo);
        $prop->user_id          = intval($userId);
        $prop->demande_id       = intval($demande_id);
        $prop->freelancer_name  = htmlspecialchars(trim($freelancer_name), ENT_QUOTES, 'UTF-8');
        $prop->message          = htmlspecialchars(trim($message), ENT_QUOTES, 'UTF-8');
        $prop->price            = floatval($price);

        if ($prop->create()) {
            return ['success' => true, 'errors' => [], 'id' => (int)$prop->id];
        }
        return ['success' => false, 'errors' => ["Erreur lors de la création de la proposition."]];
    }

    /**
     * Modifier une proposition (vérifie ownership)
     */
    public function updateProposition($id, $userId, $freelancer_name, $message, $price) {
        $existing = $this->getProposition($id);
        if (!$existing) {
            return ['success' => false, 'errors' => ["La proposition n'existe pas."]];
        }
        if ((int)$existing['user_id'] !== (int)$userId) {
            return ['success' => false, 'errors' => ["Action non autorisée."]];
        }

        // demande_id existant — utilisé seulement pour la validation
        $demandeId = isset($existing['demande_id']) ? (int)$existing['demande_id'] : 0;
        $errors = $this->validateProposition($demandeId, $freelancer_name, $message, $price);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $prop = new Proposition($this->pdo);
        $prop->id              = intval($id);
        $prop->user_id         = intval($userId);
        $prop->demande_id      = $demandeId;
        $prop->freelancer_name = htmlspecialchars(trim($freelancer_name), ENT_QUOTES, 'UTF-8');
        $prop->message         = htmlspecialchars(trim($message), ENT_QUOTES, 'UTF-8');
        $prop->price           = floatval($price);

        if ($prop->update()) {
            return ['success' => true, 'errors' => [], 'id' => (int)$prop->id];
        }
        return ['success' => false, 'errors' => ["Erreur lors de la modification de la proposition."]];
    }

    /**
     * Supprimer une proposition (vérifie ownership)
     */
    public function deleteProposition($id, $userId) {
        $existing = $this->getProposition($id);
        if (!$existing) {
            return ['success' => false, 'errors' => ["La proposition n'existe pas."]];
        }
        if ((int)$existing['user_id'] !== (int)$userId) {
            return ['success' => false, 'errors' => ["Action non autorisée."]];
        }

        $prop = new Proposition($this->pdo);
        $prop->id = intval($id);

        if ($prop->delete()) {
            return ['success' => true, 'errors' => [], 'id' => (int)$id];
        }
        return ['success' => false, 'errors' => ["Erreur lors de la suppression de la proposition."]];
    }

    /**
     * Supprimer une proposition (admin — pas de check ownership)
     */
    public function deletePropositionAsAdmin($id) {
        $existing = $this->getProposition($id);
        if (!$existing) {
            return ['success' => false, 'errors' => ["La proposition n'existe pas."]];
        }

        $prop = new Proposition($this->pdo);
        $prop->id = intval($id);

        if ($prop->delete()) {
            return ['success' => true, 'errors' => [], 'id' => (int)$id];
        }
        return ['success' => false, 'errors' => ["Erreur lors de la suppression de la proposition."]];
    }
}
