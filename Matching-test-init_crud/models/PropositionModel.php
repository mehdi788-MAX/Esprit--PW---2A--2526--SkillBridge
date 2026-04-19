<?php

class Proposition
{
    private $id;
    private $demande_id;
    private $freelancer_name;
    private $message;
    private $price;
    private $created_at;
    private $user_id;

    /**
     * Constructor
     */
    public function __construct($id = null, $demande_id = null, $freelancer_name = null, $message = null, $price = null, $created_at = null, $user_id = null)
    {
        $this->id = $id ?? null;
        $this->demande_id = $demande_id ?? null;
        $this->freelancer_name = $freelancer_name ?? null;
        $this->message = $message ?? null;
        $this->price = $price ?? null;
        $this->created_at = $created_at ?? null;
        $this->user_id = $user_id ?? null;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getDemande_id()
    {
        return $this->demande_id;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getFreelancer_name()
    {
        return $this->freelancer_name;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function setPrice($price)
    {
        $this->price = $price;
        return $this;
    }

    public function setFreelancer_name($freelancer_name)
    {
        $this->freelancer_name = $freelancer_name;
        return $this;
    }

    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    public function setDemande_id($demande_id)
    {
        $this->demande_id = $demande_id;
        return $this;
    }

    public function setCreated_at($created_at)
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function setUser_id($user_id)
    {
        $this->user_id = $user_id;
        return $this;
    }


    public function toArray()
    {
        return [
            'id' => $this->id,
            'demande_id' => $this->demande_id,
            'freelancer_name' => $this->freelancer_name,
            'message' => $this->message,
            'price' => $this->price,
            'created_at' => $this->created_at,
            'user_id' => $this->user_id
        ];
    }
}
