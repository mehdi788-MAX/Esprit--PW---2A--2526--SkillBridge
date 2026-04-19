<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/DemandeModel.php';

class DemandeController
{
    private $pdo;
    private $modelClass = Demande::class;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function getAll()
    {
        $sql = "SELECT * FROM demandes ORDER BY created_at DESC";
        try {
            $query = $this->pdo->query($sql);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching demandes: " . $e->getMessage());
        }
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM demandes WHERE id = :id";
        try {
            $query = $this->pdo->prepare($sql);
            $query->execute([':id' => $id]);
            $demande = $query->fetch(PDO::FETCH_ASSOC);
            return $demande ?: null;
        } catch (Exception $e) {
            throw new Exception("Error fetching demande: " . $e->getMessage());
        }
    }

    public function getPropositionsByDemande($id)
    {
        $sql = "SELECT * FROM propositions WHERE demande_id = :id";
        try {
            $query = $this->pdo->prepare($sql);
            $query->execute([':id' => $id]);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching propositions: " . $e->getMessage());
        }
    }

    /**
     * All propositions with parent demand title (back-office flat list).
     */
    public function getAllPropositionsWithDemande(): array
    {
        $sql = "SELECT p.*, d.title AS demande_title
                FROM propositions p
                INNER JOIN demandes d ON d.id = p.demande_id
                ORDER BY p.created_at DESC";
        try {
            $query = $this->pdo->query($sql);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching propositions with demandes: " . $e->getMessage());
        }
    }

    public function save(Demande $demande): int
    {
        $sql = "INSERT INTO demandes (title, price, deadline, description, created_at, user_id)
                VALUES (:title, :price, :deadline, :description, :created_at, :user_id)";
        try {
            $query = $this->pdo->prepare($sql);
            $query->execute([
                ':title' => $demande->getTitle(),
                ':price' => $demande->getPrice(),
                ':deadline' => $demande->getDeadline(),
                ':description' => $demande->getDescription(),
                ':created_at' => $demande->getCreated_at() ?: date('Y-m-d H:i:s'),
                ':user_id' => $demande->getUser_id(),
            ]);
            return (int) $this->pdo->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Error saving demande: " . $e->getMessage());
        }
    }

    public function update(int $id, Demande $demande): bool
    {
        $sql = "UPDATE demandes
                SET title = :title,
                    price = :price,
                    deadline = :deadline,
                    description = :description,
                    user_id = :user_id
                WHERE id = :id";
        try {
            $query = $this->pdo->prepare($sql);
            return $query->execute([
                ':id' => $id,
                ':title' => $demande->getTitle(),
                ':price' => $demande->getPrice(),
                ':deadline' => $demande->getDeadline(),
                ':description' => $demande->getDescription(),
                ':user_id' => $demande->getUser_id(),
            ]);
        } catch (Exception $e) {
            throw new Exception("Error updating demande: " . $e->getMessage());
        }
    }

    public function delete(int $id): bool
    {
        try {
            $this->pdo->beginTransaction();
            $deleteProps = $this->pdo->prepare("DELETE FROM propositions WHERE demande_id = :id");
            $deleteProps->execute([':id' => $id]);

            $deleteDemande = $this->pdo->prepare("DELETE FROM demandes WHERE id = :id");
            $deleteDemande->execute([':id' => $id]);
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new Exception("Error deleting demande: " . $e->getMessage());
        }
    }
}
