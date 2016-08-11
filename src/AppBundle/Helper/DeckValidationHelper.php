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
	
	public function canIncludeCard($deck, $card) {
		if($card->getFaction()->getCode() === 'neutral') {
			return true;
		}
		if($card->getFaction()->getCode() === $deck->getFaction()->getCode()) {
			return true;
		}
		if($card->getIsLoyal()) {
			return false;
		}
		$agenda = $deck->getSlots()->getAgenda();
		if($agenda && $this->agenda_helper->getMinorFactionCode($agenda) === $card->getFaction()->getCode()) {
			return true;
		}
		return false;
	}
	
	public function findProblem($deck)
	{
		if($deck->getSlots()->getDrawDeck()->countCards() < 30) {
			return 'too_few_cards';
		}
		foreach($deck->getSlots()->getCopiesAndDeckLimit() as $cardName => $value) {
			if($value['copies'] > $value['deck_limit']) return 'too_many_copies';
		}
		if(!empty($this->getInvalidCards($deck))) {
			return 'invalid_cards';
		}
		$investigator = $deck->getInvestigator();
		if($investigator) {

		}
		return null;
	}
	
	public function getProblemLabel($problem) {
		if(! $problem) {
			return '';
		}
		$labels = [
				'too_many_plots' => "Contains too many Plots",
				'too_few_plots' => "Contains too few Plots",
				'too_many_different_plots' => "Contains more than one duplicated Plot",
				'too_many_agendas' => "Contains more than one Agenda",
				'too_few_cards' => "Contains too few cards",
				'too_many_copies' => "Contains too many copies of a card (by title)",
				'invalid_cards' => "Contains forbidden cards (cards no permitted by Faction or Agenda)",
				'agenda' => "Doesn't comply with the Agenda conditions"
		];
		if(isset($labels[$problem])) {
			return $labels[$problem];
		}
		return '';
	}
	
	
}