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
        if (!$demande->readOne()) {
            return null;
        }
        return [
            'id'                      => (int)$demande->id,
            'title'                   => $demande->title,
            'price'                   => $demande->price,
            'deadline'                => $demande->deadline,
            'description'             => $demande->description,
            'created_at'              => $demande->created_at,
            'user_id'                 => $demande->user_id,
            'status'                  => $demande->status ?? 'open',
            'accepted_proposition_id' => $demande->accepted_proposition_id ?? null,
        ];
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

        // Le texte est stocké en clair (strip_tags supprime tout HTML).
        // L'échappement HTML est appliqué à l'AFFICHAGE seulement, jamais
        // au stockage : sinon les apostrophes deviennent &#039; en base
        // et chaque édition redouble l'encodage (&amp;#039;…).
        $demande = new Demande($this->pdo);
        $demande->user_id     = intval($userId);
        $demande->title       = trim(strip_tags($title));
        $demande->price       = floatval($price);
        $demande->deadline    = trim($deadline);
        $demande->description = trim(strip_tags($description));

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
        $demande->title       = trim(strip_tags($title));
        $demande->price       = floatval($price);
        $demande->deadline    = trim($deadline);
        $demande->description = trim(strip_tags($description));

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
        if (!$prop->readOne()) {
            return null;
        }
        return [
            'id'              => (int)$prop->id,
            'demande_id'      => $prop->demande_id,
            'user_id'         => $prop->user_id,
            'freelancer_name' => $prop->freelancer_name,
            'message'         => $prop->message,
            'price'           => $prop->price,
            'status'          => $prop->status ?? 'pending',
            'created_at'      => $prop->created_at,
        ];
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
        $prop->freelancer_name  = trim(strip_tags($freelancer_name));
        $prop->message          = trim(strip_tags($message));
        $prop->price            = floatval($price);

        if ($prop->create()) {
            // Notifier le client (auteur de la demande) qu'une nouvelle proposition est arrivée.
            // Ne bloque jamais le succès du flow si la notification échoue.
            try {
                $this->notifyClientOfNewProposition((int)$prop->id, (int)$prop->demande_id, intval($userId), $prop->freelancer_name, (float)$prop->price);
            } catch (Throwable $e) {}
            return ['success' => true, 'errors' => [], 'id' => (int)$prop->id];
        }
        return ['success' => false, 'errors' => ["Erreur lors de la création de la proposition."]];
    }

    /**
     * Notifier le client qu'une nouvelle proposition a été reçue sur sa demande.
     * Utilise la table `notifications` partagée avec le module Chat.
     */
    private function notifyClientOfNewProposition(int $propId, int $demandeId, int $freelancerId, string $freelancerName, float $price): void {
        $stmt = $this->pdo->prepare("SELECT user_id, title FROM demandes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $demandeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['user_id'])) return;
        $clientId = (int)$row['user_id'];
        if ($clientId === $freelancerId) return;

        $payload = json_encode([
            'actor_id'    => $freelancerId,
            'actor_name'  => html_entity_decode((string)$freelancerName, ENT_QUOTES, 'UTF-8') ?: 'Un freelancer',
            'demande_id'  => $demandeId,
            'proposition_id' => $propId,
            'price'       => $price,
            'preview'     => 'Nouvelle proposition sur "' . html_entity_decode((string)$row['title'], ENT_QUOTES, 'UTF-8') . '"',
        ], JSON_UNESCAPED_UNICODE);

        $ins = $this->pdo->prepare("INSERT INTO notifications (user_id, type, conversation_id, message_id, payload_json, is_read, created_at)
                                    VALUES (:uid, 'new_proposition', NULL, NULL, :payload, 0, :ts)");
        $ins->execute([
            ':uid'     => $clientId,
            ':payload' => $payload,
            ':ts'      => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Le client accepte une proposition.
     * Ferme la boucle :
     *   1. La proposition retenue passe à 'accepted'
     *   2. Toutes les propositions sœurs passent à 'declined'
     *   3. La demande est marquée 'closed' et pointe vers la proposition acceptée
     *   4. Une conversation client ↔ freelancer est ouverte (ou récupérée si elle existe)
     *   5. Un message système est posté dans la conversation
     *   6. Notifications envoyées au gagnant + perdants
     *
     * Toute l'opération est transactionnelle.
     */
    public function acceptProposition(int $propositionId, int $clientUserId): array {
        $proposition = $this->getProposition($propositionId);
        if (!$proposition) {
            return ['success' => false, 'errors' => ["Proposition introuvable."]];
        }
        $demande = $this->getDemande((int)$proposition['demande_id']);
        if (!$demande) {
            return ['success' => false, 'errors' => ["Demande introuvable."]];
        }
        if ((int)$demande['user_id'] !== $clientUserId) {
            return ['success' => false, 'errors' => ["Seul le client propriétaire de la demande peut accepter."]];
        }
        if (($demande['status'] ?? 'open') !== 'open') {
            return ['success' => false, 'errors' => ["Cette demande est déjà fermée."]];
        }
        $winnerId = (int)$proposition['user_id'];
        if ($winnerId <= 0) {
            return ['success' => false, 'errors' => ["Cette proposition n'a pas de freelancer attaché."]];
        }

        try {
            $this->pdo->beginTransaction();

            // 1. Proposition retenue → accepted
            $up1 = $this->pdo->prepare("UPDATE propositions SET status = 'accepted' WHERE id = :id");
            $up1->execute([':id' => $propositionId]);

            // 2. Sœurs → declined
            $up2 = $this->pdo->prepare("UPDATE propositions SET status = 'declined'
                                         WHERE demande_id = :did AND id != :id");
            $up2->execute([':did' => (int)$demande['id'], ':id' => $propositionId]);

            // 3. Demande → closed + pointeur
            $up3 = $this->pdo->prepare("UPDATE demandes
                                          SET status = 'closed', accepted_proposition_id = :pid
                                        WHERE id = :id");
            $up3->execute([':pid' => $propositionId, ':id' => (int)$demande['id']]);

            // 4. Conversation client ↔ freelancer (récupère si elle existe déjà)
            $convId = $this->ensureConversationBetween($clientUserId, $winnerId);

            // 5. Message système dans la conversation
            $sysContent = "Bonjour ! Votre proposition pour « "
                        . html_entity_decode((string)$demande['title'], ENT_QUOTES, 'UTF-8')
                        . " » a été acceptée. Discutons des détails ici.";
            $msg = $this->pdo->prepare("INSERT INTO messages (id_conversation, sender_id, contenu, type, date_envoi, is_seen)
                                          VALUES (:cid, :sid, :content, 'text', :ts, 0)");
            $msg->execute([
                ':cid'     => $convId,
                ':sid'     => $clientUserId,
                ':content' => $sysContent,
                ':ts'      => date('Y-m-d H:i:s'),
            ]);
            $msgId = (int)$this->pdo->lastInsertId();

            // 6a. Notifier le gagnant (avec lien vers la conversation)
            $payloadWin = json_encode([
                'demande_id'      => (int)$demande['id'],
                'demande_title'   => html_entity_decode((string)$demande['title'], ENT_QUOTES, 'UTF-8'),
                'proposition_id'  => $propositionId,
                'conversation_id' => $convId,
                'preview'         => 'Votre proposition a été acceptée 🎉',
            ], JSON_UNESCAPED_UNICODE);
            $insN = $this->pdo->prepare("INSERT INTO notifications (user_id, type, conversation_id, message_id, payload_json, is_read, created_at)
                                          VALUES (:uid, 'proposition_accepted', :cid, :mid, :payload, 0, :ts)");
            $insN->execute([
                ':uid'     => $winnerId,
                ':cid'     => $convId,
                ':mid'     => $msgId,
                ':payload' => $payloadWin,
                ':ts'      => date('Y-m-d H:i:s'),
            ]);

            // 6b. Notifier les perdants
            $losersStmt = $this->pdo->prepare("SELECT DISTINCT user_id FROM propositions
                                                WHERE demande_id = :did AND id != :id AND user_id IS NOT NULL");
            $losersStmt->execute([':did' => (int)$demande['id'], ':id' => $propositionId]);
            $payloadLose = json_encode([
                'demande_id'    => (int)$demande['id'],
                'demande_title' => html_entity_decode((string)$demande['title'], ENT_QUOTES, 'UTF-8'),
                'preview'       => 'La demande a été attribuée à un autre freelancer.',
            ], JSON_UNESCAPED_UNICODE);
            $insL = $this->pdo->prepare("INSERT INTO notifications (user_id, type, conversation_id, message_id, payload_json, is_read, created_at)
                                          VALUES (:uid, 'proposition_declined', NULL, NULL, :payload, 0, :ts)");
            foreach ($losersStmt->fetchAll(PDO::FETCH_COLUMN) as $loserId) {
                if ((int)$loserId === $winnerId) continue;
                $insL->execute([
                    ':uid'     => (int)$loserId,
                    ':payload' => $payloadLose,
                    ':ts'      => date('Y-m-d H:i:s'),
                ]);
            }

            $this->pdo->commit();
            return [
                'success'         => true,
                'errors'          => [],
                'conversation_id' => $convId,
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success' => false, 'errors' => ["Erreur lors de l'acceptation : " . $e->getMessage()]];
        }
    }

    /**
     * Récupère l'ID de la conversation entre deux utilisateurs (peu importe l'ordre user1/user2),
     * la crée si elle n'existe pas. Réutilise la table `conversations` du module Chat.
     */
    private function ensureConversationBetween(int $userA, int $userB): int {
        $stmt = $this->pdo->prepare("SELECT id_conversation FROM conversations
                                      WHERE (user1_id = :a AND user2_id = :b)
                                         OR (user1_id = :b AND user2_id = :a)
                                      LIMIT 1");
        $stmt->execute([':a' => $userA, ':b' => $userB]);
        $existing = $stmt->fetchColumn();
        if ($existing) return (int)$existing;

        $ins = $this->pdo->prepare("INSERT INTO conversations (user1_id, user2_id, date_creation)
                                     VALUES (:a, :b, :ts)");
        $ins->execute([
            ':a'  => $userA,
            ':b'  => $userB,
            ':ts' => date('Y-m-d H:i:s'),
        ]);
        return (int)$this->pdo->lastInsertId();
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
        $prop->freelancer_name = trim(strip_tags($freelancer_name));
        $prop->message         = trim(strip_tags($message));
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
