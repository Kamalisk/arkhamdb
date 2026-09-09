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
		$decklist_manager->setLimit(30);

		$typeNames = [];
		foreach($this->getDoctrine()->getRepository('AppBundle:Type')->findAll() as $type) {
			$typeNames[$type->getCode()] = $type->getName();
		}

		$decklists_by_popular = [];
		$decklists_by_recent = [];
		$decklists_by_investigator = [];
		$dupe_deck_list = [];

		$type = $this->getDoctrine()->getRepository('AppBundle:Type')->findOneBy(['code' => 'investigator'], ['id' => 'DESC']);
		$qb = $this->getDoctrine()->getRepository('AppBundle:Card')->createQueryBuilder('c');
		$cards = $qb->where('c.type = :type')
			->andWhere('c.deckOptions IS NOT NULL AND c.deckOptions != \'\'')
			->setParameter('type', $type)
			->orderBy('c.id', 'ASC')
			->getQuery()
			->getResult();

		$date1 = strtotime('2025-12-01');
		$date2 = time();

		$year1 = date('Y', $date1);
		$year2 = date('Y', $date2);

		$month1 = date('m', $date1);
		$month2 = date('m', $date2);

		// $diff = (($year2 - $year1) * 12) + ($month2 - $month1);
		$diff = $date2 - $date1;
		$weeks_since = ($diff / (60 * 60 * 24 * 7));
		if ($weeks_since >= 0 && $weeks_since < count($cards)) {
			$card = $cards[$weeks_since];
			if (!$card->getDeckOptions()) {
				$card = $cards[$weeks_since - 1];
			}
			if (!$card->getDeckOptions()) {
				$card = $cards[$weeks_since - 2];
			}
		} else {
			throw new \Exception("Ran out of investigators for spotlight.");
		}

		$paginator = $decklist_manager->findDecklistsByInvestigator($card, true);
		$iterator = $paginator->getIterator();
		$userCheck = [];
		while($iterator->valid() && count($decklists_by_investigator) < 8)
		{
			$decklist = $iterator->current();
			if (!isset($userCheck[$decklist->getUser()->getId()])){
				$decklists_by_investigator[] = ['faction' => $decklist->getCharacter()->getFaction(), 'decklist' => $decklist];
				$userCheck[$decklist->getUser()->getId()] = true;
				$dupe_deck_list[$decklist->getId()] = true;
			}
			$iterator->next();
		}

		$factions = $this->getDoctrine()->getRepository('AppBundle:Faction')->findBy(['isPrimary' => true], ['code' => 'ASC']);

		$paginator = $decklist_manager->findDecklistsByPopularity(false);

		$iterator = $paginator->getIterator();

		while($iterator->valid() && count($decklists_by_popular) < 8)
		{
			$decklist = $iterator->current();
			if ($decklist->getCharacter()->getCode() != $card->getCode() && !isset($dupe_deck_list[$decklist->getId()])) {
				$decklists_by_popular[] = ['faction' => $decklist->getCharacter()->getFaction(), 'decklist' => $decklist];
				$dupe_deck_list[$decklist->getId()] = true;
			}
			$iterator->next();
		}
		$paginator = $decklist_manager->findDecklistsByAge(false, false);
		$iterator = $paginator->getIterator();
		while($iterator->valid() && count($decklists_by_recent) < 8)
		{
			$decklist = $iterator->current();
			if (!isset($userCheck[$decklist->getUser()->getId()])){
				if ($decklist->getCharacter()->getCode() != $card->getCode() && !isset($dupe_deck_list[$decklist->getId()])) {
					$decklists_by_recent[] = ['faction' => $decklist->getCharacter()->getFaction(), 'decklist' => $decklist];
					$userCheck[$decklist->getUser()->getId()] = true;
					$dupe_deck_list[$decklist->getId()] = true;
				}
			}
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
		'investigator_highlight' => $card,
		'decklists_by_investigator' => $decklists_by_investigator,
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
