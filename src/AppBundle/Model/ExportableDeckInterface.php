<?php

namespace AppBundle\Model;

interface ExportableDeckInterface
{
	/**
	 * outputs an array with the deck info to give to app.deck.js
	 * @return array
	 */
	public function getArrayExport($withUnsavedChanges = false);
	
	/**
	 * outputs an array with data for AppBundle:Export:*
	 * @return array
	 */
	public function getTextExport();
}