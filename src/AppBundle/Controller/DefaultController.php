<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{

    public function indexAction()
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));

        $types = [];
        foreach($this->getDoctrine()->getRepository('AppBundle:Type')->findAll() as $type) {
            $types[$type->getCode()] = $type;
        }

        $decklists_by_faction = [];
        $factions = $this->getDoctrine()->getRepository('AppBundle:Faction')->findBy(['is_primary' => true], ['code' => 'ASC']);
        foreach($factions as $faction) {
            $array = [];
            $array['faction'] = $faction;

            $decklist = $this->get('decklists')->popular_for_one_faction($faction, 0, 1);
            if($decklist) {
                $array['decklist'] = $decklist;

                $countByType = $this->get('deck_interface')->getCountByType($decklist);
                $counts = [];
                foreach($countByType as $code => $qty) {
                    $type = $types[$code];
                    $counts[] = $qty . " " . $type->getName() . "s";
                }
                $array['count_by_type'] = join(' &bull; ', $counts);

                $factions = [ $faction->getName() ];
                $agenda = $this->get('deck_interface')->getAgenda($decklist);
                if($agenda) {
                    $minor_faction = $this->get('deck_interface')->getMinorFaction($agenda);
                    if($minor_faction) {
                        $factions[] = $minor_faction->getName();
                    } else {
                        $factions[] = $agenda->getName();
                    }
                }
                $array['factions'] = join(' / ', $factions);
                
                $decklists_by_faction[] = $array;
            }
        }
        dump($decklists_by_faction);

        return $this->render('AppBundle:Default:index.html.twig', [
            'pagetitle' => "A Game of Thrones: The Card Game Second Edition Deckbuilder",
            'pagedescription' => "Build your deck for A Game of Thrones: The Card Game Second Edition by Fantasy Flight Games. Browse the cards and the thousand of decklists submitted by the community. Publish your own decks and get feedback.",
            'decklists_by_faction' => $decklists_by_faction
        ], $response);
    }

    function rulesAction()
    {
    	$response = new Response();
    	$response->setPublic();
    	$response->setMaxAge($this->container->getParameter('cache_expiration'));

    	$page = $this->get('cards_data')->replaceSymbols($this->renderView('AppBundle:Default:rules.html.twig',
    			array("pagetitle" => "Rules", "pagedescription" => "Refer to the official rules of the game.")));
    	$response->setContent($page);
    	return $response;
    }

    function aboutAction()
    {
    	$response = new Response();
    	$response->setPublic();
    	$response->setMaxAge($this->container->getParameter('cache_expiration'));

    	return $this->render('AppBundle:Default:about.html.twig', array(
    			"pagetitle" => "About",
    	), $response);
    }

    function apidocAction()
    {
    	$response = new Response();
    	$response->setPublic();
    	$response->setMaxAge($this->container->getParameter('cache_expiration'));

    	return $this->render('AppBundle:Default:apidoc.html.twig', array(
    			"pagetitle" => "API documentation",
    	), $response);
    }
}
