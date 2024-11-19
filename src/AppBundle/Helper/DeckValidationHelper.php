<?php

namespace AppBundle\Helper;
use Doctrine\ORM\EntityManager;

class DeckValidationHelper
{

	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

		/**
	* Parse deck requirements/restrictions and convert to array
	* @param string $text
	* @return Array
	*/
	public function parseReqString($text) {

		$return_requirements = [];
		// seperate based on commas
		$restrictions = explode(",", $text);
		foreach($restrictions as $restriction) {
			// if we have a value then next split on :
			if (trim($restriction)){
				$matches = [];
				$params = explode(":", $restriction);
				//$text .= print_r($matches,1);
				if (isset($params[0])){
					$type = trim($params[0]);
					$param1 = false;
					$param2 = false;
					$param3 = false;
					$param4 = false;

					if (isset($params[1])){
						$param1 = trim($params[1]);
					}
					if (isset($params[2])){
						$param2 = trim($params[2]);
					}
					if (isset($params[3])){
						$param3 = trim($params[3]);
					}
					if (isset($params[4])){
						$param4 = trim($params[4]);
					}

					if (!isset($return_requirements[$type])){
						$return_requirements[$type] = [];
					}
					$parsed = false;
					switch($type){
						case "faction":{
							$return_requirements[$type][$param1] = [
								"min" => $param2,
								"max" => $param3
							];
							break;
						}
						case "cards":{
							if ($param1 == "any"){
								$return_requirements[$type][$param1] = [
									"min" => 0,
									"max" => $param3,
									"limit" => $param2
								];
							}
							break;
						}
						case "random":{
							if ($param1 && $param2){
								$return_requirements[$type][] = [
									"target" => $param1,
									"value" => $param2
								];
							}
							break;
						}
						case "investigator":{
							if ($param2){
								$return_requirements[$type] = [$param1 => $param1, $param2 => $param2];
							}else if ($param1){
								$return_requirements[$type] = [$param1 => $param1];
							}
							break;
						}
						case "card":{
							if ($param3){
								$return_requirements[$type][$param1] = [$param1 => $param1, $param2 => $param2, $param3 => $param3];
							}else if ($param2){
								$return_requirements[$type][$param1] = [$param1 => $param1, $param2 => $param2];
							}else if ($param1){
								$return_requirements[$type][$param1] = [$param1 => $param1];
							}
							break;
						}
						case "size":{
							if ($param1){
								$return_requirements[$type] = intval($param1);
							}
							break;
						}
						default:{
							$return_requirements[$type][] = $param1;
							break;
						}
					}
				}
			}
		}
		return $return_requirements;
	}

	public function getInvalidCards($deck)
	{
		$invalidCards = [];
		$deck_options = json_decode($deck->getCharacter()->getDeckOptions());

		if ($deck->getMeta()) {
			$meta = json_decode($deck->getMeta(), true);
			if (isset($meta['alternate_back'])) {
				$card = $this->entityManager->getRepository('AppBundle:Card')->findOneBy([ 'code' => $meta['alternate_back'] ]);
				if ($card && $card->getDeckOptions()){
					$deck_options = json_decode($card->getDeckOptions());
				}
			}
		}
		foreach ( $deck->getSlots() as $slot ) {
			if(! $this->canIncludeCard($deck, $slot, $deck_options)) {
				$invalidCards[] = $slot->getCard();
			}
		}
		return $invalidCards;
	}

	public function canIncludeCard($deck, $slot, $deck_options = []) {
		$card = $slot->getCard();
		$indeck = $slot->getQuantity();
		// hide investigators
		if ($card->getType()->getCode() === "investigator") {
			return false;
		}

		if ($card->getFaction()->getCode() === "mythos") {
			return false;
		}

		// reject cards restricted
		$investigator = $deck->getCharacter();
		$restrictions = $card->getRestrictions();
		if ($restrictions){
			$parsed = $this->parseReqString($restrictions);
			if ($parsed && $parsed['investigator'] && !isset($parsed['investigator'][$investigator->getCode()]) ){
				return false;
			}
		}

		// always allow the required cards regardless
		$requirements = $investigator->getDeckRequirements();
		if ($requirements) {
			$parsed = $this->parseReqString($requirements);
			if ($parsed && $parsed['card'] && $parsed['card'][$card->getCode()]) {
				return true;
			}
		}

		if ($card->getDeckLimit() > 0 && is_null($card->getXp())) {
			return true;
		}

		// allow any 2 random faction cards for now
		$deck_options[] = json_decode("{faction:['guardian','rogue', 'mystic','survivor','seeker'], limit:2}");
		// validate deck. duplicating code from app.deck.js but in php
		if ($deck_options){
			foreach($deck_options as $option) {
				$valid = false;

				if (isset($option->faction) && $option->faction) {
					$faction_valid = false;
					foreach($option->faction as $faction) {
						if ($card->getFaction()->getCode() == $faction || ($card->getFaction2() && $card->getFaction2()->getCode() == $faction) ) {
							$faction_valid = true;
						}
					}
					if (!$faction_valid) {
						continue;
					}
				}

				if (isset($option->type) && $option->type) {
					// needs to match at least one type
					$type_valid = false;
					foreach($option->type as $type) {
						if ($card->getType()->getCode() == $type){
							$type_valid = true;
						}
					}
					if (!$type_valid){
						continue;
					}
				}

				if (isset($option->trait) && $option->trait) {
					// needs to match at least one type
					$trait_valid = false;
					foreach($option->trait as $trait) {
						if (strpos(strtoupper($card->getRealTraits()), strtoupper($trait)."." ) !== false){
							$trait_valid = true;
						}
					}
					if (!$trait_valid){
						continue;
					}
				}

				if (isset($option->uses) && $option->uses) {
					// needs to match at least one type
					$uses_valid = false;
					foreach($option->uses as $uses) {
						if (strpos(strtoupper($card->getRealText()), strtoupper($uses).")." ) !== false){
							$uses_valid = true;
						}
					}
					if (!$uses_valid){
						continue;
					}
				}

				if (isset($option->tag) && $option->tag) {
					$tag_valid = false;
					foreach($option->tag as $tag) {
						if (strpos(strtoupper($card->getTags()), strtoupper($tag)."." ) !== false){
							$tag_valid = true;
						}
					}
					if (!$tag_valid){
						continue;
					}
				}


				if (isset($option->level) && $option->level) {
					// needs to match at least one type
					$level_valid = false;

					if (!is_null($card->getXp()) && $option->level){
						if ($card->getXp() >= $option->level->min && $card->getXp() <= $option->level->max) {
							$level_valid = true;
						} else {
							continue;
						}
					}
				}

				if(isset($option->permanent) && $card->getPermanent() != $option->permanent) {
					continue;
				}

				if (isset($option->not) && $option->not){
					return false;
				}else {
					if (isset($option->limit) && $option->limit){
						if (!isset($option->limit_count)){
							$option->limit_count = 0;
						}
						$option->limit_count += $indeck;
					}
					if (isset($option->atleast) && $option->atleast){
						if (!isset($option->atleast_count[$card->getFaction()->getCode()])){
							$option->atleast_count[$card->getFaction()->getCode()] = 0;
						}
						$option->atleast_count[$card->getFaction()->getCode()] += $indeck;
					}

					// if we exceed the limit, the deck is invalid
					if (isset($option->limit_count) && $option->limit_count && $option->limit) {
						// for now just complain about horribly wrong decks with double over the limit cards
						if ($option->limit_count > $option->limit * 2) {
							return false;
							//return false;
						}
					}
					return true;
				}

			}
		}
		return false;
	}

	public function findProblem($deck)
	{
		$investigator = $deck->getCharacter();
		if($investigator) {
			$req = $this->parseReqString($investigator->getDeckRequirements());
			if ($req && $req['size']){
				if($deck->getSlots()->getDrawDeck()->countCards() < 20) {
					return 'too_few_cards';
				}
			}
		}

		//foreach($deck->getSlots()->getCopiesAndDeckLimit() as $cardName => $value) {
		//	if($value['copies'] > $value['deck_limit']) return 'too_many_copies';
		//}
		if(!empty($this->getInvalidCards($deck))) {
			return 'invalid_cards';
		}

		return null;
	}

	public function getProblemLabel($problem) {
		if(! $problem) {
			return '';
		}
		$labels = [
				'too_few_cards' => "Contains too few cards",
				'too_many_cards' => "Contains too many cards",
				'too_many_copies' => "Contains too many copies of a card (by title)",
				'invalid_cards' => "Contains forbidden cards (cards not permitted by Faction or Agenda)",
				'deck_options_limit' => "Contains too many limited cards",
				'investigator' => "Doesn't comply with the Investigator requirements"
		];
		if(isset($labels[$problem])) {
			return $labels[$problem];
		}
		return '';
	}


}
