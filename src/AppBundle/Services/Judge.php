<?php

namespace AppBundle\Services;

/*
 *
 */
class Judge
{
	public function __construct($doctrine) {
		$this->doctrine = $doctrine;
	}
	
	/**
	 * Decoupe un deckcontent pour son affichage par type
	 *
	 * @param \AppBundle\Entity\Card $identity
	 */
	public function classe($cards, $identity)
	{
		$analyse = $this->analyse($cards);
		
		$classeur = [];
		/* @var $slot \AppBundle\Entity\Deckslot */
		foreach($cards as $elt) {
			/* @var $card \AppBundle\Entity\Card */
			$card = $elt['card'];
			$qty = $elt['qty'];
			$type = $card->getType()->getName();
			if($type == "Identity") continue;
			if($type == "ICE") {
				$traits = explode(" - ", $card->getTraits());
				if(in_array("Barrier", $traits)) $type = "Barrier";
				if(in_array("Code Gate", $traits)) $type = "Code Gate";
				if(in_array("Sentry", $traits)) $type = "Sentry";
			}
			if($type == "Program") {
				$traits = explode(" - ", $card->getTraits());
				if(in_array("Icebreaker", $traits)) $type = "Icebreaker";
			}
			$influence = 0;
			$qty_influence = $qty;
			if($identity->getCode() == "03029" && $card->getType()->getName() == "Program") $qty_influence--;
			if($card->getFaction()->getId() != $identity->getFaction()->getId()) $influence = $card->getFactionCost() * $qty_influence;
			$elt['influence'] = $influence;
			$elt['faction'] = str_replace(' ', '-', mb_strtolower($card->getFaction()->getName()));
			
			if(!isset($classeur[$type])) $classeur[$type] = array("qty" => 0, "slots" => []);
			$classeur[$type]["slots"][] = $elt;
			$classeur[$type]["qty"] += $qty;
		}
		if(is_string($analyse)) {
			$classeur['problem'] = $this->problem($analyse);
		} else {
			$classeur = array_merge($classeur, $analyse);
		}
		return $classeur;
	}
	
    /**
     * Analyse un deckcontent et renvoie un code indiquant le pbl du deck
     *
     * @param array $content
     * @return array
     */
	public function analyse($cards)
	{
		$identity = null;
		$deck = [];
		$deckSize = 0;
		$influenceSpent = 0;
		$agendaPoints = 0;
		
		foreach($cards as $elt) {
			$card = $elt['card'];
			$qty = $elt['qty'];
			if($card->getType()->getName() == "Identity") {
			    if(isset($identity)) return 'identities';
				$identity = $card;
			} else {
				$deck[] = $card;
				$deckSize += $qty;
			}
		}
		
		if(!isset($identity)) {
			return 'identity';
		}
		
		if($deckSize < $identity->getMinimumDeckSize()) {
			return 'deckSize';
		}
		
		foreach($deck as $card) {
			$qty = $cards[$card->getCode()]['qty'];
			
			if($qty > 3 && $identity->getFaction()->getCode() != "neutral") {
			    return 'copies';
			}
			if($qty > 1 && $card->getLimited() && $identity->getFaction()->getCode() != "neutral") {
			    return 'limited';
			}
			if($card->getSide() != $identity->getSide()) {
				return 'side';
			}
			if($identity->getCode() == "03002" && $card->getFaction()->getName() == "Jinteki") {
				return 'forbidden';
			}
			if($card->getType()->getName() == "Agenda") {
				if($card->getFaction()->getName() != "Neutral" && $card->getFaction() != $identity->getFaction() && $identity->getFaction()->getName() != "Neutral") {
					return 'agendas';
				}
				$agendaPoints += $card->getAgendaPoints() * $qty;
			}
			if($card->getFaction() != $identity->getFaction()) {
				if($identity->getCode() == "03029" && $card->getType()->getName() == "Program") {
					$influenceSpent += $card->getFactionCost() * ($qty - 1);
				} else {
					$influenceSpent += $card->getFactionCost() * $qty;
				}
			}
		}
		
		if($identity->getInfluenceLimit() !== null && $influenceSpent > $identity->getInfluenceLimit()) return 'influence';
		if($identity->getSide()->getName() == "Corp" && $identity->getFaction()->getName() != "Neutral") {
			$minAgendaPoints = floor($deckSize / 5) * 2 + 2;
			if($agendaPoints < $minAgendaPoints || $agendaPoints > $minAgendaPoints + 1) return 'agendapoints';
		}
		
		return array(
			'deckSize' => $deckSize,
			'influenceSpent' => $influenceSpent,
			'agendaPoints' => $agendaPoints
		);
	}
	
	public function problem($problem)
	{
		switch($problem) {
			case 'identity': return "The deck lacks an Identity card."; break;
			case 'identities': return "The deck has more than 1 Identity card;"; break;
			case 'deckSize': return "The deck has less cards than the minimum required by the Identity."; break;
			case 'side': return "The deck mixes Corp and Runner cards."; break;
			case 'forbidden': return "The deck includes forbidden cards."; break;
			case 'agendas': return "The deck uses Agendas from a different faction."; break;
			case 'influence': return "The deck spends more influence than available on the Identity."; break;
			case 'agendapoints': return "The deck has a wrong number of Agenda Points."; break;
			case 'copies' : return "The deck has more than 3 copies of a card."; break;
			case 'limited': return "The deck has more than 1 copy of a limited card."; break;
		}
	}
	
}