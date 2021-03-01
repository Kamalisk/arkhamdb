<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Model\DecklistManager;
use AppBundle\Entity\Decklist;

class DefaultController extends Controller
{

	public function indexAction()
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));

		/**
		* @var $decklist_manager DecklistManager
		*/
		$decklist_manager = $this->get('decklist_manager');
		$decklist_manager->setLimit(5);

		$typeNames = [];
		foreach($this->getDoctrine()->getRepository('AppBundle:Type')->findAll() as $type) {
			$typeNames[$type->getCode()] = $type->getName();
		}

		$decklists_by_popular = [];
		$decklists_by_recent = [];
		$factions = $this->getDoctrine()->getRepository('AppBundle:Faction')->findBy(['isPrimary' => true], ['code' => 'ASC']);

		$paginator = $decklist_manager->findDecklistsByPopularity();
		$iterator = $paginator->getIterator();
		while($iterator->valid() && count($decklists_by_popular) < 5)
		{
			$decklist = $iterator->current();
			$decklists_by_popular[] = ['faction' => $decklist->getCharacter()->getFaction(), 'decklist' => $decklist];
			$iterator->next();
		}
		$paginator = $decklist_manager->findDecklistsByAge();
		$iterator = $paginator->getIterator();
		while($iterator->valid() && count($decklists_by_recent) < 5)
		{
			$decklist = $iterator->current();
			$decklists_by_recent[] = ['faction' => $decklist->getCharacter()->getFaction(), 'decklist' => $decklist];
			$iterator->next();
		}

		$game_name = $this->container->getParameter('game_name');
		$publisher_name = $this->container->getParameter('publisher_name');

		$packs = $this->getDoctrine()->getRepository('AppBundle:Pack')->findBy([], ['dateRelease' => 'DESC']);

		return $this->render('AppBundle:Default:index.html.twig', [
		'pagetitle' =>  "$game_name Deckbuilder",
		'pagedescription' => "Build your deck for $game_name by $publisher_name. Browse the cards and the thousand of decklists submitted by the community. Publish your own decks and get feedback.",
		'decklists_by_popular' => $decklists_by_popular,
		'decklists_by_recent' => $decklists_by_recent,
		'packs' => array_slice($packs, 0, 4)
		], $response);
	}

	function rulesAction()
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));

		$page = $this->renderView('AppBundle:Default:rulesreference.html.twig',
		array("pagetitle" => "Rules", "pagedescription" => "Rules Reference"));
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
		"game_name" => $this->container->getParameter('game_name'),
		), $response);
	}

	function apiIntroAction()
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));

		return $this->render('AppBundle:Default:apiIntro.html.twig', array(
		"pagetitle" => "API",
		"game_name" => $this->container->getParameter('game_name'),
		"publisher_name" => $this->container->getParameter('publisher_name'),
		), $response);
	}
}
