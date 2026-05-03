<?php

class User
{
    private $id;
    private $nom;
    private $prenom;
    private $email;
    private $password;
    private $role;
    private $telephone;
    private $photo;
    private $date_inscription;

    /**
     * Constructor
     */
    public function __construct(
        $id = null,
        $nom = null,
        $prenom = null,
        $email = null,
        $password = null,
        $role = 'client',
        $telephone = null,
        $photo = null,
        $date_inscription = null
    ) {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->email = $email;
        $this->password = $password;
        $this->role = $role;
        $this->telephone = $telephone;
        $this->photo = $photo;
        $this->date_inscription = $date_inscription;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function getPrenom()
    {
        return $this->prenom;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function getTelephone()
    {
        return $this->telephone;
    }

    public function getPhoto()
    {
        return $this->photo;
    }

    public function getDateInscription()
    {
        return $this->date_inscription;
    }

    // Retained for compatibility if used elsewhere as alias
    public function getFirstName()
    {
        return $this->prenom;
    }

    public function getLastName()
    {
        return $this->nom;
    }

    public function getPhone()
    {
        return $this->telephone;
    }

    // Setters
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function setNom($nom)
    {
        $this->nom = $nom;
        return $this;
    }

    public function setPrenom($prenom)
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    public function setRole($role)
    {
        $this->role = $role;
        return $this;
    }

    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function setPhoto($photo)
    {
        $this->photo = $photo;
        return $this;
    }

    public function setDateInscription($date_inscription)
    {
        $this->date_inscription = $date_inscription;
        return $this;
    }

    /**
     * Convert object to array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'password' => $this->password,
            'role' => $this->role,
            'telephone' => $this->telephone,
            'photo' => $this->photo,
            'date_inscription' => $this->date_inscription
        ];
    }
}
