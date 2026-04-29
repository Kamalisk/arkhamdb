<?php

namespace AppBundle\Entity;

class UserMeta
{
    private $id;

    private $user;

    private $meta;

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    public function getMeta()
    {
        return $this->meta;
    }

    public function setMeta($meta)
    {
        $this->meta = $meta;
        return $this;
    }
}
