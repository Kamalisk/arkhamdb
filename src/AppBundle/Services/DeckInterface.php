<?php

namespace AppBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;

/*
 * Functions that can be used on a Deck or on a decklist
 * uses getSlots, getFaction
 */
class DeckInterface
{

        public function __construct(EntityManager $doctrine)
        {
            $this->doctrine = $doctrine;
        }

        public function countCards($slots) {
            $count = 0;
            foreach($slots as $slot) {
                $count += $slot->getQuantity();
            }
            return $count;
        }

        public function getCountByType($deck) {
            $countByType = [ 'character' => 0, 'location' => 0, 'attachment' => 0, 'event' => 0 ];
            foreach($deck->getSlots () as $slot) {
                if(array_key_exists($slot->getCard()->getType()->getCode(), $countByType)) {
                    $countByType[$slot->getCard()->getType()->getCode()] += $slot->getQuantity();
                }
            }
            return $countByType;
        }

        public function getPlotDeck($deck)
        {
            $plotDeck = [];
            foreach($deck->getSlots () as $slot) {
                if($slot->getCard()->getType()->getCode() === 'plot') {
                    $plotDeck[] = $slot;
                }
            }
            return $plotDeck;
        }

        public function getAgendas($deck)
        {
            $agendas = [];
            foreach($deck->getSlots () as $slot) {
                if($slot->getCard()->getType()->getCode() === 'agenda') {
                    $agendas[] = $slot;
                }
            }
            return $agendas;
        }

    	public function getAgenda($deck)
    	{
    		foreach ( $deck->getSlots () as $slot ) {
    			if($slot->getCard()->getType()->getCode() === 'agenda') {
    				return $slot->getCard();
    			}
    		}
    	}

        public function getMinorFactionCode($agenda) {
            if(empty($agenda)) {
                return null;
            }

        	// special case for the Core Set Banners
        	$banners_core_set = [
        		'01198' => 'baratheon',
        		'01199' => 'greyjoy',
        		'01200' => 'lannister',
        		'01201' => 'martell',
        		'01202' => 'nightswatch',
        		'01203' => 'stark',
        		'01204' => 'targaryen',
        		'01205' => 'tyrell'
        	];
            if(isset($banners_core_set[$agenda->getCode()])) {
                return $banners_core_set[$agenda->getCode()];
            }
            return null;
        }

        public function getMinorFaction($agenda) {
            $code = $this->getMinorFactionCode($agenda);
            if($code) {
                return $this->doctrine->getRepository('AppBundle:Faction')->findOneBy([ 'code' => $code ]);
            }
            return null;
        }

        public function getDrawDeck($deck)
        {
            $drawDeck = [];
            foreach($deck->getSlots () as $slot) {
                if($slot->getCard()->getType()->getCode() === 'character'
                || $slot->getCard()->getType()->getCode() === 'location'
                || $slot->getCard()->getType()->getCode() === 'attachment'
                || $slot->getCard()->getType()->getCode() === 'event') {
                    $drawDeck[] = $slot;
                }
            }
            return $drawDeck;
        }

    	public function getContent($deck)
    	{
    		$arr = array ();
    		foreach ( $deck->getSlots () as $slot ) {
    			$arr [$slot->getCard ()->getCode ()] = $slot->getQuantity ();
    		}
    		ksort ( $arr );
    		return $arr;
    	}

        public function getInvalidCards($deck)
        {
            $invalidCards = [];
            foreach ( $deck->getSlots () as $slot ) {
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
            $minorFactionCode = $this->getMinorFactionCode($this->getAgenda($deck));
            if($minorFactionCode && $minorFactionCode === $card->getFaction()->getCode()) {
                return true;
            }
            return false;
        }

        public function getProblem($deck)
        {
            $plotDeck = $this->getPlotDeck($deck);
            $plotDeckSize = $this->countCards($plotDeck);
            if($plotDeckSize > 7) {
                return 'too_many_plots';
            }
            if($plotDeckSize < 7) {
                return 'too_few_plots';
            }
            if(count($plotDeck) < 6) {
                return 'too_many_different_plots';
            }
            if($this->countCards($this->getAgendas($deck)) > 1) {
                return 'too_many_agendas';
            }
            if($this->countCards($this->getDrawDeck($deck)) < 60) {
                return 'too_few_cards';
            }
            if(count($this->getInvalidCards($deck))) {
                return 'invalid_cards';
            }
            $agenda = $this->getAgenda($deck);
            if($agenda) {

                switch($agenda->getCode()) {
                    case '01027': {
                        $drawDeck = $this->getDrawDeck($deck);
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
        		'invalid_cards' => "Contains forbidden cards (cards no permitted by Faction or Agenda)",
        		'agenda' => "Doesn't comply with the Agenda conditions"
        	];
            if(isset($labels[$problem])) {
                return $labels[$problem];
            }
            return '';
        }

}
