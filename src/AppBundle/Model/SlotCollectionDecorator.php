<?php

namespace AppBundle\Model;

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
		$slotsByType = [ 'plot' => [], 'character' => [], 'location' => [], 'attachment' => [], 'event' => [] ];
		foreach($this->slots as $slot) {
			if(array_key_exists($slot->getCard()->getType()->getCode(), $slotsByType)) {
				$slotsByType[$slot->getCard()->getType()->getCode()][] = $slot;
			}
		}
		return $slotsByType;
	}
	
	public function getCountByType() {
		$countByType = [ 'character' => 0, 'location' => 0, 'attachment' => 0, 'event' => 0 ];
		foreach($this->slots as $slot) {
			if(array_key_exists($slot->getCard()->getType()->getCode(), $countByType)) {
				$countByType[$slot->getCard()->getType()->getCode()] += $slot->getQuantity();
			}
		}
		return $countByType;
	}

	public function getPlotDeck()
	{
		$plotDeck = [];
		foreach($this->slots as $slot) {
			if($slot->getCard()->getType()->getCode() === 'plot') {
				$plotDeck[] = $slot;
			}
		}
		return new SlotCollectionDecorator($plotDeck);
	}

	public function getAgendas()
	{
		$agendas = [];
		foreach($this->slots as $slot) {
			if($slot->getCard()->getType()->getCode() === 'agenda') {
				$agendas[] = $slot;
			}
		}
		return new SlotCollectionDecorator($agendas);
	}

	public function getAgenda()
	{
		foreach ( $this->slots as $slot ) {
			if($slot->getCard()->getType()->getCode() === 'agenda') {
				return $slot->getCard();
			}
		}
	}

	public function getDrawDeck()
	{
		$drawDeck = [];
		foreach($this->slots as $slot) {
			if($slot->getCard()->getType()->getCode() === 'character'
			|| $slot->getCard()->getType()->getCode() === 'location'
			|| $slot->getCard()->getType()->getCode() === 'attachment'
			|| $slot->getCard()->getType()->getCode() === 'event') {
				$drawDeck[] = $slot;
			}
		}
		return new SlotCollectionDecorator($drawDeck);
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
