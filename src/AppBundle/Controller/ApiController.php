<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\Deck;
use AppBundle\Entity\Deckslot;
use AppBundle\Entity\Decklist;
use AppBundle\Entity\Decklistslot;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\Criteria;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class ApiController extends Controller
{

	/**
	 * Get the description of all the packs as an array of JSON objects.
	 * 
	 * @ApiDoc(
	 *  section="Pack",
	 *  resource=true,
	 *  description="All the Packs",
	 *  parameters={
	 *    {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
	 *  },
	 * )
	 * @param Request $request
	 */
	public function listPacksAction(Request $request)
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));
		$response->headers->add(array('Access-Control-Allow-Origin' => '*'));

		$jsonp = $this->getRequest()->query->get('jsonp');

		$list_packs = $this->getDoctrine()->getRepository('AppBundle:Pack')->findBy(array(), array("dateRelease" => "ASC", "position" => "ASC"));

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

	/**
	 * Get the description of a card as a JSON object.
	 *
	 * @ApiDoc(
	 *  section="Card",
	 *  resource=true,
	 *  description="One Card",
	 *  parameters={
	 *      {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
	 *  },
	 *  requirements={
     *      {
     *          "name"="card_code",
     *          "dataType"="string",
     *          "requirement"="\d+",
     *          "description"="The code of the card to get, e.g. '01001'"
     *      },
     *      {
     *          "name"="_format",
     *          "dataType"="string",
     *          "requirement"="json",
     *          "description"="The format of the returned data. Only 'json' is supported at the moment."
     *      }
     *  },
	 * )
	 * @param Request $request
	 */
	public function getCardAction($card_code, Request $request)
	{

		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));
		$response->headers->add(array('Access-Control-Allow-Origin' => '*'));

		$jsonp = $this->getRequest()->query->get('jsonp');

		$card = $this->getDoctrine()->getRepository('AppBundle:Card')->findOneBy(array("code" => $card_code));

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


	/**
	 * Get the description of all the cards as an array of JSON objects.
	 *
	 * @ApiDoc(
	 *  section="Card",
	 *  resource=true,
	 *  description="All the Cards",
	 *  parameters={
	 *      {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
	 *  },
	 * )
	 * @param Request $request
	 */
	public function listCardsAction(Request $request)
	{

		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));
		$response->headers->add(array('Access-Control-Allow-Origin' => '*'));

		$jsonp = $this->getRequest()->query->get('jsonp');

		$list_cards = $this->getDoctrine()->getRepository('AppBundle:Card')->findBy(array(), array("code" => "ASC"));

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


	/**
	 * Get the description of all the card from a pack, as an array of JSON objects.
	 *
	 * @ApiDoc(
	 *  section="Card",
	 *  resource=true,
	 *  description="All the Cards from One Pack",
	 *  parameters={
	 *      {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
	 *  },
	 *  requirements={
     *      {
     *          "name"="pack_code",
     *          "dataType"="string",
     *          "requirement"="\d+",
     *          "description"="The code of the pack to get the cards from, e.g. 'core'"
     *      },
     *      {
     *          "name"="_format",
     *          "dataType"="string",
     *          "requirement"="json|xml|xlsx|xls",
     *          "description"="The format of the returned data. Only 'json' is supported at the moment."
     *      }
     *  },
	 * )
	 * @param Request $request
	 */
	public function listCardsByPackAction($pack_code, Request $request)
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


	/**
	 * Get the description of a decklist as a JSON object.
	 *
	 * @ApiDoc(
	 *  section="Decklist",
	 *  resource=true,
	 *  description="One Decklist",
	 *  parameters={
	 *      {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
	 *  },
	 *  requirements={
	 *      {
	 *          "name"="decklist_id",
	 *          "dataType"="integer",
	 *          "requirement"="\d+",
	 *          "description"="The numeric identifier of the decklist"
	 *      },
	 *      {
	 *          "name"="_format",
	 *          "dataType"="string",
	 *          "requirement"="json",
	 *          "description"="The format of the returned data. Only 'json' is supported at the moment."
	 *      }
	 *  },
	 * )
	 * @param Request $request
	 */
	public function getDecklistAction($decklist_id, Request $request)
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
		
		/* @var $decklist \AppBundle\Entity\Decklist */
		$decklist = $this->getDoctrine()->getRepository('AppBundle:Decklist')->find($decklist_id);
		if(!$decklist) die();
		
		$response->setLastModified($decklist->getDateUpdate());
		if ($response->isNotModified($this->getRequest())) {
			return $response;
		}
		
		$content = json_encode($decklist->getArrayExport());
		
		if (isset($jsonp)) {
			$content = "$jsonp($content)";
			$response->headers->set('Content-Type', 'application/javascript');
		} else {
			$response->headers->set('Content-Type', 'application/json');
		}
		
		$response->setContent($content);
		return $response;
		
	}

	/**
	 * Get the description of all the decklists published at a given date, as an array of JSON objects.
	 *
	 * @ApiDoc(
	 *  section="Decklist",
	 *  resource=true,
	 *  description="All the Decklists from One Day",
	 *  parameters={
	 *      {"name"="jsonp", "dataType"="string", "required"=false, "description"="JSONP callback"}
	 *  },
	 *  requirements={
     *      {
     *          "name"="date",
     *          "dataType"="string",
     *          "requirement"="\d\d\d\d-\d\d-\d\d",
     *          "description"="The date, format 'Y-m-d'"
     *      },
     *      {
     *          "name"="_format",
     *          "dataType"="string",
     *          "requirement"="json",
     *          "description"="The format of the returned data. Only 'json' is supported at the moment."
     *      }
     *  },
	 * )
	 * @param Request $request
	 */
	public function listDecklistsByDateAction($date, Request $request)
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
		
		$start = \DateTime::createFromFormat('Y-m-d', $date);
		$start->setTime(0, 0, 0);
		$end = clone $start;
		$end->add(new \DateInterval("P1D"));
		
		$expr = Criteria::expr();
		$criteria = Criteria::create();
		$criteria->where($expr->gte('dateCreation', $start));
		$criteria->andWhere($expr->lt('dateCreation', $end));
		
		/* @var $decklists \Doctrine\Common\Collections\ArrayCollection */
		$decklists = $this->getDoctrine()->getRepository('AppBundle:Decklist')->matching($criteria);
		if(!$decklists) die();
		
		$dateUpdates = $decklists->map(function ($decklist) {
			return $decklist->getDateUpdate();
		})->toArray();
		
		$response->setLastModified(max($dateUpdates));
		if ($response->isNotModified($this->getRequest())) {
			return $response;
		}
		
		$list = $decklists->map(function ($decklist) {
			return $decklist->getArrayExport();
		})->toArray();
		
		$content = json_encode($list);
		
		if (isset($jsonp)) {
			$content = "$jsonp($content)";
			$response->headers->set('Content-Type', 'application/javascript');
		} else {
			$response->headers->set('Content-Type', 'application/json');
		}
		
		$response->setContent($content);
		return $response;
		
	}
}
