<?php


namespace AppBundle\Services;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Asset\Packages;
// use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use AppBundle\Helper\DeckValidationHelper;

/*
 *
 */
class CardsData
{
	public function __construct(Registry $doctrine, RequestStack $request_stack, Router $router, Packages $assets_helper, DeckValidationHelper $deckValidationHelper, $rootDir) {
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
			'[per_investigator]' => '<span class="icon-per_investigator" title="Per Investigator"></span>',
			'[bless]' => '<span class="icon-bless" title="Bless"></span>',
			'[curse]' => '<span class="icon-curse" title="Curse"></span>',
			'[frost]' => '<span class="icon-frost" title="frost"></span>',
			'[seal_a]' => '<span class="icon-seal_a" title="Seal A"></span>',
			'[seal_b]' => '<span class="icon-seal_b" title="Seal B"></span>',
			'[seal_c]' => '<span class="icon-seal_c" title="Seal C"></span>',
			'[seal_d]' => '<span class="icon-seal_d" title="Seal D"></span>',
			'[seal_e]' => '<span class="icon-seal_e" title="Seal E"></span>'
		];
		$text = preg_replace("/\[\[([^\]]+)\]\]/", '<b><i>${1}</i></b>', $text);
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
		$list_cycles = $this->doctrine->getRepository('AppBundle:Cycle')->findAll();
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
		$factions = $this->doctrine->getRepository('AppBundle:Faction')->findPrimaries();
		return $factions;
	}

	public function get_search_rows($conditions, $sortorder, $forceempty = false, $encounter = false)
	{
		$i=0;

		// construction de la requete sql
		$repo = $this->doctrine->getRepository('AppBundle:Card');
		$qb = $repo->createQueryBuilder('c');
		$qb->select('c', 'p', 'y', 't', 'b', 'm', 'l', 'f', 'f2', 'f3')
		    ->leftJoin('c.pack', 'p')
			->leftJoin('p.cycle', 'y')
			->leftJoin('c.type', 't')
			->leftJoin('c.subtype', 'b')
			->leftJoin('c.encounter', 'm')
			->leftJoin('c.linked_to', 'l')
			->leftJoin('c.faction', 'f')
			->leftJoin('c.faction2', 'f2')
			->leftJoin('c.faction3', 'f3');
		$qb2 = null;
		$qb3 = null;
		if ($encounter === "encounter"){
			$qb->andWhere("(c.encounter IS NOT NULL)");
		}else if ($encounter === true || $encounter === "1"){
			//$qb->andWhere("(c.encounter IS NULL)");
		}else {
			$qb->andWhere("(c.encounter IS NULL)");
		}
		$qb->andWhere("c.hidden is null or c.hidden = false");

		$cheat = false;
		foreach($conditions as $condition)
		{
			$searchCode = array_shift($condition);
			$searchName = \AppBundle\Controller\SearchController::$searchKeys[$searchCode];
			$searchType = \AppBundle\Controller\SearchController::$searchTypes[$searchCode];
			$operator = array_shift($condition);

			switch($searchType)
			{
				case 'special':{
					// for now this is just parsing the investigator special requirements and then making it a query magically somehow
					$card_code = $condition[0];
					$card = $this->doctrine->getRepository('AppBundle:Card')->findOneBy(array("code" => $card_code));
					if ($card && $card->getDeckOptions()){
						$where = [];
						// parse the json options
						$json_options = json_decode($card->getDeckOptions());
						foreach ($json_options as $key => $option){
							// if they have a choice, assume all possible choices
							if(isset($option->option_select)) {
								foreach ($option->option_select as $choice){
									$json_options[] = $choice;
								}
								unset($json_options[$key]);
							}
						}
						$nots = [];
						foreach ($json_options as $option){
							$sub_where = [];
							if (isset($option->faction)){
								$sub_where[] = " (f.code IN ('".implode("','",$option->faction)."') OR f2.code IN ('".implode("','",$option->faction)."') OR f3.code IN ('".implode("','",$option->faction)."')) ";
							}
							if (isset($option->type)){
								$sub_where[] = " t.code IN ('".implode("','",$option->type)."')";
							}
							if (isset($option->trait)){
								// for traits it is string match
								$trait_or = [];
								foreach($option->trait as $trait){
									$trait_or[] = " c.realTraits LIKE '%".$trait.".%' ";
								}
								$sub_where[] = " ( ".implode(" OR ", $trait_or). " ) ";
							}
							if (isset($option->uses)){
								// basically a string match mother f***
								$uses_or = [];
								foreach($option->uses as $use){
									$uses_or[] = " c.realText LIKE '%".$use.").%' ";
								}
								$sub_where[] = " ( ".implode(" OR ", $uses_or)." ) ";
							}
							if (isset($option->text)){
								// basically a string match mother f***
								$text_or = [];
								foreach($option->text as $text){
									//$text_or[] = " REGEXP(c.realText, '".$text."') = true ";
								}
								//$sub_where[] = " ( ".implode(" OR ", $text_or)." ) ";
								$sub_where[] = " true = false  ";
							}
							if (isset($option->level)){
								if (isset($option->level->min)){
									$sub_where[] = " c.xp >= ".$option->level->min." ";
								}
								if (isset($option->level->max)){
									$sub_where[] = " c.xp <= ".$option->level->max." ";
								}
							}
							if (isset($option->not)){
								$nots[] = " NOT (  ".implode(" AND ", $sub_where). " ) ";
								$sub_where[] = " true = false  ";
								continue;
							}
							$where[] = implode(" AND ", $sub_where);
						}
						$where_string = "(".implode(" OR ", $where).") AND c.encounter IS NULL";
						if (count($nots) > 0){
							$where_string .= " AND ".implode(" AND ", $nots);
						}
						$qb->andWhere($where_string);
						$cheat = true;
					}
					break;
				}
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
									case '<': $or[] = "(y.position < ?$i)"; break;
									case '>': $or[] = "(y.position > ?$i)"; break;
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
						case 'f': // faction
						{
							$or = [];
							foreach($condition as $arg) {
								switch($operator) {
									case ':': $or[] = "(f.code = ?$i)"; $or[] = "(f2.code = ?$i)"; $or[] = "(f3.code = ?$i)";break;
									case '!': $or[] = "(f.code != ?$i)"; $or[] = "(f2.code != ?$i)"; $or[] = "(f3.code != ?$i)"; break;
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
								$code = preg_match('/^\d\d(\d\d\d[abcdefgh]?|_[a-zA-Z0-9]+)$/u', $arg);
								$acronym = false;////preg_match('/^[A-Z]{2,}$/', $arg);
								if($code) {
									$or[] = "(c.code = ?$i)";
									$qb->setParameter($i++, $arg);
								} else if($acronym) {
									$or[] = "(c.name like ?$i or c.backName like ?$i or c.subname like ?$i or l.name like ?$i)";
									$qb->setParameter($i++, "%$arg%");
									$like = implode('% ', str_split($arg));
									$or[] = "(REPLACE(c.name, '-', ' ') like ?$i or REPLACE(c.backName, '-', ' ') like ?$i or REPLACE(c.subname, '-', ' ') like ?$i or REPLACE(l.name, '-', ' ') like ?$i)";
									$qb->setParameter($i++, "$like%");
								} else {
									$or[] = "(c.name like ?$i or c.backName like ?$i or c.subname like ?$i or l.name like ?$i)";
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
									case ':': $or[] = "(c.text like ?$i or c.backText like ?$i or l.text like ?$i)"; break;
									case '!': $or[] = "(c.text not like ?$i and (c.backText is null or c.backText not like ?$i) and (l.text is null or l.text not like ?$i))"; break;
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
									case ':': $or[] = "(c.flavor like ?$i or c.backFlavor like ?$i or l.flavor like ?$i)"; break;
									case '!': $or[] = "(c.flavor not like ?$i and (c.backFlavor is null or c.backFlavor not like ?$i) and (l.flavor is null or l.flavor not like ?$i))"; break;
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
										$or[] = "((c.traits = ?$i) or (c.traits like ?".($i+1).") or (c.traits like ?".($i+2).") or (c.traits like ?".($i+3).")) or ((l.traits = ?$i) or (l.traits like ?".($i+1).") or (l.traits like ?".($i+2).") or (l.traits like ?".($i+3)."))";
										$qb->setParameter($i++, "$arg.");
										$qb->setParameter($i++, "$arg. %");
										$qb->setParameter($i++, "%. $arg.");
										$qb->setParameter($i++, "%. $arg. %");
										break;
									case '!':
										$or[] = "(c.traits is null or ((c.traits != ?$i) and (c.traits not like ?".($i+1).") and (c.traits not like ?".($i+2).") and (c.traits not like ?".($i+3)."))) and (l.traits is null or ((l.traits != ?$i) and (l.traits not like ?".($i+1).") and (l.traits not like ?".($i+2).") and (l.traits not like ?".($i+3).")))";
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
									case ':': $or[] = "(c.illustrator = ?$i or l.illustrator = ?$i)"; break;
									case '!': $or[] = "(c.illustrator != ?$i or l.illustrator != ?$i)"; break;
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

		if(!$i && !$forceempty && !$cheat) {
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
		$rows = $repo->getResult($qb);

		return $rows;
	}

	/**
	 *
	 * @param \AppBundle\Entity\Card $card
	 * @param string $api
	 * @return multitype:multitype: string number mixed NULL unknown
	 */
	public function getCardInfo($card, $api = false, $bonded_cards = [])
	{
    $cardinfo = [];

    $metadata = $this->doctrine->getManager()->getClassMetadata('AppBundle:Card');
    $fieldNames = $metadata->getFieldNames();
    $associationMappings = $metadata->getAssociationMappings();

    foreach($associationMappings as $fieldName => $associationMapping)
    {
			$getter = str_replace(' ', '', ucwords(str_replace('_', ' ', "get_$fieldName")));
			if($associationMapping['isOwningSide']) {
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
				if ($fieldName != 'errataDate' || !$value) {
					continue 2;
				}
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

		if(isset($cardinfo['encounter_code']) && $cardinfo['encounter_code']) {
			$cardinfo['spoiler'] = 1;
		}

		if(isset($cardinfo['double_sided']) && $cardinfo['double_sided']) {
			$imageurl = $this->assets_helper->getUrl('bundles/cards/'.$card->getCode().'b.png');
			$imagepath= $this->rootDir . '/../web' . preg_replace('/\?.*/', '', $imageurl);
			if ( file_exists($imagepath)){
				$cardinfo['backimagesrc'] = $imageurl;
			}else {
				$imageurl = $this->assets_helper->getUrl('bundles/cards/'.$card->getCode().'b.jpg');
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

		if (isset($cardinfo['linked_to_code']) && $cardinfo['linked_to_code']){
			$cardinfo['linked_card'] = $this->getCardInfo($card->getLinkedTo());
			if ($card->getLinkedTo() && $card->getLinkedTo()->getEncounter()) {
				$cardinfo['spoiler'] = 1;
			}
		}

		if($api) {
			unset($cardinfo['id']);
			if (!$cardinfo['hidden']) {
				unset($cardinfo['hidden']);
			}
			if (!$cardinfo['exile']) {
				unset($cardinfo['exile']);
			}
			if (!$cardinfo['clues_fixed']) {
				unset($cardinfo['clues_fixed']);
			}
			if (isset($cardinfo['deck_requirements']) && $cardinfo['deck_requirements']){
				$cardinfo['deck_requirements'] = $this->deckValidationHelper->parseReqString($cardinfo['deck_requirements']);
			}
			if (isset($cardinfo['deck_options']) && $cardinfo['deck_options']){
				$cardinfo['deck_options'] = json_decode($cardinfo['deck_options']);
			}
			if (isset($cardinfo['customization_options']) && $cardinfo['customization_options']){
				$cardinfo['customization_options'] = json_decode($cardinfo['customization_options']);
			}
			if (isset($cardinfo['restrictions']) && $cardinfo['restrictions']){
				$cardinfo['restrictions'] = $this->deckValidationHelper->parseReqString($cardinfo['restrictions']);
			}
			// if we have preloaded the bonded cards, add them here
			if (isset($bonded_cards) && isset($bonded_cards[$card->getCode()]) && count($bonded_cards[$card->getCode()]) > 0) {
				$cardinfo['bonded_cards'] = $bonded_cards[$card->getCode()];
			}
			if ($card->getDuplicates()) {
				$dupes = $card->getDuplicates();
				if (count($dupes) > 0) {
					$cardinfo['duplicated_by'] = [];
					foreach($dupes as $duplicate) {
						$cardinfo['duplicated_by'][] = $duplicate->getCode();
					}
				}
			}
			if ($card->getAlternates()) {
				$dupes = $card->getAlternates();
				if (count($dupes) > 0) {
					$cardinfo['alternated_by'] = [];
					foreach($dupes as $duplicate) {
						$cardinfo['alternated_by'][] = $duplicate->getCode();
					}
				}
			}
			$cardinfo = array_filter($cardinfo, function ($var) { return isset($var); });
		} else {
			$cardinfo['text'] = $this->replaceSymbols($cardinfo['text']);
			$cardinfo['text'] = $this->splitInParagraphs($cardinfo['text']);
			if (isset($cardinfo['back_text'])){
				$cardinfo['back_text'] = $this->replaceSymbols($cardinfo['back_text']);
				$cardinfo['back_text'] = $this->splitInParagraphs($cardinfo['back_text']);
			}
			if (isset($cardinfo['customization_text'])){
				$cardinfo['customization_text'] = $this->replaceSymbols($cardinfo['customization_text']);
				$cardinfo['customization_text'] = $this->splitInParagraphs($cardinfo['customization_text']);
			}
			if (isset($cardinfo['deck_requirements']) && $cardinfo['deck_requirements']){
				$cardinfo['deck_requirements'] = $this->deckValidationHelper->parseReqString($cardinfo['deck_requirements']);
			}
			if (isset($cardinfo['deck_options']) && $cardinfo['deck_options']){
				$cardinfo['deck_options'] = $this->deckValidationHelper->parseReqString($cardinfo['deck_options']);
			}
			if (isset($cardinfo['customization_options']) && $cardinfo['customization_options']){
				$cardinfo['customization_options'] = $this->deckValidationHelper->parseReqString($cardinfo['customization_options']);
			}
			if (isset($cardinfo['restrictions']) && $cardinfo['restrictions']){
				$cardinfo['restrictions'] = $this->deckValidationHelper->parseReqString($cardinfo['restrictions']);
			}
			$cardinfo['flavor'] = $this->replaceSymbols($cardinfo['flavor']);
			if(isset($cardinfo['bonded_to']) && $cardinfo['bonded_count']) {
				// fetch the bonded cards
				$bonded_cards = $this->doctrine->getRepository('AppBundle:Card')->findBy(['realName' => $cardinfo['bonded_to']]);
				if ($bonded_cards && count($bonded_cards) > 0) {
					$cardinfo['bonded_cards'] = $bonded_cards;
				}
			} else {
				$bonded_cards = $this->doctrine->getRepository('AppBundle:Card')->findBy(['bondedTo' => $cardinfo['real_name']]);
				if ($bonded_cards && count($bonded_cards) > 0) {
					$cardinfo['bonded_cards'] = $bonded_cards;
				}
			}
			if ($card->getDuplicates()) {
				$dupes = $card->getDuplicates();
				if (count($dupes) > 0) {
					$cardinfo['duplicated_by'] = [];
					foreach($dupes as $duplicate) {
						$cardinfo['duplicated_by'][] = $duplicate;
					}
				}
			}
			if ($card->getAlternates()) {
				$dupes = $card->getAlternates();
				if (count($dupes) > 0) {
					$cardinfo['alternated_by'] = [];
					foreach($dupes as $duplicate) {
						$cardinfo['alternated_by'][] = $duplicate;
					}
				}
			}
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
				if(preg_match('/^(\p{L}\p{L}?)([:<>!])(.*)/u', $query, $match)) { // jeton "condition:"
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
        $reviews = $this->doctrine->getRepository('AppBundle:Review')->findBy(array('card' => $card, 'faq' => false, 'question' => false), array('nbVotes' => 'DESC'));

        $response = $reviews;

        return $response;
    }

    public function get_faqs($card)
    {
        $reviews = $this->doctrine->getRepository('AppBundle:Review')->findBy(array('card' => $card, 'faq' => true), array('nbVotes' => 'DESC'));

        $response = $reviews;

        return $response;
    }

    public function get_questions($card)
    {
        $reviews = $this->doctrine->getRepository('AppBundle:Review')->findBy(array('card' => $card, 'question' => true), array('nbVotes' => 'DESC'));

        $response = $reviews;

        return $response;
    }

    public function get_related($card)
    {
        $cards = $this->doctrine->getRepository('AppBundle:Card')->findBy(array('realName' => $card->getRealName(), 'hidden' => false), array('position' => 'ASC'));

        $response = $cards;

        return $response;
		}

		public function get_investigator_cards($card)
    {
			$cardsToFind = [];
			$deck_requirements = $this->deckValidationHelper->parseReqString($card->getDeckRequirements());
			if ($deck_requirements){
				foreach($deck_requirements['card'] as $card_code){
					if ($card_code){
						$cardsToFind = array_merge($cardsToFind, $card_code);
					}
				}
			}
			$cards = $this->doctrine->getRepository('AppBundle:Card')->findBy(array('code' => $cardsToFind, 'hidden' => false), array('pack' => 'ASC', 'position' => 'ASC', 'encounter' => 'ASC',));

			return $cards;
		}



		public function get_bonded($card)
    {
        $cards = $card->getBondedFrom();

        $response = $cards;

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
