<?php

class Demande
{
    private $id;
    private $title;
    private $price;
    private $deadline;
    private $description;
    private $created_at;
    private $user_id;

    /**
     * Constructor
     */
    public function __construct($id = null, $title = null, $price = null, $deadline = null, $description = null, $created_at = null, $user_id = null)
    {
        $this->id = $id ?? null;
        $this->title = $title ?? null;
        $this->price = $price ?? null;
        $this->deadline = $deadline ?? null;
        $this->description = $description ?? null;
        $this->created_at = $created_at ?? null;
        $this->user_id = $user_id ?? null;
    }

    // Getters
    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function getDeadline()
    {
        return $this->deadline;
    }

    public function getDescription()
    {
        return $this->description;
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

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function setPrice($price)
    {
        $this->price = $price;
        return $this;
    }

    public function setDeadline($deadline)
    {
        $this->deadline = $deadline;
        return $this;
    }

    public function setDescription($description)
    {
        $this->description = $description;
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
            'id' => $this->id ?? null,
            'title' => $this->title ?? null,
            'price' => $this->price ?? null,
            'deadline' => $this->deadline ?? null,
            'description' => $this->description ?? null,
            'created_at' => $this->created_at ?? null,
            'user_id' => $this->user_id ?? null
        ];
    }
}
