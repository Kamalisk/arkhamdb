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

    	public function getContent($deckLikeObject)
    	{
    		$arr = array ();
    		foreach ( $deckLikeObject->getSlots () as $slot ) {
    			$arr [$slot->getCard ()->getCode ()] = $slot->getQuantity ();
    		}
    		ksort ( $arr );
    		return $arr;
    	}

    	public function getAgenda($deckLikeObject)
    	{
    		foreach ( $deckLikeObject->getSlots () as $slot ) {
    			if($slot->getCard()->getType()->getCode() === 'agenda') {
    				return $slot->getCard();
    			}
    		}
    	}

        /**
         * outputs an array with the deck info to give to app.deck.js
         * @param integer $deck_id
         * @param boolean $decode_variation
         * @return array
         */
        public function getArray($deckLikeObject)
        {
            $array = [
                'id' => $deckLikeObject->getId(),
                'name' => $deckLikeObject->getName(),
                'date_creation' => $deckLikeObject->getDateCreation()->format('r'),
                'date_update' => $deckLikeObject->getDateUpdate()->format('r'),
                'description_md' => $deckLikeObject->getDescriptionMd(),
                'user_id' => $deckLikeObject->getUser()->getId(),
                'faction_code' => $deckLikeObject->getFaction()->getCode(),
                'faction_name' => $deckLikeObject->getFaction()->getName(),
                'slots' => []
            ];

            foreach ( $deckLikeObject->getSlots () as $slot ) {
                $array['slots'][$slot->getCard()->getCode()] = $slot->getQuantity();
                if($slot->getCard()->getType()->getCode() === 'agenda') {
                    $array['agenda_code'] = $slot->getCard()->getCode();
                }
            }

            $array['problem'] = "";

            return $array;
        }


}
