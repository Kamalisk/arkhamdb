<?php

namespace AppBundle\Services;

use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Deck;
use AppBundle\Entity\Deckslot;
use AppBundle\Entity\SideDeckslot;
use Symfony\Bridge\Monolog\Logger;
use AppBundle\Entity\Deckchange;
use AppBundle\Helper\DeckValidationHelper;
use AppBundle\Helper\AgendaHelper;

class Decks
{
	public function __construct(EntityManager $doctrine, DeckValidationHelper $deck_validation_helper, AgendaHelper $agenda_helper, Diff $diff, Logger $logger)
	{
		$this->doctrine = $doctrine;
		$this->deck_validation_helper = $deck_validation_helper;
		$this->agenda_helper = $agenda_helper;
		$this->diff = $diff;
		$this->logger = $logger;
	}

	public function getByUser($user, $decode_variation = FALSE)
	{
		$decks = $user->getDecks();
		$list = [];
		foreach($decks as $deck) {
			$list[] = $deck->jsonSerialize(false);
		}

		return $list;
	}

	/**
	 *
	 * @param unknown $user
	 * @param Deck $deck
	 * @param unknown $decklist_id
	 * @param unknown $name
	 * @param unknown $faction
	 * @param unknown $description
	 * @param unknown $tags
	 * @param unknown $content
	 * @param unknown $source_deck
	 */
	public function saveDeck($user, $deck, $decklist_id, $name, $faction, $description, $meta, $tags, $content, $source_deck, $problem="", $ignored=false, $side=false)
	{
		$deck_content = [ ];

		if ($decklist_id) {
			$decklist = $this->doctrine->getRepository ( 'AppBundle:Decklist' )->find ( $decklist_id );
			if ($decklist)
				$deck->setParent ( $decklist );
		}

		$deck->setName ( $name );
		$deck->setCharacter ( $faction );
		$deck->setDescriptionMd ( $description );
		$deck->setUser ( $user );
		$deck->setMinorVersion( $deck->getMinorVersion() + 1 );
		$cards = [ ];
		/* @var $latestPack \AppBundle\Entity\Pack */
		$latestPack = null;
		foreach ( $content as $card_code => $qty ) {
			$card = $this->doctrine->getRepository ( 'AppBundle:Card' )->findOneBy ( array (
					"code" => $card_code
			) );
			if (!$card) {
				continue;
			}
			$pack = $card->getPack ();
			if (! $latestPack) {
				$latestPack = $pack;
			} else {
				if ($latestPack->getCycle ()->getPosition () < $pack->getCycle ()->getPosition ()) {
					$latestPack = $pack;
				} else {
					if ($latestPack->getCycle ()->getPosition () == $pack->getCycle ()->getPosition () && $latestPack->getPosition () < $pack->getPosition ()) {
						$latestPack = $pack;
					}
				}
			}
			$cards [$card_code] = $card;
		}
		$deck->setLastPack ( $latestPack );
		if (empty ( $tags )) {
			// tags can never be empty. if it is we put faction in
			$tags = [  ];
		}
		if(is_string($tags))
		{
			$tags = preg_split( '/\s+/', $tags );
		}
		$tags = implode(' ', array_unique(array_values($tags)));
		$deck->setTags ( $tags );
		$this->doctrine->persist ( $deck );

		// on the deck content
		if ($source_deck) {
			// compute diff between current content and saved content
			list ( $listings ) = $this->diff->diffContents ( array (
					$content,
					$source_deck->getSlots()->getContent()
			) );

			$has_meta_change = false;
			// Look for changes to customized cards in the meta on this upgrade.
			// You aren't allowed to remove any, so we ignore those for now.
			if ($meta) {
				$old_meta = json_decode($source_deck->getMeta(), true);
				$new_meta = json_decode($meta, true);
				$meta_add = json_decode("{}", true);
				$meta_remove = json_decode("{}", true);
				if ($old_meta != $new_meta) {
					foreach ($new_meta as $key => $value) {
						if (substr($key, 0, 4) == "cus_") {
							$new_value = explode(',', $value);
							if (isset($old_meta[$key])) {
								$old_value = explode(',', $old_meta[$key]);
								$added_entries = array_diff($new_value, $old_value);
								if (count($added_entries)) {
									$meta_add[substr($key, 4)] = implode(',', $added_entries);
									$has_meta_change = true;
								}
								$removed_entries = array_diff($old_value, $new_value);
								if (count($removed_entries)) {
									$meta_remove[substr($key, 4)] = implode(',', $removed_entries);
									$has_meta_change = true;
								}
							} else {
								$meta_add[substr($key, 4)] = $value;
							}
						}
					}
					if ($old_meta) {
						foreach ($old_meta as $key => $value) {
							if (substr($key, 0, 4) == "cus_") {
								if (!isset($new_meta[$key])) {
									$meta_remove[substr($key, 4)] = $value;
								}
							}
						}
					}
				}
				$listings[2] = $meta_add;
				$listings[3] = $meta_remove;
			}

			// remove all change (autosave) since last deck update (changes are sorted)
			$changes = $this->getUnsavedChanges ( $deck );
			foreach ( $changes as $change ) {
				$this->doctrine->remove ( $change );
			}
			$this->doctrine->flush ();
			// save new change unless empty
			if (count ( $listings [0] ) || count ( $listings [1] ) || $has_meta_change) {
				$change = new Deckchange ();
				$change->setMeta ( $meta );
				$change->setDeck ( $deck );
				$change->setVariation ( json_encode ( $listings ) );
				$change->setIsSaved ( TRUE );
				$change->setVersion($deck->getVersion());
				$this->doctrine->persist ( $change );
				$this->doctrine->flush ();
			}
			// copy version
			$deck->setMajorVersion($source_deck->getMajorVersion());
			$deck->setMinorVersion($source_deck->getMinorVersion());

		}
		$deck->setMeta ( $meta );
		foreach ( $deck->getSlots () as $slot ) {
			$deck->removeSlot ( $slot );
			$this->doctrine->remove ( $slot );
		}

		foreach ( $content as $card_code => $qty ) {
			$card = $cards [$card_code];
			if (!$card) {
				continue;
			}
			$slot = new Deckslot ();
			$slot->setQuantity ( $qty );
			$slot->setCard ( $card );
			$slot->setDeck ( $deck );
			$slot->setIgnoreDeckLimit(0);
			if ($ignored && isset($ignored[$card_code]) && $ignored[$card_code] > 0){
				$slot->setIgnoreDeckLimit($ignored[$card_code]);
			}
			$deck->addSlot ( $slot );
			$deck_content [$card_code] = array (
					'card' => $card,
					'qty' => $qty
			);
		}

		if (is_array($side)) {
			foreach ( $deck->getSideSlots () as $slot ) {
				$deck->removeSideSlot ( $slot );
				$this->doctrine->remove ( $slot );
			}
			foreach ( $side as $card_code => $qty ) {
				$card = $this->doctrine->getRepository ( 'AppBundle:Card' )->findOneBy ( array (
						"code" => $card_code
				) );
				if (! $card) {
					continue;
				}
				$slot = new SideDeckslot ();
				$slot->setQuantity ( $qty );
				$slot->setCard ( $card );
				$slot->setDeck ( $deck );
				$deck->addSideSlot ( $slot );
			}
		}
		if ($problem){
			$deck->setProblem($problem);
		} else {
			$deck->setProblem($this->deck_validation_helper->findProblem($deck));
			//$deck->setProblem(null);
		}

		return $deck->getId ();
	}

public function upgradeDeck($deck, $xp, $previous_deck, $upgrades, $exiles)
	{

		$deck->setXp ( $xp + $previous_deck->getXpAdjustment());
		$deck->setPreviousDeck ( $previous_deck );
		$deck->setUpgrades ( $upgrades+1 );
		$deck->setDescriptionMd ( $previous_deck->getDescriptionMd() );

		// if any cards exiled, remove them from the deck
		foreach ( $exiles as $exile ) {
			foreach ( $deck->getSlots () as $slot ) {
				if ($slot->getCard()->getCode() == $exile->getCode()){
					if ($slot->getQuantity() <= 1){
						$deck->removeSlot ( $slot );
						$this->doctrine->remove ( $slot );
					} else {
						$slot->setQuantity ( 1 );
					}
					break;
				}
			}
		}

		$previous_deck->setNextDeck($deck);
		$this->doctrine->persist ( $deck );

		return $deck->getId ();
	}


	public function revertDeck($deck)
	{
		$changes = $this->getUnsavedChanges ( $deck );
		foreach ( $changes as $change ) {
			$this->doctrine->remove ( $change );
		}
		$this->doctrine->flush ();
	}

	public function getUnsavedChanges($deck)
	{
		return $this->doctrine->getRepository ( 'AppBundle:Deckchange' )->findBy ( array (
				'deck' => $deck,
				'isSaved' => FALSE
		) );
	}
}
