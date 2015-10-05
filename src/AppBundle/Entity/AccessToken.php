<?php

namespace AppBundle\Entity;

use FOS\OAuthServerBundle\Entity\AccessToken as BaseAccessToken;
use Doctrine\ORM\Mapping as ORM;

class AccessToken extends BaseAccessToken
{
	protected $id;

	protected $client;

	protected $user;
}