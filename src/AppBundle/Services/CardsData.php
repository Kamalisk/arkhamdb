<?php


namespace AppBundle\Services;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;

/*
 *
 */
class CardsData
{
	public function __construct(Registry $doctrine, RequestStack $request_stack, Router $router, AssetsHelper $assets_helper) {
		$this->doctrine = $doctrine;
        $this->request_stack = $request_stack;
        $this->router = $router;
        $this->assets_helper = $assets_helper;
	}

	/**
	 * Searches for and replaces symbol tokens with markup in a given text.
	 * @param string $text
	 * @return string
	 */
	public function replaceSymbols($text)
	{
		$map = array(
		);

		return str_replace(array_keys($map), array_values($map), $text);
	}
	
	public function allsetsdata()
	{
		$list_cycles = $this->doctrine->getRepository('AppBundle:Cycle')->findBy(array(), array("position" => "ASC"));
		$lines = array();
		/* @var $cycle \AppBundle\Entity\Cycle */
		foreach($list_cycles as $cycle) {
			if(!$cycle->getIsBox()) {
				$lines[] = array(
						"label" => $cycle->getName(),
						"available" => true,
						"url" => $this->router->generate('cards_cycle', array('cycle_code' => $cycle->getCode()), true),
				);
			}
			$packs = $cycle->getPacks();
			/* @var $pack \AppBundle\Entity\Pack */
			foreach($packs as $pack) {
				$known = count($pack->getCards());
				$max = $pack->getSize();
			
				if($cycle->getIsBox()) {
					$label = $pack->getName();
				} else {
					$label = $pack->getPosition() . '. ' . $pack->getName();
				}
				if($known < $max) {
					$label = sprintf("%s (%d/%d)", $label,$known, $max);
				}
			
				$lines[] = array(
						"label" => $label,
						"available" => $pack->getDateRelease() ? true : false,
						"url" => $this->router->generate('cards_list', array('pack_code' => $pack->getCode()), true),
				);
			}
		}
		return $lines;
	}
	
	public function allfactionsdata()
	{
		$factions = $this->doctrine->getRepository('AppBundle:Faction')->findBy(array(), array("name" => "ASC"));
		return $factions;
	}
	
	public function get_search_rows($conditions, $sortorder, $forceempty = false)
	{
		$i=0;
		
		// construction de la requete sql
		$qb = $this->doctrine->getRepository('AppBundle:Card')->createQueryBuilder('c');
		$qb->leftJoin('c.pack', 'p')
			->leftJoin('p.cycle', 'y')
			->leftJoin('c.type', 't')
			->leftJoin('c.faction', 'f');
		$qb2 = null;
		$qb3 = null;
		
		foreach($conditions as $condition)
		{
			$type = array_shift($condition);
			$operator = array_shift($condition);
			switch($type)
			{
				case '': // name or index
					$or = array();
					foreach($condition as $arg) {
						$code = preg_match('/^\d\d\d\d\d$/u', $arg);
						$acronym = preg_match('/^[A-Z]{2,}$/', $arg);
						if($code) {
							$or[] = "(c.code = ?$i)";
							$qb->setParameter($i++, $arg);
						} else if($acronym) {
							$or[] = "(BINARY(c.name) like ?$i)";
							$qb->setParameter($i++, "%$arg%");
							$like = implode('% ', str_split($arg));
							$or[] = "(REPLACE(c.name, '-', ' ') like ?$i)";
							$qb->setParameter($i++, "$like%");
						} else {
							$or[] = "(c.name like ?$i)";
							$qb->setParameter($i++, "%$arg%");
						}
					}
					$qb->andWhere(implode(" or ", $or));
					break;
				case 'x': // text
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(c.text like ?$i)"; break;
							case '!': $or[] = "(c.text not like ?$i)"; break;
						}
						$qb->setParameter($i++, "%$arg%");
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'a': // flavor
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(c.flavor like ?$i)"; break;
							case '!': $or[] = "(c.flavor not like ?$i)"; break;
						}
						$qb->setParameter($i++, "%$arg%");
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'e': // extension (pack)
					$or = array();
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
				case 'c': // cycle (cycle)
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(y.position = ?$i)"; break;
							case '!': $or[] = "(y.position != ?$i)"; break;
						}
						$qb->setParameter($i++, $arg);
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 't': // type
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(t.name = ?$i)"; break;
							case '!': $or[] = "(t.name != ?$i)"; break;
						}
						$qb->setParameter($i++, $arg);
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'f': // faction
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(f.name = ?$i)"; break;
							case '!': $or[] = "(f.name != ?$i)"; break;
						}
						$qb->setParameter($i++, $arg);
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 's': // subtype (traits)
					$or = array();
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
				case 'i': // illustrator
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(c.illustrator = ?$i)"; break;
							case '!': $or[] = "(c.illustrator != ?$i)"; break;
						}
						$qb->setParameter($i++, $arg);
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'o': // cost
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(c.cost = ?$i)"; break;
							case '!': $or[] = "(c.cost != ?$i)"; break;
							case '<': $or[] = "(c.cost < ?$i)"; break;
							case '>': $or[] = "(c.cost > ?$i)"; break;
						}
						$qb->setParameter($i++, $arg);
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'p': // strength
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(c.strength = ?$i)"; break;
							case '!': $or[] = "(c.strength != ?$i)"; break;
							case '<': $or[] = "(c.strength < ?$i)"; break;
							case '>': $or[] = "(c.strength > ?$i)"; break;
						}
						$qb->setParameter($i++, $arg);
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'y': // quantity
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(c.quantity = ?$i)"; break;
							case '!': $or[] = "(c.quantity != ?$i)"; break;
							case '<': $or[] = "(c.quantity < ?$i)"; break;
							case '>': $or[] = "(c.quantity > ?$i)"; break;
						}
						$qb->setParameter($i++, $arg);
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'r': // release
					$or = array();
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
				case 'u': // unique
					if(($operator == ':' && $condition[0]) || ($operator == '!' && !$condition[0])) {
						$qb->andWhere("(c.is_unique = 1)");
					} else {
						$qb->andWhere("(c.is_unique = 0)");
					}
					$i++;
					break;
			}
		}
		
		if(!$i && !$forceempty) {
			return;
		}
		switch($sortorder) {
			case 'set': $qb->orderBy('c.code'); break;
			case 'faction': $qb->orderBy('c.faction')->addOrderBy('c.type'); break;
			case 'type': $qb->orderBy('c.type')->addOrderBy('c.faction'); break;
			case 'cost': $qb->orderBy('c.type')->addOrderBy('c.cost'); break;
			case 'strength': $qb->orderBy('c.type')->addOrderBy('c.strength'); break;
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
					$value = $value->format('r');
					break;
				case 'boolean':
					$value = (boolean) $value;
					break;
			}
	    	$cardinfo[$fieldName] = $value;
	    }
	    
		$cardinfo['url'] = $this->router->generate('cards_zoom', array('card_code' => $card->getCode()), true);
		$cardinfo['imagesrc'] = $this->assets_helper->getUrl('bundles/app/images/cards/'.$card->getCode().'.png');
		
		if($api) {
			unset($cardinfo['id']);
			$cardinfo = array_filter($cardinfo, function ($var) { return isset($var); });
		} else {
			$cardinfo['text'] = $this->replaceSymbols($cardinfo['text']);
			$cardinfo['text'] = implode(array_map(function ($l) { return "<p>$l</p>"; }, preg_split('/[\r\n]+/', $cardinfo['text'])));
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

		$list = array();
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
				$match = array();
				if(preg_match('/^(\p{L})([:<>!])(.*)/u', $query, $match)) { // jeton "condition:"
					$cond = array(mb_strtolower($match[1]), $match[2]);
					$query = $match[3];
				} else {
					$cond = array("", ":");
				}
				$etat=2;
			} else {
				if( preg_match('/^"([^"]*)"(.*)/u', $query, $match) // jeton "texte libre entre guillements"
				 || preg_match('/^([\p{L}\p{N}\-\&]+)(.*)/u', $query, $match) // jeton "texte autorisé sans guillements"
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
	
	public function validateConditions(&$conditions)
	{
		// suppression des conditions invalides
		$canDoNumeric = array('o', 'n', 'p', 'r', 'y', 'e');
		$numeric = array('<', '>');
		$factions = array('h','w','a','s','c','j','n','-');
		foreach($conditions as $i => $l)
		{
			if(in_array($l[1], $numeric) && !in_array($l[0], $canDoNumeric)) unset($conditions[$i]);
			if($l[0] == 'f')
			{
				$conditions[$i][2] = substr($l[2],0,1);
				if(!in_array($conditions[$i][2], $factions)) unset($conditions[$i]);
			}
		}
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
        $reviews = $this->doctrine->getRepository('AppBundle:Review')->findBy(array('card' => $card), array('nbVotes' => 'DESC'));
        
        $response = array();
        foreach($reviews as $review) {
            /* @var $review \AppBundle\Entity\Review */
            $user = $review->getUser();
            $datecreation = $review->getDatecreation();
            $response[] = array(
                    'id' => $review->getId(),
                    'text' => $review->getText(),
                    'author_id' => $user->getId(),
                    'author_name' => $user->getUsername(),
                    'author_reputation' => $user->getReputation(),
                    'author_donation' => $user->getDonation(),
                    'author_color' => $user->getFaction(),
                    'datecreation' => $datecreation,
                    'nbVotes' => $review->getnbVotes(),
                    'comments' => $review->getComments(),
            );
        }
        
        return $response;
    }
}
