<?php

namespace Alsciende\DeckbuilderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('AlsciendeDeckbuilderBundle:Default:index.html.twig', array('name' => $name));
    }
}
