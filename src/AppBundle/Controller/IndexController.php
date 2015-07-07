<?php
namespace AppBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends Controller
{
    public function indexAction(Request $request)
    {

        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));
        
        return $this->render('AppBundle:Default:index.html.twig',
                array(
                        'pagetitle' => "A Game of Thrones: The Card Game Second Edition Deckbuilder",
                        'pagedescription' => "Build your deck for A Game of Thrones: The Card Game Second Edition by Fantasy Flight Games. Browse the cards and the thousand of decklists submitted by the community. Publish your own decks and get feedback.",
                ), $response);
        
        
    }
}