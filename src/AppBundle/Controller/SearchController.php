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
	public function zoomAction($card_code, Request $request)
	{
		$card = $this->getDoctrine()->getRepository('AppBundle:Card')->findOneBy(array("code" => $card_code));
		if(!$card) throw $this->createNotFoundException('Sorry, this card is not in the database (yet?)');
		$meta = $card->getTitle().", a ".$card->getFaction()->getName()." ".$card->getType()->getName()." card for Android:Netrunner from the set ".$card->getPack()->getName()." published by Fantasy Flight Games.";
		return $this->forward(
			'AppBundle:Search:display',
			array(
			    '_route' => $request->attributes->get('_route'),
			    '_route_params' => $request->attributes->get('_route_params'),
			    'q' => $card->getCode(),
				'view' => 'card',
				'sort' => 'set',
				'title' => $card->getTitle(),
				'meta' => $meta,
				'locale' => $request->getLocale(),
				'locales' => $this->renderView('AppBundle:Default:langs.html.twig'),
			)
		);
	}
	
	public function listAction($pack_code, $view, $sort, $page, Request $request)
	{
		$pack = $this->getDoctrine()->getRepository('AppBundle:Pack')->findOneBy(array("code" => $pack_code));
		if(!$pack) throw $this->createNotFoundException('This pack does not exist');
		$meta = $pack->getName($request->getLocale()).", a set of cards for Android:Netrunner"
				.($pack->getReleased() ? " published on ".$pack->getReleased()->format('Y/m/d') : "")
				." by Fantasy Flight Games.";
		return $this->forward(
			'AppBundle:Search:display',
			array(
			    '_route' => $request->attributes->get('_route'),
			    '_route_params' => $request->attributes->get('_route_params'),
    	        'q' => 'e:'.$pack_code,
				'view' => $view,
				'sort' => $sort,
			    'page' => $page,
				'title' => $pack->getName($request->getLocale()),
				'meta' => $meta,
				'locale' => $request->getLocale(),
				'locales' => $this->renderView('AppBundle:Default:langs.html.twig'),
			)
		);
	}

	public function cycleAction($cycle_code, $view, $sort, $page, Request $request)
	{
		$cycle = $this->getDoctrine()->getRepository('AppBundle:Cycle')->findOneBy(array("code" => $cycle_code));
		if(!$cycle) throw $this->createNotFoundException('This cycle does not exist');
		$meta = $cycle->getName($request->getLocale()).", a cycle of datapack for Android:Netrunner published by Fantasy Flight Games.";
		return $this->forward(
			'AppBundle:Search:display',
			array(
			    '_route' => $request->attributes->get('_route'),
			    '_route_params' => $request->attributes->get('_route_params'),
			    'q' => 'c:'.$cycle->getNumber(),
				'view' => $view,
				'sort' => $sort,
			    'page' => $page,
			    'title' => $cycle->getName($request->getLocale()),
				'meta' => $meta,
				'locale' => $request->getLocale(),
				'locales' => $this->renderView('AppBundle:Default:langs.html.twig'),
			)
		);
	}
	
	// target of the search form
	public function processAction(Request $request)
	{
		$view = $request->query->get('view') ?: 'list';
		$sort = $request->query->get('sort') ?: 'name';
		$locale = $request->query->get('_locale') ?: $request->getLocale();
		
		$operators = array(":","!","<",">");
		
		$params = array();
		if($request->query->get('q') != "") {
			$params[] = $request->query->get('q');
		}
		$keys = array("e","t","f","s","x","p","o","n","d","r","i","l","y","a","u");
		foreach($keys as $key) {
			$val = $request->query->get($key);
			if(isset($val) && $val != "") {
				if(is_array($val)) {
					if($key == "f" && count($val) == 8) continue;
					$params[] = $key.":".implode("|", array_map(function ($s) { return strstr($s, " ") !== FALSE ? "\"$s\"" : $s; }, $val));
				} else {
					if(strstr($val, " ") != FALSE) {
						$val = "\"$val\"";
					}
					$op = $request->query->get($key."o");
					if(!in_array($op, $operators)) {
						$op = ":";
					}
					if($key == "r") {
						$op = "";
					}
					$params[] = "$key$op$val";
				}
			}
		}
		$find = array('q' => implode(" ",$params));
		if($sort != "name") $find['sort'] = $sort;
		if($view != "list") $find['view'] = $view;
		if($locale != "en") $find['_locale'] = $locale;
		return $this->redirect($this->generateUrl('cards_find').'?'.http_build_query($find));
	}

	// target of the search input
	public function findAction(Request $request)
	{
		$q = $request->query->get('q');
		$page = $request->query->get('page') ?: 1;
		$view = $request->query->get('view') ?: 'list';
		$sort = $request->query->get('sort') ?: 'name';
		$locale = $request->query->get('_locale') ?: 'en';
		
		$request->setLocale($locale);

		// we may be able to redirect to a better url if the search is on a single set
		$conditions = $this->get('cards_data')->syntax($q);
		if(count($conditions) == 1 && count($conditions[0]) == 3 && $conditions[0][1] == ":") {
		    if($conditions[0][0] == "e") {
		        $url = $this->get('router')->generate('cards_list', array('pack_code' => $conditions[0][2], 'view' => $view, 'sort' => $sort, 'page' => $page, '_locale' => $request->getLocale()));
		        return $this->redirect($url);
		    }
		    if($conditions[0][0] == "c") {
		        $cycle_number = $conditions[0][2];
		        $cycle = $this->getDoctrine()->getRepository('AppBundle:Cycle')->findOneBy(array('number' => $cycle_number));
		        if($cycle) {
		            $url = $this->get('router')->generate('cards_cycle', array('cycle_code' => $cycle->getCode(), 'view' => $view, 'sort' => $sort, 'page' => $page, '_locale' => $request->getLocale()));
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
				'locale' => $locale,
				'locales' => $this->renderView('AppBundle:Default:langs.html.twig'),
				'_route' => $request->get('_route')
			)
		);
	}
	
	public function displayAction($q, $view="card", $sort, $page=1, $title="", $meta="", $locale=null, $locales=null, Request $request)
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('short_cache'));
		
	    static $availability = array();

		if(empty($locale)) $locale = $request->getLocale();
		else $request->setLocale($locale);

		$cards = array();
		$first = 0;
		$last = 0;
		$pagination = '';
		
		$pagesizes = array(
			'list' => 200,
			'spoiler' => 200,
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

		$this->get('cards_data')->validateConditions($conditions);

		// reconstruction de la bonne chaine de recherche pour affichage
		$q = $this->get('cards_data')->buildQueryFromConditions($conditions);
		if($q && $rows = $this->get('cards_data')->get_search_rows($conditions, $sort))
		{
			if(count($rows) == 1)
			{
				$view = 'zoom';
			}
			
			if($title == "") {
        		if(count($conditions) == 1 && count($conditions[0]) == 3 && $conditions[0][1] == ":") {
        			if($conditions[0][0] == "e") {
        				$pack = $this->getDoctrine()->getRepository('AppBundle:Pack')->findOneBy(array("code" => $conditions[0][2]));
        				if($pack) $title = $pack->getName($request->getLocale());
        			}
        			if($conditions[0][0] == "c") {
        				$cycle = $this->getDoctrine()->getRepository('AppBundle:Cycle')->findOneBy(array("code" => $conditions[0][2]));
        				if($cycle) $title = $cycle->getName($request->getLocale());
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
					if($pack->getReleased() && $pack->getReleased() <= new \DateTime()) $availability[$pack->getCode()] = true;
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
					$pagination = $this->setnavigation($card, $q, $view, $sort, $locale);
				} else {
					$pagination = $this->pagination($nb_per_page, count($rows), $first, $q, $view, $sort, $locale);
				}
			}
			
			// si on est en vue "short" on casse la liste par tri
			if(count($cards) && $view == "short") {
				
				$sortfields = array(
					'set' => 'setname',
					'name' => 'title',
					'faction' => 'faction',
					'type' => 'type',
					'cost' => 'cost',
					'strength' => 'strength',
				);
				
				$brokenlist = array();
				for($i=0; $i<count($cards); $i++) {
					$val = $cards[$i][$sortfields[$sort]];
					if($sort == "name") $val = substr($val, 0, 1);
					if(!isset($brokenlist[$val])) $brokenlist[$val] = array();
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
		
		if(empty($title)) {
			$title = $q;
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
			"pagetitle" => $title,
			"metadescription" => $meta,
			"locales" => $locales,
		), $response);
	}

	public function setnavigation($card, $q, $view, $sort, $locale)
	{
	    $em = $this->getDoctrine();
	    $prev = $em->getRepository('AppBundle:Card')->findOneBy(array("pack" => $card->getPack(), "number" => $card->getNumber()-1));
	    $next = $em->getRepository('AppBundle:Card')->findOneBy(array("pack" => $card->getPack(), "number" => $card->getNumber()+1));
	    return $this->renderView('AppBundle:Search:setnavigation.html.twig', array(
	            "prevtitle" => $prev ? $prev->getTitle($locale) : "",
	            "prevhref" => $prev ? $this->get('router')->generate('cards_zoom', array('card_code' => $prev->getCode(), "_locale" => $locale)) : "",
	            "nexttitle" => $next ? $next->getTitle($locale) : "",
	            "nexthref" => $next ? $this->get('router')->generate('cards_zoom', array('card_code' => $next->getCode(), "_locale" => $locale)) : "",
	            "settitle" => $card->getPack()->getName(),
	            "sethref" => $this->get('router')->generate('cards_list', array('pack_code' => $card->getPack()->getCode(), "_locale" => $locale)),
	            "_locale" => $locale,
	    ));
	}
	
	public function paginationItem($q = null, $v, $s, $ps, $pi, $total, $locale)
	{
		return $this->renderView('AppBundle:Search:paginationitem.html.twig', array(
			"href" => $q == null ? "" : $this->get('router')->generate('cards_find', array('q' => $q, 'view' => $v, 'sort' => $s, 'page' => $pi, '_locale' => $locale)),
			"ps" => $ps,
			"pi" => $pi,
			"s" => $ps*($pi-1)+1,
			"e" => min($ps*$pi, $total),
		));
	}
	
	public function pagination($pagesize, $total, $current, $q, $view, $sort, $locale)
	{
		if($total < $pagesize) {
			$pagesize = $total;
		}
	
		$pagecount = ceil($total / $pagesize);
		$pageindex = ceil($current / $pagesize); #1-based
		
		$first = "";
		if($pageindex > 2) {
			$first = $this->paginationItem($q, $view, $sort, $pagesize, 1, $total, $locale);
		}

		$prev = "";
		if($pageindex > 1) {
			$prev = $this->paginationItem($q, $view, $sort, $pagesize, $pageindex - 1, $total, $locale);
		}
		
		$current = $this->paginationItem(null, $view, $sort, $pagesize, $pageindex, $total, $locale);

		$next = "";
		if($pageindex < $pagecount) {
			$next = $this->paginationItem($q, $view, $sort, $pagesize, $pageindex + 1, $total, $locale);
		}
		
		$last = "";
		if($pageindex < $pagecount - 1) {
			$last = $this->paginationItem($q, $view, $sort, $pagesize, $pagecount, $total, $locale);
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
