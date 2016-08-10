<?php

namespace AppBundle\Model;

class ExportableDeck
{
	public function getArrayExport($withUnsavedChanges = false)
	{
		$slots = $this->getSlots();
		$array = [
				'id' => $this->getId(),
				'name' => $this->getName(),
				'date_creation' => $this->getDateCreation()->format('c'),
				'date_update' => $this->getDateUpdate()->format('c'),
				'description_md' => $this->getDescriptionMd(),
				'user_id' => $this->getUser()->getId(),
				'investigator_code' => $this->getCharacter()->getCode(),
				'investigator_name' => $this->getCharacter()->getName(),
				'slots' => $slots->getContent(),
				'agenda_code' => $slots->getAgenda() ? $slots->getAgenda()->getCode() : null,
				'version' => $this->getVersion(),
		];
	
		return $array;
	}
	
	public function getTextExport() 
	{
		$slots = $this->getSlots();
		return [
				'name' => $this->getName(),
				'faction' => $this->getFaction(),
				'agenda' => $slots->getAgenda(),
				'draw_deck_size' => $slots->getDrawDeck()->countCards(),
				'plot_deck_size' => $slots->getPlotDeck()->countCards(),
				'included_packs' => $slots->getIncludedPacks(),
				'slots_by_type' => $slots->getSlotsByType()
		];
	}
}