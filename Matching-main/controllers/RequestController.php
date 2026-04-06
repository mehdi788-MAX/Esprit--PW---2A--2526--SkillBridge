<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/RequestModel.php';

class RequestController
{
    private $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Get all requests
     */
    public function getAll()
    {
        $sql = "SELECT * FROM request ORDER BY createdAt DESC";
        try {
            $query = $this->pdo->query($sql);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching requests: " . $e->getMessage());
        }
    }

    /**
     * Get request by ID
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM request WHERE id = :id";
        try {
            $query = $this->pdo->prepare($sql);
            $query->execute([':id' => $id]);
            $data = $query->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                $request = new Request(
                    $data['id'],
                    $data['userId'],
                    $data['title'],
                    $data['description'],
                    $data['price'],
                    $data['createdAt'],
                    $data['updatedAt']
                );
                return $request;
            }
            return null;
        } catch (Exception $e) {
            throw new Exception("Error fetching request: " . $e->getMessage());
        }
    }

    /**
     * Get all requests by user ID
     */
    public function getByUserId($userId)
    {
        $sql = "SELECT * FROM request WHERE userId = :userId ORDER BY createdAt DESC";
        try {
            $query = $this->pdo->prepare($sql);
            $query->execute([':userId' => $userId]);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching user requests: " . $e->getMessage());
        }
    }

    /**
     * Save new request
     */
    public function save(Request $request)
    {
        $sql = "INSERT INTO request (userId, title, description, price, createdAt, updatedAt)
                VALUES (:userId, :title, :description, :price, :createdAt, :updatedAt)";
        try {
            $query = $this->pdo->prepare($sql);
            $result = $query->execute([
                ':userId' => $request->getUserId(),
                ':title' => $request->getTitle(),
                ':description' => $request->getDescription(),
                ':price' => $request->getPrice(),
                ':createdAt' => $request->getCreatedAt(),
                ':updatedAt' => $request->getUpdatedAt()
            ]);
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Error saving request: " . $e->getMessage());
        }
    }

    /**
     * Update request
     */
    public function update($id, Request $request)
    {
        $sql = "UPDATE request 
                SET userId = :userId, title = :title, description = :description, 
                    price = :price, updatedAt = :updatedAt
                WHERE id = :id";
        try {
            $query = $this->pdo->prepare($sql);
            return $query->execute([
                ':id' => $id,
                ':userId' => $request->getUserId(),
                ':title' => $request->getTitle(),
                ':description' => $request->getDescription(),
                ':price' => $request->getPrice(),
                ':updatedAt' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            throw new Exception("Error updating request: " . $e->getMessage());
        }
    }

    /**
     * Delete request
     */
    public function delete($id)
    {
        $sql = "DELETE FROM request WHERE id = :id";
        try {
            $query = $this->pdo->prepare($sql);
            return $query->execute([':id' => $id]);
        } catch (Exception $e) {
            throw new Exception("Error deleting request: " . $e->getMessage());
        }
    }

    /**
     * Get requests by price range
     */
    public function getByPriceRange($minPrice, $maxPrice)
    {
        $sql = "SELECT * FROM request WHERE price BETWEEN :minPrice AND :maxPrice ORDER BY price ASC";
        try {
            $query = $this->pdo->prepare($sql);
            $query->execute([':minPrice' => $minPrice, ':maxPrice' => $maxPrice]);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching requests by price range: " . $e->getMessage());
        }
    }

    /**
     * Search requests by title or description
     */
    public function search($keyword)
    {
        $sql = "SELECT * FROM request 
                WHERE title LIKE :keyword OR description LIKE :keyword 
                ORDER BY createdAt DESC";
        try {
            $query = $this->pdo->prepare($sql);
            $query->execute([':keyword' => '%' . $keyword . '%']);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error searching requests: " . $e->getMessage());
        }
    }

    /**
     * Count total requests
     */
    public function count()
    {
        $sql = "SELECT COUNT(*) as total FROM request";
        try {
            $query = $this->pdo->query($sql);
            $result = $query->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (Exception $e) {
            throw new Exception("Error counting requests: " . $e->getMessage());
        }
    }

    /**
     * Get request with user details
     */
    public function getRequestWithUser($id)
    {
        $sql = "SELECT r.*, u.firstName, u.lastName, u.email, u.phone 
                FROM request r
                JOIN user u ON r.userId = u.id
                WHERE r.id = :id";
        try {
            $query = $this->pdo->prepare($sql);
            $query->execute([':id' => $id]);
            return $query->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching request with user: " . $e->getMessage());
        }
    }
}
