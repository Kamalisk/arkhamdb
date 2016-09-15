<?php 

namespace AppBundle\Helper;

class DeckValidationHelper
{
	
	public function __construct(AgendaHelper $agenda_helper)
	{
		$this->agenda_helper = $agenda_helper;
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
						case "card":{
							if ($param1){
								$return_requirements[$type][$param1] = $param1;
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
		foreach ( $deck->getSlots() as $slot ) {
			if(! $this->canIncludeCard($deck, $slot->getCard())) {
				$invalidCards[] = $slot->getCard();
			}
		}
		return $invalidCards;
	}
	
	public function canIncludeCard($deck, $card, $req = []) {
		
		// hide investigators
		if ($card->getType()->getCode() === "investigator") {
			return false;
		}
		
		$investigator = $deck->getCharacter();
		$restrictions = $card->getRestrictions();
		if ($restrictions){
			$parsed = $this->parseReqString($restrictions);
			if ($parsed && $parsed['investigator'] && $parsed['investigator'][0] !== $investigator->getCode()){
				return false;
			}
		}
		/*
		var investigator = app.data.cards.findById(investigator_code);
		if (investigator.deck_options) {
			if (investigator.deck_options.faction && investigator.deck_options.faction[card.faction_code]){
				return true;
			}
		}
		*/
		return false;
	}
	
	public function findProblem($deck)
	{
		$investigator = $deck->getCharacter();
		if($investigator) {
			$req = $this->parseReqString($investigator->getDeckRequirements());
			if ($req && $req['size']){
				if($deck->getSlots()->getDrawDeck()->countCards() < $req['size']) {
					return 'too_few_cards';
				}
			}
		}

		//foreach($deck->getSlots()->getCopiesAndDeckLimit() as $cardName => $value) {
		//	if($value['copies'] > $value['deck_limit']) return 'too_many_copies';
		//}
		//if(!empty($this->getInvalidCards($deck))) {
		//	return 'invalid_cards';
		//}
		
		return null;
	}
	
	public function getProblemLabel($problem) {
		if(! $problem) {
			return '';
		}
		$labels = [
				'too_few_cards' => "Contains too few cards",
				'too_many_copies' => "Contains too many copies of a card (by title)",
				'invalid_cards' => "Contains forbidden cards (cards no permitted by Faction or Agenda)",
		];
		if(isset($labels[$problem])) {
			return $labels[$problem];
		}
		return '';
	}
	
	
}