<?php

namespace AppBundle\Model;

class ExportableDeck
{
	public function getArrayExport($withUnsavedChanges = false)
	{
		$slots = $this->getSlots();
		$previousDeck = $this->getPreviousDeck();
		$nextDeck = $this->getNextDeck();
		if ($previousDeck){
			$previousDeck = $previousDeck->getId();
		}else {
			$previousDeck = null;
		}
		if ($nextDeck){
			$nextDeck = $nextDeck->getId();
		}else {
			$nextDeck = null;
		}
		
		if (method_exists($this, "getXp")){
			$xp = $this->getXp();
		} else {
			$xp = null;
		}
		if (method_exists($this, "getXpAdjustment")){
			$xp_adjustment = $this->getXpAdjustment();
		} else {
			$xp_adjustment = null;
		}
		if (method_exists($this, "getPrecedent") && method_exists($this, "getTags")){
			$tags = $this->getTags();
			$tags = str_replace(",",", ", $tags);
		} else {
			$tags = null;
		}
		
		$array = [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'date_creation' => $this->getDateCreation()->format('c'),
			'date_update' => $this->getDateUpdate()->format('c'),
			'description_md' => $this->getDescriptionMd(),
			'user_id' => $this->getUser() ? $this->getUser()->getId() : null,
			'investigator_code' => $this->getCharacter()->getCode(),
			'investigator_name' => $this->getCharacter()->getName(),
			'slots' => $slots->getContent(),
			'ignoreDeckLimitSlots' => $slots->getIgnoreDeckLimitContent(),
			'version' => $this->getVersion(),
			'xp' => $xp,
			'xp_adjustment' => $xp_adjustment,
			'exile_string' => $this->getExiles(),
			'tags' => $tags,
			'previous_deck' => $previousDeck,
			'next_deck' => $nextDeck
		];
	
		return $array;
	}
	
	public function getTextExport($exclude=0) 
	{
		$slots = $this->getSlots();
		return [
				'name' => $this->getName(),
				'investigator' => $this->getCharacter(),
				'draw_deck_size' => $slots->getDrawDeck()->countCards(),
				'included_packs' => $slots->getIncludedPacks(),
				'slots_by_type' => $slots->getSlotsByType()
		];
	}
	
	public function getOctgnExport() 
	{
		$slots = $this->getSlots();
		return [
				'name' => $this->getName(),
				'investigator' => $this->getCharacter(),
				'draw_deck_size' => $slots->getDrawDeck()->countCards(),
				'included_packs' => $slots->getIncludedPacks(),
				'slots_by_type' => $slots->getSlotsByType(1),
				'basicweakness' => $slots->getSlotsBySubType("basicweakness"),
				'weakness' => $slots->getSlotsBySubType("weakness")
		];
	}
}