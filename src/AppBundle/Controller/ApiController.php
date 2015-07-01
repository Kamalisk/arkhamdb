<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Entity\Deck;
use AppBundle\Entity\Deckslot;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \Michelf\Markdown;
use AppBundle\Entity\Decklist;
use AppBundle\Entity\Decklistslot;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends Controller
{

	public function setsAction()
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('long_cache'));
		$response->headers->add(array('Access-Control-Allow-Origin' => '*'));
	
		$jsonp = $this->getRequest()->query->get('jsonp');
		$locale = $this->getRequest()->query->get('_locale');
		if(isset($locale)) $this->getRequest()->setLocale($locale);
	
		$data = $this->get('cards_data')->allsetsnocycledata();
	
		$content = json_encode($data);
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
	
	public function cardAction($card_code)
	{
	
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('long_cache'));
		$response->headers->add(array('Access-Control-Allow-Origin' => '*'));
	
		$jsonp = $this->getRequest()->query->get('jsonp');
		$locale = $this->getRequest()->query->get('_locale');
		if(isset($locale)) $this->getRequest()->setLocale($locale);
	
		$conditions = $this->get('cards_data')->syntax($card_code);
		$this->get('cards_data')->validateConditions($conditions);
		$query = $this->get('cards_data')->buildQueryFromConditions($conditions);
	
		$cards = array();
		$last_modified = null;
		if($query && $rows = $this->get('cards_data')->get_search_rows($conditions, "set"))
		{
			for($rowindex = 0; $rowindex < count($rows); $rowindex++) {
				if(empty($last_modified) || $last_modified < $rows[$rowindex]->getTs()) $last_modified = $rows[$rowindex]->getTs();
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
		}
	
		$response->headers->set('Content-Type', 'application/javascript');
		$response->setContent($content);
		return $response;
	
	}
	
	public function cardsAction()
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('long_cache'));
		$response->headers->add(array('Access-Control-Allow-Origin' => '*'));
	
		$jsonp = $this->getRequest()->query->get('jsonp');
		$locale = $this->getRequest()->query->get('_locale');
		if(isset($locale)) $this->getRequest()->setLocale($locale);
	
		$cards = array();
		$last_modified = null;
		if($rows = $this->get('cards_data')->get_search_rows(array(), "set", true))
		{
			for($rowindex = 0; $rowindex < count($rows); $rowindex++) {
				if(empty($last_modified) || $last_modified < $rows[$rowindex]->getTs()) $last_modified = $rows[$rowindex]->getTs();
			}
			$response->setLastModified($last_modified);
			if ($response->isNotModified($this->getRequest())) {
				return $response;
			}
			for($rowindex = 0; $rowindex < count($rows); $rowindex++) {
				$card = $this->get('cards_data')->getCardInfo($rows[$rowindex], true);
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
	
	public function setAction($pack_code)
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('long_cache'));
		$response->headers->add(array('Access-Control-Allow-Origin' => '*'));
	
		$jsonp = $this->getRequest()->query->get('jsonp');
		$locale = $this->getRequest()->query->get('_locale');
		if(isset($locale)) $this->getRequest()->setLocale($locale);
	
		$format = $this->getRequest()->getRequestFormat();
	
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
				if(empty($last_modified) || $last_modified < $rows[$rowindex]->getTs()) $last_modified = $rows[$rowindex]->getTs();
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
	
		if($format == "json")
		{
				
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
				
		}
		else if($format == "xml")
		{
				
			$cardsxml = array();
			foreach($cards as $card) {
				if(!isset($card['subtype'])) $card['subtype'] = "";
				if($card['uniqueness']) $card['subtype'] .= empty($card['subtype']) ? "Unique" : " - Unique";
				$card['subtype'] = str_replace(' - ','-',$card['subtype']);
	
				$matches = array();
				if(preg_match('/(.*): (.*)/', $card['title'], $matches)) {
					$card['title'] = $matches[1];
					$card['subtitle'] = $matches[2];
				} else {
					$card['subtitle'] = "";
				}
					
				if(!isset($card['cost'])) {
					if(isset($card['advancementcost'])) $card['cost'] = $card['advancementcost'];
					else if(isset($card['baselink'])) $card['cost'] = $card['baselink'];
					else $card['cost'] = 0;
				}
	
				if(!isset($card['strength'])) {
					if(isset($card['agendapoints'])) $card['strength'] = $card['agendapoints'];
					else if(isset($card['trash'])) $card['strength'] = $card['trash'];
					else if(isset($card['influencelimit'])) $card['strength'] = $card['influencelimit'];
					else if($card['type_code'] == "program") $card['strength'] = '-';
					else $card['strength'] = '';
				}
	
				if(!isset($card['memoryunits'])) {
					if(isset($card['minimumdecksize'])) $card['memoryunits'] = $card['minimumdecksize'];
					else $card['memoryunits'] = '';
				}
	
				if(!isset($card['factioncost'])) {
					$card['factioncost'] = '';
				}
	
				if(!isset($card['flavor'])) {
					$card['flavor'] = '';
				}
	
				if($card['faction'] == "Weyland Consortium") {
					$card['faction'] = "The Weyland Consortium";
				}
	
				$card['side'] = strtolower($card['side']);
	
				$card['text'] = str_replace("<strong>", '', $card['text']);
				$card['text'] = str_replace("</strong>", '', $card['text']);
				$card['text'] = str_replace("<sup>", '', $card['text']);
				$card['text'] = str_replace("</sup>", '', $card['text']);
				$card['text'] = str_replace("&ndash;", ' -', $card['text']);
				$card['text'] = htmlspecialchars($card['text'], ENT_QUOTES | ENT_XML1);
				$card['text'] = str_replace("\r", '&#xD;', $card['text']);
				$card['text'] = str_replace("\n", '&#xA;', $card['text']);
	
				$card['flavor'] = htmlspecialchars($card['flavor'], ENT_QUOTES | ENT_XML1);
				$card['flavor'] = str_replace("\r", '&#xD;', $card['flavor']);
				$card['flavor'] = str_replace("\n", '&#xA;', $card['flavor']);
	
				$cardsxml[] = $card;
	
			}
	
			$response->headers->set('Content-Type', 'application/xml');
			$response->setContent($this->renderView('AppBundle::apiset.xml.twig', array(
					"name" => $pack->getName(),
					"cards" => $cardsxml,
			)));
				
		}
		else if($format == 'xlsx')
		{
			$columns = array(
					"code" => "Code",
					"setname" => "Pack",
					"number" => "Number",
					"uniqueness" => "Unique",
					"title" => "Name",
					"cost" => "Cost",
					"type" => "Type",
					"subtype" => "Keywords",
					"text" => "Text",
					"side" => "Side",
					"faction" => "Faction",
					"factioncost" => "Influence cost",
					"strength" => "Strength",
					"trash" => "Trash cost",
					"memoryunits" => "MU",
					"advancementcost" => "Adv.",
					"agendapoints" => "Pts.",
					"minimumdecksize" => "Deck size",
					"influencelimit" => "Inf.",
					"baselink" => "Link",
					"illustrator" => "Illustrator",
					"flavor" => "Flavor text",
					"quantity" => "Qty",
					"limited" => "Deck limit",
			);
			$phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
			$phpExcelObject->getProperties()->setCreator("NetrunnerDB")
			->setLastModifiedBy($last_modified->format('Y-m-d'))
			->setTitle($pack->getName())
			->setSubject($pack->getName())
			->setDescription($pack->getName() . " Cards Description")
			->setKeywords("android:netrunner ".$pack->getName());
			$phpActiveSheet = $phpExcelObject->setActiveSheetIndex(0);
			$phpActiveSheet->setTitle($pack->getName());
	
			$col_index = 0;
			foreach($columns as $key => $label)
			{
				$phpCell = $phpActiveSheet->getCellByColumnAndRow($col_index++, 1);
				$phpCell->setValue($label);
			}
	
			foreach($cards as $row_index => $card)
			{
				$col_index = 0;
				foreach($columns as $key => $label)
				{
					$value = isset($card[$key]) ? $card[$key] : '';
					$phpCell = $phpActiveSheet->getCellByColumnAndRow($col_index++, $row_index+2);
					if($key == 'code')
					{
						$phpCell->setValueExplicit($value, 's');
					}
					else
					{
						$phpCell->setValue($value);
					}
				}
			}
	
			$writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007');
			$response = $this->get('phpexcel')->createStreamedResponse($writer);
			$response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
			$response->headers->set('Content-Disposition', 'attachment;filename='.$pack->getName().'.xlsx');
			$response->headers->add(array('Access-Control-Allow-Origin' => '*'));
		}
		else if($format == 'xls')
		{
			$columns = array(
					"code" => "Code",
					"setname" => "Pack",
					"number" => "Number",
					"uniqueness" => "Unique",
					"title" => "Name",
					"cost" => "Cost",
					"type" => "Type",
					"subtype" => "Keywords",
					"text" => "Text",
					"side" => "Side",
					"faction" => "Faction",
					"factioncost" => "Influence cost",
					"strength" => "Strength",
					"trash" => "Trash cost",
					"memoryunits" => "MU",
					"advancementcost" => "Adv.",
					"agendapoints" => "Pts.",
					"minimumdecksize" => "Deck size",
					"influencelimit" => "Inf.",
					"baselink" => "Link",
					"illustrator" => "Illustrator",
					"flavor" => "Flavor text",
					"quantity" => "Qty",
					"limited" => "Deck limit",
			);
			$phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
			$phpExcelObject->getProperties()->setCreator("NetrunnerDB")
			->setLastModifiedBy($last_modified->format('Y-m-d'))
			->setTitle($pack->getName())
			->setSubject($pack->getName())
			->setDescription($pack->getName() . " Cards Description")
			->setKeywords("android:netrunner ".$pack->getName());
			$phpActiveSheet = $phpExcelObject->setActiveSheetIndex(0);
			$phpActiveSheet->setTitle($pack->getName());
	
			$col_index = 0;
			foreach($columns as $key => $label)
			{
				$phpCell = $phpActiveSheet->getCellByColumnAndRow($col_index++, 1);
				$phpCell->setValue($label);
			}
	
			foreach($cards as $row_index => $card)
			{
				$col_index = 0;
				foreach($columns as $key => $label)
				{
					$value = isset($card[$key]) ? $card[$key] : '';
					$phpCell = $phpActiveSheet->getCellByColumnAndRow($col_index++, $row_index+2);
					if($key == 'code')
					{
						$phpCell->setValueExplicit($value, 's');
					}
					else
					{
						$phpCell->setValue($value);
					}
				}
			}
	
			$writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');
			$response = $this->get('phpexcel')->createStreamedResponse($writer);
			$response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
			$response->headers->set('Content-Disposition', 'attachment;filename='.$pack->getName().'.xls');
			$response->headers->add(array('Access-Control-Allow-Origin' => '*'));
		}
		return $response;
	}
	
	
    public function decklistAction ($decklist_id)
    {

        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('long_cache'));
        $response->headers->add(array(
                'Access-Control-Allow-Origin' => '*'
        ));
        
        $jsonp = $this->getRequest()->query->get('jsonp');
        $locale = $this->getRequest()->query->get('_locale');
        if (isset($locale))
            $this->getRequest()->setLocale($locale);
        
        $dbh = $this->get('doctrine')->getConnection();
        $rows = $dbh->executeQuery(
                "SELECT
				d.id,
				d.ts,
				d.name,
				d.creation,
				d.description,
				u.username
				from decklist d
				join user u on d.user_id=u.id
				where d.id=?
				", array(
                        $decklist_id
                ))->fetchAll();
        
        if (empty($rows)) {
            throw new NotFoundHttpException('Wrong id');
        }
        
        $decklist = $rows[0];
        $decklist['id'] = intval($decklist['id']);
        
        $lastModified = new \DateTime($decklist['ts']);
        $response->setLastModified($lastModified);
        if ($response->isNotModified($this->getRequest())) {
            return $response;
        }
        unset($decklist['ts']);
        
        $cards = $dbh->executeQuery("SELECT
				c.code card_code,
				s.quantity qty
				from decklistslot s
				join card c on s.card_id=c.id
				where s.decklist_id=?
				order by c.code asc", array(
                $decklist_id
        ))->fetchAll();
        
        $decklist['cards'] = array();
        foreach ($cards as $card) {
            $decklist['cards'][$card['card_code']] = intval($card['qty']);
        }
        
        $content = json_encode($decklist);
        if (isset($jsonp)) {
            $content = "$jsonp($content)";
            $response->headers->set('Content-Type', 'application/javascript');
        } else {
            $response->headers->set('Content-Type', 'application/json');
        }
        
        $response->setContent($content);
        return $response;
    
    }

    public function decklistsAction ($date)
    {

        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('long_cache'));
        $response->headers->add(array(
                'Access-Control-Allow-Origin' => '*'
        ));
        
        $jsonp = $this->getRequest()->query->get('jsonp');
        $locale = $this->getRequest()->query->get('_locale');
        if (isset($locale))
            $this->getRequest()->setLocale($locale);
        
        $dbh = $this->get('doctrine')->getConnection();
        $decklists = $dbh->executeQuery(
                "SELECT
				d.id,
				d.ts,
				d.name,
				d.creation,
				d.description,
				u.username
				from decklist d
				join user u on d.user_id=u.id
				where substring(d.creation,1,10)=?
				", array(
                        $date
                ))->fetchAll();
        
        $lastTS = null;
        foreach ($decklists as $i => $decklist) {
            $lastTS = max($lastTS, $decklist['ts']);
            unset($decklists[$i]['ts']);
        }
        $response->setLastModified(new \DateTime($lastTS));
        if ($response->isNotModified($this->getRequest())) {
            return $response;
        }
        
        foreach ($decklists as $i => $decklist) {
            $decklists[$i]['id'] = intval($decklist['id']);
            
            $cards = $dbh->executeQuery("SELECT
				c.code card_code,
				s.quantity qty
				from decklistslot s
				join card c on s.card_id=c.id
				where s.decklist_id=?
				order by c.code asc", array(
                    $decklists[$i]['id']
            ))->fetchAll();
            
            $decklists[$i]['cards'] = array();
            foreach ($cards as $card) {
                $decklists[$i]['cards'][$card['card_code']] = intval($card['qty']);
            }
        }
        
        $content = json_encode($decklists);
        if (isset($jsonp)) {
            $content = "$jsonp($content)";
            $response->headers->set('Content-Type', 'application/javascript');
        } else {
            $response->headers->set('Content-Type', 'application/json');
        }
        
        $response->setContent($content);
        return $response;
    
    }

    public function decksAction ()
    {
        $response = new Response();
        $response->setPrivate();
        $response->headers->set('Content-Type', 'application/json');
        
        $locale = $this->getRequest()->query->get('_locale');
        if (isset($locale))
            $this->getRequest()->setLocale($locale);
        
        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();
        
        if (! $user) {
            throw new UnauthorizedHttpException("You are not logged in.");
        }
        
        $response->setContent(json_encode($this->get('decks')->getByUser($user, TRUE)));
        return $response;
    }
 
    public function saveDeckAction($deck_id)
    {
        $response = new Response();
        $response->setPrivate();
        $response->headers->set('Content-Type', 'application/json');

        $user = $this->getUser();
        if (count($user->getDecks()) > $user->getMaxNbDecks())
        {
            $response->setContent(json_encode(array('success' => false, 'message' => 'You have reached the maximum number of decks allowed. Delete some decks or increase your reputation.')));
            return $response;
        }
        
        $request = $this->getRequest();
        $name = filter_var($request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $decklist_id = filter_var($request->get('decklist_id'), FILTER_SANITIZE_NUMBER_INT);
        $description = filter_var($request->get('description'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $tags = filter_var($request->get('tags'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $content = json_decode($request->get('content'), true);
        if (! count($content))
        {
            $response->setContent(json_encode(array('success' => false, 'message' => 'Cannot import empty deck')));
            return $response;
        }
        
        $em = $this->getDoctrine()->getManager();
        
        if ($deck_id) {
            $deck = $em->getRepository('AppBundle:Deck')->find($deck_id);
            if ($user->getId() != $deck->getUser()->getId())
            {
                $response->setContent(json_encode(array('success' => false, 'message' => 'Wrong user')));
                return $response;
            }
        } else {
            $deck = new Deck();
        }
        
        // $content is formatted as {card_code,qty}, expected {card_code=>qty}
        $slots = array();
        foreach($content as $arr) {
            $slots[$arr['card_code']] = intval($arr['qty']);
        }
        
        $deck_id = $this->get('decks')->saveDeck($this->getUser(), $deck, $decklist_id, $name, $description, $tags, $slots, $deck_id ? $deck : null);
        
        if(isset($deck_id))
        {
            $response->setContent(json_encode(array('success' => true, 'message' => $this->get('decks')->getById($deck_id, TRUE))));
            return $response;
        }
        else
        {
            $response->setContent(json_encode(array('success' => false, 'message' => 'Unknown error')));
            return $response;
        }
    }
    
    public function publishAction($deck_id, Request $request)
    {
        $response = new Response();
        $response->setPrivate();
        $response->headers->set('Content-Type', 'application/json');
        
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        /* @var $deck \AppBundle\Entity\Deck */
        $deck = $this->getDoctrine()
        ->getRepository('AppBundle:Deck')
        ->find($deck_id);
        if ($this->getUser()->getId() != $deck->getUser()->getId()) {
            $response->setContent(json_encode(array('success' => false, 'message' => "You don't have access to this deck.")));
            return $response;
        }
        
        $judge = $this->get('judge');
        $analyse = $judge->analyse($deck->getCards());
        if (is_string($analyse)) {
            $response->setContent(json_encode(array('success' => false, 'message' => $judge->problem($analyse))));
            return $response;
        }
        
        $new_content = json_encode($deck->getContent());
        $new_signature = md5($new_content);
        $old_decklists = $this->getDoctrine()
        ->getRepository('AppBundle:Decklist')
        ->findBy(array(
                'signature' => $new_signature
        ));
        foreach ($old_decklists as $decklist) {
            if (json_encode($decklist->getContent()) == $new_content) {
                $response->setContent(json_encode(array('success' => false, 'message' => "That decklist already exists.")));
                return $response;
            }
        }
        
        $name = filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $name = substr($name, 0, 60);
        if (empty($name)) {
            $name = $deck->getName();
        }
        
        $rawdescription = trim($request->request->get('description'));
        if (empty($rawdescription)) {
            $rawdescription = $deck->getDescription();
        }
        $description = $this->get('texts')->markdown($rawdescription);
        
        $decklist = new Decklist();
        $decklist->setName($name);
        $decklist->setPrettyname(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($name)));
        $decklist->setRawdescription($rawdescription);
        $decklist->setDescription($description);
        $decklist->setUser($this->getUser());
        $decklist->setCreation(new \DateTime());
        $decklist->setTs(new \DateTime());
        $decklist->setSignature($new_signature);
        $decklist->setIdentity($deck->getIdentity());
        $decklist->setFaction($deck->getIdentity()
                ->getFaction());
        $decklist->setSide($deck->getSide());
        $decklist->setLastPack($deck->getLastPack());
        $decklist->setNbvotes(0);
        $decklist->setNbfavorites(0);
        $decklist->setNbcomments(0);
        foreach ($deck->getSlots() as $slot) {
            $card = $slot->getCard();
            $decklistslot = new Decklistslot();
            $decklistslot->setQuantity($slot->getQuantity());
            $decklistslot->setCard($card);
            $decklistslot->setDecklist($decklist);
            $decklist->getSlots()->add($decklistslot);
        }
        if (count($deck->getChildren())) {
            $decklist->setPrecedent($deck->getChildren()[0]);
        } else
        if ($deck->getParent()) {
            $decklist->setPrecedent($deck->getParent());
        }
        $decklist->setParent($deck);
        
        $em->persist($decklist);
        $em->flush();
        
        $response->setContent(json_encode(array('success' => true, 'message' => array("id" => $decklist->getId(), "url" => $this->generateUrl('decklist_detail', array(
                'decklist_id' => $decklist->getId(),
                'decklist_name' => $decklist->getPrettyName()
        ))))));
        return $response;
        
    }
    
    public function loadDeckAction($deck_id)
    {
        $response = new Response();
        $response->setPrivate();
        $response->headers->set('Content-Type', 'application/json');
        
        $locale = $this->getRequest()->query->get('_locale');
        if (isset($locale))
            $this->getRequest()->setLocale($locale);
        
        /* @var $user \AppBundle\Entity\User */
        $user = $this->getUser();
        
        if (! $user) {
            throw new UnauthorizedHttpException("You are not logged in.");
        }
        
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $deck = $em->getRepository('AppBundle:Deck')->find($deck_id);
        if ($user->getId() != $deck->getUser()->getId())
        {
            $response->setContent(json_encode(array('success' => false, 'message' => 'Wrong user')));
            return $response;
        }
        
        $deck = $this->get('decks')->getById($deck_id, TRUE);
        $response->setContent(json_encode($deck));
        return $response;
    }
    
}
