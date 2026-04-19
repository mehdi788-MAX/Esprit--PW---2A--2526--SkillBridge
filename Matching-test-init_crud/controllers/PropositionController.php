<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/PropositionModel.php';

class PropositionController
{
    private $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Get all propositions
     */
    public function getAll()
    {
        $sql = "SELECT p.*, d.title AS demande_title
                FROM propositions p
                LEFT JOIN demandes d ON d.id = p.demande_id
                ORDER BY p.created_at DESC";
        try {
            $query = $this->pdo->query($sql);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching propositions: " . $e->getMessage());
        }
    }

    /**
     * Get proposition by ID
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM propositions WHERE id = :id";
        try {
            $query = $this->pdo->prepare($sql);
            $query->execute([':id' => $id]);
            $data = $query->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                $proposition = new Proposition(
                    $data['id'],
                    $data['demande_id'],
                    $data['freelancer_name'] ?? null,
                    $data['message'],
                    $data['price'],
                    $data['created_at'],
                    $data['user_id'] ?? null
                );
                return $proposition;
            }
            return null;
        } catch (Exception $e) {
            throw new Exception("Error fetching proposition: " . $e->getMessage());
        }
    }

    /**
     * Get all propositions by user ID
     */
    public function getByUserId($userId)
    {
        $sql = "SELECT * FROM propositions WHERE user_id = :userId ORDER BY created_at DESC";
        try {
            $query = $this->pdo->prepare($sql);
            $query->execute([':userId' => $userId]);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching user propositions: " . $e->getMessage());
        }
    }

    /**
     * Save new proposition
     */
    public function save(Proposition $proposition)
    {
        $sql = "INSERT INTO propositions (demande_id, freelancer_name, message, price, created_at)
                VALUES (:demande_id, :freelancer_name, :message, :price, :created_at)";
        try {
            $query = $this->pdo->prepare($sql);
            $query->execute([
                ':demande_id' => $proposition->getDemande_id(),
                ':freelancer_name' => $proposition->getFreelancer_name(),
                ':message' => $proposition->getMessage(),
                ':price' => $proposition->getPrice(),
                ':created_at' => $proposition->getCreated_at() ?: date('Y-m-d H:i:s'),
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Error saving proposition: " . $e->getMessage());
        }
    }

    /**
     * Update proposition
     */
    public function update($id, Proposition $proposition)
    {
        $sql = "UPDATE propositions 
                SET demande_id = :demande_id,
                    freelancer_name = :freelancer_name,
                    message = :message,
                    price = :price
                WHERE id = :id";
        try {
            $query = $this->pdo->prepare($sql);
            return $query->execute([
                ':id' => $id,
                ':demande_id' => $proposition->getDemande_id(),
                ':freelancer_name' => $proposition->getFreelancer_name(),
                ':message' => $proposition->getMessage(),
                ':price' => $proposition->getPrice(),
            ]);
        } catch (Exception $e) {
            throw new Exception("Error updating proposition: " . $e->getMessage());
        }
    }

    /**
     * Delete proposition
     */
    public function delete($id)
    {
        $sql = "DELETE FROM propositions WHERE id = :id";
        try {
            $query = $this->pdo->prepare($sql);
            return $query->execute([':id' => $id]);
        } catch (Exception $e) {
            throw new Exception("Error deleting proposition: " . $e->getMessage());
        }
    }

    public function getDemandesOptions(): array
    {
        try {
            $query = $this->pdo->query("SELECT id, title FROM demandes ORDER BY created_at DESC");
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching demandes options: " . $e->getMessage());
        }
    }
}
