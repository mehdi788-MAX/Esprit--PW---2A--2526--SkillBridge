<?php
require_once __DIR__ . '/../config.php';

class DemandeController {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function getAllDemandes() {
        return $this->pdo->query("SELECT * FROM demandes ORDER BY created_at DESC")->fetchAll();
    }

    public function getPropositionsByDemande($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM propositions WHERE demande_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
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
        return $this->pdo->query($sql)->fetchAll();
    }
}