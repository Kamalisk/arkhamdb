<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Controller\DefaultController;
use \Michelf\Markdown;
use AppBundle\AppBundle;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends Controller
{

	public static $searchKeys = array(
			''  => 'code',
			'a' => 'flavor',
			'b' => 'claim',
			'c' => 'cycle',
			'e' => 'pack',
			'f' => 'faction',
			'g' => 'isIntrigue',
			'h' => 'reserve',
			'i' => 'illustrator',
			'k' => 'traits',
			'l' => 'isLoyal',
			'm' => 'isMilitary',
			'n' => 'income',
			'o' => 'cost',
			'p' => 'isPower',
			'r' => 'date_release',
			's' => 'strength',
			't' => 'type',
			'u' => 'isUnique',
			'v' => 'initiative',
			'x' => 'text',
			'y' => 'quantity',
	);

	public static $searchTypes = array(
			't' => 'code',
			'e' => 'code',
			'f' => 'code',
			''  => 'string',
			'a' => 'string',
			'i' => 'string',
			'k' => 'string',
			'r' => 'string',
			'x' => 'string',
			'b' => 'integer',
			'c' => 'integer',
			'h' => 'integer',
			'n' => 'integer',
			'o' => 'integer',
			's' => 'integer',
			'v' => 'integer',
			'y' => 'integer',
			'g' => 'boolean',
			'l' => 'boolean',
			'm' => 'boolean',
			'p' => 'boolean',
			'u' => 'boolean',
	);

	public function formAction()
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));

		$dbh = $this->get('doctrine')->getConnection();

		$list_packs = $this->getDoctrine()->getRepository('AppBundle:Pack')->findBy([], array("dateRelease" => "ASC", "position" => "ASC"));
		$packs = [];
		foreach($list_packs as $pack) {
			$packs[] = array(
					"name" => $pack->getName(),
					"code" => $pack->getCode(),
			);
		}

		$cycles = $this->getDoctrine()->getRepository('AppBundle:Cycle')->findBy([], array("position" => "ASC"));
		$types = $this->getDoctrine()->getRepository('AppBundle:Type')->findBy([], array("name" => "ASC"));
		$factions = $this->getDoctrine()->getRepository('AppBundle:Faction')->findBy([], array("id" => "ASC"));

		$list_traits = $dbh->executeQuery("SELECT DISTINCT c.traits FROM card c WHERE c.traits != ''")->fetchAll();
		$traits = [];
		foreach($list_traits as $card) {
			$subs = explode('.', $card["traits"]);
			foreach($subs as $sub) {
				$traits[trim($sub)] = 1;
			}
		}
		$traits = array_filter(array_keys($traits));
		sort($traits);

		$list_illustrators = $dbh->executeQuery("SELECT DISTINCT c.illustrator FROM card c WHERE c.illustrator != '' ORDER BY c.illustrator")->fetchAll();
		$illustrators = array_map(function ($card) {
			return $card["illustrator"];
		}, $list_illustrators);

		return $this->render('AppBundle:Search:searchform.html.twig', array(
				"pagetitle" => "Card Search",
				"pagedescription" => "Find all the cards of the game, easily searchable.",
				"packs" => $packs,
				"cycles" => $cycles,
				"types" => $types,
				"factions" => $factions,
				"traits" => $traits,
				"illustrators" => $illustrators,
		), $response);
	}

	public function zoomAction($card_code, Request $request)
	{
		$card = $this->getDoctrine()->getRepository('AppBundle:Card')->findOneBy(array("code" => $card_code));
		if(!$card) throw $this->createNotFoundException('Sorry, this card is not in the database (yet?)');

		$meta = $card->getName().", a ".$card->getFaction()->getName()." ".$card->getType()->getName()." card for A Game of Thrones: The Card Game Second Edition from the set ".$card->getPack()->getName()." published by Fantasy Flight Games.";

		return $this->forward(
			'AppBundle:Search:display',
			array(
			    '_route' => $request->attributes->get('_route'),
			    '_route_params' => $request->attributes->get('_route_params'),
			    'q' => $card->getCode(),
				'view' => 'card',
				'sort' => 'set',
				'pagetitle' => $card->getName(),
				'meta' => $meta
			)
		);
	}

	public function listAction($pack_code, $view, $sort, $page, Request $request)
	{
		$pack = $this->getDoctrine()->getRepository('AppBundle:Pack')->findOneBy(array("code" => $pack_code));
		if(!$pack) throw $this->createNotFoundException('This pack does not exist');

		$meta = $pack->getName().", a set of cards for A Game of Thrones: The Card Game Second Edition"
				.($pack->getDateRelease() ? " published on ".$pack->getDateRelease()->format('Y/m/d') : "")
				." by Fantasy Flight Games.";

		$key = array_search('pack', SearchController::$searchKeys);

		return $this->forward(
			'AppBundle:Search:display',
			array(
			    '_route' => $request->attributes->get('_route'),
			    '_route_params' => $request->attributes->get('_route_params'),
    	        'q' => $key.':'.$pack_code,
				'view' => $view,
				'sort' => $sort,
			    'page' => $page,
				'pagetitle' => $pack->getName(),
				'meta' => $meta,
			)
		);
	}

	public function cycleAction($cycle_code, $view, $sort, $page, Request $request)
	{
		$cycle = $this->getDoctrine()->getRepository('AppBundle:Cycle')->findOneBy(array("code" => $cycle_code));
		if(!$cycle) throw $this->createNotFoundException('This cycle does not exist');

		$meta = $cycle->getName().", a cycle of datapack for A Game of Thrones: The Card Game Second Edition published by Fantasy Flight Games.";

		$key = array_search('cycle', SearchController::$searchKeys);

		return $this->forward(
			'AppBundle:Search:display',
			array(
			    '_route' => $request->attributes->get('_route'),
			    '_route_params' => $request->attributes->get('_route_params'),
			    'q' => $key.':'.$cycle->getPosition(),
				'view' => $view,
				'sort' => $sort,
			    'page' => $page,
			    'pagetitle' => $cycle->getName(),
				'meta' => $meta,
			)
		);
	}

	// target of the search form
	public function processAction(Request $request)
	{
		$view = $request->query->get('view') ?: 'list';
		$sort = $request->query->get('sort') ?: 'name';

		$operators = array(":","!","<",">");
		$factions = $this->get('doctrine')->getRepository('AppBundle:Faction')->findAll();

		$params = [];
		if($request->query->get('q') != "") {
			$params[] = $request->query->get('q');
		}
		foreach(SearchController::$searchKeys as $key => $searchName) {
			$val = $request->query->get($key);
			if(isset($val) && $val != "") {
				if(is_array($val)) {
					if($searchName == "faction" && count($val) == count($factions)) continue;
					$params[] = $key.":".implode("|", array_map(function ($s) { return strstr($s, " ") !== FALSE ? "\"$s\"" : $s; }, $val));
				} else {
					if(strstr($val, " ") != FALSE) {
						$val = "\"$val\"";
					}
					$op = $request->query->get($key."o");
					if(!in_array($op, $operators)) {
						$op = ":";
					}
					if($key == "date_release") {
						$op = "";
					}
					$params[] = "$key$op$val";
				}
			}
		}
		$find = array('q' => implode(" ",$params));
		if($sort != "name") $find['sort'] = $sort;
		if($view != "list") $find['view'] = $view;
		return $this->redirect($this->generateUrl('cards_find').'?'.http_build_query($find));
	}

	// target of the search input
	public function findAction(Request $request)
	{
		$q = $request->query->get('q');
		$page = $request->query->get('page') ?: 1;
		$view = $request->query->get('view') ?: 'list';
		$sort = $request->query->get('sort') ?: 'name';

		// we may be able to redirect to a better url if the search is on a single set
		$conditions = $this->get('cards_data')->syntax($q);
		if(count($conditions) == 1 && count($conditions[0]) == 3 && $conditions[0][1] == ":") {
		    if($conditions[0][0] == array_search('pack', SearchController::$searchKeys)) {
		        $url = $this->get('router')->generate('cards_list', array('pack_code' => $conditions[0][2], 'view' => $view, 'sort' => $sort, 'page' => $page));
		        return $this->redirect($url);
		    }
		    if($conditions[0][0] == array_search('cycle', SearchController::$searchKeys)) {
		        $cycle_position = $conditions[0][2];
		        $cycle = $this->getDoctrine()->getRepository('AppBundle:Cycle')->findOneBy(array('position' => $cycle_position));
		        if($cycle) {
		            $url = $this->get('router')->generate('cards_cycle', array('cycle_code' => $cycle->getCode(), 'view' => $view, 'sort' => $sort, 'page' => $page));
		            return $this->redirect($url);
		        }
		    }
		}

		return $this->forward(
			'AppBundle:Search:display',
			array(
				'q' => $q,
				'view' => $view,
				'sort' => $sort,
				'page' => $page,
				'_route' => $request->get('_route')
			)
		);
	}

	public function displayAction($q, $view="card", $sort, $page=1, $pagetitle="", $meta="", Request $request)
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('cache_expiration'));

	    static $availability = [];

		$cards = [];
		$first = 0;
		$last = 0;
		$pagination = '';

		$pagesizes = array(
			'list' => 240,
			'spoiler' => 240,
			'card' => 20,
			'scan' => 20,
			'short' => 1000,
		    'zoom' => 1,
		);

		if(!array_key_exists($view, $pagesizes))
		{
			$view = 'list';
		}

		$conditions = $this->get('cards_data')->syntax($q);
		$conditions = $this->get('cards_data')->validateConditions($conditions);

		// reconstruction de la bonne chaine de recherche pour affichage
		$q = $this->get('cards_data')->buildQueryFromConditions($conditions);
		if($q && $rows = $this->get('cards_data')->get_search_rows($conditions, $sort))
		{
			if(count($rows) == 1)
			{
				$view = 'zoom';
			}

			if($pagetitle == "") {
        		if(count($conditions) == 1 && count($conditions[0]) == 3 && $conditions[0][1] == ":") {
        			if($conditions[0][0] == "e") {
        				$pack = $this->getDoctrine()->getRepository('AppBundle:Pack')->findOneBy(array("code" => $conditions[0][2]));
        				if($pack) $pagetitle = $pack->getName();
        			}
        			if($conditions[0][0] == "c") {
        				$cycle = $this->getDoctrine()->getRepository('AppBundle:Cycle')->findOneBy(array("code" => $conditions[0][2]));
        				if($cycle) $pagetitle = $cycle->getName();
        			}
        		}
			}


			// calcul de la pagination
			$nb_per_page = $pagesizes[$view];
			$first = $nb_per_page * ($page - 1);
			if($first > count($rows)) {
				$page = 1;
				$first = 0;
			}
			$last = $first + $nb_per_page;

			// data à passer à la view
			for($rowindex = $first; $rowindex < $last && $rowindex < count($rows); $rowindex++) {
				$card = $rows[$rowindex];
				$pack = $card->getPack();
				$cardinfo = $this->get('cards_data')->getCardInfo($card, false);
				if(empty($availability[$pack->getCode()])) {
					$availability[$pack->getCode()] = false;
					if($pack->getDateRelease() && $pack->getDateRelease() <= new \DateTime()) $availability[$pack->getCode()] = true;
				}
				$cardinfo['available'] = $availability[$pack->getCode()];
				if($view == "zoom") {
				    $cardinfo['reviews'] = $this->get('cards_data')->get_reviews($card);
				}
				$cards[] = $cardinfo;
			}

			$first += 1;

			// si on a des cartes on affiche une bande de navigation/pagination
			if(count($rows)) {
				if(count($rows) == 1) {
					$pagination = $this->setnavigation($card, $q, $view, $sort);
				} else {
					$pagination = $this->pagination($nb_per_page, count($rows), $first, $q, $view, $sort);
				}
			}

			// si on est en vue "short" on casse la liste par tri
			if(count($cards) && $view == "short") {

				$sortfields = array(
					'set' => 'pack_name',
					'name' => 'name',
					'faction' => 'faction_name',
					'type' => 'type_name',
					'cost' => 'cost',
					'strength' => 'strength',
				);

				$brokenlist = [];
				for($i=0; $i<count($cards); $i++) {
					$val = $cards[$i][$sortfields[$sort]];
					if($sort == "name") $val = substr($val, 0, 1);
					if(!isset($brokenlist[$val])) $brokenlist[$val] = [];
					array_push($brokenlist[$val], $cards[$i]);
				}
				$cards = $brokenlist;
			}
		}

		$searchbar = $this->renderView('AppBundle:Search:searchbar.html.twig', array(
			"q" => $q,
			"view" => $view,
			"sort" => $sort,
		));

		if(empty($pagetitle)) {
			$pagetitle = $q;
		}

		// attention si $s="short", $cards est un tableau à 2 niveaux au lieu de 1 seul
		return $this->render('AppBundle:Search:display-'.$view.'.html.twig', array(
			"view" => $view,
			"sort" => $sort,
			"cards" => $cards,
			"first"=> $first,
			"last" => $last,
			"searchbar" => $searchbar,
			"pagination" => $pagination,
			"pagetitle" => $pagetitle,
			"metadescription" => $meta,
		), $response);
	}

	public function setnavigation($card, $q, $view, $sort)
	{
	    $em = $this->getDoctrine();
	    $prev = $em->getRepository('AppBundle:Card')->findOneBy(array("pack" => $card->getPack(), "position" => $card->getPosition()-1));
	    $next = $em->getRepository('AppBundle:Card')->findOneBy(array("pack" => $card->getPack(), "position" => $card->getPosition()+1));
	    return $this->renderView('AppBundle:Search:setnavigation.html.twig', array(
	            "prevtitle" => $prev ? $prev->getName() : "",
	            "prevhref" => $prev ? $this->get('router')->generate('cards_zoom', array('card_code' => $prev->getCode())) : "",
	            "nexttitle" => $next ? $next->getName() : "",
	            "nexthref" => $next ? $this->get('router')->generate('cards_zoom', array('card_code' => $next->getCode())) : "",
	            "settitle" => $card->getPack()->getName(),
	            "sethref" => $this->get('router')->generate('cards_list', array('pack_code' => $card->getPack()->getCode())),
	    ));
	}

	public function paginationItem($q = null, $v, $s, $ps, $pi, $total)
	{
		return $this->renderView('AppBundle:Search:paginationitem.html.twig', array(
			"href" => $q == null ? "" : $this->get('router')->generate('cards_find', array('q' => $q, 'view' => $v, 'sort' => $s, 'page' => $pi)),
			"ps" => $ps,
			"pi" => $pi,
			"s" => $ps*($pi-1)+1,
			"e" => min($ps*$pi, $total),
		));
	}

	public function pagination($pagesize, $total, $current, $q, $view, $sort)
	{
		if($total < $pagesize) {
			$pagesize = $total;
		}

		$pagecount = ceil($total / $pagesize);
		$pageindex = ceil($current / $pagesize); #1-based

		$first = "";
		if($pageindex > 2) {
			$first = $this->paginationItem($q, $view, $sort, $pagesize, 1, $total);
		}

		$prev = "";
		if($pageindex > 1) {
			$prev = $this->paginationItem($q, $view, $sort, $pagesize, $pageindex - 1, $total);
		}

		$current = $this->paginationItem(null, $view, $sort, $pagesize, $pageindex, $total);

		$next = "";
		if($pageindex < $pagecount) {
			$next = $this->paginationItem($q, $view, $sort, $pagesize, $pageindex + 1, $total);
		}

		$last = "";
		if($pageindex < $pagecount - 1) {
			$last = $this->paginationItem($q, $view, $sort, $pagesize, $pagecount, $total);
		}

		return $this->renderView('AppBundle:Search:pagination.html.twig', array(
			"first" => $first,
			"prev" => $prev,
			"current" => $current,
			"next" => $next,
			"last" => $last,
			"count" => $total,
			"ellipsisbefore" => $pageindex > 3,
			"ellipsisafter" => $pageindex < $pagecount - 2,
		));
	}

}
