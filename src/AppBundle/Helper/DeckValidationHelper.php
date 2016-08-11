<?php 

namespace AppBundle\Helper;

class DeckValidationHelper
{
	
	public function __construct(AgendaHelper $agenda_helper)
	{
		$this->agenda_helper = $agenda_helper;
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