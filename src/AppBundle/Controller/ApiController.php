<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\Deck;
use AppBundle\Entity\Deckslot;
use AppBundle\Entity\Decklist;
use AppBundle\Entity\Decklistslot;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends Controller
{

	public function setsAction(Request $request)
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));
		$response->headers->add(array('Access-Control-Allow-Origin' => '*'));

		$jsonp = $this->getRequest()->query->get('jsonp');

		$list_packs = $this->get('doctrine')->getRepository('AppBundle:Pack')->findBy(array(), array("dateRelease" => "ASC", "position" => "ASC"));

		// check the last-modified-since header

		$lastModified = NULL;
		/* @var $pack \AppBundle\Entity\Pack */
		foreach($list_packs as $pack) {
			if(!$lastModified || $lastModified < $pack->getDateUpdate()) {
				$lastModified = $pack->getDateUpdate();
			}
		}
		$response->setLastModified($lastModified);
		if ($response->isNotModified($request)) {
			return $response;
		}

		// build the response

		$packs = array();
		/* @var $pack \AppBundle\Entity\Pack */
		foreach($list_packs as $pack) {
			$real = count($pack->getCards());
			$max = $pack->getSize();
			$packs[] = array(
					"name" => $pack->getName(),
					"code" => $pack->getCode(),
					"position" => $pack->getPosition(),
					"cycle_position" => $pack->getCycle()->getPosition(),
					"available" => $pack->getDateRelease() ? $pack->getDateRelease()->format('Y-m-d') : '',
					"known" => intval($real),
					"total" => $max,
					"url" => $this->get('router')->generate('cards_list', array('pack_code' => $pack->getCode()), true),
			);
		}

		$content = json_encode($packs);
		if(isset($jsonp))
		{
			$content = "$jsonp($content)";
			$response->headers->set('Content-Type', 'application/javascript');
		} else
		{
			$response->headers->set('Content-Type', 'application/json');
		}
		$response->setContent($content);
		return $response;
	}

	public function cardAction($card_code, Request $request)
	{

		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));
		$response->headers->add(array('Access-Control-Allow-Origin' => '*'));

		$jsonp = $this->getRequest()->query->get('jsonp');

		$card = $this->get('doctrine')->getRepository('AppBundle:Card')->findOneBy(array("code" => $card_code));

		// check the last-modified-since header

		$lastModified = NULL;
		/* @var $card \AppBundle\Entity\Card */
		if(!$lastModified || $lastModified < $card->getDateUpdate()) {
			$lastModified = $card->getDateUpdate();
		}
		$response->setLastModified($lastModified);
		if ($response->isNotModified($request)) {
			return $response;
		}

		// build the response

		/* @var $card \AppBundle\Entity\Card */
		$card = $this->get('cards_data')->getCardInfo($card, true, "en");

		$content = json_encode($card);
		if(isset($jsonp))
		{
			$content = "$jsonp($content)";
			$response->headers->set('Content-Type', 'application/javascript');
		} else
		{
			$response->headers->set('Content-Type', 'application/json');
		}
		$response->setContent($content);
		return $response;

	}

	public function cardsAction(Request $request)
	{

		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));
		$response->headers->add(array('Access-Control-Allow-Origin' => '*'));

		$jsonp = $this->getRequest()->query->get('jsonp');

		$list_cards = $this->get('doctrine')->getRepository('AppBundle:Card')->findBy(array(), array("code" => "ASC"));

		// check the last-modified-since header

		$lastModified = NULL;
		/* @var $card \AppBundle\Entity\Card */
		foreach($list_cards as $card) {
			if(!$lastModified || $lastModified < $card->getDateUpdate()) {
				$lastModified = $card->getDateUpdate();
			}
		}
		$response->setLastModified($lastModified);
		if ($response->isNotModified($request)) {
			return $response;
		}

		// build the response

		$cards = array();
		/* @var $card \AppBundle\Entity\Card */
		foreach($list_cards as $card) {
			$cards[] = $this->get('cards_data')->getCardInfo($card, true, "en");
		}

		$content = json_encode($cards);
		if(isset($jsonp))
		{
			$content = "$jsonp($content)";
			$response->headers->set('Content-Type', 'application/javascript');
		} else
		{
			$response->headers->set('Content-Type', 'application/json');
		}
		$response->setContent($content);
		return $response;

	}

	public function setAction($pack_code)
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));
		$response->headers->add(array('Access-Control-Allow-Origin' => '*'));

		$jsonp = $this->getRequest()->query->get('jsonp');

		$format = $this->getRequest()->getRequestFormat();
		if($format !== 'json') {
			$response->setContent($this->getRequest()->getRequestFormat() . ' format not supported. Only json is supported.');
			return $response;
		}

		$pack = $this->getDoctrine()->getRepository('AppBundle:Pack')->findOneBy(array('code' => $pack_code));
		if(!$pack) die();

		$conditions = $this->get('cards_data')->syntax("e:$pack_code");
		$this->get('cards_data')->validateConditions($conditions);
		$query = $this->get('cards_data')->buildQueryFromConditions($conditions);

		$cards = array();
		$last_modified = null;
		if($query && $rows = $this->get('cards_data')->get_search_rows($conditions, "set"))
		{
			for($rowindex = 0; $rowindex < count($rows); $rowindex++) {
				if(empty($last_modified) || $last_modified < $rows[$rowindex]->getDateUpdate()) $last_modified = $rows[$rowindex]->getDateUpdate();
			}
			$response->setLastModified($last_modified);
			if ($response->isNotModified($this->getRequest())) {
				return $response;
			}
			for($rowindex = 0; $rowindex < count($rows); $rowindex++) {
				$card = $this->get('cards_data')->getCardInfo($rows[$rowindex], true, "en");
				$cards[] = $card;
			}
		}

		$content = json_encode($cards);
		if(isset($jsonp))
		{
			$content = "$jsonp($content)";
			$response->headers->set('Content-Type', 'application/javascript');
		} else
		{
			$response->headers->set('Content-Type', 'application/json');
		}
		$response->setContent($content);

		return $response;
	}


}
