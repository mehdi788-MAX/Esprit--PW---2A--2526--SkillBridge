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
        $sql = "SELECT * FROM utilisateurs ORDER BY id DESC";
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
        $sql = "SELECT * FROM utilisateurs WHERE id = :id";
        try {
            $query = $this->pdo->prepare($sql);
            $query->execute([':id' => $id]);
            $data = $query->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                // Mapping DB fields to UserModel
                $user = new User(
                    $data['id'],
                    $data['nom'],
                    $data['prenom'],
                    $data['email'],
                    $data['password'],
                    $data['role'],
                    $data['telephone'],
                    $data['photo'] ?? null,
                    $data['date_inscription'] ?? null
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
        $sql = "SELECT * FROM utilisateurs WHERE email = :email LIMIT 1";
        try {
            $query = $this->pdo->prepare($sql);
            $query->execute([':email' => $email]);
            $data = $query->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                // Mapping DB fields to UserModel
                $user = new User(
                    $data['id'],
                    $data['nom'],
                    $data['prenom'],
                    $data['email'],
                    $data['password'],
                    $data['role'],
                    $data['telephone'],
                    $data['photo'] ?? null,
                    $data['date_inscription'] ?? null
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
        $sql = "INSERT INTO utilisateurs (nom, prenom, email, password, role, telephone, photo)
                VALUES (:nom, :prenom, :email, :password, :role, :telephone, :photo)";
        try {
            $query = $this->pdo->prepare($sql);

            // Getting fields from UserModel
            $result = $query->execute([
                ':nom' => $user->getLastName(),
                ':prenom' => $user->getFirstName(),
                ':email' => $user->getEmail(),
                ':password' => password_hash($user->getPassword(), PASSWORD_BCRYPT),
                ':role' => (method_exists($user, 'getRole') ? $user->getRole() : 'client'),
                ':telephone' => $user->getPhone(),
                ':photo' => (method_exists($user, 'getPhoto') ? $user->getPhoto() : null)
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
        $password = $user->getPassword();

        if (!empty($password)) {
            $password = password_hash($password, PASSWORD_BCRYPT);
        } else {
            // If password is empty, we should not update it. Fetch existing password.
            $existingUser = $this->getById($id);
            if ($existingUser) {
                $password = $existingUser->getPassword();
            } else {
                throw new Exception("User not found for update.");
            }
        }

        $sql = "UPDATE utilisateurs
                SET nom = :nom, prenom = :prenom, email = :email, 
                    password = :password, role = :role, telephone = :telephone, photo = :photo
                WHERE id = :id";
        try {
            $query = $this->pdo->prepare($sql);
            return $query->execute([
                ':id' => $id,
                ':nom' => $user->getLastName(),
                ':prenom' => $user->getFirstName(),
                ':email' => $user->getEmail(),
                ':password' => $password,
                ':role' => (method_exists($user, 'getRole') ? $user->getRole() : 'client'),
                ':telephone' => $user->getPhone(),
                ':photo' => (method_exists($user, 'getPhoto') ? $user->getPhoto() : null)
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
        $sql = "DELETE FROM utilisateurs WHERE id = :id";
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
     * Get all clients or freelancers based on Role (replaces getAllActive)
     */
    public function getAllActive()
    {
        // Now returns active-like accounts or everyone depending on use case. (Returning all non-admins as an example)
        $sql = "SELECT * FROM utilisateurs WHERE role IN ('freelancer', 'client') ORDER BY id DESC";
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
        $sql = "SELECT COUNT(*) as total FROM utilisateurs";
        try {
            $query = $this->pdo->query($sql);
            $result = $query->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (Exception $e) {
            throw new Exception("Error counting users: " . $e->getMessage());
        }
    }
}
