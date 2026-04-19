<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/UserModel.php';

class UserController
{
    private $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Get all users
     */
    public function getAll()
    {
        $sql = "SELECT * FROM user ORDER BY id DESC";
        try {
            $query = $this->pdo->query($sql);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching users: " . $e->getMessage());
        }
    }

    /**
     * Get user by ID
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM user WHERE id = :id";
        try {
            $query = $this->pdo->prepare($sql);
            $query->execute([':id' => $id]);
            $data = $query->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                $user = new User(
                    $data['id'],
                    $data['firstName'],
                    $data['lastName'],
                    $data['email'],
                    $data['phone'],
                    $data['password'],
                    $data['active']
                );
                return $user;
            }
            return null;
        } catch (Exception $e) {
            throw new Exception("Error fetching user: " . $e->getMessage());
        }
    }

    /**
     * Get user by email
     */
    public function getByEmail($email)
    {
        $sql = "SELECT * FROM user WHERE email = :email LIMIT 1";
        try {
            $query = $this->pdo->prepare($sql);
            $query->execute([':email' => $email]);
            $data = $query->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                $user = new User(
                    $data['id'],
                    $data['firstName'],
                    $data['lastName'],
                    $data['email'],
                    $data['phone'],
                    $data['password'],
                    $data['active']
                );
                return $user;
            }
            return null;
        } catch (Exception $e) {
            throw new Exception("Error fetching user by email: " . $e->getMessage());
        }
    }

    /**
     * Save new user
     */
    public function save(User $user)
    {
        $sql = "INSERT INTO user (firstName, lastName, email, phone, password, active)
                VALUES (:firstName, :lastName, :email, :phone, :password, :active)";
        try {
            $query = $this->pdo->prepare($sql);
            $result = $query->execute([
                ':firstName' => $user->getFirstName(),
                ':lastName' => $user->getLastName(),
                ':email' => $user->getEmail(),
                ':phone' => $user->getPhone(),
                ':password' => password_hash($user->getPassword(), PASSWORD_BCRYPT),
                ':active' => $user->getActive()
            ]);
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Error saving user: " . $e->getMessage());
        }
    }

    /**
     * Update user
     */
    public function update($id, User $user)
    {
        $sql = "UPDATE user 
                SET firstName = :firstName, lastName = :lastName, email = :email, 
                    phone = :phone, password = :password, active = :active
                WHERE id = :id";
        try {
            $query = $this->pdo->prepare($sql);
            return $query->execute([
                ':id' => $id,
                ':firstName' => $user->getFirstName(),
                ':lastName' => $user->getLastName(),
                ':email' => $user->getEmail(),
                ':phone' => $user->getPhone(),
                ':password' => password_hash($user->getPassword(), PASSWORD_BCRYPT),
                ':active' => $user->getActive()
            ]);
        } catch (Exception $e) {
            throw new Exception("Error updating user: " . $e->getMessage());
        }
    }

    /**
     * Delete user
     */
    public function delete($id)
    {
        $sql = "DELETE FROM user WHERE id = :id";
        try {
            $query = $this->pdo->prepare($sql);
            return $query->execute([':id' => $id]);
        } catch (Exception $e) {
            throw new Exception("Error deleting user: " . $e->getMessage());
        }
    }

    /**
     * Verify password
     */
    public function verifyPassword($plainPassword, $hashedPassword)
    {
        return password_verify($plainPassword, $hashedPassword);
    }

    /**
     * Get all active users
     */
    public function getAllActive()
    {
        $sql = "SELECT * FROM user WHERE active = 1 ORDER BY id DESC";
        try {
            $query = $this->pdo->query($sql);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching active users: " . $e->getMessage());
        }
    }

    /**
     * Count total users
     */
    public function count()
    {
        $sql = "SELECT COUNT(*) as total FROM user";
        try {
            $query = $this->pdo->query($sql);
            $result = $query->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (Exception $e) {
            throw new Exception("Error counting users: " . $e->getMessage());
        }
    }
}
