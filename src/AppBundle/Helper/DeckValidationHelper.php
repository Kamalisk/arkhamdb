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
		$plotDeck = $deck->getSlots()->getPlotDeck();
		$plotDeckSize = $plotDeck->countCards();
		if($plotDeckSize > 7) {
			return 'too_many_plots';
		}
		if($plotDeckSize < 7) {
			return 'too_few_plots';
		}
		if(count($plotDeck) < 6) {
			return 'too_many_different_plots';
		}
		if($deck->getSlots()->getAgendas()->countCards() > 1) {
			return 'too_many_agendas';
		}
		if($deck->getSlots()->getDrawDeck()->countCards() < 60) {
			return 'too_few_cards';
		}
		foreach($deck->getSlots()->getCopiesAndDeckLimit() as $cardName => $value) {
			if($value['copies'] > $value['deck_limit']) return 'too_many_copies';
		}
		if(!empty($this->getInvalidCards($deck))) {
			return 'invalid_cards';
		}
		$agenda = $deck->getSlots()->getAgenda();
		if($agenda) {
	
			switch($agenda->getCode()) {
				case '01027': {
					$drawDeck = $deck->getSlots()->getDrawDeck();
					$count = 0;
					foreach($drawDeck as $slot) {
						if($slot->getCard()->getFaction()->getCode() === 'neutral') {
							$count += $slot->getQuantity();
						}
					}
					if($count > 15) {
						return 'agenda';
					}
					break;
				}
			}
	
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