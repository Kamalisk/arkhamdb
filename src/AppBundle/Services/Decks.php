<?php

namespace AppBundle\Services;

use Doctrine\ORM\EntityManager;
use AppBundle\Services\Judge;
use AppBundle\Entity\Deck;
use AppBundle\Entity\Deckslot;
use Symfony\Bridge\Monolog\Logger;
use AppBundle\Entity\Deckchange;

class Decks
{
	public function __construct(EntityManager $doctrine, Judge $judge, Diff $diff, Logger $logger)
	{
		$this->doctrine = $doctrine;
		$this->judge = $judge;
		$this->diff = $diff;
		$this->logger = $logger;
	}

	public function getDeckContent($deck)
	{
		$arr = array ();
		foreach ( $deck->getSlots () as $slot ) {
			$arr [$slot->getCard ()->getCode ()] = $slot->getQuantity ();
		}
		ksort ( $arr );
		return $arr;
	}

	public function getDeckAgenda($deck)
	{
		foreach ( $deck->getSlots () as $slot ) {
			if($slot->getCard()->getType()->getCode() === 'agenda') {
				return $slot->getCard();
			}
		}
	}

	public function getByUser($user, $decode_variation = FALSE)
	{
		$dbh = $this->doctrine->getConnection ();
		$ids = $dbh->executeQuery ( "SELECT
				d.id
				from deck d
				where d.user_id=?
				order by date_update desc", array (
				$user->getId ()
		) );

		$decks = [ ];
		while ( ($id = $ids->fetchColumn ( 0 )) !== FALSE ) {
			$decks [$id] = $this->getDeckInfo( $id, $decode_variation );
		}

		return $decks;
	}

	/**
	 * outputs an array with the deck info to give to app.deck.js
	 * @param integer $deck_id
	 * @param boolean $decode_variation
	 * @return array
	 */
	public function getDeckInfo($deck_id, $decode_variation = FALSE)
	{
		$dbh = $this->doctrine->getConnection ();

		$rows = $dbh->executeQuery ( "SELECT
				d.id,
				d.name,
				DATE_FORMAT(d.date_creation, '%Y-%m-%dT%TZ') date_creation,
                DATE_FORMAT(d.date_update, '%Y-%m-%dT%TZ') date_update,
                d.description_md,
                d.tags,
                d.user_id,
        		f.code faction_code,
        		f.name faction_name,
                (select count(*) from deckchange c where c.deck_id=d.id and c.is_saved=0) unsaved
				from deck d
        		join faction f on d.faction_id=f.id
				where d.id=?
				", array (
				$deck_id
		) )->fetchAll ();

		$deck = $rows [0];
		$deck['agenda_code'] = null;
		
		$rows = $dbh->executeQuery ( "SELECT
				c.code,
				t.code type_code,
				s.quantity
				from deckslot s
				join card c on s.card_id=c.id
				join type t on c.type_id=t.id
				where s.deck_id=?", array (
				$deck_id
		) )->fetchAll ();

		$cards = [ ];
		foreach ( $rows as $row ) {
			$cards [$row ['code']] = intval ( $row ['quantity'] );
			if($row['type_code'] === 'agenda') {
				$deck['agenda_code'] = $row['code'];
			}
		}

		$snapshots = [ ];

		$rows = $dbh->executeQuery ( "SELECT
				DATE_FORMAT(c.date_creation, '%Y-%m-%dT%TZ') date_creation,
				c.variation,
                c.is_saved
				from deckchange c
				where c.deck_id=? and c.is_saved=1
                order by date_creation desc", array (
				$deck_id
		) )->fetchAll ();

		// recreating the versions with the variation info, starting from $preversion
		$preversion = $cards;
		foreach ( $rows as $row ) {
			$row ['variation'] = $variation = json_decode ( $row ['variation'], TRUE );
			$row ['is_saved'] = ( boolean ) $row ['is_saved'];
			// add preversion with variation that lead to it
			$row ['content'] = $preversion;
			array_unshift ( $snapshots, $row );

			// applying variation to create 'next' (older) preversion
			foreach ( $variation [0] as $code => $qty ) {
				$preversion [$code] = $preversion [$code] - $qty;
				if ($preversion [$code] == 0)
					unset ( $preversion [$code] );
			}
			foreach ( $variation [1] as $code => $qty ) {
				if (! isset ( $preversion [$code] ))
					$preversion [$code] = 0;
				$preversion [$code] = $preversion [$code] + $qty;
			}
			ksort ( $preversion );
		}

		// add last know version with empty diff
		$row ['content'] = $preversion;
		$row ['date_creation'] = $deck ['date_creation'];
		$row ['saved'] = TRUE;
		$row ['variation'] = null;
		array_unshift ( $snapshots, $row );

		$rows = $dbh->executeQuery ( "SELECT
				DATE_FORMAT(c.date_creation, '%Y-%m-%dT%TZ') date_creation,
				c.variation,
                c.is_saved
				from deckchange c
				where c.deck_id=? and c.is_saved=0
                order by date_creation asc", array (
				$deck_id
		) )->fetchAll ();

		// recreating the snapshots with the variation info, starting from $postversion
		$postversion = $cards;
		foreach ( $rows as $row ) {
			$row ['variation'] = $variation = json_decode ( $row ['variation'], TRUE );
			$row ['is_saved'] = ( boolean ) $row ['is_saved'];
			// applying variation to postversion
			foreach ( $variation [0] as $code => $qty ) {
				if (! isset ( $postversion [$code] ))
					$postversion [$code] = 0;
				$postversion [$code] = $postversion [$code] + $qty;
			}
			foreach ( $variation [1] as $code => $qty ) {
				$postversion [$code] = $postversion [$code] - $qty;
				if ($postversion [$code] == 0)
					unset ( $postversion [$code] );
			}
			ksort ( $postversion );

			// add postversion with variation that lead to it
			$row ['content'] = $postversion;
			array_push ( $snapshots, $row );
		}

		// current deck is newest snapshot
		$deck ['slots'] = $postversion;

		$deck ['history'] = $snapshots;

		$deck['problem'] = "";

		return $deck;
	}

	public function getById($deck_id, $decode_variation = FALSE)
	{
		$dbh = $this->doctrine->getConnection ();
		$deck = $dbh->executeQuery ( "SELECT
				d.id,
				d.name,
				DATE_FORMAT(d.date_creation, '%Y-%m-%dT%TZ') datecreation,
				DATE_FORMAT(d.date_update, '%Y-%m-%dT%TZ') dateupdate,
				d.description_md,
                d.tags,
                (select count(*) from deckchange c where c.deck_id=d.id and c.is_saved=0) unsaved,
                d.problem,
				f.code faction_code
				from deck d
				left join faction f on d.faction_id=f.id
				where d.id=?", array (
				$deck_id
		) )->fetch ();

		$deck ['id'] = intval ( $deck ['id'] );

		$rows = $dbh->executeQuery ( "SELECT
				c.code card_code,
				s.quantity qty
				from deckslot s
				join card c on s.card_id=c.id
				join deck d on s.deck_id=d.id
				where d.id=?", array (
				$deck_id
		) )->fetchAll ();

		$cards = [ ];
		foreach ( $rows as $row ) {
			$row ['qty'] = intval ( $row ['qty'] );
			$cards [] = $row;
		}
		$deck ['cards'] = $cards;

		$rows = $dbh->executeQuery ( "SELECT
				DATE_FORMAT(c.date_creation, '%Y-%m-%dT%TZ') datecreation,
				c.variation
				from deckchange c
				where c.deck_id=? and c.is_saved=1
                order by datecreation desc", array (
				$deck_id
		) )->fetchAll ();

		$changes = [ ];
		foreach ( $rows as $row ) {
			if ($decode_variation)
				$row ['variation'] = json_decode ( $row ['variation'], TRUE );
			$changes [] = $row;
		}
		$deck ['history'] = $changes;

		$deck ['tags'] = $deck ['tags'] ? explode ( ' ', $deck ['tags'] ) : [ ];
		$problem = $deck ['problem'];
		$deck ['message'] = isset ( $problem ) ? $this->judge->problem ( $problem ) : '';

		return $deck;
	}

	public function saveDeck($user, $deck, $decklist_id, $name, $faction, $description, $tags, $content, $source_deck)
	{
		$deck_content = [ ];

		if ($decklist_id) {
			$decklist = $this->doctrine->getRepository ( 'AppBundle:Decklist' )->find ( $decklist_id );
			if ($decklist)
				$deck->setParent ( $decklist );
		}

		$deck->setName ( $name );
		$deck->setFaction ( $faction );
		$deck->setDescriptionMd ( $description );
		$deck->setUser ( $user );
		$cards = [ ];
		/* @var $latestPack \AppBundle\Entity\Pack */
		$latestPack = null;
		foreach ( $content as $card_code => $qty ) {
			$card = $this->doctrine->getRepository ( 'AppBundle:Card' )->findOneBy ( array (
					"code" => $card_code
			) );
			if (! $card)
				continue;
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
			$tags = array (
					$faction->getCode()
			);
		}
		if (is_array ( $tags )) {
			$tags = implode ( ' ', $tags );
		}
		$deck->setTags ( $tags );
		$this->doctrine->persist ( $deck );

		// on the deck content

		if ($source_deck) {
			// compute diff between current content and saved content
			list ( $listings ) = $this->diff->diffContents ( array (
					$content,
					$this->getDeckContent ( $source_deck )
			) );
			// remove all change (autosave) since last deck update (changes are sorted)
			$changes = $this->getUnsavedChanges ( $deck );
			foreach ( $changes as $change ) {
				$this->doctrine->remove ( $change );
			}
			$this->doctrine->flush ();
			// save new change unless empty
			if (count ( $listings [0] ) || count ( $listings [1] )) {
				$change = new Deckchange ();
				$change->setDeck ( $deck );
				$change->setVariation ( json_encode ( $listings ) );
				$change->setIsSaved ( TRUE );
				$this->doctrine->persist ( $change );
				$this->doctrine->flush ();
			}
		}
		foreach ( $deck->getSlots () as $slot ) {
			$deck->removeSlot ( $slot );
			$this->doctrine->remove ( $slot );
		}

		foreach ( $content as $card_code => $qty ) {
			$card = $cards [$card_code];
			$slot = new Deckslot ();
			$slot->setQuantity ( $qty );
			$slot->setCard ( $card );
			$slot->setDeck ( $deck );
			$deck->addSlot ( $slot );
			$deck_content [$card_code] = array (
					'card' => $card,
					'qty' => $qty
			);
		}
		/*
		 * $analyse = $this->judge->analyse($deck_content);
		 * if (is_string($analyse)) {
		 * $deck->setProblem($analyse);
		 * } else {
		 * $deck->setProblem(NULL);
		 * $deck->setDeckSize($analyse['deckSize']);
		 * $deck->setInfluenceSpent($analyse['influenceSpent']);
		 * $deck->setAgendaPoints($analyse['agendaPoints']);
		 * }
		 */
		$this->doctrine->flush ();

		return $deck->getId ();
	}

	public function revertDeck($deck)
	{
		$changes = $this->getUnsavedChanges ( $deck );
		foreach ( $changes as $change ) {
			$this->doctrine->remove ( $change );
		}
		// if deck has only one card and it's an agenda, we delete it
		if(count($deck->getSlots()) === 0 || (
			count($deck->getSlots()) === 1 && $this->getDeckAgenda($deck)
		) ) {
			$this->doctrine->remove($deck);
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
