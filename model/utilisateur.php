<?php
 
class Utilisateur {
 
    private $conn;
    private $table = 'utilisateurs';
 
    // Attributs
    public $id;
    public $nom;
    public $prenom;
    public $email;
    public $password;
    public $role;
    public $telephone;
    public $photo;
    public $date_inscription;
    public $is_active;
 
    public function __construct($db) {
        $this->conn = $db;
    }
 
    // =====================
    // CREATE
    // =====================
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                  (nom, prenom, email, password, role, telephone, date_inscription)
                  VALUES (:nom, :prenom, :email, :password, :role, :telephone, NOW())";
 
        $stmt = $this->conn->prepare($query);
 
        $this->nom       = htmlspecialchars(strip_tags($this->nom));
        $this->prenom    = htmlspecialchars(strip_tags($this->prenom));
        $this->email     = htmlspecialchars(strip_tags($this->email));
        $this->password  = password_hash($this->password, PASSWORD_BCRYPT);
        $this->role      = htmlspecialchars(strip_tags($this->role));
        $this->telephone = htmlspecialchars(strip_tags($this->telephone));
 
        $stmt->bindParam(':nom',       $this->nom);
        $stmt->bindParam(':prenom',    $this->prenom);
        $stmt->bindParam(':email',     $this->email);
        $stmt->bindParam(':password',  $this->password);
        $stmt->bindParam(':role',      $this->role);
        $stmt->bindParam(':telephone', $this->telephone);
 
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
 
    // =====================
    // READ ALL
    // =====================
    public function readAll() {
        $query = "SELECT id, nom, prenom, email, role, telephone, photo, is_active, date_inscription
                  FROM " . $this->table . "
                  ORDER BY date_inscription DESC";
 
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
 
    // =====================
    // READ ONE
    // =====================
    public function readOne() {
        $query = "SELECT id, nom, prenom, email, role, telephone, photo, is_active, date_inscription
                  FROM " . $this->table . "
                  WHERE id = :id
                  LIMIT 1";
 
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
 
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->nom              = $row['nom'];
            $this->prenom           = $row['prenom'];
            $this->email            = $row['email'];
            $this->role             = $row['role'];
            $this->telephone        = $row['telephone'];
            $this->photo            = $row['photo'];
            $this->is_active        = $row['is_active'];
            $this->date_inscription = $row['date_inscription'];
            return true;
        }
        return false;
    }
 
    // =====================
    // READ BY EMAIL
    // =====================
    public function readByEmail() {
        $query = "SELECT id, nom, prenom, email, password, role, telephone, photo, is_active, is_verified, date_inscription
                  FROM " . $this->table . "
                  WHERE email = :email
                  LIMIT 1";
 
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
 
    // =====================
    // UPDATE
    // =====================
    public function update() {
        $query = "UPDATE " . $this->table . "
                  SET nom = :nom,
                      prenom = :prenom,
                      email = :email,
                      telephone = :telephone,
                      photo = :photo
                  WHERE id = :id";
 
        $stmt = $this->conn->prepare($query);
 
        $this->nom       = htmlspecialchars(strip_tags($this->nom));
        $this->prenom    = htmlspecialchars(strip_tags($this->prenom));
        $this->email     = htmlspecialchars(strip_tags($this->email));
        $this->telephone = htmlspecialchars(strip_tags($this->telephone));
        $this->photo     = basename($this->photo);
 
        $stmt->bindParam(':nom',       $this->nom);
        $stmt->bindParam(':prenom',    $this->prenom);
        $stmt->bindParam(':email',     $this->email);
        $stmt->bindParam(':telephone', $this->telephone);
        $stmt->bindParam(':photo',     $this->photo);
        $stmt->bindParam(':id',        $this->id);
 
        return $stmt->execute();
    }
 
    // =====================
    // UPDATE PASSWORD
    // =====================
    public function updatePassword() {
        $query = "UPDATE " . $this->table . "
                  SET password = :password
                  WHERE id = :id";
 
        $stmt = $this->conn->prepare($query);
        $hashed = password_hash($this->password, PASSWORD_BCRYPT);
        $stmt->bindParam(':password', $hashed);
        $stmt->bindParam(':id',       $this->id);
 
        return $stmt->execute();
    }

    // =====================
    // UPDATE ROLE
    // =====================
    public function updateRole() {
        $query = "UPDATE " . $this->table . " SET role = :role WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':id',   $this->id);
        return $stmt->execute();
    }

    // =====================
    // UPDATE PHOTO
    // =====================
    public function updatePhoto() {
        $query = "UPDATE " . $this->table . " SET photo = :photo WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':photo', $this->photo);
        $stmt->bindParam(':id',    $this->id);
        return $stmt->execute();
    }

    // =====================
    // TOGGLE ACTIVE
    // =====================
    public function toggleActive() {
        $query = "UPDATE " . $this->table . "
                  SET is_active = :is_active
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':is_active', $this->is_active);
        $stmt->bindParam(':id',        $this->id);
        return $stmt->execute();
    }
 
    // =====================
    // DELETE
    // =====================
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
 
    // =====================
    // EMAIL EXISTS
    // =====================
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt  = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // =====================
    // EMAIL EXISTS EXCEPT CURRENT USER
    // =====================
    public function emailExistsExcept() {
        $query = "SELECT id FROM " . $this->table . "
                  WHERE email = :email AND id != :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':id',    $this->id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
 
    // =====================
    // COUNT
    // =====================
    public function countAll() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM " . $this->table);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
 
    public function countByRole($role) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM " . $this->table . " WHERE role = :role");
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // =====================
    // SEARCH
    // =====================
    public function search($keyword) {
        $query = "SELECT id, nom, prenom, email, role, telephone, photo, is_active, date_inscription
                  FROM " . $this->table . "
                  WHERE nom LIKE :kw OR prenom LIKE :kw OR email LIKE :kw
                  ORDER BY date_inscription DESC";
        $stmt = $this->conn->prepare($query);
        $kw = '%' . $keyword . '%';
        $stmt->bindParam(':kw', $kw);
        $stmt->execute();
        return $stmt;
    }
 
    // =====================
    // VERIFY PASSWORD
    // =====================
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    // =====================
    // READ ALL WITH PROFIL
    // =====================
    public function readAllWithProfil() {
        $query = "SELECT u.id, u.nom, u.prenom, u.email, u.role, u.telephone,
                         u.photo, u.is_active, u.date_inscription,
                         p.bio, p.competences, p.localisation, p.site_web
                  FROM utilisateurs u
                  LEFT JOIN profils p ON u.id = p.utilisateur_id
                  ORDER BY u.date_inscription DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // =====================
    // READ ONE WITH PROFIL
    // =====================
    public function readOneWithProfil() {
        $query = "SELECT u.id, u.nom, u.prenom, u.email, u.role, u.telephone,
                         u.photo, u.is_active, u.date_inscription,
                         p.bio, p.competences, p.localisation, p.site_web
                  FROM utilisateurs u
                  LEFT JOIN profils p ON u.id = p.utilisateur_id
                  WHERE u.id = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // =====================
    // READ BY ROLE WITH PROFIL
    // =====================
    public function readByRoleWithProfil($role) {
        $query = "SELECT u.id, u.nom, u.prenom, u.email, u.role, u.telephone,
                         u.photo, u.is_active, u.date_inscription,
                         p.bio, p.competences, p.localisation, p.site_web
                  FROM utilisateurs u
                  LEFT JOIN profils p ON u.id = p.utilisateur_id
                  WHERE u.role = :role
                  ORDER BY u.date_inscription DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =====================
    // FORGOT PASSWORD
    // =====================
    public function setResetToken($token, $expiry) {
        $query = "UPDATE " . $this->table . "
                  SET reset_token = :token, reset_token_expiry = :expiry
                  WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token',  $token);
        $stmt->bindParam(':expiry', $expiry);
        $stmt->bindParam(':email',  $this->email);
        return $stmt->execute();
    }

    public function readByResetToken($token) {
        $query = "SELECT * FROM " . $this->table . "
                  WHERE reset_token = :token
                  AND reset_token_expiry > NOW()
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function clearResetToken() {
        $query = "UPDATE " . $this->table . "
                  SET reset_token = NULL, reset_token_expiry = NULL
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

}
?>