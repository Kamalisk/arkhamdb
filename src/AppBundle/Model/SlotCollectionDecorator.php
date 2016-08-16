<?php

namespace AppBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
/**
 * Decorator for a collection of SlotInterface 
 */
class SlotCollectionDecorator implements \AppBundle\Model\SlotCollectionInterface
{
	protected $slots;
	
	public function __construct(\Doctrine\Common\Collections\Collection $slots)
	{
		$this->slots = $slots;
	}
	
	public function add($element)
	{
		return $this->slots->add($element);
	}

	public function removeElement($element)
	{
		return $this->slots->removeElement($element);
	}
	
	public function count($mode = null)
	{
		return $this->slots->count($mode);
	}
	
	public function getIterator()
	{
		return $this->slots->getIterator();
	}
	
	public function offsetExists($offset)
	{
		return $this->slots->offsetExists($offset);
	}
	
	public function offsetGet($offset)
	{
		return $this->slots->offsetGet($offset);
	}
	
	public function offsetSet($offset, $value)
	{
		return $this->slots->offsetSet($offset, $value);
	}
	
	public function offsetUnset($offset)
	{
		return $this->slots->offsetUnset($offset);
	}
	
	public function countCards() 
	{
		$count = 0;
		foreach($this->slots as $slot) {
			$count += $slot->getQuantity();
		}
		return $count;
	}
	
	public function getIncludedPacks() {
		$packs = [];
		foreach ( $this->slots as $slot ) {
			$card = $slot->getCard();
			$pack = $card->getPack();
			if(!isset($packs[$pack->getPosition()])) {
				$packs[$pack->getPosition()] = [
					'pack' => $pack,
					'nb' => 0
				];
			}
			
			$nbpacks = ceil($slot->getQuantity() / $card->getQuantity());
			if($packs[$pack->getPosition()]['nb'] < $nbpacks) {
				$packs[$pack->getPosition()]['nb'] = $nbpacks;
			}
		}
		ksort($packs);
		return array_values($packs);
	}
	
	public function getSlotsByType() {
		$slotsByType = [ 'asset' => [], 'event' => [], 'skill' => [], 'treachery' => []];
		foreach($this->slots as $slot) {
			if(array_key_exists($slot->getCard()->getType()->getCode(), $slotsByType)) {
				$slotsByType[$slot->getCard()->getType()->getCode()][] = $slot;
			}
		}
		return $slotsByType;
	}
	
	public function getCountByType() {
		$countByType = [ 'asset' => 0, 'event' => 0, 'skill' => 0, 'treachery' => 0 ];
		foreach($this->slots as $slot) {
			if(array_key_exists($slot->getCard()->getType()->getCode(), $countByType)) {
				$countByType[$slot->getCard()->getType()->getCode()] += $slot->getQuantity();
			}
		}
		return $countByType;
	}

	public function getDrawDeck()
	{
		$drawDeck = [];
		foreach($this->slots as $slot) {
			if($slot->getCard()->getType()->getCode() === 'asset'
			|| $slot->getCard()->getType()->getCode() === 'event'
			|| $slot->getCard()->getType()->getCode() === 'skill'
			|| $slot->getCard()->getType()->getCode() === 'treachery') {
				$drawDeck[] = $slot;
			}
		}
		return new SlotCollectionDecorator(new ArrayCollection($drawDeck));
	}
	
	public function getCopiesAndDeckLimit()
	{
		$copiesAndDeckLimit = [];
		foreach($this->slots as $slot) {
			$cardName = $slot->getCard()->getName();
			if(!key_exists($cardName, $copiesAndDeckLimit)) {
				$copiesAndDeckLimit[$cardName] = [
						'copies' => $slot->getQuantity(),
						'deck_limit' => $slot->getCard()->getDeckLimit(),
				];
			} else {
				$copiesAndDeckLimit[$cardName]['copies'] += $slot->getQuantity();
				$copiesAndDeckLimit[$cardName]['deck_limit'] = min($slot->getCard()->getDeckLimit(), $copiesAndDeckLimit[$cardName]['deck_limit']);
			}
		}
		return $copiesAndDeckLimit;
	}
	
	public function getSlots()
	{
		return $this->slots;
	}

	public function getContent()
	{
		$arr = array ();
		foreach ( $this->slots as $slot ) {
			$arr [$slot->getCard ()->getCode ()] = $slot->getQuantity ();
		}
		ksort ( $arr );
		return $arr;
	}
	
}
