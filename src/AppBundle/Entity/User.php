<?php

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Alsciende\DeckbuilderBundle\Model\UserInterface;

/**
 * User
 */
class User extends BaseUser implements UserInterface
{
    public function __construct()
    {
        parent::__construct();
    }
}