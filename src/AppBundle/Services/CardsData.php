<?php


namespace AppBundle\Services;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use AppBundle\Helper\DeckValidationHelper;

/*
 *
 */
class CardsData
{
	public function __construct(Registry $doctrine, RequestStack $request_stack, Router $router, AssetsHelper $assets_helper, DeckValidationHelper $deckValidationHelper, $rootDir) {
		$this->doctrine = $doctrine;
		$this->request_stack = $request_stack;
		$this->router = $router;
		$this->assets_helper = $assets_helper;
		$this->rootDir = $rootDir;
		$this->deckValidationHelper = $deckValidationHelper;
	}

	/**
	 * Searches for and replaces symbol tokens with markup in a given text.
	 * @param string $text
	 * @return string
	 */
	public function replaceSymbols($text)
	{
		static $displayTextReplacements = [
			'[eldersign]' => '<span class="icon-eldersign" title="Elder Sign"></span>',
			'[elder_sign]' => '<span class="icon-elder_sign" title="Elder Sign"></span>',
			'[cultist]' => '<span class="icon-cultist" title="Cultist"></span>',
			'[tablet]' => '<span class="icon-tablet" title="Tablet"></span>',
			'[elder_thing]' => '<span class="icon-elder_thing" title="Elder Thing"></span>',
			'[auto_fail]' => '<span class="icon-auto_fail" title="Auto Fail"></span>',
			'[fail]' => '<span class="icon-auto_fail" title="Auto Fail"></span>',
			'[skull]' => '<span class="icon-skull" title="Skull"></span>',
			'[reaction]' => '<span class="icon-reaction" title="Reaction"></span>',
			'[action]' => '<span class="icon-action" title="Action"></span>',
			'[lightning]' => '<span class="icon-lightning" title="Fast Action"></span>',
			'[fast]' => '<span class="icon-lightning" title="Fast Action"></span>',
			'[free]' => '<span class="icon-lightning" title="Fast Action"></span>',
			'[willpower]' => '<span class="icon-willpower" title="Willpower"></span>',
			'[intellect]' => '<span class="icon-intellect" title="Intellect"></span>',
			'[combat]' => '<span class="icon-combat" title="Combat"></span>',
			'[agility]' => '<span class="icon-agility" title="Agility"></span>',
			'[wild]' => '<span class="icon-wild" title="Any Skill"></span>',
			'[guardian]' => '<span class="icon-guardian" title="Guardian"></span>',
			'[survivor]' => '<span class="icon-survivor" title="Survivor"></span>',
			'[rogue]' => '<span class="icon-rogue" title="Rogue"></span>',
			'[seeker]' => '<span class="icon-seeker" title="Seeker"></span>',
			'[mystic]' => '<span class="icon-mystic" title="Mystic"></span>',
			'[neutral]' => '<span class="icon-neutral" title="Neutral">Neutral</span>',
			'[neutral]' => '<span class="icon-per_investigator" title="Per Investigator"></span>'
		];
		
		return str_replace(array_keys($displayTextReplacements), array_values($displayTextReplacements), $text);
	}
	
	/**
	 * Parse deck requirements/restrictions and convert to array
	 * @param string $text
	 * @return Array
	 */
	public function parseDeckRequirements($text)
	{
		$return_requirements = [];
		$restrictions = explode(",", $text);
		foreach($restrictions as $restriction) {
			if (trim($restriction)){
				$matches = [];
				//preg_match ( "/([A-Za-z0-9]+)(?::([A-Za-z0-9]+))+/" , trim($restriction), $matches );
				$params = explode(":", $restriction);
				//$text .= print_r($matches,1);	
				if (isset($params[0])){
					$type = trim($params[0]);
					if (!isset($return_requirements[$type])){
						$return_requirements[$type] = [];
					}
					$req = [];
					if (isset($params[1])){
						if (intval(trim($params[1]))){
							$req[] = trim($params[1]);
						}else {
							$return_requirements[$type][trim($params[1])] = trim($params[1]);
							$req[] = trim($params[1]);	
						}						
					}
					if (isset($params[2])){
						//$req[] = $params[2];
					}
					if (isset($params[3])){
						//$req[] = $params[3];
					}
					$return_requirements[$type][] = $req;
				}
			}
		}
		return $return_requirements;
	}
	
	public function splitInParagraphs($text)
	{
		if(empty($text)) return '';
		return implode(array_map(function ($l) { return "<p>$l</p>"; }, preg_split('/[\r\n]+/', $text)));	
	}
	
	public function allsetsnocycledata()
	{
		$list_packs = $this->doctrine->getRepository('AppBundle:Pack')->findBy(array(), array("dateRelease" => "ASC", "position" => "ASC"));
		$packs = array();
		foreach($list_packs as $pack) {
			$real = count($pack->getCards());
			$max = $pack->getSize();
			$packs[] = array(
					"name" => $pack->getName(),
					"code" => $pack->getCode(),
					"number" => $pack->getPosition(),
					"available" => $pack->getDateRelease() ? $pack->getDateRelease()->format('Y-m-d') : '',
					"known" => intval($real),
					"total" => $max,
					"url" => $this->router->generate('cards_list', array('pack_code' => $pack->getCode()), UrlGeneratorInterface::ABSOLUTE_URL),
			);
		}
		return $packs;
	}
	
	public function allsetsdata()
	{
		$list_cycles = $this->doctrine->getRepository('AppBundle:Cycle')->findBy(array(), array("position" => "ASC"));
		$cycles = array();
		foreach($list_cycles as $cycle) {
			$packs = array();
			$sreal=0; $smax = 0;
			foreach($cycle->getPacks() as $pack) {
				$real = count($pack->getCards());
				$sreal += $real;
				$max = $pack->getSize();
				$smax += $max;
				$packs[] = array(
						"name" => $pack->getName(),
						"code" => $pack->getCode(),
						"available" => $pack->getDateRelease() ? $pack->getDateRelease()->format('Y-m-d') : '',
						"known" => intval($real),
						"total" => $max,
						"url" => $this->router->generate('cards_list', array('pack_code' => $pack->getCode()), UrlGeneratorInterface::ABSOLUTE_URL),
						"search" => "e:".$pack->getCode()
				);
			}
			if($cycle->getSize() === 1) {
				$cycles[] = $packs[0];
			}
			else {
				$cycles[] = array(
						"name" => $cycle->getName(),
						"code" => $cycle->getCode(),
						"known" => intval($sreal),
						"total" => $smax,
						"url" => $this->router->generate('cards_cycle', array('cycle_code' => $cycle->getCode()), UrlGeneratorInterface::ABSOLUTE_URL),
						"search" => 'c:'.$cycle->getCode(),
						"packs" => $packs,
				);
			}
		}
		return $cycles;
	}
	
	
	public function getPrimaryFactions()
	{
		$factions = $this->doctrine->getRepository('AppBundle:Faction')->findBy(array("isPrimary" => TRUE), array("code" => "ASC"));
		return $factions;
	}

	public function get_search_rows($conditions, $sortorder, $forceempty = false, $encounter = false)
	{
		$i=0;

		// construction de la requete sql
		$qb = $this->doctrine->getRepository('AppBundle:Card')->createQueryBuilder('c');
		$qb->leftJoin('c.pack', 'p')
			->leftJoin('p.cycle', 'y')
			->leftJoin('c.type', 't')
			->leftJoin('c.subtype', 'b')
			->leftJoin('c.faction', 'f');
		$qb2 = null;
		$qb3 = null;
		if ($encounter === "encounter"){
			$qb->andWhere("(c.encounter IS NOT NULL)");
		}else if ($encounter === true || $encounter === "1"){
			//$qb->andWhere("(c.encounter IS NULL)");
		}else {
			$qb->andWhere("(c.encounter IS NULL)");
		}
		
		foreach($conditions as $condition)
		{
			$searchCode = array_shift($condition);
			$searchName = \AppBundle\Controller\SearchController::$searchKeys[$searchCode];
			$searchType = \AppBundle\Controller\SearchController::$searchTypes[$searchCode];
			$operator = array_shift($condition);

			switch($searchType)
			{
				case 'boolean':
				{
					switch($searchCode)
					{
						default:
						{
							if(($operator == ':' && $condition[0]) || ($operator == '!' && !$condition[0])) {
								$qb->andWhere("(c.$searchName = 1)");
							} else {
								$qb->andWhere("(c.$searchName = 0)");
							}
							$i++;
							break;
						}
					}
					break;
				}
				case 'integer':
				{
					switch($searchCode)
					{
						case 'y': // cycle
						{
							$or = [];
							foreach($condition as $arg) {
								switch($operator) {
									case ':': $or[] = "(y.position = ?$i)"; break;
									case '!': $or[] = "(y.position != ?$i)"; break;
								}
								$qb->setParameter($i++, $arg);
							}
							$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
							break;
						}
						default:
						{
							$or = [];
							foreach($condition as $arg) {
								switch($operator) {
									case ':': $or[] = "(c.$searchName = ?$i)"; break;
									case '!': $or[] = "(c.$searchName != ?$i)"; break;
									case '<': $or[] = "(c.$searchName < ?$i)"; break;
									case '>': $or[] = "(c.$searchName > ?$i)"; break;
								}
								$qb->setParameter($i++, $arg);
							}
							$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
							break;
						}
					}
					break;
				}
				case 'code':
				{
					switch($searchCode)
					{
						case 'e':
						{
							$or = [];
							foreach($condition as $arg) {
								switch($operator) {
									case ':': $or[] = "(p.code = ?$i)"; break;
									case '!': $or[] = "(p.code != ?$i)"; break;
									case '<':
										if(!isset($qb2)) {
											$qb2 = $this->doctrine->getRepository('AppBundle:Pack')->createQueryBuilder('p2');
											$or[] = $qb->expr()->lt('p.dateRelease', '(' . $qb2->select('p2.dateRelease')->where("p2.code = ?$i")->getDql() . ')');
										}
										break;
									case '>':
										if(!isset($qb3)) {
											$qb3 = $this->doctrine->getRepository('AppBundle:Pack')->createQueryBuilder('p3');
											$or[] = $qb->expr()->gt('p.dateRelease', '(' . $qb3->select('p3.dateRelease')->where("p3.code = ?$i")->getDql() . ')');
										}
										break;
								}
								$qb->setParameter($i++, $arg);
							}
							$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
							break;
						}
						default: // type and faction
						{
							$or = [];
							foreach($condition as $arg) {
								switch($operator) {
									case ':': $or[] = "($searchCode.code = ?$i)"; break;
									case '!': $or[] = "($searchCode.code != ?$i)"; break;
								}
								$qb->setParameter($i++, $arg);
							}
							$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
							break;
						}
					}
					break;
				}
				case 'string': {
					switch($searchCode)
					{
						case '': // name or index
						{
							$or = [];
							foreach($condition as $arg) {
								$code = preg_match('/^\d\d(\d\d\d|_[a-zA-Z0-9]+)$/u', $arg);
								$acronym = preg_match('/^[A-Z]{2,}$/', $arg);
								if($code) {
									$or[] = "(c.code = ?$i)";
									$qb->setParameter($i++, $arg);
								} else if($acronym) {
									$or[] = "(BINARY(c.name) like ?$i or BINARY(c.backName) like ?$i)";
									$qb->setParameter($i++, "%$arg%");
									$like = implode('% ', str_split($arg));
									$or[] = "(REPLACE(c.name, '-', ' ') like ?$i or REPLACE(c.backName, '-', ' ') like ?$i)";
									$qb->setParameter($i++, "$like%");
								} else {
									$or[] = "(c.name like ?$i or c.backName like ?$i)";
									$qb->setParameter($i++, "%$arg%");
								}
							}
							$qb->andWhere(implode(" or ", $or));
							break;
						}
						case 'x': // text
						{
							$or = [];
							foreach($condition as $arg) {
								switch($operator) {
									case ':': $or[] = "(c.text like ?$i or c.backText like ?$i)"; break;
									case '!': $or[] = "(c.text not like ?$i and (c.backText is null or c.backText not like ?$i))"; break;
								}
								$qb->setParameter($i++, "%$arg%");
								
							}
							$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
							break;
						}
						case 'v': // flavor
						{
							$or = [];
							foreach($condition as $arg) {
								switch($operator) {
									case ':': $or[] = "(c.flavor like ?$i or c.backFlavor like ?$i)"; break;
									case '!': $or[] = "(c.flavor not like ?$i and (c.backFlavor is null or c.backFlavor not like ?$i))"; break;
								}
								$qb->setParameter($i++, "%$arg%");
							}
							$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
							break;
						}
						case 'k': // subtype (traits)
						{
							$or = [];
							foreach($condition as $arg) {
								switch($operator) {
									case ':':
										$or[] = "((c.traits = ?$i) or (c.traits like ?".($i+1).") or (c.traits like ?".($i+2).") or (c.traits like ?".($i+3)."))";
										$qb->setParameter($i++, "$arg.");
										$qb->setParameter($i++, "$arg. %");
										$qb->setParameter($i++, "%. $arg.");
										$qb->setParameter($i++, "%. $arg. %");
										break;
									case '!':
										$or[] = "(c.traits is null or ((c.traits != ?$i) and (c.traits not like ?".($i+1).") and (c.traits not like ?".($i+2).") and (c.traits not like ?".($i+3).")))";
										$qb->setParameter($i++, "$arg.");
										$qb->setParameter($i++, "$arg. %");
										$qb->setParameter($i++, "%. $arg.");
										$qb->setParameter($i++, "%. $arg. %");
										break;
								}
							}
							$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
							break;
						}
						case 'l': // illustrator
						{
							$or = [];
							foreach($condition as $arg) {
								switch($operator) {
									case ':': $or[] = "(c.illustrator = ?$i)"; break;
									case '!': $or[] = "(c.illustrator != ?$i)"; break;
								}
								$qb->setParameter($i++, $arg);
							}
							$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
							break;
						}
						case 'z': // illustrator
						{
							$or = [];
							foreach($condition as $arg) {
								switch($operator) {
									case ':': $or[] = "(c.slot = ?$i)"; break;
									case '!': $or[] = "(c.slot != ?$i)"; break;
								}
								$qb->setParameter($i++, $arg);
							}
							$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
							break;
						}
						case 'r': // release
						{
							$or = [];
							foreach($condition as $arg) {
								switch($operator) {
									case '<': $or[] = "(p.dateRelease <= ?$i)"; break;
									case '>': $or[] = "(p.dateRelease > ?$i or p.dateRelease IS NULL)"; break;
								}
								if($arg == "now") $qb->setParameter($i++, new \DateTime());
								else $qb->setParameter($i++, new \DateTime($arg));
							}
							$qb->andWhere(implode(" or ", $or));
							break;
						}
					}
					break;
				}
			}
		}

		if(!$i && !$forceempty) {
			return;
		}
		switch($sortorder) {
			case 'set': $qb->orderBy('y.position')->addOrderBy('p.position')->addOrderBy('c.position'); break;
			case 'faction': $qb->orderBy('c.faction')->addOrderBy('c.type'); break;
			case 'type': $qb->orderBy('c.type')->addOrderBy('c.faction'); break;
			case 'cost': $qb->orderBy('c.type')->addOrderBy('c.cost'); break;
		}
		$qb->addOrderBy('c.name');
		$qb->addOrderBy('c.code');
		$query = $qb->getQuery();
		$rows = $query->getResult();

		return $rows;
	}

	/**
	 *
	 * @param \AppBundle\Entity\Card $card
	 * @param string $api
	 * @return multitype:multitype: string number mixed NULL unknown
	 */
	public function getCardInfo($card, $api = false)
	{
    $cardinfo = [];

    $metadata = $this->doctrine->getManager()->getClassMetadata('AppBundle:Card');
    $fieldNames = $metadata->getFieldNames();
    $associationMappings = $metadata->getAssociationMappings();

    foreach($associationMappings as $fieldName => $associationMapping)
    {
    	if($associationMapping['isOwningSide']) {
    		$getter = str_replace(' ', '', ucwords(str_replace('_', ' ', "get_$fieldName")));
    		$associationEntity = $card->$getter();
    		if(!$associationEntity) continue;

  			$cardinfo[$fieldName.'_code'] = $associationEntity->getCode();
  			$cardinfo[$fieldName.'_name'] = $associationEntity->getName();
    	}
    }

    foreach($fieldNames as $fieldName)
    {
    	$getter = str_replace(' ', '', ucwords(str_replace('_', ' ', "get_$fieldName")));
    	$value = $card->$getter();
			switch($metadata->getTypeOfField($fieldName)) {
				case 'datetime':
				case 'date':
					continue 2;
					break;
				case 'boolean':
					$value = (boolean) $value;
					break;
			}
			$fieldName = ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', $fieldName)), '_');
    	$cardinfo[$fieldName] = $value;
    }

		$cardinfo['url'] = $this->router->generate('cards_zoom', array('card_code' => $card->getCode()), UrlGeneratorInterface::ABSOLUTE_URL);
		$imageurl = $this->assets_helper->getUrl('bundles/cards/'.$card->getCode().'.png');
		$imagepath= $this->rootDir . '/../web' . preg_replace('/\?.*/', '', $imageurl);
		if(file_exists($imagepath)) {
			$cardinfo['imagesrc'] = $imageurl;
		} else {
			$imageurl = $this->assets_helper->getUrl('bundles/cards/'.$card->getCode().'.jpg');
	                $imagepath= $this->rootDir . '/../web' . preg_replace('/\?.*/', '', $imageurl);
        	        if(file_exists($imagepath)) {
                	        $cardinfo['imagesrc'] = $imageurl;
	                } else {
        	                $cardinfo['imagesrc'] = null;
                	}
		}
		
		if(isset($cardinfo['double_sided']) && $cardinfo['double_sided']) {
			$imageurl = $this->assets_helper->getUrl('bundles/cards/'.$card->getCode().'_back.png');
			$imagepath= $this->rootDir . '/../web' . preg_replace('/\?.*/', '', $imageurl);
			if ( file_exists($imagepath)){
				$cardinfo['backimagesrc'] = $imageurl;
			}else {
				$imageurl = $this->assets_helper->getUrl('bundles/cards/'.$card->getCode().'_back.jpg');
	                        $imagepath= $this->rootDir . '/../web' . preg_replace('/\?.*/', '', $imageurl);
        	                if ( file_exists($imagepath)){
                	                $cardinfo['backimagesrc'] = $imageurl;
                        	}else {
                                	$cardinfo['backimagesrc'] = null;
	                        }
			}
		}else {
			$cardinfo['double_sided'] = false;
		}

		if($api) {
			unset($cardinfo['id']);
			if (isset($cardinfo['deck_requirements']) && $cardinfo['deck_requirements']){
				$cardinfo['deck_requirements'] = $this->deckValidationHelper->parseReqString($cardinfo['deck_requirements']);
			}
			if (isset($cardinfo['deck_options']) && $cardinfo['deck_options']){
				$cardinfo['deck_options'] = $this->deckValidationHelper->parseReqString($cardinfo['deck_options']);
			}
			if (isset($cardinfo['restrictions']) && $cardinfo['restrictions']){
				$cardinfo['restrictions'] = $this->deckValidationHelper->parseReqString($cardinfo['restrictions']);
			}
			$cardinfo = array_filter($cardinfo, function ($var) { return isset($var); });
		} else {
			$cardinfo['text'] = $this->replaceSymbols($cardinfo['text']);
			$cardinfo['text'] = $this->splitInParagraphs($cardinfo['text']);
			if (isset($cardinfo['back_text'])){
				$cardinfo['back_text'] = $this->replaceSymbols($cardinfo['back_text']);
				$cardinfo['back_text'] = $this->splitInParagraphs($cardinfo['back_text']);
			}
			if (isset($cardinfo['deck_requirements']) && $cardinfo['deck_requirements']){
				$cardinfo['deck_requirements'] = $this->deckValidationHelper->parseReqString($cardinfo['deck_requirements']);
			}
			if (isset($cardinfo['deck_options']) && $cardinfo['deck_options']){
				$cardinfo['deck_options'] = $this->deckValidationHelper->parseReqString($cardinfo['deck_options']);
			}
			if (isset($cardinfo['restrictions']) && $cardinfo['restrictions']){
				$cardinfo['restrictions'] = $this->deckValidationHelper->parseReqString($cardinfo['restrictions']);
			}
			$cardinfo['flavor'] = $this->replaceSymbols($cardinfo['flavor']);
		}

		return $cardinfo;
	}

	public function syntax($query)
	{
		// renvoie une liste de conditions (array)
		// chaque condition est un tableau à n>1 éléments
		// le premier est le type de condition (0 ou 1 caractère)
		// les suivants sont les arguments, en OR

		$query = preg_replace('/\s+/u', ' ', trim($query));
		
		$list = [];
		$cond = null;
		// l'automate a 3 états :
		// 1:recherche de type
		// 2:recherche d'argument principal
		// 3:recherche d'argument supplémentaire
		// 4:erreur de parsing, on recherche la prochaine condition
		// s'il tombe sur un argument alors qu'il est en recherche de type, alors le type est vide
		$etat = 1;
		while($query != "") {
			
			if($etat == 1) {
				if(isset($cond) && $etat != 4 && count($cond)>2) {
					$list[] = $cond;
				}
				// on commence par rechercher un type de condition
				$match = [];
				if(preg_match('/^(\p{L})([:<>!])(.*)/u', $query, $match)) { // jeton "condition:"
					$cond = array(mb_strtolower($match[1]), $match[2]);
					$query = $match[3];
				} else {
					$cond = array("", ":");
				}
				
				$etat=2;
			} else {
				if( preg_match('/^"([^"]*)"(.*)/u', $query, $match) // jeton "texte libre entre guillements"
				 || preg_match('/^([_\p{L}\p{N}\-\&]+)(.*)/u', $query, $match) // jeton "texte autorisé sans guillements"
				) {
					if(($etat == 2 && count($cond)==2) || $etat == 3) {
						$cond[] = $match[1];
						$query = $match[2];
						$etat = 2;
					} else {
						// erreur
						$query = $match[2];
						$etat = 4;
					}
				} else if( preg_match('/^\|(.*)/u', $query, $match) ) { // jeton "|"
					if(($cond[1] == ':' || $cond[1] == '!') && (($etat == 2 && count($cond)>2) || $etat == 3)) {
						$query = $match[1];
						$etat = 3;
					} else {
						// erreur
						$query = $match[1];
						$etat = 4;
					}
				} else if( preg_match('/^ (.*)/u', $query, $match) ) { // jeton " "
					$query = $match[1];
					$etat=1;
				} else {
					// erreur
					$query = substr($query, 1);
					$etat = 4;
				}
			}
		}
		if(isset($cond) && $etat != 4 && count($cond)>2) {
			$list[] = $cond;
		}
		return $list;
	}

	public function validateConditions($conditions)
	{
		// suppression des conditions invalides
		$numeric = array('<', '>');

		foreach($conditions as $i => $l)
		{
			$searchCode = $l[0];
			$searchOp = $l[1];

			if(in_array($searchOp, $numeric) && \AppBundle\Controller\SearchController::$searchTypes[$searchCode] !== 'integer')
			{
				// operator is numeric but searched property is not
				unset($conditions[$i]);
			}
		}
		
		return array_values($conditions);
	}

	public function buildQueryFromConditions($conditions)
	{
		// reconstruction de la bonne chaine de recherche pour affichage
		return implode(" ", array_map(
				function ($l) {
					return ($l[0] ? $l[0].$l[1] : "")
					. implode("|", array_map(
							function ($s) {
								return preg_match("/^[\p{L}\p{N}\-\&]+$/u", $s) ?$s : "\"$s\"";
							},
							array_slice($l, 2)
					));
				},
				$conditions
		));
	}

    public function get_reviews($card)
    {
        $reviews = $this->doctrine->getRepository('AppBundle:Review')->findBy(array('card' => $card, 'faq' => false), array('nbVotes' => 'DESC'));

        $response = $reviews;

        return $response;
    }
    
    public function get_faqs($card)
    {
        $reviews = $this->doctrine->getRepository('AppBundle:Review')->findBy(array('card' => $card, 'faq' => true), array('nbVotes' => 'DESC'));

        $response = $reviews;

        return $response;
    }
    
    public function getDistinctTraits()
    {
    	/**
    	 * @var $em \Doctrine\ORM\EntityManager
    	 */
    	$em = $this->doctrine->getManager();
    	$qb = $em->createQueryBuilder();
    	$qb->from('AppBundle:Card', 'c');
    	$qb->select('c.traits');
    	$qb->distinct();
    	$result = $qb->getQuery()->getResult();
    	
    	$traits = [];
    	foreach($result as $card) {
    		$subs = explode('.', $card["traits"]);
    		foreach($subs as $sub) {
    			$traits[trim($sub)] = 1;
    		}
    	}
    	 
    }
}
